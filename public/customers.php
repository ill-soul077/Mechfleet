<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;
$successType = '';

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
    $msg = 'Customer added successfully';
    $successType = 'create';
  } elseif ($action === 'update') {
    $id = (int)($_POST['customer_id'] ?? 0);
    [$ok, $m] = validate_customer($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE customer SET first_name=:fn,last_name=:ln,email=:em,phone=:ph,address=:ad,city=:ci,state=:st,zip_code=:zip WHERE customer_id=:id');
    $stmt->execute([
      ':fn'=>trim($_POST['first_name']), ':ln'=>trim($_POST['last_name']), ':em'=>trim($_POST['email']), ':ph'=>trim($_POST['phone']),
      ':ad'=>trim($_POST['address'] ?? ''), ':ci'=>trim($_POST['city'] ?? ''), ':st'=>trim($_POST['state'] ?? ''), ':zip'=>trim($_POST['zip_code'] ?? ''), ':id'=>$id,
    ]);
    $msg = 'Customer updated successfully';
    $successType = 'update';
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
    $successType = 'delete';
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
$current_page = 'customers';
require __DIR__ . '/header_modern.php';
?>

<!-- Page Header -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Customers</h1>
        <p class="text-muted">Manage customer information and records</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add Customer
        </button>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="customersTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Vehicles</th>
                        <th>Work Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong>#<?= e((string)$r['customer_id']) ?></strong></td>
                        <td><?= e($r['first_name'].' '.$r['last_name']) ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><?= e($r['city'] ?: 'N/A') ?></td>
                        <td>
                            <?php if ($r['vehicle_count'] > 0): ?>
                                <span class="mf-badge mf-badge-primary"><?= e((string)$r['vehicle_count']) ?></span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['work_count'] > 0): ?>
                                <span class="mf-badge mf-badge-info"><?= e((string)$r['work_count']) ?></span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm mf-btn-icon" onclick="editCustomer(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($r['vehicle_count'] > 0 || $r['work_count'] > 0): ?>
                                <button class="btn btn-sm mf-btn-icon" disabled title="Cannot delete: Has <?= e((string)$r['vehicle_count']) ?> vehicle(s) and <?= e((string)$r['work_count']) ?> work order(s)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm mf-btn-icon" onclick="deleteCustomer(<?= e((string)$r['customer_id']) ?>, '<?= e($r['first_name'].' '.$r['last_name']) ?>')" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="customerForm" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="customer_id" id="customerId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstName" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastName" name="last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address">
                        </div>
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" maxlength="2">
                        </div>
                        <div class="col-md-4">
                            <label for="zipCode" class="form-label">ZIP Code</label>
                            <input type="text" class="form-control" id="zipCode" name="zip_code">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><span id="submitBtn">Save Customer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="customer_id" id="deleteCustomerId">
</form>

<script>
// Initialize DataTable
$(document).ready(function() {
    initDataTable('#customersTable', {
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: 7 } // Actions column
        ]
    });
});

// Reset form for adding new customer
function resetForm() {
    document.getElementById('customerForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('customerId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Customer';
    document.getElementById('submitBtn').textContent = 'Save Customer';
    // Remove validation classes
    document.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

// Edit customer
function editCustomer(customer) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('customerId').value = customer.customer_id;
    document.getElementById('firstName').value = customer.first_name;
    document.getElementById('lastName').value = customer.last_name;
    document.getElementById('email').value = customer.email;
    document.getElementById('phone').value = customer.phone;
    document.getElementById('address').value = customer.address || '';
    document.getElementById('city').value = customer.city || '';
    document.getElementById('state').value = customer.state || '';
    document.getElementById('zipCode').value = customer.zip_code || '';
    document.getElementById('modalTitle').textContent = 'Edit Customer #' + customer.customer_id;
    document.getElementById('submitBtn').textContent = 'Update Customer';
    
    // Show modal
    new bootstrap.Modal(document.getElementById('customerModal')).show();
}

// Delete customer
function deleteCustomer(id, name) {
    confirmDelete(
        `Are you sure you want to delete customer "${name}"?`,
        function() {
            document.getElementById('deleteCustomerId').value = id;
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
