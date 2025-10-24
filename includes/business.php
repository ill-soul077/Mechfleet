<?php
// includes/business.php
// Business logic: snapshot pricing & stock control

/**
 * Add a work part with snapshot pricing and stock control.
 * - Locks the product row, validates stock unless allow_backorder
 * - Inserts/updates work_parts with unit_price snapshot and line_total
 * - Decrements product stock
 * - Recomputes parts_cost and total_cost on the work order
 */
function addWorkPart(PDO $pdo, int $work_id, int $product_id, int $quantity, bool $allow_backorder=false): void {
  if ($work_id<=0 || $product_id<=0 || $quantity<=0) {
    throw new InvalidArgumentException('Invalid parameters');
  }
  $pdo->beginTransaction();
  try {
    // Lock product
    $st = $pdo->prepare('SELECT unit_price, stock_qty FROM product_details WHERE product_id=:pid FOR UPDATE');
    $st->execute([':pid'=>$product_id]);
    $prod = $st->fetch(PDO::FETCH_ASSOC);
    if (!$prod) throw new RuntimeException('Product not found');
    $stock = (int)$prod['stock_qty'];
    if (!$allow_backorder && $stock < $quantity) {
      throw new RuntimeException('Insufficient stock');
    }
    $unit = (float)$prod['unit_price'];
    $line = round($unit * $quantity, 2);

    // Insert or update existing part line (accumulate quantity)
    $chk = $pdo->prepare('SELECT quantity FROM work_parts WHERE work_id=:w AND product_id=:p');
    $chk->execute([':w'=>$work_id, ':p'=>$product_id]);
    $existingQty = $chk->fetchColumn();
    if ($existingQty !== false) {
      $newQty = (int)$existingQty + $quantity;
      $newLineTotal = round($unit * $newQty, 2);
      $upd = $pdo->prepare('UPDATE work_parts SET quantity=:q, unit_price=:u, line_total=:lt WHERE work_id=:w AND product_id=:p');
      $upd->execute([':q'=>$newQty, ':u'=>$unit, ':lt'=>$newLineTotal, ':w'=>$work_id, ':p'=>$product_id]);
    } else {
      $ins = $pdo->prepare('INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total) VALUES (:w,:p,:q,:u,:t)');
      $ins->execute([':w'=>$work_id, ':p'=>$product_id, ':q'=>$quantity, ':u'=>$unit, ':t'=>$line]);
    }

    // Update stock
    $pdo->prepare('UPDATE product_details SET stock_qty = stock_qty - :q WHERE product_id=:p')
        ->execute([':q'=>$quantity, ':p'=>$product_id]);

    // Recompute parts and totals
    $sum = $pdo->prepare('SELECT ROUND(COALESCE(SUM(line_total),0),2) FROM work_parts WHERE work_id=:w');
    $sum->execute([':w'=>$work_id]);
    $parts_total = (float)$sum->fetchColumn();
    $total_cost = $pdo->query("SELECT labor_cost FROM working_details WHERE work_id={$work_id}")->fetchColumn();
    $total_cost = round((float)$total_cost + $parts_total, 2);
    $pdo->prepare('UPDATE working_details SET parts_cost=:pc, total_cost=:tc WHERE work_id=:w')
        ->execute([':pc'=>$parts_total, ':tc'=>$total_cost, ':w'=>$work_id]);

    $pdo->commit();
  } catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $t;
  }
}

/**
 * Create or refresh invoice snapshot for a work order.
 * - Sums current work_parts line totals (already snapshot) and adds labor snapshot
 * - Writes into working_details.labor_cost/parts_cost/total_cost in a transaction
 */
function createInvoiceForWork(PDO $pdo, int $work_id): void {
  if ($work_id<=0) throw new InvalidArgumentException('Invalid work');
  $pdo->beginTransaction();
  try {
    // Fetch labor snapshot components
    $st = $pdo->prepare('SELECT w.assigned_mechanic_id, w.service_id FROM working_details w WHERE w.work_id=:w FOR UPDATE');
    $st->execute([':w'=>$work_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Work not found');
    // Get current hourly rate and estimated hours at time of invoicing (defines labor snapshot)
    $lr = $pdo->prepare('SELECT m.hourly_rate, s.estimated_hours FROM mechanics m, service_details s WHERE m.mechanic_id=:m AND s.service_id=:s');
    $lr->execute([':m'=>$row['assigned_mechanic_id'], ':s'=>$row['service_id']]);
    $lrRow = $lr->fetch(PDO::FETCH_ASSOC);
    if (!$lrRow) throw new RuntimeException('Pricing context not found');
    $labor = round((float)$lrRow['hourly_rate'] * (float)$lrRow['estimated_hours'], 2);

    // Sum parts snapshot totals
    $sum = $pdo->prepare('SELECT ROUND(COALESCE(SUM(line_total),0),2) FROM work_parts WHERE work_id=:w');
    $sum->execute([':w'=>$work_id]);
    $parts = (float)$sum->fetchColumn();

    // Persist snapshot totals
    $upd = $pdo->prepare('UPDATE working_details SET labor_cost=:lc, parts_cost=:pc, total_cost=ROUND(:lc + :pc,2) WHERE work_id=:w');
    $upd->execute([':lc'=>$labor, ':pc'=>$parts, ':w'=>$work_id]);

    $pdo->commit();
  } catch (Throwable $t) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $t;
  }
}
