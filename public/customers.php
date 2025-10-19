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
    $stmt = $pdo->prepare('DELETE FROM customer WHERE customer_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Customer deleted';
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) {
  $st = $pdo->prepare('SELECT * FROM customer WHERE customer_id=:id');
  $st->execute([':id'=>$editId]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC);
}

$rows = $pdo->query('SELECT * FROM customer ORDER BY customer_id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

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
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e((string)$r['customer_id']) ?></td>
              <td><?= e($r['first_name'].' '.$r['last_name']) ?></td>
              <td><?= e($r['email']) ?></td>
              <td><?= e($r['phone']) ?></td>
              <td>
                <a href="customers.php?edit=<?= e((string)$r['customer_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete customer #<?= e((string)$r['customer_id']) ?>?');">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="customer_id" value="<?= e((string)$r['customer_id']) ?>" />
                  <button type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </div>
<?php require __DIR__ . '/footer.php'; ?>
