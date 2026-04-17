<?php
// ============================================================
// CONFIGURAZIONE DATABASE LOCALE
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'vr5av337_gobettiservicesprova');

// Connessione PDO al database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
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
