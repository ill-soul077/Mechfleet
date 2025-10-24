<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$work_id = (int)($_GET['work_id'] ?? 0);
$products = $pdo->query('SELECT product_id, sku, product_name, stock_qty FROM product_details ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);
?>
<form id="addPartForm" onsubmit="return savePart(event)">
  <input type="hidden" name="work_id" value="<?= e((string)$work_id) ?>" />
  
  <div class="mb-3">
    <label for="partProduct" class="form-label">Product <span class="text-danger">*</span></label>
    <select class="form-select" id="partProduct" name="product_id" required>
      <option value="">Select Product</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= e((string)$p['product_id']) ?>">
          <?= e($p['product_name'].' - SKU: '.$p['sku'].' (Stock: '.$p['stock_qty'].')') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  
  <div class="mb-3">
    <label for="partQuantity" class="form-label">Quantity <span class="text-danger">*</span></label>
    <input type="number" class="form-control" id="partQuantity" name="quantity" value="1" min="1" required />
  </div>
  
  <div id="part-msg" class="alert alert-info d-none"></div>
  
  <div class="d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-secondary" onclick="closeModal()">
      <i class="fas fa-times me-2"></i>Cancel
    </button>
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-plus me-2"></i>Add Part
    </button>
  </div>
</form>

<script>
function savePart(ev){
  ev.preventDefault();
  const form = ev.target;
  const data = new FormData(form);
  const msgDiv = document.getElementById('part-msg');
  
  // Show loading
  msgDiv.className = 'alert alert-info';
  msgDiv.classList.remove('d-none');
  msgDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding part...';
  
  fetch('api/add_work_part.php',{method:'POST', body:data})
    .then(r=>r.json())
    .then(j=>{
      if(j.success){ 
        msgDiv.className = 'alert alert-success';
        msgDiv.innerHTML = '<i class="fas fa-check me-2"></i>Part added successfully! Reloading...';
        setTimeout(()=>window.location.reload(), 800); 
      } else { 
        msgDiv.className = 'alert alert-danger';
        msgDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error: '+(j.error||'Unknown error');
      }
    })
    .catch(e=>{ 
      msgDiv.className = 'alert alert-danger';
      msgDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Network error. Please try again.';
    });
  
  return false;
}

function closeModal() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('partsModal'));
  if (modal) modal.hide();
}
</script>
