<?php
require_once __DIR__ . '/../includes/db.php';

$id = 100;

echo "Checking Work Order #$id\n\n";

// Check if work order exists
$work = $pdo->query("SELECT * FROM working_details WHERE work_id = $id")->fetch();
if (!$work) {
    die("Work order #$id does not exist!\n");
}

echo "Work Order Details:\n";
echo "  Customer ID: {$work['customer_id']}\n";
echo "  Labor Cost: \${$work['labor_cost']}\n";
echo "  Parts Cost: \${$work['parts_cost']}\n";
echo "  Total Cost: \${$work['total_cost']}\n\n";

// Check parts
$parts = $pdo->query("SELECT * FROM work_parts WHERE work_id = $id")->fetchAll();
echo "Parts in work_parts table: " . count($parts) . "\n";
foreach ($parts as $p) {
    echo "  - Product {$p['product_id']}: qty={$p['quantity']}, unit=\${$p['unit_price']}, total=\${$p['line_total']}\n";
}

echo "\n";

// Try adding a part
$product = $pdo->query("SELECT product_id, sku, product_name, stock_qty FROM product_details WHERE stock_qty > 5 LIMIT 1")->fetch();
echo "Testing: Adding product {$product['product_id']} ({$product['sku']})...\n";

require_once __DIR__ . '/../includes/business.php';

try {
    addWorkPart($pdo, $id, (int)$product['product_id'], 1, false);
    echo "SUCCESS!\n\n";
    
    $after = $pdo->query("SELECT * FROM working_details WHERE work_id = $id")->fetch();
    echo "After adding part:\n";
    echo "  Parts Cost: \${$after['parts_cost']}\n";
    echo "  Total Cost: \${$after['total_cost']}\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
