-- sql/ddl.sql
-- This file demonstrates DDL: CREATE TABLE, PK/FK, UNIQUE, INDEX, CHECK.
-- Schema for Mechfleet management system (MySQL 8.x)
--
-- Concepts demonstrated:
-- - CREATE DATABASE with charset/collation
-- - Primary keys (AUTO_INCREMENT, INT/BIGINT)
-- - Foreign keys with ON UPDATE/ON DELETE actions
-- - UNIQUE constraints
-- - CHECK constraints (MySQL 8.0+)
-- - ENUM types for fixed value sets
-- - Indexes (single-column, composite, prefix indexes)
-- - DECIMAL for currency
-- - TIMESTAMP with DEFAULT CURRENT_TIMESTAMP and ON UPDATE
-- - Views for reporting (window functions)
-- - DROP TABLE IF EXISTS for idempotent re-runs

-- =====================================================================
-- DATABASE CREATION (optional - uncomment if needed)
-- =====================================================================
-- CREATE DATABASE IF NOT EXISTS mechfleet CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- USE mechfleet;

-- =====================================================================
-- DROP TABLES (reverse dependency order)
-- =====================================================================
DROP TABLE IF EXISTS income;
DROP TABLE IF EXISTS work_parts;
DROP TABLE IF EXISTS working_details;
DROP TABLE IF EXISTS product_details;
DROP TABLE IF EXISTS service_details;
DROP TABLE IF EXISTS mechanics;
DROP TABLE IF EXISTS vehicle;
DROP TABLE IF EXISTS customer;
DROP TABLE IF EXISTS manager;

-- =====================================================================
-- MANAGER TABLE
-- =====================================================================
-- Stores manager information who oversee mechanics
CREATE TABLE manager (
  manager_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30),
  hired_date DATE NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Is manager currently active',
  PRIMARY KEY (manager_id),
  UNIQUE KEY uq_manager_email (email)
) ENGINE=InnoDB COMMENT='Managers who supervise mechanics';

-- =====================================================================
-- CUSTOMER TABLE
-- =====================================================================
-- Stores customer information (vehicle owners)
CREATE TABLE customer (
  customer_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  address VARCHAR(255),
  city VARCHAR(100),
  state CHAR(2),
  zip_code VARCHAR(10),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_customer_email (email),
  INDEX idx_customer_phone (phone)
) ENGINE=InnoDB COMMENT='Customers who own vehicles';

-- =====================================================================
-- VEHICLE TABLE
-- =====================================================================
-- Stores vehicle information linked to customers
CREATE TABLE vehicle (
  vehicle_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  customer_id INT UNSIGNED NOT NULL COMMENT 'FK to customer',
  vin CHAR(17) NOT NULL COMMENT 'Vehicle Identification Number',
  make VARCHAR(50) NOT NULL,
  model VARCHAR(50) NOT NULL,
  year YEAR NOT NULL,
  color VARCHAR(30),
  mileage INT UNSIGNED DEFAULT 0 COMMENT 'Current odometer reading',
  license_plate VARCHAR(20),
  PRIMARY KEY (vehicle_id),
  CONSTRAINT fk_vehicle_customer FOREIGN KEY (customer_id)
    REFERENCES customer(customer_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT COMMENT 'Cannot delete customer with vehicles',
  CONSTRAINT uq_vehicle_vin UNIQUE (vin),
  CONSTRAINT chk_vehicle_year CHECK (year >= 1900 AND year <= 2100),
  CONSTRAINT chk_vehicle_mileage CHECK (mileage >= 0)
) ENGINE=InnoDB COMMENT='Vehicles owned by customers';

-- =====================================================================
-- MECHANICS TABLE
-- =====================================================================
-- Stores mechanic information with optional manager assignment
CREATE TABLE mechanics (
  mechanic_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30),
  specialty VARCHAR(100) COMMENT 'e.g., Engine, Transmission, Electrical',
  hourly_rate DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Labor rate per hour',
  managed_by INT UNSIGNED NULL COMMENT 'FK to manager - can be NULL',
  hired_date DATE NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (mechanic_id),
  CONSTRAINT fk_mechanics_manager FOREIGN KEY (managed_by)
    REFERENCES manager(manager_id)
    ON UPDATE CASCADE
    ON DELETE SET NULL COMMENT 'Set NULL if manager is deleted',
  UNIQUE KEY uq_mechanics_email (email),
  CONSTRAINT chk_mechanics_rate CHECK (hourly_rate >= 0)
) ENGINE=InnoDB COMMENT='Mechanics who perform service work';

-- =====================================================================
-- SERVICE_DETAILS TABLE
-- =====================================================================
-- Catalog of services offered (e.g., Oil Change, Brake Repair)
CREATE TABLE service_details (
  service_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  service_name VARCHAR(100) NOT NULL,
  description TEXT,
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Standard service price',
  estimated_hours DECIMAL(4,2) DEFAULT 1.00 COMMENT 'Typical hours required',
  active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (service_id),
  UNIQUE KEY uq_service_name (service_name),
  CONSTRAINT chk_service_price CHECK (base_price >= 0),
  CONSTRAINT chk_service_hours CHECK (estimated_hours > 0)
) ENGINE=InnoDB COMMENT='Catalog of available services';

-- =====================================================================
-- PRODUCT_DETAILS TABLE
-- =====================================================================
-- Parts inventory with stock tracking and reorder levels
CREATE TABLE product_details (
  product_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  sku VARCHAR(50) NOT NULL COMMENT 'Stock Keeping Unit - UNIQUE',
  product_name VARCHAR(150) NOT NULL,
  description TEXT,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Price per unit',
  stock_qty INT NOT NULL DEFAULT 0 COMMENT 'Current inventory quantity',
  reorder_level INT NOT NULL DEFAULT 10 COMMENT 'Min stock before reorder alert',
  category VARCHAR(50) COMMENT 'e.g., Engine Parts, Filters, Fluids',
  PRIMARY KEY (product_id),
  CONSTRAINT uq_product_sku UNIQUE (sku),
  CONSTRAINT chk_product_price CHECK (unit_price >= 0),
  CONSTRAINT chk_product_stock CHECK (stock_qty >= 0),
  CONSTRAINT chk_product_reorder CHECK (reorder_level >= 0)
) ENGINE=InnoDB COMMENT='Parts and products inventory';

-- Index on SKU for fast lookups (even though it's UNIQUE, explicit index for clarity)
CREATE INDEX idx_product_sku ON product_details (sku);

-- =====================================================================
-- WORKING_DETAILS TABLE
-- =====================================================================
-- Work orders linking customer, vehicle, mechanic, and service
-- Demonstrates ENUM for status and snapshot columns for labor/parts/total
CREATE TABLE working_details (
  work_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  customer_id INT UNSIGNED NOT NULL COMMENT 'FK to customer',
  vehicle_id INT UNSIGNED NOT NULL COMMENT 'FK to vehicle',
  assigned_mechanic_id INT UNSIGNED NOT NULL COMMENT 'FK to mechanics',
  service_id INT UNSIGNED NOT NULL COMMENT 'FK to service_details',
  status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'
    COMMENT 'Current work order status',
  labor_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total labor charges',
  parts_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Total parts charges',
  total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Grand total (labor + parts)',
  start_date DATE NOT NULL,
  completion_date DATE NULL,
  notes TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (work_id),
  CONSTRAINT fk_work_customer FOREIGN KEY (customer_id)
    REFERENCES customer(customer_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_work_vehicle FOREIGN KEY (vehicle_id)
    REFERENCES vehicle(vehicle_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_work_mechanic FOREIGN KEY (assigned_mechanic_id)
    REFERENCES mechanics(mechanic_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_work_service FOREIGN KEY (service_id)
    REFERENCES service_details(service_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_work_labor CHECK (labor_cost >= 0),
  CONSTRAINT chk_work_parts CHECK (parts_cost >= 0),
  CONSTRAINT chk_work_total CHECK (total_cost >= 0),
  CONSTRAINT chk_work_dates CHECK (completion_date IS NULL OR completion_date >= start_date)
) ENGINE=InnoDB COMMENT='Work orders for services performed';

-- Indexes for common queries
CREATE INDEX idx_work_status ON working_details (status);
CREATE INDEX idx_work_mech ON working_details (assigned_mechanic_id);
CREATE INDEX idx_work_vehicle ON working_details (vehicle_id);
CREATE INDEX idx_work_start_date ON working_details (start_date);

-- =====================================================================
-- WORK_PARTS TABLE (Junction)
-- =====================================================================
-- Many-to-many: links work orders to products/parts used
-- Stores unit_price and quantity at time of work (snapshot for historical accuracy)
CREATE TABLE work_parts (
  work_id INT UNSIGNED NOT NULL COMMENT 'FK to working_details',
  product_id INT UNSIGNED NOT NULL COMMENT 'FK to product_details',
  quantity INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Number of units used',
  unit_price DECIMAL(10,2) NOT NULL COMMENT 'Price per unit at time of work',
  line_total DECIMAL(10,2) NOT NULL COMMENT 'quantity * unit_price',
  PRIMARY KEY (work_id, product_id),
  CONSTRAINT fk_workparts_work FOREIGN KEY (work_id)
    REFERENCES working_details(work_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE COMMENT 'Delete parts records if work order deleted',
  CONSTRAINT fk_workparts_product FOREIGN KEY (product_id)
    REFERENCES product_details(product_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_workparts_qty CHECK (quantity > 0),
  CONSTRAINT chk_workparts_price CHECK (unit_price >= 0),
  CONSTRAINT chk_workparts_total CHECK (line_total >= 0)
) ENGINE=InnoDB COMMENT='Junction table: parts used in each work order';

-- =====================================================================
-- INCOME TABLE
-- =====================================================================
-- Payments received for completed work orders
CREATE TABLE income (
  income_id INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  work_id INT UNSIGNED NOT NULL COMMENT 'FK to working_details',
  amount DECIMAL(10,2) NOT NULL COMMENT 'Payment amount received',
  tax DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Sales tax amount',
  payment_method ENUM('cash', 'credit_card', 'debit_card', 'check', 'bank_transfer') NOT NULL
    COMMENT 'How customer paid',
  payment_date DATE NOT NULL,
  transaction_reference VARCHAR(100) COMMENT 'Receipt/transaction ID',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (income_id),
  CONSTRAINT fk_income_work FOREIGN KEY (work_id)
    REFERENCES working_details(work_id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT chk_income_amount CHECK (amount > 0),
  CONSTRAINT chk_income_tax CHECK (tax >= 0)
) ENGINE=InnoDB COMMENT='Payments received for work orders';

CREATE INDEX idx_income_work ON income (work_id);
CREATE INDEX idx_income_date ON income (payment_date);

-- =====================================================================
-- VIEW DEMO: vw_open_jobs
-- =====================================================================
-- View to show all open (pending or in_progress) work orders with details
DROP VIEW IF EXISTS vw_open_jobs;
CREATE VIEW vw_open_jobs AS
SELECT
  wd.work_id,
  wd.status,
  wd.start_date,
  CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
  c.phone AS customer_phone,
  CONCAT(v.year, ' ', v.make, ' ', v.model) AS vehicle_info,
  v.vin,
  CONCAT(m.first_name, ' ', m.last_name) AS mechanic_name,
  s.service_name,
  wd.labor_cost,
  wd.parts_cost,
  wd.total_cost,
  wd.notes
FROM working_details wd
JOIN customer c ON c.customer_id = wd.customer_id
JOIN vehicle v ON v.vehicle_id = wd.vehicle_id
JOIN mechanics m ON m.mechanic_id = wd.assigned_mechanic_id
JOIN service_details s ON s.service_id = wd.service_id
WHERE wd.status IN ('pending', 'in_progress')
ORDER BY wd.start_date ASC, wd.work_id ASC;
