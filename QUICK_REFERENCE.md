# Quick Reference: Modernizing Remaining Pages

## 📝 Step-by-Step Pattern

Follow these steps to modernize any page in your application:

---

## 1️⃣ Update Page Header

### Replace Old Header
```php
// OLD
$pageTitle = 'Page Name';
require __DIR__ . '/header.php';
```

### With New Header
```php
// NEW
$pageTitle = 'Page Name';
$current_page = 'page_name';  // Must match href in header_modern.php sidebar
require __DIR__ . '/header_modern.php';
```

---

## 2️⃣ Update Page Footer

### Replace Old Footer
```php
// OLD
<?php require __DIR__ . '/footer.php'; ?>
```

### With New Footer
```php
// NEW
<?php require __DIR__ . '/footer_modern.php'; ?>
```

---

## 3️⃣ Add Page Header Section

After the PHP header include, add:

```php
<!-- Page Header -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Page Name</h1>
        <p class="text-muted">Page description</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yourModal">
            <i class="fas fa-plus me-2"></i>Add Item
        </button>
    </div>
</div>
```

---

## 4️⃣ Convert Table to Card with DataTables

### Old Table
```php
<table class="table">
    <thead>
        <tr><th>Col 1</th><th>Col 2</th></tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['field1']) ?></td>
            <td><?= e($r['field2']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### New Table with Card
```php
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="yourTable" class="table table-hover">
                <thead>
                    <tr><th>Col 1</th><th>Col 2</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['field1']) ?></td>
                        <td><?= e($r['field2']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#yourTable', {
        order: [[0, 'desc']]
    });
});
</script>
```

---

## 5️⃣ Convert Form to Modal

### Old Inline Form
```php
<form method="post">
    <label>Field Name</label><br />
    <input name="field" value="" required />
    <button type="submit">Submit</button>
</form>
```

### New Modal Form
```php
<!-- Modal -->
<div class="modal fade" id="yourModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="yourForm" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="item_id" id="itemId">
                    
                    <div class="mb-3">
                        <label for="fieldName" class="form-label">Field Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fieldName" name="field" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

## 6️⃣ Add Action Buttons with Icons

### Old Action Links
```php
<a href="?edit=<?= $id ?>">Edit</a>
<form method="post" style="display:inline">
    <input type="hidden" name="action" value="delete">
    <button type="submit">Delete</button>
</form>
```

### New Icon Buttons
```php
<button class="btn btn-sm mf-btn-icon" onclick="editItem(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)" title="Edit">
    <i class="fas fa-edit"></i>
</button>
<button class="btn btn-sm mf-btn-icon" onclick="deleteItem(<?= $id ?>, '<?= e($name) ?>')" title="Delete">
    <i class="fas fa-trash-alt"></i>
</button>
```

---

## 7️⃣ Add JavaScript Functions

Add before closing `</body>` or in separate script:

```javascript
// Reset form for adding new item
function resetForm() {
    document.getElementById('yourForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('itemId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Item';
}

// Edit item
function editItem(item) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('itemId').value = item.id;
    document.getElementById('fieldName').value = item.field;
    document.getElementById('modalTitle').textContent = 'Edit Item #' + item.id;
    
    new bootstrap.Modal(document.getElementById('yourModal')).show();
}

// Delete item
function deleteItem(id, name) {
    confirmDelete(
        `Are you sure you want to delete "${name}"?`,
        function() {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="item_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
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
```

---

## 8️⃣ Add Badges for Status/Counts

### Status Badges
```php
<?php
$statusClass = 'secondary';
switch ($status) {
    case 'Completed': $statusClass = 'success'; break;
    case 'In Progress': $statusClass = 'warning'; break;
    case 'Pending': $statusClass = 'info'; break;
    case 'Cancelled': $statusClass = 'danger'; break;
}
?>
<span class="mf-badge mf-badge-<?= $statusClass ?>">
    <?= e($status) ?>
</span>
```

### Count Badges
```php
<?php if ($count > 0): ?>
    <span class="mf-badge mf-badge-primary"><?= $count ?></span>
<?php else: ?>
    <span class="mf-badge mf-badge-secondary">0</span>
<?php endif; ?>
```

---

## 9️⃣ Replace Alerts with Toastr

### Remove Old Alerts
```php
// OLD - Remove this
<?php if ($msg): ?>
    <p class="ok"><?= e($msg) ?></p>
<?php endif; ?>

<?php if ($err): ?>
    <p class="err"><?= e($err) ?></p>
<?php endif; ?>
```

### Add Toastr Script
```javascript
// NEW - Add this in script section
<?php if ($msg): ?>
    showSuccess('<?= addslashes($msg) ?>');
<?php endif; ?>

<?php if ($err): ?>
    showError('<?= addslashes($err) ?>');
<?php endif; ?>
```

---

## 🔟 Update Delete with Validation

### PHP Backend
```php
elseif ($action === 'delete') {
    $id = (int)($_POST['item_id'] ?? 0);
    
    // Check for related records
    $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM related_table WHERE item_id = :id');
    $checkStmt->execute([':id' => $id]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        throw new RuntimeException('Cannot delete: This item has ' . $result['count'] . ' related record(s).');
    }
    
    // Safe to delete
    $stmt = $pdo->prepare('DELETE FROM your_table WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $msg = 'Item deleted successfully';
}
```

### Frontend (Disable Button)
```php
<?php if ($hasRelatedRecords): ?>
    <button class="btn btn-sm mf-btn-icon" disabled title="Cannot delete: Has related records">
        <i class="fas fa-trash-alt"></i>
    </button>
<?php else: ?>
    <button class="btn btn-sm mf-btn-icon" onclick="deleteItem(<?= $id ?>, '<?= e($name) ?>')">
        <i class="fas fa-trash-alt"></i>
    </button>
<?php endif; ?>
```

---

## 📊 Example: Vehicles Page

Here's a complete example for `vehicles.php`:

```php
<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

// Handle CRUD operations
try {
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO vehicle (vehicle_name, vehicle_no, customer_id) VALUES (:name, :no, :cid)');
        $stmt->execute([
            ':name' => trim($_POST['vehicle_name']),
            ':no' => trim($_POST['vehicle_no']),
            ':cid' => (int)$_POST['customer_id']
        ]);
        $msg = 'Vehicle added successfully';
    } elseif ($action === 'update') {
        $id = (int)$_POST['vehicle_id'];
        $stmt = $pdo->prepare('UPDATE vehicle SET vehicle_name=:name, vehicle_no=:no, customer_id=:cid WHERE vehicle_id=:id');
        $stmt->execute([
            ':name' => trim($_POST['vehicle_name']),
            ':no' => trim($_POST['vehicle_no']),
            ':cid' => (int)$_POST['customer_id'],
            ':id' => $id
        ]);
        $msg = 'Vehicle updated successfully';
    } elseif ($action === 'delete') {
        $id = (int)$_POST['vehicle_id'];
        
        // Check work orders
        $checkStmt = $pdo->prepare('SELECT COUNT(*) as count FROM working_details WHERE vehicle_id = :id');
        $checkStmt->execute([':id' => $id]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new RuntimeException('Cannot delete: Vehicle has ' . $result['count'] . ' work order(s).');
        }
        
        $stmt = $pdo->prepare('DELETE FROM vehicle WHERE vehicle_id=:id');
        $stmt->execute([':id' => $id]);
        $msg = 'Vehicle deleted successfully';
    }
} catch (Throwable $t) {
    $err = $t->getMessage();
}

// Fetch data
$rows = $pdo->query('
    SELECT v.*, 
           c.customer_name,
           COUNT(w.work_id) as work_count
    FROM vehicle v
    LEFT JOIN customer c ON v.customer_id = c.customer_id
    LEFT JOIN working_details w ON v.vehicle_id = w.vehicle_id
    GROUP BY v.vehicle_id
    ORDER BY v.vehicle_id DESC
')->fetchAll(PDO::FETCH_ASSOC);

$customers = $pdo->query('SELECT customer_id, customer_name FROM customer ORDER BY customer_name')->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Vehicles';
$current_page = 'vehicles';
require __DIR__ . '/header_modern.php';
?>

<!-- Page Header -->
<div class="mf-content-header">
    <div>
        <h1 class="mf-page-title">Vehicles</h1>
        <p class="text-muted">Manage vehicle information</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vehicleModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Add Vehicle
        </button>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="vehiclesTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vehicle Name</th>
                        <th>Vehicle Number</th>
                        <th>Customer</th>
                        <th>Work Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><strong>#<?= e($r['vehicle_id']) ?></strong></td>
                        <td><?= e($r['vehicle_name']) ?></td>
                        <td><?= e($r['vehicle_no']) ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td>
                            <?php if ($r['work_count'] > 0): ?>
                                <span class="mf-badge mf-badge-info"><?= $r['work_count'] ?></span>
                            <?php else: ?>
                                <span class="mf-badge mf-badge-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm mf-btn-icon" onclick="editVehicle(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($r['work_count'] > 0): ?>
                                <button class="btn btn-sm mf-btn-icon" disabled title="Has work orders">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm mf-btn-icon" onclick="deleteVehicle(<?= $r['vehicle_id'] ?>, '<?= e($r['vehicle_name']) ?>')">
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

<!-- Modal -->
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="vehicle_id" id="vehicleId">
                    
                    <div class="mb-3">
                        <label class="form-label">Vehicle Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="vehicle_name" id="vehicleName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="vehicle_no" id="vehicleNo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select class="form-select" name="customer_id" id="customerId" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['customer_id'] ?>"><?= e($c['customer_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    initDataTable('#vehiclesTable', {
        order: [[0, 'desc']]
    });
});

function resetForm() {
    document.getElementById('vehicleForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('vehicleId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Vehicle';
}

function editVehicle(vehicle) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('vehicleId').value = vehicle.vehicle_id;
    document.getElementById('vehicleName').value = vehicle.vehicle_name;
    document.getElementById('vehicleNo').value = vehicle.vehicle_no;
    document.getElementById('customerId').value = vehicle.customer_id;
    document.getElementById('modalTitle').textContent = 'Edit Vehicle #' + vehicle.vehicle_id;
    
    new bootstrap.Modal(document.getElementById('vehicleModal')).show();
}

function deleteVehicle(id, name) {
    confirmDelete(
        `Delete vehicle "${name}"?`,
        function() {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="vehicle_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
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
```

---

## 🎯 Checklist for Each Page

When modernizing a page, check off:

- [ ] Updated to `header_modern.php` and `footer_modern.php`
- [ ] Added `$current_page` variable
- [ ] Added `mf-content-header` section
- [ ] Wrapped table in `<div class="card">` and `<div class="card-body">`
- [ ] Added `id` to table for DataTables
- [ ] Converted form to Bootstrap 5 modal
- [ ] Changed action links to icon buttons
- [ ] Added `onclick` handlers for edit/delete
- [ ] Added JavaScript functions (resetForm, editItem, deleteItem)
- [ ] Replaced alerts with Toastr notifications
- [ ] Added status/count badges
- [ ] Added delete validation (check related records)
- [ ] Tested add, edit, delete operations
- [ ] Tested DataTables search/sort/pagination
- [ ] Tested responsive design (mobile/tablet)

---

## 🚀 Copy-Paste Snippets

### Import at Top
```php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();
```

### Page Variables
```php
$pageTitle = 'Your Page';
$current_page = 'page_name';
require __DIR__ . '/header_modern.php';
```

### Card Wrapper
```php
<div class="card">
    <div class="card-body">
        <!-- Content here -->
    </div>
</div>
```

### DataTables Init
```javascript
$(document).ready(function() {
    initDataTable('#yourTable', {
        order: [[0, 'desc']]
    });
});
```

### Toast Notifications
```javascript
<?php if ($msg): ?>
    showSuccess('<?= addslashes($msg) ?>');
<?php endif; ?>

<?php if ($err): ?>
    showError('<?= addslashes($err) ?>');
<?php endif; ?>
```

---

**Happy Coding! 🎨✨**
