<?php
/**
 * Locaties Beheer Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Deze pagina is voor het beheren van alle vestigingen/locaties.
 * Functies:
 * - Nieuwe locaties toevoegen
 * - Bestaande locaties bewerken
 * - Overzicht van voorraadwaarde per locatie
 * - Snel navigeren naar voorraad van een specifieke locatie
 * 
 * Toegang: Alleen admin gebruikers
 */

require_once 'includes/config.php';
require_once 'includes/db_functions.php';

// Alleen admin gebruikers hebben toegang tot locatie beheer
requireRole('admin');

$page_title = 'Locaties Beheer';
$message = '';  // Succesbericht
$error = '';    // Foutmelding

// Verwerk formulier acties (toevoegen, bewerken)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ACTIE: Nieuwe locatie toevoegen
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Naam is verplicht, adres is optioneel
        if ($name) {
            if (addLocation($name, $address)) {
                $message = 'Locatie succesvol toegevoegd!';
            } else {
                $error = 'Fout bij toevoegen locatie. Naam bestaat mogelijk al.';
            }
        } else {
            $error = 'Vul minimaal de naam in.';
        }
    }
    
    // ACTIE: Bestaande locatie bewerken
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if ($id && $name) {
            if (updateLocation($id, $name, $address)) {
                $message = 'Locatie succesvol bijgewerkt!';
            } else {
                $error = 'Fout bij bijwerken locatie.';
            }
        } else {
            $error = 'Ongeldige invoer.';
        }
    }
}

// Controleer of er een locatie bewerkt wordt (via URL parameter)
$edit_location = null;
if (isset($_GET['edit'])) {
    $edit_location = getLocationById($_GET['edit']);
}

// Haal alle locaties en hun voorraadwaardes op
$locations = getAllLocations();
$value_by_location = getInventoryValueByLocation();

// Maak een lookup array voor voorraadwaardes per locatie
$values_lookup = [];
foreach ($value_by_location as $val) {
    $values_lookup[$val['location_name']] = $val;
}

include 'includes/nav_header.php';
?>

<div class="page-header">
    <h1>Locaties Beheer</h1>
    <p>Beheer alle vestigingen van ToolsForEver</p>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= escape($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?= escape($error) ?></div>
<?php endif; ?>

<div class="form-section">
    <h2><?= $edit_location ? '✏️ Locatie Bewerken' : '➕ Nieuwe Locatie Toevoegen' ?></h2>
    <form method="POST" action="" class="location-form">
        <input type="hidden" name="action" value="<?= $edit_location ? 'edit' : 'add' ?>">
        <?php if ($edit_location): ?>
            <input type="hidden" name="id" value="<?= $edit_location['id'] ?>">
        <?php endif; ?>
        
        <div class="form-row">
            <div class="form-group">
                <label for="name">Locatie Naam *</label>
                <input type="text" id="name" name="name" required 
                       value="<?= $edit_location ? escape($edit_location['name']) : '' ?>"
                       placeholder="bijv. Amsterdam">
            </div>
            
            <div class="form-group">
                <label for="address">Adres</label>
                <input type="text" id="address" name="address" 
                       value="<?= $edit_location ? escape($edit_location['address']) : '' ?>"
                       placeholder="bijv. Straatnaam 123, 1000 AB Amsterdam">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= $edit_location ? '💾 Opslaan' : '➕ Toevoegen' ?>
            </button>
            <?php if ($edit_location): ?>
                <a href="locations.php" class="btn btn-secondary">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-section">
    <h2>Alle Locaties</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>Adres</th>
                    <th>Aantal Items</th>
                    <th>Totale Waarde (Verkoop)</th>
                    <th>Aangemaakt</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locations as $location): 
                    $values = $values_lookup[$location['name']] ?? null;
                ?>
                <tr>
                    <td><strong><?= escape($location['name']) ?></strong></td>
                    <td><?= escape($location['address']) ?></td>
                    <td><?= $values ? number_format($values['total_items']) : 0 ?></td>
                    <td>
                        <?= $values ? '€ ' . number_format($values['total_sale_value'], 2, ',', '.') : '€ 0,00' ?>
                    </td>
                    <td><?= date('d-m-Y', strtotime($location['created_at'])) ?></td>
                    <td class="action-buttons">
                        <a href="?edit=<?= $location['id'] ?>" class="btn btn-sm btn-secondary">✏️ Bewerken</a>
                        <a href="inventory.php?location=<?= $location['id'] ?>" class="btn btn-sm btn-primary">👁️ Voorraad</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="info-box">
    <h3>💡 Informatie:</h3>
    <ul>
        <li>Locaties kunnen niet worden verwijderd als er nog voorraad aan gekoppeld is</li>
        <li>Bij het toevoegen van een nieuwe locatie kan er direct voorraad worden toegewezen via Voorraad Beheer</li>
        <li>Bestaande bestellingen blijven gekoppeld aan locaties</li>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
