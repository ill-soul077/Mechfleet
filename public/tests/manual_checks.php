<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/util.php';

// Optional: basic gate - only show when DEV_MODE or logged-in manager
if (defined('DEV_MODE') && !DEV_MODE) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$checks = [
    'Total customers' => 'SELECT COUNT(*) AS cnt FROM customer',
    "Work orders in progress" => "SELECT COUNT(*) AS cnt FROM working_details WHERE status='in_progress'",
    'Income last 30 days' => "SELECT COALESCE(SUM(amount),0) AS amt FROM income WHERE paid_on >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
];

function runCount(PDO $pdo, string $sql): array {
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: [];
}

$results = [];
foreach ($checks as $label => $sql) {
    try {
        $results[] = [
            'label' => $label,
            'sql' => $sql,
            'result' => runCount($pdo, $sql),
        ];
    } catch (Throwable $e) {
        $results[] = [
            'label' => $label,
            'sql' => $sql,
            'error' => $e->getMessage(),
        ];
    }
}

$pageTitle = 'Manual SQL Checks';
include __DIR__ . '/../../public/header.php';
?>
<div class="card">
  <h2>Manual SQL Checks</h2>
  <p class="muted">Use this page to capture screenshot proof for the seed data and recent income. After running the refresh scripts, take a screenshot of this table showing the counts.</p>
  <ul>
    <li>Run scripts/refresh_db.bat (Windows) or scripts/refresh_db.sh (Linux/mac) first.</li>
    <li>Reload this page and take a screenshot including the timestamp and results.</li>
  </ul>

  <table class="table" style="margin-top:1rem">
    <thead><tr><th>Check</th><th>SQL</th><th>Result</th></tr></thead>
    <tbody>
      <?php foreach ($results as $r): ?>
        <tr>
          <td><?= e($r['label']) ?></td>
          <td><code><?= e($r['sql']) ?></code></td>
          <td>
            <?php if (isset($r['error'])): ?>
              <span class="err">Error: <?= e($r['error']) ?></span>
            <?php else: ?>
              <?php if (isset($r['result']['cnt'])): ?>
                <strong><?= (int)$r['result']['cnt'] ?></strong>
              <?php elseif (isset($r['result']['amt'])): ?>
                <strong><?= number_format((float)$r['result']['amt'], 2) ?></strong>
              <?php else: ?>
                <em class="muted">No rows</em>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="muted" style="margin-top:.5rem">Timestamp: <?= e(gmdate('Y-m-d H:i:s')) ?>Z</p>
</div>
<?php include __DIR__ . '/../../public/footer.php';
