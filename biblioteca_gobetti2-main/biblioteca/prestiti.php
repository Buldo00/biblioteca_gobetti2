<?php
// ============================================================
// PRESTITI.PHP – Gestione prestiti e prenotazioni
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

aggiornaPrenotazioniScadute($pdo);
aggiornaPrestitiScaduti($pdo);

$isAll    = isset($_GET['all']) && canManageBooks($userLevel);
$isClasse = isset($_GET['classe']) && canBookForClass($userLevel);

// ── Azioni POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    // Annulla prenotazione (utente stesso o bibliotecario)
    if ($action === 'annulla_pren') {
        $idPren = (int)$_POST['id_prenotazione'];
        $stmt   = $pdo->prepare("SELECT * FROM bib_prenotazioni WHERE id = ?");
        $stmt->execute([$idPren]);
        $pren = $stmt->fetch();

        if ($pren && ($pren['id_utente'] === $idUtente || canManageBooks($userLevel))
                  && in_array($pren['stato'], ['in_attesa', 'confermata'])) {
            $pdo->prepare("UPDATE bib_prenotazioni SET stato='annullata' WHERE id = ?")
                ->execute([$idPren]);
            // Libera la copia
            if ($pren['id_copia']) {
                $pdo->prepare("UPDATE bib_copie SET stato='disponibile' WHERE id = ?")
                    ->execute([$pren['id_copia']]);
            }
            logOperazione($pdo, $idUtente, 'annulla_prenotazione',
                "Prenotazione ID {$idPren}", $idPren, 'prenotazione');
            redirectWith($_SERVER['REQUEST_URI'], 'success', 'Prenotazione annullata.');
        }
    }

    // Conferma ritiro (bibliotecario)
    if ($action === 'conferma_ritiro' && canManageBooks($userLevel)) {
        $idPren       = (int)$_POST['id_prenotazione'];
        $impostazioni = getImpostazioni($pdo);
        $giorniPrestito = (int)($impostazioni['giorni_prestito'] ?? 30);

        $stmt = $pdo->prepare("SELECT * FROM bib_prenotazioni WHERE id = ? AND stato='confermata'");
        $stmt->execute([$idPren]);
        $pren = $stmt->fetch();

        if ($pren) {
            $dataScad = date('Y-m-d H:i:s', strtotime("+{$giorniPrestito} days"));

            // Crea prestito
            $ins = $pdo->prepare(
                "INSERT INTO bib_prestiti
                 (id_utente, id_copia, id_prenotazione, data_scadenza, stato,
                  confermato_bibliotecario)
                 VALUES (?, ?, ?, ?, 'attivo', 1)"
            );
            $ins->execute([$pren['id_utente'], $pren['id_copia'], $idPren, $dataScad]);
            $idPrestito = $pdo->lastInsertId();

            // Aggiorna prenotazione e copia
            $pdo->prepare("UPDATE bib_prenotazioni SET stato='ritirata' WHERE id = ?")
                ->execute([$idPren]);
            $pdo->prepare("UPDATE bib_copie SET stato='in_prestito' WHERE id = ?")
                ->execute([$pren['id_copia']]);

            logOperazione($pdo, $idUtente, 'conferma_ritiro',
                "Prestito ID {$idPrestito}", $idPrestito, 'prestito');
            redirectWith($_SERVER['REQUEST_URI'], 'success', 'Ritiro confermato. Prestito creato.');
        }
    }

    // Conferma prenotazione (bibliotecario → da in_attesa a confermata)
    if ($action === 'conferma_pren' && canManageBooks($userLevel)) {
        $idPren       = (int)$_POST['id_prenotazione'];
        $impostazioni = getImpostazioni($pdo);
        $giorniRitiro = (int)($impostazioni['giorni_ritiro'] ?? 3);
        $dataScad     = date('Y-m-d H:i:s', strtotime("+{$giorniRitiro} days"));

        $pdo->prepare(
            "UPDATE bib_prenotazioni SET stato='confermata', data_scadenza=? WHERE id=? AND stato='in_attesa'"
        )->execute([$dataScad, $idPren]);

        logOperazione($pdo, $idUtente, 'conferma_prenotazione',
            "Prenotazione ID {$idPren}", $idPren, 'prenotazione');
        redirectWith($_SERVER['REQUEST_URI'], 'success', 'Prenotazione confermata.');
    }

    // Conferma restituzione (bibliotecario)
    if ($action === 'restituzione' && canManageBooks($userLevel)) {
        $idPrestito = (int)$_POST['id_prestito'];
        $stmt       = $pdo->prepare("SELECT * FROM bib_prestiti WHERE id = ? AND stato='attivo'");
        $stmt->execute([$idPrestito]);
        $prestito = $stmt->fetch();

        if ($prestito) {
            $pdo->prepare(
                "UPDATE bib_prestiti SET stato='restituito', data_restituzione=NOW() WHERE id = ?"
            )->execute([$idPrestito]);
            $pdo->prepare("UPDATE bib_copie SET stato='disponibile' WHERE id = ?")
                ->execute([$prestito['id_copia']]);

            logOperazione($pdo, $idUtente, 'restituzione',
                "Prestito ID {$idPrestito}", $idPrestito, 'prestito');
            redirectWith($_SERVER['REQUEST_URI'], 'success', 'Restituzione confermata.');
        }
    }
}

// ── Carica dati ────────────────────────────────────────────
$prenotazioni = [];
$prestiti     = [];

if ($isAll) {
    // Tutte le prenotazioni attive (bibliotecario)
    $prenotazioni = $pdo->query(
        "SELECT p.*, l.titolo AS titolo_libro, l.autore,
                u.emailUtente,
                s.nomeStu, s.cognomeStu,
                c.numero_copia
         FROM bib_prenotazioni p
         JOIN bib_libri l ON l.id = p.id_libro
         JOIN utenti u    ON u.IDUtente = p.id_utente
         LEFT JOIN studenti s ON s.IDUtente = p.id_utente
         LEFT JOIN bib_copie c ON c.id = p.id_copia
         WHERE p.stato IN ('in_attesa','confermata')
         ORDER BY p.data_prenotazione DESC"
    )->fetchAll();

    $prestiti = $pdo->query(
        "SELECT pt.*, l.titolo AS titolo_libro,
                c.numero_copia, c.qr_code,
                u.emailUtente,
                s.nomeStu, s.cognomeStu
         FROM bib_prestiti pt
         JOIN bib_copie c ON c.id = pt.id_copia
         JOIN bib_libri l ON l.id = c.id_libro
         JOIN utenti u   ON u.IDUtente = pt.id_utente
         LEFT JOIN studenti s ON s.IDUtente = pt.id_utente
         WHERE pt.stato = 'attivo'
         ORDER BY pt.data_scadenza ASC"
    )->fetchAll();

} elseif ($isClasse) {
    // Prenotazioni di classe
    $prenotazioni = $pdo->query(
        "SELECT p.*, l.titolo AS titolo_libro, l.autore,
                u.emailUtente,
                s.nomeStu, s.cognomeStu,
                cl.anno, cl.sezione,
                c.numero_copia
         FROM bib_prenotazioni p
         JOIN bib_libri l ON l.id = p.id_libro
         JOIN utenti u    ON u.IDUtente = p.id_utente
         LEFT JOIN studenti s ON s.IDUtente = p.id_utente
         LEFT JOIN classi cl ON cl.IDClasse = p.id_classe
         LEFT JOIN bib_copie c ON c.id = p.id_copia
         WHERE p.tipo = 'classe' AND p.stato IN ('in_attesa','confermata')
         ORDER BY p.data_prenotazione DESC"
    )->fetchAll();

} else {
    // Prenotazioni e prestiti dell'utente corrente
    $stmtPren = $pdo->prepare(
        "SELECT p.*, l.titolo AS titolo_libro, l.autore, l.id AS id_libro,
                c.numero_copia
         FROM bib_prenotazioni p
         JOIN bib_libri l ON l.id = p.id_libro
         LEFT JOIN bib_copie c ON c.id = p.id_copia
         WHERE p.id_utente = ?
         ORDER BY p.data_prenotazione DESC"
    );
    $stmtPren->execute([$idUtente]);
    $prenotazioni = $stmtPren->fetchAll();

    $stmtPrest = $pdo->prepare(
        "SELECT pt.*, l.titolo AS titolo_libro, c.numero_copia, c.qr_code
         FROM bib_prestiti pt
         JOIN bib_copie c ON c.id = pt.id_copia
         JOIN bib_libri l ON l.id = c.id_libro
         WHERE pt.id_utente = ?
         ORDER BY pt.data_prestito DESC"
    );
    $stmtPrest->execute([$idUtente]);
    $prestiti = $stmtPrest->fetchAll();
}

$pageTitle = $isAll ? 'Tutti i prestiti' : ($isClasse ? 'Prestiti di classe' : 'I miei prestiti');
require_once __DIR__ . '/includes/header.php';
?>
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>
<?php showFlash(); ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">
        <i class="bi bi-bookmark-check me-2"></i><?= htmlspecialchars($pageTitle) ?>
    </h2>
    <?php if (canManageBooks($userLevel)): ?>
    <div class="btn-group btn-group-sm">
        <a href="prestiti.php" class="btn btn-outline-primary <?= !$isAll && !$isClasse ? 'active' : '' ?>">
            I miei</a>
        <a href="prestiti.php?classe=1" class="btn btn-outline-success <?= $isClasse ? 'active' : '' ?>">
            Classe</a>
        <a href="prestiti.php?all=1" class="btn btn-outline-warning <?= $isAll ? 'active' : '' ?>">
            Tutti</a>
    </div>
    <?php endif; ?>
</div>

<!-- ── Prenotazioni ─────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock me-1"></i>Prenotazioni</span>
        <span class="badge bg-light text-dark"><?= count($prenotazioni) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover table-biblioteca mb-0">
            <thead>
                <tr>
                    <?php if ($isAll || $isClasse): ?>
                    <th>Utente</th>
                    <?php endif; ?>
                    <th>Libro</th>
                    <th>Tipo</th>
                    <?php if ($isClasse): ?><th>Classe</th><?php endif; ?>
                    <th>Stato</th>
                    <th>Prenotato il</th>
                    <th>Scadenza ritiro</th>
                    <th>Copia</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prenotazioni)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-3">
                            Nessuna prenotazione attiva.
                        </td>
                    </tr>
                <?php else: ?>
                <?php foreach ($prenotazioni as $p): ?>
                <tr>
                    <?php if ($isAll || $isClasse): ?>
                    <td class="small">
                        <?php if (!empty($p['nomeStu'])): ?>
                            <?= htmlspecialchars($p['cognomeStu'] . ' ' . $p['nomeStu']) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($p['emailUtente'] ?? '—') ?>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <a href="libro.php?id=<?= $p['id_libro'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($p['titolo_libro']) ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge <?= $p['tipo'] === 'classe' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $p['tipo'] === 'classe' ? 'Classe' : 'Personale' ?>
                        </span>
                    </td>
                    <?php if ($isClasse): ?>
                    <td><?= htmlspecialchars(($p['anno'] ?? '') . ($p['sezione'] ?? '')) ?></td>
                    <?php endif; ?>
                    <td><?= statoPrenotazioneLabel($p['stato']) ?></td>
                    <td class="small"><?= formatData($p['data_prenotazione']) ?></td>
                    <td class="small">
                        <?php if ($p['data_scadenza']): ?>
                            <?php $scad = new DateTime($p['data_scadenza']); $now = new DateTime(); ?>
                            <span class="<?= $scad < $now ? 'text-danger' : '' ?>">
                                <?= formatData($p['data_scadenza']) ?>
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small"><?= $p['numero_copia'] ? '#' . $p['numero_copia'] : '—' ?></td>
                    <td class="text-nowrap">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="id_prenotazione" value="<?= $p['id'] ?>">

                            <?php if (canManageBooks($userLevel) && $p['stato'] === 'in_attesa'): ?>
                            <button type="submit" name="action" value="conferma_pren"
                                    class="btn btn-sm btn-success py-0 px-1 me-1"
                                    title="Conferma">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <?php endif; ?>

                            <?php if (canManageBooks($userLevel) && $p['stato'] === 'confermata'): ?>
                            <button type="submit" name="action" value="conferma_ritiro"
                                    class="btn btn-sm btn-primary py-0 px-1 me-1"
                                    title="Conferma ritiro">
                                <i class="bi bi-box-arrow-in-down"></i>
                            </button>
                            <?php endif; ?>

                            <?php if (in_array($p['stato'], ['in_attesa','confermata'])
                                      && ($p['id_utente'] === $idUtente || canManageBooks($userLevel))): ?>
                            <button type="submit" name="action" value="annulla_pren"
                                    class="btn btn-sm btn-outline-danger py-0 px-1"
                                    title="Annulla"
                                    data-confirm="Annullare questa prenotazione?">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Prestiti ────────────────────────────────────────── -->
<div class="card">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-book me-1"></i>Prestiti</span>
        <span class="badge bg-light text-dark"><?= count($prestiti) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover table-biblioteca mb-0">
            <thead>
                <tr>
                    <?php if ($isAll): ?><th>Utente</th><?php endif; ?>
                    <th>Libro</th>
                    <th>Copia</th>
                    <th>Stato</th>
                    <th>Data prestito</th>
                    <th>Scadenza</th>
                    <th>Restituito</th>
                    <?php if (canManageBooks($userLevel)): ?><th>Azioni</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestiti)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">
                            Nessun prestito registrato.
                        </td>
                    </tr>
                <?php else: ?>
                <?php foreach ($prestiti as $pt):
                    $scaduto = $pt['stato'] === 'scaduto' || ($pt['data_scadenza'] && new DateTime($pt['data_scadenza']) < new DateTime() && $pt['stato'] === 'attivo');
                ?>
                <tr class="<?= $scaduto ? 'prestito-scaduto' : '' ?>">
                    <?php if ($isAll): ?>
                    <td class="small">
                        <?php if (!empty($pt['nomeStu'])): ?>
                            <?= htmlspecialchars($pt['cognomeStu'] . ' ' . $pt['nomeStu']) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($pt['emailUtente'] ?? '—') ?>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($pt['titolo_libro']) ?></td>
                    <td>#<?= $pt['numero_copia'] ?></td>
                    <td><?= statoPrestitoLabel($pt['stato']) ?></td>
                    <td class="small"><?= formatData($pt['data_prestito']) ?></td>
                    <td class="small">
                        <span class="<?= $scaduto ? 'text-danger fw-bold' : '' ?>">
                            <?= formatData($pt['data_scadenza']) ?>
                        </span>
                    </td>
                    <td class="small"><?= formatData($pt['data_restituzione']) ?></td>
                    <?php if (canManageBooks($userLevel)): ?>
                    <td>
                        <?php if ($pt['stato'] === 'attivo'): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token"  value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="id_prestito" value="<?= $pt['id'] ?>">
                            <button type="submit" name="action" value="restituzione"
                                    class="btn btn-sm btn-outline-success py-0 px-1"
                                    title="Conferma restituzione"
                                    data-confirm="Confermare la restituzione?">
                                <i class="bi bi-arrow-return-left"></i> Restituito
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<footer class="mt-5 py-3 text-center text-muted small border-top">
    Biblioteca Gobetti &copy; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/biblioteca.js"></script>
</body>
</html>
