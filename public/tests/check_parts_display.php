<?php
// Test to see if parts are displaying
require_once __DIR__ . '/../../includes/db.php';

$id = 103; // Use the work order we just added parts to

$job = $pdo->prepare('SELECT w.*, CONCAT(c.first_name, " ", c.last_name) AS customer_name FROM working_details w JOIN customer c ON c.customer_id=w.customer_id WHERE w.work_id=:id');
$job->execute([':id'=>$id]);
$jobRow = $job->fetch(PDO::FETCH_ASSOC);

echo "Work Order #{$id}\n";
echo "Customer: {$jobRow['customer_name']}\n";
echo "Labor Cost: \${$jobRow['labor_cost']}\n";
echo "Parts Cost: \${$jobRow['parts_cost']}\n";
echo "Total Cost: \${$jobRow['total_cost']}\n\n";

$parts = $pdo->prepare('SELECT wp.*, p.sku, p.product_name FROM work_parts wp JOIN product_details p ON p.product_id=wp.product_id WHERE wp.work_id=:id ORDER BY p.product_name');
$parts->execute([':id'=>$id]);
$partsRows = $parts->fetchAll(PDO::FETCH_ASSOC);

echo "Parts Used (" . count($partsRows) . " items):\n";
if (empty($partsRows)) {
    echo "  [No parts]\n";
} else {
    foreach ($partsRows as $p) {
        echo "  - SKU: {$p['sku']} | {$p['product_name']} | Qty: {$p['quantity']} | Unit: \${$p['unit_price']} | Total: \${$p['line_total']}\n";
    }
}
