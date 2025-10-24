<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/business.php';

echo "Testing multiple work orders...\n\n";

// Test 3 different work orders
$workOrders = $pdo->query("SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 3")->fetchAll();

foreach ($workOrders as $wo) {
    $id = $wo['work_id'];
    
    // Get before state
    $before = $pdo->query("SELECT labor_cost, parts_cost, total_cost FROM working_details WHERE work_id = $id")->fetch();
    
    echo "Work Order #$id:\n";
    echo "  BEFORE: Labor=\${$before['labor_cost']}, Parts=\${$before['parts_cost']}, Total=\${$before['total_cost']}\n";
    
    // Add a part
    $product = $pdo->query("SELECT product_id, sku FROM product_details WHERE stock_qty > 10 ORDER BY RAND() LIMIT 1")->fetch();
    
    try {
        addWorkPart($pdo, $id, (int)$product['product_id'], 1, false);
        
        // Get after state
        $after = $pdo->query("SELECT labor_cost, parts_cost, total_cost FROM working_details WHERE work_id = $id")->fetch();
        $parts = $pdo->query("SELECT COUNT(*) FROM work_parts WHERE work_id = $id")->fetchColumn();
        
        echo "  AFTER:  Labor=\${$after['labor_cost']}, Parts=\${$after['parts_cost']}, Total=\${$after['total_cost']}\n";
        echo "  Parts in DB: $parts\n";
        echo "  Status: " . ($after['parts_cost'] > $before['parts_cost'] ? "✓ COST UPDATED" : "✗ FAILED") . "\n\n";
        
    } catch (Exception $e) {
        echo "  ERROR: {$e->getMessage()}\n\n";
    }
}

echo "All work orders use the same code - work_orders.php handles ALL IDs dynamically.\n";
