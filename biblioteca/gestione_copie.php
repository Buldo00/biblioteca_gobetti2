<?php
// ============================================================
// GESTIONE_COPIE.PHP – Gestione copie fisiche (bibliotecari+)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

if (!canManageBooks($userLevel)) {
    redirectWith('index.php', 'danger', 'Accesso non autorizzato.');
}

$idLibro = (int)($_GET['id_libro'] ?? 0);
if (!$idLibro) {
    redirectWith('gestione_libri.php', 'warning', 'Seleziona un libro.');
}

// Carica libro
$stmt = $pdo->prepare("SELECT * FROM bib_libri WHERE id = ?");
$stmt->execute([$idLibro]);
$libro = $stmt->fetch();
if (!$libro) {
    redirectWith('gestione_libri.php', 'danger', 'Libro non trovato.');
}

$errore = '';

// ── Elimina copia ──────────────────────────────────────────
if (isset($_GET['delete_copia']) && is_numeric($_GET['delete_copia'])) {
    if (!isset($_GET['csrf']) || $_GET['csrf'] !== getCsrfToken()) {
        $errore = 'Token CSRF non valido.';
    } else {
        $idCopia = (int)$_GET['delete_copia'];
        // Controlla se la copia è in prestito
        $stmtChk = $pdo->prepare(
            "SELECT stato FROM bib_copie WHERE id = ? AND id_libro = ?"
        );
        $stmtChk->execute([$idCopia, $idLibro]);
        $copiaRow = $stmtChk->fetch();
        if ($copiaRow && in_array($copiaRow['stato'], ['in_prestito', 'prenotato'])) {
            $errore = 'Impossibile eliminare: la copia è attualmente prenotata o in prestito.';
        } else {
            $pdo->prepare("DELETE FROM bib_copie WHERE id = ? AND id_libro = ?")
                ->execute([$idCopia, $idLibro]);
            logOperazione($pdo, $idUtente, 'elimina_copia',
                "Copia ID {$idCopia} del libro ID {$idLibro}", $idCopia, 'copia');
            redirectWith("gestione_copie.php?id_libro={$idLibro}", 'success', 'Copia eliminata.');
        }
    }
}

// ── Modifica stato/posizione copia ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_copia'])) {
    checkCsrf();

    $idCopia    = (int)$_POST['id_copia'];
    $action     = $_POST['action_copia'];

    if ($action === 'modifica') {
        $armadio    = sanitizeString($_POST['armadio']    ?? '', 50);
        $ripiano    = sanitizeString($_POST['ripiano']    ?? '', 50);
        $stato      = in_array($_POST['stato'] ?? '', ['disponibile','prenotato','in_prestito','danneggiato','smarrito'])
                      ? $_POST['stato'] : 'disponibile';
        $noteDanno  = sanitizeString($_POST['note_danno'] ?? '', 500);

        $pdo->prepare(
            "UPDATE bib_copie SET armadio=?, ripiano=?, stato=?, note_danno=? WHERE id=? AND id_libro=?"
        )->execute([$armadio, $ripiano, $stato, $noteDanno, $idCopia, $idLibro]);

        logOperazione($pdo, $idUtente, 'modifica_copia',
            "Copia ID {$idCopia}: stato={$stato}", $idCopia, 'copia');
        redirectWith("gestione_copie.php?id_libro={$idLibro}", 'success', 'Copia aggiornata.');
    }
}

// ── Aggiungi nuova copia ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_nuova_copia'])) {
    checkCsrf();

    $armadio = sanitizeString($_POST['armadio'] ?? '', 50);
    $ripiano = sanitizeString($_POST['ripiano'] ?? '', 50);

    // Calcola prossimo numero copia
    $stmtMax = $pdo->prepare("SELECT MAX(numero_copia) FROM bib_copie WHERE id_libro = ?");
    $stmtMax->execute([$idLibro]);
    $maxNum     = (int)$stmtMax->fetchColumn();
    $nextNum    = $maxNum + 1;
    $qrCode     = generaQrCode($idLibro, $nextNum);

    $pdo->prepare(
        "INSERT INTO bib_copie (id_libro, numero_copia, qr_code, armadio, ripiano, stato)
         VALUES (?, ?, ?, ?, ?, 'disponibile')"
    )->execute([$idLibro, $nextNum, $qrCode, $armadio, $ripiano]);

    $newId = $pdo->lastInsertId();
    logOperazione($pdo, $idUtente, 'aggiungi_copia',
        "Copia #{$nextNum} libro ID {$idLibro}", $newId, 'copia');
    redirectWith("gestione_copie.php?id_libro={$idLibro}", 'success',
        "Copia #{$nextNum} aggiunta. QR: {$qrCode}");
}

// ── Carica copie ───────────────────────────────────────────
$stmtCopie = $pdo->prepare(
    "SELECT c.*,
            p.id AS id_pren, p.data_scadenza AS pren_scadenza,
            u.emailUtente AS utente_prenotato
     FROM bib_copie c
     LEFT JOIN bib_prenotazioni p ON p.id_copia = c.id AND p.stato IN ('in_attesa','confermata')
     LEFT JOIN utenti u ON u.IDUtente = p.id_utente
     WHERE c.id_libro = ?
     ORDER BY c.numero_copia"
);
$stmtCopie->execute([$idLibro]);
$copie = $stmtCopie->fetchAll();

// Copia da modificare (inline via GET)
$copiaEdit = null;
if (isset($_GET['edit_copia']) && is_numeric($_GET['edit_copia'])) {
    foreach ($copie as $c) {
        if ($c['id'] === (int)$_GET['edit_copia']) {
            $copiaEdit = $c;
            break;
        }
    }
}

$pageTitle = 'Gestione Copie: ' . $libro['titolo'];
require_once __DIR__ . '/includes/header.php';
?>
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>
<?php showFlash(); ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="gestione_libri.php">Gestione Libri</a></li>
        <li class="breadcrumb-item"><a href="libro.php?id=<?= $idLibro ?>"><?= htmlspecialchars($libro['titolo']) ?></a></li>
        <li class="breadcrumb-item active">Copie</li>
    </ol>
</nav>

<div class="librarian-header">
    <h2 class="mb-0">
        <i class="bi bi-stack me-2"></i>Gestione Copie
        <small class="fs-6 ms-2 opacity-75">– <?= htmlspecialchars($libro['titolo']) ?></small>
    </h2>
</div>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Aggiunta nuova copia -->
    <div class="col-lg-3">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="bi bi-plus-circle me-1"></i>Aggiungi copia</h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action_nuova_copia" value="1">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Armadio</label>
                        <input type="text" class="form-control form-control-sm" name="armadio"
                               placeholder="Es. A1" maxlength="50">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ripiano</label>
                        <input type="text" class="form-control form-control-sm" name="ripiano"
                               placeholder="Es. 2" maxlength="50">
                    </div>
                    <p class="small text-muted">Il QR code verrà generato automaticamente.</p>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus me-1"></i>Aggiungi copia
                    </button>
                </form>
            </div>
        </div>

        <!-- Form modifica copia -->
        <?php if ($copiaEdit): ?>
        <div class="card shadow-sm border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-pencil me-1"></i>Modifica copia #<?= $copiaEdit['numero_copia'] ?></h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token"  value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action_copia" value="modifica">
                    <input type="hidden" name="id_copia"    value="<?= $copiaEdit['id'] ?>">

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Armadio</label>
                        <input type="text" class="form-control form-control-sm" name="armadio"
                               value="<?= htmlspecialchars($copiaEdit['armadio'] ?? '') ?>" maxlength="50">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Ripiano</label>
                        <input type="text" class="form-control form-control-sm" name="ripiano"
                               value="<?= htmlspecialchars($copiaEdit['ripiano'] ?? '') ?>" maxlength="50">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Stato</label>
                        <select class="form-select form-select-sm" name="stato">
                            <?php foreach (['disponibile','prenotato','in_prestito','danneggiato','smarrito'] as $s): ?>
                                <option value="<?= $s ?>"
                                    <?= $copiaEdit['stato'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Note danno</label>
                        <textarea class="form-control form-control-sm" name="note_danno" rows="2"
                                  maxlength="500"><?= htmlspecialchars($copiaEdit['note_danno'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning btn-sm flex-grow-1">
                            <i class="bi bi-save me-1"></i>Salva
                        </button>
                        <a href="gestione_copie.php?id_libro=<?= $idLibro ?>"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabella copie -->
    <div class="col-lg-9">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-biblioteca">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>QR Code</th>
                        <th>Armadio</th>
                        <th>Ripiano</th>
                        <th>Stato</th>
                        <th>Prenotato da</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($copie)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                Nessuna copia registrata. Aggiungine una.
                            </td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($copie as $copia): ?>
                    <tr>
                        <td><?= $copia['numero_copia'] ?></td>
                        <td>
                            <code class="small"><?= htmlspecialchars($copia['qr_code']) ?></code>
                        </td>
                        <td><?= htmlspecialchars($copia['armadio'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($copia['ripiano'] ?? '—') ?></td>
                        <td><?= statoCopiaLabel($copia['stato']) ?></td>
                        <td class="small">
                            <?php if ($copia['utente_prenotato']): ?>
                                <?= htmlspecialchars($copia['utente_prenotato']) ?>
                                <?php if ($copia['pren_scadenza']): ?>
                                    <br><span class="text-muted">Scade: <?= formatData($copia['pren_scadenza']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <a href="gestione_copie.php?id_libro=<?= $idLibro ?>&edit_copia=<?= $copia['id'] ?>"
                               class="btn btn-sm btn-outline-warning py-0 px-1 me-1" title="Modifica">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="etichetta_pdf.php?id_copia=<?= $copia['id'] ?>"
                               class="btn btn-sm btn-outline-secondary py-0 px-1 me-1"
                               title="Stampa etichetta" target="_blank">
                                <i class="bi bi-printer"></i>
                            </a>
                            <a href="gestione_copie.php?id_libro=<?= $idLibro ?>&delete_copia=<?= $copia['id'] ?>&csrf=<?= getCsrfToken() ?>"
                               class="btn btn-sm btn-outline-danger py-0 px-1"
                               title="Elimina"
                               data-confirm="Eliminare la copia #<?= $copia['numero_copia'] ?>?">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Link stampa tutte etichette -->
        <?php if (!empty($copie)): ?>
        <div class="mt-2">
            <a href="etichetta_pdf.php?id_libro=<?= $idLibro ?>"
               class="btn btn-outline-secondary btn-sm" target="_blank">
                <i class="bi bi-printer me-1"></i>Stampa tutte le etichette
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<footer class="mt-5 py-3 text-center text-muted small border-top">
    Biblioteca Gobetti &copy; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/biblioteca.js"></script>
</body>
</html>
