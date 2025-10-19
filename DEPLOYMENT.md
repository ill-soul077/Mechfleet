# Mechfleet Deployment Guide

## Quick Start - Command Line Setup

### Prerequisites
- XAMPP installed at `E:\Xampp`
- Apache and MySQL services running in XAMPP Control Panel

### Step 1: Create Database and Load Schema

Open PowerShell in the project root (`E:\Xampp\htdocs\Mechfleet`) and run:

```powershell
# Create database and tables
& "E:\Xampp\mysql\bin\mysql.exe" -u root --execute="SOURCE sql/ddl.sql"
```

This will:
- Create database `mechfleet` with utf8mb4 charset
- Create all tables (manager, customer, vehicle, mechanics, service_details, product_details, working_details, work_parts, income)
- Set up foreign keys, indexes, and constraints
- Create the `vw_open_jobs` view

### Step 2: Insert Seed Data

```powershell
# Load sample data
& "E:\Xampp\mysql\bin\mysql.exe" -u root mechfleet --execute="SOURCE sql/dml_seed.sql"
```

This will insert:
- 5 managers
- 50 customers
- ~70 vehicles
- 10 mechanics
- 12 services
- 40 products
- 100 work orders
- 250 work parts
- 120 income payments

### Step 3: Verify Installation

```powershell
# Check record counts
& "E:\Xampp\mysql\bin\mysql.exe" -u root -e "USE mechfleet; SELECT COUNT(*) AS total_customers FROM customer; SELECT COUNT(*) AS total_work_orders FROM working_details;"
```

You should see:
- 50 customers
- 100 work orders

### Step 4: Access the Application

1. Ensure Apache is running in XAMPP Control Panel
2. Open your browser and visit: `http://localhost/Mechfleet/public/`
3. Default login (if required):
   - Check `sql/dml_seed.sql` for manager credentials

### Step 5: Test Database Connection

Visit: `http://localhost/Mechfleet/public/tests/db_check.php`

You should see:
- Status: OK
- Database configuration details

### Step 6: View Manual Checks

Visit: `http://localhost/Mechfleet/public/tests/manual_checks.php`

This page runs critical queries and shows:
- Total customers
- Work orders in progress
- Income last 30 days

**Take a screenshot** of this page including the timestamp for submission proof.

## Alternative: Using the Refresh Scripts

If you have mysql in your PATH, you can use the convenience scripts:

### Windows (PowerShell):
```powershell
# Default database name (mechfleet)
scripts\refresh_db.bat

# Custom database name
scripts\refresh_db.bat my_custom_db

# If root has password
$env:MYSQL_ARGS='-u root -p'
scripts\refresh_db.bat
```

### Linux/macOS (Bash):
```bash
# Default database name (mechfleet)
bash scripts/refresh_db.sh

# Custom database name
bash scripts/refresh_db.sh my_custom_db

# If root has password
MYSQL_ARGS="-u root -p" bash scripts/refresh_db.sh
```

## Troubleshooting

### MySQL not in PATH
Use the full path to mysql.exe as shown in Step 1-3 above.

### MariaDB vs MySQL
XAMPP uses MariaDB 10.4, which is compatible but doesn't support:
- `utf8mb4_0900_ai_ci` collation (uses `utf8mb4_unicode_ci` instead)
- COMMENT clauses on foreign key constraints

The DDL has been updated for MariaDB compatibility.

### Connection refused
- Verify MySQL is running in XAMPP Control Panel
- Check port 3306 is not blocked
- Verify credentials in `includes/config.php`

### Table already exists
Drop and recreate:
```powershell
& "E:\Xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS mechfleet; CREATE DATABASE mechfleet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then run Step 1-2 again.

## Key URLs

- Home: `http://localhost/Mechfleet/public/`
- DB Check: `http://localhost/Mechfleet/public/tests/db_check.php`
- Manual Checks: `http://localhost/Mechfleet/public/tests/manual_checks.php`
- SQL Demos: `http://localhost/Mechfleet/public/sql_demos.php`
- Transactions Demo: `http://localhost/Mechfleet/public/sql_transactions_demo.php`
- EXPLAIN Demo: `http://localhost/Mechfleet/public/sql_explain_demo.php`
- Reports: `http://localhost/Mechfleet/public/reports.php`
- Set Ops & Subqueries: `http://localhost/Mechfleet/public/setops_subqueries.php`

## Database Configuration

Edit `includes/config.php` if needed:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'mechfleet');
define('DB_USER', 'root');
define('DB_PASS', '');
```

## Success Indicators

✅ Database created with utf8mb4 charset
✅ All 9 tables + 1 view created
✅ 50 customers inserted
✅ 100 work orders inserted
✅ DB check page shows "OK"
✅ Manual checks page displays counts
✅ Application loads without errors
