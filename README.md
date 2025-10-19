# Mechfleet (Raw PHP + PDO + MySQL 8)

A learning-oriented auto-service management app showcasing SQL and transaction best practices with raw PHP + PDO over MySQL 8. It includes a complete schema, rich seed data, demo pages for SQL concepts, and CRUD screens for day-to-day operations.

## Learning goals (SQL concepts covered)
- DDL: tables, PK/FK, UNIQUE, CHECK, ENUM, indexes, views
- DML: bulk INSERT, UPDATE, DELETE
- Joins: inner/left joins across normalized tables
- Subqueries: scalar and correlated
- Set operations: UNION/UNION ALL; INTERSECT/EXCEPT simulated (MySQL)
- Aggregates: GROUP BY, HAVING
- Window functions: RANK, ROW_NUMBER, LAG
- Transactions: ACID flows with COMMIT/ROLLBACK
- EXPLAIN & indexing: plan analysis and index impact

## Project structure
- `includes/config.php` — DB env and DEV_MODE
- `includes/db.php` — PDO connection and helpers (runQuery/runStatement)
- `includes/auth.php` — simple session auth (manager login)
- `includes/business.php` — snapshot pricing and stock control services
- `includes/util.php` — helpers (escaping, param parsing)
- `public/*.php` — pages (CRUD, demos, reports)
- `sql/ddl.sql` — schema (all tables, FKs, constraints, view)
- `sql/dml_seed.sql` — seed data (bulk inserts)
- `sql/queries/*.sql` — topic-focused examples

## Setup (XAMPP on Windows)
1) Place project
- Clone or copy this repo into `E:\Xampp\htdocs\Mechfleet` (so URLs are `http://localhost/Mechfleet/public`).

2) Create database (phpMyAdmin)
- Open http://localhost/phpmyadmin → Databases → Create database
- Name: `mechfleet`; Collation: `utf8mb4_0900_ai_ci` → Create

3) Import schema and seed
- In phpMyAdmin, select `mechfleet` → Import → choose `sql/ddl.sql` → Go
- Then Import → choose `sql/dml_seed.sql` → Go
  - The seed creates: 5 managers, 50 customers, ~70 vehicles, 10 mechanics, 12 services, 40 products, 100 work orders, 250 work_parts, 120 income
  - If phpMyAdmin times out, use MySQL CLI:
    ```powershell
    # from project root
    mysql -u root -p mechfleet < sql\ddl.sql
    mysql -u root -p mechfleet < sql\dml_seed.sql
    ```

4) Configure DB connection
- Edit `includes/config.php` if needed (defaults below):
  - DB_HOST=127.0.0.1; DB_PORT=3306; DB_NAME=mechfleet; DB_USER=root; DB_PASS=""
  - DEV_MODE=true (enables dev-only pages and detailed errors)

5) Start Apache
- Launch XAMPP Control Panel → Start Apache and MySQL
- Visit: `http://localhost/Mechfleet/public/`

## Where to find each concept (mapping)
- DDL → `sql/ddl.sql`
- DML (bulk insert) → `sql/dml_seed.sql`
- Joins → `sql/queries/queries_joins.sql` + `public/sql_demos.php`
- Subqueries → `sql/queries/queries_subqueries.sql` + `public/setops_subqueries.php`
- Set ops → `sql/queries/queries_set_ops.sql` + `public/setops_subqueries.php`
- Views → `sql/queries/queries_views.sql` + `public/sql_demos.php`
- Aggregates & HAVING → `sql/queries/queries_aggregates.sql` + `public/sql_demos.php`
- Window functions → `sql/queries/queries_window.sql` + `public/reports.php`
- Transactions → `public/sql_transactions_demo.php`
- EXPLAIN & Indexes → `public/sql_explain_demo.php` + `sql/queries/queries_explain.sql`
- Business rules (snapshot pricing, stock control) → `includes/business.php`
- Reports (manager-facing) → `public/reports.php`

## Key demo pages
- SQL Demos: `public/sql_demos.php` (read-only SELECT/EXPLAIN runner and curated queries)
- Transactions Demo (dev-only): `public/sql_transactions_demo.php` — shows BEGIN/INSERT/UPDATE/COMMIT and rollback with final stock
- EXPLAIN Demo: `public/sql_explain_demo.php` — compare plans and timing before/after an index
- Set Ops & Subqueries: `public/setops_subqueries.php` — UNION/ALL, INTERSECT/EXCEPT simulation, correlated subquery
- Reports: `public/reports.php` — manager reports with raw SQL shown

## Suggested screenshots for graders
1) Schema overview: snippet from `sql/ddl.sql` and phpMyAdmin table list
2) SQL Demos running a join and an aggregate (table results visible)
3) Transactions Demo: success and rollback sections with logs and final stock
4) EXPLAIN Demo: Before and After tables showing type/key/rows differences
5) Set Ops & Subqueries page with UNION vs UNION ALL and correlated subquery outputs
6) Reports page: top expensive jobs and monthly revenue with % change

## 90–120s live demo script
- 0:00 Open `public/index.php` → Click “SQL Demos” → Run a JOIN query and show results.
- 0:20 Open “Transactions Demo” → Run Success, then Rollback → point out COMMIT vs ROLLBACK and final stock.
- 0:45 Open “EXPLAIN Demo” → Run Before (no index), then After (with index) → compare `type`/`rows`/time.
- 1:10 Open “Set Ops & Subqueries” → Scroll through UNION vs UNION ALL and the correlated subquery.
- 1:30 Open “Reports” → Show “Top 5 expensive jobs” and “Monthly revenue % change”.

## Notes
- All interactive pages use prepared statements.
- DEV_MODE toggles access to dev-only pages and verbose errors.
- Seed dates are relative to `CURDATE()` so the dataset looks fresh.
