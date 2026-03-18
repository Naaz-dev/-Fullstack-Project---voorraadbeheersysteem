<?php
/**
 * Navigatie Header Include - ToolsForEver Voorraadbeheersysteem
 * 
 * Dit bestand bevat de HTML header en navigatiebalk voor alle pagina's.
 * Het wordt geïnclude aan het begin van elke pagina.
 * 
 * Functies:
 * - HTML head met meta tags en CSS
 * - Navigatiebalk met logo en menu items
 * - Dynamische menu items op basis van gebruikersrol
 * - Gebruikersinformatie en uitlog knop
 */
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamische pagina titel gebaseerd op $page_title variabele -->
    <title><?= isset($page_title) ? escape($page_title) : 'ToolsForEver' ?> - Voorraadbeheersysteem</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Hoofd navigatiebalk -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo en merknaam -->
            <div class="nav-brand">
                <a href="index.php">🔧 ToolsForEver</a>
            </div>
            
            <!-- Navigatie menu -->
            <ul class="nav-menu">
                <!-- Dashboard - toegankelijk voor iedereen -->
                <li><a href="index.php">Dashboard</a></li>
                <!-- Voorraad Overzicht - toegankelijk voor iedereen -->
                <li><a href="inventory.php">Voorraad Overzicht</a></li>
                
                <?php if (hasAnyRole(['admin', 'magazijn'])): ?>
                    <!-- Producten Beheer - alleen admin en magazijn -->
                    <li><a href="products.php">Producten</a></li>
                    <!-- Voorraad Beheer - alleen admin en magazijn -->
                    <li><a href="manage_inventory.php">Voorraad Beheer</a></li>
                <?php endif; ?>
                
                <?php if (hasAnyRole(['admin', 'magazijn'])): ?>
                    <!-- Bestellingen - alleen admin en magazijn -->
                    <li><a href="orders.php">Bestellingen</a></li>
                <?php endif; ?>
                
                <?php if (hasRole('admin')): ?>
                    <!-- Locaties Beheer - alleen admin -->
                    <li><a href="locations.php">Locaties</a></li>
                <?php endif; ?>
                
                <!-- Gebruikersinformatie en uitlog knop -->
                <li class="nav-user">
                    <span>👤 <?= escape($_SESSION['username']) ?> (<?= escape($_SESSION['role']) ?>)</span>
                    <a href="logout.php" class="btn btn-sm">Uitloggen</a>
                </li>
            </ul>
        </div>
    </nav>
    
    <!-- Begin van de hoofd content container (gesloten in footer.php) -->
    <main class="container">
