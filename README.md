# Biblioteca Gobetti 2 — Avvio e test in locale

## 1) Prerequisiti
- PHP 8+ disponibile da terminale
- MySQL o MariaDB attivo in locale

## 2) Crea il database
Apri MySQL e crea il database richiesto dal progetto:

```sql
CREATE DATABASE IF NOT EXISTS vr5av337_gobettiservicesprova
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

## 3) Importa i dump SQL
Da terminale:

```bash
mysql -u root -p vr5av337_gobettiservicesprova < /home/runner/work/biblioteca_gobetti2/biblioteca_gobetti2/database.sql
mysql -u root -p vr5av337_gobettiservicesprova < /home/runner/work/biblioteca_gobetti2/biblioteca_gobetti2/biblioteca/database_biblioteca.sql
```

> Il primo dump importa il database esistente, il secondo aggiunge le tabelle della biblioteca (`bib_*`).

## 4) Verifica credenziali DB del progetto
File usato dalla connessione:

`/home/runner/work/biblioteca_gobetti2/biblioteca_gobetti2/biblioteca/includes/db.php`

Default locali:
- host: `localhost`
- user: `root`
- password: vuota
- db: `vr5av337_gobettiservicesprova`

Se usi credenziali diverse, imposta variabili ambiente prima di avviare PHP:

```bash
export DB_USER='tuo_utente'
export DB_PASS='tua_password'
export DB_NAME='vr5av337_gobettiservicesprova'
```

## 5) Avvia il server PHP

```bash
cd /home/runner/work/biblioteca_gobetti2/biblioteca_gobetti2
php -S 127.0.0.1:8000
```

## 6) Apri l'app nel browser

Vai su:

`http://127.0.0.1:8000/biblioteca/index.php`

## 7) Verifica rapida
- Se la pagina si apre, il test locale è OK.
- Se compare errore DB:
  - controlla che MySQL sia acceso
  - ricontrolla nome database/utente/password
  - verifica che entrambi i dump siano stati importati
