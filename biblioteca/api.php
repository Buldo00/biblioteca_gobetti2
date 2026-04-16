<?php
// ============================================================
// API.PHP – Endpoint AJAX per il sistema biblioteca
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$idUtente  = (int)$_SESSION['IDUtente'];
$userLevel = getUserLevel($pdo, $idUtente);

// Risposta JSON helper
function jsonResponse(bool $success, array $data = [], string $message = ''): void
{
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Valida CSRF per richieste POST
function checkApiCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        jsonResponse(false, [], 'Token CSRF non valido.');
    }
}

$action = sanitizeString($_GET['action'] ?? $_POST['action'] ?? '', 50);

// ============================================================
// GET: Ricerca libri (catalogo AJAX)
// ============================================================
if ($action === 'search_books' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cerca       = sanitizeString($_GET['cerca']     ?? '', 200);
    $genere      = sanitizeString($_GET['genere']    ?? '', 100);
    $lingua      = sanitizeString($_GET['lingua']    ?? '', 50);
    $tipologia   = sanitizeString($_GET['tipologia'] ?? '', 50);
    $disponibile = (int)($_GET['disponibile'] ?? 0);
    $page        = max(1, (int)($_GET['p'] ?? 1));
    $perPage     = 24;
    $offset      = ($page - 1) * $perPage;

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

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM bib_libri l WHERE $whereStr");
    $stmtCount->execute($params);
    $totale = (int)$stmtCount->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT l.*,
                (SELECT COUNT(*) FROM bib_copie c WHERE c.id_libro = l.id AND c.stato = 'disponibile')
                AS copie_disponibili
         FROM bib_libri l
         WHERE $whereStr
         ORDER BY l.titolo
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $libri = $stmt->fetchAll();

    jsonResponse(true, ['libri' => $libri, 'totale' => $totale]);
}

// ============================================================
// POST: Prenota libro
// ============================================================
if ($action === 'prenota' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkApiCsrf();

    $idLibro = (int)($_POST['id_libro'] ?? 0);
    if (!$idLibro) jsonResponse(false, [], 'ID libro mancante.');

    if (isBlacklisted($pdo, $idUtente)) {
        jsonResponse(false, [], 'Sei in blacklist. Non puoi prenotare.');
    }
    if (haPrenotatoLibro($pdo, $idUtente, $idLibro)) {
        jsonResponse(false, [], 'Hai già una prenotazione attiva per questo libro.');
    }

    $imp     = getImpostazioni($pdo);
    $maxPren = isStudent($userLevel)
               ? (int)($imp['max_prestiti_studente'] ?? 3)
               : (int)($imp['max_prestiti_docente'] ?? 10);

    if (!canManageBooks($userLevel) && countPrenotazioniAttive($pdo, $idUtente) >= $maxPren) {
        jsonResponse(false, [], "Limite di {$maxPren} prenotazioni raggiunto.");
    }

    // Trova copia disponibile
    $stmt = $pdo->prepare(
        "SELECT id FROM bib_copie WHERE id_libro = ? AND stato = 'disponibile' LIMIT 1"
    );
    $stmt->execute([$idLibro]);
    $idCopia = $stmt->fetchColumn();
    if (!$idCopia) jsonResponse(false, [], 'Nessuna copia disponibile.');

    $giorniRitiro = (int)($imp['giorni_ritiro'] ?? 3);
    $dataScad     = date('Y-m-d H:i:s', strtotime("+{$giorniRitiro} days"));

    $ins = $pdo->prepare(
        "INSERT INTO bib_prenotazioni (id_utente, id_libro, id_copia, tipo, stato, data_scadenza)
         VALUES (?, ?, ?, 'personale', 'in_attesa', ?)"
    );
    $ins->execute([$idUtente, $idLibro, $idCopia, $dataScad]);
    $idPren = $pdo->lastInsertId();

    $pdo->prepare("UPDATE bib_copie SET stato = 'prenotato' WHERE id = ?")->execute([$idCopia]);
    logOperazione($pdo, $idUtente, 'prenotazione_api', "Libro ID {$idLibro}", $idPren, 'prenotazione');

    jsonResponse(true, ['id_prenotazione' => $idPren],
        'Prenotazione effettuata con successo!');
}

// ============================================================
// POST: Avvisami quando disponibile
// ============================================================
if ($action === 'avvisami' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkApiCsrf();

    $idLibro = (int)($_POST['id_libro'] ?? 0);
    if (!$idLibro) jsonResponse(false, [], 'ID libro mancante.');

    try {
        $pdo->prepare(
            "INSERT INTO bib_avvisami (id_utente, id_libro) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE notificato = 0, data_richiesta = NOW()"
        )->execute([$idUtente, $idLibro]);

        jsonResponse(true, [], 'Richiesta di avviso salvata.');
    } catch (PDOException $e) {
        jsonResponse(false, [], 'Errore nel salvataggio della richiesta.');
    }
}

// ============================================================
// POST: Conferma ritiro prenotazione (bibliotecario)
// ============================================================
if ($action === 'conferma_ritiro' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkApiCsrf();
    if (!canManageBooks($userLevel)) jsonResponse(false, [], 'Permesso negato.');

    $idPren = (int)($_POST['id_prenotazione'] ?? 0);
    $stmt   = $pdo->prepare("SELECT * FROM bib_prenotazioni WHERE id = ? AND stato = 'confermata'");
    $stmt->execute([$idPren]);
    $pren = $stmt->fetch();
    if (!$pren) jsonResponse(false, [], 'Prenotazione non trovata o non confermata.');

    $imp  = getImpostazioni($pdo);
    $gg   = (int)($imp['giorni_prestito'] ?? 30);
    $ds   = date('Y-m-d H:i:s', strtotime("+{$gg} days"));

    $ins = $pdo->prepare(
        "INSERT INTO bib_prestiti
         (id_utente, id_copia, id_prenotazione, data_scadenza, stato, confermato_bibliotecario)
         VALUES (?,?,?,?,'attivo',1)"
    );
    $ins->execute([$pren['id_utente'], $pren['id_copia'], $idPren, $ds]);
    $idPrestito = $pdo->lastInsertId();

    $pdo->prepare("UPDATE bib_prenotazioni SET stato='ritirata' WHERE id=?")->execute([$idPren]);
    $pdo->prepare("UPDATE bib_copie SET stato='in_prestito' WHERE id=?")->execute([$pren['id_copia']]);

    logOperazione($pdo, $idUtente, 'conferma_ritiro_api',
        "Prestito ID {$idPrestito}", $idPrestito, 'prestito');

    jsonResponse(true, ['id_prestito' => $idPrestito], 'Ritiro confermato.');
}

// ============================================================
// POST: Conferma restituzione (bibliotecario)
// ============================================================
if ($action === 'restituzione' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkApiCsrf();
    if (!canManageBooks($userLevel)) jsonResponse(false, [], 'Permesso negato.');

    $idPrestito = (int)($_POST['id_prestito'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM bib_prestiti WHERE id = ? AND stato = 'attivo'");
    $stmt->execute([$idPrestito]);
    $prestito = $stmt->fetch();
    if (!$prestito) jsonResponse(false, [], 'Prestito non trovato.');

    $pdo->prepare(
        "UPDATE bib_prestiti SET stato='restituito', data_restituzione=NOW() WHERE id=?"
    )->execute([$idPrestito]);
    $pdo->prepare("UPDATE bib_copie SET stato='disponibile' WHERE id=?")
        ->execute([$prestito['id_copia']]);

    logOperazione($pdo, $idUtente, 'restituzione_api',
        "Prestito ID {$idPrestito}", $idPrestito, 'prestito');

    jsonResponse(true, [], 'Restituzione confermata.');
}

// ============================================================
// GET: Studenti di una classe
// ============================================================
if ($action === 'get_studenti' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!canBookForClass($userLevel)) jsonResponse(false, [], 'Permesso negato.');

    $idClasse = (int)($_GET['id_classe'] ?? 0);
    if (!$idClasse) jsonResponse(false, [], 'ID classe mancante.');

    $studenti = getStudentiPerClasse($pdo, $idClasse);
    jsonResponse(true, ['studenti' => $studenti]);
}

// ============================================================
// POST: Aggiungi utente a blacklist (bibliotecario)
// ============================================================
if ($action === 'aggiungi_blacklist' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    checkApiCsrf();
    if (!canManageBooks($userLevel)) jsonResponse(false, [], 'Permesso negato.');

    $idTarget = (int)($_POST['id_utente'] ?? 0);
    $motivo   = sanitizeString($_POST['motivo'] ?? '', 500);

    if ($idTarget === $idUtente) {
        jsonResponse(false, [], 'Non puoi aggiungere te stesso alla blacklist.');
    }

    $livelloTarget = getUserLevel($pdo, $idTarget);
    if (canManageBooks($livelloTarget)) {
        jsonResponse(false, [], 'Non puoi aggiungere un bibliotecario alla blacklist.');
    }

    $pdo->prepare("UPDATE bib_blacklist SET attiva = 0 WHERE id_utente = ? AND attiva = 1")
        ->execute([$idTarget]);
    $pdo->prepare(
        "INSERT INTO bib_blacklist (id_utente, motivo, attiva) VALUES (?, ?, 1)"
    )->execute([$idTarget, $motivo]);

    logOperazione($pdo, $idUtente, 'aggiungi_blacklist_api',
        "Utente ID {$idTarget}", $idTarget, 'utente');

    jsonResponse(true, [], 'Utente aggiunto alla blacklist.');
}

// ============================================================
// GET: Controlla se l'utente corrente è in blacklist
// ============================================================
if ($action === 'check_blacklist') {
    $blacklisted = isBlacklisted($pdo, $idUtente);
    jsonResponse(true, ['blacklisted' => $blacklisted]);
}

// ============================================================
// GET: Statistiche rapide (per dashboard)
// ============================================================
if ($action === 'stats' && canManageBooks($userLevel)) {
    $totLibri      = (int)$pdo->query("SELECT COUNT(*) FROM bib_libri")->fetchColumn();
    $totCopie      = (int)$pdo->query("SELECT COUNT(*) FROM bib_copie")->fetchColumn();
    $totDisp       = (int)$pdo->query("SELECT COUNT(*) FROM bib_copie WHERE stato='disponibile'")->fetchColumn();
    $totPren       = (int)$pdo->query("SELECT COUNT(*) FROM bib_prenotazioni WHERE stato IN ('in_attesa','confermata')")->fetchColumn();
    $totPrestiti   = (int)$pdo->query("SELECT COUNT(*) FROM bib_prestiti WHERE stato='attivo'")->fetchColumn();
    $totBlacklist  = (int)$pdo->query("SELECT COUNT(*) FROM bib_blacklist WHERE attiva=1")->fetchColumn();

    jsonResponse(true, [
        'libri'     => $totLibri,
        'copie'     => $totCopie,
        'disponibili' => $totDisp,
        'prenotazioni' => $totPren,
        'prestiti'  => $totPrestiti,
        'blacklist' => $totBlacklist,
    ]);
}

// ── Azione non riconosciuta ────────────────────────────────
jsonResponse(false, [], 'Azione non riconosciuta: ' . htmlspecialchars($action));
