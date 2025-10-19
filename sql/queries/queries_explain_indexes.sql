-- queries_explain_indexes.sql
-- Demonstrates: EXPLAIN for slow vs. optimized queries; index recommendations

-- 1) Slow query candidate: search vehicles by license_plate (no index defined in DDL)
-- Guidance: Add an index on vehicle.license_plate to improve ref lookups
EXPLAIN FORMAT=TRADITIONAL
SELECT vehicle_id, customer_id, vin, license_plate
FROM vehicle
WHERE license_plate = 'MEF-0042';

-- Suggested index:
-- CREATE INDEX idx_vehicle_plate ON vehicle (license_plate);

-- 2) Composite filtering on working_details by status and start_date
-- Guidance: We have idx_work_status and idx_work_start_date; a composite (status, start_date) helps combined filters
EXPLAIN FORMAT=TRADITIONAL
SELECT work_id, status, start_date
FROM working_details
WHERE status = 'completed' AND start_date >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
ORDER BY start_date DESC
LIMIT 50;

-- Optional composite index:
-- CREATE INDEX idx_work_status_start ON working_details (status, start_date);

-- 3) Parts lookup by SKU through work_parts
-- Guidance: product_details has idx on sku; ensure join path uses product_id and prefer covering index on work_parts(product_id, work_id)
EXPLAIN FORMAT=TRADITIONAL
SELECT wp.work_id, p.sku, wp.quantity, wp.line_total
FROM product_details p
JOIN work_parts wp ON wp.product_id = p.product_id
WHERE p.sku = 'SKU-0008'
ORDER BY wp.work_id DESC
LIMIT 20;

-- Optional supporting index:
-- CREATE INDEX idx_workparts_product ON work_parts (product_id, work_id);
