-- queries_set_ops.sql
-- Demonstrates: UNION vs UNION ALL, simulated INTERSECT and EXCEPT using JOIN/NOT EXISTS in MySQL

-- 1) UNION: Customers who have income OR have jobs (distinct customers)
-- Expected: customer_id, customer_name
SELECT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
UNION
SELECT c2.customer_id, CONCAT(c2.first_name,' ',c2.last_name) AS customer_name
FROM income i
JOIN working_details w2 ON w2.work_id = i.work_id
JOIN customer c2 ON c2.customer_id = w2.customer_id
ORDER BY customer_id;

-- 2) UNION ALL: Same as above but keep duplicates to show difference
-- Expected: customer_id, customer_name (with duplicates)
SELECT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
UNION ALL
SELECT c2.customer_id, CONCAT(c2.first_name,' ',c2.last_name) AS customer_name
FROM income i
JOIN working_details w2 ON w2.work_id = i.work_id
JOIN customer c2 ON c2.customer_id = w2.customer_id
ORDER BY customer_id;

-- 3) Simulate INTERSECT: Customers who both have jobs AND have income
-- Expected: customer_id, customer_name
SELECT DISTINCT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
JOIN income i ON i.work_id = w.work_id
ORDER BY c.customer_id;

-- 4) Simulate EXCEPT: Customers who have jobs but NO income (unpaid or cancelled)
-- Expected: customer_id, customer_name
SELECT DISTINCT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
WHERE NOT EXISTS (
  SELECT 1
  FROM income i
  WHERE i.work_id = w.work_id
)
ORDER BY c.customer_id;
