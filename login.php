<?php
/**
 * Login Pagina - ToolsForEver Voorraadbeheersysteem
 * 
 * Deze pagina verzorgt de gebruikersauthenticatie voor het systeem.
 * Functies:
 * - Controleert gebruikersnaam en wachtwoord
 * - Maakt een beveiligde sessie aan bij succesvol inloggen
 * - Redirect naar dashboard na login
 * - Toont foutmeldingen bij ongeldige login pogingen
 */

require_once 'includes/config.php';

// Als gebruiker al is ingelogd, stuur direct door naar dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Verwerk login formulier wanneer deze wordt verstuurd
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Haal gebruikersnaam en wachtwoord op uit POST data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Controleer of beide velden zijn ingevuld
    if ($username && $password) {
        try {
            // Maak database connectie
            $pdo = getDBConnection();
            
            // Zoek gebruiker in database op basis van gebruikersnaam
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Controleer of gebruiker bestaat en wachtwoord klopt
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login succesvol! Sla gebruikersgegevens op in sessie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Genereer nieuwe sessie ID ter beveiliging (voorkomt session fixation)
                session_regenerate_id(true);
                
                // Redirect naar dashboard
                header('Location: index.php');
                exit;
            } else {
                // Ongeldige login poging
                $error = 'Ongeldige gebruikersnaam of wachtwoord';
            }
        } catch (PDOException $e) {
            // Database fout opgetreden
            $error = 'Er is een fout opgetreden. Probeer het later opnieuw.';
        }
    } else {
        // Niet alle velden ingevuld
        $error = 'Vul alle velden in';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ToolsForEver Voorraadbeheersysteem</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>🔧 ToolsForEver</h1>
                <p>Voorraadbeheersysteem</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= escape($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Gebruikersnaam</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Wachtwoord</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Inloggen</button>
            </form>
            
            <!-- <div class="login-info">
                <h3>Test accounts:</h3>
                <ul>
                    <li><strong>admin</strong> - Volledige rechten</li>
                    <li><strong>buitendienst</strong> - Voorraad bekijken</li>
                    <li><strong>magazijn</strong> - Voorraad beheren</li>
                    <li><strong>directie</strong> - Rapportages bekijken</li>
                </ul>
                <p><em>Alle wachtwoorden: admin123</em></p>
            </div> -->
        </div>
    </div>
</body>
</html>
