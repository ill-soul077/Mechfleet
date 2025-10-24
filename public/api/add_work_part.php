<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/business.php';

header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!auth_is_logged_in()) { 
  echo json_encode(['success'=>false,'error'=>'Unauthorized']); 
  exit; 
}

$work_id = (int)($_POST['work_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['quantity'] ?? 1));
$allow_backorder = isset($_POST['allow_backorder']) && (($_POST['allow_backorder'] === '1') || (strtolower((string)$_POST['allow_backorder']) === 'true'));

try {
  if (!$work_id || !$product_id) {
    throw new RuntimeException('Invalid input: work_id=' . $work_id . ', product_id=' . $product_id);
  }
  
  // Add the part
  addWorkPart($pdo, $work_id, $product_id, $qty, $allow_backorder);
  
  // Return success
  echo json_encode(['success'=>true, 'work_id'=>$work_id, 'product_id'=>$product_id, 'quantity'=>$qty]);
  
} catch (Throwable $t) {
  if ($pdo->inTransaction()) { 
    $pdo->rollBack(); 
  }
  error_log('add_work_part error: ' . $t->getMessage());
  echo json_encode(['success'=>false, 'error'=>$t->getMessage()]);
}
