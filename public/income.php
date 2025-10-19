<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$msg = null; $err = null;
$prefill_work_id = isset($_GET['work_id']) ? (int)$_GET['work_id'] : 0;

$action = $_POST['action'] ?? '';
try {
  if ($action === 'create') {
    $work_id = (int)($_POST['work_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $method = trim($_POST['payment_method'] ?? 'cash');
    $date = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $ref = trim($_POST['transaction_reference'] ?? '');
    if (!$work_id) throw new RuntimeException('Work order required');
    if ($amount <= 0) throw new RuntimeException('Amount must be > 0');
    if ($tax < 0) throw new RuntimeException('Tax must be >= 0');
    $allowed = ['cash','credit_card','debit_card','check','bank_transfer'];
    if (!in_array($method, $allowed, true)) throw new RuntimeException('Invalid payment method');
    // Ensure work exists
    $chk = $pdo->prepare('SELECT status FROM working_details WHERE work_id=:id');
    $chk->execute([':id'=>$work_id]);
    $st = $chk->fetchColumn();
    if ($st === false) throw new RuntimeException('Work order not found');
    // Insert payment
    $ins = $pdo->prepare('INSERT INTO income (work_id, amount, tax, payment_method, payment_date, transaction_reference) VALUES (:w,:a,:t,:m,:d,:r)');
    $ins->execute([':w'=>$work_id, ':a'=>$amount, ':t'=>$tax, ':m'=>$method, ':d'=>$date, ':r'=>$ref !== '' ? $ref : null]);
    $msg = 'Payment recorded';
    $prefill_work_id = $work_id;
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

// Load options
$workOrders = $pdo->query('SELECT w.work_id, CONCAT("#", w.work_id, " — ", c.first_name, " ", c.last_name, " — ", s.service_name) AS label FROM working_details w JOIN customer c ON c.customer_id=w.customer_id JOIN service_details s ON s.service_id=w.service_id ORDER BY w.work_id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

$recent = $pdo->query('SELECT i.*, CONCAT(c.first_name, " ", c.last_name) AS customer, s.service_name FROM income i JOIN working_details w ON w.work_id=i.work_id JOIN customer c ON c.customer_id=w.customer_id JOIN service_details s ON s.service_id=w.service_id ORDER BY i.income_id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Record Payment';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2><?= e($pageTitle) ?></h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><strong>Error:</strong> <?= e($err) ?></p><?php endif; ?>

  <section>
    <h3>New Payment</h3>
    <form method="post">
      <input type="hidden" name="action" value="create" />
      <label>Work Order</label><br />
      <select name="work_id" required>
        <option value="">-- choose --</option>
        <?php foreach ($workOrders as $w): $sel = ($prefill_work_id && (int)$w['work_id']===$prefill_work_id) ? 'selected' : ''; ?>
          <option value="<?= e((string)$w['work_id']) ?>" <?= $sel ?>><?= e($w['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <br /><label>Amount</label><br />
      <input type="number" name="amount" step="0.01" min="0.01" required />
      <br /><label>Tax</label><br />
      <input type="number" name="tax" step="0.01" min="0" value="0.00" />
      <br /><label>Payment Method</label><br />
      <select name="payment_method">
        <option>cash</option>
        <option>credit_card</option>
        <option>debit_card</option>
        <option>check</option>
        <option>bank_transfer</option>
      </select>
      <br /><label>Payment Date</label><br />
      <input type="date" name="payment_date" value="<?= e(date('Y-m-d')) ?>" />
      <br /><label>Transaction Ref</label><br />
      <input name="transaction_reference" />
      <div style="margin-top:.5rem"></div>
      <button type="submit">Save</button>
    </form>
  </section>

  <section>
    <h3>Recent Payments</h3>
    <?php if (empty($recent)): ?><p class="muted">No payments yet.</p><?php endif; ?>
    <?php if (!empty($recent)): ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Customer</th><th>Service</th><th>Method</th><th>Amount</th><th>Tax</th><th>Work</th><th>Txn</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= e($r['payment_date']) ?></td>
            <td><?= e($r['customer']) ?></td>
            <td><?= e($r['service_name']) ?></td>
            <td><?= e($r['payment_method']) ?></td>
            <td><?= e((string)$r['amount']) ?></td>
            <td><?= e((string)$r['tax']) ?></td>
            <td><a href="work_orders.php?id=<?= e((string)$r['work_id']) ?>">#<?= e((string)$r['work_id']) ?></a></td>
            <td><?= e($r['transaction_reference'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>

<?php require __DIR__ . '/footer.php'; ?>
