# Work Order Parts Management - System Documentation

## Overview
The MechFleet system automatically manages parts inventory, costs, and stock levels when parts are added to work orders. This document explains how the integration works.

## Key Features

### 1. **Automatic Parts Cost Calculation**
When you add a part to a work order:
- The system captures the current `unit_price` from `product_details`
- Calculates `line_total` = `quantity` × `unit_price`
- Updates `working_details.parts_cost` with the sum of all parts
- Recalculates `working_details.total_cost` = `labor_cost` + `parts_cost`

### 2. **Automatic Stock Decrement**
When parts are added:
- The system automatically decrements `product_details.stock_qty` by the quantity used
- Stock validation prevents using more parts than available (unless backorder is allowed)
- The system uses database row locking (FOR UPDATE) to prevent race conditions

### 3. **Real-time Display Updates**
After adding a part:
- The "Parts Used" section automatically refreshes to show the new part
- The "Cost Breakdown" section updates to reflect new parts cost
- The stock count in the Products page decreases accordingly

## Database Structure

### Tables Involved

#### `working_details` (Work Orders)
```sql
- work_id (PK)
- labor_cost DECIMAL(10,2)     -- Set when work order is created
- parts_cost DECIMAL(10,2)     -- Auto-calculated from work_parts
- total_cost DECIMAL(10,2)     -- Auto-calculated (labor + parts)
```

#### `work_parts` (Junction Table)
```sql
- work_id (FK to working_details)
- product_id (FK to product_details)
- quantity INT                  -- Number of units used
- unit_price DECIMAL(10,2)      -- Snapshot price at time of use
- line_total DECIMAL(10,2)      -- quantity × unit_price
```

#### `product_details` (Inventory)
```sql
- product_id (PK)
- stock_qty INT                 -- Decremented when part is added
- unit_price DECIMAL(10,2)      -- Current price (snapshot to work_parts)
```

## How It Works

### Adding a Part to Work Order

**File: `public/api/add_work_part.php`**
```php
1. Receives: work_id, product_id, quantity
2. Calls: addWorkPart($pdo, $work_id, $product_id, $quantity)
3. Returns: JSON {success: true} or {success: false, error: "..."}
```

**Function: `includes/business.php::addWorkPart()`**
```php
Transaction begins:
1. SELECT ... FROM product_details WHERE product_id=X FOR UPDATE
   - Locks the product row to prevent concurrent modifications
   - Gets current unit_price and stock_qty

2. Validate stock: if (!allow_backorder && stock < quantity) throw error

3. INSERT/UPDATE work_parts:
   - If part already exists for this work order: ADD to quantity
   - If new part: INSERT new row
   - Always uses current unit_price as snapshot

4. UPDATE product_details SET stock_qty = stock_qty - quantity

5. Calculate new totals:
   - parts_cost = SUM(line_total) from all work_parts for this work order
   - UPDATE working_details SET parts_cost=X, total_cost=labor_cost+X

Transaction commits
```

### UI Flow

**Work Order Detail Page (`public/work_orders.php`)**

1. **Click "Add Part" button**
   - Opens modal with product selection form
   - Loads: `public/work_parts_add.php`

2. **Select Product & Quantity**
   - Shows available stock
   - Displays unit price and line total preview
   - Validates quantity against stock
   - Prevents submission if insufficient stock

3. **Submit Form**
   - AJAX POST to `api/add_work_part.php`
   - Success: Reload page to show updated parts list and costs
   - Error: Display error message in modal

4. **Updated Display Shows:**
   - New part in "Parts Used" section
   - Updated "Parts Cost" in cost breakdown
   - Updated "Total Cost"
   - Stock count reduced in products page

## Example Scenario

### Initial State
```
Work Order #123:
  Labor Cost: $150.00
  Parts Cost: $0.00
  Total Cost: $150.00

Product: Oil Filter (SKU: OF-001)
  Stock: 50 units
  Unit Price: $12.99
```

### User Adds 2 Oil Filters
```
Action: Add 2 × Oil Filter to Work Order #123

Database Changes:
1. work_parts table:
   INSERT (work_id=123, product_id=5, quantity=2, unit_price=12.99, line_total=25.98)

2. product_details table:
   UPDATE product_details SET stock_qty = 48 WHERE product_id = 5

3. working_details table:
   UPDATE working_details SET 
     parts_cost = 25.98,
     total_cost = 175.98  -- (150.00 + 25.98)
   WHERE work_id = 123
```

### Final State
```
Work Order #123:
  Labor Cost: $150.00
  Parts Cost: $25.98
  Total Cost: $175.98
  
  Parts Used:
    - Oil Filter (OF-001) × 2 @ $12.99 = $25.98

Product: Oil Filter (SKU: OF-001)
  Stock: 48 units  ← Decreased from 50
  Unit Price: $12.99
```

## Important Features

### Snapshot Pricing
- The system captures `unit_price` at the time the part is added
- If product price changes later, work orders keep historical pricing
- This ensures invoice accuracy and prevents retroactive price changes

### Stock Validation
- System prevents adding more parts than available stock
- Option for `allow_backorder` can override this check
- Database locking prevents two users from depleting stock simultaneously

### Cumulative Parts
- Adding the same part twice to a work order ACCUMULATES the quantity
- Example: Add 2 oil filters, then add 3 more = total 5 filters
- Line total recalculates based on cumulative quantity

### Transaction Safety
- All operations wrapped in database transaction
- If ANY step fails, ALL changes are rolled back
- Prevents partial updates (e.g., stock decremented but part not added)

## SQL Queries for Verification

**Check work order costs:**
```sql
SELECT work_id, labor_cost, parts_cost, total_cost 
FROM working_details 
WHERE work_id = X;
```

**Check parts used:**
```sql
SELECT wp.*, p.product_name 
FROM work_parts wp 
JOIN product_details p ON p.product_id = wp.product_id 
WHERE wp.work_id = X;
```

**Check stock levels:**
```sql
SELECT product_id, sku, product_name, stock_qty 
FROM product_details 
ORDER BY stock_qty ASC;
```

**Verify cost calculations:**
```sql
SELECT 
    w.work_id,
    w.parts_cost AS recorded_parts_cost,
    COALESCE(SUM(wp.line_total), 0) AS calculated_parts_cost,
    w.total_cost AS recorded_total,
    (w.labor_cost + COALESCE(SUM(wp.line_total), 0)) AS calculated_total
FROM working_details w
LEFT JOIN work_parts wp ON wp.work_id = w.work_id
GROUP BY w.work_id
HAVING ABS(w.parts_cost - calculated_parts_cost) > 0.01;
```

## User Interface Guide

### Adding Parts

1. Navigate to Work Orders page
2. Click on a work order to view details
3. In "Parts Used" section, click "Add Part" button
4. Select product from dropdown (shows SKU and current stock)
5. Enter quantity (form validates against available stock)
6. See preview of unit price and line total
7. Click "Add Part" to save
8. Page reloads showing updated parts list and costs

### What Users See

**Before Adding Part:**
```
Cost Breakdown:
  Labor Cost: $150.00
  Parts Cost: $0.00
  ───────────────────
  Total Cost: $150.00

Parts Used:
  [No parts added yet]
```

**After Adding Part:**
```
Cost Breakdown:
  Labor Cost: $150.00
  Parts Cost: $25.98
  ───────────────────
  Total Cost: $175.98

Parts Used:
  SKU          Product      Qty  Unit Price  Total
  OF-001       Oil Filter    2    $12.99     $25.98
```

## Troubleshooting

### "Insufficient stock" error
- Check current stock: `SELECT stock_qty FROM product_details WHERE product_id = X`
- Either reduce quantity or add stock in Products page

### Parts cost not updating
- Verify transaction committed successfully
- Check for database errors in PHP error log
- Run verification query to check for discrepancies

### Stock not decreasing
- Check if `addWorkPart()` function is being called
- Verify database user has UPDATE permission
- Check for failed transactions in application logs

## API Reference

### Add Work Part
**Endpoint:** `POST /api/add_work_part.php`

**Parameters:**
- `work_id` (int, required) - Work order ID
- `product_id` (int, required) - Product/part ID
- `quantity` (int, required) - Number of units to add
- `allow_backorder` (bool, optional) - Allow adding even if insufficient stock

**Response:**
```json
// Success
{"success": true}

// Error
{"success": false, "error": "Insufficient stock"}
```

## Maintenance

### Recompute All Work Order Costs
If costs become inconsistent, run:
```sql
UPDATE working_details w
SET 
  parts_cost = (
    SELECT COALESCE(SUM(line_total), 0) 
    FROM work_parts 
    WHERE work_id = w.work_id
  ),
  total_cost = labor_cost + (
    SELECT COALESCE(SUM(line_total), 0) 
    FROM work_parts 
    WHERE work_id = w.work_id
  );
```

---

**Last Updated:** $(date)
**System Version:** MechFleet 1.0
