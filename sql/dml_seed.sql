-- sql/dml_seed.sql
-- Sample data for Mechfleet management system
-- This file demonstrates DML: bulk INSERT statements with realistic data
-- Import AFTER running sql/ddl.sql
-- Uses DATE_SUB(CURDATE(), INTERVAL ...) for varied historical dates

-- =====================================================================
-- DML: Inserting managers (demonstrates bulk INSERT)
-- =====================================================================
INSERT INTO manager (first_name, last_name, email, phone, hired_date, active) VALUES
('Robert', 'Johnson', 'robert.johnson@mechfleet.com', '555-0101', '2015-03-15', 1),
('Patricia', 'Williams', 'patricia.williams@mechfleet.com', '555-0102', '2016-07-22', 1),
('Michael', 'Brown', 'michael.brown@mechfleet.com', '555-0103', '2017-11-10', 1),
('Linda', 'Davis', 'linda.davis@mechfleet.com', '555-0104', '2018-02-28', 1),
('James', 'Miller', 'james.miller@mechfleet.com', '555-0105', '2019-05-14', 0);

-- =====================================================================
-- DML: Inserting customers (50 rows, demonstrates bulk INSERT)
-- =====================================================================
INSERT INTO customer (first_name, last_name, email, phone, address, city, state, zip_code) VALUES
('John','Smith','john.smith@example.com','555-1001','123 Maple St','Springfield','IL','62701'),
('Mary','Johnson','mary.johnson@example.com','555-1002','456 Oak Ave','Columbus','OH','43004'),
('William','Williams','william.williams@example.com','555-1003','789 Pine Rd','Austin','TX','73301'),
('Elizabeth','Brown','elizabeth.brown@example.com','555-1004','12 Cedar Ln','Phoenix','AZ','85001'),
('David','Jones','david.jones@example.com','555-1005','34 Birch Blvd','Denver','CO','80014'),
('Jennifer','Garcia','jennifer.garcia@example.com','555-1006','56 Walnut Dr','Seattle','WA','98101'),
('Richard','Miller','richard.miller@example.com','555-1007','78 Cherry Ct','Madison','WI','53703'),
('Susan','Davis','susan.davis@example.com','555-1008','90 Aspen Way','Nashville','TN','37201'),
('Joseph','Rodriguez','joseph.rodriguez@example.com','555-1009','101 Poplar St','Atlanta','GA','30301'),
('Sarah','Martinez','sarah.martinez@example.com','555-1010','202 Elm St','Raleigh','NC','27601'),
('Charles','Hernandez','charles.hernandez@example.com','555-1011','303 Willow Ave','Portland','OR','97035'),
('Karen','Lopez','karen.lopez@example.com','555-1012','404 Sycamore Rd','Miami','FL','33101'),
('Thomas','Gonzalez','thomas.gonzalez@example.com','555-1013','505 Dogwood Dr','Chicago','IL','60601'),
('Nancy','Wilson','nancy.wilson@example.com','555-1014','606 Magnolia Ct','Detroit','MI','48201'),
('Christopher','Anderson','christopher.anderson@example.com','555-1015','707 Redwood Ln','Boise','ID','83701'),
('Lisa','Thomas','lisa.thomas@example.com','555-1016','808 Cypress Blvd','Richmond','VA','23219'),
('Daniel','Taylor','daniel.taylor@example.com','555-1017','909 Spruce Way','Newark','NJ','07102'),
('Betty','Moore','betty.moore@example.com','555-1018','111 Palm St','Orlando','FL','32801'),
('Matthew','Jackson','matthew.jackson@example.com','555-1019','222 Pinecone Ave','Dallas','TX','75201'),
('Sandra','Martin','sandra.martin@example.com','555-1020','333 River Rd','Cleveland','OH','44101'),
('Anthony','Lee','anthony.lee@example.com','555-1021','444 Lakeview Dr','Minneapolis','MN','55401'),
('Ashley','Perez','ashley.perez@example.com','555-1022','555 Hilltop Ct','Salt Lake City','UT','84101'),
('Mark','Thompson','mark.thompson@example.com','555-1023','666 Meadow Ln','Omaha','NE','68102'),
('Donna','White','donna.white@example.com','555-1024','777 Brookside Blvd','Kansas City','MO','64106'),
('Paul','Harris','paul.harris@example.com','555-1025','888 Sunrise Dr','San Diego','CA','92101'),
('Dorothy','Sanchez','dorothy.sanchez@example.com','555-1026','999 Sunset Ave','Tucson','AZ','85701'),
('Steven','Clark','steven.clark@example.com','555-1027','1212 Ridge Rd','Buffalo','NY','14201'),
('Michelle','Ramirez','michelle.ramirez@example.com','555-1028','1313 Valley St','Birmingham','AL','35203'),
('Andrew','Lewis','andrew.lewis@example.com','555-1029','1414 Canyon Dr','Pittsburgh','PA','15222'),
('Emily','Robinson','emily.robinson@example.com','555-1030','1515 Creek Ct','Charlotte','NC','28202'),
('Joshua','Walker','joshua.walker@example.com','555-1031','1616 Lake St','Indianapolis','IN','46204'),
('Barbara','Young','barbara.young@example.com','555-1032','1717 Forest Ave','Milwaukee','WI','53202'),
('Kevin','Allen','kevin.allen@example.com','555-1033','1818 Prairie Rd','Tulsa','OK','74103'),
('Jessica','King','jessica.king@example.com','555-1034','1919 Garden Ln','Las Vegas','NV','88901'),
('Brian','Wright','brian.wright@example.com','555-1035','2020 Park Ave','Reno','NV','89501'),
('Angela','Scott','angela.scott@example.com','555-1036','2121 Center St','Baton Rouge','LA','70801'),
('Edward','Torres','edward.torres@example.com','555-1037','2222 Market St','Albuquerque','NM','87101'),
('Stephanie','Nguyen','stephanie.nguyen@example.com','555-1038','2323 College Ave','Bozeman','MT','59715'),
('George','Hill','george.hill@example.com','555-1039','2424 Prospect Rd','Cheyenne','WY','82001'),
('Rebecca','Flores','rebecca.flores@example.com','555-1040','2525 Prospect Ave','Helena','MT','59601'),
('Timothy','Green','timothy.green@example.com','555-1041','2626 Division St','Anchorage','AK','99501'),
('Sharon','Adams','sharon.adams@example.com','555-1042','2727 Riverfront Dr','Juneau','AK','99801'),
('Jason','Baker','jason.baker@example.com','555-1043','2828 High St','Honolulu','HI','96801'),
('Amy','Gonzales','amy.gonzales@example.com','555-1044','2929 Low St','Hilo','HI','96720'),
('Jeffrey','Nelson','jeffrey.nelson@example.com','555-1045','3030 North Ave','Boise','ID','83702'),
('Carol','Carter','carol.carter@example.com','555-1046','3131 South St','Fargo','ND','58102'),
('Ryan','Mitchell','ryan.mitchell@example.com','555-1047','3232 East Blvd','Sioux Falls','SD','57104'),
('Katherine','Perez','katherine.perez@example.com','555-1048','3333 West Ave','Des Moines','IA','50309'),
('Eric','Roberts','eric.roberts@example.com','555-1049','3434 Main St','Oklahoma City','OK','73102');

-- =====================================================================
-- DML: Inserting vehicles (~70 rows, linked to customers by pattern)
-- Pattern: vehicle_id i -> customer_id ((i-1) % 50)+1
-- =====================================================================
INSERT INTO vehicle (customer_id, vin, make, model, year, color, mileage, license_plate) VALUES
(1,'1HGCM82633A000001','Toyota','Camry',2018,'Silver',45000,'MEF-0001'),
(2,'1HGCM82633A000002','Honda','Civic',2019,'Blue',38000,'MEF-0002'),
(3,'1HGCM82633A000003','Ford','F-150',2016,'Red',72000,'MEF-0003'),
(4,'1HGCM82633A000004','Chevrolet','Silverado',2017,'Black',69000,'MEF-0004'),
(5,'1HGCM82633A000005','Nissan','Altima',2020,'White',21000,'MEF-0005'),
(6,'1HGCM82633A000006','Jeep','Wrangler',2015,'Green',88000,'MEF-0006'),
(7,'1HGCM82633A000007','Subaru','Outback',2018,'Gray',54000,'MEF-0007'),
(8,'1HGCM82633A000008','BMW','330i',2019,'Black',30000,'MEF-0008'),
(9,'1HGCM82633A000009','Audi','A4',2017,'White',51000,'MEF-0009'),
(10,'1HGCM82633A000010','Mercedes','C300',2016,'Silver',60000,'MEF-0010'),
(11,'1HGCM82633A000011','Hyundai','Elantra',2021,'Blue',15000,'MEF-0011'),
(12,'1HGCM82633A000012','Kia','Sorento',2018,'Brown',47000,'MEF-0012'),
(13,'1HGCM82633A000013','Volkswagen','Golf',2015,'Red',82000,'MEF-0013'),
(14,'1HGCM82633A000014','Toyota','RAV4',2020,'Gray',24000,'MEF-0014'),
(15,'1HGCM82633A000015','Honda','Accord',2017,'Black',59000,'MEF-0015'),
(16,'1HGCM82633A000016','Tesla','Model 3',2021,'White',12000,'MEF-0016'),
(17,'1HGCM82633A000017','Ford','Escape',2016,'Blue',77000,'MEF-0017'),
(18,'1HGCM82633A000018','Chevrolet','Malibu',2019,'Silver',36000,'MEF-0018'),
(19,'1HGCM82633A000019','Nissan','Rogue',2018,'Green',49000,'MEF-0019'),
(20,'1HGCM82633A000020','Jeep','Grand Cherokee',2017,'Black',64000,'MEF-0020'),
(21,'1HGCM82633A000021','Subaru','Forester',2019,'White',33000,'MEF-0021'),
(22,'1HGCM82633A000022','BMW','X3',2018,'Blue',42000,'MEF-0022'),
(23,'1HGCM82633A000023','Audi','Q5',2017,'Gray',58000,'MEF-0023'),
(24,'1HGCM82633A000024','Mercedes','GLC',2016,'Silver',61000,'MEF-0024'),
(25,'1HGCM82633A000025','Hyundai','Santa Fe',2020,'White',26000,'MEF-0025'),
(26,'1HGCM82633A000026','Kia','Sportage',2019,'Red',34000,'MEF-0026'),
(27,'1HGCM82633A000027','Volkswagen','Tiguan',2018,'Blue',47000,'MEF-0027'),
(28,'1HGCM82633A000028','Toyota','Highlander',2017,'Black',65000,'MEF-0028'),
(29,'1HGCM82633A000029','Honda','Pilot',2016,'Brown',73000,'MEF-0029'),
(30,'1HGCM82633A000030','Ford','Explorer',2021,'Silver',18000,'MEF-0030'),
(31,'1HGCM82633A000031','Chevrolet','Tahoe',2019,'Gray',41000,'MEF-0031'),
(32,'1HGCM82633A000032','Nissan','Murano',2018,'Blue',52000,'MEF-0032'),
(33,'1HGCM82633A000033','Jeep','Compass',2017,'Green',66000,'MEF-0033'),
(34,'1HGCM82633A000034','Subaru','Crosstrek',2020,'Yellow',22000,'MEF-0034'),
(35,'1HGCM82633A000035','BMW','528i',2016,'Black',70000,'MEF-0035'),
(36,'1HGCM82633A000036','Audi','A6',2015,'White',83000,'MEF-0036'),
(37,'1HGCM82633A000037','Mercedes','E300',2017,'Silver',59000,'MEF-0037'),
(38,'1HGCM82633A000038','Hyundai','Tucson',2019,'Blue',37000,'MEF-0038'),
(39,'1HGCM82633A000039','Kia','Optima',2018,'Red',50000,'MEF-0039'),
(40,'1HGCM82633A000040','Volkswagen','Passat',2017,'Gray',64000,'MEF-0040'),
(41,'1HGCM82633A000041','Toyota','Corolla',2016,'White',76000,'MEF-0041'),
(42,'1HGCM82633A000042','Honda','Fit',2015,'Blue',90000,'MEF-0042'),
(43,'1HGCM82633A000043','Ford','Fusion',2018,'Black',52000,'MEF-0043'),
(44,'1HGCM82633A000044','Chevrolet','Cruze',2019,'Silver',30000,'MEF-0044'),
(45,'1HGCM82633A000045','Nissan','Sentra',2020,'Gray',23000,'MEF-0045'),
(46,'1HGCM82633A000046','Jeep','Renegade',2017,'Green',61000,'MEF-0046'),
(47,'1HGCM82633A000047','Subaru','Impreza',2016,'Red',78000,'MEF-0047'),
(48,'1HGCM82633A000048','BMW','X5',2019,'Black',35000,'MEF-0048'),
(49,'1HGCM82633A000049','Audi','Q7',2018,'White',48000,'MEF-0049'),
(50,'1HGCM82633A000050','Mercedes','GLE350',2017,'Silver',62000,'MEF-0050'),
(1,'1HGCM82633A000051','Toyota','Prius',2016,'Blue',77000,'MEF-0051'),
(2,'1HGCM82633A000052','Honda','CR-V',2021,'Gray',14000,'MEF-0052'),
(3,'1HGCM82633A000053','Ford','Mustang',2019,'Red',32000,'MEF-0053'),
(4,'1HGCM82633A000054','Chevrolet','Camaro',2018,'Yellow',41000,'MEF-0054'),
(5,'1HGCM82633A000055','Nissan','Maxima',2017,'White',67000,'MEF-0055'),
(6,'1HGCM82633A000056','Jeep','Cherokee',2016,'Black',82000,'MEF-0056'),
(7,'1HGCM82633A000057','Subaru','Legacy',2015,'Silver',91000,'MEF-0057'),
(8,'1HGCM82633A000058','BMW','M3',2020,'Blue',22000,'MEF-0058'),
(9,'1HGCM82633A000059','Audi','S4',2019,'Gray',29000,'MEF-0059'),
(10,'1HGCM82633A000060','Mercedes','C43 AMG',2018,'Black',36000,'MEF-0060'),
(11,'1HGCM82633A000061','Hyundai','Sonata',2017,'White',65000,'MEF-0061'),
(12,'1HGCM82633A000062','Kia','Telluride',2021,'Green',12000,'MEF-0062'),
(13,'1HGCM82633A000063','Volkswagen','Atlas',2020,'Blue',26000,'MEF-0063'),
(14,'1HGCM82633A000064','Toyota','Tacoma',2019,'Red',39000,'MEF-0064'),
(15,'1HGCM82633A000065','Honda','Ridgeline',2018,'Silver',47000,'MEF-0065'),
(16,'1HGCM82633A000066','Tesla','Model Y',2021,'White',11000,'MEF-0066'),
(17,'1HGCM82633A000067','Ford','Bronco',2021,'Blue',9000,'MEF-0067'),
(18,'1HGCM82633A000068','Chevrolet','Bolt',2020,'White',20000,'MEF-0068'),
(19,'1HGCM82633A000069','Nissan','Leaf',2019,'Green',28000,'MEF-0069'),
(20,'1HGCM82633A000070','Jeep','Gladiator',2020,'Gray',24000,'MEF-0070');

-- =====================================================================
-- DML: Inserting mechanics (10 rows, managed_by links)
-- =====================================================================
INSERT INTO mechanics (first_name, last_name, email, phone, specialty, hourly_rate, managed_by, hired_date, active) VALUES
('Alex','Turner','alex.turner@mechfleet.com','555-2001','Engine',85.00,1,'2018-01-15',1),
('Brooke','Carter','brooke.carter@mechfleet.com','555-2002','Transmission',88.00,2,'2019-03-22',1),
('Chris','Nguyen','chris.nguyen@mechfleet.com','555-2003','Electrical',75.00,1,'2020-05-10',1),
('Dana','Lopez','dana.lopez@mechfleet.com','555-2004','Brakes',70.00,3,'2017-09-01',1),
('Evan','Gonzalez','evan.gonzalez@mechfleet.com','555-2005','Suspension',72.50,2,'2016-11-30',1),
('Faith','Kim','faith.kim@mechfleet.com','555-2006','Diagnostics',90.00,4,'2021-02-14',1),
('Gabe','Hernandez','gabe.hernandez@mechfleet.com','555-2007','HVAC',65.00,5,'2015-07-07',1),
('Haley','Singh','haley.singh@mechfleet.com','555-2008','Tires/Alignment',68.00,3,'2018-04-18',1),
('Ivan','Kowalski','ivan.kowalski@mechfleet.com','555-2009','Body/Trim',60.00,4,'2020-08-25',1),
('Jade','Owen','jade.owen@mechfleet.com','555-2010','Generalist',55.00,2,'2019-12-05',1);

-- =====================================================================
-- DML: Inserting service catalog (12 rows)
-- =====================================================================
INSERT INTO service_details (service_name, description, base_price, estimated_hours, active) VALUES
('Oil Change','Replace engine oil and filter',49.99,0.75,1),
('Brake Pad Replacement','Replace brake pads on both axles',199.99,2.50,1),
('Tire Rotation','Rotate tires to even wear',29.99,0.50,1),
('Battery Replacement','Replace and test battery',129.99,0.80,1),
('Engine Diagnostics','Scan and diagnose engine issues',99.99,1.50,1),
('Transmission Service','Fluid change and inspection',249.99,3.00,1),
('AC Recharge','Recharge A/C system and leak test',149.99,1.20,1),
('Wheel Alignment','Four-wheel alignment and adjustment',119.99,1.00,1),
('Coolant Flush','Flush cooling system and refill',139.99,1.30,1),
('Spark Plug Replacement','Replace spark plugs (4-8 cyl)',179.99,2.00,1),
('Timing Belt Replacement','Replace timing belt and inspect components',799.99,6.50,1),
('Brake Fluid Flush','Flush brake fluid and bleed system',99.99,1.20,1);

-- =====================================================================
-- DML: Inserting products (40 rows)
-- =====================================================================
INSERT INTO product_details (sku, product_name, description, unit_price, stock_qty, reorder_level, category) VALUES
('SKU-0001','Engine Oil 5W-30','Synthetic motor oil 5W-30, 1qt',8.99,200,50,'Fluids'),
('SKU-0002','Oil Filter','Standard oil filter',6.49,150,40,'Filters'),
('SKU-0003','Air Filter','Engine air filter',14.99,120,30,'Filters'),
('SKU-0004','Cabin Air Filter','HVAC cabin filter',15.99,100,25,'Filters'),
('SKU-0005','Brake Pads Front','Ceramic brake pads (front set)',69.99,80,20,'Brakes'),
('SKU-0006','Brake Pads Rear','Ceramic brake pads (rear set)',64.99,75,20,'Brakes'),
('SKU-0007','Brake Rotors','Front brake rotors (pair)',129.99,40,10,'Brakes'),
('SKU-0008','Battery 12V','Maintenance-free car battery',119.99,30,10,'Electrical'),
('SKU-0009','Spark Plugs (4)','Set of 4 iridium spark plugs',39.99,90,20,'Electrical'),
('SKU-0010','Coolant (1gal)','Long-life engine coolant',18.99,110,30,'Fluids'),
('SKU-0011','ATF Fluid (1qt)','Automatic transmission fluid',9.99,140,35,'Fluids'),
('SKU-0012','AC Refrigerant R134a','A/C refrigerant can',24.99,70,20,'HVAC'),
('SKU-0013','Belt - Serpentine','Serpentine belt',29.99,60,15,'Engine Parts'),
('SKU-0014','Timing Belt','Timing belt',79.99,25,10,'Engine Parts'),
('SKU-0015','Water Pump','Engine water pump',149.99,20,5,'Engine Parts'),
('SKU-0016','Thermostat','Engine thermostat',19.99,55,15,'Engine Parts'),
('SKU-0017','Wiper Blades (pair)','All-season wiper blades',21.99,120,30,'Accessories'),
('SKU-0018','Headlight Bulb','Halogen headlight bulb',12.99,130,35,'Electrical'),
('SKU-0019','Brake Fluid (1qt)','DOT 4 brake fluid',7.99,100,25,'Fluids'),
('SKU-0020','Power Steering Fluid','Power steering fluid',8.49,90,25,'Fluids'),
('SKU-0021','Fuel Filter','In-line fuel filter',19.99,70,20,'Filters'),
('SKU-0022','Alternator','12V alternator',229.99,10,3,'Electrical'),
('SKU-0023','Starter Motor','High-torque starter',199.99,12,3,'Electrical'),
('SKU-0024','Radiator','Aluminum radiator',249.99,8,3,'Cooling'),
('SKU-0025','AC Compressor','A/C compressor unit',349.99,6,2,'HVAC'),
('SKU-0026','Shock Absorber (pair)','Front shocks (pair)',159.99,20,5,'Suspension'),
('SKU-0027','Strut Assembly (pair)','Front struts (pair)',249.99,15,5,'Suspension'),
('SKU-0028','Control Arm','Lower control arm',99.99,25,8,'Suspension'),
('SKU-0029','Tie Rod End','Outer tie rod end',29.99,45,12,'Suspension'),
('SKU-0030','Wheel Bearing','Front wheel bearing',59.99,35,10,'Suspension'),
('SKU-0031','All-Season Tire','205/55R16 tire',89.99,50,12,'Tires'),
('SKU-0032','Performance Tire','225/45R17 tire',109.99,40,10,'Tires'),
('SKU-0033','Winter Tire','195/65R15 tire',84.99,45,12,'Tires'),
('SKU-0034','Battery Cable Set','Positive/negative cables',24.99,30,10,'Electrical'),
('SKU-0035','Engine Mount','Rubber engine mount',49.99,25,8,'Engine Parts'),
('SKU-0036','O2 Sensor','Oxygen sensor',69.99,20,6,'Engine Parts'),
('SKU-0037','Mass Air Flow Sensor','MAF sensor',129.99,15,5,'Engine Parts'),
('SKU-0038','Ignition Coil','Ignition coil pack',59.99,30,10,'Electrical'),
('SKU-0039','Wheel Alignment Kit','Shims and hardware',39.99,25,8,'Accessories'),
('SKU-0040','Detailing Kit','Wash, wax, interior cleaner',29.99,60,15,'Accessories');

-- Using number generator via user variables and digit tables for MySQL INSERT compatibility
INSERT INTO working_details (
	customer_id, vehicle_id, assigned_mechanic_id, service_id,
	status, labor_cost, parts_cost, total_cost, start_date, completion_date, notes
)
SELECT
	(SELECT v.customer_id FROM vehicle v WHERE v.vehicle_id = (((seq.n - 1) % 70) + 1)) AS customer_id,
	((seq.n - 1) % 70) + 1 AS vehicle_id,
	((seq.n - 1) % 10) + 1 AS assigned_mechanic_id,
	((seq.n - 1) % 12) + 1 AS service_id,
	CASE
		WHEN (seq.n % 20) = 0 THEN 'cancelled'
		WHEN (seq.n % 5) = 4 THEN 'in_progress'
		WHEN (seq.n % 3) = 0 THEN 'pending'
		ELSE 'completed'
	END AS status,
	ROUND((SELECT sd.estimated_hours FROM service_details sd WHERE sd.service_id = ((seq.n - 1) % 12) + 1)
				* (SELECT m.hourly_rate FROM mechanics m WHERE m.mechanic_id = ((seq.n - 1) % 10) + 1)
				* (1.0 + (seq.n % 3) * 0.1), 2) AS labor_cost,
	0.00 AS parts_cost,
	ROUND((SELECT sd.estimated_hours FROM service_details sd WHERE sd.service_id = ((seq.n - 1) % 12) + 1)
				* (SELECT m.hourly_rate FROM mechanics m WHERE m.mechanic_id = ((seq.n - 1) % 10) + 1)
				* (1.0 + (seq.n % 3) * 0.1), 2) AS total_cost,
	DATE_SUB(CURDATE(), INTERVAL ((seq.n % 330) + 10) DAY) AS start_date,
	CASE
		WHEN (seq.n % 20) = 0 THEN NULL
		WHEN (seq.n % 5) = 4 THEN NULL
		WHEN (seq.n % 3) = 0 THEN NULL
		ELSE DATE_SUB(CURDATE(), INTERVAL ((seq.n % 330) + 7) DAY)
	END AS completion_date,
	CONCAT('Seed work #', seq.n) AS notes
FROM (
	SELECT @n:=@n+1 AS n
	FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
				UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d1
	CROSS JOIN (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
				UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d2
	CROSS JOIN (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
				UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d3
	CROSS JOIN (SELECT @n:=0) init
	LIMIT 100
) AS seq;

INSERT INTO work_parts (work_id, product_id, quantity, unit_price, line_total)
SELECT
	((seq.n - 1) % 100) + 1 AS work_id,
	((seq.n * 7 - 1) % 40) + 1 AS product_id,
	(seq.n % 4) + 1 AS quantity,
	(SELECT pd.unit_price FROM product_details pd WHERE pd.product_id = (((seq.n * 7 - 1) % 40) + 1)) AS unit_price,
	ROUND(((seq.n % 4) + 1) * (SELECT pd.unit_price FROM product_details pd WHERE pd.product_id = (((seq.n * 7 - 1) % 40) + 1)), 2) AS line_total
FROM (
	SELECT @n2:=@n2+1 AS n
	FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
				UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d1
	CROSS JOIN (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
				UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d2
	CROSS JOIN (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
				UNION ALL SELECT 5) d3
	CROSS JOIN (SELECT @n2:=0) init
	LIMIT 250
) AS seq;

-- After inserting parts, update working_details to reflect parts_cost and total_cost
UPDATE working_details w
JOIN (
	SELECT work_id, ROUND(SUM(line_total), 2) AS parts_total
	FROM work_parts
	GROUP BY work_id
) p ON p.work_id = w.work_id
SET w.parts_cost = p.parts_total,
		w.total_cost = ROUND(w.labor_cost + p.parts_total, 2);

-- Build a 1..120 row sequence and map across completed works in a round-robin manner
INSERT INTO income (work_id, amount, tax, payment_method, payment_date, transaction_reference)
SELECT
	c.work_id,
	c.total_cost AS amount,
	ROUND(c.total_cost * 0.08, 2) AS tax,
	CASE (s.rn % 5)
		WHEN 0 THEN 'cash'
		WHEN 1 THEN 'credit_card'
		WHEN 2 THEN 'debit_card'
		WHEN 3 THEN 'check'
		ELSE 'bank_transfer'
	END AS payment_method,
	DATE_ADD(c.completion_date, INTERVAL (s.rn % 5) DAY) AS payment_date,
	CONCAT('TX-', LPAD(s.rn, 5, '0')) AS transaction_reference
FROM (
  SELECT @i:=@i+1 AS rn
  FROM (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
	  UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d1
  CROSS JOIN (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
	  UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d2
  CROSS JOIN (SELECT 0 UNION ALL SELECT 1) d3
  CROSS JOIN (SELECT @i:=0) init
  LIMIT 120
) s
JOIN (
	SELECT w.work_id, w.completion_date, w.total_cost,
				 @r:=@r+1 AS rownum
	FROM working_details w
	JOIN (SELECT @r:=0) r
	WHERE w.status = 'completed'
	ORDER BY w.work_id
) c ON (((s.rn - 1) % (SELECT COUNT(*) FROM working_details WHERE status = 'completed')) + 1) = c.rownum;

-- End of seed data
