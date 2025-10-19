<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
if (!auth_is_logged_in()) { echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }

$work_id = (int)($_POST['work_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['quantity'] ?? 1));

try {
  if (!$work_id || !$product_id) throw new RuntimeException('Invalid input');
  $pdo->beginTransaction();
  // Lock product row
  $st = $pdo->prepare('SELECT unit_price, stock_qty FROM product_details WHERE product_id=:id FOR UPDATE');
  $st->execute([':id'=>$product_id]);
  $p = $st->fetch(PDO::FETCH_ASSOC);
  if (!$p) throw new RuntimeException('Product not found');
  if ((int)$p['stock_qty'] < $qty) throw new RuntimeException('Insufficient stock');
  $unit = (float)$p['unit_price'];
  $ins = $pdo->prepare('INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total) VALUES (:w,:p,:q,:u,:t)');
  $ins->execute([':w'=>$work_id, ':p'=>$product_id, ':q'=>$qty, ':u'=>$unit, ':t'=>round($unit*$qty,2)]);
  $up = $pdo->prepare('UPDATE product_details SET stock_qty = stock_qty - :q WHERE product_id=:p');
  $up->execute([':q'=>$qty, ':p'=>$product_id]);
  // Recompute parts_cost and total_cost for the work order
  $sum = $pdo->prepare('SELECT ROUND(SUM(line_total),2) AS parts_total FROM work_parts WHERE work_id=:w');
  $sum->execute([':w'=>$work_id]);
  $parts_total = (float)($sum->fetchColumn() ?: 0);
  $upd = $pdo->prepare('UPDATE working_details SET parts_cost=:pc, total_cost=ROUND(labor_cost + :pc,2) WHERE work_id=:w');
  $upd->execute([':pc'=>$parts_total, ':w'=>$work_id]);
  $pdo->commit();
  echo json_encode(['success'=>true]);
} catch (Throwable $t) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  echo json_encode(['success'=>false, 'error'=>$t->getMessage()]);
}
