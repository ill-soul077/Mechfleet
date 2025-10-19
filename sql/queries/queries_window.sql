-- queries_window.sql
-- Demonstrates: ROW_NUMBER(), RANK(), and running totals with SUM() OVER

-- 1) Row numbers per mechanic by completion date
-- Expected: work_id, assigned_mechanic_id, completion_date, rn
SELECT work_id, assigned_mechanic_id, completion_date,
       ROW_NUMBER() OVER (PARTITION BY assigned_mechanic_id
                          ORDER BY completion_date, work_id) AS rn
FROM working_details
WHERE completion_date IS NOT NULL
ORDER BY assigned_mechanic_id, rn
LIMIT 50;

-- 2) Rank services by total revenue contribution
-- Expected: service_id, service_name, service_revenue, rnk
SELECT s.service_id, s.service_name,
       ROUND(SUM(w.total_cost), 2) AS service_revenue,
       RANK() OVER (ORDER BY SUM(w.total_cost) DESC) AS rnk
FROM working_details w
JOIN service_details s ON s.service_id = w.service_id
GROUP BY s.service_id, s.service_name
ORDER BY rnk
LIMIT 10;

-- 3) Running total of income by day
-- Expected: payment_date, daily_amount, running_total
SELECT t.payment_date,
       ROUND(t.daily_amount, 2) AS daily_amount,
       ROUND(SUM(t.daily_amount) OVER (ORDER BY t.payment_date), 2) AS running_total
FROM (
  SELECT payment_date, SUM(amount) AS daily_amount
  FROM income
  GROUP BY payment_date
) t
ORDER BY t.payment_date DESC
LIMIT 30;
-- sql/queries/queries_window.sql
-- Concepts: window functions (ROW_NUMBER, RANK, SUM OVER, LAG/LEAD), partitions and ordering, running totals

-- Latest assignment per vehicle (matches view logic)
SELECT vehicle_id, driver_id, starts_at, ends_at
FROM (
  SELECT a.*, ROW_NUMBER() OVER (PARTITION BY vehicle_id ORDER BY COALESCE(ends_at,'9999-12-31') DESC, starts_at DESC) AS rn
  FROM assignments a
) t
WHERE rn = 1;

-- Running maintenance cost per vehicle over time
SELECT vehicle_id, performed_on, cost,
       SUM(cost) OVER (PARTITION BY vehicle_id ORDER BY performed_on ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS running_cost
FROM maintenance
ORDER BY vehicle_id, performed_on;

-- Gap between assignments per vehicle using LAG
SELECT vehicle_id, driver_id, starts_at,
       LAG(ends_at) OVER (PARTITION BY vehicle_id ORDER BY starts_at) AS prev_ends_at
FROM assignments
ORDER BY vehicle_id, starts_at;
