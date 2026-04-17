<?php
// ============================================================
// GESTIONE_LIBRI.PHP – CRUD libri (bibliotecari e admin)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

// Solo bibliotecari e superiori
if (!canManageBooks($userLevel)) {
    redirectWith('index.php', 'danger', 'Accesso non autorizzato.');
}

$errore  = '';
$successo = '';

// ── Elimina libro ──────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!isset($_GET['csrf']) || $_GET['csrf'] !== getCsrfToken()) {
        $errore = 'Token CSRF non valido.';
    } else {
        $idDel = (int)$_GET['delete'];
        // Controlla se esistono copie registrate
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM bib_copie WHERE id_libro = ?");
        $stmtCheck->execute([$idDel]);
        $nCopie = (int)$stmtCheck->fetchColumn();

        if ($nCopie > 0) {
            $errore = 'Impossibile eliminare: il libro ha copie registrate. Elimina prima le copie.';
        } else {
            $pdo->prepare("DELETE FROM bib_libri WHERE id = ?")->execute([$idDel]);
            logOperazione($pdo, $idUtente, 'elimina_libro', "Libro ID {$idDel}", $idDel, 'libro');
            redirectWith('gestione_libri.php', 'success', 'Libro eliminato con successo.');
        }
    }
}

// ── Salva libro (aggiungi / modifica) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $idEdit       = (int)($_POST['id_libro'] ?? 0);
    $titolo       = sanitizeString($_POST['titolo']       ?? '', 255);
    $autore       = sanitizeString($_POST['autore']       ?? '', 255);
    $anno         = $_POST['anno'] !== '' ? (int)$_POST['anno'] : null;
    $casaEditrice = sanitizeString($_POST['casa_editrice'] ?? '', 255);
    $lingua       = sanitizeString($_POST['lingua']       ?? '', 50);
    $genere       = sanitizeString($_POST['genere']       ?? '', 100);
    $dewey        = sanitizeString($_POST['codice_dewey'] ?? '', 50);
    $isbn         = sanitizeString($_POST['isbn']         ?? '', 20);
    $trama        = sanitizeString($_POST['trama']        ?? '', 5000);
    $tipologia    = in_array($_POST['tipologia'] ?? '', ['libro','rivista','dizionario','manuale'])
                    ? $_POST['tipologia'] : 'libro';
    $aula         = sanitizeString($_POST['aula'] ?? '', 50);

    if ($titolo === '' || $autore === '') {
        $errore = 'Titolo e autore sono obbligatori.';
    } else {
        // Gestione upload copertina
        $copertina = null;
        if (!empty($_FILES['copertina']['name'])) {
            $copertina = uploadCopertina($_FILES['copertina']);
            if ($copertina === null) {
                $errore = 'Errore upload copertina: formato non valido o file troppo grande (max 5MB, jpg/png/gif).';
            }
        }

        if (!$errore) {
            if ($idEdit) {
                // Aggiornamento
                $setCop = $copertina !== null ? ', copertina = ?' : '';
                $sql    = "UPDATE bib_libri SET titolo=?, autore=?, anno=?, casa_editrice=?,
                           lingua=?, genere=?, codice_dewey=?, isbn=?, trama=?, tipologia=?,
                           aula=? $setCop WHERE id=?";
                $params = [$titolo, $autore, $anno, $casaEditrice, $lingua, $genere,
                           $dewey, $isbn, $trama, $tipologia, $aula];
                if ($copertina !== null) $params[] = $copertina;
                $params[] = $idEdit;
                $pdo->prepare($sql)->execute($params);
                logOperazione($pdo, $idUtente, 'modifica_libro', "Libro: {$titolo}", $idEdit, 'libro');
                redirectWith('gestione_libri.php', 'success', "Libro «{$titolo}» aggiornato.");
            } else {
                // Inserimento
                $stmt = $pdo->prepare(
                    "INSERT INTO bib_libri
                     (titolo, autore, anno, casa_editrice, lingua, genere, codice_dewey,
                      isbn, trama, tipologia, aula, copertina)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([$titolo, $autore, $anno, $casaEditrice, $lingua, $genere,
                                $dewey, $isbn, $trama, $tipologia, $aula, $copertina]);
                $newId = $pdo->lastInsertId();
                logOperazione($pdo, $idUtente, 'aggiungi_libro', "Libro: {$titolo}", $newId, 'libro');
                redirectWith('gestione_libri.php', 'success', "Libro «{$titolo}» aggiunto con successo.");
            }
        }
    }
}

// ── Carica libro da modificare ────────────────────────────────
$libroEdit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM bib_libri WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $libroEdit = $stmt->fetch();
}

// ── Lista libri con ricerca ───────────────────────────────────
$cerca   = sanitizeString($_GET['cerca'] ?? '', 200);
$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($cerca !== '') {
    $where[]  = "(titolo LIKE ? OR autore LIKE ? OR isbn LIKE ?)";
    $term     = '%' . $cerca . '%';
    $params   = [$term, $term, $term];
}
$whereStr = implode(' AND ', $where);

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM bib_libri WHERE $whereStr");
$stmtCount->execute($params);
$totale     = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totale / $perPage));

$sqlList = "SELECT l.*,
                   (SELECT COUNT(*) FROM bib_copie c WHERE c.id_libro = l.id) AS n_copie
            FROM bib_libri l
            WHERE $whereStr
            ORDER BY l.titolo
            LIMIT $perPage OFFSET $offset";
$stmtList = $pdo->prepare($sqlList);
$stmtList->execute($params);
$libri = $stmtList->fetchAll();

$pageTitle = 'Gestione Libri';
require_once __DIR__ . '/includes/header.php';
?>
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>
<?php showFlash(); ?>

<div class="librarian-header mb-3">
    <h2 class="mb-0"><i class="bi bi-journal-plus me-2"></i>Gestione Libri</h2>
</div>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Form aggiungi/modifica -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header <?= $libroEdit ? 'bg-warning text-dark' : 'bg-primary text-white' ?>">
                <h6 class="mb-0">
                    <?= $libroEdit ? '<i class="bi bi-pencil me-1"></i>Modifica libro' : '<i class="bi bi-plus-circle me-1"></i>Aggiungi libro' ?>
                </h6>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="id_libro" value="<?= $libroEdit['id'] ?? 0 ?>">

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Titolo *</label>
                        <input type="text" class="form-control form-control-sm" name="titolo"
                               value="<?= htmlspecialchars($libroEdit['titolo'] ?? '') ?>"
                               required maxlength="255">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Autore *</label>
                        <input type="text" class="form-control form-control-sm" name="autore"
                               value="<?= htmlspecialchars($libroEdit['autore'] ?? '') ?>"
                               required maxlength="255">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Anno</label>
                            <input type="number" class="form-control form-control-sm" name="anno"
                                   value="<?= htmlspecialchars($libroEdit['anno'] ?? '') ?>"
                                   min="1000" max="<?= date('Y') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tipologia</label>
                            <select class="form-select form-select-sm" name="tipologia">
                                <?php foreach (['libro','rivista','dizionario','manuale'] as $t): ?>
                                    <option value="<?= $t ?>"
                                        <?= ($libroEdit['tipologia'] ?? 'libro') === $t ? 'selected' : '' ?>>
                                        <?= ucfirst($t) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Casa editrice</label>
                        <input type="text" class="form-control form-control-sm" name="casa_editrice"
                               value="<?= htmlspecialchars($libroEdit['casa_editrice'] ?? '') ?>" maxlength="255">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Lingua</label>
                            <input type="text" class="form-control form-control-sm" name="lingua"
                                   value="<?= htmlspecialchars($libroEdit['lingua'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Genere</label>
                            <input type="text" class="form-control form-control-sm" name="genere"
                                   value="<?= htmlspecialchars($libroEdit['genere'] ?? '') ?>" maxlength="100">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Codice Dewey</label>
                            <input type="text" class="form-control form-control-sm" name="codice_dewey"
                                   value="<?= htmlspecialchars($libroEdit['codice_dewey'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ISBN</label>
                            <input type="text" class="form-control form-control-sm" name="isbn"
                                   value="<?= htmlspecialchars($libroEdit['isbn'] ?? '') ?>" maxlength="20">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Aula / Posizione</label>
                        <input type="text" class="form-control form-control-sm" name="aula"
                               value="<?= htmlspecialchars($libroEdit['aula'] ?? '') ?>" maxlength="50">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Trama / Descrizione</label>
                        <textarea class="form-control form-control-sm" name="trama" rows="3"
                                  maxlength="5000"><?= htmlspecialchars($libroEdit['trama'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Copertina (JPG/PNG, max 5MB)</label>
                        <?php if (!empty($libroEdit['copertina'])): ?>
                            <div class="mb-1">
                                <img src="uploads/copertine/<?= htmlspecialchars($libroEdit['copertina']) ?>"
                                     style="height:60px;border-radius:4px;" alt="Copertina attuale">
                                <small class="text-muted d-block">Copertina attuale</small>
                            </div>
                        <?php endif; ?>
                        <img id="coverPreview" class="d-none mb-1"
                             style="height:80px;border-radius:4px;" alt="Anteprima">
                        <input type="file" class="form-control form-control-sm" name="copertina"
                               id="copertina" accept="image/*">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm <?= $libroEdit ? 'btn-warning' : 'btn-primary' ?> flex-grow-1">
                            <i class="bi bi-save me-1"></i><?= $libroEdit ? 'Aggiorna' : 'Aggiungi' ?>
                        </button>
                        <?php if ($libroEdit): ?>
                            <a href="gestione_libri.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista libri -->
    <div class="col-lg-8">
        <!-- Ricerca -->
        <form method="get" class="d-flex gap-2 mb-3">
            <input type="text" class="form-control form-control-sm" name="cerca"
                   placeholder="Cerca titolo, autore, ISBN…"
                   value="<?= htmlspecialchars($cerca) ?>">
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($cerca): ?>
                <a href="gestione_libri.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x"></i>
                </a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-biblioteca">
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Autore</th>
                        <th>Anno</th>
                        <th>Tipo</th>
                        <th class="text-center">Copie</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($libri)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Nessun libro trovato.</td></tr>
                    <?php else: ?>
                    <?php foreach ($libri as $l): ?>
                    <tr>
                        <td>
                            <a href="libro.php?id=<?= $l['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= htmlspecialchars($l['titolo']) ?>
                            </a>
                            <?php if ($l['isbn']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($l['isbn']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($l['autore']) ?></td>
                        <td><?= $l['anno'] ?? '—' ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($l['tipologia']) ?></span></td>
                        <td class="text-center">
                            <a href="gestione_copie.php?id_libro=<?= $l['id'] ?>"
                               class="badge bg-primary text-decoration-none">
                                <?= $l['n_copie'] ?>
                            </a>
                        </td>
                        <td class="text-nowrap">
                            <a href="gestione_libri.php?edit=<?= $l['id'] ?>"
                               class="btn btn-xs btn-outline-warning btn-sm py-0 px-1 me-1"
                               title="Modifica">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="gestione_copie.php?id_libro=<?= $l['id'] ?>"
                               class="btn btn-xs btn-outline-primary btn-sm py-0 px-1 me-1"
                               title="Copie">
                                <i class="bi bi-stack"></i>
                            </a>
                            <a href="etichetta_pdf.php?id_libro=<?= $l['id'] ?>"
                               class="btn btn-xs btn-outline-secondary btn-sm py-0 px-1 me-1"
                               title="Etichette" target="_blank">
                                <i class="bi bi-printer"></i>
                            </a>
                            <a href="gestione_libri.php?delete=<?= $l['id'] ?>&csrf=<?= getCsrfToken() ?>"
                               class="btn btn-xs btn-outline-danger btn-sm py-0 px-1"
                               title="Elimina"
                               data-confirm="Eliminare il libro «<?= htmlspecialchars($l['titolo']) ?>»?">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginazione -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-2">
            <ul class="pagination pagination-sm justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
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
