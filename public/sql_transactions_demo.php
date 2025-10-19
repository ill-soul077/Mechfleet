<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';

if (!DEV_MODE) {
  http_response_code(404);
  exit('Not available in production');
}

auth_require_login();

function q($v): string {
  if ($v === null) return 'NULL';
  if (is_numeric($v)) return (string)$v;
  return "'" . str_replace("'", "''", (string)$v) . "'";
}

$logSuccess = [];
$logRollback = [];
$ranSuccess = false;
$ranRollback = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $scenario = $_POST['scenario'] ?? '';

  if ($scenario === 'success') {
    $ranSuccess = true;
    try {
      // Pick a work and a product not yet used on that work, with enough stock
      $work_id = (int)$pdo->query('SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 1')->fetchColumn();
      $ps = $pdo->prepare('SELECT p.product_id, p.unit_price, p.stock_qty FROM product_details p WHERE p.stock_qty >= 2 AND NOT EXISTS (SELECT 1 FROM work_parts wp WHERE wp.work_id = :w AND wp.product_id = p.product_id) ORDER BY p.product_id DESC LIMIT 1');
      $ps->execute([':w'=>$work_id]);
      $prod = $ps->fetch(PDO::FETCH_ASSOC);
      if (!$work_id || !$prod) {
        $logSuccess[] = 'Setup error: Need a work order and a product with sufficient stock not already on the work.';
      } else {
        $product_id = (int)$prod['product_id'];
        $qty = 2;
        $logSuccess[] = '-- Successful transaction scenario';
        $logSuccess[] = 'BEGIN;';
        $pdo->beginTransaction();

        $sql1 = 'SELECT unit_price, stock_qty FROM product_details WHERE product_id = '.q($product_id).' FOR UPDATE';
        $st1 = $pdo->prepare('SELECT unit_price, stock_qty FROM product_details WHERE product_id = :pid FOR UPDATE');
        $st1->execute([':pid'=>$product_id]);
        $row1 = $st1->fetch(PDO::FETCH_ASSOC);
        $logSuccess[] = $sql1 . ' -- => unit_price='.$row1['unit_price'].', stock_qty='.$row1['stock_qty'];

        $unit = (float)$row1['unit_price'];
        $line = round($unit * $qty, 2);
        $sql2 = 'INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total) VALUES ('.q($work_id).', '.q($product_id).', '.q($qty).', '.q($unit).', '.q($line).')';
        $ins = $pdo->prepare('INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total) VALUES (:w,:p,:q,:u,:t)');
        $ins->execute([':w'=>$work_id, ':p'=>$product_id, ':q'=>$qty, ':u'=>$unit, ':t'=>$line]);
        $logSuccess[] = $sql2 . ' -- OK';

        $sql3 = 'UPDATE product_details SET stock_qty = stock_qty - '.q($qty).' WHERE product_id = '.q($product_id);
        $up = $pdo->prepare('UPDATE product_details SET stock_qty = stock_qty - :q WHERE product_id = :p');
        $up->execute([':q'=>$qty, ':p'=>$product_id]);
        $logSuccess[] = $sql3 . ' -- OK';

        $pdo->commit();
        $logSuccess[] = 'COMMIT; -- OK';

        $final = $pdo->prepare('SELECT stock_qty FROM product_details WHERE product_id=:p');
        $final->execute([':p'=>$product_id]);
        $finalStock = (int)$final->fetchColumn();
        $logSuccess[] = '-- Final stock for product_id '.(string)$product_id.': '.$finalStock;
      }
    } catch (Throwable $t) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $logSuccess[] = 'ROLLBACK; -- due to error: '.$t->getMessage();
    }
  }

  if ($scenario === 'rollback') {
    $ranRollback = true;
    try {
      // Choose a product with the least stock and attempt an excessive quantity
      $work_id = (int)$pdo->query('SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 1')->fetchColumn();
      $pmin = $pdo->query('SELECT product_id, unit_price, stock_qty FROM product_details ORDER BY stock_qty ASC, product_id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
      if (!$work_id || !$pmin) {
        $logRollback[] = 'Setup error: Need a work order and a product.';
      } else {
        $product_id = (int)$pmin['product_id'];
        $have = (int)$pmin['stock_qty'];
        $qty = $have + 999; // force failure
        $logRollback[] = '-- Rollback scenario (allow_backorder = false)';
        $logRollback[] = 'BEGIN;';
        $pdo->beginTransaction();

        $sql1 = 'SELECT unit_price, stock_qty FROM product_details WHERE product_id = '.q($product_id).' FOR UPDATE';
        $st1 = $pdo->prepare('SELECT unit_price, stock_qty FROM product_details WHERE product_id = :pid FOR UPDATE');
        $st1->execute([':pid'=>$product_id]);
        $row1 = $st1->fetch(PDO::FETCH_ASSOC);
        $logRollback[] = $sql1 . ' -- => unit_price='.$row1['unit_price'].', stock_qty='.$row1['stock_qty'];

        if ((int)$row1['stock_qty'] < $qty) {
          $logRollback[] = '-- Check: requested qty ('.(string)$qty.") > stock (".(string)$row1['stock_qty'].') => insufficient stock';
          // Show the statements that would have been attempted, but are blocked by the check
          $unit = (float)$row1['unit_price'];
          $line = round($unit * $qty, 2);
          $logRollback[] = 'INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total) VALUES ('.q($work_id).', '.q($product_id).', '.q($qty).', '.q($unit).', '.q($line).') -- ATTEMPTED (blocked by check)';
          $logRollback[] = 'UPDATE product_details SET stock_qty = stock_qty - '.q($qty).' WHERE product_id = '.q($product_id).' -- ATTEMPTED (blocked by check)';
          $pdo->rollBack();
          $logRollback[] = 'ROLLBACK; -- Insufficient stock, no changes applied';
        } else {
          // Not expected, but handle gracefully
          $logRollback[] = '-- Unexpected: stock sufficient, performing insert/update then rolling back manually for demo';
          $unit = (float)$row1['unit_price'];
          $line = round($unit * $qty, 2);
          $logRollback[] = 'INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total) VALUES ('.q($work_id).', '.q($product_id).', '.q($qty).', '.q($unit).', '.q($line).') -- (demo)';
          $logRollback[] = 'UPDATE product_details SET stock_qty = stock_qty - '.q($qty).' WHERE product_id = '.q($product_id).' -- (demo)';
          $pdo->rollBack();
          $logRollback[] = 'ROLLBACK; -- Demo rollback';
        }

        $final = $pdo->prepare('SELECT stock_qty FROM product_details WHERE product_id=:p');
        $final->execute([':p'=>$product_id]);
        $finalStock = (int)$final->fetchColumn();
        $logRollback[] = '-- Final stock for product_id '.(string)$product_id.': '.$finalStock.' (should be unchanged: '.(string)$have.')';
      }
    } catch (Throwable $t) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $logRollback[] = 'ROLLBACK; -- due to error: '.$t->getMessage();
    }
  }
}

$pageTitle = 'Transactions Demo (Dev)';
require __DIR__ . '/header.php';
?>
  <h2>Transactions Demo (Dev Only)</h2>
  <p class="muted">This page shows two transaction scenarios with SQL lines and outcomes for screenshotting.</p>

  <div style="display:flex;gap:1rem;flex-wrap:wrap;margin:.5rem 0;">
    <form method="post">
      <input type="hidden" name="scenario" value="success" />
      <button type="submit">Run Successful Transaction</button>
    </form>
    <form method="post">
      <input type="hidden" name="scenario" value="rollback" />
      <button type="submit">Run Rollback Scenario</button>
    </form>
  </div>

  <?php if ($ranSuccess): ?>
    <section>
      <h3>Successful Transaction</h3>
      <pre style="background:#f8f9fa;border:1px solid #ddd;padding:.75rem;white-space:pre-wrap;word-break:break-word;">
<?php foreach ($logSuccess as $line) { echo e($line)."\n"; } ?>
      </pre>
    </section>
  <?php endif; ?>

  <?php if ($ranRollback): ?>
    <section>
      <h3>Rollback Scenario</h3>
      <pre style="background:#fff5f5;border:1px solid #f5c2c7;padding:.75rem;white-space:pre-wrap;word-break:break-word;">
<?php foreach ($logRollback as $line) { echo e($line)."\n"; } ?>
      </pre>
    </section>
  <?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
