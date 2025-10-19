<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

function validate_mechanic(array $d): array {
  $errors = [];
  if (trim($d['first_name'] ?? '') === '') $errors[] = 'First name is required';
  if (trim($d['last_name'] ?? '') === '') $errors[] = 'Last name is required';
  if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
  if (!is_numeric($d['hourly_rate'] ?? '')) $errors[] = 'Hourly rate must be numeric';
  if (trim($d['hired_date'] ?? '') === '') $errors[] = 'Hired date is required';
  if (!empty($errors)) return [false, implode('; ', $errors)];
  return [true, 'OK'];
}

try {
  if ($action === 'create') {
    [$ok, $m] = validate_mechanic($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('INSERT INTO mechanics (first_name,last_name,email,phone,specialty,hourly_rate,managed_by,hired_date,active) VALUES (:fn,:ln,:em,:ph,:sp,:hr,:mb,:hd,:ac)');
    $stmt->execute([
      ':fn'=>trim($_POST['first_name']), ':ln'=>trim($_POST['last_name']), ':em'=>trim($_POST['email']), ':ph'=>trim($_POST['phone'] ?? ''),
      ':sp'=>trim($_POST['specialty'] ?? ''), ':hr'=>(float)$_POST['hourly_rate'], ':mb'=>$_POST['managed_by'] !== '' ? (int)$_POST['managed_by'] : null,
      ':hd'=>trim($_POST['hired_date']), ':ac'=>isset($_POST['active']) ? 1 : 0,
    ]);
    $msg = 'Mechanic added';
  } elseif ($action === 'update') {
    $id = (int)($_POST['mechanic_id'] ?? 0);
    [$ok, $m] = validate_mechanic($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE mechanics SET first_name=:fn,last_name=:ln,email=:em,phone=:ph,specialty=:sp,hourly_rate=:hr,managed_by=:mb,hired_date=:hd,active=:ac WHERE mechanic_id=:id');
    $stmt->execute([
      ':fn'=>trim($_POST['first_name']), ':ln'=>trim($_POST['last_name']), ':em'=>trim($_POST['email']), ':ph'=>trim($_POST['phone'] ?? ''),
      ':sp'=>trim($_POST['specialty'] ?? ''), ':hr'=>(float)$_POST['hourly_rate'], ':mb'=>$_POST['managed_by'] !== '' ? (int)$_POST['managed_by'] : null,
      ':hd'=>trim($_POST['hired_date']), ':ac'=>isset($_POST['active']) ? 1 : 0, ':id'=>$id,
    ]);
    $msg = 'Mechanic updated';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['mechanic_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM mechanics WHERE mechanic_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Mechanic deleted';
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) {
  $st = $pdo->prepare('SELECT * FROM mechanics WHERE mechanic_id=:id');
  $st->execute([':id'=>$editId]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC);
}
$managers = $pdo->query('SELECT manager_id, CONCAT(first_name," ",last_name) AS name FROM manager ORDER BY manager_id')->fetchAll(PDO::FETCH_ASSOC);
$rows = $pdo->query('SELECT * FROM mechanics ORDER BY mechanic_id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Mechanics';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2>Mechanics</h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><strong>Error:</strong> <?= e($err) ?></p><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <section>
      <h3><?= $editRow ? 'Edit Mechanic #'.e((string)$editId) : 'Add Mechanic' ?></h3>
      <form method="post">
        <?php if ($editRow): ?><input type="hidden" name="action" value="update" /><input type="hidden" name="mechanic_id" value="<?= e((string)$editId) ?>" /><?php else: ?><input type="hidden" name="action" value="create" /><?php endif; ?>
        <label>First name</label><br /><input name="first_name" value="<?= e($editRow['first_name'] ?? '') ?>" required />
        <br /><label>Last name</label><br /><input name="last_name" value="<?= e($editRow['last_name'] ?? '') ?>" required />
        <br /><label>Email</label><br /><input type="email" name="email" value="<?= e($editRow['email'] ?? '') ?>" required />
        <br /><label>Phone</label><br /><input name="phone" value="<?= e($editRow['phone'] ?? '') ?>" />
        <br /><label>Specialty</label><br /><input name="specialty" value="<?= e($editRow['specialty'] ?? '') ?>" />
        <br /><label>Hourly rate</label><br /><input type="number" step="0.01" name="hourly_rate" value="<?= e((string)($editRow['hourly_rate'] ?? '0')) ?>" required />
        <br /><label>Managed by</label><br />
        <select name="managed_by">
          <option value="">(none)</option>
          <?php foreach ($managers as $m): $sel = ($editRow['managed_by'] ?? null) == $m['manager_id'] ? 'selected' : ''; ?>
            <option value="<?= e((string)$m['manager_id']) ?>" <?= $sel ?>><?= e($m['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <br /><label>Hired date</label><br /><input type="date" name="hired_date" value="<?= e($editRow['hired_date'] ?? date('Y-m-d')) ?>" required />
        <br /><label><input type="checkbox" name="active" <?= (!isset($editRow) || ($editRow && (int)$editRow['active'] === 1)) ? 'checked' : '' ?> /> Active</label>
        <div style="margin-top:.5rem"></div>
        <button type="submit"><?= $editRow ? 'Update' : 'Create' ?></button>
        <?php if ($editRow): ?><a href="mechanics.php" style="margin-left:.5rem">Cancel</a><?php endif; ?>
      </form>
    </section>

    <section>
      <h3>Recent Mechanics</h3>
      <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Rate</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e((string)$r['mechanic_id']) ?></td>
              <td><?= e($r['first_name'].' '.$r['last_name']) ?></td>
              <td><?= e($r['email']) ?></td>
              <td><?= e((string)$r['hourly_rate']) ?></td>
              <td><?= e((string)$r['active']) ?></td>
              <td>
                <a href="mechanics.php?edit=<?= e((string)$r['mechanic_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete mechanic #<?= e((string)$r['mechanic_id']) ?>?');">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="mechanic_id" value="<?= e((string)$r['mechanic_id']) ?>" />
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
