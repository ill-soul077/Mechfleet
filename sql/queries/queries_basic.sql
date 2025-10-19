-- queries_basic.sql
-- Demonstrates: SELECT, projection (AS), WHERE filters, ORDER BY, LIMIT
-- Expected columns noted above each query.

-- 1) Simple projection with alias, filtering by last name prefix
-- Expected: customer_id, name
SELECT customer_id, CONCAT(first_name,' ',last_name) AS name
FROM customer
WHERE last_name LIKE 'R%'
ORDER BY customer_id DESC
LIMIT 10;

-- 2) Projection from vehicle with computed label
-- Expected: vehicle_id, vehicle_label, mileage
SELECT v.vehicle_id,
       CONCAT(v.year,' ',v.make,' ',v.model) AS vehicle_label,
       v.mileage
FROM vehicle v
WHERE v.mileage BETWEEN 20000 AND 80000
ORDER BY v.mileage DESC, v.vehicle_id ASC
LIMIT 15;

-- 3) Services priced below $150 sorted by price then name
-- Expected: service_id, service_name, base_price
SELECT service_id, service_name, base_price
FROM service_details
WHERE base_price < 150.00 AND active = 1
ORDER BY base_price ASC, service_name ASC
LIMIT 20;
