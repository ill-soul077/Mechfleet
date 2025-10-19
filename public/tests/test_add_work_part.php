<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/business.php';

header('Content-Type: text/plain');

echo "== Test addWorkPart success ==\n";
try {
  // Pick the latest work, product with stock
  $w = $pdo->query('SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 1')->fetchColumn();
  $pRow = $pdo->query('SELECT product_id, stock_qty FROM product_details WHERE stock_qty >= 2 ORDER BY product_id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
  if (!$w || !$pRow) { echo "Setup failed: need a work and a product with stock.\n"; exit; }
  $pid = (int)$pRow['product_id'];
  $before = (int)$pRow['stock_qty'];
  addWorkPart($pdo, (int)$w, $pid, 2, false);
  $after = (int)$pdo->query('SELECT stock_qty FROM product_details WHERE product_id='.(int)$pid)->fetchColumn();
  echo "Stock before: $before, after: $after (expected: before-2)\n";
} catch (Throwable $t) {
  echo "Unexpected error: ".$t->getMessage()."\n";
}

echo "\n== Test addWorkPart rollback (insufficient stock) ==\n";
try {
  $w = $pdo->query('SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 1')->fetchColumn();
  $pRow = $pdo->query('SELECT product_id, stock_qty FROM product_details ORDER BY stock_qty ASC, product_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
  $pid = (int)$pRow['product_id'];
  $have = (int)$pRow['stock_qty'];
  $qty = $have + 999; // force insufficient stock
  try {
    addWorkPart($pdo, (int)$w, $pid, $qty, false);
    echo "ERROR: expected rollback due to insufficient stock, but succeeded.\n";
  } catch (Throwable $e) {
    echo "Caught expected error: ".$e->getMessage()."\n";
  }
  $after = (int)$pdo->query('SELECT stock_qty FROM product_details WHERE product_id='.(int)$pid)->fetchColumn();
  echo "Stock remained: $after (expected unchanged: $have)\n";
} catch (Throwable $t) {
  echo "Unexpected error: ".$t->getMessage()."\n";
}
