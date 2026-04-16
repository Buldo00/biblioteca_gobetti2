<?php
// ============================================================
// CONFIGURAZIONE DATABASE
// IMPORTANTE: Non inserire mai credenziali reali in questo file
// se il codice è versionato. Utilizzare variabili d'ambiente
// oppure creare un file db.local.php (escluso dal repo) e
// includerlo al posto di questo.
// ============================================================
// Leggi da variabili d'ambiente, con fallback ai valori di default
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'YOUR_DB_USER');   // ← Impostare tramite variabile d'ambiente DB_USER
define('DB_PASS', getenv('DB_PASS') ?: 'YOUR_DB_PASS');   // ← Impostare tramite variabile d'ambiente DB_PASS
define('DB_NAME', getenv('DB_NAME') ?: 'YOUR_DB_NAME');   // ← Impostare tramite variabile d'ambiente DB_NAME

// Connessione PDO al database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // In produzione non mostrare dettagli dell'errore
    die('<div style="padding:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;margin:20px;">
        <strong>Errore di connessione al database.</strong> Verificare la configurazione in includes/db.php
    </div>');
}
