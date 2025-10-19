<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';

auth_require_login();

// Enhance validation to allow SELECT/EXPLAIN/SHOW/WITH (CTEs limited to read-only queries)
function validate_demo_sql_extended(string $sql): array {
    $trimmed = trim($sql);
    if (preg_match('/;\s*\S/m', $trimmed)) {
        return [false, 'Only a single statement is allowed.'];
    }
    if (!preg_match('/^(SELECT|EXPLAIN|SHOW|WITH)\b/i', $trimmed)) {
        return [false, 'Only SELECT/EXPLAIN/SHOW/WITH statements are allowed.'];
    }
    // Block DML/DDL keywords
    $blocked = ['INSERT','UPDATE','DELETE','REPLACE','ALTER','DROP','TRUNCATE','CREATE','GRANT','REVOKE','LOCK','UNLOCK','SET PASSWORD','SHUTDOWN','KILL','USE','CALL','HANDLER','LOAD','INSTALL','UNINSTALL','RESET','PURGE','BACKUP','RESTORE','ANALYZE TABLE','OPTIMIZE TABLE','CHECK TABLE'];
    $upper = strtoupper($trimmed);
    foreach ($blocked as $kw) {
        if (strpos($upper, $kw) !== false) {
            return [false, 'Disallowed keyword detected: ' . $kw];
        }
    }
    return [true, 'OK'];
}

// Collect queries from sql/queries folder and parse comments
$queriesDir = realpath(__DIR__ . '/../sql/queries');
$files = glob($queriesDir . '/*.sql');
natsort($files);

function load_queries_from_file(string $path): array {
    $content = file_get_contents($path);
    $lines = preg_split("/\r?\n/", $content);
    $blocks = [];
    $current = ['comment' => [], 'sql' => []];
    foreach ($lines as $ln) {
        if (preg_match('/^\s*--(.*)$/', $ln, $m)) {
            // Comment line
            if (!empty($current['sql'])) {
                $blocks[] = $current; $current = ['comment' => [], 'sql' => []];
            }
            $current['comment'][] = trim($m[1]);
        } else {
            if (trim($ln) === '') { continue; }
            $current['sql'][] = $ln;
        }
    }
    if (!empty($current['sql']) || !empty($current['comment'])) {
        $blocks[] = $current;
    }
    // Clean up
    foreach ($blocks as &$b) {
        $b['comment'] = implode("\n", array_filter($b['comment']));
        $b['sql'] = trim(implode("\n", $b['sql']));
    }
    return array_values(array_filter($blocks, fn($b) => $b['sql'] !== ''));
}

// Handle actions: run/explain or ad-hoc
$action = $_POST['action'] ?? '';
$fileSel = $_POST['file'] ?? '';
$sqlIndex = (int)($_POST['idx'] ?? -1);
$adhocSql = $_POST['sql'] ?? '';
$rawParams = $_POST['params'] ?? '';

$exec = [ 'ok' => false, 'error' => null, 'elapsed' => 0.0, 'rows' => [], 'columns' => 0 ];
$explain = [ 'rows' => [] ];

try {
    if ($action === 'run' || $action === 'explain') {
        $blocks = load_queries_from_file($fileSel);
        if (!isset($blocks[$sqlIndex])) { throw new RuntimeException('Query not found.'); }
        $sqlToRun = $blocks[$sqlIndex]['sql'];
        [$ok, $msg] = validate_demo_sql_extended($sqlToRun);
        if (!$ok) { throw new RuntimeException($msg); }

        if ($action === 'explain') {
            $stmtE = $pdo->prepare('EXPLAIN ' . $sqlToRun);
            $stmtE->execute();
            $explain['rows'] = $stmtE->fetchAll(PDO::FETCH_ASSOC);
        }

        $start = microtime(true);
        $stmt = $pdo->query($sqlToRun);
        $exec['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $exec['columns'] = $stmt->columnCount();
        $exec['ok'] = true;
        $exec['elapsed'] = (microtime(true) - $start) * 1000.0; // ms
    } elseif ($action === 'adhoc') {
        [$ok, $msg] = validate_demo_sql_extended($adhocSql);
        if (!$ok) { throw new RuntimeException($msg); }
        $params = parse_params($rawParams);
        $start = microtime(true);
        if (preg_match('/^EXPLAIN\b/i', trim($adhocSql))) {
            $stmt = $pdo->prepare($adhocSql);
        } else {
            $stmt = $pdo->prepare($adhocSql);
        }
        foreach ($params as $name => $data) {
            $paramName = str_starts_with($name, ':') ? $name : (":" . $name);
            $stmt->bindValue($paramName, $data['value'], $data['type']);
        }
        $stmt->execute();
        $exec['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $exec['columns'] = $stmt->columnCount();
        $exec['ok'] = true;
        $exec['elapsed'] = (microtime(true) - $start) * 1000.0; // ms
    }
} catch (Throwable $t) {
    $exec['error'] = $t->getMessage();
}

$pageTitle = 'SQL Demos (Manager)';
require __DIR__ . '/header.php';
?>
  <div style="display:flex; justify-content: space-between; align-items:center; gap:1rem">
    <h2>SQL Demos</h2>
    <div>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <p class="muted">Read-only queries only (SELECT/EXPLAIN/SHOW/WITH). For transactional demos, use <code>public/sql_transactions_demo.php</code> in dev only.</p>

  <h3>Prewritten queries</h3>
  <p class="muted">Click Run to execute the query and show results. Click Explain to see EXPLAIN output first.</p>
  <div>
    <?php foreach ($files as $f): $blocks = load_queries_from_file($f); if (empty($blocks)) continue; ?>
      <details style="margin: .75rem 0;">
        <summary><strong><?= e(basename($f)) ?></strong> <span class="muted">(<?= count($blocks) ?> queries)</span></summary>
        <?php foreach ($blocks as $i => $b): ?>
          <div style="border:1px solid #ddd; padding: .5rem; margin:.5rem 0;">
            <?php if (!empty($b['comment'])): ?>
              <pre class="muted" style="white-space: pre-wrap; margin:0 0 .5rem 0;"><?= e($b['comment']) ?></pre>
            <?php endif; ?>
            <pre style="white-space: pre-wrap; margin:0 0 .5rem 0; background:#f8f8f8; padding:.5rem;"><?= e($b['sql']) ?></pre>
            <form method="post" style="display:inline-block; margin-right:.5rem;">
              <input type="hidden" name="action" value="run" />
              <input type="hidden" name="file" value="<?= e($f) ?>" />
              <input type="hidden" name="idx" value="<?= e((string)$i) ?>" />
              <button type="submit">Run</button>
            </form>
            <form method="post" style="display:inline-block;">
              <input type="hidden" name="action" value="explain" />
              <input type="hidden" name="file" value="<?= e($f) ?>" />
              <input type="hidden" name="idx" value="<?= e((string)$i) ?>" />
              <button type="submit">Explain</button>
            </form>
          </div>
        <?php endforeach; ?>
      </details>
    <?php endforeach; ?>
  </div>

  <h3>Ad-hoc query</h3>
  <p class="muted">Paste a single SELECT/EXPLAIN/SHOW/WITH statement. Multiple statements are blocked.</p>
  <form method="post">
    <input type="hidden" name="action" value="adhoc" />
    <label for="sql"><strong>SQL</strong></label><br />
    <textarea id="sql" name="sql" placeholder="SELECT * FROM customer WHERE last_name LIKE 'R%';" rows="6"></textarea>
    <div style="margin-top:.5rem"></div>
    <label for="params"><strong>Params</strong> <span class="muted">(one per line: name=value; use :int:, :float:, :bool: prefixes)</span></label><br />
    <textarea id="params" name="params" placeholder=":id=:int:1&#10;:active=:bool:true" rows="4"></textarea>
    <div style="margin-top:.5rem"></div>
    <button type="submit">Run</button>
  </form>

  <?php if ($exec['error']): ?>
    <p class="err"><strong>Error:</strong> <?= e($exec['error']) ?></p>
  <?php endif; ?>

  <?php if ($exec['ok']): ?>
    <?php if (!empty($explain['rows'])): ?>
      <h3>EXPLAIN</h3>
      <table class="table">
        <thead>
          <tr>
            <?php foreach (array_keys($explain['rows'][0]) as $col): ?>
              <th><?= e((string)$col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($explain['rows'] as $row): ?>
            <tr>
              <?php foreach ($row as $val): ?>
                <td><?= e(is_scalar($val) ? (string)$val : json_encode($val)) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <h3>Result <span class="muted">(<?= number_format($exec['elapsed'], 2) ?> ms)</span></h3>
    <?php if (empty($exec['rows'])): ?>
      <p class="muted">No rows.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <?php foreach (array_keys($exec['rows'][0]) as $col): ?>
              <th><?= e((string)$col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($exec['rows'] as $row): ?>
            <tr>
              <?php foreach ($row as $val): ?>
                <td><?= e(is_scalar($val) ? (string)$val : json_encode($val)) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="muted">Rows: <?= e((string)count($exec['rows'])) ?>, Columns: <?= e((string)$exec['columns']) ?></p>
    <?php endif; ?>
  <?php endif; ?>

  <h3>Security note</h3>
  <p class="muted">This page is restricted to managers and only allows read-only queries. For transactional demos, use a dev-only script <code>public/sql_transactions_demo.php</code> and do not expose it in production.</p>
<?php require __DIR__ . '/footer.php'; ?>
