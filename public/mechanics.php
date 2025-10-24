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
    $msg = 'Mechanic added successfully';
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
    $msg = 'Mechanic updated successfully';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['mechanic_id'] ?? 0);
    
    // Check if mechanic has work orders before deleting
    $checkStmt = $pdo->prepare('SELECT COUNT(*) as work_count FROM working_details WHERE assigned_mechanic_id = :id');
    $checkStmt->execute([':id' => $id]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['work_count'] > 0) {
      throw new RuntimeException('Cannot delete mechanic: This mechanic has ' . $result['work_count'] . ' work order(s). Please reassign the work orders first.');
    }
    
    $stmt = $pdo->prepare('DELETE FROM mechanics WHERE mechanic_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Mechanic deleted successfully';
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
$rows = $pdo->query('
  SELECT m.*, 
         COUNT(w.work_id) as work_count
  FROM mechanics m
  LEFT JOIN working_details w ON m.mechanic_id = w.assigned_mechanic_id
  GROUP BY m.mechanic_id
  ORDER BY m.mechanic_id DESC 
  LIMIT 200
')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Mechanics';
$current_page = 'mechanics';
require __DIR__ . '/header_modern.php';
?>

<!-- Page Header -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Mechanics</h1>
        <p class="text-muted">Manage mechanic information and assignments</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mechanicModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add Mechanic
        </button>
    </div>
</div>

<!-- Mechanics Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="mechanicsTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Specialty</th>
                        <th>Hourly Rate</th>
                        <th>Work Orders</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong>#<?= e((string)$r['mechanic_id']) ?></strong></td>
                        <td><?= e($r['first_name'].' '.$r['last_name']) ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td><?= e($r['phone'] ?: 'N/A') ?></td>
                        <td><?= e($r['specialty'] ?: 'General') ?></td>
                        <td>$<?= number_format($r['hourly_rate'], 2) ?>/hr</td>
                        <td>
                            <?php if ($r['work_count'] > 0): ?>
                                <span class="mf-badge mf-badge-info"><?= e((string)$r['work_count']) ?></span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['active']): ?>
                                <span class="mf-badge mf-badge-success">Active</span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm mf-btn-icon" onclick="editMechanic(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($r['work_count'] > 0): ?>
                                <button class="btn btn-sm mf-btn-icon" disabled title="Cannot delete: Has <?= e((string)$r['work_count']) ?> work order(s)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm mf-btn-icon" onclick="deleteMechanic(<?= e((string)$r['mechanic_id']) ?>, '<?= e($r['first_name'].' '.$r['last_name']) ?>')" title="Delete">
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

<!-- Mechanic Modal -->
<div class="modal fade" id="mechanicModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="mechanicForm" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Mechanic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="mechanic_id" id="mechanicId">
                    
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
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label for="specialty" class="form-label">Specialty</label>
                            <input type="text" class="form-control" id="specialty" name="specialty" placeholder="e.g., Engine Repair, Brakes">
                        </div>
                        <div class="col-md-6">
                            <label for="hourlyRate" class="form-label">Hourly Rate <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="hourlyRate" name="hourly_rate" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label for="managedBy" class="form-label">Managed By</label>
                            <select class="form-select" id="managedBy" name="managed_by">
                                <option value="">None</option>
                                <?php foreach ($managers as $m): ?>
                                    <option value="<?= $m['manager_id'] ?>"><?= e($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="hiredDate" class="form-label">Hired Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="hiredDate" name="hired_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                <label class="form-check-label" for="active">
                                    Active (available for work assignments)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><span id="submitBtn">Save Mechanic</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="mechanic_id" id="deleteMechanicId">
</form>

<script>
// Initialize DataTable
$(document).ready(function() {
    initDataTable('#mechanicsTable', {
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: 8 } // Actions column
        ]
    });
});

// Reset form for adding new mechanic
function resetForm() {
    document.getElementById('mechanicForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('mechanicId').value = '';
    document.getElementById('active').checked = true;
    document.getElementById('hiredDate').value = '<?= date('Y-m-d') ?>';
    document.getElementById('modalTitle').textContent = 'Add Mechanic';
    document.getElementById('submitBtn').textContent = 'Save Mechanic';
    document.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

// Edit mechanic
function editMechanic(mechanic) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('mechanicId').value = mechanic.mechanic_id;
    document.getElementById('firstName').value = mechanic.first_name;
    document.getElementById('lastName').value = mechanic.last_name;
    document.getElementById('email').value = mechanic.email;
    document.getElementById('phone').value = mechanic.phone || '';
    document.getElementById('specialty').value = mechanic.specialty || '';
    document.getElementById('hourlyRate').value = mechanic.hourly_rate;
    document.getElementById('managedBy').value = mechanic.managed_by || '';
    document.getElementById('hiredDate').value = mechanic.hired_date;
    document.getElementById('active').checked = mechanic.active == 1;
    document.getElementById('modalTitle').textContent = 'Edit Mechanic #' + mechanic.mechanic_id;
    document.getElementById('submitBtn').textContent = 'Update Mechanic';
    
    new bootstrap.Modal(document.getElementById('mechanicModal')).show();
}

// Delete mechanic
function deleteMechanic(id, name) {
    confirmDelete(
        `Are you sure you want to delete mechanic "${name}"?`,
        function() {
            document.getElementById('deleteMechanicId').value = id;
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
