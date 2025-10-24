<?php
require_once __DIR__ . '/../includes/db.php';

$id = 100;

$parts = $pdo->prepare('SELECT wp.*, p.sku, p.product_name FROM work_parts wp JOIN product_details p ON p.product_id=wp.product_id WHERE wp.work_id=:id ORDER BY p.product_name');
$parts->execute([':id'=>$id]);
$partsRows = $parts->fetchAll(PDO::FETCH_ASSOC);

echo "Query result for work order #$id:\n";
echo "Parts found: " . count($partsRows) . "\n\n";

if (empty($partsRows)) {
    echo "No parts - will show 'No parts added yet' message\n";
} else {
    echo "Parts list:\n";
    foreach ($partsRows as $p) {
        echo "  SKU: {$p['sku']}\n";
        echo "  Product: {$p['product_name']}\n";
        echo "  Quantity: {$p['quantity']}\n";
        echo "  Unit Price: \${$p['unit_price']}\n";
        echo "  Line Total: \${$p['line_total']}\n";
        echo "  ---\n";
    }
}
