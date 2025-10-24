<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

// Load complex report SQL file
$sqlFile = __DIR__ . '/../sql/queries/queries_complex_reports.sql';
$all = file_get_contents($sqlFile);

// Extract named SQL blocks
preg_match_all('/--\s*BEGIN_SQL:([a-zA-Z0-9_\-]+)\s*(.*?)--\s*END_SQL/s', $all, $matches, PREG_SET_ORDER);
$blocks = [];
foreach ($matches as $m) {
  $name = $m[1];
  $sql = trim($m[2]);
  $blocks[$name] = $sql;
}

// Define order and labels
$order = [
  'top_expensive_jobs' => ['title' => 'Top 5 Expensive Jobs This Month', 'icon' => 'fa-chart-line', 'color' => 'primary'],
  'mechanic_avg_completion_rank' => ['title' => 'Mechanic Ranking by Avg Completion Time', 'icon' => 'fa-users-cog', 'color' => 'success'],
  'monthly_revenue_change' => ['title' => 'Monthly Revenue with % Change', 'icon' => 'fa-dollar-sign', 'color' => 'info'],
  'loyal_customers' => ['title' => 'Loyal Customers (3+ Visits in 12 Months)', 'icon' => 'fa-star', 'color' => 'warning'],
];

// Execute each report
$results = [];
foreach ($order as $key => $config) {
  if (!isset($blocks[$key])) continue;
  $sql = $blocks[$key];
  $st = $pdo->query($sql);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $results[] = ['key'=>$key, 'config'=>$config, 'sql'=>$sql, 'rows'=>$rows];
}

$pageTitle = 'Reports';
$current_page = 'reports';
require __DIR__ . '/header_modern.php';
?>

<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Reports & Analytics</h1>
        <p class="text-muted">Business intelligence and performance metrics</p>
    </div>
</div>

<?php foreach ($results as $r): ?>
<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas <?= $r['config']['icon'] ?> me-2 text-<?= $r['config']['color'] ?>"></i>
                <?= e($r['config']['title']) ?>
            </h5>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#sql-<?= $r['key'] ?>">
                <i class="fas fa-code me-1"></i>View SQL
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="collapse mb-3" id="sql-<?= $r['key'] ?>">
            <div class="alert alert-secondary">
                <strong>SQL Query:</strong>
                <pre class="mb-0 mt-2" style="background:#f8f9fa;border:1px solid #ddd;padding:0.75rem;white-space:pre-wrap;word-break:break-word;"><code><?= e($r['sql']) ?></code></pre>
            </div>
        </div>
        
        <?php if (empty($r['rows'])): ?>
            <div class="text-center py-3 text-muted">
                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                <p>No data available for this report</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <?php foreach (array_keys($r['rows'][0]) as $col): ?>
                                <th><?= e(ucwords(str_replace('_', ' ', $col))) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($r['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $colName => $val): ?>
                                    <td>
                                        <?php if (is_numeric($val) && strpos($colName, 'revenue') !== false || strpos($colName, 'total') !== false || strpos($colName, 'cost') !== false || strpos($colName, 'amount') !== false): ?>
                                            <strong>$<?= number_format((float)$val, 2) ?></strong>
                                        <?php elseif (strpos($colName, 'pct') !== false || strpos($colName, 'percent') !== false || strpos($colName, 'change') !== false): ?>
                                            <?php 
                                            $numVal = (float)$val;
                                            $badgeClass = $numVal >= 0 ? 'success' : 'danger';
                                            $icon = $numVal >= 0 ? 'arrow-up' : 'arrow-down';
                                            ?>
                                            <span class="mf-badge mf-badge-<?= $badgeClass ?>">
                                                <i class="fas fa-<?= $icon ?> me-1"></i><?= number_format(abs($numVal), 2) ?>%
                                            </span>
                                        <?php else: ?>
                                            <?= e((string)$val) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Note:</strong> These reports use complex SQL queries for transparency and educational purposes. 
    Click "View SQL" to see the underlying query for each report.
</div>

<?php require __DIR__ . '/footer_modern.php'; ?>
