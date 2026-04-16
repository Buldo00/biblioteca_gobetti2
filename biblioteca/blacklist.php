<?php
// ============================================================
// BLACKLIST.PHP – Gestione blacklist utenti (bibliotecari+)
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

$errore = '';

// ── Aggiunta in blacklist ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'aggiungi') {
    checkCsrf();

    $idTarget = (int)$_POST['id_utente_target'];
    $motivo   = sanitizeString($_POST['motivo'] ?? '', 500);
    $dataFine = !empty($_POST['data_fine']) ? $_POST['data_fine'] : null;

    if ($idTarget === $idUtente) {
        $errore = 'Non puoi aggiungere te stesso alla blacklist.';
    } else {
        // Controlla il livello dell'utente da blacklistare
        $livelloTarget = getUserLevel($pdo, $idTarget);
        if (canManageBooks($livelloTarget)) {
            $errore = 'Non puoi aggiungere un bibliotecario o superiore alla blacklist.';
        } else {
            // Disattiva eventuale blacklist precedente
            $pdo->prepare(
                "UPDATE bib_blacklist SET attiva = 0 WHERE id_utente = ? AND attiva = 1"
            )->execute([$idTarget]);

            $pdo->prepare(
                "INSERT INTO bib_blacklist (id_utente, motivo, data_fine, attiva)
                 VALUES (?, ?, ?, 1)"
            )->execute([$idTarget, $motivo, $dataFine]);

            logOperazione($pdo, $idUtente, 'aggiungi_blacklist',
                "Utente ID {$idTarget}: {$motivo}", $idTarget, 'utente');
            redirectWith('blacklist.php', 'success', 'Utente aggiunto alla blacklist.');
        }
    }
}

// ── Rimozione da blacklist ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rimuovi') {
    checkCsrf();

    $idBl = (int)$_POST['id_blacklist'];
    $pdo->prepare("UPDATE bib_blacklist SET attiva = 0, data_fine = NOW() WHERE id = ?")
        ->execute([$idBl]);

    logOperazione($pdo, $idUtente, 'rimuovi_blacklist',
        "Blacklist ID {$idBl}", $idBl, 'blacklist');
    redirectWith('blacklist.php', 'success', 'Utente rimosso dalla blacklist.');
}

// ── Carica blacklist attive ────────────────────────────────
$blacklisted = $pdo->query(
    "SELECT bl.*, u.emailUtente,
            s.nomeStu, s.cognomeStu
     FROM bib_blacklist bl
     JOIN utenti u ON u.IDUtente = bl.id_utente
     LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
     WHERE bl.attiva = 1 AND (bl.data_fine IS NULL OR bl.data_fine > NOW())
     ORDER BY bl.data_inizio DESC"
)->fetchAll();

// ── Storico blacklist ─────────────────────────────────────
$storico = $pdo->query(
    "SELECT bl.*, u.emailUtente,
            s.nomeStu, s.cognomeStu
     FROM bib_blacklist bl
     JOIN utenti u ON u.IDUtente = bl.id_utente
     LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
     WHERE bl.attiva = 0
     ORDER BY bl.data_inizio DESC
     LIMIT 50"
)->fetchAll();

// ── Lista utenti per il form (studenti e docenti) ──────────
$utenti = $pdo->query(
    "SELECT u.IDUtente, u.emailUtente, s.nomeStu, s.cognomeStu,
            MAX(t.livelloAccount) AS livello
     FROM utenti u
     LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
     LEFT JOIN utenti_tipolivelli ul ON ul.idUtente = u.IDUtente
     LEFT JOIN tipolivelli t ON t.IDTipoAccount = ul.idLivello
     WHERE u.statoUtente = 1
     GROUP BY u.IDUtente
     HAVING livello <= 300 OR livello IS NULL
     ORDER BY s.cognomeStu, s.nomeStu, u.emailUtente"
)->fetchAll();

$pageTitle = 'Blacklist';
require_once __DIR__ . '/includes/header.php';
?>
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>
<?php showFlash(); ?>

<div class="librarian-header">
    <h2 class="mb-0"><i class="bi bi-person-x me-2"></i>Gestione Blacklist</h2>
</div>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Form aggiunta -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="bi bi-person-dash me-1"></i>Aggiungi alla blacklist</h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action" value="aggiungi">

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Utente *</label>
                        <select class="form-select form-select-sm" name="id_utente_target" required>
                            <option value="">Seleziona utente…</option>
                            <?php foreach ($utenti as $u): ?>
                                <?php
                                $nomeU = !empty($u['nomeStu'])
                                    ? htmlspecialchars($u['cognomeStu'] . ' ' . $u['nomeStu'])
                                    : htmlspecialchars($u['emailUtente']);
                                ?>
                                <option value="<?= $u['IDUtente'] ?>"><?= $nomeU ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Motivo</label>
                        <textarea class="form-control form-control-sm" name="motivo" rows="2"
                                  maxlength="500"
                                  placeholder="Es. 3 prenotazioni non ritirate"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Data fine (opzionale)</label>
                        <input type="date" class="form-control form-control-sm" name="data_fine"
                               min="<?= date('Y-m-d') ?>">
                        <small class="text-muted">Lascia vuoto per blacklist permanente</small>
                    </div>

                    <button type="submit" class="btn btn-danger btn-sm w-100">
                        <i class="bi bi-person-x me-1"></i>Aggiungi alla blacklist
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Blacklist attive -->
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-danger text-white d-flex justify-content-between">
                <span><i class="bi bi-slash-circle me-1"></i>Utenti in blacklist attiva</span>
                <span class="badge bg-light text-dark"><?= count($blacklisted) ?></span>
            </div>
            <?php if (empty($blacklisted)): ?>
                <div class="card-body text-muted text-center py-3">
                    Nessun utente in blacklist.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-biblioteca mb-0">
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Motivo</th>
                            <th>Dal</th>
                            <th>Scadenza</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blacklisted as $bl): ?>
                        <tr class="blacklist-row">
                            <td>
                                <?php if (!empty($bl['nomeStu'])): ?>
                                    <strong><?= htmlspecialchars($bl['cognomeStu'] . ' ' . $bl['nomeStu']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($bl['emailUtente']) ?></small>
                                <?php else: ?>
                                    <?= htmlspecialchars($bl['emailUtente']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= nl2br(htmlspecialchars($bl['motivo'] ?? '—')) ?></td>
                            <td class="small"><?= formatData($bl['data_inizio']) ?></td>
                            <td class="small">
                                <?= $bl['data_fine'] ? formatData($bl['data_fine']) : '<em>Permanente</em>' ?>
                            </td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                    <input type="hidden" name="action" value="rimuovi">
                                    <input type="hidden" name="id_blacklist" value="<?= $bl['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2"
                                            data-confirm="Rimuovere questo utente dalla blacklist?">
                                        <i class="bi bi-person-check me-1"></i>Rimuovi
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Storico -->
        <?php if (!empty($storico)): ?>
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <span><i class="bi bi-clock-history me-1"></i>Storico (ultime 50 voci)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-biblioteca mb-0">
                    <thead>
                        <tr>
                            <th>Utente</th>
                            <th>Motivo</th>
                            <th>Dal</th>
                            <th>Al</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($storico as $bl): ?>
                        <tr class="text-muted">
                            <td class="small">
                                <?php if (!empty($bl['nomeStu'])): ?>
                                    <?= htmlspecialchars($bl['cognomeStu'] . ' ' . $bl['nomeStu']) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($bl['emailUtente']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars(mb_substr($bl['motivo'] ?? '—', 0, 60)) ?></td>
                            <td class="small"><?= formatData($bl['data_inizio']) ?></td>
                            <td class="small"><?= formatData($bl['data_fine']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
