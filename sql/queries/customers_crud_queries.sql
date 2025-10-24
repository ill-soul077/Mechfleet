-- ========================================
-- CUSTOMERS.PHP - SQL QUERIES REFERENCE
-- ========================================
-- This file documents all SQL queries used in the customers.php page
-- All queries are BASIC SQL (no PL/SQL, no stored procedures)
-- Using PDO prepared statements with named parameters for security

-- ========================================
-- 1. CREATE (INSERT) - Add New Customer
-- ========================================
-- Used when: User submits the "Add Customer" form
-- Action: action=create

INSERT INTO customer (
    first_name, 
    last_name, 
    email, 
    phone, 
    address, 
    city, 
    state, 
    zip_code
) VALUES (
    :fn,    -- first name (required)
    :ln,    -- last name (required)
    :em,    -- email (required, validated)
    :ph,    -- phone (required)
    :ad,    -- address (optional)
    :ci,    -- city (optional)
    :st,    -- state (optional, max 2 chars)
    :zip    -- zip code (optional)
);

-- Example with actual values:
-- INSERT INTO customer (first_name, last_name, email, phone, address, city, state, zip_code)
-- VALUES ('John', 'Doe', 'john.doe@example.com', '555-1234', '123 Main St', 'Springfield', 'IL', '62701');


-- ========================================
-- 2. READ (SELECT) - Retrieve Customers
-- ========================================

-- 2a. Get all customers (limited to most recent 200)
-- Used when: Page loads to display customer list
SELECT * 
FROM customer 
ORDER BY customer_id DESC 
LIMIT 200;

-- 2b. Get specific customer for editing
-- Used when: User clicks "Edit" button
-- Parameter: customer_id from URL (?edit=123)
SELECT * 
FROM customer 
WHERE customer_id = :id;

-- Example with actual value:
-- SELECT * FROM customer WHERE customer_id = 15;


-- ========================================
-- 3. UPDATE - Modify Existing Customer
-- ========================================
-- Used when: User submits the "Edit Customer" form
-- Action: action=update

UPDATE customer 
SET 
    first_name = :fn,
    last_name = :ln,
    email = :em,
    phone = :ph,
    address = :ad,
    city = :ci,
    state = :st,
    zip_code = :zip
WHERE customer_id = :id;

-- Example with actual values:
-- UPDATE customer 
-- SET first_name='Jane', last_name='Smith', email='jane.smith@example.com', 
--     phone='555-5678', address='456 Oak Ave', city='Columbus', state='OH', zip_code='43004'
-- WHERE customer_id = 15;


-- ========================================
-- 4. DELETE - Remove Customer
-- ========================================
-- Used when: User clicks "Delete" button and confirms
-- Action: action=delete

-- IMPORTANT: Check for related records BEFORE deleting
-- Step 1: Check if customer has vehicles
SELECT COUNT(*) as vehicle_count 
FROM vehicle 
WHERE customer_id = :id;

-- Step 2: Check if customer has work orders
SELECT COUNT(*) as work_count 
FROM working_details 
WHERE customer_id = :id;

-- Step 3: Only delete if both counts are 0
DELETE FROM customer 
WHERE customer_id = :id;

-- Example with actual value:
-- DELETE FROM customer WHERE customer_id = 15;

-- Note: This will fail if the customer has related records in the vehicle table
-- or working_details table due to foreign key constraints (ON DELETE RESTRICT)

-- IMPROVED QUERY: Get customers with their related record counts
-- Used to display in the list and disable delete button for customers with relations
SELECT c.*, 
       COUNT(DISTINCT v.vehicle_id) as vehicle_count,
       COUNT(DISTINCT w.work_id) as work_count
FROM customer c
LEFT JOIN vehicle v ON c.customer_id = v.customer_id
LEFT JOIN working_details w ON c.customer_id = w.customer_id
GROUP BY c.customer_id
ORDER BY c.customer_id DESC 
LIMIT 200;


-- ========================================
-- VALIDATION RULES (enforced in PHP)
-- ========================================
-- Before any INSERT or UPDATE:
-- 1. first_name - Required, cannot be empty
-- 2. last_name - Required, cannot be empty
-- 3. email - Required, must be valid email format
-- 4. phone - Required, cannot be empty
-- 5. All other fields (address, city, state, zip_code) - Optional


-- ========================================
-- DATABASE CONSTRAINTS (enforced in MySQL)
-- ========================================
-- 1. customer_id - PRIMARY KEY, AUTO_INCREMENT
-- 2. email - UNIQUE (no duplicate emails allowed)
-- 3. Phone - Indexed for fast lookups
-- 4. created_at - Automatically set to current timestamp on INSERT
-- 5. updated_at - Automatically updated to current timestamp on UPDATE


-- ========================================
-- TESTING QUERIES
-- ========================================

-- Count total customers
SELECT COUNT(*) as total_customers FROM customer;

-- View all customers with basic info
SELECT customer_id, first_name, last_name, email, phone 
FROM customer 
ORDER BY customer_id DESC 
LIMIT 10;

-- Search customers by name
SELECT customer_id, first_name, last_name, email, phone 
FROM customer 
WHERE first_name LIKE '%John%' OR last_name LIKE '%John%'
ORDER BY last_name, first_name;

-- Find customer by email
SELECT * FROM customer WHERE email = 'john.smith@example.com';

-- View customers with their vehicles
SELECT 
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.email,
    c.phone,
    COUNT(v.vehicle_id) as total_vehicles
FROM customer c
LEFT JOIN vehicle v ON c.customer_id = v.customer_id
GROUP BY c.customer_id
ORDER BY total_vehicles DESC;
