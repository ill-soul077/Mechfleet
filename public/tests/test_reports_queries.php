<?php
require_once __DIR__ . '/../../includes/db.php';

echo "<h2>Testing Reports Queries</h2><hr>";

// Test 1: Monthly Revenue
echo "<h3>1. Monthly Revenue</h3>";
try {
    $sql1 = "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS year_month, COUNT(*) AS orders, ROUND(SUM(amount), 2) AS gross_amount, ROUND(AVG(amount), 2) AS avg_amount FROM income GROUP BY 1 ORDER BY 1 DESC LIMIT 12";
    $result = $pdo->query($sql1)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Top Expensive Jobs
echo "<h3>2. Top Expensive Jobs</h3>";
try {
    $sql2 = "SELECT w.work_id, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle_info, w.total_cost, w.completion_date, CONCAT(m.first_name, ' ', m.last_name) AS mechanic_name FROM working_details w JOIN customer c ON c.customer_id = w.customer_id JOIN vehicle v ON v.vehicle_id = w.vehicle_id JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id WHERE w.status = 'completed' AND w.completion_date IS NOT NULL AND w.completion_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') ORDER BY w.total_cost DESC LIMIT 5";
    $result = $pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Parts Usage
echo "<h3>3. Parts Usage</h3>";
try {
    $sql3 = "SELECT p.product_id, p.product_name, COUNT(*) AS uses_count, SUM(wp.quantity) AS qty_total, ROUND(SUM(wp.line_total), 2) AS revenue_total FROM work_parts wp JOIN product_details p ON p.product_id = wp.product_id GROUP BY p.product_id, p.product_name ORDER BY revenue_total DESC LIMIT 10";
    $result = $pdo->query($sql3)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Mechanics Workload
echo "<h3>4. Mechanics Workload</h3>";
try {
    $sql4 = "SELECT w.assigned_mechanic_id AS mechanic_id, CONCAT(m.first_name, ' ', m.last_name) AS mechanic_name, COUNT(*) AS jobs, ROUND(AVG(w.total_cost), 2) AS avg_job_cost, ROUND(MAX(w.total_cost), 2) AS max_job_cost FROM working_details w JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id GROUP BY w.assigned_mechanic_id, mechanic_name HAVING COUNT(*) >= 1 ORDER BY jobs DESC, mechanic_id ASC LIMIT 10";
    $result = $pdo->query($sql4)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Work Order Details
echo "<h3>5. Work Order Details</h3>";
try {
    $sql5 = "SELECT w.work_id, CONCAT(c.first_name, ' ', c.last_name) AS customer, CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle, CONCAT(m.first_name, ' ', m.last_name) AS mechanic, s.service_name, w.status, w.total_cost, w.start_date FROM working_details w INNER JOIN customer c ON c.customer_id = w.customer_id INNER JOIN vehicle v ON v.vehicle_id = w.vehicle_id INNER JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id INNER JOIN service_details s ON s.service_id = w.service_id ORDER BY w.work_id DESC LIMIT 10";
    $result = $pdo->query($sql5)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 6: Customers Vehicles
echo "<h3>6. Customers Vehicles</h3>";
try {
    $sql6 = "SELECT c.customer_id, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, c.email, COUNT(v.vehicle_id) AS vehicle_count, CASE WHEN COUNT(v.vehicle_id) = 0 THEN 'No Vehicles' ELSE 'Has Vehicles' END AS status FROM customer c LEFT JOIN vehicle v ON v.customer_id = c.customer_id GROUP BY c.customer_id, customer_name, c.email ORDER BY vehicle_count DESC, c.customer_id DESC LIMIT 10";
    $result = $pdo->query($sql6)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 7: Unused Products
echo "<h3>7. Unused Products</h3>";
try {
    $sql7 = "SELECT p.product_id, p.sku, p.product_name, p.stock_qty, p.unit_price FROM product_details p LEFT JOIN work_parts wp ON wp.product_id = p.product_id WHERE wp.product_id IS NULL ORDER BY p.product_id DESC LIMIT 10";
    $result = $pdo->query($sql7)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 8: All People (UNION)
echo "<h3>8. All People (UNION)</h3>";
try {
    $sql8 = "SELECT CONCAT(first_name, ' ', last_name) AS name, email, phone, 'Customer' AS type FROM customer UNION SELECT CONCAT(first_name, ' ', last_name) AS name, email, phone, 'Mechanic' AS type FROM mechanics ORDER BY type, name LIMIT 20";
    $result = $pdo->query($sql8)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 9: Services Usage
echo "<h3>9. Services Usage</h3>";
try {
    $sql9 = "SELECT s.service_id, s.service_name, COUNT(w.work_id) AS times_used, CASE WHEN COUNT(w.work_id) = 0 THEN 'Never Used' WHEN COUNT(w.work_id) < 5 THEN 'Rarely Used' ELSE 'Frequently Used' END AS usage_status FROM service_details s LEFT JOIN working_details w ON w.service_id = s.service_id GROUP BY s.service_id, s.service_name ORDER BY times_used DESC, s.service_id LIMIT 15";
    $result = $pdo->query($sql9)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 10: Mechanics Status
echo "<h3>10. Mechanics Status</h3>";
try {
    $sql10 = "SELECT 'Active' AS status, COUNT(*) AS count, ROUND(AVG(hourly_rate), 2) AS avg_hourly_rate FROM mechanics WHERE active = 1 UNION SELECT 'Inactive' AS status, COUNT(*) AS count, ROUND(AVG(hourly_rate), 2) AS avg_hourly_rate FROM mechanics WHERE active = 0";
    $result = $pdo->query($sql10)->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Success - " . count($result) . " rows<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr><h3>All tests completed!</h3>";
?>
