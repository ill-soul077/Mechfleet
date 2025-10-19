<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

function validate_vehicle(array $d): array {
  $errors = [];
  if (!(int)($d['customer_id'] ?? 0)) $errors[] = 'Customer is required';
  if (strlen(trim($d['vin'] ?? '')) !== 17) $errors[] = 'VIN must be 17 characters';
  if (trim($d['make'] ?? '') === '') $errors[] = 'Make is required';
  if (trim($d['model'] ?? '') === '') $errors[] = 'Model is required';
  $year = (int)($d['year'] ?? 0); if ($year < 1900 || $year > 2100) $errors[] = 'Year out of range';
  $mileage = (int)($d['mileage'] ?? 0); if ($mileage < 0) $errors[] = 'Mileage must be non-negative';
  if (!empty($errors)) return [false, implode('; ', $errors)];
  return [true, 'OK'];
}

try {
  if ($action === 'create') {
    [$ok, $m] = validate_vehicle($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('INSERT INTO vehicle (customer_id,vin,make,model,year,color,mileage,license_plate) VALUES (:cid,:vin,:mk,:md,:yr,:cl,:mi,:lp)');
    $stmt->execute([
      ':cid'=>(int)$_POST['customer_id'], ':vin'=>trim($_POST['vin']), ':mk'=>trim($_POST['make']), ':md'=>trim($_POST['model']), ':yr'=>(int)$_POST['year'], ':cl'=>trim($_POST['color'] ?? ''), ':mi'=>(int)$_POST['mileage'], ':lp'=>trim($_POST['license_plate'] ?? ''),
    ]);
    $msg = 'Vehicle added';
  } elseif ($action === 'update') {
    $id = (int)($_POST['vehicle_id'] ?? 0);
    [$ok, $m] = validate_vehicle($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE vehicle SET customer_id=:cid, vin=:vin, make=:mk, model=:md, year=:yr, color=:cl, mileage=:mi, license_plate=:lp WHERE vehicle_id=:id');
    $stmt->execute([
      ':cid'=>(int)$_POST['customer_id'], ':vin'=>trim($_POST['vin']), ':mk'=>trim($_POST['make']), ':md'=>trim($_POST['model']), ':yr'=>(int)$_POST['year'], ':cl'=>trim($_POST['color'] ?? ''), ':mi'=>(int)$_POST['mileage'], ':lp'=>trim($_POST['license_plate'] ?? ''), ':id'=>$id,
    ]);
    $msg = 'Vehicle updated';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['vehicle_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM vehicle WHERE vehicle_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Vehicle deleted';
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) {
  $st = $pdo->prepare('SELECT * FROM vehicle WHERE vehicle_id=:id');
  $st->execute([':id'=>$editId]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC);
}
$customers = $pdo->query('SELECT customer_id, CONCAT(first_name, " ", last_name) AS name FROM customer ORDER BY customer_id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
$rows = $pdo->query('SELECT v.*, CONCAT(c.first_name, " ", c.last_name) AS customer_name FROM vehicle v JOIN customer c ON c.customer_id = v.customer_id ORDER BY v.vehicle_id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Vehicles';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2>Vehicles</h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><strong>Error:</strong> <?= e($err) ?></p><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <section>
      <h3><?= $editRow ? 'Edit Vehicle #'.e((string)$editId) : 'Add Vehicle' ?></h3>
      <form method="post">
        <?php if ($editRow): ?><input type="hidden" name="action" value="update" /><input type="hidden" name="vehicle_id" value="<?= e((string)$editId) ?>" /><?php else: ?><input type="hidden" name="action" value="create" /><?php endif; ?>
        <label>Customer</label><br />
        <select name="customer_id" required>
          <option value="">-- choose --</option>
          <?php foreach ($customers as $c): $sel = ($editRow['customer_id'] ?? null) == $c['customer_id'] ? 'selected' : ''; ?>
            <option value="<?= e((string)$c['customer_id']) ?>" <?= $sel ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <br /><label>VIN</label><br /><input name="vin" maxlength="17" value="<?= e($editRow['vin'] ?? '') ?>" required />
        <br /><label>Make</label><br /><input name="make" value="<?= e($editRow['make'] ?? '') ?>" required />
        <br /><label>Model</label><br /><input name="model" value="<?= e($editRow['model'] ?? '') ?>" required />
        <br /><label>Year</label><br /><input type="number" name="year" value="<?= e((string)($editRow['year'] ?? date('Y'))) ?>" required />
        <br /><label>Color</label><br /><input name="color" value="<?= e($editRow['color'] ?? '') ?>" />
        <br /><label>Mileage</label><br /><input type="number" name="mileage" value="<?= e((string)($editRow['mileage'] ?? '0')) ?>" />
        <br /><label>License plate</label><br /><input name="license_plate" value="<?= e($editRow['license_plate'] ?? '') ?>" />
        <div style="margin-top:.5rem"></div>
        <button type="submit"><?= $editRow ? 'Update' : 'Create' ?></button>
        <?php if ($editRow): ?><a href="vehicles.php" style="margin-left:.5rem">Cancel</a><?php endif; ?>
      </form>
    </section>

    <section>
      <h3>Recent Vehicles</h3>
      <table class="table">
        <thead><tr><th>ID</th><th>Owner</th><th>VIN</th><th>Make/Model/Year</th><th>Mileage</th><th>Plate</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e((string)$r['vehicle_id']) ?></td>
              <td><?= e($r['customer_name']) ?></td>
              <td><?= e($r['vin']) ?></td>
              <td><?= e($r['year'].' '.$r['make'].' '.$r['model']) ?></td>
              <td><?= e((string)$r['mileage']) ?></td>
              <td><?= e($r['license_plate']) ?></td>
              <td>
                <a href="vehicles.php?edit=<?= e((string)$r['vehicle_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete vehicle #<?= e((string)$r['vehicle_id']) ?>?');">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="vehicle_id" value="<?= e((string)$r['vehicle_id']) ?>" />
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
