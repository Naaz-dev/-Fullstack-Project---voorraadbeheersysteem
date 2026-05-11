<?php
/**
 * Database functies voor ToolsForEver Voorraadbeheersysteem
 * 
 * Dit bestand bevat alle functies voor database interacties:
 * - Product beheer (CRUD operaties)
 * - Locatie beheer
 * - Voorraad beheer
 * - Bestelling beheer
 * - Statistieken en rapportages
 */

require_once 'config.php';

// ==========================================
// PRODUCT FUNCTIES
// ==========================================

/**
 * Haalt alle producten op uit de database
 * 
 * @return array Array van alle producten, gesorteerd op naam
 */
function getAllProducts() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Haalt een specifiek product op basis van ID
 * 
 * @param int $id Het product ID
 * @return array|false Product data of false als niet gevonden
 */
function getProductById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Voegt een nieuw product toe aan de database
 * 
 * @param string $sku Unieke product code (Stock Keeping Unit)
 * @param string $name Productnaam
 * @param string $type Producttype/categorie
 * @param string $brand Merknaam
 * @param float $purchase_price Inkoopprijs
 * @param float $sale_price Verkoopprijs
 * @param int $min_stock Minimum voorraadniveau
 * @return bool True bij succes, false bij falen
 */
function addProduct($sku, $name, $type, $brand, $purchase_price, $sale_price, $min_stock) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO products (sku, name, type, brand, purchase_price, sale_price, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$sku, $name, $type, $brand, $purchase_price, $sale_price, $min_stock]);
}

/**
 * Werkt een bestaand product bij
 * 
 * @param int $id Product ID dat moet worden bijgewerkt
 * @param string $sku Unieke product code
 * @param string $name Productnaam
 * @param string $type Producttype/categorie
 * @param string $brand Merknaam
 * @param float $purchase_price Inkoopprijs
 * @param float $sale_price Verkoopprijs
 * @param int $min_stock Minimum voorraadniveau
 * @return bool True bij succes, false bij falen
 */
function updateProduct($id, $sku, $name, $type, $brand, $purchase_price, $sale_price, $min_stock) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE products SET sku = ?, name = ?, type = ?, brand = ?, purchase_price = ?, sale_price = ?, min_stock = ? WHERE id = ?");
    return $stmt->execute([$sku, $name, $type, $brand, $purchase_price, $sale_price, $min_stock, $id]);
}

/**
 * Verwijdert een product uit de database
 * 
 * LET OP: Dit kan falen als er nog voorraad of bestellingen aan gekoppeld zijn
 * 
 * @param int $id Product ID om te verwijderen
 * @return bool True bij succes, false bij falen
 */
function deleteProduct($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==========================================
// LOCATIE FUNCTIES
// ==========================================

/**
 * Haalt alle locaties/vestigingen op uit de database
 * 
 * @return array Array van alle locaties, gesorteerd op naam
 */
function getAllLocations() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM locations ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Haalt een specifieke locatie op basis van ID
 * 
 * @param int $id Locatie ID
 * @return array|false Locatie data of false als niet gevonden
 */
function getLocationById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Voegt een nieuwe locatie/vestiging toe
 * 
 * @param string $name Naam van de locatie (bijv. "Amsterdam")
 * @param string $address Volledig adres van de vestiging
 * @return bool True bij succes, false bij falen
 */
function addLocation($name, $address) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO locations (name, address) VALUES (?, ?)");
    return $stmt->execute([$name, $address]);
}

/**
 * Werkt een bestaande locatie bij
 * 
 * @param int $id Locatie ID dat moet worden bijgewerkt
 * @param string $name Nieuwe naam van de locatie
 * @param string $address Nieuw adres
 * @return bool True bij succes, false bij falen
 */
function updateLocation($id, $name, $address) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE locations SET name = ?, address = ? WHERE id = ?");
    return $stmt->execute([$name, $address, $id]);
}

// ==========================================
// VOORRAAD BEHEER FUNCTIES
// ==========================================

/**
 * Haalt alle voorraad op voor een specifieke locatie
 * 
 * Combineert voorraad data met product- en locatie-informatie
 * 
 * @param int $location_id ID van de locatie
 * @return array Array van voorraad items met product details
 */
function getInventoryByLocation($location_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, p.sku, p.name, p.type, p.brand, p.purchase_price, p.sale_price, p.min_stock,
               l.name as location_name
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        JOIN locations l ON i.location_id = l.id
        WHERE i.location_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$location_id]);
    return $stmt->fetchAll();
}

/**
 * Haalt alle voorraadlocaties op voor een specifiek product
 * 
 * Laat zien op welke locaties het product aanwezig is en de hoeveelheid
 * 
 * @param int $product_id ID van het product
 * @return array Array van locaties met voorraad hoeveelheden
 */
function getInventoryByProduct($product_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT i.*, l.name as location_name, l.address
        FROM inventory i
        JOIN locations l ON i.location_id = l.id
        WHERE i.product_id = ?
        ORDER BY l.name
    ");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

/**
 * Haalt alle voorraad op uit het systeem
 * 
 * Combineert alle voorraad met product- en locatie-informatie voor een volledig overzicht
 * 
 * @return array Array van alle voorraad items
 */
function getAllInventory() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT i.*, p.sku, p.name, p.type, p.brand, p.purchase_price, p.sale_price, p.min_stock,
               l.name as location_name
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        JOIN locations l ON i.location_id = l.id
        ORDER BY p.name, l.name
    ");
    return $stmt->fetchAll();
}

/**
 * Werkt de voorraad hoeveelheid bij voor een product op een locatie
 * 
 * Deze functie controleert eerst of er al een voorraad record bestaat.
 * Als het bestaat, wordt het bijgewerkt. Anders wordt een nieuw record aangemaakt.
 * Dit voorkomt dubbele entries en maakt het beheer makkelijker.
 * 
 * @param int $product_id ID van het product
 * @param int $location_id ID van de locatie
 * @param int $quantity Nieuwe voorraad hoeveelheid
 * @return bool True bij succes, false bij falen
 */
function updateInventoryQuantity($product_id, $location_id, $quantity) {
    $pdo = getDBConnection();
    
    // Controleer of er al een voorraad record bestaat voor dit product en locatie
    $stmt = $pdo->prepare("SELECT id FROM inventory WHERE product_id = ? AND location_id = ?");
    $stmt->execute([$product_id, $location_id]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Update bestaand record
        $stmt = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE product_id = ? AND location_id = ?");
        return $stmt->execute([$quantity, $product_id, $location_id]);
    } else {
        // Maak nieuw record aan
        $stmt = $pdo->prepare("INSERT INTO inventory (product_id, location_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$product_id, $location_id, $quantity]);
    }
}

/**
 * Haalt alle producten op die onder het minimum voorraadniveau zitten
 * 
 * Deze functie is essentieel voor het waarschuwingssysteem en automatische bestellingen.
 * Het berekent ook hoeveel er besteld moet worden om op minimumniveau te komen.
 * 
 * @return array Array van producten met lage voorraad, inclusief locatie informatie
 */
function getLowStockProducts() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT p.*, i.quantity, i.location_id, l.name as location_name,
               (p.min_stock - i.quantity) as quantity_needed
        FROM products p
        JOIN inventory i ON p.id = i.product_id
        JOIN locations l ON i.location_id = l.id
        WHERE i.quantity < p.min_stock
        ORDER BY l.name, p.name
    ");
    return $stmt->fetchAll();
}

// ==========================================
// BESTELLING FUNCTIES
// ==========================================

/**
 * Haalt alle bestellingen op met locatie- en gebruikersinformatie
 * 
 * @return array Array van alle bestellingen, nieuwste eerst
 */
function getAllOrders() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT o.*, l.name as location_name, u.username as created_by_user
        FROM orders o
        JOIN locations l ON o.location_id = l.id
        LEFT JOIN users u ON o.created_by = u.id
        ORDER BY o.ordered_at DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Haalt een specifieke bestelling op basis van ID
 * 
 * @param int $id Bestelling ID
 * @return array|false Bestelling data met locatie informatie
 */
function getOrderById($id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT o.*, l.name as location_name
        FROM orders o
        JOIN locations l ON o.location_id = l.id
        WHERE o.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Haalt alle producten/items op van een specifieke bestelling
 * 
 * @param int $order_id Bestelling ID
 * @return array Array van bestelde producten met hoeveelheden
 */
function getOrderItems($order_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT oi.*, p.sku, p.name, p.type, p.brand
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

/**
 * Maakt een nieuwe bestelling aan in het systeem
 * 
 * @param int $location_id ID van de bestemming locatie
 * @param string $ordered_at Datum van bestellen (YYYY-MM-DD)
 * @param string $expected_arrival Verwachte leverdatum (YYYY-MM-DD)
 * @param int $created_by ID van de gebruiker die de bestelling aanmaakt
 * @return int Het ID van de nieuw aangemaakte bestelling
 */
function createOrder($location_id, $ordered_at, $expected_arrival, $created_by) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO orders (location_id, ordered_at, expected_arrival, created_by) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$location_id, $ordered_at, $expected_arrival, $created_by]);
    return $pdo->lastInsertId();
}

/**
 * Voegt een product toe aan een bestaande bestelling
 * 
 * @param int $order_id ID van de bestelling
 * @param int $product_id ID van het product
 * @param int $quantity Aantal te bestellen
 * @return bool True bij succes, false bij falen
 */
function addOrderItem($order_id, $product_id, $quantity) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
    return $stmt->execute([$order_id, $product_id, $quantity]);
}

/**
 * Markeert een bestelling als geleverd
 * 
 * LET OP: Dit verandert alleen de status, het werkt NIET automatisch de voorraad bij.
 * Voorraad moet handmatig worden bijgewerkt in het voorraad beheer scherm.
 * 
 * @param int $order_id ID van de bestelling
 * @return bool True bij succes, false bij falen
 */
function markOrderAsDelivered($order_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE orders SET delivered = 1 WHERE id = ?");
    return $stmt->execute([$order_id]);
}

// ==========================================
// STATISTIEK EN RAPPORTAGE FUNCTIES
// ==========================================

/**
 * Berekent de totale waarde van alle voorraad in het systeem
 * 
 * Retourneert zowel de totale inkoopwaarde (wat we ervoor betaald hebben)
 * als de totale verkoopwaarde (wat we ervoor zouden krijgen).
 * 
 * @return array Associatieve array met 'total_purchase_value' en 'total_sale_value'
 */
function getTotalInventoryValue() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            SUM(i.quantity * p.purchase_price) as total_purchase_value,
            SUM(i.quantity * p.sale_price) as total_sale_value
        FROM inventory i
        JOIN products p ON i.product_id = p.id
    ");
    return $stmt->fetch();
}

/**
 * Berekent voorraadwaarde per locatie/vestiging
 * 
 * Geeft een gedetailleerd overzicht van:
 * - Totale inkoopwaarde per locatie
 * - Totale verkoopwaarde per locatie  
 * - Totaal aantal items per locatie
 * 
 * Nuttig voor directie om te zien waar de meeste waarde zit.
 * 
 * @return array Array van locaties met hun voorraadwaardes
 */
function getInventoryValueByLocation() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            l.name as location_name,
            SUM(i.quantity * p.purchase_price) as total_purchase_value,
            SUM(i.quantity * p.sale_price) as total_sale_value,
            SUM(i.quantity) as total_items
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        JOIN locations l ON i.location_id = l.id
        GROUP BY l.id, l.name
        ORDER BY l.name
    ");
    return $stmt->fetchAll();
}

// ==========================================
// ZOEK FUNCTIE
// ==========================================

/**
 * Zoekt producten op basis van zoekterm
 * 
 * Zoekt in de volgende velden:
 * - Productnaam
 * - SKU (productcode)
 * - Merknaam
 * - Producttype
 * 
 * Kan optioneel gefilterd worden op een specifieke locatie.
 * 
 * @param string $search_term De zoekterm (wordt gezocht met wildcards)
 * @param int|null $location_id Optioneel: filter op specifieke locatie
 * @return array Array van gevonden producten met voorraad informatie
 */
function searchProducts($search_term, $location_id = null) {
    $pdo = getDBConnection();
    
    // Bouw de SQL query met JOINs voor complete product informatie
    $sql = "
        SELECT DISTINCT p.*, 
               GROUP_CONCAT(CONCAT(l.name, ':', i.quantity) SEPARATOR '|') as inventory_info
        FROM products p
        LEFT JOIN inventory i ON p.id = i.product_id
        LEFT JOIN locations l ON i.location_id = l.id
        WHERE (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.type LIKE ?)
    ";
    
    // Voeg wildcards toe aan de zoekterm voor gedeeltelijke matches
    $params = ["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"];
    
    // Voeg locatie filter toe indien opgegeven
    if ($location_id) {
        $sql .= " AND (i.location_id = ? OR i.location_id IS NULL)";
        $params[] = $location_id;
    }
    
    $sql .= " GROUP BY p.id ORDER BY p.name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
