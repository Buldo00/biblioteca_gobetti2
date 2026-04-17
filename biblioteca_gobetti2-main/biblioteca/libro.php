<?php
// ============================================================
// LIBRO.PHP – Pagina dettaglio libro
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

$idLibro = (int)($_GET['id'] ?? 0);
if (!$idLibro) {
    redirectWith('index.php', 'danger', 'Libro non trovato.');
}

// Carica dati libro
$stmt = $pdo->prepare("SELECT * FROM bib_libri WHERE id = ?");
$stmt->execute([$idLibro]);
$libro = $stmt->fetch();
if (!$libro) {
    redirectWith('index.php', 'danger', 'Libro non trovato.');
}

// Carica copie del libro
$stmtCopie = $pdo->prepare(
    "SELECT * FROM bib_copie WHERE id_libro = ? ORDER BY numero_copia"
);
$stmtCopie->execute([$idLibro]);
$copie = $stmtCopie->fetchAll();

$copieDisp    = array_filter($copie, fn($c) => $c['stato'] === 'disponibile');
$numDisp      = count($copieDisp);
$isBlacklisted = isBlacklisted($pdo, $idUtente);
$haPrenotato  = haPrenotatoLibro($pdo, $idUtente, $idLibro);
$haAvviso     = haRichiestoAvviso($pdo, $idUtente, $idLibro);

$pageTitle = htmlspecialchars($libro['titolo']);

require_once __DIR__ . '/includes/header.php';
?>
<meta name="csrf-token" content="<?= getCsrfToken() ?>">
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<?php showFlash(); ?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Catalogo</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($libro['titolo']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <!-- Colonna copertina + azioni -->
    <div class="col-md-3 text-center">
        <?php if ($libro['copertina']): ?>
            <img src="uploads/copertine/<?= htmlspecialchars($libro['copertina']) ?>"
                 class="libro-cover-lg img-fluid mb-3" alt="Copertina">
        <?php else: ?>
            <div class="book-cover-placeholder rounded mb-3" style="height:260px;">
                <i class="bi bi-book display-3"></i>
                <p class="mt-2"><?= htmlspecialchars($libro['tipologia']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Disponibilità -->
        <?php if ($numDisp > 0): ?>
            <div class="alert alert-success py-2 mb-3">
                <i class="bi bi-check-circle-fill me-1"></i>
                <strong><?= $numDisp ?></strong> cop<?= $numDisp > 1 ? 'ie' : 'ia' ?> disponibile<?= $numDisp > 1 ? 'i' : '' ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger py-2 mb-3">
                <i class="bi bi-x-circle-fill me-1"></i>Non disponibile
            </div>
        <?php endif; ?>

        <!-- Bottoni azione -->
        <?php if ($numDisp > 0 && !$isBlacklisted && !$haPrenotato): ?>
            <a href="prenota.php?id_libro=<?= $idLibro ?>"
               class="btn btn-primary w-100 mb-2">
                <i class="bi bi-bookmark-plus-fill me-1"></i>Prenota
            </a>
        <?php elseif ($haPrenotato): ?>
            <button class="btn btn-success w-100 mb-2" disabled>
                <i class="bi bi-check2-circle me-1"></i>Già prenotato
            </button>
        <?php elseif ($isBlacklisted): ?>
            <button class="btn btn-danger w-100 mb-2" disabled>
                <i class="bi bi-slash-circle me-1"></i>Account in blacklist
            </button>
        <?php endif; ?>

        <?php if ($numDisp === 0 && !$haAvviso && !$haPrenotato): ?>
            <button class="btn btn-outline-secondary w-100 btn-avvisami"
                    data-id-libro="<?= $idLibro ?>">
                <i class="bi bi-bell me-1"></i>Avvisami quando disponibile
            </button>
        <?php elseif ($haAvviso): ?>
            <button class="btn btn-secondary w-100" disabled>
                <i class="bi bi-bell-fill me-1"></i>Avviso attivo
            </button>
        <?php endif; ?>

        <!-- Azioni bibliotecario -->
        <?php if (canManageBooks($userLevel)): ?>
            <hr>
            <a href="gestione_libri.php?edit=<?= $idLibro ?>"
               class="btn btn-outline-warning w-100 mb-2 btn-sm">
                <i class="bi bi-pencil me-1"></i>Modifica libro
            </a>
            <a href="gestione_copie.php?id_libro=<?= $idLibro ?>"
               class="btn btn-outline-primary w-100 mb-2 btn-sm">
                <i class="bi bi-upc-scan me-1"></i>Gestione copie
            </a>
            <a href="etichetta_pdf.php?id_libro=<?= $idLibro ?>"
               class="btn btn-outline-secondary w-100 btn-sm" target="_blank">
                <i class="bi bi-printer me-1"></i>Stampa etichette
            </a>
        <?php endif; ?>
    </div>

    <!-- Colonna dati libro -->
    <div class="col-md-9">
        <h1 class="h2 mb-1"><?= htmlspecialchars($libro['titolo']) ?></h1>
        <h2 class="h5 text-muted mb-3"><?= htmlspecialchars($libro['autore']) ?></h2>

        <!-- Badge tipologia -->
        <div class="mb-3">
            <span class="badge bg-secondary"><?= htmlspecialchars($libro['tipologia']) ?></span>
            <?php if ($libro['genere']): ?>
                <span class="badge bg-info text-dark"><?= htmlspecialchars($libro['genere']) ?></span>
            <?php endif; ?>
            <?php if ($libro['lingua']): ?>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($libro['lingua']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Scheda bibliografica -->
        <div class="card mb-3">
            <div class="card-header bg-light fw-semibold">
                <i class="bi bi-info-circle me-1"></i>Scheda bibliografica
            </div>
            <div class="card-body">
                <div class="row g-2" style="font-size:.92rem;">
                    <?php $meta = [
                        ['Anno di pubblicazione', $libro['anno']],
                        ['Casa editrice',         $libro['casa_editrice']],
                        ['ISBN',                  $libro['isbn']],
                        ['Codice Dewey',          $libro['codice_dewey']],
                        ['Aula / posizione',      $libro['aula']],
                    ];
                    foreach ($meta as [$label, $val]): if ($val): ?>
                    <div class="col-sm-6">
                        <strong><?= $label ?>:</strong>
                        <?= htmlspecialchars($val) ?>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Trama -->
        <?php if ($libro['trama']): ?>
        <div class="card mb-4">
            <div class="card-header bg-light fw-semibold">
                <i class="bi bi-card-text me-1"></i>Trama / Descrizione
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($libro['trama'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Elenco copie -->
        <div class="card">
            <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-stack me-1"></i>Copie disponibili</span>
                <span class="badge bg-primary"><?= count($copie) ?> cop<?= count($copie) !== 1 ? 'ie' : 'ia' ?></span>
            </div>
            <?php if (empty($copie)): ?>
                <div class="card-body text-muted">Nessuna copia registrata.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-biblioteca mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Stato</th>
                                <th>Armadio</th>
                                <th>Ripiano</th>
                                <?php if (canManageBooks($userLevel)): ?><th>QR Code</th><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($copie as $copia): ?>
                            <tr>
                                <td><?= $copia['numero_copia'] ?></td>
                                <td><?= statoCopiaLabel($copia['stato']) ?></td>
                                <td><?= htmlspecialchars($copia['armadio'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($copia['ripiano'] ?? '—') ?></td>
                                <?php if (canManageBooks($userLevel)): ?>
                                <td>
                                    <code class="small"><?= htmlspecialchars($copia['qr_code']) ?></code>
                                </td>
                                <td>
                                    <a href="etichetta_pdf.php?id_copia=<?= $copia['id'] ?>"
                                       class="btn btn-outline-secondary btn-sm"
                                       target="_blank" title="Stampa etichetta">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
