<?php
/**
 * Producten Beheer Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Deze pagina is voor het beheren van alle producten in het systeem.
 * Functies:
 * - Nieuwe producten toevoegen
 * - Bestaande producten bewerken
 * - Producten verwijderen
 * - Overzicht van alle producten met prijzen
 * 
 * Toegang: Alleen admin gebruikers
 */

require_once 'includes/config.php';
require_once 'includes/db_functions.php';

// Alleen admin gebruikers hebben toegang tot product beheer
requireRole('admin');

$page_title = 'Producten Beheer';
$message = '';  // Succesbericht
$error = '';    // Foutmelding

// Verwerk formulier acties (toevoegen, bewerken, verwijderen)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ACTIE: Nieuw product toevoegen
    if ($action === 'add') {
        // Haal en valideer alle invoervelden
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $purchase_price = floatval($_POST['purchase_price'] ?? 0);
        $sale_price = floatval($_POST['sale_price'] ?? 0);
        $min_stock = intval($_POST['min_stock'] ?? 10);
        
        // Controleer of alle verplichte velden zijn ingevuld
        if ($sku && $name && $purchase_price > 0 && $sale_price > 0) {
            if (addProduct($sku, $name, $type, $brand, $purchase_price, $sale_price, $min_stock)) {
                $message = 'Product succesvol toegevoegd!';
            } else {
                $error = 'Fout bij toevoegen product. SKU bestaat mogelijk al.';
            }
        } else {
            $error = 'Vul alle verplichte velden correct in.';
        }
    }
    
    // ACTIE: Bestaand product bewerken
    if ($action === 'edit') {
        // Haal product ID en alle velden op
        $id = intval($_POST['id'] ?? 0);
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $purchase_price = floatval($_POST['purchase_price'] ?? 0);
        $sale_price = floatval($_POST['sale_price'] ?? 0);
        $min_stock = intval($_POST['min_stock'] ?? 10);
        
        // Valideer invoer
        if ($id && $sku && $name && $purchase_price > 0 && $sale_price > 0) {
            if (updateProduct($id, $sku, $name, $type, $brand, $purchase_price, $sale_price, $min_stock)) {
                $message = 'Product succesvol bijgewerkt!';
            } else {
                $error = 'Fout bij bijwerken product.';
            }
        } else {
            $error = 'Ongeldige invoer.';
        }
    }
    
    // ACTIE: Product verwijderen
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id && deleteProduct($id)) {
            $message = 'Product succesvol verwijderd!';
        } else {
            $error = 'Fout bij verwijderen product.';
        }
    }
}

// Controleer of er een product bewerkt wordt (via URL parameter)
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_product = getProductById($_GET['edit']);
}

// Haal alle producten op voor de tabel
$products = getAllProducts();

include 'includes/nav_header.php';
?>

<div class="page-header">
    <h1>🛠️ Producten Beheer</h1>
    <p>Beheer alle producten in het systeem</p>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= escape($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?= escape($error) ?></div>
<?php endif; ?>

<div class="form-section">
    <h2><?= $edit_product ? '✏️ Product Bewerken' : '➕ Nieuw Product Toevoegen' ?></h2>
    <form method="POST" action="" class="product-form">
        <input type="hidden" name="action" value="<?= $edit_product ? 'edit' : 'add' ?>">
        <?php if ($edit_product): ?>
            <input type="hidden" name="id" value="<?= $edit_product['id'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label for="sku">SKU *</label>
                <input type="text" id="sku" name="sku" required 
                       value="<?= $edit_product ? escape($edit_product['sku']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="name">Productnaam *</label>
                <input type="text" id="name" name="name" required 
                       value="<?= $edit_product ? escape($edit_product['name']) : '' ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="type">Type</label>
                <input type="text" id="type" name="type" 
                       value="<?= $edit_product ? escape($edit_product['type']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="brand">Merk</label>
                <input type="text" id="brand" name="brand" 
                       value="<?= $edit_product ? escape($edit_product['brand']) : '' ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="purchase_price">Inkoopprijs (€) *</label>
                <input type="number" id="purchase_price" name="purchase_price" step="0.01" required 
                       value="<?= $edit_product ? $edit_product['purchase_price'] : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="sale_price">Verkoopprijs (€) *</label>
                <input type="number" id="sale_price" name="sale_price" step="0.01" required 
                       value="<?= $edit_product ? $edit_product['sale_price'] : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="min_stock">Minimum Voorraad *</label>
                <input type="number" id="min_stock" name="min_stock" required 
                       value="<?= $edit_product ? $edit_product['min_stock'] : 10 ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $edit_product ? '💾 Opslaan' : '➕ Toevoegen' ?>
            </button>
            <?php if ($edit_product): ?>
                <a href="products.php" class="btn btn-secondary">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-section">
    <h2>Alle Producten</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Naam</th>
                    <th>Type</th>
                    <th>Merk</th>
                    <th>Inkoopprijs</th>
                    <th>Verkoopprijs</th>
                    <th>Min. Voorraad</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><code><?= escape($product['sku']) ?></code></td>
                    <td><strong><?= escape($product['name']) ?></strong></td>
                    <td><?= escape($product['type']) ?></td>
                    <td><?= escape($product['brand']) ?></td>
                    <td>€ <?= number_format($product['purchase_price'], 2, ',', '.') ?></td>
                    <td>€ <?= number_format($product['sale_price'], 2, ',', '.') ?></td>
                    <td><?= $product['min_stock'] ?></td>
                    <td class="action-buttons">
                        <a href="?edit=<?= $product['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Weet je zeker dat je dit product wilt verwijderen?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
