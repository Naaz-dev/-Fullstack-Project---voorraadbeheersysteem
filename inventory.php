<?php
/**
 * Voorraad Overzicht Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Deze pagina toont een overzicht van alle voorraad per product en locatie.
 * Functies:
 * - Zoeken in producten (naam, merk, type, SKU)
 * - Filteren op locatie
 * - Visuele indicatoren voor voorraadniveaus (groen/oranje/rood)
 * - Overzicht van totale voorraad per product
 * 
 * Toegang: Alle ingelogde gebruikers
 */

require_once 'includes/config.php';
require_once 'includes/db_functions.php';

// Controleer of gebruiker is ingelogd
requireLogin();

$page_title = 'Voorraad Overzicht';

// Haal zoek- en filter parameters op uit de URL
$search = $_GET['search'] ?? '';           // Zoekterm voor producten
$location_filter = $_GET['location'] ?? ''; // Optionele locatie filter

// Haal alle locaties op voor de filter dropdown
$locations = getAllLocations();

// Haal voorraad data op, afhankelijk van zoek/filter criteria
if ($search) {
    // Als er gezocht wordt, gebruik de zoekfunctie
    $inventory = searchProducts($search, $location_filter ?: null);
} else {
    // Anders haal alle voorraad op
    $inventory = getAllInventory();
    
    // Filter op locatie indien opgegeven
    if ($location_filter) {
        $inventory = array_filter($inventory, function($item) use ($location_filter) {
            return $item['location_id'] == $location_filter;
        });
    }
}

// Groepeer voorraad per product voor betere weergave
$products_inventory = [];
foreach ($inventory as $item) {
    $product_id = $item['id'] ?? $item['product_id'];
    if (!isset($products_inventory[$product_id])) {
        $products_inventory[$product_id] = [
            'product' => $item,
            'locations' => []  // Array van locatie => hoeveelheid
        ];
    }
    // Sla voorraad per locatie op
    $products_inventory[$product_id]['locations'][$item['location_name']] = $item['quantity'];
}

include 'includes/nav_header.php';
?>

<div class="page-header">
    <h1>Voorraad Overzicht</h1>
    <p>Bekijk de beschikbare voorraad per product en locatie</p>
</div>

<div class="search-filter-section">
    <form method="GET" action="" class="search-form">
        <div class="search-group">
            <input type="text" 
                   name="search" 
                   placeholder="Zoek op productnaam, merk, type of SKU..." 
                   value="<?= escape($search) ?>"
                   class="search-input">
            
            <select name="location" class="filter-select">
                <option value="">Alle locaties</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['id'] ?>" <?= $location_filter == $loc['id'] ? 'selected' : '' ?>>
                        <?= escape($loc['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Zoeken</button>
            
            <?php if ($search || $location_filter): ?>
                <a href="inventory.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (count($products_inventory) > 0): ?>
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Type</th>
                <th>Merk</th>
                <?php foreach ($locations as $loc): ?>
                    <th><?= escape($loc['name']) ?></th>
                <?php endforeach; ?>
                <th>Totaal</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products_inventory as $pid => $data): 
                $product = $data['product'];
                // Bereken totale voorraad over alle locaties
                $total_quantity = array_sum($data['locations']);
                $is_low = false;
                
                // Controleer of er op één van de locaties te weinig voorraad is
                foreach ($data['locations'] as $loc_name => $qty) {
                    if ($qty < $product['min_stock']) {
                        $is_low = true;
                        break;
                    }
                }
            ?>
            <!-- Geef waarschuwingskleur aan rij als voorraad laag is -->
            <tr class="<?= $is_low ? 'row-warning' : '' ?>">
                <td><code><?= escape($product['sku']) ?></code></td>
                <td>
                    <strong><?= escape($product['name']) ?></strong>
                </td>
                <td><?= escape($product['type']) ?></td>
                <td><?= escape($product['brand']) ?></td>
                
                <?php foreach ($locations as $loc): ?>
                    <td>
                        <?php 
                        // Haal voorraad op voor deze locatie (of 0 als niet beschikbaar)
                        $qty = $data['locations'][$loc['name']] ?? 0;
                        
                        // Gebruik gekleurde badges op basis van voorraadniveau
                        if ($qty < $product['min_stock']) {
                            // Rood: onder minimum
                            echo '<span class="badge badge-danger">' . $qty . '</span>';
                        } elseif ($qty < $product['min_stock'] * 1.5) {
                            // Oranje: nadert minimum
                            echo '<span class="badge badge-warning">' . $qty . '</span>';
                        } else {
                            // Groen: voldoende voorraad
                            echo '<span class="badge badge-success">' . $qty . '</span>';
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
                
                <td><strong><?= $total_quantity ?></strong></td>
                <td>
                    <?php 
                    // Totale status badge op basis van algemene voorraad
                    if ($is_low): ?>
                        <span class="badge badge-danger">Laag</span>
                    <?php elseif ($total_quantity < $product['min_stock'] * 1.5): ?>
                        <span class="badge badge-warning">Matig</span>
                    <?php else: ?>
                        <span class="badge badge-success">OK</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="info-box">
    <h3>Legenda:</h3>
    <ul class="legend">
        <li><span class="badge badge-success">Groen</span> - Voldoende voorraad</li>
        <li><span class="badge badge-warning">Oranje</span> - Voorraad nadert minimum</li>
        <li><span class="badge badge-danger">Rood</span> - Onder minimum voorraad</li>
    </ul>
</div>

<?php else: ?>
<div class="alert alert-info">
    <p>Geen producten gevonden<?= $search ? ' voor "' . escape($search) . '"' : '' ?>.</p>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
