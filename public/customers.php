<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

function validate_customer(array $d): array {
  $errors = [];
  if (trim($d['first_name'] ?? '') === '') $errors[] = 'First name is required';
  if (trim($d['last_name'] ?? '') === '') $errors[] = 'Last name is required';
  if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
  if (trim($d['phone'] ?? '') === '') $errors[] = 'Phone is required';
  if (!empty($errors)) return [false, implode('; ', $errors)];
  return [true, 'OK'];
}

try {
  if ($action === 'create') {
    [$ok, $m] = validate_customer($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('INSERT INTO customer (first_name,last_name,email,phone,address,city,state,zip_code) VALUES (:fn,:ln,:em,:ph,:ad,:ci,:st,:zip)');
    $stmt->execute([
      ':fn'=>trim($_POST['first_name']), ':ln'=>trim($_POST['last_name']), ':em'=>trim($_POST['email']), ':ph'=>trim($_POST['phone']),
      ':ad'=>trim($_POST['address'] ?? ''), ':ci'=>trim($_POST['city'] ?? ''), ':st'=>trim($_POST['state'] ?? ''), ':zip'=>trim($_POST['zip_code'] ?? ''),
    ]);
    $msg = 'Customer added';
  } elseif ($action === 'update') {
    $id = (int)($_POST['customer_id'] ?? 0);
    [$ok, $m] = validate_customer($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE customer SET first_name=:fn,last_name=:ln,email=:em,phone=:ph,address=:ad,city=:ci,state=:st,zip_code=:zip WHERE customer_id=:id');
    $stmt->execute([
      ':fn'=>trim($_POST['first_name']), ':ln'=>trim($_POST['last_name']), ':em'=>trim($_POST['email']), ':ph'=>trim($_POST['phone']),
      ':ad'=>trim($_POST['address'] ?? ''), ':ci'=>trim($_POST['city'] ?? ''), ':st'=>trim($_POST['state'] ?? ''), ':zip'=>trim($_POST['zip_code'] ?? ''), ':id'=>$id,
    ]);
    $msg = 'Customer updated';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['customer_id'] ?? 0);
    
    // Check if customer has vehicles before deleting
    $checkStmt = $pdo->prepare('SELECT COUNT(*) as vehicle_count FROM vehicle WHERE customer_id = :id');
    $checkStmt->execute([':id' => $id]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['vehicle_count'] > 0) {
      throw new RuntimeException('Cannot delete customer: This customer has ' . $result['vehicle_count'] . ' vehicle(s) registered. Please delete or reassign the vehicles first.');
    }
    
    // Check if customer has work orders
    $checkWorkStmt = $pdo->prepare('SELECT COUNT(*) as work_count FROM working_details WHERE customer_id = :id');
    $checkWorkStmt->execute([':id' => $id]);
    $workResult = $checkWorkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($workResult['work_count'] > 0) {
      throw new RuntimeException('Cannot delete customer: This customer has ' . $workResult['work_count'] . ' work order(s). Please delete the work orders first.');
    }
    
    // Safe to delete
    $stmt = $pdo->prepare('DELETE FROM customer WHERE customer_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Customer deleted successfully';
  }
} catch (Throwable $t) { 
  // Better error message for foreign key constraints
  if (strpos($t->getMessage(), 'Integrity constraint violation') !== false) {
    $err = 'Cannot delete customer: This customer has related records (vehicles or work orders). Please delete those records first.';
  } else {
    $err = $t->getMessage();
  }
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) {
  $st = $pdo->prepare('SELECT * FROM customer WHERE customer_id=:id');
  $st->execute([':id'=>$editId]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC);
}

$rows = $pdo->query('
  SELECT c.*, 
         COUNT(DISTINCT v.vehicle_id) as vehicle_count,
         COUNT(DISTINCT w.work_id) as work_count
  FROM customer c
  LEFT JOIN vehicle v ON c.customer_id = v.customer_id
  LEFT JOIN working_details w ON c.customer_id = w.customer_id
  GROUP BY c.customer_id
  ORDER BY c.customer_id DESC 
  LIMIT 200
')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Customers';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2>Customers</h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><strong>Error:</strong> <?= e($err) ?></p><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <section>
      <h3><?= $editRow ? 'Edit Customer #'.e((string)$editId) : 'Add Customer' ?></h3>
      <form method="post">
        <?php if ($editRow): ?><input type="hidden" name="action" value="update" /><input type="hidden" name="customer_id" value="<?= e((string)$editId) ?>" /><?php else: ?><input type="hidden" name="action" value="create" /><?php endif; ?>
        <label>First name</label><br /><input name="first_name" value="<?= e($editRow['first_name'] ?? '') ?>" required />
        <br /><label>Last name</label><br /><input name="last_name" value="<?= e($editRow['last_name'] ?? '') ?>" required />
        <br /><label>Email</label><br /><input type="email" name="email" value="<?= e($editRow['email'] ?? '') ?>" required />
        <br /><label>Phone</label><br /><input name="phone" value="<?= e($editRow['phone'] ?? '') ?>" required />
        <br /><label>Address</label><br /><input name="address" value="<?= e($editRow['address'] ?? '') ?>" />
        <br /><label>City</label><br /><input name="city" value="<?= e($editRow['city'] ?? '') ?>" />
        <br /><label>State</label><br /><input name="state" maxlength="2" value="<?= e($editRow['state'] ?? '') ?>" />
        <br /><label>ZIP</label><br /><input name="zip_code" value="<?= e($editRow['zip_code'] ?? '') ?>" />
        <div style="margin-top:.5rem"></div>
        <button type="submit"><?= $editRow ? 'Update' : 'Create' ?></button>
        <?php if ($editRow): ?><a href="customers.php" style="margin-left:.5rem">Cancel</a><?php endif; ?>
      </form>
    </section>

    <section>
      <h3>Recent Customers</h3>
      <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Vehicles</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e((string)$r['customer_id']) ?></td>
              <td><?= e($r['first_name'].' '.$r['last_name']) ?></td>
              <td><?= e($r['email']) ?></td>
              <td><?= e($r['phone']) ?></td>
              <td><?= e((string)$r['vehicle_count']) ?></td>
              <td>
                <a href="customers.php?edit=<?= e((string)$r['customer_id']) ?>">Edit</a>
                <?php if ($r['vehicle_count'] > 0 || $r['work_count'] > 0): ?>
                  <button type="button" disabled title="Cannot delete: Has <?= e((string)$r['vehicle_count']) ?> vehicle(s) and <?= e((string)$r['work_count']) ?> work order(s)">Delete</button>
                <?php else: ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete customer #<?= e((string)$r['customer_id']) ?>?');">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="customer_id" value="<?= e((string)$r['customer_id']) ?>" />
                    <button type="submit">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </div>
<?php require __DIR__ . '/footer.php'; ?>
