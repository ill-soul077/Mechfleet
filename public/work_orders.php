<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/business.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

function compute_labor_cost(PDO $pdo, int $mechanic_id, int $service_id): float {
  $st = $pdo->prepare('SELECT m.hourly_rate, s.estimated_hours FROM mechanics m, service_details s WHERE m.mechanic_id=:mid AND s.service_id=:sid');
  $st->execute([':mid'=>$mechanic_id, ':sid'=>$service_id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) return 0.0;
  return round((float)$row['hourly_rate'] * (float)$row['estimated_hours'], 2);
}

try {
  if ($action === 'create') {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $vehicle_id  = (int)($_POST['vehicle_id'] ?? 0);
    $mechanic_id = (int)($_POST['assigned_mechanic_id'] ?? 0);
    $service_id  = (int)($_POST['service_id'] ?? 0);
    $start_date  = trim($_POST['start_date'] ?? date('Y-m-d'));
    $status      = trim($_POST['status'] ?? 'pending');
    $notes       = trim($_POST['notes'] ?? '');
    if (!$customer_id || !$vehicle_id || !$mechanic_id || !$service_id) throw new RuntimeException('All fields are required');
    $labor = compute_labor_cost($pdo, $mechanic_id, $service_id);
    $parts_cost = 0.00;
    $total_cost = $labor + $parts_cost;
    $stmt = $pdo->prepare('INSERT INTO working_details (customer_id,vehicle_id,assigned_mechanic_id,service_id,status,labor_cost,parts_cost,total_cost,start_date,notes) VALUES (:c,:v,:m,:s,:st,:lc,:pc,:tc,:sd,:n)');
    $stmt->execute([':c'=>$customer_id, ':v'=>$vehicle_id, ':m'=>$mechanic_id, ':s'=>$service_id, ':st'=>$status, ':lc'=>$labor, ':pc'=>$parts_cost, ':tc'=>$total_cost, ':sd'=>$start_date, ':n'=>$notes]);
    $msg = 'Work order created successfully';
    // Redirect to detail view to clearly show the newly created record
    $newId = (int)$pdo->lastInsertId();
    // Fallback: if lastInsertId is not available, go back to list
    if ($newId > 0) {
      header('Location: work_orders.php?id=' . $newId . '&success=created');
    } else {
      // Redirect to prevent form resubmission
      header('Location: work_orders.php?success=created');
    }
    exit;
  } elseif ($action === 'update') {
    $work_id = (int)($_POST['work_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'pending');
    $completion_date = $_POST['completion_date'] !== '' ? $_POST['completion_date'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $stmt = $pdo->prepare('UPDATE working_details SET status=:st, completion_date=:cd, notes=:n WHERE work_id=:id');
    $stmt->execute([':st'=>$status, ':cd'=>$completion_date, ':n'=>$notes, ':id'=>$work_id]);
    // If marked completed, snapshot invoice totals now
    if ($status === 'completed') {
      createInvoiceForWork($pdo, $work_id);
    }
    $msg = 'Work order updated successfully';
    // Redirect back to detail view
    header('Location: work_orders.php?id='.$work_id.'&success=updated');
    exit;
  } elseif ($action === 'delete') {
    $work_id = (int)($_POST['work_id'] ?? 0);
    
    // Get work order details
    $checkStmt = $pdo->prepare('SELECT status, total_cost FROM working_details WHERE work_id = :id');
    $checkStmt->execute([':id' => $work_id]);
    $workOrder = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$workOrder) {
      throw new RuntimeException('Work order not found');
    }
    
    // Only allow deletion of completed work orders
    if ($workOrder['status'] !== 'completed') {
      throw new RuntimeException('Can only delete completed work orders. Current status: ' . $workOrder['status']);
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
      // Delete related work parts first
      $stmt = $pdo->prepare('DELETE FROM work_parts WHERE work_id = :id');
      $stmt->execute([':id' => $work_id]);
      
      // Delete related income records
      $stmt = $pdo->prepare('DELETE FROM income WHERE work_id = :id');
      $stmt->execute([':id' => $work_id]);
      
      // Delete the work order
      $stmt = $pdo->prepare('DELETE FROM working_details WHERE work_id = :id');
      $stmt->execute([':id' => $work_id]);
      
      $pdo->commit();
      $msg = 'Work order deleted successfully and removed from records';
      // Redirect to list view
      header('Location: work_orders.php?success=deleted');
      exit;
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }
} catch (Throwable $t) { 
  $err = $t->getMessage(); 
}

// Handle success messages from redirects
if (isset($_GET['success'])) {
  switch ($_GET['success']) {
    case 'created':
      $msg = 'Work order created successfully';
      break;
    case 'updated':
      $msg = 'Work order updated successfully';
      break;
    case 'deleted':
      $msg = 'Work order deleted successfully and removed from records';
      break;
  }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Data for forms
$customers = $pdo->query('SELECT customer_id, CONCAT(first_name, " ", last_name) AS name FROM customer ORDER BY customer_id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
$vehicles  = $pdo->query('SELECT v.vehicle_id, CONCAT(v.year, " ", v.make, " ", v.model, " (", c.first_name, " ", c.last_name, ")") AS label FROM vehicle v JOIN customer c ON c.customer_id=v.customer_id ORDER BY v.vehicle_id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
$mechanics = $pdo->query('SELECT mechanic_id, CONCAT(first_name, " ", last_name) AS name FROM mechanics WHERE active=1 ORDER BY mechanic_id')->fetchAll(PDO::FETCH_ASSOC);
$services  = $pdo->query('SELECT service_id, service_name FROM service_details WHERE active=1 ORDER BY service_id')->fetchAll(PDO::FETCH_ASSOC);

if ($id) {
  $job = $pdo->prepare('SELECT w.*, CONCAT(c.first_name, " ", c.last_name) AS customer_name, CONCAT(v.year, " ", v.make, " ", v.model) AS vehicle_info, CONCAT(m.first_name, " ", m.last_name) AS mechanic_name, s.service_name FROM working_details w JOIN customer c ON c.customer_id=w.customer_id JOIN vehicle v ON v.vehicle_id=w.vehicle_id JOIN mechanics m ON m.mechanic_id=w.assigned_mechanic_id JOIN service_details s ON s.service_id=w.service_id WHERE w.work_id=:id');
  $job->execute([':id'=>$id]);
  $jobRow = $job->fetch(PDO::FETCH_ASSOC);
  $parts = $pdo->prepare('SELECT wp.*, p.sku, p.product_name FROM work_parts wp JOIN product_details p ON p.product_id=wp.product_id WHERE wp.work_id=:id ORDER BY p.product_name');
  $parts->execute([':id'=>$id]);
  $partsRows = $parts->fetchAll(PDO::FETCH_ASSOC);
  $income = $pdo->prepare('SELECT * FROM income WHERE work_id=:id ORDER BY payment_date DESC');
  $income->execute([':id'=>$id]);
  $incomeRows = $income->fetchAll(PDO::FETCH_ASSOC);
}

// Search functionality
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status_filter'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

// Build WHERE clause for search
$whereConditions = [];
$params = [];

if ($search !== '') {
  $whereConditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE :search 
                         OR CONCAT(v.year, ' ', v.make, ' ', v.model) LIKE :search 
                         OR s.service_name LIKE :search)";
  $params[':search'] = '%' . $search . '%';
}

if ($statusFilter !== '') {
  $whereConditions[] = "w.status = :status";
  $params[':status'] = $statusFilter;
}

if ($dateFrom !== '') {
  $whereConditions[] = "w.start_date >= :date_from";
  $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
  $whereConditions[] = "w.start_date <= :date_to";
  $params[':date_to'] = $dateTo;
}

$whereClause = '';
if (!empty($whereConditions)) {
  $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

// Build and execute query
$sql = "SELECT w.work_id, w.status, w.start_date, w.completion_date, w.total_cost, 
        CONCAT(c.first_name, ' ', c.last_name) AS customer, 
        CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle, 
        s.service_name 
        FROM working_details w 
        JOIN customer c ON c.customer_id = w.customer_id 
        JOIN vehicle v ON v.vehicle_id = w.vehicle_id 
        JOIN service_details s ON s.service_id = w.service_id" 
        . $whereClause . " 
        ORDER BY w.work_id DESC 
        LIMIT 200";

if (!empty($params)) {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $list = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = $id ? ('Work Order #'.$id) : 'Work Orders';
$current_page = 'work_orders';
require __DIR__ . '/header_modern.php';
?>

  <?php if (!$id): ?>
    <!-- Page Header -->
    <div class="mf-content-header">
        <div>
            <h1 class="mf-page-title">Work Orders</h1>
            <p class="text-muted">Manage repair work orders and service requests</p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#workOrderModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Create Work Order
            </button>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Customer, Vehicle, or Service" 
                           value="<?= htmlspecialchars($search) ?>">
                    <small class="text-muted">Search by customer name, vehicle, or service</small>
                </div>
                <div class="col-md-2">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter" name="status_filter">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="dateFrom" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="dateFrom" name="date_from" 
                           value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="col-md-2">
                    <label for="dateTo" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="dateTo" name="date_to" 
                           value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="work_orders.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear
                    </a>
                </div>
            </form>
            <?php if ($search || $statusFilter || $dateFrom || $dateTo): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-filter me-1"></i>
                        Showing <?= count($list) ?> result(s)
                        <?php if ($search): ?>
                            | Search: <strong><?= htmlspecialchars($search) ?></strong>
                        <?php endif; ?>
                        <?php if ($statusFilter): ?>
                            | Status: <strong><?= ucfirst($statusFilter) ?></strong>
                        <?php endif; ?>
                        <?php if ($dateFrom || $dateTo): ?>
                            | Date Range: <strong><?= $dateFrom ?: 'Any' ?> to <?= $dateTo ?: 'Any' ?></strong>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Work Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="workOrdersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Service</th>
                            <th>Start Date</th>
                            <th>Status</th>
                            <th>Total Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $r): ?>
                        <tr>
                            <td><strong>#<?= e((string)$r['work_id']) ?></strong></td>
                            <td><?= e($r['customer']) ?></td>
                            <td><?= e($r['vehicle']) ?></td>
                            <td><?= e($r['service_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($r['start_date'])) ?></td>
                            <td>
                                <?php
                                $statusClass = 'secondary';
                                switch ($r['status']) {
                                    case 'completed': $statusClass = 'success'; break;
                                    case 'in_progress': $statusClass = 'warning'; break;
                                    case 'pending': $statusClass = 'info'; break;
                                    case 'cancelled': $statusClass = 'danger'; break;
                                }
                                ?>
                                <span class="mf-badge mf-badge-<?= $statusClass ?>">
                                    <?= e(ucfirst($r['status'])) ?>
                                </span>
                            </td>
                            <td>$<?= number_format($r['total_cost'], 2) ?></td>
                            <td>
                                <a href="work_orders.php?id=<?= e((string)$r['work_id']) ?>" class="btn btn-sm mf-btn-icon" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($r['status'] === 'completed'): ?>
                                    <button class="btn btn-sm mf-btn-icon text-danger" onclick='deleteWorkOrder(<?= (int)$r['work_id'] ?>, <?= htmlspecialchars(json_encode($r['customer'] . " - " . $r['vehicle']), ENT_QUOTES) ?>)' title="Delete (Completed)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm mf-btn-icon" disabled title="Cannot delete: Work order is <?= e($r['status']) ?>">
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

    <!-- Work Order Modal -->
    <div class="modal fade" id="workOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="workOrderForm" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Create Work Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="customerId" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customerId" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= e((string)$c['customer_id']) ?>"><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vehicleId" class="form-label">Vehicle <span class="text-danger">*</span></label>
                                <select class="form-select" id="vehicleId" name="vehicle_id" required>
                                    <option value="">Select Vehicle</option>
                                    <?php foreach ($vehicles as $v): ?>
                                        <option value="<?= e((string)$v['vehicle_id']) ?>"><?= e($v['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="mechanicId" class="form-label">Assigned Mechanic <span class="text-danger">*</span></label>
                                <select class="form-select" id="mechanicId" name="assigned_mechanic_id" required>
                                    <option value="">Select Mechanic</option>
                                    <?php foreach ($mechanics as $m): ?>
                                        <option value="<?= e((string)$m['mechanic_id']) ?>"><?= e($m['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="serviceId" class="form-label">Service <span class="text-danger">*</span></label>
                                <select class="form-select" id="serviceId" name="service_id" required>
                                    <option value="">Select Service</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?= e((string)$s['service_id']) ?>"><?= e($s['service_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="startDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="startDate" name="start_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Work Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="post" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="work_id" id="deleteWorkOrderId">
    </form>

    <script>
    // Initialize DataTable
    $(document).ready(function() {
        initDataTable('#workOrdersTable', {
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: 7 } // Actions column
            ]
        });
    });

    // Reset form for creating new work order
    function resetForm() {
        document.getElementById('workOrderForm').reset();
        document.getElementById('customerId').value = '';
        document.getElementById('vehicleId').value = '';
        document.getElementById('mechanicId').value = '';
        document.getElementById('serviceId').value = '';
        document.getElementById('status').value = 'pending';
        document.getElementById('startDate').value = '<?= date('Y-m-d') ?>';
        document.getElementById('notes').value = '';
        // Remove validation classes
        document.querySelectorAll('#workOrderForm .is-invalid, #workOrderForm .is-valid').forEach(el => {
            el.classList.remove('is-invalid', 'is-valid');
        });
    }

    // Delete work order
    function deleteWorkOrder(id, description) {
        confirmDelete(
            `Are you sure you want to delete this completed work order?\n\n${description}\n\nThis will remove the work order and all related records (parts, payments) from the database.`,
            function() {
                document.getElementById('deleteWorkOrderId').value = id;
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
  <?php else: ?>
    <?php if (!$jobRow): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>Work order not found.
      </div>
      <a href="work_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
    <?php else: ?>
      <!-- Page Header -->
      <div class="mf-content-header">
          <div>
              <h1 class="mf-page-title">Work Order #<?= $id ?></h1>
              <p class="text-muted">View and update work order details</p>
          </div>
          <div>
              <a href="work_orders.php" class="btn btn-secondary">
                  <i class="fas fa-arrow-left me-2"></i>Back to List
              </a>
          </div>
      </div>

      <!-- Work Order Details -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Information</h5>
            </div>
            <div class="card-body">
              <table class="table table-borderless mb-0">
                <tr>
                  <td width="40%"><strong>Customer:</strong></td>
                  <td><?= e($jobRow['customer_name']) ?></td>
                </tr>
                <tr>
                  <td><strong>Vehicle:</strong></td>
                  <td><?= e($jobRow['vehicle_info']) ?></td>
                </tr>
                <tr>
                  <td><strong>Mechanic:</strong></td>
                  <td><?= e($jobRow['mechanic_name']) ?></td>
                </tr>
                <tr>
                  <td><strong>Service:</strong></td>
                  <td><?= e($jobRow['service_name']) ?></td>
                </tr>
                <tr>
                  <td><strong>Start Date:</strong></td>
                  <td><?= date('M d, Y', strtotime($jobRow['start_date'])) ?></td>
                </tr>
                <tr>
                  <td><strong>Completion:</strong></td>
                  <td><?= $jobRow['completion_date'] ? date('M d, Y', strtotime($jobRow['completion_date'])) : 'Not completed' ?></td>
                </tr>
              </table>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-header bg-white">
              <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Cost Breakdown</h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span>Labor Cost:</span>
                <strong>$<?= number_format($jobRow['labor_cost'], 2) ?></strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Parts Cost:</span>
                <strong>$<?= number_format($jobRow['parts_cost'], 2) ?></strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between">
                <strong>Total Cost:</strong>
                <h4 class="text-primary mb-0">$<?= number_format($jobRow['total_cost'], 2) ?></h4>
              </div>
              <div class="mt-3">
                <?php
                $statusClass = 'secondary';
                switch ($jobRow['status']) {
                    case 'completed': $statusClass = 'success'; break;
                    case 'in_progress': $statusClass = 'warning'; break;
                    case 'pending': $statusClass = 'info'; break;
                    case 'cancelled': $statusClass = 'danger'; break;
                }
                ?>
                <span class="mf-badge mf-badge-<?= $statusClass ?>">
                    <?= e(ucfirst($jobRow['status'])) ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Update Form -->
      <div class="card mb-4">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Update Work Order</h5>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="work_id" value="<?= e((string)$id) ?>" />
            <div class="row g-3">
              <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" name="status" id="status">
                  <?php foreach (['pending','in_progress','completed','cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $st===$jobRow['status']?'selected':'' ?>><?= ucfirst($st) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label for="completion_date" class="form-label">Completion Date</label>
                <input type="date" class="form-control" name="completion_date" id="completion_date" value="<?= e((string)$jobRow['completion_date']) ?>" />
              </div>
              <div class="col-md-4">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-save me-2"></i>Save Changes
                </button>
              </div>
              <div class="col-12">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="notes" rows="2"><?= e($jobRow['notes'] ?? '') ?></textarea>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Parts Section -->
      <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Parts Used</h5>
          <button type="button" class="btn btn-sm btn-primary" onclick="openPartsModal(<?= (int)$id ?>)">
            <i class="fas fa-plus me-2"></i>Add Part
          </button>
        </div>
        <div class="card-body">
          <?php if (empty($partsRows)): ?>
            <div class="text-center py-3 text-muted">
              <i class="fas fa-box-open fa-2x mb-2"></i>
              <p>No parts added yet</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($partsRows as $p): ?>
                    <tr>
                      <td><code><?= e($p['sku']) ?></code></td>
                      <td><?= e($p['product_name']) ?></td>
                      <td><?= e((string)$p['quantity']) ?></td>
                      <td>$<?= number_format($p['unit_price'], 2) ?></td>
                      <td><strong>$<?= number_format($p['line_total'], 2) ?></strong></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Payments Section -->
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Records</h5>
        </div>
        <div class="card-body">
          <?php if (empty($incomeRows)): ?>
            <div class="text-center py-3 text-muted">
              <i class="fas fa-cash-register fa-2x mb-2"></i>
              <p>No payment records yet</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Tax</th>
                    <th>Transaction Ref</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($incomeRows as $inc): ?>
                    <tr>
                      <td><?= date('M d, Y', strtotime($inc['payment_date'])) ?></td>
                      <td><?= e($inc['payment_method']) ?></td>
                      <td>$<?= number_format($inc['amount'], 2) ?></td>
                      <td>$<?= number_format($inc['tax'], 2) ?></td>
                      <td><?= e($inc['transaction_reference'] ?? 'N/A') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <script>
      // Show notifications from PHP
      <?php if ($msg): ?>
          showSuccess('<?= addslashes($msg) ?>');
      <?php endif; ?>

      <?php if ($err): ?>
          showError('<?= addslashes($err) ?>');
      <?php endif; ?>
      </script>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Parts Modal -->
  <div class="modal fade" id="partsModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Part</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="modal-body">
          Loading...
        </div>
      </div>
    </div>
  </div>

  <script>
    function openPartsModal(workId){
      const modal = new bootstrap.Modal(document.getElementById('partsModal'));
      const body = document.getElementById('modal-body');
      body.textContent='Loading...';
      modal.show();
      fetch('work_parts_add.php?work_id='+encodeURIComponent(workId))
        .then(r=>r.text()).then(html=>{ body.innerHTML = html; });
    }
  </script>

<?php require __DIR__ . '/footer_modern.php'; ?>
