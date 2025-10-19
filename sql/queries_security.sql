-- sql/queries_security.sql
-- Concepts: SQL injection-safe patterns with placeholders, IN-lists via JSON_TABLE for dynamic params

-- Safe filter by email with a named placeholder (bind via PDO)
-- Bind :email in application code
SELECT id, first_name, last_name FROM drivers WHERE email = :email;

-- Safe LIKE with placeholder (driver provides pattern including %)
SELECT id, model FROM vehicles WHERE model LIKE :pattern;

-- Dynamic IN list example using JSON_TABLE to avoid string concatenation
-- Provide a JSON array string as :ids (e.g., "[1,2,3]") and join via JSON_TABLE
SELECT v.*
FROM vehicles v
JOIN JSON_TABLE(:ids, '$[*]' COLUMNS (id BIGINT PATH '$')) jt ON jt.id = v.id;
