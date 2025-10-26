<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

// ============================================
// AGGREGATION QUERIES
// ============================================

// 2) Top 5 most expensive jobs this month
$sql2 = "SELECT w.work_id, 
         CONCAT(c.first_name, ' ', c.last_name) AS customer_name, 
         CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle_info, 
         w.total_cost, 
         w.completion_date, 
         CONCAT(m.first_name, ' ', m.last_name) AS mechanic_name 
         FROM working_details w 
         JOIN customer c ON c.customer_id = w.customer_id 
         JOIN vehicle v ON v.vehicle_id = w.vehicle_id 
         JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id 
         WHERE w.status = 'completed' 
         AND w.completion_date IS NOT NULL 
         AND MONTH(w.completion_date) = MONTH(CURDATE())
         AND YEAR(w.completion_date) = YEAR(CURDATE())
         ORDER BY w.total_cost DESC 
         LIMIT 5";
$topExpensiveJobs = $pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);

// 3) Parts usage per product with totals
$sql3 = "SELECT p.product_id, 
         p.product_name, 
         COUNT(*) AS uses_count, 
         SUM(wp.quantity) AS qty_total, 
         ROUND(SUM(wp.line_total), 2) AS revenue_total 
         FROM work_parts wp 
         JOIN product_details p ON p.product_id = wp.product_id 
         GROUP BY p.product_id, p.product_name 
         ORDER BY revenue_total DESC 
         LIMIT 10";
$partsUsage = $pdo->query($sql3)->fetchAll(PDO::FETCH_ASSOC);

// 4) Mechanics workload summary with HAVING filter
$sql4 = "SELECT w.assigned_mechanic_id AS mechanic_id, 
         CONCAT(m.first_name, ' ', m.last_name) AS mechanic_name, 
         COUNT(*) AS jobs, 
         ROUND(AVG(w.total_cost), 2) AS avg_job_cost, 
         ROUND(MAX(w.total_cost), 2) AS max_job_cost 
         FROM working_details w 
         JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id 
         GROUP BY w.assigned_mechanic_id, mechanic_name 
         HAVING COUNT(*) >= 1 
         ORDER BY jobs DESC, mechanic_id ASC 
         LIMIT 10";
$mechanicsWorkload = $pdo->query($sql4)->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// JOIN QUERIES
// ============================================

// 5) Complete work order details (INNER JOIN)
$sql5 = "SELECT w.work_id, 
         CONCAT(c.first_name, ' ', c.last_name) AS customer, 
         CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle, 
         CONCAT(m.first_name, ' ', m.last_name) AS mechanic, 
         s.service_name, 
         w.status, 
         w.total_cost, 
         w.start_date 
         FROM working_details w 
         INNER JOIN customer c ON c.customer_id = w.customer_id 
         INNER JOIN vehicle v ON v.vehicle_id = w.vehicle_id 
         INNER JOIN mechanics m ON m.mechanic_id = w.assigned_mechanic_id 
         INNER JOIN service_details s ON s.service_id = w.service_id 
         ORDER BY w.work_id DESC 
         LIMIT 10";
$workOrderDetails = $pdo->query($sql5)->fetchAll(PDO::FETCH_ASSOC);

// 6) Customers with and without vehicles (LEFT JOIN)
$sql6 = "SELECT c.customer_id, 
         CONCAT(c.first_name, ' ', c.last_name) AS customer_name, 
         c.email, 
         COUNT(v.vehicle_id) AS vehicle_count, 
         CASE WHEN COUNT(v.vehicle_id) = 0 THEN 'No Vehicles' ELSE 'Has Vehicles' END AS status 
         FROM customer c 
         LEFT JOIN vehicle v ON v.customer_id = c.customer_id 
         GROUP BY c.customer_id, c.first_name, c.last_name, c.email 
         ORDER BY vehicle_count DESC, c.customer_id DESC 
         LIMIT 10";
$customersVehicles = $pdo->query($sql6)->fetchAll(PDO::FETCH_ASSOC);

// 7) Products never used in work orders (LEFT JOIN with IS NULL)
$sql7 = "SELECT p.product_id, 
         p.sku, 
         p.product_name, 
         p.stock_qty, 
         p.unit_price 
         FROM product_details p 
         LEFT JOIN work_parts wp ON wp.product_id = p.product_id 
         WHERE wp.product_id IS NULL 
         ORDER BY p.product_id DESC 
         LIMIT 10";
$unusedProducts = $pdo->query($sql7)->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// SET OPERATIONS
// ============================================

// 8) All people (customers UNION mechanics) - Combined contact list
$sql8 = "SELECT CONCAT(first_name, ' ', last_name) AS name, 
         email, 
         phone, 
         'Customer' AS type 
         FROM customer 
         UNION 
         SELECT CONCAT(first_name, ' ', last_name) AS name, 
         email, 
         phone, 
         'Mechanic' AS type 
         FROM mechanics 
         ORDER BY type, name 
         LIMIT 20";
$allPeople = $pdo->query($sql8)->fetchAll(PDO::FETCH_ASSOC);

// 9) Services used vs Services not used (LEFT JOIN with CASE)
$sql9 = "SELECT s.service_id, 
         s.service_name, 
         COUNT(w.work_id) AS times_used, 
         CASE 
           WHEN COUNT(w.work_id) = 0 THEN 'Never Used' 
           WHEN COUNT(w.work_id) < 5 THEN 'Rarely Used' 
           ELSE 'Frequently Used' 
         END AS usage_status 
         FROM service_details s 
         LEFT JOIN working_details w ON w.service_id = s.service_id 
         GROUP BY s.service_id, s.service_name 
         ORDER BY times_used DESC, s.service_id 
         LIMIT 15";
$servicesUsage = $pdo->query($sql9)->fetchAll(PDO::FETCH_ASSOC);

// 10) Active vs Inactive Mechanics (UNION for status comparison)
$sql10 = "SELECT 'Active' AS status, 
          COUNT(*) AS count, 
          ROUND(AVG(hourly_rate), 2) AS avg_hourly_rate 
          FROM mechanics 
          WHERE active = 1 
          UNION 
          SELECT 'Inactive' AS status, 
          COUNT(*) AS count, 
          ROUND(AVG(hourly_rate), 2) AS avg_hourly_rate 
          FROM mechanics 
          WHERE active = 0";
$mechanicsStatus = $pdo->query($sql10)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Reports';
$current_page = 'reports';
require __DIR__ . '/header_modern.php';
?>

<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Reports & Analytics</h1>
        <p class="text-muted">Comprehensive business intelligence using SQL operations</p>
    </div>
</div>

<!-- Info Alert -->
<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>SQL Operations Showcase:</strong> This page demonstrates various SQL operations including 
    <span class="badge bg-primary">Aggregations (GROUP BY, SUM, AVG, COUNT)</span>, 
    <span class="badge bg-success">JOINs (INNER, LEFT)</span>, and 
    <span class="badge bg-warning text-dark">SET Operations (UNION)</span>
</div>

<!-- ============================================ -->
<!-- AGGREGATION REPORTS -->
<!-- ============================================ -->
<div class="mb-4">
    <h3 class="text-primary"><i class="fas fa-calculator me-2"></i>Aggregation Queries</h3>
    <p class="text-muted">Using GROUP BY, SUM, AVG, COUNT, MAX, and HAVING clauses</p>
</div>

<!-- 2. Top Expensive Jobs -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top 5 Most Expensive Jobs This Month</h5>
        <small>SQL: INNER JOINs with WHERE and ORDER BY DESC LIMIT</small>
    </div>
    <div class="card-body">
        <?php if (empty($topExpensiveJobs)): ?>
            <div class="text-center py-3 text-muted">No completed jobs this month</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Work ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Mechanic</th>
                            <th>Total Cost</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topExpensiveJobs as $row): ?>
                        <tr>
                            <td><strong>#<?= e((string)$row['work_id']) ?></strong></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td><?= e($row['vehicle_info']) ?></td>
                            <td><?= e($row['mechanic_name']) ?></td>
                            <td><strong class="text-success">$<?= number_format($row['total_cost'], 2) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($row['completion_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 3. Parts Usage -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Top 10 Most Used Parts</h5>
        <small>SQL: GROUP BY with COUNT, SUM aggregations, ORDER BY revenue</small>
    </div>
    <div class="card-body">
        <?php if (empty($partsUsage)): ?>
            <div class="text-center py-3 text-muted">No parts usage data</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Times Used</th>
                            <th>Total Qty</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partsUsage as $row): ?>
                        <tr>
                            <td><strong>#<?= e((string)$row['product_id']) ?></strong></td>
                            <td><?= e($row['product_name']) ?></td>
                            <td><span class="badge bg-info"><?= e((string)$row['uses_count']) ?></span></td>
                            <td><?= e((string)$row['qty_total']) ?></td>
                            <td><strong class="text-success">$<?= number_format($row['revenue_total'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 4. Mechanics Workload -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i>Mechanics Workload Summary</h5>
        <small>SQL: GROUP BY with HAVING (filter groups), AVG, MAX, COUNT</small>
    </div>
    <div class="card-body">
        <?php if (empty($mechanicsWorkload)): ?>
            <div class="text-center py-3 text-muted">No mechanic data</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Mechanic ID</th>
                            <th>Mechanic Name</th>
                            <th>Total Jobs</th>
                            <th>Avg Job Cost</th>
                            <th>Max Job Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mechanicsWorkload as $row): ?>
                        <tr>
                            <td><strong>#<?= e((string)$row['mechanic_id']) ?></strong></td>
                            <td><?= e($row['mechanic_name']) ?></td>
                            <td><span class="badge bg-primary"><?= e((string)$row['jobs']) ?></span></td>
                            <td>$<?= number_format($row['avg_job_cost'], 2) ?></td>
                            <td><strong class="text-success">$<?= number_format($row['max_job_cost'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================ -->
<!-- JOIN REPORTS -->
<!-- ============================================ -->
<div class="mb-4 mt-5">
    <h3 class="text-success"><i class="fas fa-link me-2"></i>JOIN Queries</h3>
    <p class="text-muted">Using INNER JOIN, LEFT JOIN, and JOIN with multiple tables</p>
</div>

<!-- 5. Work Order Details (INNER JOIN) -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Recent Work Orders (Complete Details)</h5>
        <small>SQL: Multiple INNER JOINs across 5 tables</small>
    </div>
    <div class="card-body">
        <?php if (empty($workOrderDetails)): ?>
            <div class="text-center py-3 text-muted">No work orders</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Mechanic</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Cost</th>
                            <th>Start Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workOrderDetails as $row): ?>
                        <tr>
                            <td><strong>#<?= e((string)$row['work_id']) ?></strong></td>
                            <td><?= e($row['customer']) ?></td>
                            <td><?= e($row['vehicle']) ?></td>
                            <td><?= e($row['mechanic']) ?></td>
                            <td><?= e($row['service_name']) ?></td>
                            <td>
                                <?php
                                $statusClass = 'secondary';
                                switch ($row['status']) {
                                    case 'completed': $statusClass = 'success'; break;
                                    case 'in_progress': $statusClass = 'warning'; break;
                                    case 'pending': $statusClass = 'info'; break;
                                }
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= e(ucfirst($row['status'])) ?></span>
                            </td>
                            <td>$<?= number_format($row['total_cost'], 2) ?></td>
                            <td><?= date('M d', strtotime($row['start_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 6. Customers with Vehicles (LEFT JOIN) -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Customers & Their Vehicles</h5>
        <small>SQL: LEFT JOIN with GROUP BY and COUNT (shows customers with 0 vehicles too)</small>
    </div>
    <div class="card-body">
        <?php if (empty($customersVehicles)): ?>
            <div class="text-center py-3 text-muted">No customers</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Vehicle Count</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customersVehicles as $row): ?>
                        <tr>
                            <td><strong>#<?= e((string)$row['customer_id']) ?></strong></td>
                            <td><?= e($row['customer_name']) ?></td>
                            <td><?= e($row['email']) ?></td>
                            <td><span class="badge bg-primary"><?= e((string)$row['vehicle_count']) ?></span></td>
                            <td>
                                <?php if ($row['status'] === 'Has Vehicles'): ?>
                                    <span class="badge bg-success"><?= e($row['status']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= e($row['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 7. Unused Products (LEFT JOIN with NULL check) -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-box-open me-2"></i>Products Never Used in Work Orders</h5>
        <small>SQL: LEFT JOIN with WHERE IS NULL (finds products with no matches)</small>
    </div>
    <div class="card-body">
        <?php if (empty($unusedProducts)): ?>
            <div class="text-center py-3 text-success">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <p>Great! All products have been used at least once.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product ID</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Stock Qty</th>
                            <th>Unit Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unusedProducts as $row): ?>
                        <tr>
                            <td><strong>#<?= e((string)$row['product_id']) ?></strong></td>
                            <td><code><?= e($row['sku']) ?></code></td>
                            <td><?= e($row['product_name']) ?></td>
                            <td><?= e((string)$row['stock_qty']) ?></td>
                            <td>$<?= number_format($row['unit_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================ -->
<!-- SET OPERATIONS REPORTS -->
<!-- ============================================ -->
<div class="mb-4 mt-5">
    <h3 class="text-warning"><i class="fas fa-layer-group me-2"></i>SET Operations</h3>
    <p class="text-muted">Using UNION to combine result sets from multiple queries</p>
</div>

<!-- 8. All People (UNION) -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-address-book me-2"></i>Combined Contact List (Customers + Mechanics)</h5>
        <small>SQL: UNION to combine customers and mechanics into single result set</small>
    </div>
    <div class="card-body">
        <?php if (empty($allPeople)): ?>
            <div class="text-center py-3 text-muted">No contacts</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allPeople as $row): ?>
                        <tr>
                            <td><strong><?= e($row['name']) ?></strong></td>
                            <td><?= e($row['email']) ?></td>
                            <td><?= e($row['phone'] ?: 'N/A') ?></td>
                            <td>
                                <?php if ($row['type'] === 'Customer'): ?>
                                    <span class="badge bg-primary"><?= e($row['type']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-info"><?= e($row['type']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 9. Services Usage Status -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Services Usage Analysis</h5>
        <small>SQL: LEFT JOIN with GROUP BY and CASE for categorization</small>
    </div>
    <div class="card-body">
        <?php if (empty($servicesUsage)): ?>
            <div class="text-center py-3 text-muted">No services</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Service ID</th>
                            <th>Service Name</th>
                            <th>Times Used</th>
                            <th>Usage Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicesUsage as $row): ?>
                        <tr>
                            <td><strong>#<?= e((string)$row['service_id']) ?></strong></td>
                            <td><?= e($row['service_name']) ?></td>
                            <td><span class="badge bg-primary"><?= e((string)$row['times_used']) ?></span></td>
                            <td>
                                <?php
                                $statusClass = 'secondary';
                                if ($row['usage_status'] === 'Frequently Used') $statusClass = 'success';
                                elseif ($row['usage_status'] === 'Rarely Used') $statusClass = 'warning';
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= e($row['usage_status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 10. Mechanics Status Summary (UNION) -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>Active vs Inactive Mechanics Summary</h5>
        <small>SQL: UNION to combine active and inactive mechanic statistics</small>
    </div>
    <div class="card-body">
        <?php if (empty($mechanicsStatus)): ?>
            <div class="text-center py-3 text-muted">No mechanics data</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Avg Hourly Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mechanicsStatus as $row): ?>
                        <tr>
                            <td>
                                <?php if ($row['status'] === 'Active'): ?>
                                    <span class="badge bg-success"><?= e($row['status']) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= e($row['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= e((string)$row['count']) ?></strong></td>
                            <td>$<?= number_format($row['avg_hourly_rate'], 2) ?>/hr</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Info -->
<div class="alert alert-success">
    <i class="fas fa-graduation-cap me-2"></i>
    <strong>SQL Operations Demonstrated:</strong>
    <ul class="mb-0 mt-2">
        <li><strong>Aggregations:</strong> GROUP BY, COUNT, SUM, AVG, MAX, MIN, HAVING</li>
        <li><strong>JOINs:</strong> INNER JOIN (multiple tables), LEFT JOIN, LEFT JOIN with IS NULL</li>
        <li><strong>SET Operations:</strong> UNION (combining different tables)</li>
        <li><strong>Advanced:</strong> CASE statements, DATE functions, subqueries</li>
    </ul>
</div>

<?php require __DIR__ . '/footer_modern.php'; ?>