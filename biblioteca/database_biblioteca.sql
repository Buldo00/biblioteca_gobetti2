-- ============================================================
-- BIBLIOTECA GOBETTI - Database SQL
-- Tabelle aggiuntive per il sistema biblioteca
-- Da importare nel database esistente
-- ============================================================

-- Tabella: bib_libri (catalogo libri)
CREATE TABLE IF NOT EXISTS `bib_libri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titolo` varchar(255) NOT NULL,
  `autore` varchar(255) NOT NULL,
  `anno` int(4) DEFAULT NULL,
  `casa_editrice` varchar(255) DEFAULT NULL,
  `lingua` varchar(50) DEFAULT NULL,
  `genere` varchar(100) DEFAULT NULL,
  `codice_dewey` varchar(50) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `copertina` varchar(500) DEFAULT NULL,
  `trama` text,
  `tipologia` enum('libro','rivista','dizionario','manuale') NOT NULL DEFAULT 'libro',
  `aula` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabella: bib_copie (copie fisiche dei libri)
CREATE TABLE IF NOT EXISTS `bib_copie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_libro` int(11) NOT NULL,
  `numero_copia` int(11) NOT NULL,
  `qr_code` varchar(100) UNIQUE NOT NULL,
  `armadio` varchar(50) DEFAULT NULL,
  `ripiano` varchar(50) DEFAULT NULL,
  `stato` enum('disponibile','prenotato','in_prestito','danneggiato','smarrito') NOT NULL DEFAULT 'disponibile',
  `note_danno` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_libro` (`id_libro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabella: bib_prenotazioni (prenotazioni)
CREATE TABLE IF NOT EXISTS `bib_prenotazioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `id_libro` int(11) NOT NULL,
  `id_copia` int(11) DEFAULT NULL,
  `tipo` enum('personale','classe') NOT NULL DEFAULT 'personale',
  `id_classe` int(10) DEFAULT NULL,
  `stato` enum('in_attesa','confermata','annullata','scaduta','ritirata') NOT NULL DEFAULT 'in_attesa',
  `data_prenotazione` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_scadenza` datetime DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  KEY `id_utente` (`id_utente`),
  KEY `id_libro` (`id_libro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabella: bib_prestiti (prestiti)
CREATE TABLE IF NOT EXISTS `bib_prestiti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `id_copia` int(11) NOT NULL,
  `id_prenotazione` int(11) DEFAULT NULL,
  `data_prestito` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_scadenza` datetime DEFAULT NULL,
  `data_restituzione` datetime DEFAULT NULL,
  `stato` enum('attivo','restituito','scaduto') NOT NULL DEFAULT 'attivo',
  `confermato_bibliotecario` tinyint(1) NOT NULL DEFAULT '0',
  `confermato_utente` tinyint(1) NOT NULL DEFAULT '0',
  `note` text,
  PRIMARY KEY (`id`),
  KEY `id_utente` (`id_utente`),
  KEY `id_copia` (`id_copia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabella: bib_blacklist
CREATE TABLE IF NOT EXISTS `bib_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `motivo` text,
  `data_inizio` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_fine` datetime DEFAULT NULL,
  `attiva` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id_utente` (`id_utente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabella: bib_avvisami (notifiche disponibilità)
CREATE TABLE IF NOT EXISTS `bib_avvisami` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `id_libro` int(11) NOT NULL,
  `data_richiesta` datetime DEFAULT CURRENT_TIMESTAMP,
  `notificato` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `utente_libro` (`id_utente`, `id_libro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Tabella: bib_impostazioni (impostazioni sistema)
CREATE TABLE IF NOT EXISTS `bib_impostazioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chiave` varchar(100) NOT NULL UNIQUE,
  `valore` text NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Impostazioni predefinite
INSERT INTO `bib_impostazioni` (`chiave`, `valore`, `descrizione`) VALUES
('max_prestiti_studente', '3', 'Numero massimo di prestiti per gli studenti'),
('giorni_ritiro', '3', 'Giorni disponibili per il ritiro dopo la prenotazione'),
('max_mancate_prenotazioni_blacklist', '3', 'Numero di prenotazioni non ritirate prima di blacklist automatica'),
('giorni_prestito', '30', 'Durata in giorni di un prestito'),
('orario_apertura', '08:00', 'Orario di apertura biblioteca'),
('orario_chiusura', '17:00', 'Orario di chiusura biblioteca'),
('email_mittente', 'biblioteca@gobetti.edu.it', 'Email mittente per notifiche'),
('nome_biblioteca', 'Biblioteca Gobetti', 'Nome della biblioteca'),
('max_prestiti_docente', '10', 'Numero massimo di prestiti per i docenti')
ON DUPLICATE KEY UPDATE valore=VALUES(valore);

-- Tabella: bib_log (log operazioni)
CREATE TABLE IF NOT EXISTS `bib_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utente` int(10) UNSIGNED NOT NULL,
  `azione` varchar(100) NOT NULL,
  `dettagli` text,
  `id_riferimento` int(11) DEFAULT NULL,
  `tipo_riferimento` varchar(50) DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dati di esempio: libri
INSERT INTO `bib_libri` (`titolo`, `autore`, `anno`, `casa_editrice`, `lingua`, `genere`, `codice_dewey`, `isbn`, `trama`, `tipologia`, `aula`) VALUES
('Il Nome della Rosa', 'Umberto Eco', 1980, 'Bompiani', 'Italiano', 'Romanzo storico', '853.914', '9788845292613', 'Un monaco francescano investiga una serie di morti misteriose in un monastero medievale.', 'libro', 'B1'),
('Divina Commedia', 'Dante Alighieri', 1320, 'Mondadori', 'Italiano', 'Poesia', '851.1', '9788804668237', 'Il capolavoro della letteratura italiana, viaggio nell\'oltretomba.', 'libro', 'B1'),
('I Promessi Sposi', 'Alessandro Manzoni', 1827, 'Einaudi', 'Italiano', 'Romanzo storico', '853.7', '9788806209148', 'Romanzo storico ambientato nella Lombardia del XVII secolo.', 'libro', 'B1'),
('Matematica Blu 2.0', 'Bergamini, Trifone, Barozzi', 2017, 'Zanichelli', 'Italiano', 'Manuale scolastico', '510', '9788808220752', 'Manuale di matematica per le scuole superiori.', 'manuale', 'A3'),
('Fisica: principi e problemi', 'Serway, Jewett', 2018, 'Zanichelli', 'Italiano', 'Manuale scolastico', '530', '9788808421357', 'Testo di fisica per licei scientifici.', 'manuale', 'A3');

-- Dati di esempio: copie
INSERT INTO `bib_copie` (`id_libro`, `numero_copia`, `qr_code`, `armadio`, `ripiano`, `stato`) VALUES
(1, 1, 'QR-LIB-1-1', 'A1', '1', 'disponibile'),
(1, 2, 'QR-LIB-1-2', 'A1', '1', 'disponibile'),
(2, 1, 'QR-LIB-2-1', 'A1', '2', 'disponibile'),
(3, 1, 'QR-LIB-3-1', 'A1', '2', 'disponibile'),
(3, 2, 'QR-LIB-3-2', 'A1', '3', 'disponibile'),
(4, 1, 'QR-LIB-4-1', 'B2', '1', 'disponibile'),
(4, 2, 'QR-LIB-4-2', 'B2', '1', 'disponibile'),
(4, 3, 'QR-LIB-4-3', 'B2', '1', 'disponibile'),
(5, 1, 'QR-LIB-5-1', 'B2', '2', 'disponibile'),
(5, 2, 'QR-LIB-5-2', 'B2', '2', 'disponibile');
