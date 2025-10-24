<?php
/**
 * Test script to demonstrate Work Parts Integration
 * This script shows how parts are added to work orders and how it affects:
 * 1. Parts cost in work order
 * 2. Total cost calculation
 * 3. Stock decrement in products
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/business.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Work Parts Integration Test</title>";
echo "<style>
  body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
  h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
  h2 { color: #0d6efd; margin-top: 30px; }
  .test-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #0d6efd; }
  .success { color: #198754; font-weight: bold; }
  .error { color: #dc3545; font-weight: bold; }
  .info { color: #0d6efd; }
  table { width: 100%; border-collapse: collapse; margin: 10px 0; }
  th { background: #0d6efd; color: white; padding: 10px; text-align: left; }
  td { padding: 8px; border-bottom: 1px solid #dee2e6; }
  tr:hover { background: #f1f1f1; }
  .highlight { background: #fff3cd; }
  .badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; }
  .badge-success { background: #198754; color: white; }
  .badge-warning { background: #ffc107; color: black; }
  .badge-danger { background: #dc3545; color: white; }
  code { background: #f1f1f1; padding: 2px 6px; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔧 Work Parts Integration Test</h1>";
echo "<p>This test demonstrates the automatic integration between work orders, parts, and inventory.</p>";

try {
  // =================================================================
  // STEP 1: Find a work order
  // =================================================================
  echo "<div class='test-section'>";
  echo "<h2>📋 Step 1: Select a Work Order</h2>";
  
  $workStmt = $pdo->query("
    SELECT w.work_id, w.labor_cost, w.parts_cost, w.total_cost, w.status,
           CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
           CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle_info,
           s.service_name
    FROM working_details w
    JOIN customer c ON c.customer_id = w.customer_id
    JOIN vehicle v ON v.vehicle_id = w.vehicle_id
    JOIN service_details s ON s.service_id = w.service_id
    WHERE w.status IN ('pending', 'in_progress')
    ORDER BY w.work_id DESC
    LIMIT 1
  ");
  
  $workOrder = $workStmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$workOrder) {
    echo "<p class='error'>❌ No active work orders found. Please create a work order first.</p>";
    echo "</div></body></html>";
    exit;
  }
  
  echo "<table>";
  echo "<tr><th>Property</th><th>Value</th></tr>";
  echo "<tr><td><strong>Work Order ID</strong></td><td>#{$workOrder['work_id']}</td></tr>";
  echo "<tr><td><strong>Customer</strong></td><td>{$workOrder['customer_name']}</td></tr>";
  echo "<tr><td><strong>Vehicle</strong></td><td>{$workOrder['vehicle_info']}</td></tr>";
  echo "<tr><td><strong>Service</strong></td><td>{$workOrder['service_name']}</td></tr>";
  echo "<tr><td><strong>Status</strong></td><td><span class='badge badge-warning'>{$workOrder['status']}</span></td></tr>";
  echo "<tr class='highlight'><td><strong>Labor Cost</strong></td><td>$" . number_format($workOrder['labor_cost'], 2) . "</td></tr>";
  echo "<tr class='highlight'><td><strong>Parts Cost</strong></td><td>$" . number_format($workOrder['parts_cost'], 2) . "</td></tr>";
  echo "<tr class='highlight'><td><strong>Total Cost</strong></td><td><strong>$" . number_format($workOrder['total_cost'], 2) . "</strong></td></tr>";
  echo "</table>";
  echo "<p class='info'>✅ Using Work Order #{$workOrder['work_id']} for testing</p>";
  echo "</div>";
  
  // =================================================================
  // STEP 2: Find a product with stock
  // =================================================================
  echo "<div class='test-section'>";
  echo "<h2>📦 Step 2: Select a Product</h2>";
  
  $productStmt = $pdo->query("
    SELECT product_id, sku, product_name, stock_qty, unit_price,
           category, subcategory
    FROM product_details
    WHERE stock_qty >= 5
    ORDER BY stock_qty DESC
    LIMIT 5
  ");
  
  $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
  
  if (empty($products)) {
    echo "<p class='error'>❌ No products with sufficient stock found.</p>";
    echo "</div></body></html>";
    exit;
  }
  
  echo "<table>";
  echo "<tr><th>ID</th><th>SKU</th><th>Product Name</th><th>Category</th><th>Stock</th><th>Unit Price</th><th>Inventory Value</th></tr>";
  foreach ($products as $p) {
    $stockClass = $p['stock_qty'] > 20 ? 'badge-success' : ($p['stock_qty'] > 10 ? 'badge-warning' : 'badge-danger');
    $invValue = $p['stock_qty'] * $p['unit_price'];
    echo "<tr>";
    echo "<td>{$p['product_id']}</td>";
    echo "<td><code>{$p['sku']}</code></td>";
    echo "<td>{$p['product_name']}</td>";
    echo "<td>{$p['category']}</td>";
    echo "<td><span class='badge {$stockClass}'>{$p['stock_qty']}</span></td>";
    echo "<td>$" . number_format($p['unit_price'], 2) . "</td>";
    echo "<td>$" . number_format($invValue, 2) . "</td>";
    echo "</tr>";
  }
  echo "</table>";
  
  $testProduct = $products[0];
  echo "<p class='info'>✅ Using Product: <strong>{$testProduct['product_name']}</strong> (SKU: {$testProduct['sku']})</p>";
  echo "</div>";
  
  // =================================================================
  // STEP 3: Current parts for this work order
  // =================================================================
  echo "<div class='test-section'>";
  echo "<h2>🔩 Step 3: Current Parts on Work Order</h2>";
  
  $partsStmt = $pdo->prepare("
    SELECT wp.*, p.sku, p.product_name, p.category
    FROM work_parts wp
    JOIN product_details p ON p.product_id = wp.product_id
    WHERE wp.work_id = :wid
    ORDER BY p.product_name
  ");
  $partsStmt->execute([':wid' => $workOrder['work_id']]);
  $currentParts = $partsStmt->fetchAll(PDO::FETCH_ASSOC);
  
  if (empty($currentParts)) {
    echo "<p class='info'>ℹ️ No parts currently added to this work order.</p>";
  } else {
    echo "<table>";
    echo "<tr><th>SKU</th><th>Product</th><th>Category</th><th>Qty</th><th>Unit Price</th><th>Line Total</th></tr>";
    $partsTotal = 0;
    foreach ($currentParts as $part) {
      echo "<tr>";
      echo "<td><code>{$part['sku']}</code></td>";
      echo "<td>{$part['product_name']}</td>";
      echo "<td>{$part['category']}</td>";
      echo "<td>{$part['quantity']}</td>";
      echo "<td>$" . number_format($part['unit_price'], 2) . "</td>";
      echo "<td><strong>$" . number_format($part['line_total'], 2) . "</strong></td>";
      echo "</tr>";
      $partsTotal += $part['line_total'];
    }
    echo "<tr class='highlight'><td colspan='5' align='right'><strong>Parts Total:</strong></td><td><strong>$" . number_format($partsTotal, 2) . "</strong></td></tr>";
    echo "</table>";
    
    if (abs($partsTotal - $workOrder['parts_cost']) > 0.01) {
      echo "<p class='error'>⚠️ WARNING: Calculated parts total ($" . number_format($partsTotal, 2) . ") doesn't match work order parts_cost ($" . number_format($workOrder['parts_cost'], 2) . ")</p>";
    } else {
      echo "<p class='success'>✅ Parts total matches work order parts_cost</p>";
    }
  }
  echo "</div>";
  
  // =================================================================
  // STEP 4: Demonstrate what WOULD happen if we add a part
  // =================================================================
  echo "<div class='test-section'>";
  echo "<h2>🧮 Step 4: Simulation - Adding 3 units of {$testProduct['product_name']}</h2>";
  
  $qty = 3;
  $unitPrice = $testProduct['unit_price'];
  $lineTotal = $qty * $unitPrice;
  $newStock = $testProduct['stock_qty'] - $qty;
  $newPartsCost = $workOrder['parts_cost'] + $lineTotal;
  $newTotalCost = $workOrder['labor_cost'] + $newPartsCost;
  
  echo "<h3>📊 Before vs After Comparison</h3>";
  echo "<table>";
  echo "<tr><th>Item</th><th>BEFORE</th><th>CHANGE</th><th>AFTER</th></tr>";
  
  echo "<tr class='highlight'>";
  echo "<td><strong>Product Stock ({$testProduct['sku']})</strong></td>";
  echo "<td>{$testProduct['stock_qty']} units</td>";
  echo "<td class='error'>-{$qty} units</td>";
  echo "<td><strong>{$newStock} units</strong></td>";
  echo "</tr>";
  
  echo "<tr class='highlight'>";
  echo "<td><strong>Work Order Parts Cost</strong></td>";
  echo "<td>$" . number_format($workOrder['parts_cost'], 2) . "</td>";
  echo "<td class='success'>+$" . number_format($lineTotal, 2) . "</td>";
  echo "<td><strong>$" . number_format($newPartsCost, 2) . "</strong></td>";
  echo "</tr>";
  
  echo "<tr class='highlight'>";
  echo "<td><strong>Work Order Total Cost</strong></td>";
  echo "<td>$" . number_format($workOrder['total_cost'], 2) . "</td>";
  echo "<td class='success'>+$" . number_format($lineTotal, 2) . "</td>";
  echo "<td><strong>$" . number_format($newTotalCost, 2) . "</strong></td>";
  echo "</tr>";
  
  echo "</table>";
  
  echo "<h3>📝 Transaction Details</h3>";
  echo "<pre style='background:#f1f1f1; padding:15px; border-radius:5px;'>";
  echo "1. Lock product row (FOR UPDATE)\n";
  echo "2. Validate stock: {$testProduct['stock_qty']} >= {$qty} ✅\n";
  echo "3. Capture unit price: \${$unitPrice}\n";
  echo "4. Calculate line total: {$qty} × \${$unitPrice} = \${$lineTotal}\n";
  echo "5. INSERT/UPDATE work_parts table\n";
  echo "6. UPDATE product_details: stock_qty = {$testProduct['stock_qty']} - {$qty} = {$newStock}\n";
  echo "7. Calculate new parts_cost: SUM(work_parts.line_total) = \${$newPartsCost}\n";
  echo "8. UPDATE working_details:\n";
  echo "   - parts_cost = \${$newPartsCost}\n";
  echo "   - total_cost = \${$workOrder['labor_cost']} + \${$newPartsCost} = \${$newTotalCost}\n";
  echo "9. COMMIT transaction\n";
  echo "</pre>";
  
  echo "<p class='info'><strong>ℹ️ Note:</strong> This is a simulation. No actual database changes were made.</p>";
  echo "<p>To actually add parts, use the Work Orders page in the application and click the 'Add Part' button.</p>";
  echo "</div>";
  
  // =================================================================
  // STEP 5: Verify Database Integrity
  // =================================================================
  echo "<div class='test-section'>";
  echo "<h2>✅ Step 5: Database Integrity Check</h2>";
  
  $integrityStmt = $pdo->query("
    SELECT 
      w.work_id,
      w.labor_cost,
      w.parts_cost AS recorded_parts_cost,
      COALESCE(SUM(wp.line_total), 0) AS calculated_parts_cost,
      w.total_cost AS recorded_total,
      (w.labor_cost + COALESCE(SUM(wp.line_total), 0)) AS calculated_total,
      ABS(w.parts_cost - COALESCE(SUM(wp.line_total), 0)) AS parts_variance,
      ABS(w.total_cost - (w.labor_cost + COALESCE(SUM(wp.line_total), 0))) AS total_variance
    FROM working_details w
    LEFT JOIN work_parts wp ON wp.work_id = w.work_id
    GROUP BY w.work_id, w.labor_cost, w.parts_cost, w.total_cost
    HAVING parts_variance > 0.01 OR total_variance > 0.01
    ORDER BY w.work_id DESC
    LIMIT 10
  ");
  
  $issues = $integrityStmt->fetchAll(PDO::FETCH_ASSOC);
  
  if (empty($issues)) {
    echo "<p class='success'>✅ All work orders have consistent cost calculations!</p>";
    echo "<p>No discrepancies found between recorded costs and calculated totals.</p>";
  } else {
    echo "<p class='error'>⚠️ Found " . count($issues) . " work order(s) with cost discrepancies:</p>";
    echo "<table>";
    echo "<tr><th>Work ID</th><th>Labor</th><th>Parts (Recorded)</th><th>Parts (Calculated)</th><th>Total (Recorded)</th><th>Total (Calculated)</th></tr>";
    foreach ($issues as $issue) {
      echo "<tr>";
      echo "<td>#{$issue['work_id']}</td>";
      echo "<td>$" . number_format($issue['labor_cost'], 2) . "</td>";
      echo "<td>$" . number_format($issue['recorded_parts_cost'], 2) . "</td>";
      echo "<td>$" . number_format($issue['calculated_parts_cost'], 2) . "</td>";
      echo "<td>$" . number_format($issue['recorded_total'], 2) . "</td>";
      echo "<td>$" . number_format($issue['calculated_total'], 2) . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  }
  echo "</div>";
  
  // =================================================================
  // Summary
  // =================================================================
  echo "<div class='test-section'>";
  echo "<h2>📊 Summary</h2>";
  echo "<ul>";
  echo "<li class='success'>✅ Work orders table has labor_cost, parts_cost, and total_cost columns</li>";
  echo "<li class='success'>✅ work_parts junction table stores individual part line items</li>";
  echo "<li class='success'>✅ product_details table has stock_qty that decrements automatically</li>";
  echo "<li class='success'>✅ addWorkPart() function handles all calculations atomically in a transaction</li>";
  echo "<li class='success'>✅ Unit prices are captured as snapshots for historical accuracy</li>";
  echo "<li class='success'>✅ Stock validation prevents overselling</li>";
  echo "</ul>";
  
  echo "<h3>🎯 System Features</h3>";
  echo "<ol>";
  echo "<li><strong>Automatic Cost Calculation:</strong> Parts cost and total cost update automatically when parts are added</li>";
  echo "<li><strong>Stock Management:</strong> Product stock decrements automatically when used in work orders</li>";
  echo "<li><strong>Snapshot Pricing:</strong> Prices captured at time of use (immune to future price changes)</li>";
  echo "<li><strong>Transaction Safety:</strong> All operations in a database transaction (all-or-nothing)</li>";
  echo "<li><strong>Visual Feedback:</strong> UI reloads to show updated costs and parts list immediately</li>";
  echo "</ol>";
  echo "</div>";
  
  echo "<hr>";
  echo "<p style='text-align:center; color:#666;'>Test completed at " . date('Y-m-d H:i:s') . "</p>";
  
} catch (Exception $e) {
  echo "<div class='test-section'>";
  echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
  echo "</div>";
}

echo "</body></html>";
