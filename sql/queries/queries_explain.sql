-- sql/queries/queries_explain.sql
-- Concepts: EXPLAIN plans, using indexes, checking filter and join strategies

-- EXPLAIN a join that should use FK/PK
EXPLAIN FORMAT=TRADITIONAL
SELECT mnt.id, v.vin, v.model
FROM maintenance mnt
JOIN vehicles v ON v.id = mnt.vehicle_id
WHERE v.model LIKE 'Tit%';

-- EXPLAIN with window function (MySQL will still show plan for underlying scans)
EXPLAIN
SELECT vehicle_id, driver_id,
       ROW_NUMBER() OVER (PARTITION BY vehicle_id ORDER BY starts_at DESC) AS rn
FROM assignments;
