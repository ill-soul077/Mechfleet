<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

function compute_labor_cost(PDO $pdo, int $mechanic_id, int $service_id): float {
  $st = $pdo->prepare('SELECT m.hourly_rate, s.estimated_hours FROM mechanics m, service_details s WHERE m.mechanic_id=:mid AND s.service_id=:sid');
  $st->execute([':mid'=>$mechanic_id, ':sid'=>$service_id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) return 0.0;
  return round((float)$row['hourly_rate'] * (float)$row['estimated_hours'], 2);
}

try {
  if ($action === 'create') {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $vehicle_id  = (int)($_POST['vehicle_id'] ?? 0);
    $mechanic_id = (int)($_POST['assigned_mechanic_id'] ?? 0);
    $service_id  = (int)($_POST['service_id'] ?? 0);
    $start_date  = trim($_POST['start_date'] ?? date('Y-m-d'));
    $status      = trim($_POST['status'] ?? 'pending');
    $notes       = trim($_POST['notes'] ?? '');
    if (!$customer_id || !$vehicle_id || !$mechanic_id || !$service_id) throw new RuntimeException('All fields are required');
    $labor = compute_labor_cost($pdo, $mechanic_id, $service_id);
    $stmt = $pdo->prepare('INSERT INTO working_details (customer_id,vehicle_id,assigned_mechanic_id,service_id,status,labor_cost,parts_cost,total_cost,start_date,notes) VALUES (:c,:v,:m,:s,:st,:lc,0.00,:lc,:sd,:n)');
    $stmt->execute([':c'=>$customer_id, ':v'=>$vehicle_id, ':m'=>$mechanic_id, ':s'=>$service_id, ':st'=>$status, ':lc'=>$labor, ':sd'=>$start_date, ':n'=>$notes]);
    $msg = 'Work order created';
  } elseif ($action === 'update') {
    $work_id = (int)($_POST['work_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'pending');
    $completion_date = $_POST['completion_date'] !== '' ? $_POST['completion_date'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $stmt = $pdo->prepare('UPDATE working_details SET status=:st, completion_date=:cd, notes=:n WHERE work_id=:id');
    $stmt->execute([':st'=>$status, ':cd'=>$completion_date, ':n'=>$notes, ':id'=>$work_id]);
    $msg = 'Work order updated';
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Data for forms
$customers = $pdo->query('SELECT customer_id, CONCAT(first_name, " ", last_name) AS name FROM customer ORDER BY customer_id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
$vehicles  = $pdo->query('SELECT v.vehicle_id, CONCAT(v.year, " ", v.make, " ", v.model, " (", c.first_name, " ", c.last_name, ")") AS label FROM vehicle v JOIN customer c ON c.customer_id=v.customer_id ORDER BY v.vehicle_id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
$mechanics = $pdo->query('SELECT mechanic_id, CONCAT(first_name, " ", last_name) AS name FROM mechanics WHERE active=1 ORDER BY mechanic_id')->fetchAll(PDO::FETCH_ASSOC);
$services  = $pdo->query('SELECT service_id, service_name FROM service_details WHERE active=1 ORDER BY service_id')->fetchAll(PDO::FETCH_ASSOC);

if ($id) {
  $job = $pdo->prepare('SELECT w.*, CONCAT(c.first_name, " ", c.last_name) AS customer_name, CONCAT(v.year, " ", v.make, " ", v.model) AS vehicle_info, CONCAT(m.first_name, " ", m.last_name) AS mechanic_name, s.service_name FROM working_details w JOIN customer c ON c.customer_id=w.customer_id JOIN vehicle v ON v.vehicle_id=w.vehicle_id JOIN mechanics m ON m.mechanic_id=w.assigned_mechanic_id JOIN service_details s ON s.service_id=w.service_id WHERE w.work_id=:id');
  $job->execute([':id'=>$id]);
  $jobRow = $job->fetch(PDO::FETCH_ASSOC);
  $parts = $pdo->prepare('SELECT wp.*, p.sku, p.product_name FROM work_parts wp JOIN product_details p ON p.product_id=wp.product_id WHERE wp.work_id=:id ORDER BY p.product_name');
  $parts->execute([':id'=>$id]);
  $partsRows = $parts->fetchAll(PDO::FETCH_ASSOC);
  $income = $pdo->prepare('SELECT * FROM income WHERE work_id=:id ORDER BY payment_date DESC');
  $income->execute([':id'=>$id]);
  $incomeRows = $income->fetchAll(PDO::FETCH_ASSOC);
}

$list = $pdo->query('SELECT w.work_id, w.status, w.start_date, w.completion_date, w.total_cost, CONCAT(c.first_name, " ", c.last_name) AS customer, CONCAT(v.year, " ", v.make, " ", v.model) AS vehicle, s.service_name FROM working_details w JOIN customer c ON c.customer_id=w.customer_id JOIN vehicle v ON v.vehicle_id=w.vehicle_id JOIN service_details s ON s.service_id=w.service_id ORDER BY w.work_id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $id ? ('Work Order #'.$id) : 'Work Orders';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2><?= e($pageTitle) ?></h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><strong>Error:</strong> <?= e($err) ?></p><?php endif; ?>

  <?php if (!$id): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <section>
        <h3>Create Work Order</h3>
        <form method="post">
          <input type="hidden" name="action" value="create" />
          <label>Customer</label><br />
          <select name="customer_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= e((string)$c['customer_id']) ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <br /><label>Vehicle</label><br />
          <select name="vehicle_id" required>
            <option value="">-- choose --</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= e((string)$v['vehicle_id']) ?>"><?= e($v['label']) ?></option>
            <?php endforeach; ?>
          </select>
          <br /><label>Mechanic</label><br />
          <select name="assigned_mechanic_id" required>
            <?php foreach ($mechanics as $m): ?>
              <option value="<?= e((string)$m['mechanic_id']) ?>"><?= e($m['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <br /><label>Service</label><br />
          <select name="service_id" required>
            <?php foreach ($services as $s): ?>
              <option value="<?= e((string)$s['service_id']) ?>"><?= e($s['service_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <br /><label>Status</label><br />
          <select name="status">
            <option>pending</option>
            <option>in_progress</option>
            <option>completed</option>
            <option>cancelled</option>
          </select>
          <br /><label>Start date</label><br /><input type="date" name="start_date" value="<?= e(date('Y-m-d')) ?>" />
          <br /><label>Notes</label><br /><input name="notes" />
          <div style="margin-top:.5rem"></div>
          <button type="submit">Create</button>
        </form>
      </section>

      <section>
        <h3>Recent Work Orders</h3>
        <table class="table">
          <thead><tr><th>ID</th><th>Customer</th><th>Vehicle</th><th>Service</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($list as $r): ?>
              <tr>
                <td><?= e((string)$r['work_id']) ?></td>
                <td><?= e($r['customer']) ?></td>
                <td><?= e($r['vehicle']) ?></td>
                <td><?= e($r['service_name']) ?></td>
                <td><?= e($r['status']) ?></td>
                <td><?= e((string)$r['total_cost']) ?></td>
                <td><a href="work_orders.php?id=<?= e((string)$r['work_id']) ?>">Details</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  <?php else: ?>
    <?php if (!$jobRow): ?>
      <p class="err">Work order not found.</p>
    <?php else: ?>
      <section>
        <h3>Details</h3>
        <p><strong>Customer:</strong> <?= e($jobRow['customer_name']) ?> — <strong>Vehicle:</strong> <?= e($jobRow['vehicle_info']) ?></p>
        <p><strong>Mechanic:</strong> <?= e($jobRow['mechanic_name']) ?> — <strong>Service:</strong> <?= e($jobRow['service_name']) ?></p>
        <p><strong>Status:</strong> <?= e($jobRow['status']) ?> — <strong>Start:</strong> <?= e($jobRow['start_date']) ?> — <strong>Complete:</strong> <?= e((string)$jobRow['completion_date']) ?></p>
        <p><strong>Labor:</strong> $<?= e((string)$jobRow['labor_cost']) ?> — <strong>Parts:</strong> $<?= e((string)$jobRow['parts_cost']) ?> — <strong>Total:</strong> $<?= e((string)$jobRow['total_cost']) ?></p>
        <form method="post" style="margin-top:.5rem">
          <input type="hidden" name="action" value="update" />
          <input type="hidden" name="work_id" value="<?= e((string)$id) ?>" />
          <label>Status</label>
          <select name="status">
            <?php foreach (['pending','in_progress','completed','cancelled'] as $st): $sel = $st===$jobRow['status']?'selected':''; ?>
              <option <?= $sel ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
          <label>Completion date</label>
          <input type="date" name="completion_date" value="<?= e((string)$jobRow['completion_date']) ?>" />
          <label>Notes</label>
          <input name="notes" value="<?= e($jobRow['notes'] ?? '') ?>" />
          <button type="submit">Save</button>
          <a href="work_orders.php" style="margin-left:.5rem">Back</a>
        </form>
      </section>

      <section>
        <h3>Parts</h3>
        <button type="button" onclick="openPartsModal(<?= (int)$id ?>)">Add Part</button>
        <table class="table" style="margin-top:.5rem">
          <thead><tr><th>SKU</th><th>Name</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($partsRows as $p): ?>
              <tr>
                <td><?= e($p['sku']) ?></td>
                <td><?= e($p['product_name']) ?></td>
                <td><?= e((string)$p['quantity']) ?></td>
                <td><?= e((string)$p['unit_price']) ?></td>
                <td><?= e((string)$p['line_total']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section>
        <h3>Payments</h3>
        <?php if (empty($incomeRows)): ?><p class="muted">No income records yet.</p><?php endif; ?>
        <?php if (!empty($incomeRows)): ?>
        <table class="table">
          <thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Tax</th><th>Txn</th></tr></thead>
          <tbody>
            <?php foreach ($incomeRows as $inc): ?>
              <tr>
                <td><?= e($inc['payment_date']) ?></td>
                <td><?= e($inc['payment_method']) ?></td>
                <td><?= e((string)$inc['amount']) ?></td>
                <td><?= e((string)$inc['tax']) ?></td>
                <td><?= e($inc['transaction_reference'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <div id="modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:#fff; padding:1rem; max-width:520px; width:95%;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <strong>Add Part</strong>
        <button onclick="closeModal()">X</button>
      </div>
      <div id="modal-body" style="margin-top:.5rem">Loading...</div>
    </div>
  </div>

  <script>
    function openPartsModal(workId){
      const modal = document.getElementById('modal');
      const body = document.getElementById('modal-body');
      modal.style.display='flex';
      body.textContent='Loading...';
      fetch('work_parts_add.php?work_id='+encodeURIComponent(workId))
        .then(r=>r.text()).then(html=>{ body.innerHTML = html; });
    }
    function closeModal(){ document.getElementById('modal').style.display='none'; }
  </script>

<?php require __DIR__ . '/footer.php'; ?>
