<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

// Load complex report SQL file
$sqlFile = __DIR__ . '/../sql/queries/queries_complex_reports.sql';
$all = file_get_contents($sqlFile);

// Extract named SQL blocks using markers -- BEGIN_SQL:name ... -- END_SQL
preg_match_all('/--\s*BEGIN_SQL:([a-zA-Z0-9_\-]+)\s*(.*?)--\s*END_SQL/s', $all, $matches, PREG_SET_ORDER);
$blocks = [];
foreach ($matches as $m) {
  $name = $m[1];
  $sql = trim($m[2]);
  $blocks[$name] = $sql;
}

// Define order and labels
$order = [
  'top_expensive_jobs' => 'Top 5 expensive jobs this month',
  'mechanic_avg_completion_rank' => 'Mechanic ranking by avg completion time',
  'monthly_revenue_change' => 'Monthly revenue with % change vs prior month',
  'loyal_customers' => 'Customers > 3 visits in last 12 months',
];

// Execute each report
$results = [];
foreach ($order as $key => $label) {
  if (!isset($blocks[$key])) continue;
  $sql = $blocks[$key];
  $st = $pdo->query($sql);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  $results[] = ['key'=>$key, 'label'=>$label, 'sql'=>$sql, 'rows'=>$rows];
}

$pageTitle = 'Manager Reports';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2>Manager Reports</h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <p class="muted">These reports show raw SQL and results for transparency.</p>

  <?php foreach ($results as $r): ?>
    <section style="margin:1rem 0;">
      <h3><?= e($r['label']) ?></h3>
      <details open>
        <summary>Show SQL</summary>
        <pre style="background:#f8f9fa;border:1px solid #ddd;padding:.75rem;white-space:pre-wrap;word-break:break-word;"><?= e($r['sql']) ?></pre>
      </details>
      <?php if (empty($r['rows'])): ?>
        <p class="muted">No data.</p>
      <?php else: ?>
        <div style="overflow:auto;">
          <table class="table">
            <thead><tr>
              <?php foreach (array_keys($r['rows'][0]) as $col): ?>
                <th><?= e($col) ?></th>
              <?php endforeach; ?>
            </tr></thead>
            <tbody>
              <?php foreach ($r['rows'] as $row): ?>
                <tr>
                  <?php foreach ($row as $val): ?>
                    <td><?= e((string)$val) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>
