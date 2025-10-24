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
    $msg = 'Vehicle added successfully';
  } elseif ($action === 'update') {
    $id = (int)($_POST['vehicle_id'] ?? 0);
    [$ok, $m] = validate_vehicle($_POST);
    if (!$ok) throw new RuntimeException($m);
    $stmt = $pdo->prepare('UPDATE vehicle SET customer_id=:cid, vin=:vin, make=:mk, model=:md, year=:yr, color=:cl, mileage=:mi, license_plate=:lp WHERE vehicle_id=:id');
    $stmt->execute([
      ':cid'=>(int)$_POST['customer_id'], ':vin'=>trim($_POST['vin']), ':mk'=>trim($_POST['make']), ':md'=>trim($_POST['model']), ':yr'=>(int)$_POST['year'], ':cl'=>trim($_POST['color'] ?? ''), ':mi'=>(int)$_POST['mileage'], ':lp'=>trim($_POST['license_plate'] ?? ''), ':id'=>$id,
    ]);
    $msg = 'Vehicle updated successfully';
  } elseif ($action === 'delete') {
    $id = (int)($_POST['vehicle_id'] ?? 0);
    
    // Check if vehicle has work orders before deleting
    $checkStmt = $pdo->prepare('SELECT COUNT(*) as work_count FROM working_details WHERE vehicle_id = :id');
    $checkStmt->execute([':id' => $id]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['work_count'] > 0) {
      throw new RuntimeException('Cannot delete vehicle: This vehicle has ' . $result['work_count'] . ' work order(s). Please delete the work orders first.');
    }
    
    $stmt = $pdo->prepare('DELETE FROM vehicle WHERE vehicle_id=:id');
    $stmt->execute([':id'=>$id]);
    $msg = 'Vehicle deleted successfully';
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

// Search functionality
$search = trim($_GET['search'] ?? '');
$make = trim($_GET['make'] ?? '');
$year = trim($_GET['year'] ?? '');

$whereConditions = [];
$params = [];

if ($search !== '') {
  $whereConditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE :search OR v.vin LIKE :search OR v.license_plate LIKE :search OR v.model LIKE :search)";
  $params[':search'] = '%' . $search . '%';
}

if ($make !== '') {
  $whereConditions[] = "v.make LIKE :make";
  $params[':make'] = '%' . $make . '%';
}

if ($year !== '') {
  $whereConditions[] = "v.year = :year";
  $params[':year'] = $year;
}

$whereClause = '';
if (!empty($whereConditions)) {
  $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

$sql = "SELECT v.*, 
         CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
         COUNT(w.work_id) as work_count
  FROM vehicle v 
  JOIN customer c ON c.customer_id = v.customer_id 
  LEFT JOIN working_details w ON v.vehicle_id = w.vehicle_id"
  . $whereClause . "
  GROUP BY v.vehicle_id
  ORDER BY v.vehicle_id DESC 
  LIMIT 200";

if (!empty($params)) {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Vehicles';
$current_page = 'vehicles';
require __DIR__ . '/header_modern.php';
?>

<!-- Page Header -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Vehicles</h1>
        <p class="text-muted">Manage vehicle information and records</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vehicleModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add Vehicle
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
                       placeholder="Owner, VIN, License Plate, or Model" 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label for="make" class="form-label">Make</label>
                <input type="text" class="form-control" id="make" name="make" 
                       placeholder="e.g., Toyota, Honda" 
                       value="<?= htmlspecialchars($make) ?>">
            </div>
            <div class="col-md-2">
                <label for="year" class="form-label">Year</label>
                <input type="number" class="form-control" id="year" name="year" 
                       placeholder="e.g., 2020" min="1900" max="2100"
                       value="<?= htmlspecialchars($year) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-2"></i>Search
                </button>
                <a href="vehicles.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
        <?php if ($search || $make || $year): ?>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-filter me-1"></i>
                    Showing <?= count($rows) ?> result(s)
                    <?php if ($search): ?>
                        | Search: <strong><?= htmlspecialchars($search) ?></strong>
                    <?php endif; ?>
                    <?php if ($make): ?>
                        | Make: <strong><?= htmlspecialchars($make) ?></strong>
                    <?php endif; ?>
                    <?php if ($year): ?>
                        | Year: <strong><?= htmlspecialchars($year) ?></strong>
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Vehicles Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="vehiclesTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Owner</th>
                        <th>Vehicle</th>
                        <th>VIN</th>
                        <th>License Plate</th>
                        <th>Mileage</th>
                        <th>Work Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong>#<?= e((string)$r['vehicle_id']) ?></strong></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td>
                            <strong><?= e($r['year'].' '.$r['make'].' '.$r['model']) ?></strong>
                            <?php if ($r['color']): ?>
                                <br><small class="text-muted"><?= e($r['color']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($r['vin']) ?></code></td>
                        <td><?= e($r['license_plate'] ?: 'N/A') ?></td>
                        <td><?= number_format($r['mileage']) ?> mi</td>
                        <td>
                            <?php if ($r['work_count'] > 0): ?>
                                <span class="mf-badge mf-badge-info"><?= e((string)$r['work_count']) ?></span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm mf-btn-icon" onclick='editVehicle(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)' title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($r['work_count'] > 0): ?>
                                <button class="btn btn-sm mf-btn-icon" disabled title="Cannot delete: Has <?= e((string)$r['work_count']) ?> work order(s)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm mf-btn-icon" onclick='deleteVehicle(<?= (int)$r['vehicle_id'] ?>, <?= htmlspecialchars(json_encode($r['year'].' '.$r['make'].' '.$r['model']), ENT_QUOTES) ?>)' title="Delete">
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

<!-- Vehicle Modal -->
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="vehicleForm" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="vehicle_id" id="vehicleId">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="customerId" class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select" id="customerId" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['customer_id'] ?>"><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="vin" class="form-label">VIN (17 characters) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vin" name="vin" maxlength="17" required>
                        </div>
                        <div class="col-md-6">
                            <label for="licensePlate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="licensePlate" name="license_plate">
                        </div>
                        <div class="col-md-4">
                            <label for="make" class="form-label">Make <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="make" name="make" required>
                        </div>
                        <div class="col-md-4">
                            <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="model" name="model" required>
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="year" name="year" min="1900" max="2100" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color">
                        </div>
                        <div class="col-md-6">
                            <label for="mileage" class="form-label">Mileage</label>
                            <input type="number" class="form-control" id="mileage" name="mileage" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><span id="submitBtn">Save Vehicle</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" method="post" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="vehicle_id" id="deleteVehicleId">
</form>

<script>
// Initialize DataTable
$(document).ready(function() {
    initDataTable('#vehiclesTable', {
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: 7 } // Actions column
        ]
    });
});

// Reset form for adding new vehicle
function resetForm() {
    document.getElementById('vehicleForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('vehicleId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Vehicle';
    document.getElementById('submitBtn').textContent = 'Save Vehicle';
    document.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

// Edit vehicle
function editVehicle(vehicle) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('vehicleId').value = vehicle.vehicle_id;
    document.getElementById('customerId').value = vehicle.customer_id;
    document.getElementById('vin').value = vehicle.vin;
    document.getElementById('licensePlate').value = vehicle.license_plate || '';
    document.getElementById('make').value = vehicle.make;
    document.getElementById('model').value = vehicle.model;
    document.getElementById('year').value = vehicle.year;
    document.getElementById('color').value = vehicle.color || '';
    document.getElementById('mileage').value = vehicle.mileage;
    document.getElementById('modalTitle').textContent = 'Edit Vehicle #' + vehicle.vehicle_id;
    document.getElementById('submitBtn').textContent = 'Update Vehicle';
    
    new bootstrap.Modal(document.getElementById('vehicleModal')).show();
}

// Delete vehicle
function deleteVehicle(id, name) {
    confirmDelete(
        `Are you sure you want to delete vehicle "${name}"?`,
        function() {
            document.getElementById('deleteVehicleId').value = id;
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
