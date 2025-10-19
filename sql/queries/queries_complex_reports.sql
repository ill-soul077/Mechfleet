-- queries_complex_reports.sql
-- Manager-level reports: expensive jobs, mechanic performance, revenue trends, loyal customers

-- REPORT: Top 5 most expensive jobs this month
-- BEGIN_SQL:top_expensive_jobs
SELECT w.work_id,
       CONCAT(c.first_name,' ',c.last_name) AS customer_name,
        CONCAT(v.year,' ',v.make,' ',v.model) AS vehicle_info,
       w.total_cost, w.completion_date,
       CONCAT(m.first_name,' ',m.last_name) AS mechanic_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
JOIN vehicle v ON v.vehicle_id = w.vehicle_id
JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id
WHERE w.status = 'completed'
  AND w.completion_date IS NOT NULL
  AND w.completion_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
ORDER BY w.total_cost DESC
LIMIT 5;
-- END_SQL

-- REPORT: Mechanic ranking by average completion time (days)
-- BEGIN_SQL:mechanic_avg_completion_rank
SELECT mechanic_id,
       mechanic_name,
       jobs,
       avg_days_to_complete,
       RANK() OVER (ORDER BY avg_days_to_complete ASC) AS rank_by_speed
FROM (
  SELECT m.mechanic_id,
         CONCAT(m.first_name,' ',m.last_name) AS mechanic_name,
         COUNT(*) AS jobs,
         ROUND(AVG(DATEDIFF(w.completion_date, w.start_date)), 2) AS avg_days_to_complete
  FROM working_details w
  JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id
  WHERE w.status = 'completed' AND w.completion_date IS NOT NULL
  GROUP BY m.mechanic_id, mechanic_name
  HAVING COUNT(*) >= 1
) x
ORDER BY avg_days_to_complete ASC, jobs DESC
LIMIT 20;
-- END_SQL

-- REPORT: Monthly revenue with % change vs prior month
-- BEGIN_SQL:monthly_revenue_change
WITH monthly AS (
  SELECT DATE_FORMAT(completion_date, '%Y-%m') AS ym,
         ROUND(SUM(total_cost),2) AS revenue
  FROM working_details
  WHERE status = 'completed' AND completion_date IS NOT NULL
  GROUP BY DATE_FORMAT(completion_date, '%Y-%m')
), ranked AS (
  SELECT ym, revenue,
         LAG(revenue) OVER (ORDER BY ym) AS prev_revenue
  FROM monthly
)
SELECT ym,
       revenue,
       prev_revenue,
       CASE WHEN prev_revenue IS NULL OR prev_revenue = 0 THEN NULL
            ELSE ROUND((revenue - prev_revenue) / prev_revenue * 100, 2)
       END AS pct_change
FROM ranked
ORDER BY ym DESC
LIMIT 24;
-- END_SQL

-- REPORT: Customers with > 3 visits in the last 12 months
-- BEGIN_SQL:loyal_customers
SELECT c.customer_id,
       CONCAT(c.first_name,' ',c.last_name) AS customer_name,
       COUNT(*) AS jobs_last_year
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
WHERE w.status = 'completed'
  AND w.completion_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
GROUP BY c.customer_id, customer_name
HAVING COUNT(*) > 3
ORDER BY jobs_last_year DESC, c.customer_id ASC
LIMIT 50;
-- END_SQL
