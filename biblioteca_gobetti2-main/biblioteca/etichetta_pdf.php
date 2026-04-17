<?php
// ============================================================
// ETICHETTA_PDF.PHP – Generazione etichette stampa (HTML+CSS)
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$idUtente    = (int)$_SESSION['IDUtente'];
$userLevel   = getUserLevel($pdo, $idUtente);

if (!canManageBooks($userLevel)) {
    redirectWith('index.php', 'danger', 'Accesso non autorizzato.');
}

$idLibro = isset($_GET['id_libro']) ? (int)$_GET['id_libro'] : 0;
$idCopia = isset($_GET['id_copia']) ? (int)$_GET['id_copia'] : 0;

$copie = [];

if ($idCopia) {
    // Singola copia
    $stmt = $pdo->prepare(
        "SELECT c.*, l.titolo, l.autore, l.codice_dewey, l.aula, l.isbn
         FROM bib_copie c
         JOIN bib_libri l ON l.id = c.id_libro
         WHERE c.id = ?"
    );
    $stmt->execute([$idCopia]);
    $copie = $stmt->fetchAll();

} elseif ($idLibro) {
    // Tutte le copie di un libro
    $stmt = $pdo->prepare(
        "SELECT c.*, l.titolo, l.autore, l.codice_dewey, l.aula, l.isbn
         FROM bib_copie c
         JOIN bib_libri l ON l.id = c.id_libro
         WHERE c.id_libro = ?
         ORDER BY c.numero_copia"
    );
    $stmt->execute([$idLibro]);
    $copie = $stmt->fetchAll();
}

if (empty($copie)) {
    redirectWith('gestione_libri.php', 'warning', 'Nessuna copia trovata per generare le etichette.');
}

$nomeBiblioteca = getImpostazione($pdo, 'nome_biblioteca', 'Biblioteca Gobetti');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etichette – <?= htmlspecialchars($copie[0]['titolo'] ?? 'Libro') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ── Stile generale ──────────────────────────────── */
        body {
            background: #f4f4f4;
            font-family: 'Courier New', monospace;
        }

        .no-print {
            background: #fff;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #ddd;
        }

        /* ── Layout etichette ────────────────────────────── */
        #printArea {
            display: flex;
            flex-wrap: wrap;
            gap: 8mm;
            padding: 10mm;
            background: #fff;
        }

        /* ── Singola etichetta ───────────────────────────── */
        .etichetta {
            width: 90mm;
            min-height: 56mm;
            border: 2px solid #222;
            border-radius: 3px;
            padding: 4mm 5mm;
            background: #fff;
            page-break-inside: avoid;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        .etichetta-header {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            border-bottom: 1px solid #333;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
            letter-spacing: 0.05em;
        }

        .etichetta-body {
            display: flex;
            gap: 3mm;
        }

        .etichetta-info {
            flex: 1;
            font-size: 8.5pt;
            line-height: 1.6;
        }

        .etichetta-info strong {
            display: inline-block;
            min-width: 24mm;
            font-size: 7.5pt;
            color: #555;
        }

        .etichetta-qr-wrap {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1mm;
        }

        .etichetta-qr-wrap img {
            width: 22mm;
            height: 22mm;
        }

        .etichetta-qr-wrap small {
            font-size: 6pt;
            word-break: break-all;
            text-align: center;
            max-width: 22mm;
        }

        .etichetta-dewey {
            margin-top: 2mm;
            font-size: 14pt;
            font-weight: 900;
            text-align: center;
            letter-spacing: 0.08em;
            border: 2px solid #222;
            display: inline-block;
            padding: 1mm 3mm;
            border-radius: 2px;
        }

        .etichetta-numero {
            position: absolute;
            top: 2mm;
            right: 3mm;
            font-size: 18pt;
            font-weight: 900;
            color: #ccc;
        }

        /* ── Media print ─────────────────────────────────── */
        @media print {
            body, html { background: #fff; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            #printArea {
                padding: 5mm;
                gap: 5mm;
            }
            .etichetta {
                border: 1.5pt solid #000;
            }
        }
    </style>
</head>
<body>

<!-- Controlli (nascosti in stampa) -->
<div class="no-print">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0">
                    <i class="bi bi-printer me-2"></i>
                    Etichette: <?= htmlspecialchars($copie[0]['titolo'] ?? '') ?>
                </h5>
                <small class="text-muted"><?= count($copie) ?> etichett<?= count($copie) === 1 ? 'a' : 'e' ?></small>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i>Stampa / Salva PDF
                </button>
                <button onclick="window.close()" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i>Chiudi
                </button>
            </div>
        </div>
        <div class="alert alert-info mt-2 py-2 small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Per salvare come PDF: usa <strong>Stampa → Destinazione → Salva come PDF</strong>.
            Imposta margini minimi per ottimizzare lo spazio.
        </div>
    </div>
</div>

<!-- Area etichette (stampata) -->
<div id="printArea">
    <?php foreach ($copie as $copia):
        $qrUrl = getQrImageUrl($copia['qr_code'], 88); // ~22mm a 96dpi
    ?>
    <div class="etichetta">
        <!-- Numero copia in filigrana -->
        <div class="etichetta-numero">#<?= $copia['numero_copia'] ?></div>

        <!-- Intestazione biblioteca -->
        <div class="etichetta-header">
            <?= htmlspecialchars(mb_strtoupper($nomeBiblioteca)) ?>
        </div>

        <div class="etichetta-body">
            <!-- Dati bibliografici -->
            <div class="etichetta-info">
                <div>
                    <strong>Titolo:</strong>
                    <?= htmlspecialchars(mb_substr($copia['titolo'], 0, 40))
                        . (mb_strlen($copia['titolo']) > 40 ? '…' : '') ?>
                </div>
                <div>
                    <strong>Copia n.:</strong>
                    <?= $copia['numero_copia'] ?>
                </div>
                <?php if ($copia['armadio']): ?>
                <div>
                    <strong>Armadio:</strong>
                    <?= htmlspecialchars($copia['armadio']) ?>
                </div>
                <?php endif; ?>
                <?php if ($copia['ripiano']): ?>
                <div>
                    <strong>Ripiano:</strong>
                    <?= htmlspecialchars($copia['ripiano']) ?>
                </div>
                <?php endif; ?>
                <?php if ($copia['aula']): ?>
                <div>
                    <strong>Aula:</strong>
                    <?= htmlspecialchars($copia['aula']) ?>
                </div>
                <?php endif; ?>
                <?php if ($copia['isbn']): ?>
                <div>
                    <strong>ISBN:</strong>
                    <?= htmlspecialchars($copia['isbn']) ?>
                </div>
                <?php endif; ?>
                <?php if ($copia['codice_dewey']): ?>
                <div class="mt-1">
                    <div class="etichetta-dewey"><?= htmlspecialchars($copia['codice_dewey']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- QR Code -->
            <div class="etichetta-qr-wrap">
                <img src="<?= htmlspecialchars($qrUrl) ?>"
                     alt="QR <?= htmlspecialchars($copia['qr_code']) ?>"
                     loading="lazy">
                <small><?= htmlspecialchars($copia['qr_code']) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
