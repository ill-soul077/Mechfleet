-- queries_subqueries.sql
-- Demonstrates: non-correlated and correlated subqueries

-- 1) Non-correlated: Services cheaper than the overall average price
-- Expected: service_id, service_name, base_price
SELECT s.service_id, s.service_name, s.base_price
FROM service_details s
WHERE s.base_price < (
  SELECT AVG(base_price) FROM service_details WHERE active = 1
)
ORDER BY s.base_price ASC;

-- 2) Correlated: Work orders with parts_cost greater than the avg parts_cost for that service
-- Expected: work_id, service_id, parts_cost, avg_service_parts_cost
SELECT w.work_id, w.service_id, w.parts_cost,
       (
         SELECT AVG(w2.parts_cost)
         FROM working_details w2
         WHERE w2.service_id = w.service_id
       ) AS avg_service_parts_cost
FROM working_details w
WHERE w.parts_cost > (
  SELECT AVG(w3.parts_cost)
  FROM working_details w3
  WHERE w3.service_id = w.service_id
)
ORDER BY w.parts_cost DESC
LIMIT 25;
