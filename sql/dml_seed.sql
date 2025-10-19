-- sql/dml_seed.sql
-- Seed data for Mechfleet demo
-- Concepts demonstrated:
-- - INSERT single and multi-row
-- - Maintaining referential integrity (FK order)
-- - JSON literals in INSERT
-- - Using NULL and DEFAULT
-- - Natural keys vs surrogate keys

-- Manufacturers
INSERT INTO manufacturers (name, country_code, founded_year) VALUES
  ('Acme Trucks', 'US', 1958),
  ('RoadRunner Motors', 'DE', 1972),
  ('Sunrise Automotive', 'JP', 1965);

-- Vehicles (FK to manufacturers)
INSERT INTO vehicles (manufacturer_id, vin, model, model_year, type, capacity_kg, acquired_on, active, attributes) VALUES
  (1, '1A4AABBC5KD501999', 'Titan 500', 2021, 'truck', 12000, '2021-06-01', 1, JSON_OBJECT('color','red','doors',2)),
  (1, '1A4AABBC5KD502000', 'Titan 300', 2020, 'truck', 9000, '2020-02-15', 1, JSON_OBJECT('color','blue')),
  (2, 'WDBJF65J1YB123456', 'Sprinter X', 2022, 'van', 3000, '2022-03-20', 1, NULL),
  (3, 'JHMCM56557C404453', 'Shinrai', 2019, 'car', 500, '2019-10-05', 0, JSON_OBJECT('notes','decommissioned'));

-- Drivers
INSERT INTO drivers (first_name, last_name, email, phone, hired_at, active) VALUES
  ('Alice', 'Nguyen', 'alice.nguyen@example.com', '+1-555-1010', '2020-01-10', 1),
  ('Bob', 'Martinez', 'bob.martinez@example.com', '+1-555-2020', '2019-07-22', 1),
  ('Carol', 'Singh', 'carol.singh@example.com', '+1-555-3030', '2023-04-01', 1);

-- Assignments
-- Note: open-ended assignment has NULL ends_at
INSERT INTO assignments (vehicle_id, driver_id, starts_at, ends_at) VALUES
  (1, 1, '2023-01-01 08:00:00', '2023-06-30 18:00:00'),
  (1, 2, '2023-07-01 08:00:00', NULL),
  (2, 3, '2023-03-15 09:00:00', NULL),
  (3, 1, '2024-02-01 08:00:00', '2024-05-01 17:00:00');

-- Maintenance
INSERT INTO maintenance (vehicle_id, performed_on, odometer_km, kind, cost, notes) VALUES
  (1, '2023-02-01', 15000, 'Oil Change', 129.99, 'Full synthetic'),
  (1, '2023-07-15', 30000, 'Tire Rotation', 89.50, NULL),
  (2, '2023-04-10', 8000, 'Brake Pads', 320.00, 'Front axle'),
  (3, '2024-03-20', 12000, 'Inspection', 0.00, 'Warranty service');
