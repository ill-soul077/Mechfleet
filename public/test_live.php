<?php
// Direct live test - add part and check immediately
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/business.php';

// Get latest work order
$work = $pdo->query("SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 1")->fetch();
$work_id = $work['work_id'];

// Get a product
$product = $pdo->query("SELECT product_id FROM product_details WHERE stock_qty > 5 LIMIT 1")->fetch();
$product_id = $product['product_id'];

echo "<h2>Testing Work Order #{$work_id}</h2>";

// Before state
$before = $pdo->query("SELECT * FROM working_details WHERE work_id = {$work_id}")->fetch();
echo "<h3>BEFORE:</h3>";
echo "Parts Cost: \${$before['parts_cost']}<br>";
echo "Total Cost: \${$before['total_cost']}<br>";

// Add part
echo "<h3>Adding part {$product_id}...</h3>";
try {
    addWorkPart($pdo, $work_id, $product_id, 1, false);
    echo "✅ SUCCESS<br>";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "<br>";
}

// After state
$after = $pdo->query("SELECT * FROM working_details WHERE work_id = {$work_id}")->fetch();
echo "<h3>AFTER:</h3>";
echo "Parts Cost: \${$after['parts_cost']}<br>";
echo "Total Cost: \${$after['total_cost']}<br>";

// Check parts
$parts = $pdo->query("SELECT * FROM work_parts WHERE work_id = {$work_id}")->fetchAll();
echo "<h3>Parts in DB:</h3>";
foreach ($parts as $p) {
    echo "Product {$p['product_id']}: Qty={$p['quantity']}, Price=\${$p['unit_price']}, Total=\${$p['line_total']}<br>";
}

echo "<br><a href='work_orders.php?id={$work_id}'>View Work Order #{$work_id}</a>";
