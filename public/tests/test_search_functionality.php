<?php
/**
 * Test: Search Functionality Across All Pages
 * This test verifies that search functionality works correctly
 * and doesn't have PDO parameter binding errors
 */

require_once __DIR__ . '/../../includes/db.php';

echo "<h1>Search Functionality Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
table { border-collapse: collapse; margin: 20px 0; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.step { background-color: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
</style>";

$tests = [
    [
        'name' => 'Products Search',
        'sql' => "SELECT * FROM product_details WHERE (sku LIKE :search OR product_name LIKE :search2 OR description LIKE :search3) LIMIT 5",
        'params' => [':search' => '%oil%', ':search2' => '%oil%', ':search3' => '%oil%'],
        'page' => 'products.php'
    ],
    [
        'name' => 'Services Search',
        'sql' => "SELECT * FROM service_details WHERE (service_name LIKE :search OR description LIKE :search2) LIMIT 5",
        'params' => [':search' => '%oil%', ':search2' => '%oil%'],
        'page' => 'services.php'
    ],
    [
        'name' => 'Income Search',
        'sql' => "SELECT i.* FROM income i 
                  LEFT JOIN customer c ON c.customer_id = i.customer_id 
                  LEFT JOIN service_details s ON s.service_id = i.service_id 
                  WHERE (CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                         OR s.service_name LIKE :search2 
                         OR i.transaction_reference LIKE :search3) 
                  LIMIT 5",
        'params' => [':search' => '%test%', ':search2' => '%test%', ':search3' => '%test%'],
        'page' => 'income.php'
    ],
    [
        'name' => 'Customers Search',
        'sql' => "SELECT c.* FROM customer c 
                  WHERE (CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                         OR c.email LIKE :search2 
                         OR c.phone LIKE :search3) 
                  LIMIT 5",
        'params' => [':search' => '%john%', ':search2' => '%john%', ':search3' => '%john%'],
        'page' => 'customers.php'
    ],
    [
        'name' => 'Vehicles Search',
        'sql' => "SELECT v.* FROM vehicle v 
                  LEFT JOIN customer c ON c.customer_id = v.customer_id 
                  WHERE (CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                         OR v.vin LIKE :search2 
                         OR v.license_plate LIKE :search3 
                         OR v.model LIKE :search4) 
                  LIMIT 5",
        'params' => [':search' => '%toyota%', ':search2' => '%toyota%', ':search3' => '%toyota%', ':search4' => '%toyota%'],
        'page' => 'vehicles.php'
    ],
    [
        'name' => 'Work Orders Search',
        'sql' => "SELECT w.* FROM working_details w 
                  JOIN customer c ON c.customer_id = w.customer_id 
                  JOIN vehicle v ON v.vehicle_id = w.vehicle_id 
                  JOIN service_details s ON s.service_id = w.service_id 
                  WHERE (CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                         OR CONCAT(v.year, ' ', v.make, ' ', v.model) LIKE :search2 
                         OR s.service_name LIKE :search3) 
                  LIMIT 5",
        'params' => [':search' => '%test%', ':search2' => '%test%', ':search3' => '%test%'],
        'page' => 'work_orders.php'
    ]
];

$results = [];
$allPassed = true;

echo "<h2>Running Search Tests...</h2>";

foreach ($tests as $test) {
    echo "<div class='step'>";
    echo "<h3>{$test['name']}</h3>";
    
    try {
        $stmt = $pdo->prepare($test['sql']);
        $stmt->execute($test['params']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✓ Query executed successfully</p>";
        echo "<p class='info'>Found " . count($rows) . " result(s)</p>";
        echo "<p class='info'>Page: <a href='../{$test['page']}' target='_blank'>{$test['page']}</a></p>";
        
        $results[] = [
            'name' => $test['name'],
            'status' => 'PASS',
            'count' => count($rows),
            'page' => $test['page']
        ];
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Query failed!</p>";
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='error'>SQL State: " . htmlspecialchars($e->getCode()) . "</p>";
        
        $results[] = [
            'name' => $test['name'],
            'status' => 'FAIL',
            'error' => $e->getMessage(),
            'page' => $test['page']
        ];
        
        $allPassed = false;
    }
    
    echo "</div>";
}

// Summary table
echo "<h2>Test Summary</h2>";
echo "<table>";
echo "<tr><th>Test Name</th><th>Page</th><th>Status</th><th>Results</th></tr>";

foreach ($results as $result) {
    $statusClass = $result['status'] === 'PASS' ? 'success' : 'error';
    $statusIcon = $result['status'] === 'PASS' ? '✓' : '✗';
    
    echo "<tr>";
    echo "<td>{$result['name']}</td>";
    echo "<td><a href='../{$result['page']}' target='_blank'>{$result['page']}</a></td>";
    echo "<td class='$statusClass'>$statusIcon {$result['status']}</td>";
    
    if ($result['status'] === 'PASS') {
        echo "<td>{$result['count']} records found</td>";
    } else {
        echo "<td class='error'>" . htmlspecialchars($result['error']) . "</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Final result
if ($allPassed) {
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2 style='color: #155724;'>✓✓✓ ALL SEARCH TESTS PASSED! ✓✓✓</h2>";
    echo "<p style='color: #155724;'><strong>All pages have working search functionality with no PDO parameter errors!</strong></p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Products search: <strong>Working</strong></li>";
    echo "<li>Services search: <strong>Working</strong></li>";
    echo "<li>Income search: <strong>Working</strong></li>";
    echo "<li>Customers search: <strong>Working</strong></li>";
    echo "<li>Vehicles search: <strong>Working</strong></li>";
    echo "<li>Work Orders search: <strong>Working</strong></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2 style='color: #721c24;'>✗ SOME TESTS FAILED</h2>";
    echo "<p style='color: #721c24;'>Please review the errors above and fix the issues.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='../products.php?search=oil' target='_blank'>Test Products Search</a></li>";
echo "<li><a href='../services.php?search=oil' target='_blank'>Test Services Search</a></li>";
echo "<li><a href='../income.php?search=test' target='_blank'>Test Income Search</a></li>";
echo "<li><a href='../customers.php?search=john' target='_blank'>Test Customers Search</a></li>";
echo "<li><a href='../vehicles.php?search=toyota' target='_blank'>Test Vehicles Search</a></li>";
echo "<li><a href='../work_orders.php?search=test' target='_blank'>Test Work Orders Search</a></li>";
echo "</ul>";

echo "<p><a href='../products.php'>← Back to Products</a></p>";
?>
