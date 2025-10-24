<?php
require_once __DIR__ . '/../../includes/db.php';

echo "=== Checking Work Orders Database ===\n\n";

try {
    // Check total count
    $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM working_details');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total work orders in database: " . $row['cnt'] . "\n\n";
    
    // Get latest work orders
    $stmt = $pdo->query('SELECT work_id, customer_id, vehicle_id, assigned_mechanic_id, service_id, status, start_date, total_cost FROM working_details ORDER BY work_id DESC LIMIT 10');
    echo "Latest 10 work orders:\n";
    echo str_repeat("-", 80) . "\n";
    
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$r['work_id']} | Customer: {$r['customer_id']} | Vehicle: {$r['vehicle_id']} | Mechanic: {$r['assigned_mechanic_id']} | Service: {$r['service_id']} | Status: {$r['status']} | Date: {$r['start_date']} | Cost: \${$r['total_cost']}\n";
    }
    
    // Check if auto-increment is working
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'working_details'");
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Auto_increment value: " . $tableInfo['Auto_increment'] . "\n";
    
    // Test INSERT capability
    echo "\n=== Testing INSERT (will rollback) ===\n";
    $pdo->beginTransaction();
    
    $testStmt = $pdo->prepare('INSERT INTO working_details (customer_id,vehicle_id,assigned_mechanic_id,service_id,status,labor_cost,parts_cost,total_cost,start_date,notes) VALUES (1,1,1,1,"pending",100.00,0.00,100.00,CURDATE(),"Test insert")');
    $result = $testStmt->execute();
    
    echo "INSERT result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    echo "Last insert ID: " . $pdo->lastInsertId() . "\n";
    echo "Rows affected: " . $testStmt->rowCount() . "\n";
    
    $pdo->rollBack();
    echo "Transaction rolled back (no changes made)\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}

echo "\n=== Done ===\n";
