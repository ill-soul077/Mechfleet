<?php
// Debug script to test work order creation
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/business.php';

echo "=== Testing Work Order Creation ===\n\n";

// Test function
function compute_labor_cost(PDO $pdo, int $mechanic_id, int $service_id): float {
  $st = $pdo->prepare('SELECT m.hourly_rate, s.estimated_hours FROM mechanics m, service_details s WHERE m.mechanic_id=:mid AND s.service_id=:sid');
  $st->execute([':mid'=>$mechanic_id, ':sid'=>$service_id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) return 0.0;
  return round((float)$row['hourly_rate'] * (float)$row['estimated_hours'], 2);
}

// Simulate POST data
$_POST = [
    'action' => 'create',
    'customer_id' => '1',
    'vehicle_id' => '1',
    'assigned_mechanic_id' => '1',
    'service_id' => '1',
    'start_date' => date('Y-m-d'),
    'status' => 'pending',
    'notes' => 'Test work order from debug script'
];

echo "POST Data:\n";
print_r($_POST);
echo "\n";

try {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $vehicle_id  = (int)($_POST['vehicle_id'] ?? 0);
    $mechanic_id = (int)($_POST['assigned_mechanic_id'] ?? 0);
    $service_id  = (int)($_POST['service_id'] ?? 0);
    $start_date  = trim($_POST['start_date'] ?? date('Y-m-d'));
    $status      = trim($_POST['status'] ?? 'pending');
    $notes       = trim($_POST['notes'] ?? '');
    
    echo "Validated inputs:\n";
    echo "  Customer ID: $customer_id\n";
    echo "  Vehicle ID: $vehicle_id\n";
    echo "  Mechanic ID: $mechanic_id\n";
    echo "  Service ID: $service_id\n";
    echo "  Start Date: $start_date\n";
    echo "  Status: $status\n";
    echo "  Notes: $notes\n\n";
    
    if (!$customer_id || !$vehicle_id || !$mechanic_id || !$service_id) {
        throw new RuntimeException('All fields are required');
    }
    
    $labor = compute_labor_cost($pdo, $mechanic_id, $service_id);
    echo "Computed labor cost: \$$labor\n\n";
    
    // Start transaction to test
    $pdo->beginTransaction();
    
    $parts_cost = 0.00;
    $total_cost = $labor + $parts_cost;
    
    $stmt = $pdo->prepare('INSERT INTO working_details (customer_id,vehicle_id,assigned_mechanic_id,service_id,status,labor_cost,parts_cost,total_cost,start_date,notes) VALUES (:c,:v,:m,:s,:st,:lc,:pc,:tc,:sd,:n)');
    
    $result = $stmt->execute([
        ':c'=>$customer_id, 
        ':v'=>$vehicle_id, 
        ':m'=>$mechanic_id, 
        ':s'=>$service_id, 
        ':st'=>$status, 
        ':lc'=>$labor,
        ':pc'=>$parts_cost,
        ':tc'=>$total_cost,
        ':sd'=>$start_date, 
        ':n'=>$notes
    ]);
    
    $newId = (int)$pdo->lastInsertId();
    
    echo "INSERT Result:\n";
    echo "  Success: " . ($result ? "YES" : "NO") . "\n";
    echo "  New Work Order ID: $newId\n";
    echo "  Rows Affected: " . $stmt->rowCount() . "\n";
    
    // Verify the record exists
    $verify = $pdo->prepare('SELECT * FROM working_details WHERE work_id = :id');
    $verify->execute([':id' => $newId]);
    $record = $verify->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        echo "\nVerified record in database:\n";
        print_r($record);
    } else {
        echo "\nWARNING: Record not found in database!\n";
    }
    
    // Rollback to not affect actual data
    $pdo->rollBack();
    echo "\n\nTransaction rolled back (test data not saved)\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
