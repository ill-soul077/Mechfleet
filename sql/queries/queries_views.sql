-- queries_views.sql
-- Demonstrates: CREATE VIEW and selecting from it, plus a materialized-view workaround using a snapshot table

-- 1) Create or replace vw_open_jobs (should match ddl.sql; included here for teaching)
-- Expected columns: work_id, status, start_date, customer_name, customer_phone, vehicle_info, vin, mechanic_name, service_name, labor_cost, parts_cost, total_cost, notes
DROP VIEW IF EXISTS vw_open_jobs;
CREATE VIEW vw_open_jobs AS
SELECT
  wd.work_id,
  wd.status,
  wd.start_date,
  CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
  c.phone AS customer_phone,
  CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle_info,
  v.vin,
  CONCAT(m.first_name, ' ', m.last_name) AS mechanic_name,
  s.service_name,
  wd.labor_cost,
  wd.parts_cost,
  wd.total_cost,
  wd.notes
FROM working_details wd
JOIN customer c ON c.customer_id = wd.customer_id
JOIN vehicle v ON v.vehicle_id = wd.vehicle_id
JOIN mechanics m ON m.mechanic_id = wd.assigned_mechanic_id
JOIN service_details s ON s.service_id = wd.service_id
WHERE wd.status IN ('pending', 'in_progress')
ORDER BY wd.start_date ASC, wd.work_id ASC;

-- 2) Query the view
-- Expected: same columns as the view
SELECT * FROM vw_open_jobs LIMIT 20;

-- 3) Materialized view workaround: monthly revenue snapshot
-- Expected table: monthly_revenue_snapshots with year_month, orders, gross_amount
DROP TABLE IF EXISTS monthly_revenue_snapshots;
CREATE TABLE monthly_revenue_snapshots (
  year_month CHAR(7) PRIMARY KEY,
  orders INT NOT NULL,
  gross_amount DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB;

-- Populate snapshot from income
INSERT INTO monthly_revenue_snapshots (year_month, orders, gross_amount)
SELECT DATE_FORMAT(payment_date, '%Y-%m') AS year_month,
       COUNT(*) AS orders,
       ROUND(SUM(amount), 2) AS gross_amount
FROM income
GROUP BY DATE_FORMAT(payment_date, '%Y-%m');

-- Query the snapshot
SELECT * FROM monthly_revenue_snapshots ORDER BY year_month DESC;
