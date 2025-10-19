<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

function validate_product(array $d): array {
  $errors = [];
  if (trim($d['sku'] ?? '') === '') $errors[] = 'SKU is required';
  if (trim($d['product_name'] ?? '') === '') $errors[] = 'Product name is required';
  if (!is_numeric($d['unit_price'] ?? '')) $errors[] = 'Unit price must be numeric';
  if (!is_numeric($d['stock_qty'] ?? '')) $errors[] = 'Stock qty must be numeric';
  if (!is_numeric($d['reorder_level'] ?? '')) $errors[] = 'Reorder level must be numeric';
  if (!empty($errors)) return [false, implode('; ', $errors)];
  return [true, 'OK'];
}

try {
  if ($action === 'create') {
    [$ok, $m] = validate_product($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('INSERT INTO product_details (sku,product_name,description,unit_price,stock_qty,reorder_level,category) VALUES (:sku,:nm,:desc,:price,:qty,:reorder,:cat)');
    $stmt->execute([
      ':sku'=>trim($_POST['sku']), ':nm'=>trim($_POST['product_name']), ':desc'=>trim($_POST['description'] ?? ''),
      ':price'=>(float)$_POST['unit_price'], ':qty'=>(int)$_POST['stock_qty'], ':reorder'=>(int)$_POST['reorder_level'], ':cat'=>trim($_POST['category'] ?? ''),
    ]);
    $msg = 'Product added';
  } elseif ($action === 'update') {
    $id = (int)($_POST['product_id'] ?? 0);
    [$ok, $m] = validate_product($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE product_details SET sku=:sku,product_name=:nm,description=:desc,unit_price=:price,stock_qty=:qty,reorder_level=:reorder,category=:cat WHERE product_id=:id');
    $stmt->execute([
      ':sku'=>trim($_POST['sku']), ':nm'=>trim($_POST['product_name']), ':desc'=>trim($_POST['description'] ?? ''),
      ':price'=>(float)$_POST['unit_price'], ':qty'=>(int)$_POST['stock_qty'], ':reorder'=>(int)$_POST['reorder_level'], ':cat'=>trim($_POST['category'] ?? ''), ':id'=>$id,
    ]);
    $msg = 'Product updated';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['product_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM product_details WHERE product_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Product deleted';
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId) {
  $st = $pdo->prepare('SELECT * FROM product_details WHERE product_id=:id');
  $st->execute([':id'=>$editId]);
  $editRow = $st->fetch(PDO::FETCH_ASSOC);
}

$rows = $pdo->query('SELECT * FROM product_details ORDER BY product_id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Products (Inventory)';
require __DIR__ . '/header.php';
?>
  <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
    <h2>Products (Inventory)</h2>
    <div><a href="index.php">Home</a></div>
  </div>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="err"><strong>Error:</strong> <?= e($err) ?></p><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <section>
      <h3><?= $editRow ? 'Edit Product #'.e((string)$editId) : 'Add Product' ?></h3>
      <form method="post">
        <?php if ($editRow): ?><input type="hidden" name="action" value="update" /><input type="hidden" name="product_id" value="<?= e((string)$editId) ?>" /><?php else: ?><input type="hidden" name="action" value="create" /><?php endif; ?>
        <label>SKU</label><br /><input name="sku" value="<?= e($editRow['sku'] ?? '') ?>" required />
        <br /><label>Name</label><br /><input name="product_name" value="<?= e($editRow['product_name'] ?? '') ?>" required />
        <br /><label>Description</label><br /><input name="description" value="<?= e($editRow['description'] ?? '') ?>" />
        <br /><label>Unit price</label><br /><input type="number" step="0.01" name="unit_price" value="<?= e((string)($editRow['unit_price'] ?? '0')) ?>" required />
        <br /><label>Stock qty</label><br /><input type="number" name="stock_qty" value="<?= e((string)($editRow['stock_qty'] ?? '0')) ?>" required />
        <br /><label>Reorder level</label><br /><input type="number" name="reorder_level" value="<?= e((string)($editRow['reorder_level'] ?? '0')) ?>" required />
        <br /><label>Category</label><br /><input name="category" value="<?= e($editRow['category'] ?? '') ?>" />
        <div style="margin-top:.5rem"></div>
        <button type="submit"><?= $editRow ? 'Update' : 'Create' ?></button>
        <?php if ($editRow): ?><a href="products.php" style="margin-left:.5rem">Cancel</a><?php endif; ?>
      </form>
    </section>

    <section>
      <h3>Inventory</h3>
      <table class="table">
        <thead><tr><th>ID</th><th>SKU</th><th>Name</th><th>Stock</th><th>Reorder</th><th>Price</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): $low = (int)$r['stock_qty'] <= (int)$r['reorder_level']; ?>
            <tr style="<?= $low ? 'background:#fff3cd;' : '' ?>">
              <td><?= e((string)$r['product_id']) ?></td>
              <td><?= e($r['sku']) ?></td>
              <td><?= e($r['product_name']) ?></td>
              <td><?= e((string)$r['stock_qty']) ?></td>
              <td><?= e((string)$r['reorder_level']) ?></td>
              <td><?= e((string)$r['unit_price']) ?></td>
              <td>
                <a href="products.php?edit=<?= e((string)$r['product_id']) ?>">Edit</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete product #<?= e((string)$r['product_id']) ?>?');">
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="product_id" value="<?= e((string)$r['product_id']) ?>" />
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
