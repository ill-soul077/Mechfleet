<?php
// Simple test to see if adding parts works
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/business.php';

// Find a work order and a product
$work = $pdo->query("SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 1")->fetch();
$product = $pdo->query("SELECT product_id, sku, product_name, stock_qty, unit_price FROM product_details WHERE stock_qty > 5 ORDER BY product_id LIMIT 1")->fetch();

if (!$work || !$product) {
    die("Need at least one work order and one product with stock\n");
}

echo "Testing addWorkPart function:\n";
echo "Work Order ID: {$work['work_id']}\n";
echo "Product: {$product['product_name']} (SKU: {$product['sku']})\n";
echo "Current Stock: {$product['stock_qty']}\n";
echo "Unit Price: \${$product['unit_price']}\n\n";

// Get current state
$before = $pdo->prepare("SELECT labor_cost, parts_cost, total_cost FROM working_details WHERE work_id = ?");
$before->execute([$work['work_id']]);
$beforeData = $before->fetch();

echo "BEFORE:\n";
echo "  Labor Cost: \${$beforeData['labor_cost']}\n";
echo "  Parts Cost: \${$beforeData['parts_cost']}\n";
echo "  Total Cost: \${$beforeData['total_cost']}\n\n";

// Try adding 2 units
try {
    echo "Adding 2 units...\n";
    addWorkPart($pdo, (int)$work['work_id'], (int)$product['product_id'], 2, false);
    echo "SUCCESS!\n\n";
    
    // Get new state
    $after = $pdo->prepare("SELECT labor_cost, parts_cost, total_cost FROM working_details WHERE work_id = ?");
    $after->execute([$work['work_id']]);
    $afterData = $after->fetch();
    
    echo "AFTER:\n";
    echo "  Labor Cost: \${$afterData['labor_cost']}\n";
    echo "  Parts Cost: \${$afterData['parts_cost']}\n";
    echo "  Total Cost: \${$afterData['total_cost']}\n\n";
    
    // Check stock
    $stockCheck = $pdo->prepare("SELECT stock_qty FROM product_details WHERE product_id = ?");
    $stockCheck->execute([$product['product_id']]);
    $newStock = $stockCheck->fetchColumn();
    echo "Stock updated: {$product['stock_qty']} -> $newStock\n\n";
    
    // Check work_parts
    $partsCheck = $pdo->prepare("SELECT * FROM work_parts WHERE work_id = ? AND product_id = ?");
    $partsCheck->execute([$work['work_id'], $product['product_id']]);
    $partRow = $partsCheck->fetch();
    if ($partRow) {
        echo "work_parts entry:\n";
        echo "  Quantity: {$partRow['quantity']}\n";
        echo "  Unit Price: \${$partRow['unit_price']}\n";
        echo "  Line Total: \${$partRow['line_total']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
