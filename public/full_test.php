<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/business.php';

echo "=== COMPREHENSIVE TEST ===\n\n";

// Get a work order
$work = $pdo->query("SELECT work_id, labor_cost, parts_cost, total_cost FROM working_details ORDER BY work_id DESC LIMIT 1")->fetch();
$workId = $work['work_id'];

echo "Testing Work Order #$workId\n";
echo "BEFORE:\n";
echo "  Labor: \${$work['labor_cost']}\n";
echo "  Parts: \${$work['parts_cost']}\n";
echo "  Total: \${$work['total_cost']}\n\n";

// Get a product
$product = $pdo->query("SELECT product_id, sku, product_name, stock_qty, unit_price FROM product_details WHERE stock_qty > 10 ORDER BY product_id LIMIT 1")->fetch();

echo "Adding: {$product['product_name']} (ID: {$product['product_id']})\n";
echo "Stock before: {$product['stock_qty']}\n";
echo "Price: \${$product['unit_price']}\n\n";

// Add part
try {
    addWorkPart($pdo, $workId, (int)$product['product_id'], 2, false);
    echo "✓ Part added successfully\n\n";
} catch (Exception $e) {
    echo "✗ ERROR: {$e->getMessage()}\n";
    exit(1);
}

// Check results
$afterWork = $pdo->query("SELECT labor_cost, parts_cost, total_cost FROM working_details WHERE work_id = $workId")->fetch();
$afterStock = $pdo->query("SELECT stock_qty FROM product_details WHERE product_id = {$product['product_id']}")->fetch();
$parts = $pdo->query("SELECT * FROM work_parts WHERE work_id = $workId AND product_id = {$product['product_id']}")->fetch();

echo "AFTER:\n";
echo "  Labor: \${$afterWork['labor_cost']}\n";
echo "  Parts: \${$afterWork['parts_cost']} " . ($afterWork['parts_cost'] > $work['parts_cost'] ? '✓' : '✗ NOT UPDATED!') . "\n";
echo "  Total: \${$afterWork['total_cost']} " . ($afterWork['total_cost'] > $work['total_cost'] ? '✓' : '✗ NOT UPDATED!') . "\n";
echo "  Stock: {$afterStock['stock_qty']} " . ($afterStock['stock_qty'] < $product['stock_qty'] ? '✓' : '✗ NOT DECREMENTED!') . "\n\n";

if ($parts) {
    echo "work_parts entry: ✓\n";
    echo "  Quantity: {$parts['quantity']}\n";
    echo "  Unit Price: \${$parts['unit_price']}\n";
    echo "  Line Total: \${$parts['line_total']}\n\n";
} else {
    echo "work_parts entry: ✗ NOT FOUND!\n\n";
}

// Test the query used in work_orders.php
$displayParts = $pdo->prepare('SELECT wp.*, p.sku, p.product_name FROM work_parts wp JOIN product_details p ON p.product_id=wp.product_id WHERE wp.work_id=:id ORDER BY p.product_name');
$displayParts->execute([':id'=>$workId]);
$partsRows = $displayParts->fetchAll(PDO::FETCH_ASSOC);

echo "Parts query (as shown on page):\n";
if (empty($partsRows)) {
    echo "  ✗ NO PARTS FOUND (empty array)\n";
} else {
    echo "  ✓ Found " . count($partsRows) . " part(s)\n";
    foreach ($partsRows as $p) {
        echo "    - {$p['sku']}: {$p['product_name']} x{$p['quantity']} @ \${$p['unit_price']} = \${$p['line_total']}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
