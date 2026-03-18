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
            
            <?php if (isset($error)): ?>
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
            
            <div class="login-info">
                <h3>Test accounts:</h3>
                <ul>
                    <li><strong>admin</strong> - Volledige rechten</li>
                    <li><strong>buitendienst</strong> - Voorraad bekijken</li>
                    <li><strong>magazijn</strong> - Voorraad beheren</li>
                    <li><strong>directie</strong> - Rapportages bekijken</li>
                </ul>
                <p><em>Alle wachtwoorden: admin123</em></p>
            </div>
        </div>
    </div>
</body>
</html>
