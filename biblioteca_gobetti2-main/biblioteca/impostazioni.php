<?php
// ============================================================
// IMPOSTAZIONI.PHP – Configurazione sistema (solo admin)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

if (!isAdmin($userLevel)) {
    redirectWith('index.php', 'danger', 'Accesso riservato agli amministratori.');
}

// ── Salvataggio impostazioni ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $campiConsentiti = [
        'max_prestiti_studente',
        'giorni_ritiro',
        'max_mancate_prenotazioni_blacklist',
        'giorni_prestito',
        'orario_apertura',
        'orario_chiusura',
        'email_mittente',
        'nome_biblioteca',
        'max_prestiti_docente',
    ];

    $stmt = $pdo->prepare(
        "UPDATE bib_impostazioni SET valore = ? WHERE chiave = ?"
    );

    foreach ($campiConsentiti as $chiave) {
        if (isset($_POST[$chiave])) {
            $valore = sanitizeString($_POST[$chiave], 500);
            $stmt->execute([$valore, $chiave]);
        }
    }

    logOperazione($pdo, $idUtente, 'modifica_impostazioni', 'Aggiornamento impostazioni biblioteca');
    redirectWith('impostazioni.php', 'success', 'Impostazioni salvate con successo.');
}

// ── Carica impostazioni correnti ───────────────────────────
$impostazioni = $pdo->query("SELECT * FROM bib_impostazioni ORDER BY id")->fetchAll();
$imp = [];
foreach ($impostazioni as $row) {
    $imp[$row['chiave']] = $row;
}

$pageTitle = 'Impostazioni';
require_once __DIR__ . '/includes/header.php';
?>
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>
<?php showFlash(); ?>

<div class="admin-header">
    <h2 class="mb-0"><i class="bi bi-gear me-2"></i>Impostazioni Sistema</h2>
    <small class="opacity-75">Configurazione del sistema biblioteca Gobetti</small>
</div>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

    <div class="row g-3">
        <!-- Colonna sinistra -->
        <div class="col-lg-6">

            <h5 class="mb-3 border-bottom pb-2"><i class="bi bi-building me-1"></i>Informazioni biblioteca</h5>

            <div class="settings-card">
                <label for="nome_biblioteca">Nome biblioteca</label>
                <small class="d-block mb-1"><?= htmlspecialchars($imp['nome_biblioteca']['descrizione'] ?? '') ?></small>
                <input type="text" class="form-control form-control-sm" id="nome_biblioteca"
                       name="nome_biblioteca"
                       value="<?= htmlspecialchars($imp['nome_biblioteca']['valore'] ?? '') ?>"
                       maxlength="200">
            </div>

            <div class="settings-card">
                <label for="email_mittente">Email mittente</label>
                <small class="d-block mb-1"><?= htmlspecialchars($imp['email_mittente']['descrizione'] ?? '') ?></small>
                <input type="email" class="form-control form-control-sm" id="email_mittente"
                       name="email_mittente"
                       value="<?= htmlspecialchars($imp['email_mittente']['valore'] ?? '') ?>"
                       maxlength="200">
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <div class="settings-card">
                        <label for="orario_apertura">Orario apertura</label>
                        <small class="d-block mb-1"><?= htmlspecialchars($imp['orario_apertura']['descrizione'] ?? '') ?></small>
                        <input type="time" class="form-control form-control-sm" id="orario_apertura"
                               name="orario_apertura"
                               value="<?= htmlspecialchars($imp['orario_apertura']['valore'] ?? '08:00') ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="settings-card">
                        <label for="orario_chiusura">Orario chiusura</label>
                        <small class="d-block mb-1"><?= htmlspecialchars($imp['orario_chiusura']['descrizione'] ?? '') ?></small>
                        <input type="time" class="form-control form-control-sm" id="orario_chiusura"
                               name="orario_chiusura"
                               value="<?= htmlspecialchars($imp['orario_chiusura']['valore'] ?? '17:00') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonna destra -->
        <div class="col-lg-6">

            <h5 class="mb-3 border-bottom pb-2"><i class="bi bi-sliders me-1"></i>Parametri prestiti</h5>

            <div class="settings-card">
                <label for="max_prestiti_studente">Max prestiti per studente</label>
                <small class="d-block mb-1"><?= htmlspecialchars($imp['max_prestiti_studente']['descrizione'] ?? '') ?></small>
                <input type="number" class="form-control form-control-sm" id="max_prestiti_studente"
                       name="max_prestiti_studente"
                       value="<?= htmlspecialchars($imp['max_prestiti_studente']['valore'] ?? '3') ?>"
                       min="1" max="20">
            </div>

            <div class="settings-card">
                <label for="max_prestiti_docente">Max prestiti per docente</label>
                <small class="d-block mb-1"><?= htmlspecialchars($imp['max_prestiti_docente']['descrizione'] ?? '') ?></small>
                <input type="number" class="form-control form-control-sm" id="max_prestiti_docente"
                       name="max_prestiti_docente"
                       value="<?= htmlspecialchars($imp['max_prestiti_docente']['valore'] ?? '10') ?>"
                       min="1" max="50">
            </div>

            <div class="settings-card">
                <label for="giorni_prestito">Durata prestito (giorni)</label>
                <small class="d-block mb-1"><?= htmlspecialchars($imp['giorni_prestito']['descrizione'] ?? '') ?></small>
                <input type="number" class="form-control form-control-sm" id="giorni_prestito"
                       name="giorni_prestito"
                       value="<?= htmlspecialchars($imp['giorni_prestito']['valore'] ?? '30') ?>"
                       min="1" max="365">
            </div>

            <div class="settings-card">
                <label for="giorni_ritiro">Giorni per il ritiro</label>
                <small class="d-block mb-1"><?= htmlspecialchars($imp['giorni_ritiro']['descrizione'] ?? '') ?></small>
                <input type="number" class="form-control form-control-sm" id="giorni_ritiro"
                       name="giorni_ritiro"
                       value="<?= htmlspecialchars($imp['giorni_ritiro']['valore'] ?? '3') ?>"
                       min="1" max="30">
            </div>

            <div class="settings-card">
                <label for="max_mancate_prenotazioni_blacklist">Prenotazioni non ritirate → blacklist automatica</label>
                <small class="d-block mb-1"><?= htmlspecialchars($imp['max_mancate_prenotazioni_blacklist']['descrizione'] ?? '') ?></small>
                <input type="number" class="form-control form-control-sm"
                       id="max_mancate_prenotazioni_blacklist"
                       name="max_mancate_prenotazioni_blacklist"
                       value="<?= htmlspecialchars($imp['max_mancate_prenotazioni_blacklist']['valore'] ?? '3') ?>"
                       min="1" max="20">
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-danger px-4">
            <i class="bi bi-save me-1"></i>Salva impostazioni
        </button>
        <a href="index.php" class="btn btn-outline-secondary ms-2">Annulla</a>
    </div>
</form>

<!-- Log recenti -->
<hr class="my-4">
<h5><i class="bi bi-list-ul me-1"></i>Log operazioni recenti</h5>
<div class="table-responsive">
    <table class="table table-sm table-hover table-biblioteca">
        <thead>
            <tr>
                <th>Data/Ora</th>
                <th>Utente</th>
                <th>Azione</th>
                <th>Dettagli</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $logs = $pdo->query(
                "SELECT l.*, u.emailUtente, s.nomeStu, s.cognomeStu
                 FROM bib_log l
                 JOIN utenti u ON u.IDUtente = l.id_utente
                 LEFT JOIN studenti s ON s.IDUtente = l.id_utente
                 ORDER BY l.timestamp DESC
                 LIMIT 100"
            )->fetchAll();

            if (empty($logs)):
            ?>
            <tr><td colspan="4" class="text-center text-muted">Nessuna operazione registrata.</td></tr>
            <?php else: ?>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td class="small text-nowrap"><?= formatDataOra($log['timestamp']) ?></td>
                <td class="small">
                    <?= !empty($log['nomeStu'])
                        ? htmlspecialchars($log['cognomeStu'] . ' ' . $log['nomeStu'])
                        : htmlspecialchars($log['emailUtente']) ?>
                </td>
                <td class="small">
                    <code><?= htmlspecialchars($log['azione']) ?></code>
                </td>
                <td class="small"><?= htmlspecialchars($log['dettagli'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<footer class="mt-5 py-3 text-center text-muted small border-top">
    Biblioteca Gobetti &copy; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/biblioteca.js"></script>
</body>
</html>
