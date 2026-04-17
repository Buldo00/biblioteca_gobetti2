<?php
// ============================================================
// PRENOTA.PHP – Effettua una prenotazione
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);
$currentUser = getCurrentUser($pdo, $idUtente);

$idLibro = (int)($_GET['id_libro'] ?? $_POST['id_libro'] ?? 0);
if (!$idLibro) {
    redirectWith('index.php', 'danger', 'Libro non specificato.');
}

// Carica libro
$stmt = $pdo->prepare("SELECT * FROM bib_libri WHERE id = ?");
$stmt->execute([$idLibro]);
$libro = $stmt->fetch();
if (!$libro) {
    redirectWith('index.php', 'danger', 'Libro non trovato.');
}

// Controlla blacklist
if (isBlacklisted($pdo, $idUtente)) {
    redirectWith('libro.php?id=' . $idLibro, 'danger',
        'Non puoi prenotare: il tuo account è in blacklist.');
}

// Controlla se già prenotato
if (haPrenotatoLibro($pdo, $idUtente, $idLibro)) {
    redirectWith('libro.php?id=' . $idLibro, 'warning',
        'Hai già una prenotazione attiva per questo libro.');
}

$impostazioni = getImpostazioni($pdo);
$maxStudente  = (int)($impostazioni['max_prestiti_studente'] ?? 3);
$maxDocente   = (int)($impostazioni['max_prestiti_docente']  ?? 10);
$giorniRitiro = (int)($impostazioni['giorni_ritiro']         ?? 3);

// Controlla limite prenotazioni
$attive  = countPrenotazioniAttive($pdo, $idUtente);
$maxConsentite = isStudent($userLevel) ? $maxStudente : $maxDocente;
if (!canManageBooks($userLevel) && $attive >= $maxConsentite) {
    redirectWith('libro.php?id=' . $idLibro, 'warning',
        "Hai raggiunto il limite massimo di {$maxConsentite} prenotazioni attive.");
}

// Copie disponibili
$stmtCopie = $pdo->prepare(
    "SELECT * FROM bib_copie WHERE id_libro = ? AND stato = 'disponibile' LIMIT 1"
);
$stmtCopie->execute([$idLibro]);
$copiaDisp = $stmtCopie->fetch();

if (!$copiaDisp) {
    redirectWith('libro.php?id=' . $idLibro, 'warning',
        'Non ci sono copie disponibili al momento.');
}

$classi = canBookForClass($userLevel) ? getClassi($pdo) : [];

$errore  = '';
$successo = false;

// ── Elaborazione form ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();

    $tipo      = sanitizeString($_POST['tipo'] ?? 'personale', 20);
    $idClasse  = (int)($_POST['id_classe'] ?? 0);
    $note      = sanitizeString($_POST['note'] ?? '', 500);
    $studentiIds = $_POST['studenti'] ?? [];

    // Solo studenti → sempre prenotazione personale
    if (isStudent($userLevel)) {
        $tipo = 'personale';
    }

    if ($tipo === 'classe' && !canBookForClass($userLevel)) {
        $errore = 'Non hai i permessi per prenotare per una classe.';
    } elseif ($tipo === 'classe' && !$idClasse) {
        $errore = 'Seleziona una classe.';
    } else {
        try {
            $pdo->beginTransaction();
            $dataScad = date('Y-m-d H:i:s', strtotime("+{$giorniRitiro} days"));

            if ($tipo === 'personale') {
                // Prenotazione personale singola
                $ins = $pdo->prepare(
                    "INSERT INTO bib_prenotazioni
                     (id_utente, id_libro, id_copia, tipo, stato, data_scadenza, note)
                     VALUES (?, ?, ?, 'personale', 'in_attesa', ?, ?)"
                );
                $ins->execute([$idUtente, $idLibro, $copiaDisp['id'], $dataScad, $note]);
                $idPren = $pdo->lastInsertId();

                // Segna la copia come prenotata
                $pdo->prepare("UPDATE bib_copie SET stato = 'prenotato' WHERE id = ?")
                    ->execute([$copiaDisp['id']]);

                logOperazione($pdo, $idUtente, 'prenotazione_personale',
                    "Libro ID {$idLibro}", $idPren, 'prenotazione');

            } else {
                // Prenotazione di classe: una per ogni studente selezionato
                if (empty($studentiIds)) {
                    // Prenota per intera classe
                    $stud = getStudentiPerClasse($pdo, $idClasse);
                    $studentiIds = array_column($stud, 'IDUtente');
                }
                foreach ($studentiIds as $idStu) {
                    $idStu = (int)$idStu;
                    if (!$idStu) continue;

                    // Trova copia disponibile per ogni studente
                    $cStu = $pdo->prepare(
                        "SELECT id FROM bib_copie WHERE id_libro = ? AND stato = 'disponibile' LIMIT 1"
                    );
                    $cStu->execute([$idLibro]);
                    $copStu = $cStu->fetchColumn();

                    $ins = $pdo->prepare(
                        "INSERT INTO bib_prenotazioni
                         (id_utente, id_libro, id_copia, tipo, id_classe, stato, data_scadenza, note)
                         VALUES (?, ?, ?, 'classe', ?, 'in_attesa', ?, ?)"
                    );
                    $ins->execute([$idStu, $idLibro, $copStu ?: null,
                                   $idClasse, $dataScad, $note]);

                    if ($copStu) {
                        $pdo->prepare("UPDATE bib_copie SET stato = 'prenotato' WHERE id = ?")
                            ->execute([$copStu]);
                    }
                }
                logOperazione($pdo, $idUtente, 'prenotazione_classe',
                    "Classe ID {$idClasse}, Libro ID {$idLibro}", $idClasse, 'classe');
            }

            $pdo->commit();
            redirectWith('prestiti.php', 'success',
                'Prenotazione effettuata con successo! Hai '
                . $giorniRitiro . ' giorni per ritirare il libro.');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errore = 'Errore durante la prenotazione. Riprova.';
        }
    }
}

$pageTitle = 'Prenota: ' . $libro['titolo'];
require_once __DIR__ . '/includes/header.php';
?>
<meta name="csrf-token" content="<?= getCsrfToken() ?>">
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Catalogo</a></li>
        <li class="breadcrumb-item"><a href="libro.php?id=<?= $idLibro ?>"><?= htmlspecialchars($libro['titolo']) ?></a></li>
        <li class="breadcrumb-item active">Prenota</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-bookmark-plus me-2"></i>Nuova prenotazione</h5>
            </div>
            <div class="card-body">
                <?php if ($errore): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
                <?php endif; ?>

                <!-- Info libro -->
                <div class="d-flex align-items-start gap-3 mb-4">
                    <?php if ($libro['copertina']): ?>
                        <img src="uploads/copertine/<?= htmlspecialchars($libro['copertina']) ?>"
                             style="width:60px;height:80px;object-fit:cover;border-radius:4px;" alt="">
                    <?php else: ?>
                        <div class="book-cover-placeholder rounded" style="width:60px;height:80px;font-size:1.5rem;">
                            <i class="bi bi-book"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($libro['titolo']) ?></div>
                        <div class="text-muted"><?= htmlspecialchars($libro['autore']) ?></div>
                        <div class="small text-success mt-1">
                            <i class="bi bi-check-circle me-1"></i>Copia disponibile: #<?= $copiaDisp['numero_copia'] ?>
                        </div>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="id_libro" value="<?= $idLibro ?>">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <?php if (canBookForClass($userLevel) && !isStudent($userLevel)): ?>
                    <!-- Tipo prenotazione -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo di prenotazione</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipo" value="personale"
                                   id="tipo-pers" checked>
                            <label class="btn btn-outline-primary" for="tipo-pers">
                                <i class="bi bi-person me-1"></i>Personale
                            </label>
                            <input type="radio" class="btn-check" name="tipo" value="classe"
                                   id="tipo-classe">
                            <label class="btn btn-outline-success" for="tipo-classe">
                                <i class="bi bi-people me-1"></i>Per classe
                            </label>
                        </div>
                    </div>

                    <!-- Selezione classe (visibile solo per tipo=classe) -->
                    <div id="classe-section" class="mb-3 d-none">
                        <label for="id_classe" class="form-label fw-semibold">Classe</label>
                        <select class="form-select" name="id_classe" id="id_classe">
                            <option value="">Seleziona classe…</option>
                            <?php foreach ($classi as $cl): ?>
                                <option value="<?= $cl['IDClasse'] ?>">
                                    <?= htmlspecialchars($cl['anno']) ?><?= htmlspecialchars($cl['sezione']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="studenti-section" class="mt-3"></div>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="tipo" value="personale">
                    <?php endif; ?>

                    <!-- Note -->
                    <div class="mb-3">
                        <label for="note" class="form-label fw-semibold">Note (opzionale)</label>
                        <textarea class="form-control" name="note" id="note" rows="2"
                                  placeholder="Eventuali note per il bibliotecario…"
                                  maxlength="500"></textarea>
                    </div>

                    <!-- Info limite -->
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Prenotazioni attive: <strong><?= $attive ?></strong> /
                        <strong><?= $maxConsentite ?></strong> &mdash;
                        Hai <strong><?= $giorniRitiro ?> giorni</strong> per ritirare il libro
                        dalla conferma.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Conferma prenotazione
                        </button>
                        <a href="libro.php?id=<?= $idLibro ?>" class="btn btn-outline-secondary">
                            Annulla
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<footer class="mt-5 py-3 text-center text-muted small border-top">
    Biblioteca Gobetti &copy; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/biblioteca.js"></script>
<script>
// Mostra/nasconde la sezione classe in base al tipo selezionato
document.querySelectorAll('input[name="tipo"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const cs = document.getElementById('classe-section');
        if (cs) cs.classList.toggle('d-none', radio.value !== 'classe');
    });
});
</script>
</body>
</html>
