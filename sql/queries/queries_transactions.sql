-- queries_transactions.sql
-- Demonstrates: explicit transactions for atomic multi-statement operations
-- Example: Add a part to a work order and decrement inventory stock; rollback on insufficient stock

-- Note: Run these commands in a SQL client that allows multi-statements (not the demo page)

-- 1) Happy path: insert work_parts row and update product stock together
-- Expected: work_parts row inserted, product_details.stock_qty decremented
START TRANSACTION;

-- Parameters (example values):
-- SET @p_work_id = 5; SET @p_product_id = 12; SET @p_qty = 2;
-- We'll fetch the unit price snapshot dynamically

SET @p_work_id = 5;
SET @p_product_id = 12;
SET @p_qty = 2;

SELECT @price := unit_price FROM product_details WHERE product_id = @p_product_id FOR UPDATE;

INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total)
VALUES (@p_work_id, @p_product_id, @p_qty, @price, ROUND(@price * @p_qty, 2));

UPDATE product_details
SET stock_qty = stock_qty - @p_qty
WHERE product_id = @p_product_id;

COMMIT;

-- 2) Rollback on insufficient stock (simulate check)
-- Expected: no changes committed if stock would go negative
START TRANSACTION;

SET @p_work_id = 6;
SET @p_product_id = 25;
SET @p_qty = 9999; -- intentionally excessive

SELECT @cur := stock_qty FROM product_details WHERE product_id = @p_product_id FOR UPDATE;

-- Conditional check; if not enough stock, rollback
IF @cur < @p_qty THEN
  ROLLBACK;
ELSE
  SELECT @price := unit_price FROM product_details WHERE product_id = @p_product_id;
  INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total)
  VALUES (@p_work_id, @p_product_id, @p_qty, @price, ROUND(@price * @p_qty, 2));
  UPDATE product_details SET stock_qty = stock_qty - @p_qty WHERE product_id = @p_product_id;
  COMMIT;
END IF;

-- Isolation/atomicity note:
-- - Using SELECT ... FOR UPDATE prevents concurrent transactions from reading and modifying the same product row uncoordinatedly.
-- - The entire set of operations either commits together or rolls back, ensuring consistency.
