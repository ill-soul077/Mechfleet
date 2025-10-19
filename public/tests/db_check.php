<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/util.php';

header('Content-Type: text/html; charset=utf-8');
$ok = true; $msg = 'Connected';
try {
    // simple ping
    $row = $pdo->query('SELECT 1 AS ok')->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['ok'] !== 1) { $ok = false; $msg = 'Ping failed'; }
} catch (Throwable $e) {
    $ok = false; $msg = $e->getMessage();
}

?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>DB Check</title>
<link rel="stylesheet" href="../css/style.css"></head>
<body>
<div class="container" style="margin-top:2rem">
  <div class="card">
    <h2>Database Connection Check</h2>
    <p>Status: <?php echo $ok ? '<span class="ok">OK</span>' : '<span class="err">FAIL</span>'; ?></p>
    <p>Message: <code><?php echo e($msg); ?></code></p>
    <h3>Configuration</h3>
    <ul>
      <li>DB_HOST: <code><?php echo e(DB_HOST); ?></code></li>
      <li>DB_PORT: <code><?php echo e(DB_PORT); ?></code></li>
      <li>DB_NAME: <code><?php echo e(DB_NAME); ?></code></li>
      <li>DB_USER: <code><?php echo e(DB_USER); ?></code></li>
      <li>DB_CHARSET: <code><?php echo e(DB_CHARSET); ?></code></li>
    </ul>
    <p class="muted">Edit <code>includes/config.php</code> to change these values.</p>
  </div>
</div>
</body></html>
