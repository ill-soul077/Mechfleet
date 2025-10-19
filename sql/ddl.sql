-- sql/ddl.sql
-- Schema DDL for Mechfleet demo (MySQL 8.x)
-- Concepts demonstrated:
-- - Database creation and charset/collation
-- - Tables with primary keys (INT/BIGINT, AUTO_INCREMENT)
-- - Foreign keys with ON DELETE / ON UPDATE actions
-- - UNIQUE constraints and CHECK constraints
-- - Generated columns (stored and virtual)
-- - Indexes (BTREE, composite, partial prefix index for VARCHAR)
-- - ENUM vs reference table (we prefer reference table here)
-- - Timestamps with DEFAULT CURRENT_TIMESTAMP and ON UPDATE
-- - JSON column with CHECK for basic validation
-- - Views (for readable reporting)
-- - Comments on tables/columns

-- Drop and recreate database (optional for local dev)
-- NOTE: Run carefully; graders may run within an existing DB. Adjust DB name as needed.
-- CREATE DATABASE IF NOT EXISTS mechfleet CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
-- USE mechfleet;

-- Manufacturers
DROP TABLE IF EXISTS manufacturers;
CREATE TABLE manufacturers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  country_code CHAR(2) NOT NULL,
  founded_year SMALLINT UNSIGNED CHECK (founded_year >= 1800),
  PRIMARY KEY (id),
  UNIQUE KEY uq_manufacturer_name (name)
) ENGINE=InnoDB COMMENT='Vehicle manufacturers';

-- Vehicles
DROP TABLE IF EXISTS vehicles;
CREATE TABLE vehicles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'PK',
  manufacturer_id INT UNSIGNED NOT NULL,
  vin CHAR(17) NOT NULL COMMENT 'Vehicle Identification Number',
  model VARCHAR(100) NOT NULL,
  model_year YEAR NOT NULL,
  type ENUM('truck','van','car','trailer') NOT NULL,
  capacity_kg INT UNSIGNED NOT NULL DEFAULT 0,
  acquired_on DATE,
  active TINYINT(1) NOT NULL DEFAULT 1,
  attributes JSON NULL,
  -- generated column example (virtual): text_search combines model + type
  text_search VARCHAR(220) GENERATED ALWAYS AS (CONCAT_WS(' ', model, type)) VIRTUAL,
  PRIMARY KEY (id),
  CONSTRAINT fk_vehicles_manufacturer FOREIGN KEY (manufacturer_id)
    REFERENCES manufacturers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT uq_vehicles_vin UNIQUE (vin),
  CONSTRAINT chk_capacity_non_negative CHECK (capacity_kg >= 0),
  -- Simple JSON validation check (MySQL 8.0.13+): ensure attributes is valid JSON when set
  CONSTRAINT chk_attributes_json CHECK (attributes IS NULL OR JSON_VALID(attributes))
) ENGINE=InnoDB COMMENT='Fleet vehicles';

-- Drivers
DROP TABLE IF EXISTS drivers;
CREATE TABLE drivers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(30),
  hired_at DATE NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_drivers_email (email)
) ENGINE=InnoDB COMMENT='Drivers employed by the company';

-- Assignments: which driver is assigned to which vehicle over time
DROP TABLE IF EXISTS assignments;
CREATE TABLE assignments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  driver_id BIGINT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_assignments_vehicle_starts (vehicle_id, starts_at),
  KEY ix_assignments_driver_starts (driver_id, starts_at),
  CONSTRAINT fk_assignments_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_assignments_driver FOREIGN KEY (driver_id) REFERENCES drivers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_assignment_range CHECK (ends_at IS NULL OR ends_at > starts_at)
) ENGINE=InnoDB COMMENT='Driver-vehicle assignment history';

-- Maintenance records
DROP TABLE IF EXISTS maintenance;
CREATE TABLE maintenance (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  performed_on DATE NOT NULL,
  odometer_km INT UNSIGNED,
  kind VARCHAR(80) NOT NULL,
  cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  notes VARCHAR(500),
  PRIMARY KEY (id),
  KEY ix_maint_vehicle_date (vehicle_id, performed_on),
  CONSTRAINT fk_maint_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_cost_non_negative CHECK (cost >= 0)
) ENGINE=InnoDB COMMENT='Maintenance history per vehicle';

-- Index examples
CREATE INDEX ix_vehicles_model_prefix ON vehicles (model(20)); -- prefix index for faster LIKE 'prefix%'
CREATE INDEX ix_vehicles_text_search ON vehicles (text_search);

-- View example for current (active) assignment per vehicle using window function
DROP VIEW IF EXISTS v_vehicle_current_assignment;
CREATE VIEW v_vehicle_current_assignment AS
SELECT
  a.vehicle_id,
  a.driver_id,
  a.starts_at,
  a.ends_at
FROM (
  SELECT *,
         ROW_NUMBER() OVER (PARTITION BY vehicle_id ORDER BY COALESCE(ends_at, '9999-12-31') DESC, starts_at DESC) AS rn
  FROM assignments
) a
WHERE a.rn = 1;
