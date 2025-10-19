<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/auth.php';
auth_require_login();

$work_id = (int)($_GET['work_id'] ?? 0);
$products = $pdo->query('SELECT product_id, sku, product_name, stock_qty FROM product_details ORDER BY product_name')->fetchAll(PDO::FETCH_ASSOC);
?>
<form onsubmit="return savePart(event)">
  <input type="hidden" name="work_id" value="<?= e((string)$work_id) ?>" />
  <label>Product</label><br />
  <select name="product_id" required>
    <?php foreach ($products as $p): ?>
      <option value="<?= e((string)$p['product_id']) ?>"><?= e($p['product_name'].' ('.$p['sku'].', stock '.$p['stock_qty'].')') ?></option>
    <?php endforeach; ?>
  </select>
  <br /><label>Quantity</label><br />
  <input type="number" name="quantity" value="1" min="1" />
  <div style="margin-top:.5rem"></div>
  <button type="submit">Add</button>
  <button type="button" onclick="closeModal()" style="margin-left:.5rem">Cancel</button>
</form>
<div id="part-msg" class="muted" style="margin-top:.5rem"></div>
<script>
function savePart(ev){
  ev.preventDefault();
  const form = ev.target;
  const data = new FormData(form);
  fetch('api/add_work_part.php',{method:'POST', body:data})
    .then(r=>r.json())
    .then(j=>{
      const m = document.getElementById('part-msg');
      if(j.success){ m.textContent = 'Added.'; setTimeout(()=>window.location.reload(), 500); }
      else { m.textContent = 'Error: '+(j.error||'Unknown'); }
    })
    .catch(e=>{ document.getElementById('part-msg').textContent = 'Network error'; });
}
</script>
