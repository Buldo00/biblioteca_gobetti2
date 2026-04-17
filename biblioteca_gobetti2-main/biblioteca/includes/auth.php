<?php
// ============================================================
// AUTH.PHP - Autenticazione e gestione permessi
// ============================================================

// Avvia la sessione se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Reindirizza alla home se l'utente non è autenticato
if (!isset($_SESSION['IDUtente'])) {
    header('Location: /');
    exit;
}

/**
 * Restituisce il livello massimo dell'utente (MAX livelloAccount).
 */
function getUserLevel(PDO $pdo, int $idUtente): int
{
    $stmt = $pdo->prepare(
        "SELECT MAX(t.livelloAccount) AS livello
         FROM utenti_tipolivelli ul
         JOIN tipolivelli t ON ul.idLivello = t.IDTipoAccount
         WHERE ul.idUtente = ?"
    );
    $stmt->execute([$idUtente]);
    $row = $stmt->fetch();
    return $row ? (int)$row['livello'] : 0;
}

/**
 * Restituisce dati base dell'utente corrente (email, nome studente ecc.)
 */
function getCurrentUser(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare(
        "SELECT u.IDUtente, u.emailUtente, u.emailPers, u.statoUtente,
                s.nomeStu, s.cognomeStu, s.IDClasse,
                c.anno, c.sezione
         FROM utenti u
         LEFT JOIN studenti s ON s.IDUtente = u.IDUtente
         LEFT JOIN classi c ON c.IDClasse = s.IDClasse
         WHERE u.IDUtente = ?"
    );
    $stmt->execute([$idUtente]);
    return $stmt->fetch() ?: [];
}

// Livelli permessi
// ─────────────────────────────────────────────────────────────
// 100  Studente
// 300  Docente  → può prenotare per classe, non gestisce libri
// 320  Bibliotecario → gestione libri/copie/prestiti
// 400  Tecnici        → stessi permessi bibliotecario
// 500  Collaboratori  → stessi permessi bibliotecario
// 600  Amministrativi → stessi permessi bibliotecario
// 900  Dirigenti      → stessi permessi bibliotecario
// 999  Admin          → tutto, incluse impostazioni
// ─────────────────────────────────────────────────────────────

/** Utente studente */
function isStudent(int $level): bool { return $level === 100; }

/** Docente (ha permessi extra di prenotazione classe, ma NON gestione libri) */
function isTeacher(int $level): bool { return $level === 300; }

/**
 * Utente con permessi da bibliotecario:
 * livello 320 oppure >= 400 (Tecnici, Collaboratori, Amministrativi, Dirigenti, Admin)
 */
function canManageBooks(int $level): bool
{
    return $level === 320 || $level >= 400;
}

/** Alias per compatibilità */
function isLibrarian(int $level): bool { return canManageBooks($level); }

/** Solo admin (livello 999) */
function isAdmin(int $level): bool { return $level >= 999; }

/** Docente o superiore (può fare prenotazioni di classe) */
function canBookForClass(int $level): bool { return $level >= 300; }

/** Verifica se l'utente è in blacklist attiva */
function isBlacklisted(PDO $pdo, int $idUtente): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM bib_blacklist
         WHERE id_utente = ? AND attiva = 1
           AND (data_fine IS NULL OR data_fine > NOW())"
    );
    $stmt->execute([$idUtente]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Restituisce il colore Bootstrap associato al livello utente.
 * Usato nella navbar e nei badge.
 */
function getLevelColor(int $level): string
{
    if ($level >= 999) return 'danger';       // Admin → rosso
    if (canManageBooks($level)) return 'warning'; // Bibliotecario+ → arancio
    if ($level === 300) return 'success';     // Docente → verde
    return 'primary';                         // Studente → blu
}

/**
 * Restituisce l'etichetta testuale del livello utente.
 */
function getLevelLabel(int $level): string
{
    if ($level >= 999) return 'Amministratore';
    if ($level >= 900) return 'Dirigente';
    if ($level >= 600) return 'Amministrativo';
    if ($level >= 500) return 'Collaboratore';
    if ($level >= 400) return 'Tecnico';
    if ($level === 320) return 'Bibliotecario';
    if ($level === 300) return 'Docente';
    if ($level === 100) return 'Studente';
    return 'Utente';
}
