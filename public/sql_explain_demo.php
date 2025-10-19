<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$q = trim($_GET['q'] ?? 'fil'); // default small prefix so index can help when used as prefix
$run = isset($_GET['run']) ? (int)$_GET['run'] : 0;
$prefix = isset($_GET['prefix']) && $_GET['prefix'] === '1';

function explain(PDO $pdo, string $sql, array $params=[]): array {
  $st = $pdo->prepare('EXPLAIN ' . $sql);
  $st->execute($params);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

function timeQuery(PDO $pdo, string $sql, array $params=[]): float {
  $t0 = microtime(true);
  $st = $pdo->prepare($sql);
  $st->execute($params);
  // drain results to ensure full execution
  while ($st->fetch(PDO::FETCH_ASSOC)) {}
  return (microtime(true) - $t0) * 1000.0; // ms
}

// Pattern: substring (non-sargable) or prefix (index-usable)
$sql = 'SELECT product_id, product_name, unit_price FROM product_details WHERE product_name LIKE :p';
$param = [':p' => $prefix ? ($q . '%') : ('%' . $q . '%')];

$beforePlan = $beforeMs = $afterPlan = $afterMs = null;
$indexed = false; $indexError = null;

if ($run === 1) {
  try {
    $beforePlan = explain($pdo, $sql, $param);
    $beforeMs = timeQuery($pdo, $sql, $param);
  } catch (Throwable $t) { $indexError = $t->getMessage(); }
} elseif ($run === 2) {
  try {
    // Create index only if missing
    $chk = $pdo->prepare('SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \"product_details\" AND INDEX_NAME = \"idx_product_name\"');
    $chk->execute();
    $exists = (int)$chk->fetchColumn() > 0;
    if (!$exists) {
      $pdo->exec('CREATE INDEX idx_product_name ON product_details(product_name)');
      $indexed = true;
    } else {
      $indexed = true;
    }
    $afterPlan = explain($pdo, $sql, $param);
    $afterMs = timeQuery($pdo, $sql, $param);
  } catch (Throwable $t) { $indexError = $t->getMessage(); }
}

$pageTitle = 'EXPLAIN Demo: Index Impact';
require __DIR__ . '/header.php';
?>
  <h2>EXPLAIN Demo: Index Impact</h2>
  <p class="muted">Compare query plan and timing before and after creating an index. Pattern: product_name LIKE '%<?= e($q) ?>%'.</p>
  <form method="get" style="margin:.5rem 0;">
    <label>Search term</label>
    <input name="q" value="<?= e($q) ?>" />
    <label style="margin-left:1rem"><input type="checkbox" name="prefix" value="1" <?= $prefix ? 'checked' : '' ?> /> Use prefix match (q%)</label>
    <button type="submit" name="run" value="1">Run Before (no index)</button>
    <button type="submit" name="run" value="2">Run After (with index)</button>
  </form>

  <?php if ($indexError): ?><p class="err">Error: <?= e($indexError) ?></p><?php endif; ?>

  <?php if ($beforePlan !== null): ?>
    <section>
      <h3>Before Index</h3>
  <p>Expected: For substring ("%q%"), full scan (type=ALL), no key. For prefix ("q%"), without index it may still be a full scan.</p>
      <div style="overflow:auto">
        <table class="table">
          <thead>
            <tr><?php foreach (array_keys($beforePlan[0] ?? []) as $k): ?><th><?= e($k) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($beforePlan as $r): ?><tr><?php foreach ($r as $v): ?><td><?= e((string)$v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p><strong>Execution time:</strong> <?= e(number_format((float)$beforeMs,2)) ?> ms</p>
    </section>
  <?php endif; ?>

  <?php if ($afterPlan !== null): ?>
    <section>
      <h3>After Index (idx_product_name)</h3>
  <p>Note: Standard BTREE index on product_name is used for prefix LIKE ("q%") — expect better type (range/ref) and fewer rows. It cannot help with substring ("%q%") patterns.</p>
      <div style="overflow:auto">
        <table class="table">
          <thead>
            <tr><?php foreach (array_keys($afterPlan[0] ?? []) as $k): ?><th><?= e($k) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($afterPlan as $r): ?><tr><?php foreach ($r as $v): ?><td><?= e((string)$v) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p><strong>Execution time:</strong> <?= e(number_format((float)$afterMs,2)) ?> ms</p>
      <p class="muted">Tip: For substring searches, consider FULLTEXT indexes (natural language) or n-gram/Trigram approaches. For prefix searches, a normal index suffices and improves type/rows in EXPLAIN.</p>
    </section>
  <?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
