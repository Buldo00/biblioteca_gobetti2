<?php
// ============================================================
// HEADER.PHP - Intestazione HTML e barra di navigazione
// ============================================================
// Variabili attese nel contesto chiamante:
//   $pageTitle  - titolo della pagina
//   $userLevel  - livello dell'utente corrente
//   $currentUser - array dati utente
// ============================================================
$pageTitle   = $pageTitle   ?? 'Biblioteca Gobetti';
$userLevel   = $userLevel   ?? 0;
$currentUser = $currentUser ?? [];

$nomeUtente = '';
if (!empty($currentUser['nomeStu'])) {
    $nomeUtente = $currentUser['nomeStu'] . ' ' . $currentUser['cognomeStu'];
} elseif (!empty($currentUser['emailUtente'])) {
    $nomeUtente = $currentUser['emailUtente'];
}

$levelColor = getLevelColor($userLevel);
$levelLabel = getLevelLabel($userLevel);

// Pagina corrente per evidenziare il menu
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – Biblioteca Gobetti</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- CSS personalizzato biblioteca -->
    <link href="<?= rtrim(dirname($_SERVER['PHP_SELF']), '/') ?>/assets/css/biblioteca.css" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm" id="mainNavbar">
    <div class="container-fluid">
        <!-- Logo / Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-book-half fs-4 text-<?= $levelColor ?>"></i>
            <span class="fw-bold">Biblioteca Gobetti</span>
        </a>

        <!-- Hamburger per mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMenu" aria-controls="navbarMenu"
                aria-expanded="false" aria-label="Apri menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <!-- Catalogo – visibile a tutti -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
                       href="index.php">
                        <i class="bi bi-search me-1"></i>Catalogo
                    </a>
                </li>

                <!-- I miei prestiti – visibile a tutti -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'prestiti.php' && !isset($_GET['all']) ? 'active' : '' ?>"
                       href="prestiti.php">
                        <i class="bi bi-bookmark-check me-1"></i>I miei prestiti
                    </a>
                </li>

                <?php if (canBookForClass($userLevel)): ?>
                <!-- Prestiti di classe – Docenti e superiori -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'prestiti.php' && isset($_GET['classe']) ? 'active' : '' ?>"
                       href="prestiti.php?classe=1">
                        <i class="bi bi-people me-1"></i>Prestiti di classe
                    </a>
                </li>
                <?php endif; ?>

                <?php if (canManageBooks($userLevel)): ?>
                <!-- Sezione gestione – Bibliotecari e superiori -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentPage, ['gestione_libri.php','gestione_copie.php']) ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-archive me-1"></i>Gestione
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li>
                            <a class="dropdown-item <?= $currentPage === 'gestione_libri.php' ? 'active' : '' ?>"
                               href="gestione_libri.php">
                                <i class="bi bi-journal-plus me-1"></i>Gestione libri
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $currentPage === 'gestione_copie.php' ? 'active' : '' ?>"
                               href="gestione_copie.php">
                                <i class="bi bi-upc-scan me-1"></i>Gestione copie
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item <?= $currentPage === 'prestiti.php' && isset($_GET['all']) ? 'active' : '' ?>"
                               href="prestiti.php?all=1">
                                <i class="bi bi-list-check me-1"></i>Tutti i prestiti
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= $currentPage === 'blacklist.php' ? 'active' : '' ?>"
                               href="blacklist.php">
                                <i class="bi bi-person-x me-1"></i>Blacklist
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isAdmin($userLevel)): ?>
                <!-- Impostazioni – solo Admin -->
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'impostazioni.php' ? 'active' : '' ?>"
                       href="impostazioni.php">
                        <i class="bi bi-gear me-1"></i>Impostazioni
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Informazioni utente -->
            <div class="d-flex align-items-center gap-2 text-light">
                <span class="badge bg-<?= $levelColor ?> text-dark-on-light">
                    <i class="bi bi-person-fill me-1"></i>
                    <?= htmlspecialchars($nomeUtente ?: 'Utente') ?>
                </span>
                <span class="badge bg-secondary">
                    <?= htmlspecialchars($levelLabel) ?>
                </span>
            </div>
        </div>
    </div>
</nav>
<!-- FINE NAVBAR -->

<div class="container-fluid py-3 px-3 px-lg-4" id="mainContent">
    <!-- Barra colorata indicatrice del livello utente -->
    <div class="level-bar bg-<?= $levelColor ?> mb-3"></div>
