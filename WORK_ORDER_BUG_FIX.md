# Work Order Creation Bug Fix

## Problem
Work orders were **not being created** and **not saving to the database**.

## Root Cause
**SQL Parameter Mismatch Error** in `public/work_orders.php` (line 30)

### The Bug
```php
// BROKEN CODE - Parameter :lc used twice!
$stmt = $pdo->prepare('INSERT INTO working_details (...,labor_cost,parts_cost,total_cost,...) VALUES (:c,:v,:m,:s,:st,:lc,0.00,:lc,:sd,:n)');
$stmt->execute([':c'=>$customer_id, ':v'=>$vehicle_id, ':m'=>$mechanic_id, ':s'=>$service_id, ':st'=>$status, ':lc'=>$labor, ':sd'=>$start_date, ':n'=>$notes]);
```

**Issues:**
1. `:lc` parameter appears **twice** in the VALUES clause
2. Hardcoded `0.00` for `parts_cost` instead of using a parameter
3. Only 8 parameters provided in execute() but SQL expects 10 values
4. PDO throws: `SQLSTATE[HY093]: Invalid parameter number`

### The Fix
```php
// FIXED CODE - All parameters properly defined
$parts_cost = 0.00;
$total_cost = $labor + $parts_cost;
$stmt = $pdo->prepare('INSERT INTO working_details (...,labor_cost,parts_cost,total_cost,...) VALUES (:c,:v,:m,:s,:st,:lc,:pc,:tc,:sd,:n)');
$stmt->execute([':c'=>$customer_id, ':v'=>$vehicle_id, ':m'=>$mechanic_id, ':s'=>$service_id, ':st'=>$status, ':lc'=>$labor, ':pc'=>$parts_cost, ':tc'=>$total_cost, ':sd'=>$start_date, ':n'=>$notes]);
```

**Changes:**
1. Added `:pc` parameter for `parts_cost`
2. Added `:tc` parameter for `total_cost`
3. Removed duplicate `:lc` usage
4. All 10 parameters now properly matched

## Testing
Created test script `public/tests/test_work_order_create.php` that confirms:
- ✅ INSERT statement executes successfully
- ✅ lastInsertId() returns new work order ID
- ✅ Record verified in database
- ✅ All fields populated correctly

## Files Modified
- `public/work_orders.php` - Fixed SQL INSERT statement (lines 28-33)
- `public/tests/check_work_orders.php` - Database verification script (new)
- `public/tests/test_work_order_create.php` - Creation test script (new)

## Commit
```
8f6fd13 - Fix work order creation - resolve SQL parameter mismatch bug
```

## How to Test
1. Go to http://localhost/Mechfleet/public/work_orders.php
2. Click "Create Work Order" button
3. Fill in all required fields:
   - Customer
   - Vehicle
   - Assigned Mechanic
   - Service
   - Status
   - Start Date
4. Click "Create Work Order"
5. **Expected Result:** Redirected to Work Order #[ID] detail page with success message
6. **Verification:** Check database or list view - new work order should appear

## Database Verification
Run from command line:
```bash
php public/tests/check_work_orders.php
```

This will show:
- Total count of work orders
- Latest 10 work orders
- Auto-increment value
- Test INSERT capability

---
**Status:** ✅ RESOLVED - Work orders now create and save to database successfully
