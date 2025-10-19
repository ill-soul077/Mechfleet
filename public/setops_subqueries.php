<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$sqlFile = __DIR__ . '/../sql/queries/queries_set_ops.sql';
$content = file_get_contents($sqlFile);

// Split queries by blank line separators where each example is documented
$sections = [];
{
  $chunks = preg_split('/\n\s*\n/s', $content);
  $bufSql = '';
  $bufComment = [];
  foreach ($chunks as $chunk) {
    $trim = trim($chunk);
    if ($trim === '') continue;
    if (str_starts_with($trim, '--')) {
      $bufComment[] = $trim;
    } else {
      $bufSql = $trim;
      if ($bufSql !== '') {
        $sections[] = [
          'comment' => implode("\n", $bufComment),
          'sql' => $bufSql
        ];
      }
      $bufComment = [];
      $bufSql = '';
    }
  }
}

$pageTitle = 'Set Operations & Subqueries';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2>Set Operations & Subqueries</h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <p class="muted">Examples of UNION/UNION ALL, simulated INTERSECT and EXCEPT, and a correlated subquery. Each block shows comments, SQL, and results.</p>

  <?php foreach ($sections as $i => $s): ?>
    <?php if (!$s['sql']) continue; ?>
    <section style="margin:1rem 0;">
      <h3>Example <?= $i+1 ?></h3>
      <?php if (!empty($s['comment'])): ?>
        <pre style="background:#f8f9fa;border:1px solid #ddd;padding:.75rem;white-space:pre-wrap;word-break:break-word;"><?= e($s['comment']) ?></pre>
      <?php endif; ?>
      <details open>
        <summary>Show SQL</summary>
        <pre style="background:#eef5ff;border:1px solid #b6d4fe;padding:.75rem;white-space:pre-wrap;word-break:break-word;"><?= e($s['sql']) ?></pre>
      </details>
      <?php
        $rows = [];
        try {
          $st = $pdo->query($s['sql']);
          $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $t) {
          echo '<p class="err">Error: '.e($t->getMessage()).'</p>';
        }
      ?>
      <?php if (empty($rows)): ?>
        <p class="muted">No data.</p>
      <?php else: ?>
        <div style="overflow:auto;">
          <table class="table">
            <thead><tr><?php foreach (array_keys($rows[0]) as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
              <?php foreach ($rows as $r): ?><tr><?php foreach ($r as $v): ?><td><?= e((string)$v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>
