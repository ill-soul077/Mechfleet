-- sql/queries_basics.sql
-- Concepts: basic SELECT, projection, filtering (WHERE), ordering (ORDER BY), limiting (LIMIT/OFFSET), LIKE with prefix index

-- Select all vehicles
SELECT id, vin, model, model_year, type, capacity_kg, active FROM vehicles;

-- Filter: only active trucks with capacity >= 10000
SELECT id, model, capacity_kg
FROM vehicles
WHERE active = 1 AND type = 'truck' AND capacity_kg >= 10000
ORDER BY capacity_kg DESC;

-- LIKE prefix can use ix_vehicles_model_prefix
SELECT id, model
FROM vehicles
WHERE model LIKE 'Tit%'
ORDER BY model ASC
LIMIT 10 OFFSET 0;
