# Grading Mapping

This project demonstrates core SQL concepts and safe transactional programming in a compact raw PHP app. Use this guide to quickly locate proof for each requirement.

## Environment
- App root: `E:\Xampp\htdocs\Mechfleet`
- Base URL: `http://localhost/Mechfleet/public/`
- Dev toggle: `includes/config.php` → `DEV_MODE`

## Concept → Files and URLs
- DDL
  - SQL: `sql/ddl.sql`
  - Evidence: Tables, PK/FK, UNIQUE, CHECK, ENUM, indexes, view `vw_open_jobs`
- DML (bulk insert)
  - SQL: `sql/dml_seed.sql`
  - Evidence: multi-row INSERTs for managers/customers/vehicles/mechanics/services/products/work_orders/work_parts/income
- Joins
  - SQL: `sql/queries/queries_joins.sql`
  - Run: `public/sql_demos.php` (select the joins example)
- Subqueries
  - SQL: `sql/queries/queries_subqueries.sql`
  - Run: `public/setops_subqueries.php`
- Set operations
  - SQL: `sql/queries/queries_set_ops.sql`
  - Run: `public/setops_subqueries.php`
- Views
  - SQL: `sql/queries/queries_views.sql`
  - Run: `public/sql_demos.php`
- Aggregates & HAVING
  - SQL: `sql/queries/queries_aggregates.sql`
  - Run: `public/sql_demos.php`
- Window functions
  - SQL: `sql/queries/queries_window.sql`
  - Run: `public/reports.php` (monthly revenue change, mechanic ranking)
- Transactions (ACID)
  - Run: `public/sql_transactions_demo.php` — shows BEGIN/COMMIT and ROLLBACK paths with stock checks
- EXPLAIN & Indexes
  - SQL: `sql/queries/queries_explain.sql`
  - Run: `public/sql_explain_demo.php` — plan and timing before/after creating `idx_product_name`
- Business rules (snapshot pricing & stock control)
  - Code: `includes/business.php`
  - Usage: add part via `public/work_orders.php` (modal → API), invoice snapshot on completion
- Manager reports
  - SQL: `sql/queries/queries_complex_reports.sql` (BEGIN_SQL/END_SQL blocks)
  - Run: `public/reports.php` — shows raw SQL and results

## Demo script (quick)
1) DDL/DML: open `sql/ddl.sql` and `sql/dml_seed.sql` (phpMyAdmin tables visible)
2) SQL Demos: run a join and an aggregate in `public/sql_demos.php`
3) Transactions Demo: success + rollback in `public/sql_transactions_demo.php`
4) EXPLAIN Demo: compare plans/time in `public/sql_explain_demo.php`
5) Set Ops: `public/setops_subqueries.php` (UNION/ALL, INTERSECT/EXCEPT simulation, correlated subquery)
6) Reports: `public/reports.php` (top expensive jobs, revenue % change)
