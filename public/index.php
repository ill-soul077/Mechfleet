<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';

// Require login
auth_require_login();

$pageTitle = 'Dashboard';
$current_page = 'index';

// Fetch dashboard statistics
try {
    // Total Customers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customer");
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active Work Orders (not completed)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM working_details WHERE status != 'completed'");
    $activeWorkOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Revenue (this month)
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
    $monthlyRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Mechanics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mechanics WHERE active = 1");
    $totalMechanics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent Work Orders
    $stmt = $pdo->query("
        SELECT 
            w.work_id,
            w.start_date,
            w.status,
            w.total_cost,
            CONCAT(c.first_name, ' ', c.last_name) as customer_name,
            CONCAT(v.year, ' ', v.make, ' ', v.model) as vehicle_name,
            v.vin as vehicle_no
        FROM working_details w
        JOIN customer c ON w.customer_id = c.customer_id
        JOIN vehicle v ON w.vehicle_id = v.vehicle_id
        ORDER BY w.work_id DESC
        LIMIT 10
    ");
    $recentWorkOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Failed to fetch dashboard data: ' . $e->getMessage();
    $totalCustomers = $activeWorkOrders = $monthlyRevenue = $totalMechanics = 0;
    $recentWorkOrders = [];
}

require __DIR__ . '/header_modern.php';
?>

<!-- Dashboard Content -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Dashboard</h1>
        <p class="text-muted">Welcome to Mechfleet Management System</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="location.href='work_orders.php'">
            <i class="fas fa-plus me-2"></i>New Work Order
        </button>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="mf-metric-card">
            <div class="mf-metric-icon mf-metric-icon-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="mf-metric-content">
                <div class="mf-metric-label">Total Customers</div>
                <div class="mf-metric-value"><?= number_format($totalCustomers) ?></div>
                <div class="mf-metric-change text-success">
                    <i class="fas fa-arrow-up"></i> Active accounts
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="mf-metric-card">
            <div class="mf-metric-icon mf-metric-icon-warning">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="mf-metric-content">
                <div class="mf-metric-label">Active Work Orders</div>
                <div class="mf-metric-value"><?= number_format($activeWorkOrders) ?></div>
                <div class="mf-metric-change text-warning">
                    <i class="fas fa-hourglass-half"></i> In progress
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="mf-metric-card">
            <div class="mf-metric-icon mf-metric-icon-success">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="mf-metric-content">
                <div class="mf-metric-label">Monthly Revenue</div>
                <div class="mf-metric-value">$<?= number_format($monthlyRevenue, 2) ?></div>
                <div class="mf-metric-change text-success">
                    <i class="fas fa-arrow-up"></i> This month
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="mf-metric-card">
            <div class="mf-metric-icon mf-metric-icon-info">
                <i class="fas fa-wrench"></i>
            </div>
            <div class="mf-metric-content">
                <div class="mf-metric-label">Total Mechanics</div>
                <div class="mf-metric-value"><?= number_format($totalMechanics) ?></div>
                <div class="mf-metric-change text-info">
                    <i class="fas fa-user-check"></i> Available
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Work Orders</h5>
                <a href="work_orders.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentWorkOrders)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No work orders yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Work ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Total Cost</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentWorkOrders as $order): ?>
                                <tr>
                                    <td><strong>#<?= e($order['work_id']) ?></strong></td>
                                    <td><?= date('M d, Y', strtotime($order['start_date'])) ?></td>
                                    <td><?= e($order['customer_name']) ?></td>
                                    <td>
                                        <?= e($order['vehicle_name']) ?>
                                        <small class="text-muted d-block"><?= e($order['vehicle_no']) ?></small>
                                    </td>
                                    <td>$<?= number_format($order['total_cost'], 2) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'secondary';
                                        switch (strtolower($order['status'])) {
                                            case 'completed': $statusClass = 'success'; break;
                                            case 'in_progress': $statusClass = 'warning'; break;
                                            case 'pending': $statusClass = 'info'; break;
                                            case 'cancelled': $statusClass = 'danger'; break;
                                        }
                                        ?>
                                        <span class="mf-badge mf-badge-<?= $statusClass ?>">
                                            <?= e(ucfirst($order['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer_modern.php'; ?>
