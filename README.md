# Mechfleet (Raw PHP + PDO + MySQL 8)

Requirements:
- PHP 8.x on XAMPP/WAMP
- MySQL 8.x
- Raw PHP (no frameworks), PDO for DB access
- Public web files live under `public/`

## Structure
- `includes/db.php` — reusable PDO connection
- `includes/util.php` — helpers (HTML escaping, SQL validation, param parsing)
- `public/index.php` — landing page
- `public/sql_demos.php` — paste and run SQL (SELECT/EXPLAIN only)
- `sql/ddl.sql` — schema
- `sql/dml_seed.sql` — seed data
- `sql/queries/` — sample queries by concept

## Setup (XAMPP/WAMP)
1. Create database in phpMyAdmin (default name: `mechfleet`):
  - Open http://localhost/phpmyadmin
  - Click Databases → Create database → Name: `mechfleet`, Collation: `utf8mb4_0900_ai_ci` → Create
2. Import schema and seed data:
  - In phpMyAdmin, select `mechfleet` → Import → Choose file `sql/ddl.sql` → Go
  - Then Import → Choose file `sql/dml_seed.sql` → Go
3. Configure DB connection (optional, defaults shown):
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_NAME=mechfleet`
   - `DB_USER=root`
   - `DB_PASS=` (empty by default on some XAMPP/WAMP)

On XAMPP, you can configure DB credentials directly in `includes/db.php` or set environment variables in Apache VirtualHost. Defaults are fine for local dev (root with no password).

## Usage
- Visit `http://localhost/Mechfleet/public/` for the landing page.
- Go to `SQL Demo Runner` to paste and run queries:
  - Only `SELECT` and `EXPLAIN` are allowed.
  - Multiple statements are blocked for safety.
  - Use named placeholders (e.g., `:id`) and provide params in the box, one per line.
  - Param syntax: `name=value`; optional type prefixes `:int:`, `:float:`, `:bool:`.

## Notes
- Prepared statements are used for any user input.
- All SQL files contain comments describing the SQL concepts they demonstrate.
- No stored procedures or non-SQL procedural code is used in the SQL files.
