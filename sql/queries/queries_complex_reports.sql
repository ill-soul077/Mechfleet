-- queries_complex_reports.sql
-- Manager-level reports: expensive jobs, mechanic performance, loyal customers, reorder list

-- 1) Top 5 most expensive jobs this month
-- Expected: work_id, customer_name, vehicle_info, total_cost, completion_date
SELECT w.work_id,
       CONCAT(c.first_name,' ',c.last_name) AS customer_name,
       CONCAT(v.year,' ',v.make,' ',v.model) AS vehicle_info,
       w.total_cost, w.completion_date
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
JOIN vehicle v ON v.vehicle_id = w.vehicle_id
WHERE w.status = 'completed'
  AND w.completion_date IS NOT NULL
  AND w.completion_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
ORDER BY w.total_cost DESC
LIMIT 5;

-- 2) Mechanics ranked by average completion time (days) for completed jobs
-- Expected: mechanic_id, mechanic_name, jobs, avg_days_to_complete
SELECT m.mechanic_id,
       CONCAT(m.first_name,' ',m.last_name) AS mechanic_name,
       COUNT(*) AS jobs,
       ROUND(AVG(DATEDIFF(w.completion_date, w.start_date)), 2) AS avg_days_to_complete
FROM working_details w
JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id
WHERE w.status = 'completed' AND w.completion_date IS NOT NULL
GROUP BY m.mechanic_id, mechanic_name
HAVING COUNT(*) >= 3
ORDER BY avg_days_to_complete ASC, jobs DESC
LIMIT 10;

-- 3) Loyal customers: > 3 jobs completed in the last 12 months
-- Expected: customer_id, customer_name, jobs_last_year
SELECT c.customer_id,
       CONCAT(c.first_name,' ',c.last_name) AS customer_name,
       COUNT(*) AS jobs_last_year
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
WHERE w.status = 'completed'
  AND w.completion_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY c.customer_id, customer_name
HAVING COUNT(*) > 3
ORDER BY jobs_last_year DESC, c.customer_id ASC;

-- 4) Reorder list: products at or below reorder level
-- Expected: product_id, product_name, stock_qty, reorder_level
SELECT product_id, product_name, stock_qty, reorder_level
FROM product_details
WHERE stock_qty <= reorder_level
ORDER BY stock_qty ASC, product_id ASC
LIMIT 50;
