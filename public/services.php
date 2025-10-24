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
    $msg = 'Service added successfully';
    header('Location: services.php?success=created');
    exit;
  } elseif ($action === 'update') {
    $id = (int)($_POST['service_id'] ?? 0);
    [$ok, $m] = validate_service($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE service_details SET service_name=:nm,description=:ds,base_price=:bp,estimated_hours=:eh,active=:ac WHERE service_id=:id');
    $stmt->execute([
      ':nm'=>trim($_POST['service_name']), ':ds'=>trim($_POST['description'] ?? ''), ':bp'=>(float)$_POST['base_price'], ':eh'=>(float)$_POST['estimated_hours'], ':ac'=>isset($_POST['active'])?1:0, ':id'=>$id,
    ]);
    $msg = 'Service updated successfully';
    header('Location: services.php?success=updated');
    exit;
  } elseif ($action === 'delete') {
    $id = (int)($_POST['service_id'] ?? 0);
    $stmt = $pdo->prepare('DELETE FROM service_details WHERE service_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Service deleted successfully';
    header('Location: services.php?success=deleted');
    exit;
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

// Handle success messages
if (isset($_GET['success'])) {
  switch ($_GET['success']) {
    case 'created': $msg = 'Service added successfully'; break;
    case 'updated': $msg = 'Service updated successfully'; break;
    case 'deleted': $msg = 'Service deleted successfully'; break;
  }
}

// Search functionality
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status_filter'] ?? '');

$whereConditions = [];
$params = [];

if ($search !== '') {
  $whereConditions[] = "(service_name LIKE :search OR description LIKE :search2)";
  $params[':search'] = '%' . $search . '%';
  $params[':search2'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
  $whereConditions[] = "active = :active";
  $params[':active'] = $statusFilter === 'active' ? 1 : 0;
}

$whereClause = '';
if (!empty($whereConditions)) {
  $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

$sql = "SELECT * FROM service_details" . $whereClause . " ORDER BY service_id DESC LIMIT 200";

if (!empty($params)) {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Services';
$current_page = 'services';
require __DIR__ . '/header_modern.php';
?>

<!-- Page Header -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Services</h1>
        <p class="text-muted">Manage service catalog and pricing</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add Service
        </button>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Service name or description" 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label for="statusFilter" class="form-label">Status</label>
                <select class="form-select" id="statusFilter" name="status_filter">
                    <option value="">All Services</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-2"></i>Search
                </button>
                <a href="services.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
        <?php if ($search || $statusFilter): ?>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-filter me-1"></i>
                    Showing <?= count($rows) ?> result(s)
                    <?php if ($search): ?>
                        | Search: <strong><?= htmlspecialchars($search) ?></strong>
                    <?php endif; ?>
                    <?php if ($statusFilter): ?>
                        | Status: <strong><?= $statusFilter === 'active' ? 'Active' : 'Inactive' ?></strong>
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Services Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="servicesTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Base Price</th>
                        <th>Est. Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong>#<?= e((string)$r['service_id']) ?></strong></td>
                        <td>
                            <strong><?= e($r['service_name']) ?></strong>
                            <?php if ($r['description']): ?>
                                <br><small class="text-muted"><?= e($r['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><strong>$<?= number_format($r['base_price'], 2) ?></strong></td>
                        <td><?= number_format($r['estimated_hours'], 2) ?> hrs</td>
                        <td>
                            <?php if ((int)$r['active'] === 1): ?>
                                <span class="mf-badge mf-badge-success">Active</span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm mf-btn-icon" onclick='editService(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm mf-btn-icon" onclick='deleteService(<?= (int)$r['service_id'] ?>, <?= htmlspecialchars(json_encode($r['service_name']), ENT_QUOTES) ?>)' title="Delete">
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

<!-- Service Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="serviceForm" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="service_id" id="serviceId">
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="serviceName" class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="serviceName" name="service_name" required>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="basePrice" class="form-label">Base Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="basePrice" name="base_price" required>
                        </div>
                        <div class="col-md-6">
                            <label for="estimatedHours" class="form-label">Estimated Hours <span class="text-danger">*</span></label>
                            <input type="number" step="0.25" min="0" class="form-control" id="estimatedHours" name="estimated_hours" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                <label class="form-check-label" for="active">
                                    Active (available for work orders)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><span id="submitBtn">Save Service</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="service_id" id="deleteServiceId">
</form>

<script>
$(document).ready(function() {
    initDataTable('#servicesTable', {
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: 5 }
        ]
    });
});

function resetForm() {
    document.getElementById('serviceForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('serviceId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Service';
    document.getElementById('submitBtn').textContent = 'Save Service';
    document.getElementById('active').checked = true;
    document.querySelectorAll('#serviceForm .is-invalid, #serviceForm .is-valid').forEach(el => {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

function editService(service) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('serviceId').value = service.service_id;
    document.getElementById('serviceName').value = service.service_name;
    document.getElementById('description').value = service.description || '';
    document.getElementById('basePrice').value = service.base_price;
    document.getElementById('estimatedHours').value = service.estimated_hours;
    document.getElementById('active').checked = parseInt(service.active) === 1;
    document.getElementById('modalTitle').textContent = 'Edit Service #' + service.service_id;
    document.getElementById('submitBtn').textContent = 'Update Service';
    
    const modal = new bootstrap.Modal(document.getElementById('serviceModal'));
    modal.show();
}

function deleteService(id, name) {
    confirmDelete(
        `Are you sure you want to delete this service?\n\nService: ${name}`,
        function() {
            document.getElementById('deleteServiceId').value = id;
            document.getElementById('deleteForm').submit();
        }
    );
}

<?php if ($msg): ?>
    showSuccess('<?= addslashes($msg) ?>');
<?php endif; ?>

<?php if ($err): ?>
    showError('<?= addslashes($err) ?>');
<?php endif; ?>
</script>

<?php require __DIR__ . '/footer_modern.php'; ?>
