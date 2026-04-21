<?php
/**
 * Bestellingen Beheer Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Deze pagina is voor het beheren van alle bestellingen.
 * Functies:
 * - Nieuwe bestellingen aanmaken (meerdere producten per bestelling)
 * - Bestellingen markeren als geleverd
 * - Overzicht van alle bestellingen met status
 * - Waarschuwing voor producten met lage voorraad
 * 
 * LET OP: Het markeren van een bestelling als geleverd werkt NIET automatisch
 * de voorraad bij. Voorraad moet handmatig worden aangepast in Voorraad Beheer.
 * 
 * Toegang: Admin en magazijn gebruikers
 */

require_once 'includes/config.php';
require_once 'includes/db_functions.php';

// Controleer of gebruiker is ingelogd
requireLogin();

// Alleen admin en magazijn personeel mag bestellingen beheren
if (!hasAnyRole(['admin', 'magazijn'])) {
    die('Toegang geweigerd.');
}

$page_title = 'Bestellingen Beheer';
$message = '';  // Succesbericht
$error = '';    // Foutmelding

// ACTIE: Markeer bestelling als geleverd
if (isset($_POST['mark_delivered'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    if ($order_id && markOrderAsDelivered($order_id)) {
        $message = 'Bestelling gemarkeerd als geleverd!';
    } else {
        $error = 'Fout bij bijwerken bestelling.';
    }
}

// ACTIE: Maak een nieuwe bestelling aan
if (isset($_POST['create_order'])) {
    // Haal bestelling details op
    $location_id = intval($_POST['location_id'] ?? 0);
    $ordered_at = $_POST['ordered_at'] ?? date('Y-m-d');
    $expected_arrival = $_POST['expected_arrival'] ?? '';
    $items = $_POST['items'] ?? [];  // Array van bestelde producten
    
    // Valideer dat alle verplichte velden zijn ingevuld
    if ($location_id && $expected_arrival && count($items) > 0) {
        // Maak de bestelling aan
        $order_id = createOrder($location_id, $ordered_at, $expected_arrival, $_SESSION['user_id']);
        
        if ($order_id) {
            // Voeg alle producten toe aan de bestelling
            foreach ($items as $item) {
                // Sla alleen items op met geldig product ID en positieve hoeveelheid
                if ($item['product_id'] && $item['quantity'] > 0) {
                    addOrderItem($order_id, $item['product_id'], $item['quantity']);
                }
            }
            $message = 'Bestelling succesvol aangemaakt!';
        } else {
            $error = 'Fout bij aanmaken bestelling.';
        }
    } else {
        $error = 'Vul alle verplichte velden in.';
    }
}

// Haal alle benodigde data op voor de pagina
$orders = getAllOrders();           // Alle bestellingen
$locations = getAllLocations();     // Alle locaties voor dropdown
$products = getAllProducts();       // Alle producten voor dropdown
$low_stock = getLowStockProducts(); // Producten met lage voorraad (voor waarschuwing)

include 'includes/nav_header.php';
?>

<div class="page-header">
    <h1>Bestellingen Beheer</h1>
    <p>Beheer bestellingen en bekijk bestelhistorie</p>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= escape($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?= escape($error) ?></div>
<?php endif; ?>

<!-- Toon waarschuwing als er producten onder minimum voorraad zijn -->
<?php if (count($low_stock) > 0): ?>
<div class="alert alert-warning">
    <h3>Let op: <?= count($low_stock) ?> product(en) onder minimum voorraad!</h3>
    <ul>
        <?php 
        // Toon maximaal de eerste 5 producten met lage voorraad
        foreach (array_slice($low_stock, 0, 5) as $item): 
        ?>
        <li>
            <strong><?= escape($item['name']) ?></strong> 
            (<?= escape($item['location_name']) ?>): 
            <?= $item['quantity'] ?> / min. <?= $item['min_stock'] ?>
            - Bestel: <?= $item['quantity_needed'] ?> stuks
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="form-section">
    <h2>➕ Nieuwe Bestelling Aanmaken</h2>
    <form method="POST" class="order-form" id="orderForm">
        <input type="hidden" name="create_order" value="1">
        
        <div class="form-row">
            <div class="form-group">
                <label for="location_id">Locatie *</label>
                <select id="location_id" name="location_id" required>
                    <option value="">Selecteer locatie</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= $loc['id'] ?>"><?= escape($loc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="ordered_at">Besteldatum *</label>
                <input type="date" id="ordered_at" name="ordered_at" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="expected_arrival">Verwachte Aankomst *</label>
                <input type="date" id="expected_arrival" name="expected_arrival" required>
            </div>
        </div>
        
        <h3>Producten toevoegen aan bestelling:</h3>
        <div id="orderItems">
            <div class="order-item-row">
                <select name="items[0][product_id]" class="product-select">
                    <option value="">Selecteer product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>">
                            <?= escape($product['name']) ?> (<?= escape($product['brand']) ?>) - SKU: <?= escape($product['sku']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="items[0][quantity]" placeholder="Aantal" min="1" class="quantity-input">
            </div>
        </div>
        
        <button type="button" class="btn btn-secondary" onclick="addOrderItem()">+ Product Toevoegen</button>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Bestelling Plaatsen</button>
        </div>
    </form>
</div>

<div class="table-section">
    <h2>Alle Bestellingen</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Bestel ID</th>
                    <th>Locatie</th>
                    <th>Besteldatum</th>
                    <th>Verwachte Aankomst</th>
                    <th>Status</th>
                    <th>Aangemaakt door</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong>#<?= $order['id'] ?></strong></td>
                    <td><?= escape($order['location_name']) ?></td>
                    <td><?= date('d-m-Y', strtotime($order['ordered_at'])) ?></td>
                    <td><?= date('d-m-Y', strtotime($order['expected_arrival'])) ?></td>
                    <td>
                        <?php if ($order['delivered']): ?>
                            <span class="badge badge-success">✓ Geleverd</span>
                        <?php else: ?>
                            <span class="badge badge-warning">⏳ In afwachting</span>
                        <?php endif; ?>
                    </td>
                    <td><?= escape($order['created_by_user'] ?? 'Onbekend') ?></td>
                    <td>
                        <button class="btn btn-sm btn-secondary" onclick="viewOrderDetails(<?= $order['id'] ?>)">👁️ Details</button>
                        <?php if (!$order['delivered']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="mark_delivered" value="1">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">✓ Geleverd</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="orderDetailsModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Besteldetails</h2>
        <div id="orderDetailsContent"></div>
    </div>
</div>

<script>
let itemCounter = 1;

function addOrderItem() {
    const container = document.getElementById('orderItems');
    const newRow = document.createElement('div');
    newRow.className = 'order-item-row';
    newRow.innerHTML = `
        <select name="items[${itemCounter}][product_id]" class="product-select">
            <option value="">Selecteer product</option>
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id'] ?>">
                    <?= escape($product['name']) ?> (<?= escape($product['brand']) ?>) - SKU: <?= escape($product['sku']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="items[${itemCounter}][quantity]" placeholder="Aantal" min="1" class="quantity-input">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">✕</button>
    `;
    container.appendChild(newRow);
    itemCounter++;
}

function viewOrderDetails(orderId) {
    // In a real implementation, this would fetch order details via AJAX
    // For now, we'll just show a placeholder
    document.getElementById('orderDetailsContent').innerHTML = '<p>Laden van besteldetails voor order #' + orderId + '...</p>';
    document.getElementById('orderDetailsModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
