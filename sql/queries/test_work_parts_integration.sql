-- =====================================================================
-- TEST: Work Parts Integration - Verify automatic cost updates and stock decrement
-- =====================================================================
-- This script demonstrates that when parts are added to work orders:
-- 1. Stock is automatically decremented in product_details
-- 2. Parts cost is automatically updated in working_details
-- 3. Total cost is recalculated (labor + parts)

-- SETUP: Get a test work order and product
SELECT 
    w.work_id,
    w.labor_cost,
    w.parts_cost,
    w.total_cost,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    s.service_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
JOIN service_details s ON s.service_id = w.service_id
WHERE w.status != 'cancelled'
ORDER BY w.work_id DESC
LIMIT 1;

-- Check current stock of a product
SELECT 
    product_id,
    sku,
    product_name,
    stock_qty,
    unit_price,
    (stock_qty * unit_price) AS inventory_value
FROM product_details
WHERE stock_qty > 5
ORDER BY product_id
LIMIT 5;

-- View current parts for a work order
SELECT 
    wp.work_id,
    wp.product_id,
    p.sku,
    p.product_name,
    wp.quantity,
    wp.unit_price,
    wp.line_total,
    w.parts_cost AS work_order_parts_total,
    w.labor_cost,
    w.total_cost
FROM work_parts wp
JOIN product_details p ON p.product_id = wp.product_id
JOIN working_details w ON w.work_id = wp.work_id
ORDER BY wp.work_id DESC, p.product_name
LIMIT 10;

-- Summary report: Work orders with parts breakdown
SELECT 
    w.work_id,
    w.status,
    CONCAT(c.first_name, ' ', c.last_name) AS customer,
    COUNT(DISTINCT wp.product_id) AS parts_count,
    SUM(wp.quantity) AS total_parts_qty,
    w.labor_cost,
    w.parts_cost,
    w.total_cost,
    (w.total_cost - w.labor_cost - w.parts_cost) AS cost_variance
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
LEFT JOIN work_parts wp ON wp.work_id = w.work_id
GROUP BY w.work_id, w.status, customer, w.labor_cost, w.parts_cost, w.total_cost
HAVING parts_count > 0
ORDER BY w.work_id DESC
LIMIT 20;

-- Verify stock changes after adding parts
-- This query shows products used in recent work orders
SELECT 
    p.product_id,
    p.sku,
    p.product_name,
    p.stock_qty AS current_stock,
    COALESCE(SUM(wp.quantity), 0) AS total_used_in_work_orders,
    p.unit_price,
    COALESCE(SUM(wp.line_total), 0) AS total_revenue_from_parts
FROM product_details p
LEFT JOIN work_parts wp ON wp.product_id = p.product_id
GROUP BY p.product_id, p.sku, p.product_name, p.stock_qty, p.unit_price
HAVING total_used_in_work_orders > 0
ORDER BY total_used_in_work_orders DESC
LIMIT 20;

-- Check for any inconsistencies in cost calculations
SELECT 
    w.work_id,
    w.labor_cost,
    w.parts_cost AS recorded_parts_cost,
    COALESCE(SUM(wp.line_total), 0) AS calculated_parts_cost,
    (w.parts_cost - COALESCE(SUM(wp.line_total), 0)) AS parts_cost_variance,
    w.total_cost AS recorded_total,
    (w.labor_cost + COALESCE(SUM(wp.line_total), 0)) AS calculated_total,
    (w.total_cost - (w.labor_cost + COALESCE(SUM(wp.line_total), 0))) AS total_variance
FROM working_details w
LEFT JOIN work_parts wp ON wp.work_id = w.work_id
GROUP BY w.work_id, w.labor_cost, w.parts_cost, w.total_cost
HAVING ABS(parts_cost_variance) > 0.01 OR ABS(total_variance) > 0.01
ORDER BY w.work_id DESC;

-- EXPECTED BEHAVIOR VERIFICATION:
-- After running addWorkPart() function through the application:
-- 1. work_parts table should have a new row (or updated quantity)
-- 2. product_details.stock_qty should be decremented by quantity
-- 3. working_details.parts_cost should equal SUM(work_parts.line_total)
-- 4. working_details.total_cost should equal (labor_cost + parts_cost)
