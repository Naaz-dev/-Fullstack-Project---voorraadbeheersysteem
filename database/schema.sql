-- ==========================================
-- ToolsForEver Voorraadbeheersysteem Database Schema
-- ==========================================
-- Database: toolsforever
-- 
-- Dit schema bevat alle tabellen voor het voorraadbeheersysteem:
-- - users: Gebruikersaccounts met rollen
-- - locations: Vestigingen/locaties
-- - products: Product catalogus
-- - inventory: Voorraad per product per locatie
-- - orders: Bestellingen
-- - order_items: Producten in bestellingen

-- ==========================================
-- VERWIJDER BESTAANDE TABELLEN
-- ==========================================
-- Tabellen worden in de juiste volgorde verwijderd om foreign key constraints te respecteren
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS users;

-- ==========================================
-- TABEL: USERS
-- ==========================================
-- Opslag van gebruikersaccounts voor authenticatie en autorisatie
CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,                                          -- Uniek gebruikers ID
    username VARCHAR(100) NOT NULL UNIQUE,                                          -- Gebruikersnaam voor login (moet uniek zijn)
    password_hash VARCHAR(255) NOT NULL,                                            -- Gehashte wachtwoord (bcrypt)
    role ENUM('admin', 'buitendienst', 'magazijn', 'directie') NOT NULL DEFAULT 'buitendienst',  -- Gebruikersrol voor toegangscontrole
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,                                 -- Aanmaakdatum
    INDEX idx_username (username)                                                   -- Index voor snelle username lookup
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TABEL: LOCATIONS
-- ==========================================
-- Opslag van alle vestigingen/locaties van ToolsForEver
CREATE TABLE locations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,                                          -- Uniek locatie ID
    name VARCHAR(100) NOT NULL UNIQUE,                                              -- Naam van de locatie (bijv. "Rotterdam", moet uniek zijn)
    address VARCHAR(255),                                                           -- Volledig adres van de vestiging
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,                                 -- Aanmaakdatum
    INDEX idx_name (name)                                                           -- Index voor snelle zoek operaties
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TABEL: PRODUCTS
-- ==========================================
-- Catalogus van alle producten die verkocht worden
CREATE TABLE products (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,                                          -- Uniek product ID
    sku VARCHAR(50) NOT NULL UNIQUE,                                                -- Stock Keeping Unit - unieke productcode
    name VARCHAR(200) NOT NULL,                                                     -- Productnaam
    type VARCHAR(100),                                                              -- Producttype/model nummer
    brand VARCHAR(100),                                                             -- Merknaam
    purchase_price DECIMAL(10,2) NOT NULL,                                          -- Inkoopprijs (wat wij betalen)
    sale_price DECIMAL(10,2) NOT NULL,                                              -- Verkoopprijs (wat klanten betalen)
    min_stock INT(11) DEFAULT 10,                                                   -- Minimum voorraadniveau (trigger voor bestelling)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,                                 -- Aanmaakdatum
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,     -- Laatst gewijzigd datum
    INDEX idx_sku (sku),                                                            -- Index voor SKU lookups
    INDEX idx_name (name),                                                          -- Index voor naam zoekacties
    INDEX idx_brand (brand)                                                         -- Index voor merk filtering
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TABEL: INVENTORY
-- ==========================================
-- Voorraad opslag - koppelt producten aan locaties met hoeveelheden
-- Elke combinatie van product + locatie kan maar één keer voorkomen (UNIQUE constraint)
CREATE TABLE inventory (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,                                          -- Uniek voorraad record ID
    product_id INT(11) NOT NULL,                                                    -- Verwijzing naar product
    location_id INT(11) NOT NULL,                                                   -- Verwijzing naar locatie
    quantity INT(11) NOT NULL DEFAULT 0,                                            -- Aantal stuks op voorraad
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,   -- Laatst bijgewerkt timestamp
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,             -- Als product verwijderd wordt, verwijder ook voorraad
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,           -- Als locatie verwijderd wordt, verwijder ook voorraad
    UNIQUE KEY unique_product_location (product_id, location_id),                   -- Voorkom dubbele entries voor zelfde product+locatie
    INDEX idx_product (product_id),                                                 -- Index voor product queries
    INDEX idx_location (location_id)                                                -- Index voor locatie queries
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TABEL: ORDERS
-- ==========================================
-- Bestellingen die gedaan zijn voor aanvulling van voorraad
CREATE TABLE orders (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,                                          -- Uniek bestel ID
    location_id INT(11) NOT NULL,                                                   -- Bestemming locatie voor de bestelling
    ordered_at DATE NOT NULL,                                                       -- Datum waarop besteld is
    expected_arrival DATE,                                                          -- Verwachte leverdatum
    delivered TINYINT(1) DEFAULT 0,                                                 -- Status: 0 = nog niet geleverd, 1 = geleverd
    created_by INT(11),                                                             -- Gebruiker die de bestelling aangemaakt heeft
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,                                 -- Aanmaakdatum in systeem
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,           -- Bij verwijderen locatie, verwijder ook bestellingen
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,               -- Bij verwijderen gebruiker, zet created_by op NULL
    INDEX idx_location (location_id),                                               -- Index voor locatie filtering
    INDEX idx_delivered (delivered),                                                -- Index voor status filtering
    INDEX idx_ordered_at (ordered_at)                                               -- Index voor datum queries
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TABEL: ORDER_ITEMS
-- ==========================================
-- Individuele producten binnen een bestelling (meerdere items per order)
CREATE TABLE order_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,                                          -- Uniek order item ID
    order_id INT(11) NOT NULL,                                                      -- Verwijzing naar de bestelling
    product_id INT(11) NOT NULL,                                                    -- Welk product besteld is
    quantity INT(11) NOT NULL,                                                      -- Hoeveel stuks besteld zijn
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,                 -- Bij verwijderen order, verwijder ook alle items
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,             -- Bij verwijderen product, verwijder ook order items
    INDEX idx_order (order_id),                                                     -- Index voor order queries
    INDEX idx_product (product_id)                                                  -- Index voor product queries
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- TEST DATA EN INITIËLE GEGEVENS
-- ==========================================

-- Voeg standaard gebruikers toe voor verschillende rollen
-- Alle wachtwoorden zijn: admin123 (gehashed met bcrypt)
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('buitendienst', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buitendienst'),
('magazijn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'magazijn'),
('directie', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'directie');

-- Voeg de drie hoofdvestigingen toe
INSERT INTO locations (name, address) VALUES
('Rotterdam', 'Havenstraat 123, 3011 Rotterdam'),
('Almere', 'Stadsplein 45, 1315 Almere'),
('Eindhoven', 'Industrieweg 78, 5600 Eindhoven');

-- Voeg producten toe op basis van de bijlage data
-- Producten zijn verdeeld over de drie locaties
INSERT INTO products (sku, name, type, brand, purchase_price, sale_price, min_stock) VALUES
-- Producten van Rotterdam locatie
('WX382', 'Accuboorhamer', 'WX 382', 'Worx', 69.95, 111.75, 10),
('KA280K', '4-in-1 schuurmachine', 'KA 280 K', 'Black & Decker', 55.95, 67.95, 10),
('BT-MS2112', 'Verstekzaag', 'BT-MS 2112', 'Einhell', 49.95, 67.49, 10),
-- Producten van Almere locatie
('WD2200', 'Alleszuiger', 'WD2.200', 'Kärcher', 29.95, 47.96, 10),
('PSR144', 'Accuboormachine', 'PSR 14.4', 'Bosch', 59.95, 68.00, 20),
('SENCYS33', '33-delige borenset', '', 'Sencys', 9.95, 15.20, 20),
-- Producten van Eindhoven locatie
('WM536', 'Workmate', 'WM 536', 'Black & Decker', 49.95, 63.20, 10),
('PCL20', 'Kruislijnlaserset', 'PCL 20', 'Bosch', 99.95, 122.40, 10);

-- Voeg initiele voorraad toe per product per locatie
-- Gebaseerd op de bijlage data
INSERT INTO inventory (product_id, location_id, quantity) VALUES
-- Rotterdam (location_id: 1)
(1, 1, 10),  -- Accuboorhamer: 10 stuks
(2, 1, 15),  -- 4-in-1 schuurmachine: 15 stuks
(3, 1, 2),   -- Verstekzaag: 2 stuks (ONDER MINIMUM!)
-- Almere (location_id: 2)
(4, 2, 4),   -- Alleszuiger: 4 stuks (ONDER MINIMUM!)
(1, 2, 11),  -- Accuboorhamer: 11 stuks
(5, 2, 12),  -- Accuboormachine: 12 stuks (ONDER MINIMUM - min is 20)
(6, 2, 54),  -- 33-delige borenset: 54 stuks
-- Eindhoven (location_id: 3)
(7, 3, 14),  -- Workmate: 14 stuks
(8, 3, 11),  -- Kruislijnlaserset: 11 stuks
(1, 3, 11),  -- Accuboorhamer: 11 stuks
(5, 3, 12);  -- Accuboormachine: 12 stuks (ONDER MINIMUM - min is 20)

-- Voeg voorbeeldbestellingen toe (gebaseerd op bijlage)
INSERT INTO orders (location_id, ordered_at, expected_arrival, delivered, created_by) VALUES
(1, '2022-12-13', '2022-12-24', 1, 1),  -- Rotterdam - Bestelling is geleverd
(2, '2023-01-14', '2023-01-16', 0, 1),  -- Almere - Nog niet geleverd (openstaand)
(3, '2023-01-16', '2023-01-22', 0, 1);  -- Eindhoven - Nog niet geleverd (openstaand)

-- Voeg bestelde items toe aan de bestellingen
INSERT INTO order_items (order_id, product_id, quantity) VALUES
-- Order 1: Rotterdam (8 verstekzagen om voorraad aan te vullen)
(1, 3, 8),   -- Verstekzaag: 8 stuks
-- Order 2: Almere (alleszuigers en accuboorhamer)
(2, 4, 6),   -- Alleszuiger: 6 stuks
(2, 1, 9),   -- Accuboorhamer: 9 stuks
-- Order 3: Eindhoven (accuboorhamer en accuboormachine)
(3, 1, 9),   -- Accuboorhamer: 9 stuks
(3, 5, 8);   -- Accuboormachine: 8 stuks
