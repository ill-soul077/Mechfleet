<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

function validate_service(array $d): array {
  $errors = [];
  if (trim($d['service_name'] ?? '') === '') $errors[] = 'Service name is required';
  if (!is_numeric($d['base_price'] ?? '')) $errors[] = 'Base price must be numeric';
  if (!is_numeric($d['estimated_hours'] ?? '')) $errors[] = 'Estimated hours must be numeric';
  if (!empty($errors)) return [false, implode('; ', $errors)];
  return [true, 'OK'];
}

try {
  if ($action === 'create') {
    [$ok, $m] = validate_service($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('INSERT INTO service_details (service_name,description,base_price,estimated_hours,active) VALUES (:nm,:ds,:bp,:eh,:ac)');
    $stmt->execute([
      ':nm'=>trim($_POST['service_name']), ':ds'=>trim($_POST['description'] ?? ''), ':bp'=>(float)$_POST['base_price'], ':eh'=>(float)$_POST['estimated_hours'], ':ac'=>isset($_POST['active'])?1:0,
    ]);
    $msg = 'Service added';
  } elseif ($action === 'update') {
    $id = (int)($_POST['service_id'] ?? 0);
    [$ok, $m] = validate_service($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE service_details SET service_name=:nm,description=:ds,base_price=:bp,estimated_hours=:eh,active=:ac WHERE service_id=:id');
    $stmt->execute([
      ':nm'=>trim($_POST['service_name']), ':ds'=>trim($_POST['description'] ?? ''), ':bp'=>(float)$_POST['base_price'], ':eh'=>(float)$_POST['estimated_hours'], ':ac'=>isset($_POST['active'])?1:0, ':id'=>$id,
    ]);
    $msg = 'Service updated';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['service_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM service_details WHERE service_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Service deleted';
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) {
  $st = $pdo->prepare('SELECT * FROM service_details WHERE service_id=:id');
  $st->execute([':id'=>$editId]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC);
}

$rows = $pdo->query('SELECT * FROM service_details ORDER BY service_id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Services';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2>Services</h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><strong>Error:</strong> <?= e($err) ?></p><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <section>
      <h3><?= $editRow ? 'Edit Service #'.e((string)$editId) : 'Add Service' ?></h3>
      <form method="post">
        <?php if ($editRow): ?><input type="hidden" name="action" value="update" /><input type="hidden" name="service_id" value="<?= e((string)$editId) ?>" /><?php else: ?><input type="hidden" name="action" value="create" /><?php endif; ?>
        <label>Name</label><br /><input name="service_name" value="<?= e($editRow['service_name'] ?? '') ?>" required />
        <br /><label>Description</label><br /><input name="description" value="<?= e($editRow['description'] ?? '') ?>" />
        <br /><label>Base price</label><br /><input type="number" step="0.01" name="base_price" value="<?= e((string)($editRow['base_price'] ?? '0')) ?>" required />
        <br /><label>Estimated hours</label><br /><input type="number" step="0.25" name="estimated_hours" value="<?= e((string)($editRow['estimated_hours'] ?? '1')) ?>" required />
        <br /><label><input type="checkbox" name="active" <?= (!isset($editRow) || ($editRow && (int)$editRow['active'] === 1)) ? 'checked' : '' ?> /> Active</label>
        <div style="margin-top:.5rem"></div>
        <button type="submit"><?= $editRow ? 'Update' : 'Create' ?></button>
        <?php if ($editRow): ?><a href="services.php" style="margin-left:.5rem">Cancel</a><?php endif; ?>
      </form>
    </section>

    <section>
      <h3>Service Catalog</h3>
      <table class="table">
        <thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Hours</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e((string)$r['service_id']) ?></td>
              <td><?= e($r['service_name']) ?></td>
              <td><?= e((string)$r['base_price']) ?></td>
              <td><?= e((string)$r['estimated_hours']) ?></td>
              <td><?= e((string)$r['active']) ?></td>
              <td>
                <a href="services.php?edit=<?= e((string)$r['service_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete service #<?= e((string)$r['service_id']) ?>?');">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="service_id" value="<?= e((string)$r['service_id']) ?>" />
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
