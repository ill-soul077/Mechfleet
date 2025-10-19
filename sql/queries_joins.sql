-- sql/queries_joins.sql
-- Concepts: INNER JOIN, LEFT JOIN, join on FKs, multi-table joins, USING vs ON, table aliases

-- Vehicle with manufacturer name (INNER JOIN)
SELECT v.id, v.vin, v.model, m.name AS manufacturer
FROM vehicles AS v
JOIN manufacturers AS m ON m.id = v.manufacturer_id
ORDER BY v.id;

-- Vehicles and their current driver, if any (LEFT JOIN view)
SELECT v.id, v.model, d.first_name, d.last_name
FROM vehicles v
LEFT JOIN v_vehicle_current_assignment ca ON ca.vehicle_id = v.id
LEFT JOIN drivers d ON d.id = ca.driver_id
ORDER BY v.id;

-- Maintenance joined to vehicles and manufacturers
SELECT mnt.id, v.vin, v.model, m.name AS manufacturer, mnt.kind, mnt.cost
FROM maintenance mnt
JOIN vehicles v ON v.id = mnt.vehicle_id
JOIN manufacturers m ON m.id = v.manufacturer_id
ORDER BY mnt.performed_on DESC;
