-- queries_aggregates.sql
-- Demonstrates: GROUP BY, HAVING, COUNT, SUM, AVG, MIN, MAX, and monthly date rollups

-- 1) Monthly revenue: sum of income amounts by year-month
-- Expected: year_month, orders, gross_amount, avg_amount
SELECT DATE_FORMAT(payment_date, '%Y-%m') AS year_month,
       COUNT(*) AS orders,
       ROUND(SUM(amount), 2) AS gross_amount,
       ROUND(AVG(amount), 2) AS avg_amount
FROM income
GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
ORDER BY year_month DESC
LIMIT 12;

-- 2) Parts usage per product with totals
-- Expected: product_id, product_name, uses_count, qty_total, revenue_total
SELECT p.product_id, p.product_name,
       COUNT(*) AS uses_count,
       SUM(wp.quantity) AS qty_total,
       ROUND(SUM(wp.line_total), 2) AS revenue_total
FROM work_parts wp
JOIN product_details p ON p.product_id = wp.product_id
GROUP BY p.product_id, p.product_name
ORDER BY revenue_total DESC
LIMIT 20;

-- 3) Mechanics workload summary with HAVING filter
-- Expected: mechanic_id, jobs, avg_total_cost, max_total_cost
SELECT w.assigned_mechanic_id AS mechanic_id,
       COUNT(*) AS jobs,
       ROUND(AVG(w.total_cost), 2) AS avg_total_cost,
       ROUND(MAX(w.total_cost), 2) AS max_total_cost
FROM working_details w
GROUP BY w.assigned_mechanic_id
HAVING COUNT(*) >= 5
ORDER BY jobs DESC, mechanic_id ASC;
