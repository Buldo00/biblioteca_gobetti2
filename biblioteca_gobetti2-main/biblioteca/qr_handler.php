<?php
// ============================================================
// QR_HANDLER.PHP – Gestione scansione QR code copie
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

$qrCode = sanitizeString($_GET['qr'] ?? '', 100);

if ($qrCode === '') {
    redirectWith('index.php', 'warning', 'Nessun QR code specificato.');
}

// Cerca la copia corrispondente
$stmt = $pdo->prepare(
    "SELECT c.*, l.id AS id_libro, l.titolo, l.autore, l.codice_dewey, l.aula
     FROM bib_copie c
     JOIN bib_libri l ON l.id = c.id_libro
     WHERE c.qr_code = ?"
);
$stmt->execute([$qrCode]);
$copia = $stmt->fetch();

if (!$copia) {
    redirectWith('index.php', 'danger', 'QR code non riconosciuto: ' . htmlspecialchars($qrCode));
}

// Prenotazione attiva legata a questa copia
$prenAttiva = null;
$stmtPren = $pdo->prepare(
    "SELECT p.*, u.emailUtente, s.nomeStu, s.cognomeStu
     FROM bib_prenotazioni p
     JOIN utenti u ON u.IDUtente = p.id_utente
     LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
     WHERE p.id_copia = ? AND p.stato IN ('in_attesa','confermata')
     LIMIT 1"
);
$stmtPren->execute([$copia['id']]);
$prenAttiva = $stmtPren->fetch();

// Prestito attivo legato a questa copia
$prestitoAttivo = null;
$stmtPrest = $pdo->prepare(
    "SELECT pt.*, u.emailUtente, s.nomeStu, s.cognomeStu
     FROM bib_prestiti pt
     JOIN utenti u ON u.IDUtente = pt.id_utente
     LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
     WHERE pt.id_copia = ? AND pt.stato = 'attivo'
     LIMIT 1"
);
$stmtPrest->execute([$copia['id']]);
$prestitoAttivo = $stmtPrest->fetch();

// Azione rapida: restituzione via QR (solo bibliotecari)
if (isset($_POST['action_qr']) && canManageBooks($userLevel)) {
    checkCsrf();

    if ($_POST['action_qr'] === 'restituzione' && $prestitoAttivo) {
        $pdo->prepare(
            "UPDATE bib_prestiti SET stato='restituito', data_restituzione=NOW() WHERE id=?"
        )->execute([$prestitoAttivo['id']]);
        $pdo->prepare("UPDATE bib_copie SET stato='disponibile' WHERE id=?")
            ->execute([$copia['id']]);
        logOperazione($pdo, $idUtente, 'restituzione_qr',
            "Copia QR: {$qrCode}", $prestitoAttivo['id'], 'prestito');
        redirectWith("qr_handler.php?qr=" . urlencode($qrCode), 'success',
            'Restituzione confermata.');
    }

    if ($_POST['action_qr'] === 'conferma_ritiro' && $prenAttiva) {
        $imp = getImpostazioni($pdo);
        $gg  = (int)($imp['giorni_prestito'] ?? 30);
        $ds  = date('Y-m-d H:i:s', strtotime("+{$gg} days"));

        $pdo->prepare(
            "INSERT INTO bib_prestiti
             (id_utente, id_copia, id_prenotazione, data_scadenza, stato, confermato_bibliotecario)
             VALUES (?,?,?,?,'attivo',1)"
        )->execute([$prenAttiva['id_utente'], $copia['id'], $prenAttiva['id'], $ds]);

        $pdo->prepare("UPDATE bib_prenotazioni SET stato='ritirata' WHERE id=?")
            ->execute([$prenAttiva['id']]);
        $pdo->prepare("UPDATE bib_copie SET stato='in_prestito' WHERE id=?")
            ->execute([$copia['id']]);

        logOperazione($pdo, $idUtente, 'conferma_ritiro_qr',
            "Copia QR: {$qrCode}", $prenAttiva['id'], 'prenotazione');
        redirectWith("qr_handler.php?qr=" . urlencode($qrCode), 'success', 'Ritiro confermato.');
    }
}

$pageTitle = 'QR: ' . $copia['qr_code'];
require_once __DIR__ . '/includes/header.php';
?>
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>
<?php showFlash(); ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Catalogo</a></li>
        <li class="breadcrumb-item active">Scansione QR</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">

        <!-- Info copia -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-dark text-white d-flex align-items-center gap-2">
                <i class="bi bi-upc-scan fs-4"></i>
                <div>
                    <div class="fw-bold">Copia scansionata</div>
                    <code class="small"><?= htmlspecialchars($copia['qr_code']) ?></code>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <strong>Libro:</strong><br>
                        <a href="libro.php?id=<?= $copia['id_libro'] ?>">
                            <?= htmlspecialchars($copia['titolo']) ?>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <strong>Autore:</strong><br>
                        <?= htmlspecialchars($copia['autore']) ?>
                    </div>
                    <div class="col-sm-4">
                        <strong>Numero copia:</strong><br>
                        #<?= $copia['numero_copia'] ?>
                    </div>
                    <div class="col-sm-4">
                        <strong>Armadio/Ripiano:</strong><br>
                        <?= htmlspecialchars($copia['armadio'] ?? '—') ?> /
                        <?= htmlspecialchars($copia['ripiano'] ?? '—') ?>
                    </div>
                    <div class="col-sm-4">
                        <strong>Stato:</strong><br>
                        <?= statoCopiaLabel($copia['stato']) ?>
                    </div>
                    <?php if ($copia['codice_dewey']): ?>
                    <div class="col-sm-4">
                        <strong>Dewey:</strong><br>
                        <?= htmlspecialchars($copia['codice_dewey']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stato e azioni -->
        <?php if ($prestitoAttivo): ?>
        <div class="card mb-3 border-warning shadow-sm">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-person-check me-1"></i>In prestito
            </div>
            <div class="card-body">
                <p>
                    <strong>Utente:</strong>
                    <?php if (!empty($prestitoAttivo['nomeStu'])): ?>
                        <?= htmlspecialchars($prestitoAttivo['cognomeStu'] . ' ' . $prestitoAttivo['nomeStu']) ?>
                    <?php else: ?>
                        <?= htmlspecialchars($prestitoAttivo['emailUtente']) ?>
                    <?php endif; ?>
                </p>
                <p>
                    <strong>Scadenza:</strong>
                    <?= formatData($prestitoAttivo['data_scadenza']) ?>
                </p>
                <?php if (canManageBooks($userLevel)): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token"  value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action_qr"   value="restituzione">
                    <button type="submit" class="btn btn-success"
                            data-confirm="Confermare la restituzione di questa copia?">
                        <i class="bi bi-arrow-return-left me-1"></i>Conferma restituzione
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($prenAttiva): ?>
        <div class="card mb-3 border-info shadow-sm">
            <div class="card-header bg-info text-dark">
                <i class="bi bi-bookmark-check me-1"></i>Prenotazione attiva
            </div>
            <div class="card-body">
                <p>
                    <strong>Prenotato da:</strong>
                    <?php if (!empty($prenAttiva['nomeStu'])): ?>
                        <?= htmlspecialchars($prenAttiva['cognomeStu'] . ' ' . $prenAttiva['nomeStu']) ?>
                    <?php else: ?>
                        <?= htmlspecialchars($prenAttiva['emailUtente']) ?>
                    <?php endif; ?>
                </p>
                <p>
                    <strong>Stato:</strong> <?= statoPrenotazioneLabel($prenAttiva['stato']) ?>
                </p>
                <?php if (canManageBooks($userLevel) && $prenAttiva['stato'] === 'confermata'): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action_qr"  value="conferma_ritiro">
                    <button type="submit" class="btn btn-primary"
                            data-confirm="Confermare il ritiro di questa copia?">
                        <i class="bi bi-box-arrow-in-down me-1"></i>Conferma ritiro
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($copia['stato'] === 'disponibile'): ?>
        <div class="card mb-3 border-success shadow-sm">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle me-1"></i>Disponibile
            </div>
            <div class="card-body">
                <p>Questa copia è disponibile per il prestito.</p>
                <a href="prenota.php?id_libro=<?= $copia['id_libro'] ?>"
                   class="btn btn-primary">
                    <i class="bi bi-bookmark-plus me-1"></i>Prenota
                </a>
            </div>
        </div>

        <?php else: ?>
        <div class="card mb-3 border-secondary shadow-sm">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-exclamation-circle me-1"></i>Non disponibile
            </div>
            <div class="card-body">
                <p>Questa copia non è attualmente disponibile (<?= htmlspecialchars($copia['stato']) ?>).</p>
                <?php if ($copia['note_danno']): ?>
                    <p class="text-danger"><strong>Note:</strong> <?= htmlspecialchars($copia['note_danno']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- QR image -->
        <div class="text-center">
            <img src="<?= htmlspecialchars(getQrImageUrl($copia['qr_code'], 200)) ?>"
                 alt="QR Code" class="img-fluid" style="max-width:200px;">
        </div>

    </div>
</div>

<footer class="mt-5 py-3 text-center text-muted small border-top">
    Biblioteca Gobetti &copy; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/biblioteca.js"></script>
</body>
</html>
