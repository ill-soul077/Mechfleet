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
    $msg = 'Product added successfully';
    header('Location: products.php?success=created');
    exit;
  } elseif ($action === 'update') {
    $id = (int)($_POST['product_id'] ?? 0);
    [$ok, $m] = validate_product($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE product_details SET sku=:sku,product_name=:nm,description=:desc,unit_price=:price,stock_qty=:qty,reorder_level=:reorder,category=:cat WHERE product_id=:id');
    $stmt->execute([
      ':sku'=>trim($_POST['sku']), ':nm'=>trim($_POST['product_name']), ':desc'=>trim($_POST['description'] ?? ''),
      ':price'=>(float)$_POST['unit_price'], ':qty'=>(int)$_POST['stock_qty'], ':reorder'=>(int)$_POST['reorder_level'], ':cat'=>trim($_POST['category'] ?? ''), ':id'=>$id,
    ]);
    $msg = 'Product updated successfully';
    header('Location: products.php?success=updated');
    exit;
  } elseif ($action === 'delete') {
    $id = (int)($_POST['product_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM product_details WHERE product_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Product deleted successfully';
    header('Location: products.php?success=deleted');
    exit;
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

// Handle success messages from redirects
if (isset($_GET['success'])) {
  switch ($_GET['success']) {
    case 'created':
      $msg = 'Product added successfully';
      break;
    case 'updated':
      $msg = 'Product updated successfully';
      break;
    case 'deleted':
      $msg = 'Product deleted successfully';
      break;
  }
}

// Search functionality
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$stockFilter = trim($_GET['stock_filter'] ?? '');

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($search !== '') {
  $whereConditions[] = "(sku LIKE :search OR product_name LIKE :search OR description LIKE :search)";
  $params[':search'] = '%' . $search . '%';
}

if ($category !== '') {
  $whereConditions[] = "category = :category";
  $params[':category'] = $category;
}

if ($stockFilter === 'low') {
  $whereConditions[] = "stock_qty <= reorder_level";
} elseif ($stockFilter === 'out') {
  $whereConditions[] = "stock_qty = 0";
}

$whereClause = '';
if (!empty($whereConditions)) {
  $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

// Get all categories for filter dropdown
$categories = $pdo->query('SELECT DISTINCT category FROM product_details WHERE category IS NOT NULL AND category != "" ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

// Build and execute query
$sql = "SELECT * FROM product_details" . $whereClause . " ORDER BY product_id DESC LIMIT 200";

if (!empty($params)) {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Products & Inventory';
$current_page = 'products';
require __DIR__ . '/header_modern.php';
?>

<!-- Page Header -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Products & Inventory</h1>
        <p class="text-muted">Manage product catalog and stock levels</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add Product
        </button>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="SKU, Name, or Description" 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label for="categoryFilter" class="form-label">Category</label>
                <select class="form-select" id="categoryFilter" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="stockFilter" class="form-label">Stock Status</label>
                <select class="form-select" id="stockFilter" name="stock_filter">
                    <option value="">All Stock</option>
                    <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-2"></i>Search
                </button>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
        <?php if ($search || $category || $stockFilter): ?>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-filter me-1"></i>
                    Showing <?= count($rows) ?> result(s)
                    <?php if ($search): ?>
                        | Search: <strong><?= htmlspecialchars($search) ?></strong>
                    <?php endif; ?>
                    <?php if ($category): ?>
                        | Category: <strong><?= htmlspecialchars($category) ?></strong>
                    <?php endif; ?>
                    <?php if ($stockFilter): ?>
                        | Stock: <strong><?= $stockFilter === 'low' ? 'Low Stock' : 'Out of Stock' ?></strong>
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="productsTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Reorder Level</th>
                        <th>Unit Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): 
                        $lowStock = (int)$r['stock_qty'] <= (int)$r['reorder_level'];
                        $outOfStock = (int)$r['stock_qty'] === 0;
                    ?>
                    <tr>
                        <td><strong>#<?= e((string)$r['product_id']) ?></strong></td>
                        <td><code><?= e($r['sku']) ?></code></td>
                        <td>
                            <strong><?= e($r['product_name']) ?></strong>
                            <?php if ($r['description']): ?>
                                <br><small class="text-muted"><?= e($r['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($r['category'] ?: 'N/A') ?></td>
                        <td>
                            <?php if ($outOfStock): ?>
                                <span class="mf-badge mf-badge-danger"><?= e((string)$r['stock_qty']) ?> (Out)</span>
                            <?php elseif ($lowStock): ?>
                                <span class="mf-badge mf-badge-warning"><?= e((string)$r['stock_qty']) ?> (Low)</span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-success"><?= e((string)$r['stock_qty']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e((string)$r['reorder_level']) ?></td>
                        <td><strong>$<?= number_format($r['unit_price'], 2) ?></strong></td>
                        <td>
                            <button class="btn btn-sm mf-btn-icon" onclick='editProduct(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm mf-btn-icon" onclick='deleteProduct(<?= (int)$r['product_id'] ?>, <?= htmlspecialchars(json_encode($r['product_name']), ENT_QUOTES) ?>)' title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="productForm" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="product_id" id="productId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sku" name="sku" required>
                        </div>
                        <div class="col-md-6">
                            <label for="productName" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="productName" name="product_name" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" list="categoryList">
                            <datalist id="categoryList">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label for="unitPrice" class="form-label">Unit Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="unitPrice" name="unit_price" required>
                        </div>
                        <div class="col-md-6">
                            <label for="stockQty" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" id="stockQty" name="stock_qty" required>
                        </div>
                        <div class="col-md-6">
                            <label for="reorderLevel" class="form-label">Reorder Level <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" id="reorderLevel" name="reorder_level" required>
                            <small class="text-muted">Alert when stock falls to this level</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><span id="submitBtn">Save Product</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="product_id" id="deleteProductId">
</form>

<script>
// Initialize DataTable
$(document).ready(function() {
    initDataTable('#productsTable', {
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: 7 } // Actions column
        ]
    });
});

// Reset form for creating new product
function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('productId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('submitBtn').textContent = 'Save Product';
    // Remove validation classes
    document.querySelectorAll('#productForm .is-invalid, #productForm .is-valid').forEach(el => {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

// Edit product
function editProduct(product) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('productId').value = product.product_id;
    document.getElementById('sku').value = product.sku;
    document.getElementById('productName').value = product.product_name;
    document.getElementById('description').value = product.description || '';
    document.getElementById('category').value = product.category || '';
    document.getElementById('unitPrice').value = product.unit_price;
    document.getElementById('stockQty').value = product.stock_qty;
    document.getElementById('reorderLevel').value = product.reorder_level;
    document.getElementById('modalTitle').textContent = 'Edit Product #' + product.product_id;
    document.getElementById('submitBtn').textContent = 'Update Product';
    
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

// Delete product
function deleteProduct(id, name) {
    confirmDelete(
        `Are you sure you want to delete this product?\n\nProduct: ${name}`,
        function() {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteForm').submit();
        }
    );
}

// Show notifications from PHP
<?php if ($msg): ?>
    showSuccess('<?= addslashes($msg) ?>');
<?php endif; ?>

<?php if ($err): ?>
    showError('<?= addslashes($err) ?>');
<?php endif; ?>
</script>

<?php require __DIR__ . '/footer_modern.php'; ?>
