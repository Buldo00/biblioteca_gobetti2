<?php
// ============================================================
// FUNCTIONS.PHP - Funzioni di utilità del sistema biblioteca
// ============================================================

/**
 * Carica tutte le impostazioni dal database in un array associativo.
 */
function getImpostazioni(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT chiave, valore FROM bib_impostazioni");
    $rows = $stmt->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[$row['chiave']] = $row['valore'];
    }
    return $result;
}

/**
 * Restituisce il valore di una singola impostazione.
 */
function getImpostazione(PDO $pdo, string $chiave, string $default = ''): string
{
    $stmt = $pdo->prepare("SELECT valore FROM bib_impostazioni WHERE chiave = ?");
    $stmt->execute([$chiave]);
    $row = $stmt->fetch();
    return $row ? $row['valore'] : $default;
}

/**
 * Conta le prenotazioni attive di un utente (in_attesa o confermata).
 */
function countPrenotazioniAttive(PDO $pdo, int $idUtente): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM bib_prenotazioni
         WHERE id_utente = ? AND stato IN ('in_attesa','confermata')"
    );
    $stmt->execute([$idUtente]);
    return (int)$stmt->fetchColumn();
}

/**
 * Conta i prestiti attivi di un utente.
 */
function countPrestitiAttivi(PDO $pdo, int $idUtente): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM bib_prestiti
         WHERE id_utente = ? AND stato = 'attivo'"
    );
    $stmt->execute([$idUtente]);
    return (int)$stmt->fetchColumn();
}

/**
 * Genera un codice QR univoco per una copia.
 * Formato: QR-LIB-{idLibro}-{numeroCopia}-{timestamp}
 */
function generaQrCode(int $idLibro, int $numeroCopia): string
{
    return 'QR-LIB-' . $idLibro . '-' . $numeroCopia . '-' . time();
}

/**
 * Aggiunge una voce al log delle operazioni.
 */
function logOperazione(
    PDO $pdo,
    int $idUtente,
    string $azione,
    ?string $dettagli = null,
    ?int $idRiferimento = null,
    ?string $tipoRiferimento = null
): void {
    $stmt = $pdo->prepare(
        "INSERT INTO bib_log (id_utente, azione, dettagli, id_riferimento, tipo_riferimento)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$idUtente, $azione, $dettagli, $idRiferimento, $tipoRiferimento]);
}

/**
 * Verifica quante prenotazioni "scadute" (non ritirate) ha un utente.
 * Usato per la blacklist automatica.
 */
function countPrenotazioniScadute(PDO $pdo, int $idUtente): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM bib_prenotazioni
         WHERE id_utente = ? AND stato = 'scaduta'"
    );
    $stmt->execute([$idUtente]);
    return (int)$stmt->fetchColumn();
}

/**
 * Verifica e aggiorna prenotazioni scadute (cron-like, chiamata ad ogni richiesta).
 * Segna come "scaduta" le prenotazioni confermate ma non ritirate oltre la data_scadenza.
 */
function aggiornaPrenotazioniScadute(PDO $pdo): void
{
    $pdo->exec(
        "UPDATE bib_prenotazioni
         SET stato = 'scaduta'
         WHERE stato = 'confermata'
           AND data_scadenza IS NOT NULL
           AND data_scadenza < NOW()"
    );
    // Libera le copie prenotate ma scadute
    $pdo->exec(
        "UPDATE bib_copie c
         INNER JOIN bib_prenotazioni p ON p.id_copia = c.id
         SET c.stato = 'disponibile'
         WHERE p.stato = 'scaduta' AND c.stato = 'prenotato'"
    );
}

/**
 * Verifica e aggiorna prestiti scaduti.
 */
function aggiornaPrestitiScaduti(PDO $pdo): void
{
    $pdo->exec(
        "UPDATE bib_prestiti
         SET stato = 'scaduto'
         WHERE stato = 'attivo'
           AND data_scadenza IS NOT NULL
           AND data_scadenza < NOW()"
    );
}

/**
 * Restituisce il numero di copie disponibili per un libro.
 */
function getCopieDiponibili(PDO $pdo, int $idLibro): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM bib_copie
         WHERE id_libro = ? AND stato = 'disponibile'"
    );
    $stmt->execute([$idLibro]);
    return (int)$stmt->fetchColumn();
}

/**
 * Controlla se l'utente ha già prenotato o ha in prestito un determinato libro.
 */
function haPrenotatoLibro(PDO $pdo, int $idUtente, int $idLibro): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM bib_prenotazioni
         WHERE id_utente = ? AND id_libro = ? AND stato IN ('in_attesa','confermata')"
    );
    $stmt->execute([$idUtente, $idLibro]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Controlla se l'utente ha richiesto notifica per un libro.
 */
function haRichiestoAvviso(PDO $pdo, int $idUtente, int $idLibro): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM bib_avvisami
         WHERE id_utente = ? AND id_libro = ? AND notificato = 0"
    );
    $stmt->execute([$idUtente, $idLibro]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Sanitizza e limita l'input stringa.
 */
function sanitizeString(string $input, int $maxLen = 255): string
{
    return substr(trim(strip_tags($input)), 0, $maxLen);
}

/**
 * Restituisce un badge HTML colorato per lo stato della copia.
 */
function statoCopiaLabel(string $stato): string
{
    $map = [
        'disponibile' => ['success',  'Disponibile'],
        'prenotato'   => ['warning',  'Prenotato'],
        'in_prestito' => ['primary',  'In prestito'],
        'danneggiato' => ['danger',   'Danneggiato'],
        'smarrito'    => ['secondary','Smarrito'],
    ];
    [$color, $label] = $map[$stato] ?? ['secondary', ucfirst($stato)];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Restituisce un badge HTML per lo stato di una prenotazione.
 */
function statoPrenotazioneLabel(string $stato): string
{
    $map = [
        'in_attesa'  => ['info',      'In attesa'],
        'confermata' => ['success',   'Confermata'],
        'annullata'  => ['secondary', 'Annullata'],
        'scaduta'    => ['danger',    'Scaduta'],
        'ritirata'   => ['primary',   'Ritirata'],
    ];
    [$color, $label] = $map[$stato] ?? ['secondary', ucfirst($stato)];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Restituisce un badge HTML per lo stato di un prestito.
 */
function statoPrestitoLabel(string $stato): string
{
    $map = [
        'attivo'      => ['success', 'Attivo'],
        'restituito'  => ['secondary','Restituito'],
        'scaduto'     => ['danger',  'Scaduto'],
    ];
    [$color, $label] = $map[$stato] ?? ['secondary', ucfirst($stato)];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Formatta una data italiana da stringa datetime.
 */
function formatData(?string $datetime): string
{
    if (!$datetime) return '—';
    $d = new DateTime($datetime);
    return $d->format('d/m/Y');
}

/**
 * Formatta data e ora italiana da stringa datetime.
 */
function formatDataOra(?string $datetime): string
{
    if (!$datetime) return '—';
    $d = new DateTime($datetime);
    return $d->format('d/m/Y H:i');
}

/**
 * Costruisce l'URL dell'immagine di copertina (o placeholder se assente).
 */
function getCopertinaSrc(?string $copertina): string
{
    if ($copertina && file_exists(__DIR__ . '/../uploads/copertine/' . $copertina)) {
        return 'uploads/copertine/' . htmlspecialchars($copertina);
    }
    return 'assets/img/no-cover.png';
}

/**
 * Genera l'URL immagine QR tramite API qrserver.com.
 */
function getQrImageUrl(string $qrCode, int $size = 150): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
        . '&data=' . urlencode($qrCode);
}

/**
 * Reindirizza con un messaggio flash in sessione.
 */
function redirectWith(string $url, string $tipo, string $messaggio): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'messaggio' => $messaggio];
    header('Location: ' . $url);
    exit;
}

/**
 * Mostra e svuota il messaggio flash dalla sessione.
 */
function showFlash(): void
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $tipo = in_array($f['tipo'], ['success','danger','warning','info']) ? $f['tipo'] : 'info';
        echo '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">'
            . htmlspecialchars($f['messaggio'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
            . '</div>';
    }
}

/**
 * Gestisce upload copertina: valida tipo/dimensione, sposta il file.
 * Restituisce il nome del file salvato o null in caso di errore.
 */
function uploadCopertina(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedMimes)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // max 5 MB

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('cover_', true) . '.' . strtolower($ext);
    $dest     = __DIR__ . '/../uploads/copertine/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $filename;
}

/**
 * Ottiene la lista delle classi dal DB.
 */
function getClassi(PDO $pdo): array
{
    return $pdo->query(
        "SELECT IDClasse, anno, sezione FROM classi ORDER BY anno, sezione"
    )->fetchAll();
}

/**
 * Ottiene studenti di una classe.
 */
function getStudentiPerClasse(PDO $pdo, int $idClasse): array
{
    $stmt = $pdo->prepare(
        "SELECT s.IDStudente, s.IDUtente, s.nomeStu, s.cognomeStu, u.emailUtente
         FROM studenti s
         JOIN utenti u ON u.IDUtente = s.IDUtente
         WHERE s.IDClasse = ?
         ORDER BY s.cognomeStu, s.nomeStu"
    );
    $stmt->execute([$idClasse]);
    return $stmt->fetchAll();
}

/**
 * Controlla CSRF token.
 */
function checkCsrf(): void
{
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Token CSRF non valido.');
    }
}

/**
 * Genera e memorizza un CSRF token in sessione.
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
