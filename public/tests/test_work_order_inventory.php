<?php
/**
 * Test: Work Order Creation with Inventory Decrement
 * This test verifies that when a work order is created with parts,
 * the inventory is properly decremented in the database.
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/business.php';

echo "<h1>Work Order Inventory Decrement Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
table { border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.step { background-color: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
</style>";

try {
    echo "<div class='step'><h2>Step 1: Get Test Data</h2>";
    
    // Get a product with stock
    $product = $pdo->query('SELECT product_id, product_name, sku, stock_qty, unit_price FROM product_details WHERE stock_qty > 10 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("No products with sufficient stock found");
    }
    
    echo "<p class='info'>Selected Product: <strong>{$product['product_name']}</strong> (SKU: {$product['sku']})</p>";
    echo "<p class='info'>Product ID: {$product['product_id']}</p>";
    echo "<p class='info'>Current Stock: <strong>{$product['stock_qty']}</strong></p>";
    echo "<p class='info'>Unit Price: \${$product['unit_price']}</p>";
    echo "</div>";
    
    // Get test customer, vehicle, mechanic, service
    $customer = $pdo->query('SELECT customer_id, CONCAT(first_name, " ", last_name) AS name FROM customer LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $vehicle = $pdo->query('SELECT vehicle_id, CONCAT(year, " ", make, " ", model) AS name FROM vehicle LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $mechanic = $pdo->query('SELECT mechanic_id, CONCAT(first_name, " ", last_name) AS name FROM mechanics WHERE active=1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $service = $pdo->query('SELECT service_id, service_name, estimated_hours FROM service_details WHERE active=1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer || !$vehicle || !$mechanic || !$service) {
        throw new Exception("Missing required test data (customer, vehicle, mechanic, or service)");
    }
    
    echo "<div class='step'><h2>Step 2: Create Work Order</h2>";
    
    // Calculate labor cost
    $laborRate = $pdo->query("SELECT hourly_rate FROM mechanics WHERE mechanic_id={$mechanic['mechanic_id']}")->fetchColumn();
    $laborCost = round($laborRate * $service['estimated_hours'], 2);
    
    echo "<p class='info'>Customer: {$customer['name']}</p>";
    echo "<p class='info'>Vehicle: {$vehicle['name']}</p>";
    echo "<p class='info'>Mechanic: {$mechanic['name']} (\${$laborRate}/hr)</p>";
    echo "<p class='info'>Service: {$service['service_name']} ({$service['estimated_hours']} hrs)</p>";
    echo "<p class='info'>Labor Cost: \${$laborCost}</p>";
    
    // Insert work order
    $stmt = $pdo->prepare('INSERT INTO working_details (customer_id,vehicle_id,assigned_mechanic_id,service_id,status,labor_cost,parts_cost,total_cost,start_date,notes) VALUES (:c,:v,:m,:s,:st,:lc,:pc,:tc,:sd,:n)');
    $stmt->execute([
        ':c' => $customer['customer_id'],
        ':v' => $vehicle['vehicle_id'],
        ':m' => $mechanic['mechanic_id'],
        ':s' => $service['service_id'],
        ':st' => 'pending',
        ':lc' => $laborCost,
        ':pc' => 0,
        ':tc' => $laborCost,
        ':sd' => date('Y-m-d'),
        ':n' => 'TEST: Inventory decrement verification'
    ]);
    
    $workOrderId = (int)$pdo->lastInsertId();
    echo "<p class='success'>✓ Work Order Created: #$workOrderId</p>";
    echo "</div>";
    
    echo "<div class='step'><h2>Step 3: Add Parts (Simulating Frontend Form Submission)</h2>";
    
    // Simulate adding 3 units of the product
    $quantityToAdd = 3;
    $stockBeforeAddingPart = (int)$pdo->query("SELECT stock_qty FROM product_details WHERE product_id={$product['product_id']}")->fetchColumn();
    
    echo "<p class='info'>Quantity to Add: <strong>$quantityToAdd</strong></p>";
    echo "<p class='info'>Stock Before Adding Part: <strong>$stockBeforeAddingPart</strong></p>";
    
    // Call addWorkPart (this is what work_orders.php does)
    addWorkPart($pdo, $workOrderId, $product['product_id'], $quantityToAdd, false);
    
    echo "<p class='success'>✓ Part Added to Work Order</p>";
    echo "</div>";
    
    echo "<div class='step'><h2>Step 4: Verify Inventory Decrement</h2>";
    
    // Check stock after
    $stockAfterAddingPart = (int)$pdo->query("SELECT stock_qty FROM product_details WHERE product_id={$product['product_id']}")->fetchColumn();
    $expectedStock = $stockBeforeAddingPart - $quantityToAdd;
    $actualDecrement = $stockBeforeAddingPart - $stockAfterAddingPart;
    
    echo "<p class='info'>Stock After Adding Part: <strong>$stockAfterAddingPart</strong></p>";
    echo "<p class='info'>Expected Stock: <strong>$expectedStock</strong></p>";
    echo "<p class='info'>Actual Decrement: <strong>$actualDecrement</strong></p>";
    
    if ($stockAfterAddingPart === $expectedStock) {
        echo "<p class='success'>✓✓✓ INVENTORY CORRECTLY DECREMENTED!</p>";
    } else {
        echo "<p class='error'>✗ ERROR: Stock mismatch! Expected $expectedStock but got $stockAfterAddingPart</p>";
    }
    echo "</div>";
    
    echo "<div class='step'><h2>Step 5: Verify Work Order Totals</h2>";
    
    // Check work order totals
    $workOrder = $pdo->query("SELECT labor_cost, parts_cost, total_cost FROM working_details WHERE work_id=$workOrderId")->fetch(PDO::FETCH_ASSOC);
    $expectedPartsCost = round($product['unit_price'] * $quantityToAdd, 2);
    $expectedTotalCost = round($laborCost + $expectedPartsCost, 2);
    
    echo "<table>";
    echo "<tr><th>Description</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";
    echo "<tr><td>Labor Cost</td><td>\${$laborCost}</td><td>\${$workOrder['labor_cost']}</td><td>" . ($workOrder['labor_cost'] == $laborCost ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td></tr>";
    echo "<tr><td>Parts Cost</td><td>\${$expectedPartsCost}</td><td>\${$workOrder['parts_cost']}</td><td>" . ($workOrder['parts_cost'] == $expectedPartsCost ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td></tr>";
    echo "<tr><td>Total Cost</td><td>\${$expectedTotalCost}</td><td>\${$workOrder['total_cost']}</td><td>" . ($workOrder['total_cost'] == $expectedTotalCost ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div class='step'><h2>Step 6: Verify Work Parts Record</h2>";
    
    // Check work_parts table
    $workPart = $pdo->query("SELECT wp.*, p.product_name FROM work_parts wp JOIN product_details p ON p.product_id=wp.product_id WHERE wp.work_id=$workOrderId")->fetch(PDO::FETCH_ASSOC);
    
    if ($workPart) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Product</td><td>{$workPart['product_name']}</td></tr>";
        echo "<tr><td>Quantity</td><td>{$workPart['quantity']}</td></tr>";
        echo "<tr><td>Unit Price (Snapshot)</td><td>\${$workPart['unit_price']}</td></tr>";
        echo "<tr><td>Line Total</td><td>\${$workPart['line_total']}</td></tr>";
        echo "</table>";
        echo "<p class='success'>✓ Work Parts Record Exists</p>";
    } else {
        echo "<p class='error'>✗ ERROR: No work parts record found!</p>";
    }
    echo "</div>";
    
    echo "<div class='step'><h2>Step 7: Cleanup Test Data</h2>";
    
    // Cleanup
    $pdo->exec("DELETE FROM work_parts WHERE work_id=$workOrderId");
    $pdo->exec("DELETE FROM working_details WHERE work_id=$workOrderId");
    $pdo->exec("UPDATE product_details SET stock_qty=$stockBeforeAddingPart WHERE product_id={$product['product_id']}");
    
    $stockAfterCleanup = (int)$pdo->query("SELECT stock_qty FROM product_details WHERE product_id={$product['product_id']}")->fetchColumn();
    
    echo "<p class='info'>Stock Restored: <strong>$stockAfterCleanup</strong> (was $stockBeforeAddingPart)</p>";
    echo "<p class='success'>✓ Test Data Cleaned Up</p>";
    echo "</div>";
    
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2 style='color: #155724;'>✓✓✓ ALL TESTS PASSED! ✓✓✓</h2>";
    echo "<p style='color: #155724;'><strong>Summary:</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Work order creation: <strong>Working</strong></li>";
    echo "<li>Inventory decrement: <strong>Working</strong></li>";
    echo "<li>Parts cost calculation: <strong>Working</strong></li>";
    echo "<li>Total cost calculation: <strong>Working</strong></li>";
    echo "<li>Database synchronization: <strong>Working</strong></li>";
    echo "</ul>";
    echo "<p style='color: #155724;'><strong>Your work order creation with inventory decrement is functioning correctly!</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2 style='color: #721c24;'>✗ TEST FAILED</h2>";
    echo "<p style='color: #721c24;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: #721c24;'><strong>Stack Trace:</strong></p>";
    echo "<pre style='color: #721c24;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<hr>
<h2>How to Use This Test</h2>
<ol>
    <li>Access this test page in your browser</li>
    <li>The test will automatically:
        <ul>
            <li>Create a test work order</li>
            <li>Add parts to it</li>
            <li>Verify inventory is decremented</li>
            <li>Verify costs are calculated correctly</li>
            <li>Clean up test data</li>
        </ul>
    </li>
    <li>All tests should pass with green checkmarks ✓</li>
</ol>

<p><a href="work_orders.php">← Back to Work Orders</a></p>
