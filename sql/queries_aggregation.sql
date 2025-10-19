-- sql/queries_aggregation.sql
-- Concepts: GROUP BY, HAVING, aggregate functions, rollups, subqueries

-- Total maintenance cost per vehicle
SELECT v.id AS vehicle_id, v.model, SUM(m.cost) AS total_cost, COUNT(*) AS num_records
FROM vehicles v
LEFT JOIN maintenance m ON m.vehicle_id = v.id
GROUP BY v.id, v.model
ORDER BY total_cost DESC;

-- Manufacturers with avg capacity over 5000
SELECT mf.name, AVG(v.capacity_kg) AS avg_capacity
FROM manufacturers mf
JOIN vehicles v ON v.manufacturer_id = mf.id
GROUP BY mf.id, mf.name
HAVING AVG(v.capacity_kg) > 5000
ORDER BY avg_capacity DESC;

-- Subquery: vehicles with cost above fleet median cost
SELECT v.id, v.model, x.total_cost
FROM (
  SELECT vehicle_id, SUM(cost) AS total_cost
  FROM maintenance
  GROUP BY vehicle_id
) x
JOIN vehicles v ON v.id = x.vehicle_id
WHERE x.total_cost > (
  SELECT AVG(t.total_cost) FROM (
    SELECT SUM(cost) AS total_cost FROM maintenance GROUP BY vehicle_id
  ) t
);
