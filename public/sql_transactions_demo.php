<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';

if (!DEV_MODE) {
  http_response_code(404);
  exit('Not available in production');
}

auth_require_login();

$message = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $work_id = (int)($_POST['work_id'] ?? 1);
  $product_id = (int)($_POST['product_id'] ?? 1);
  $qty = max(1, (int)($_POST['qty'] ?? 1));
  try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT unit_price, stock_qty FROM product_details WHERE product_id = :pid FOR UPDATE');
    $stmt->execute([':pid' => $product_id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prod) { throw new RuntimeException('Product not found'); }
    if ((int)$prod['stock_qty'] < $qty) {
      throw new RuntimeException('Insufficient stock');
    }
    $price = (float)$prod['unit_price'];
    $stmt = $pdo->prepare('INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total) VALUES (:w,:p,:q,:u,:t)');
    $stmt->execute([':w'=>$work_id, ':p'=>$product_id, ':q'=>$qty, ':u'=>$price, ':t'=>round($price*$qty,2)]);
    $stmt = $pdo->prepare('UPDATE product_details SET stock_qty = stock_qty - :q WHERE product_id = :p');
    $stmt->execute([':q'=>$qty, ':p'=>$product_id]);
    $pdo->commit();
    $message = 'Transaction committed';
  } catch (Throwable $t) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $error = $t->getMessage();
  }
}

$pageTitle = 'Transactions Demo (Dev)';
require __DIR__ . '/header.php';
?>
  <h2>Transactions Demo (Dev Only)</h2>
  <?php if ($message): ?><p class="ok"><?= e($message) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="err">Error: <?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <label>Work ID</label><br /><input type="number" name="work_id" value="1" /><br />
    <label>Product ID</label><br /><input type="number" name="product_id" value="1" /><br />
    <label>Quantity</label><br /><input type="number" name="qty" value="1" /><br />
    <div style="margin-top:.5rem"></div>
    <button type="submit">Run Transaction</button>
  </form>
<?php require __DIR__ . '/footer.php'; ?>
