<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$msg = null; $err = null;
$action = $_POST['action'] ?? '';

try {
  if ($action === 'create') {
    $work_id = (int)($_POST['work_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $method = trim($_POST['payment_method'] ?? 'cash');
    $date = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $ref = trim($_POST['transaction_reference'] ?? '');
    if (!$work_id) throw new RuntimeException('Work order required');
    if ($amount <= 0) throw new RuntimeException('Amount must be > 0');
    if ($tax < 0) throw new RuntimeException('Tax must be >= 0');
    $allowed = ['cash','credit_card','debit_card','check','bank_transfer'];
    if (!in_array($method, $allowed, true)) throw new RuntimeException('Invalid payment method');
    $chk = $pdo->prepare('SELECT status FROM working_details WHERE work_id=:id');
    $chk->execute([':id'=>$work_id]);
    $st = $chk->fetchColumn();
    if ($st === false) throw new RuntimeException('Work order not found');
    $ins = $pdo->prepare('INSERT INTO income (work_id, amount, tax, payment_method, payment_date, transaction_reference) VALUES (:w,:a,:t,:m,:d,:r)');
    $ins->execute([':w'=>$work_id, ':a'=>$amount, ':t'=>$tax, ':m'=>$method, ':d'=>$date, ':r'=>$ref !== '' ? $ref : null]);
    $msg = 'Payment recorded successfully';
    header('Location: income.php?success=created');
    exit;
  }
} catch (Throwable $t) { $err = $t->getMessage(); }

if (isset($_GET['success']) && $_GET['success'] === 'created') {
  $msg = 'Payment recorded successfully';
}

// Search functionality
$search = trim($_GET['search'] ?? '');
$method = trim($_GET['method'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$whereConditions = [];
$params = [];

if ($search !== '') {
  $whereConditions[] = "(CONCAT(c.first_name, ' ', c.last_name) LIKE :search OR s.service_name LIKE :search2 OR i.transaction_reference LIKE :search3)";
  $params[':search'] = '%' . $search . '%';
  $params[':search2'] = '%' . $search . '%';
  $params[':search3'] = '%' . $search . '%';
}

if ($method !== '') {
  $whereConditions[] = "i.payment_method = :method";
  $params[':method'] = $method;
}

if ($dateFrom !== '') {
  $whereConditions[] = "i.payment_date >= :date_from";
  $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
  $whereConditions[] = "i.payment_date <= :date_to";
  $params[':date_to'] = $dateTo;
}

$whereClause = '';
if (!empty($whereConditions)) {
  $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
}

$sql = "SELECT i.*, 
        CONCAT(c.first_name, ' ', c.last_name) AS customer, 
        s.service_name,
        w.work_id
        FROM income i 
        JOIN working_details w ON w.work_id = i.work_id 
        JOIN customer c ON c.customer_id = w.customer_id 
        JOIN service_details s ON s.service_id = w.service_id"
        . $whereClause . " 
        ORDER BY i.payment_date DESC, i.income_id DESC 
        LIMIT 200";

if (!empty($params)) {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $recent = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$workOrders = $pdo->query('SELECT w.work_id, CONCAT("#", w.work_id, " — ", c.first_name, " ", c.last_name, " — ", s.service_name, " ($", w.total_cost, ")") AS label FROM working_details w JOIN customer c ON c.customer_id=w.customer_id JOIN service_details s ON s.service_id=w.service_id ORDER BY w.work_id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Income & Payments';
$current_page = 'income';
require __DIR__ . '/header_modern.php';
?>

<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Income & Payments</h1>
        <p class="text-muted">Record and track customer payments</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="fas fa-plus me-2"></i>Record Payment
        </button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Customer, Service, or Ref" 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label for="methodFilter" class="form-label">Payment Method</label>
                <select class="form-select" id="methodFilter" name="method">
                    <option value="">All Methods</option>
                    <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="credit_card" <?= $method === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                    <option value="debit_card" <?= $method === 'debit_card' ? 'selected' : '' ?>>Debit Card</option>
                    <option value="check" <?= $method === 'check' ? 'selected' : '' ?>>Check</option>
                    <option value="bank_transfer" <?= $method === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="dateFrom" class="form-label">From Date</label>
                <input type="date" class="form-control" id="dateFrom" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label for="dateTo" class="form-label">To Date</label>
                <input type="date" class="form-control" id="dateTo" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-2"></i>Search
                </button>
                <a href="income.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Clear
                </a>
            </div>
        </form>
        <?php if ($search || $method || $dateFrom || $dateTo): ?>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-filter me-1"></i>
                    Showing <?= count($recent) ?> result(s)
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($recent)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-cash-register fa-3x mb-3"></i>
                <p>No payment records found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="incomeTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Tax</th>
                            <th>Work Order</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($r['payment_date'])) ?></td>
                            <td><?= e($r['customer']) ?></td>
                            <td><?= e($r['service_name']) ?></td>
                            <td>
                                <span class="mf-badge mf-badge-info">
                                    <?= ucwords(str_replace('_', ' ', $r['payment_method'])) ?>
                                </span>
                            </td>
                            <td><strong>$<?= number_format($r['amount'], 2) ?></strong></td>
                            <td>$<?= number_format($r['tax'], 2) ?></td>
                            <td><a href="work_orders.php?id=<?= e((string)$r['work_id']) ?>">#<?= e((string)$r['work_id']) ?></a></td>
                            <td><?= e($r['transaction_reference'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="workOrder" class="form-label">Work Order <span class="text-danger">*</span></label>
                            <select class="form-select" id="workOrder" name="work_id" required>
                                <option value="">-- Select Work Order --</option>
                                <?php foreach ($workOrders as $w): ?>
                                    <option value="<?= e((string)$w['work_id']) ?>"><?= e($w['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tax" class="form-label">Tax</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="tax" name="tax" value="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="paymentMethod" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="paymentMethod" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="check">Check</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="paymentDate" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="paymentDate" name="payment_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="transactionRef" class="form-label">Transaction Reference</label>
                            <input type="text" class="form-control" id="transactionRef" name="transaction_reference" placeholder="Check number, confirmation code, etc.">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#incomeTable', {
        order: [[0, 'desc']]
    });
});

<?php if ($msg): ?>
    showSuccess('<?= addslashes($msg) ?>');
<?php endif; ?>

<?php if ($err): ?>
    showError('<?= addslashes($err) ?>');
<?php endif; ?>
</script>

<?php require __DIR__ . '/footer_modern.php'; ?>
