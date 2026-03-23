<?php
/**
 * Configuratiebestand voor ToolsForEver Voorraadbeheersysteem
 * 
 * Dit bestand bevat alle essentiële configuratie-instellingen,
 * database connectie functies en beveiligingsfuncties
 */

// ==========================================
// DATABASE CONFIGURATIE
// ==========================================
// Hostnaam van de MySQL database server
define('DB_HOST', 'mysql');
// Gebruikersnaam voor database authenticatie
define('DB_USER', 'root');
// Wachtwoord voor database authenticatie
define('DB_PASS', 'password');
// Naam van de te gebruiken database
define('DB_NAME', 'toolsforever');
// Karakterset voor database communicatie (UTF-8 voor internationale karakters)
define('DB_CHARSET', 'utf8mb4');

/**
 * Maakt een PDO database connectie
 * 
 * Deze functie creëert een nieuwe database connectie met behulp van PDO
 * en configureert foutafhandeling en fetch modes.
 * 
 * @return PDO Database connectie object
 * @throws PDOException Als de connectie mislukt
 */
function getDBConnection() {
    try {
        // Bouw de Data Source Name (DSN) string voor MySQL
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Stel PDO opties in voor veiligheid en betrouwbaarheid
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // Gooi exceptions bij fouten
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Haal data op als associatieve array
            PDO::ATTR_EMULATE_PREPARES => false,                   // Gebruik echte prepared statements voor beveiliging
        ];
        
        // Maak de PDO connectie
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Bij een fout, stop de applicatie en toon foutmelding
        die("Database connectie fout: " . $e->getMessage());
    }
}

// ==========================================
// BEVEILIGING INSTELLINGEN
// ==========================================
// Voorkom JavaScript toegang tot session cookies (XSS bescherming)
ini_set('session.cookie_httponly', 1);
// Sta alleen cookies toe voor sessies (geen URL parameters)
ini_set('session.use_only_cookies', 1);
// Zet op 1 als HTTPS wordt gebruikt voor extra beveiliging
ini_set('session.cookie_secure', 0);

// Start de PHP sessie als deze nog niet actief is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// BEVEILIGINGSFUNCTIES
// ==========================================

/**
 * Escapt speciale HTML karakters ter preventie van XSS aanvallen
 * 
 * Deze functie moet ALTIJD worden gebruikt bij het tonen van gebruikersinvoer
 * of database data in HTML context.
 * 
 * @param string $value De waarde om te escapen
 * @return string De veilig ge-escapete string
 */
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Controleert of een gebruiker is ingelogd
 * 
 * @return bool True als gebruiker is ingelogd, anders false
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Controleert of gebruiker een specifieke rol heeft
 * 
 * @param string $role De te controleren rol (bijv. 'admin', 'magazijn')
 * @return bool True als gebruiker de rol heeft, anders false
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Controleert of gebruiker één van de opgegeven rollen heeft
 * 
 * @param array $roles Array van toegestane rollen
 * @return bool True als gebruiker een van de rollen heeft, anders false
 */
function hasAnyRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

/**
 * Verplicht dat gebruiker is ingelogd, anders redirect naar login
 * 
 * Deze functie moet aan het begin van elke beschermde pagina worden aangeroepen.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Verplicht een specifieke gebruikersrol voor toegang tot de pagina
 * 
 * @param string $role De vereiste rol (bijv. 'admin')
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        die("Toegang geweigerd. Je hebt niet de juiste rechten voor deze pagina.");
    }
}
