<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
$pageTitle = 'Home';
require __DIR__ . '/header.php';
?>

  <h2>Welcome</h2>
  <p>Raw PHP (no frameworks) with PDO + MySQL 8.x.</p>

  <div class="card" style="margin-bottom:1rem;">
    <h3>Environment</h3>
    <ul>
      <li>PHP: <strong><?= e(PHP_VERSION) ?></strong></li>
      <li>PDO: <?= class_exists('PDO') ? '<span class="ok">available</span>' : '<span class="err">missing</span>' ?></li>
      <li>DB: Connected to <code><?= e(DB_NAME) ?></code> on <code><?= e(DB_HOST) ?></code></li>
    </ul>
  </div>

  <div class="card" style="margin-bottom:1rem;">
    <h3>Pages</h3>
    <ul>
      <li><a href="sql_demos.php">SQL Demo Runner</a> — paste and run <code>SELECT</code>/<code>EXPLAIN</code> only</li>
    </ul>
  </div>

  <div class="card">
    <h3>SQL Files</h3>
    <ul>
      <li><code>sql/ddl.sql</code> — schema definitions</li>
      <li><code>sql/dml_seed.sql</code> — seed data</li>
      <li><code>sql/queries/*.sql</code> — example queries</li>
    </ul>
    <p class="muted">Tip: Set environment variables in Apache VirtualHost or system to override DB defaults.</p>
  </div>

<?php require __DIR__ . '/footer.php'; ?>
