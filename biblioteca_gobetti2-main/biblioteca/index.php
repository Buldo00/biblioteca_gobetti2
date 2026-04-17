<?php
// ============================================================
// INDEX.PHP – Catalogo libri con ricerca e filtri
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

// Aggiorna stati scaduti ad ogni visita
aggiornaPrenotazioniScadute($pdo);
aggiornaPrestitiScaduti($pdo);

$pageTitle = 'Catalogo';

// ── Parametri di ricerca ───────────────────────────────────
$cerca     = sanitizeString($_GET['cerca']     ?? '', 200);
$genere    = sanitizeString($_GET['genere']    ?? '', 100);
$lingua    = sanitizeString($_GET['lingua']    ?? '', 50);
$tipologia = sanitizeString($_GET['tipologia'] ?? '', 50);
$disponibile = isset($_GET['disponibile']) ? (int)$_GET['disponibile'] : 0;
$page      = max(1, (int)($_GET['p'] ?? 1));
$perPage   = 24;
$offset    = ($page - 1) * $perPage;

// ── Query catalogo ─────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($cerca !== '') {
    $where[]  = "(l.titolo LIKE ? OR l.autore LIKE ? OR l.isbn LIKE ? OR l.codice_dewey LIKE ?)";
    $term     = '%' . $cerca . '%';
    $params   = array_merge($params, [$term, $term, $term, $term]);
}
if ($genere !== '') {
    $where[]  = "l.genere = ?";
    $params[] = $genere;
}
if ($lingua !== '') {
    $where[]  = "l.lingua = ?";
    $params[] = $lingua;
}
if ($tipologia !== '') {
    $where[]  = "l.tipologia = ?";
    $params[] = $tipologia;
}
if ($disponibile) {
    $where[] = "(SELECT COUNT(*) FROM bib_copie c WHERE c.id_libro = l.id AND c.stato = 'disponibile') > 0";
}

$whereStr = implode(' AND ', $where);

// Conteggio totale per paginazione
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM bib_libri l WHERE $whereStr");
$stmtCount->execute($params);
$totalLibri = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalLibri / $perPage));
$page       = min($page, $totalPages);

// Query principale
$sql = "SELECT l.*,
               (SELECT COUNT(*) FROM bib_copie c WHERE c.id_libro = l.id AND c.stato = 'disponibile') AS copie_disponibili,
               (SELECT COUNT(*) FROM bib_copie c WHERE c.id_libro = l.id) AS copie_totali
        FROM bib_libri l
        WHERE $whereStr
        ORDER BY l.titolo
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$libri = $stmt->fetchAll();

// Opzioni filtro
$generi    = $pdo->query("SELECT DISTINCT genere FROM bib_libri WHERE genere IS NOT NULL AND genere != '' ORDER BY genere")->fetchAll(PDO::FETCH_COLUMN);
$lingue    = $pdo->query("SELECT DISTINCT lingua FROM bib_libri WHERE lingua IS NOT NULL AND lingua != '' ORDER BY lingua")->fetchAll(PDO::FETCH_COLUMN);

// Messaggi flash
$isBlacklisted = isBlacklisted($pdo, $idUtente);

require_once __DIR__ . '/includes/header.php';
?>
<!-- Meta CSRF per chiamate AJAX -->
<meta name="csrf-token" content="<?= getCsrfToken() ?>">

<!-- Toast container -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<!-- Banner blacklist -->
<?php if ($isBlacklisted): ?>
<div class="alert alert-danger mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Attenzione:</strong> il tuo account è in blacklist. Non puoi effettuare nuove prenotazioni.
    Contatta la biblioteca per maggiori informazioni.
</div>
<?php endif; ?>

<?php showFlash(); ?>

<!-- Intestazione pagina -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0"><i class="bi bi-search me-2 text-primary"></i>Catalogo Libri</h2>
    <small class="text-muted"><?= number_format($totalLibri) ?> libri trovati</small>
</div>

<!-- Form ricerca e filtri -->
<form id="searchForm" method="get" class="search-bar">
    <div class="row g-2">
        <div class="col-12 col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" name="cerca" id="cerca"
                       placeholder="Titolo, autore, ISBN, Dewey…"
                       value="<?= htmlspecialchars($cerca) ?>">
            </div>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="genere">
                <option value="">Tutti i generi</option>
                <?php foreach ($generi as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>"
                        <?= $g === $genere ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="lingua">
                <option value="">Tutte le lingue</option>
                <?php foreach ($lingue as $l): ?>
                    <option value="<?= htmlspecialchars($l) ?>"
                        <?= $l === $lingua ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="tipologia">
                <option value="">Tutti i tipi</option>
                <option value="libro"      <?= $tipologia === 'libro'      ? 'selected' : '' ?>>Libro</option>
                <option value="rivista"    <?= $tipologia === 'rivista'    ? 'selected' : '' ?>>Rivista</option>
                <option value="dizionario" <?= $tipologia === 'dizionario' ? 'selected' : '' ?>>Dizionario</option>
                <option value="manuale"    <?= $tipologia === 'manuale'    ? 'selected' : '' ?>>Manuale</option>
            </select>
        </div>
        <div class="col-6 col-md-1 d-flex align-items-center">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="disponibile" value="1" id="dispCheck"
                       <?= $disponibile ? 'checked' : '' ?>>
                <label class="form-check-label small" for="dispCheck">Disponibili</label>
            </div>
        </div>
        <div class="col-12 col-md-1">
            <button type="submit" class="btn btn-primary w-100">
                <span id="searchSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>
</form>

<!-- Griglia libri -->
<div class="row" id="catalogResults">
    <?php if (empty($libri)): ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-book fs-1"></i>
            <p class="mt-2">Nessun libro trovato con i criteri selezionati.</p>
            <a href="index.php" class="btn btn-outline-primary btn-sm">Azzera filtri</a>
        </div>
    <?php else: ?>
        <?php foreach ($libri as $libro): ?>
            <?php
            $disp        = (int)$libro['copie_disponibili'];
            $badgeClass  = $disp > 0 ? 'success' : 'danger';
            $badgeText   = $disp > 0 ? $disp . ' disponibile' . ($disp > 1 ? 'i' : '') : 'Non disponibile';
            ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3">
                <div class="book-card d-flex flex-column">
                    <a href="libro.php?id=<?= $libro['id'] ?>" class="text-decoration-none text-dark">
                        <?php if ($libro['copertina']): ?>
                            <img src="uploads/copertine/<?= htmlspecialchars($libro['copertina']) ?>"
                                 class="book-cover" alt="Copertina">
                        <?php else: ?>
                            <div class="book-cover-placeholder">
                                <i class="bi bi-book fs-1"></i>
                                <small class="mt-1"><?= htmlspecialchars($libro['tipologia']) ?></small>
                            </div>
                        <?php endif; ?>
                        <div class="p-2 flex-grow-1 d-flex flex-column">
                            <div class="book-title mb-1"><?= htmlspecialchars($libro['titolo']) ?></div>
                            <div class="book-author mb-1"><?= htmlspecialchars($libro['autore']) ?></div>
                            <div class="book-meta mb-auto">
                                <?= $libro['anno'] ? htmlspecialchars($libro['anno']) : '' ?>
                                <?php if ($libro['aula']): ?>
                                    · Aula <?= htmlspecialchars($libro['aula']) ?>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-<?= $badgeClass ?> availability-badge mt-2">
                                <?= $badgeText ?>
                            </span>
                        </div>
                    </a>
                    <div class="p-2 pt-0 d-flex gap-1">
                        <?php if ($disp > 0 && !$isBlacklisted && !haPrenotatoLibro($pdo, $idUtente, $libro['id'])): ?>
                            <a href="prenota.php?id_libro=<?= $libro['id'] ?>"
                               class="btn btn-sm btn-primary flex-grow-1">
                                <i class="bi bi-bookmark-plus"></i> Prenota
                            </a>
                        <?php elseif ($disp === 0 && !haRichiestoAvviso($pdo, $idUtente, $libro['id'])): ?>
                            <button class="btn btn-sm btn-outline-secondary flex-grow-1 btn-avvisami"
                                    data-id-libro="<?= $libro['id'] ?>">
                                <i class="bi bi-bell"></i> Avvisami
                            </button>
                        <?php elseif (haPrenotatoLibro($pdo, $idUtente, $libro['id'])): ?>
                            <span class="btn btn-sm btn-success flex-grow-1 disabled">
                                <i class="bi bi-check2"></i> Prenotato
                            </span>
                        <?php elseif (haRichiestoAvviso($pdo, $idUtente, $libro['id'])): ?>
                            <span class="btn btn-sm btn-secondary flex-grow-1 disabled">
                                <i class="bi bi-bell-fill"></i> In attesa
                            </span>
                        <?php endif; ?>
                        <a href="libro.php?id=<?= $libro['id'] ?>"
                           class="btn btn-sm btn-outline-dark"
                           data-bs-toggle="tooltip" title="Dettagli">
                            <i class="bi bi-info-circle"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Paginazione -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Paginazione catalogo" class="mt-3">
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page - 1])) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php
        $from = max(1, $page - 2);
        $to   = min($totalPages, $page + 2);
        if ($from > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        for ($i = $from; $i <= $to; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor;
        if ($to < $totalPages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page + 1])) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<footer class="mt-5 py-3 text-center text-muted small border-top">
    Biblioteca Gobetti &copy; <?= date('Y') ?> &mdash; Sistema gestione biblioteca scolastica
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/biblioteca.js"></script>
</body>
</html>
