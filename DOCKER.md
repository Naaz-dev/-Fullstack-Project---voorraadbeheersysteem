# 🐳 Docker Setup - ToolsForEver Voorraadbeheersysteem

## 📦 Wat Zit Erin?

Deze Docker setup bevat:
- **MySQL 8.0** database op poort 3306
- **phpMyAdmin** op poort 8081 (optioneel, voor database beheer in browser)
- Automatische database initialisatie met je bestaande schema

## 🚀 Installatie & Gebruik

### Stap 1: Docker Starten

```powershell
# Zorg dat je in de project folder bent
cd "C:\Software Development\-Fullstack-Project---voorraadbeheersysteem"

# Start alle containers
docker-compose up -d

# Output zou moeten zijn:
# Creating toolsforever-mysql ... done
# Creating toolsforever-phpmyadmin ... done
```

### Stap 2: Controleer of Containers Draaien

```powershell
# Bekijk draaiende containers
docker-compose ps

# Of:
docker ps

# Je zou moeten zien:
# toolsforever-mysql      (poort 3306)
# toolsforever-phpmyadmin (poort 8081)
```

### Stap 3: Database Schema Laden (Als Database Leeg Is)

Omdat je database al gemaakt hebt, hoef je dit waarschijnlijk niet te doen. Maar als je opnieuw wilt beginnen:

```powershell
# Stop containers
docker-compose down -v

# Start opnieuw (laadt schema.sql automatisch)
docker-compose up -d

# Wacht 10-20 seconden voor MySQL start
Start-Sleep -Seconds 15

# Verificeer database
docker exec -it toolsforever-mysql mysql -uroot -ppassword -e "SHOW DATABASES;"
```

## 🔌 Connectie met MySQL Workbench

### Workbench Configuratie:

1. **Open MySQL Workbench**

2. **Klik op "+" bij MySQL Connections**

3. **Vul in:**
   ```
   Connection Name: ToolsForEver Docker
   Connection Method: Standard (TCP/IP)
   
   Hostname: localhost
   Port: 3306
   Username: root
   Password: password (klik "Store in Vault")
   
   Default Schema: toolsforever
   ```

4. **Test Connection** → Moet "Successfully made the MySQL connection" tonen

5. **OK** → Verbinding is opgeslagen

6. **Dubbelklik** op de nieuwe connectie om te openen

### Verificatie:

```sql
-- In Workbench, voer uit:
SHOW DATABASES;
USE toolsforever;
SHOW TABLES;

-- Moet je tables tonen:
-- users, locations, products, inventory, orders, order_items
```

## 🌐 phpMyAdmin (Browser Database Management)

Als alternatief voor Workbench kun je ook phpMyAdmin gebruiken:

1. **Open browser:** http://localhost:8081

2. **Login:**
   - Server: `mysql`
   - Username: `root`
   - Password: `password`

3. **Selecteer** `toolsforever` database

Nu kun je de database beheren via je browser!

## 🔧 PHP Applicatie Configuratie

### Optie A: PHP op je Computer (Aanbevolen)

Als je PHP lokaal draait, gebruik deze config in `includes/config.php`:

```php
define('DB_HOST', 'localhost');  // Of '127.0.0.1'
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'toolsforever');
define('DB_PORT', '3306');
```

Start PHP server:
```powershell
php -S localhost:8000
```

### Optie B: PHP in Docker (Als je dat wilt)

Uncomment de PHP service in `docker-compose.yml` en herstart:

```powershell
docker-compose down
docker-compose up -d
```

Dan gebruik je: http://localhost:8080

En in `config.php`:
```php
define('DB_HOST', 'mysql');  // Container naam, niet localhost!
```

## 📊 Database Credentials Overzicht

| Item | Waarde |
|------|--------|
| Host (van buiten Docker) | `localhost` of `127.0.0.1` |
| Host (vanuit Docker PHP) | `mysql` |
| Port | `3306` |
| Root Username | `root` |
| Root Password | `password` |
| Database Naam | `toolsforever` |
| Extra User (optioneel) | `toolsforever_user` |
| Extra Password (optioneel) | `toolsforever_pass` |

## 🛠 Handige Docker Commando's

```powershell
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Stop en verwijder volumes (VOORZICHTIG: verwijdert data!)
docker-compose down -v

# Bekijk logs
docker-compose logs mysql

# Realtime logs volgen
docker-compose logs -f mysql

# Ga in MySQL container
docker exec -it toolsforever-mysql bash

# Direct MySQL query uitvoeren
docker exec -it toolsforever-mysql mysql -uroot -ppassword toolsforever -e "SELECT * FROM users;"

# Herstart specifieke container
docker-compose restart mysql

# Bekijk container status
docker-compose ps

# Stop alle containers
docker-compose stop

# Verwijder alleen containers (data blijft)
docker-compose rm
```

## 🔄 Database Backup & Restore

### Backup maken:

```powershell
# Maak backup directory
New-Item -ItemType Directory -Path ".\database\backup" -Force

# Maak backup
docker exec toolsforever-mysql mysqldump -uroot -ppassword toolsforever > ".\database\backup\backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
```

### Restore van backup:

```powershell
# Restore specifieke backup
Get-Content ".\database\backup\backup_20251209_120000.sql" | docker exec -i toolsforever-mysql mysql -uroot -ppassword toolsforever
```

## 🧪 Test de Connectie

```powershell
# Test 1: Controleer of MySQL draait
docker exec -it toolsforever-mysql mysql -uroot -ppassword -e "SELECT VERSION();"

# Test 2: Controleer of database bestaat
docker exec -it toolsforever-mysql mysql -uroot -ppassword -e "SHOW DATABASES LIKE 'toolsforever';"

# Test 3: Tel aantal tabellen
docker exec -it toolsforever-mysql mysql -uroot -ppassword toolsforever -e "SELECT COUNT(*) as tables FROM information_schema.tables WHERE table_schema = 'toolsforever';"

# Test 4: Bekijk users
docker exec -it toolsforever-mysql mysql -uroot -ppassword toolsforever -e "SELECT username, role FROM users;"
```

## ⚠️ Troubleshooting

### "Port 3306 already in use"

```powershell
# Bekijk wat poort 3306 gebruikt
netstat -ano | findstr :3306

# Stop andere MySQL instance of wijzig poort in docker-compose.yml:
# ports:
#   - "3307:3306"  # Dan gebruik je localhost:3307
```

### "Container steeds opnieuw opstart"

```powershell
# Bekijk MySQL logs voor errors
docker-compose logs mysql

# Vaak: wachtwoord al gezet, dan:
docker-compose down -v  # Verwijdert volumes
docker-compose up -d    # Start opnieuw
```

### "Can't connect to MySQL server"

```powershell
# Wacht even, MySQL heeft tijd nodig om op te starten
Start-Sleep -Seconds 20

# Check of container healthy is
docker inspect toolsforever-mysql | Select-String "Health"
```

### "Access denied for user"

Check of je de juiste credentials gebruikt:
- Username: `root`
- Password: `password`

Of gebruik de extra user:
- Username: `toolsforever_user`
- Password: `toolsforever_pass`

## 🎯 Quick Start Samenvatting

```powershell
# 1. Start Docker containers
docker-compose up -d

# 2. Wacht 15 seconden
Start-Sleep -Seconds 15

# 3. Test connectie
docker exec -it toolsforever-mysql mysql -uroot -ppassword -e "SHOW DATABASES;"

# 4. Open Workbench en maak connectie:
#    Host: localhost, Port: 3306, User: root, Pass: password

# 5. Start PHP applicatie
php -S localhost:8000

# 6. Open browser
#    http://localhost:8000 (Applicatie)
#    http://localhost:8081 (phpMyAdmin)
```

## 📝 Belangrijke Notities

1. **Data Persistentie**: Data blijft bewaard in Docker volume `mysql_data`
2. **Auto Schema Load**: Als database leeg is, wordt `schema.sql` automatisch geladen
3. **Workbench Port**: Gebruik altijd `localhost:3306` (niet `mysql`)
4. **PHP Host**: 
   - Lokale PHP → `localhost`
   - Docker PHP → `mysql`
5. **phpMyAdmin**: Handige web interface op http://localhost:8081

## ✅ Je bent klaar!

Je kunt nu:
- ✅ Database benaderen via Workbench op localhost:3306
- ✅ phpMyAdmin gebruiken op http://localhost:8081
- ✅ PHP applicatie draaien met connectie naar Docker MySQL
- ✅ Data blijft bewaard tussen restarts
