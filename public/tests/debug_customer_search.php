<?php
require_once __DIR__ . '/../../includes/db.php';

echo "=== Testing Customers Search SQL ===\n\n";

$_GET['search'] = 'test';
$search = trim($_GET['search'] ?? '');
$city = trim($_GET['city'] ?? '');
$state = trim($_GET['state'] ?? '');

$whereConditions = [];
$params = [];

if ($search !== '') {
  $whereConditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE :search OR c.email LIKE :search2 OR c.phone LIKE :search3)";
  $params[':search'] = '%' . $search . '%';
  $params[':search2'] = '%' . $search . '%';
  $params[':search3'] = '%' . $search . '%';
}

if ($city !== '') {
  $whereConditions[] = "c.city LIKE :city";
  $params[':city'] = '%' . $city . '%';
}

if ($state !== '') {
  $whereConditions[] = "c.state = :state";
  $params[':state'] = $state;
}

$whereClause = '';
if (!empty($whereConditions)) {
  $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

$sql = "SELECT c.*, 
         COUNT(DISTINCT v.vehicle_id) as vehicle_count,
         COUNT(DISTINCT w.work_id) as work_count
  FROM customer c
  LEFT JOIN vehicle v ON c.customer_id = v.customer_id
  LEFT JOIN working_details w ON c.customer_id = w.customer_id"
  . $whereClause . "
  GROUP BY c.customer_id
  ORDER BY c.customer_id DESC 
  LIMIT 200";

echo "SQL Query:\n";
echo $sql . "\n\n";

echo "Parameters:\n";
print_r($params);
echo "\n";

try {
    $stmt = $pdo->prepare($sql);
    echo "SQL prepared successfully\n";
    
    $stmt->execute($params);
    echo "Query executed successfully\n";
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Rows returned: " . count($rows) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Error on line: " . $e->getLine() . "\n";
}
