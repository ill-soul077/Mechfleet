<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$work_id = (int)($_GET['work_id'] ?? 0);
// Get products with current stock and unit price
$products = $pdo->query('SELECT product_id, sku, product_name, stock_qty, unit_price FROM product_details WHERE stock_qty > 0 ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);
?>
<form id="addPartForm" onsubmit="return savePart(event)">
  <input type="hidden" name="work_id" value="<?= e((string)$work_id) ?>" />
  
  <div class="mb-3">
    <label for="partProduct" class="form-label">Product <span class="text-danger">*</span></label>
    <select class="form-select" id="partProduct" name="product_id" required onchange="updatePartInfo()">
      <option value="">Select Product</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= e((string)$p['product_id']) ?>" 
                data-sku="<?= e($p['sku']) ?>"
                data-price="<?= e((string)$p['unit_price']) ?>"
                data-stock="<?= e((string)$p['stock_qty']) ?>">
          <?= e($p['product_name'].' - SKU: '.$p['sku'].' (Stock: '.$p['stock_qty'].')') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="text-muted">Only products with stock available are shown</small>
  </div>
  
  <div class="mb-3">
    <label for="partQuantity" class="form-label">Quantity <span class="text-danger">*</span></label>
    <input type="number" class="form-control" id="partQuantity" name="quantity" value="1" min="1" required oninput="updatePartInfo()" />
    <small class="text-muted" id="stockWarning"></small>
  </div>
  
  <div id="partInfoBox" class="alert alert-info d-none">
    <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Part Details</h6>
    <div class="row">
      <div class="col-6">
        <small class="text-muted">Unit Price:</small><br>
        <strong id="unitPrice">$0.00</strong>
      </div>
      <div class="col-6">
        <small class="text-muted">Line Total:</small><br>
        <strong id="lineTotal" class="text-primary">$0.00</strong>
      </div>
    </div>
    <div class="mt-2">
      <small class="text-muted">Available Stock:</small><br>
      <span id="availableStock" class="badge bg-success">0</span>
    </div>
  </div>
  
  <div id="part-msg" class="alert alert-info d-none"></div>
  
  <div class="d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-secondary" onclick="closeModal()">
      <i class="fas fa-times me-2"></i>Cancel
    </button>
    <button type="submit" class="btn btn-primary" id="submitBtn">
      <i class="fas fa-plus me-2"></i>Add Part
    </button>
  </div>
</form>

<script>
function updatePartInfo() {
  const select = document.getElementById('partProduct');
  const qtyInput = document.getElementById('partQuantity');
  const infoBox = document.getElementById('partInfoBox');
  const unitPriceSpan = document.getElementById('unitPrice');
  const lineTotalSpan = document.getElementById('lineTotal');
  const stockSpan = document.getElementById('availableStock');
  const stockWarning = document.getElementById('stockWarning');
  const submitBtn = document.getElementById('submitBtn');
  
  if (!select.value) {
    infoBox.classList.add('d-none');
    stockWarning.textContent = '';
    submitBtn.disabled = false;
    return;
  }
  
  const option = select.options[select.selectedIndex];
  const price = parseFloat(option.dataset.price || 0);
  const stock = parseInt(option.dataset.stock || 0);
  const qty = parseInt(qtyInput.value || 1);
  const lineTotal = price * qty;
  
  // Update display
  unitPriceSpan.textContent = '$' + price.toFixed(2);
  lineTotalSpan.textContent = '$' + lineTotal.toFixed(2);
  stockSpan.textContent = stock;
  
  // Update stock badge color
  if (stock >= qty) {
    stockSpan.className = 'badge bg-success';
    stockWarning.textContent = '';
    stockWarning.className = 'text-muted';
    submitBtn.disabled = false;
  } else {
    stockSpan.className = 'badge bg-danger';
    stockWarning.textContent = '⚠️ Insufficient stock! Available: ' + stock + ', Requested: ' + qty;
    stockWarning.className = 'text-danger';
    submitBtn.disabled = true;
  }
  
  infoBox.classList.remove('d-none');
}

function savePart(ev){
  ev.preventDefault();
  const form = ev.target;
  const data = new FormData(form);
  const msgDiv = document.getElementById('part-msg');
  const submitBtn = document.getElementById('submitBtn');
  
  console.log('[savePart] Form submitted');
  console.log('[savePart] work_id:', data.get('work_id'));
  console.log('[savePart] product_id:', data.get('product_id'));
  console.log('[savePart] quantity:', data.get('quantity'));
  
  // Disable submit button
  submitBtn.disabled = true;
  
  // Show loading
  msgDiv.className = 'alert alert-info';
  msgDiv.classList.remove('d-none');
  msgDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding part to work order...';
  
  console.log('[savePart] Sending request to api/add_work_part.php');
  
  fetch('api/add_work_part.php',{method:'POST', body:data})
    .then(r=> {
      console.log('[savePart] Response status:', r.status, r.statusText);
      return r.text();
    })
    .then(text => {
      console.log('[savePart] Response text:', text);
      try {
        const j = JSON.parse(text);
        console.log('[savePart] Parsed JSON:', j);
        if(j.success){ 
          msgDiv.className = 'alert alert-success';
          msgDiv.innerHTML = '<i class="fas fa-check me-2"></i>Part added! Reloading...';
          console.log('[savePart] Success! Closing modal and reloading page...');
          // Close modal and reload parent page
          setTimeout(()=>{
            const modal = bootstrap.Modal.getInstance(document.getElementById('partsModal'));
            if (modal) {
              console.log('[savePart] Closing modal');
              modal.hide();
            }
            // Reload the work order page with cache-busting timestamp
            const reloadUrl = 'work_orders.php?id=' + data.get('work_id') + '&t=' + Date.now();
            console.log('[savePart] Reloading to:', reloadUrl);
            window.top.location.href = reloadUrl;
          }, 500); 
        } else { 
          msgDiv.className = 'alert alert-danger';
          msgDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error: '+(j.error||'Unknown error');
          console.error('API error:', j.error);
          submitBtn.disabled = false;
        }
      } catch(e) {
        msgDiv.className = 'alert alert-danger';
        msgDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Invalid response from server';
        console.error('Parse error:', e, text);
        submitBtn.disabled = false;
      }
    })
    .catch(e=>{ 
      msgDiv.className = 'alert alert-danger';
      msgDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Network error: ' + e.message;
      console.error('Network error:', e);
      submitBtn.disabled = false;
    });
  
  return false;
}

function closeModal() {
  const modal = bootstrap.Modal.getInstance(document.getElementById('partsModal'));
  if (modal) modal.hide();
}
</script>
