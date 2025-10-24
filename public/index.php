<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';

$pageTitle = 'Dashboard';
$current_page = 'index';

// Fetch dashboard statistics
try {
    // Total Customers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customer");
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active Work Orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM working_details WHERE status != 'Completed'");
    $activeWorkOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Revenue (this month)
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM income WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
    $monthlyRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Mechanics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mechanics");
    $totalMechanics = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent Work Orders
    $stmt = $pdo->query("
        SELECT 
            w.work_id,
            w.date,
            w.status,
            w.total_cost,
            c.customer_name,
            v.vehicle_name,
            v.vehicle_no
        FROM working_details w
        LEFT JOIN customer c ON w.customer_id = c.customer_id
        LEFT JOIN vehicle v ON w.vehicle_id = v.vehicle_id
        ORDER BY w.date DESC
        LIMIT 10
    ");
    $recentWorkOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Revenue by month (last 6 months)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date, '%b') as month,
            COALESCE(SUM(amount), 0) as revenue
        FROM income
        WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY YEAR(date), MONTH(date)
        ORDER BY date ASC
    ");
    $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Work order status distribution
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM working_details
        GROUP BY status
    ");
    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Failed to fetch dashboard data: ' . $e->getMessage();
    $totalCustomers = $activeWorkOrders = $monthlyRevenue = $totalMechanics = 0;
    $recentWorkOrders = [];
    $revenueData = [];
    $statusData = [];
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

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Revenue Chart -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Revenue Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Status Distribution Chart -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Work Order Status</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart"></canvas>
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
                                    <td><?= date('M d, Y', strtotime($order['date'])) ?></td>
                                    <td><?= e($order['customer_name']) ?></td>
                                    <td>
                                        <?= e($order['vehicle_name']) ?>
                                        <small class="text-muted d-block"><?= e($order['vehicle_no']) ?></small>
                                    </td>
                                    <td>$<?= number_format($order['total_cost'], 2) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'secondary';
                                        switch ($order['status']) {
                                            case 'Completed': $statusClass = 'success'; break;
                                            case 'In Progress': $statusClass = 'warning'; break;
                                            case 'Pending': $statusClass = 'info'; break;
                                            case 'Cancelled': $statusClass = 'danger'; break;
                                        }
                                        ?>
                                        <span class="mf-badge mf-badge-<?= $statusClass ?>">
                                            <?= e($order['status']) ?>
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

<!-- Chart.js Scripts -->
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($revenueData, 'month')) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_column($revenueData, 'revenue')) ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenue: $' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($statusData, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($statusData, 'count')) ?>,
            backgroundColor: [
                '#27ae60', // Completed - green
                '#f39c12', // In Progress - yellow
                '#3498db', // Pending - blue
                '#e74c3c'  // Cancelled - red
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + ' orders';
                    }
                }
            }
        }
    }
});
</script>

<?php require __DIR__ . '/footer_modern.php'; ?>
