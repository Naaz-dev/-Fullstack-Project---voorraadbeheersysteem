# ToolsForEver - Voorraadbeheersysteem

Voorraadbeheersysteem voor ToolsForEver, een groothandel in gereedschappen met vestigingen in Rotterdam, Almere en Eindhoven.

## Over het Project

Dit systeem helpt bij het beheren van voorraad over meerdere locaties. Het bevat functionaliteit voor:
- Voorraad bekijken per locatie
- Producten toevoegen en bewerken
- Bestellingen plaatsen bij lage voorraad
- Rapportages voor directie

## Functionaliteiten

### Alle gebruikers
- Inloggen met username en wachtwoord
- Voorraad bekijken
- Zoeken en filteren op locatie

### Magazijn en Admin
- Producten toevoegen/bewerken/verwijderen
- Voorraad bijwerken
- Bestellingen plaatsen

### Directie en Admin
- Voorraadwaarde bekijken
- Rapportages per locatie
- Statistieken

## Technische informatie

- Backend: PHP met PDO
- Database: MySQL
- Frontend: HTML, CSS, JavaScript
- Beveiliging: Wachtwoord hashing, prepared statements, XSS preventie

## Installatie

### Vereisten

- Docker Desktop
- PHP 7.4+
- MySQL Workbench (optioneel)
- Webserver (bijv. XAMPP, WAMP, of PHP's ingebouwde server)

### Stap 1: Docker MySQL Container

Zorg dat je Docker container met MySQL draait:

```bash
# Controleer of container draait
docker ps

# Als container niet draait, start deze
docker start <container-name>
```

### Stap 2: Database Aanmaken

1. Open MySQL Workbench
2. Maak verbinding met:
   - Host: `localhost` (of `mysql` als je binnen Docker werkt)
   - Port: `3306`
   - Username: `root`
   - Password: `password`

3. Voer het database schema uit:
   - Open `database/schema.sql`
   - Voer het volledige script uit in Workbench
   - Dit maakt de database `toolsforever` aan met alle tabellen en testdata

### Stap 3: Configuratie Controleren

Controleer `includes/config.php` voor database instellingen:

```php
define('DB_HOST', 'mysql');      // Of 'localhost' als je niet via Docker werkt
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'toolsforever');
```

### Stap 4: Applicatie Starten

**Optie 1: PHP Built-in Server**

```powershell
cd "C:\Software Development\-Fullstack-Project---voorraadbeheersysteem"
php -S localhost:8000
```

Open browser en ga naar: `http://localhost:8000`

**Optie 2: XAMPP/WAMP**

1. Plaats project in `htdocs` of `www` folder
2. Start Apache
3. Ga naar `http://localhost/voorraadbeheersysteem`

## Gebruik

### Login gegevens

| Username      | Password  | Rol         |
|--------------|-----------|-------------|
| admin        | admin123  | Admin       |
| buitendienst | admin123  | Buitendienst|
| magazijn     | admin123  | Magazijn    |
| directie     | admin123  | Directie    |

### Pagina's

- index.php - Dashboard
- inventory.php - Voorraad overzicht
- products.php - Producten beheren
- manage_inventory.php - Voorraad bijwerken
- orders.php - Bestellingen
- locations.php - Locaties

## Projectstructuur

```
voorraadbeheersysteem/
├── assets/
│   └── css/
│       └── style.css          # Alle styling (responsive design)
├── database/
│   └── schema.sql             # Database schema en test data
├── includes/
│   ├── config.php             # Database connectie en security functies
│   ├── db_functions.php       # Alle database queries
│   ├── nav_header.php         # Header met navigatie
│   └── footer.php             # Footer
├── index.php                  # Dashboard homepage
├── login.php                  # Login pagina
├── logout.php                 # Logout functionaliteit
├── inventory.php              # Voorraad overzicht (zoeken/filteren)
├── products.php               # Product beheer (CRUD)
├── manage_inventory.php       # Voorraad bijwerken per locatie
├── orders.php                 # Bestellingen beheer
├── locations.php              # Locaties beheer
└── README.md                  # Deze documentatie
```

## Beveiliging

- Wachtwoord hashing met bcrypt
- Prepared statements tegen SQL injection
- XSS preventie met htmlspecialchars
- Role-based access control
- Sessie beveiliging
- **Parameter Binding**: Nooit directe string concatenatie in queries

## Database

### Tabellen

- users - Gebruikers
- locations - Vestigingen
- products - Producten
- inventory - Voorraad
- orders - Bestellingen
- order_items - Bestelregels

## Contact

Gemaakt voor ToolsForEver - December 2025
