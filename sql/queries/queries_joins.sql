-- queries_joins.sql
-- Demonstrates: INNER JOIN, LEFT JOIN, FULL OUTER JOIN workaround using UNION

-- 1) INNER JOIN: Jobs with assigned mechanic and service info
-- Expected: work_id, status, mechanic_name, service_name, start_date, completion_date
SELECT w.work_id, w.status,
       CONCAT(m.first_name,' ',m.last_name) AS mechanic_name,
       s.service_name,
       w.start_date, w.completion_date
FROM working_details w
INNER JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id
INNER JOIN service_details s ON s.service_id = w.service_id
ORDER BY w.start_date DESC, w.work_id DESC
LIMIT 25;

-- 2) LEFT JOIN: All jobs, include parts summary if present
-- Expected: work_id, total_cost, parts_count, parts_total
SELECT w.work_id, w.total_cost,
       COUNT(wp.product_id) AS parts_count,
       COALESCE(SUM(wp.line_total), 0) AS parts_total
FROM working_details w
LEFT JOIN work_parts wp ON wp.work_id = w.work_id
GROUP BY w.work_id, w.total_cost
ORDER BY parts_count DESC, w.work_id ASC
LIMIT 30;

-- 3) FULL OUTER JOIN workaround between customers and income
-- MySQL lacks FULL OUTER JOIN; emulate with UNION of LEFT JOINs
-- Expected: customer_id, customer_name, income_id, amount
SELECT c.customer_id,
       CONCAT(c.first_name,' ',c.last_name) AS customer_name,
       i.income_id,
       i.amount
FROM customer c
LEFT JOIN working_details w ON w.customer_id = c.customer_id
LEFT JOIN income i ON i.work_id = w.work_id
UNION
SELECT c2.customer_id,
       CONCAT(c2.first_name,' ',c2.last_name) AS customer_name,
       i2.income_id,
       i2.amount
FROM income i2
LEFT JOIN working_details w2 ON w2.work_id = i2.work_id
LEFT JOIN customer c2 ON c2.customer_id = w2.customer_id
ORDER BY customer_id, income_id;
-- sql/queries/queries_joins.sql
-- Concepts: INNER JOIN, LEFT JOIN, join on FKs, multi-table joins, USING vs ON, table aliases

-- Vehicle with manufacturer name (INNER JOIN)
SELECT v.id, v.vin, v.model, m.name AS manufacturer
FROM vehicles AS v
JOIN manufacturers AS m ON m.id = v.manufacturer_id
ORDER BY v.id;

-- Vehicles and their current driver, if any (LEFT JOIN view)
SELECT v.id, v.model, d.first_name, d.last_name
FROM vehicles v
LEFT JOIN v_vehicle_current_assignment ca ON ca.vehicle_id = v.id
LEFT JOIN drivers d ON d.id = ca.driver_id
ORDER BY v.id;

-- Maintenance joined to vehicles and manufacturers
SELECT mnt.id, v.vin, v.model, m.name AS manufacturer, mnt.kind, mnt.cost
FROM maintenance mnt
JOIN vehicles v ON v.id = mnt.vehicle_id
JOIN manufacturers m ON m.id = v.manufacturer_id
ORDER BY mnt.performed_on DESC;
