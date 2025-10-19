-- sql/queries_window.sql
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
