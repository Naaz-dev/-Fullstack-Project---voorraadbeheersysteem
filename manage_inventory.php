<?php
/**
 * Voorraad Beheer Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Deze pagina is voor het beheren van voorraad hoeveelheden.
 * Toont een matrix van alle producten x locaties waar voorraad kan worden bijgewerkt.
 * 
 * Functies:
 * - Voorraad hoeveelheid per product per locatie bijwerken
 * - Visuele waarschuwing bij voorraad onder minimum
 * - Snel overzicht van alle voorraad in het systeem
 * 
 * Toegang: Admin en magazijn gebruikers
 */

require_once 'includes/config.php';
require_once 'includes/db_functions.php';

// Controleer of gebruiker is ingelogd
requireLogin();

// Alleen admin en magazijn personeel mag voorraad beheren
if (!hasAnyRole(['admin', 'magazijn'])) {
    die('Toegang geweigerd.');
}

$page_title = 'Voorraad Beheer';
$message = '';  // Succesbericht
$error = '';    // Foutmelding

// Verwerk formulier wanneer voorraad wordt bijgewerkt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Haal product, locatie en nieuwe hoeveelheid op
    $product_id = intval($_POST['product_id'] ?? 0);
    $location_id = intval($_POST['location_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    
    // Valideer invoer (hoeveelheid mag 0 zijn voor lege voorraad)
    if ($product_id && $location_id && $quantity >= 0) {
        if (updateInventoryQuantity($product_id, $location_id, $quantity)) {
            $message = 'Voorraad succesvol bijgewerkt!';
        } else {
            $error = 'Fout bij bijwerken voorraad.';
        }
    } else {
        $error = 'Ongeldige invoer.';
    }
}

// Haal alle benodigde data op voor de matrix weergave
$products = getAllProducts();   // Alle producten (rijen)
$locations = getAllLocations(); // Alle locaties (kolommen)
$inventory = getAllInventory(); // Huidige voorraad

// Maak een lookup array voor snelle voorraad toegang
// Key format: "product_id_location_id" => hoeveelheid
$inventory_lookup = [];
foreach ($inventory as $item) {
    $key = $item['product_id'] . '_' . $item['location_id'];
    $inventory_lookup[$key] = $item['quantity'];
}

include 'includes/nav_header.php';
?>

<div class="page-header">
    <h1>Voorraad Beheer</h1>
    <p>Beheer de voorraad per product en locatie</p>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= escape($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?= escape($error) ?></div>
<?php endif; ?>

<div class="inventory-management">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Merk</th>
                    <?php foreach ($locations as $loc): ?>
                        <th><?= escape($loc['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <strong><?= escape($product['name']) ?></strong><br>
                        <small>SKU: <?= escape($product['sku']) ?></small><br>
                        <small>Min: <?= $product['min_stock'] ?></small>
                    </td>
                    <td><?= escape($product['brand']) ?></td>
                    
                    <?php 
                    // Voor elke locatie, toon een invoerveld voor voorraad
                    foreach ($locations as $loc): 
                        // Haal huidige voorraad op uit de lookup array
                        $key = $product['id'] . '_' . $loc['id'];
                        $current_qty = $inventory_lookup[$key] ?? 0;
                        
                        // Controleer of voorraad onder minimum is (voor visuele waarschuwing)
                        $is_low = $current_qty < $product['min_stock'];
                    ?>
                    <td>
                        <!-- Inline formulier voor snelle voorraad aanpassing -->
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="location_id" value="<?= $loc['id'] ?>">
                            <div class="quantity-input-group">
                                <!-- Voorraad invoerveld met waarschuwingskleur bij lage voorraad -->
                                <input type="number" 
                                       name="quantity" 
                                       value="<?= $current_qty ?>" 
                                       min="0" 
                                       class="quantity-input <?= $is_low ? 'input-warning' : '' ?>">
                                <!-- Opslaan knop -->
                                <button type="submit" class="btn btn-sm btn-primary">💾</button>
                            </div>
                        </form>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="info-box">
    <h3>💡 Tips:</h3>
    <ul>
        <li>Oranje achtergrond = Voorraad onder minimum</li>
        <li>Wijzig het aantal en klik op 💾 om op te slaan</li>
        <li>Gebruik het dashboard om producten onder minimum te zien</li>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
