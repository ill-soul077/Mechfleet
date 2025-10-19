<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/business.php';

header('Content-Type: text/plain');

echo "== Test createInvoiceForWork ==\n";
try {
  $w = $pdo->query('SELECT work_id FROM working_details ORDER BY work_id DESC LIMIT 1')->fetchColumn();
  if (!$w) { echo "No work order found.\n"; exit; }
  // Mutate product prices to ensure invoice uses snapshot totals from work_parts not current prices
  $pdo->exec('UPDATE product_details SET unit_price = unit_price + 1 WHERE product_id IN (SELECT product_id FROM work_parts WHERE work_id='.(int)$w.')');
  createInvoiceForWork($pdo, (int)$w);
  $row = $pdo->query('SELECT labor_cost, parts_cost, total_cost FROM working_details WHERE work_id='.(int)$w)->fetch(PDO::FETCH_ASSOC);
  echo "Invoice totals -> labor: {$row['labor_cost']}, parts: {$row['parts_cost']}, total: {$row['total_cost']}\n";
  echo "(Verify parts_cost is the sum of work_parts.line_total; labor based on current mechanic/service snapshot at invoice time.)\n";
} catch (Throwable $t) {
  echo "Error: ".$t->getMessage()."\n";
}
