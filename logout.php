<?php
/**
 * Logout Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Deze pagina verwerkt het uitloggen van gebruikers.
 * Het voert een veilige logout procedure uit:
 * 1. Verwijdert alle sessie data
 * 2. Vernietigt de sessie cookie
 * 3. Vernietigt de sessie op de server
 * 4. Redirect naar login pagina
 */

require_once 'includes/config.php';

// Wis alle sessie variabelen
$_SESSION = [];

// Verwijder de sessie cookie uit de browser
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Vernietig de sessie op de server
session_destroy();

// Redirect naar login pagina
header('Location: login.php');
exit;
