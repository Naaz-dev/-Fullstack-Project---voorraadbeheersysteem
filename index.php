<?php
/**
 * Dashboard Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Dit is de hoofdpagina die gebruikers zien na het inloggen.
 * Het dashboard toont:
 * - Belangrijke statistieken (voorraadwaardes, lage voorraad, openstaande bestellingen)
 * - Overzicht van producten onder minimum voorraad
 * - Recente bestellingen
 * 
 * Toegang: Alle ingelogde gebruikers
 */

require_once 'includes/config.php';
require_once 'includes/db_functions.php';

// Controleer of gebruiker is ingelogd
requireLogin();

$page_title = 'Dashboard';

// Haal statistieken op uit de database
$total_value = getTotalInventoryValue();  // Totale voorraadwaarde (inkoop + verkoop)
$low_stock = getLowStockProducts();        // Producten onder minimum niveau
$recent_orders = getAllOrders();            // Alle bestellingen

include 'includes/nav_header.php';
?>

<div class="dashboard">
    <h1>Welkom, <?= escape($_SESSION['username']) ?>!</h1>
    <p class="subtitle">Rol: <?= escape(ucfirst($_SESSION['role'])) ?></p>
    
    <div class="stats-grid">
        <!-- Statistiek kaart: Totale Inkoopwaarde -->
        <div class="stat-card">
            <div class="stat-content">
                <h3>Totale Inkoopwaarde</h3>
                <p class="stat-value">€ <?= number_format($total_value['total_purchase_value'], 2, ',', '.') ?></p>
            </div>
        </div>
        
        <!-- Statistiek kaart: Totale Verkoopwaarde -->
        <div class="stat-card">
            <div class="stat-content">
                <h3>Totale Verkoopwaarde</h3>
                <p class="stat-value">€ <?= number_format($total_value['total_sale_value'], 2, ',', '.') ?></p>
            </div>
        </div>
        
        <!-- Statistiek kaart: Producten onder minimum -->
        <div class="stat-card">
            <div class="stat-content">
                <h3>Producten Onder Minimum</h3>
                <p class="stat-value"><?= count($low_stock) ?></p>
            </div>
        </div>
        
        <!-- Statistiek kaart: Openstaande bestellingen -->
        <div class="stat-card">
            <div class="stat-content">
                <h3>Openstaande Bestellingen</h3>
                <p class="stat-value">
                    <?php
                    // Tel het aantal bestellingen dat nog niet is geleverd
                    $pending = 0;
                    foreach ($recent_orders as $order) {
                        if (!$order['delivered']) $pending++;
                    }
                    echo $pending;
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Toon alleen als er producten onder minimum voorraad zijn -->
        <?php if (count($low_stock) > 0): ?>
        <div class="dashboard-section">
            <h2>Producten Onder Minimum Voorraad</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Locatie</th>
                            <th>Huidige Voorraad</th>
                            <th>Minimum</th>
                            <th>Te Bestellen</th>
                            <th>Actie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Toon maximaal de eerste 5 producten met lage voorraad
                        foreach (array_slice($low_stock, 0, 5) as $item): 
                        ?>
                        <tr>
                            <td>
                                <strong><?= escape($item['name']) ?></strong><br>
                                <small><?= escape($item['brand']) ?> - <?= escape($item['type']) ?></small>
                            </td>
                            <td><?= escape($item['location_name']) ?></td>
                            <td><span class="badge badge-danger"><?= $item['quantity'] ?></span></td>
                            <td><?= $item['min_stock'] ?></td>
                            <td><strong><?= $item['quantity_needed'] ?></strong></td>
                            <td>
                                <?php if (hasAnyRole(['admin', 'magazijn'])): ?>
                                <a href="orders.php?create=1&product=<?= $item['id'] ?>&location=<?= $item['location_id'] ?>" 
                                   class="btn btn-sm btn-primary">Bestellen</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($low_stock) > 5): ?>
            <p class="text-center">
                <a href="orders.php" class="btn btn-secondary">Bekijk alle <?= count($low_stock) ?> producten</a>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Sectie voor recente bestellingen -->
        <div class="dashboard-section">
            <h2>Recente Bestellingen</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Bestel ID</th>
                            <th>Locatie</th>
                            <th>Besteldatum</th>
                            <th>Verwachte Aankomst</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Toon maximaal de 5 meest recente bestellingen
                        $displayed = 0;
                        foreach ($recent_orders as $order): 
                            if ($displayed >= 5) break;
                            $displayed++;
                        ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= escape($order['location_name']) ?></td>
                            <td><?= date('d-m-Y', strtotime($order['ordered_at'])) ?></td>
                            <td><?= date('d-m-Y', strtotime($order['expected_arrival'])) ?></td>
                            <td>
                                <?php if ($order['delivered']): ?>
                                    <span class="badge badge-success">Geleverd</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">In afwachting</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (hasAnyRole(['admin', 'magazijn'])): ?>
            <p class="text-center">
                <a href="orders.php" class="btn btn-secondary">Alle bestellingen bekijken</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
