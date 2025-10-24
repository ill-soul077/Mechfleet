<?php
require_once __DIR__ . '/../../includes/db.php';

echo "=== Testing Vehicles Search SQL ===\n\n";

$_GET['search'] = 'test';
$search = trim($_GET['search'] ?? '');
$make = trim($_GET['make'] ?? '');
$year = trim($_GET['year'] ?? '');

$whereConditions = [];
$params = [];

if ($search !== '') {
  $whereConditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE :search OR v.vin LIKE :search2 OR v.license_plate LIKE :search3 OR v.model LIKE :search4)";
  $params[':search'] = '%' . $search . '%';
  $params[':search2'] = '%' . $search . '%';
  $params[':search3'] = '%' . $search . '%';
  $params[':search4'] = '%' . $search . '%';
}

if ($make !== '') {
  $whereConditions[] = "v.make LIKE :make";
  $params[':make'] = '%' . $make . '%';
}

if ($year !== '') {
  $whereConditions[] = "v.year = :year";
  $params[':year'] = $year;
}

$whereClause = '';
if (!empty($whereConditions)) {
  $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

$sql = "SELECT v.*, 
         CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
         COUNT(w.work_id) as work_count
  FROM vehicle v 
  JOIN customer c ON c.customer_id = v.customer_id 
  LEFT JOIN working_details w ON v.vehicle_id = w.vehicle_id"
  . $whereClause . "
  GROUP BY v.vehicle_id
  ORDER BY v.vehicle_id DESC 
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
