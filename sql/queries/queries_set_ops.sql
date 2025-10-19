-- queries_set_ops.sql
-- Demonstrates set operations and subqueries in MySQL (which lacks native INTERSECT/EXCEPT).
-- Each example includes:
-- - Concept name
-- - Expected output columns
-- - Complexity notes (rough big-O with indexes assumed on PK/FKs)

-- Complexity: O(N log N) due to deduplication; each branch O(N) with proper indexes.
SELECT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
UNION
SELECT c2.customer_id, CONCAT(c2.first_name,' ',c2.last_name) AS customer_name
FROM income i
JOIN working_details w2 ON w2.work_id = i.work_id
JOIN customer c2 ON c2.customer_id = w2.customer_id
ORDER BY customer_id;

-- Complexity: O(N) concatenation without dedup; ORDER BY adds O(N log N).
SELECT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
UNION ALL
SELECT c2.customer_id, CONCAT(c2.first_name,' ',c2.last_name) AS customer_name
FROM income i
JOIN working_details w2 ON w2.work_id = i.work_id
JOIN customer c2 ON c2.customer_id = w2.customer_id
ORDER BY customer_id;

-- Complexity: O(N) with index on income.work_id and working_details.work_id enabling semi-join.
SELECT DISTINCT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
JOIN income i ON i.work_id = w.work_id
ORDER BY c.customer_id;

-- Complexity: O(N) with anti-join using NOT EXISTS; ensure index on income.work_id.
SELECT DISTINCT c.customer_id, CONCAT(c.first_name,' ',c.last_name) AS customer_name
FROM working_details w
JOIN customer c ON c.customer_id = w.customer_id
WHERE NOT EXISTS (
  SELECT 1
  FROM income i
  WHERE i.work_id = w.work_id
)
ORDER BY c.customer_id;

-- 5) Correlated subquery example: compare each work's total_cost to the average for its service
-- Concept: Correlated subquery per row, referencing outer row's service_id.
-- Expected: work_id, service_id, total_cost, service_avg, delta
-- Complexity: Naively O(N^2). With aggregation per service and JOIN, you can reduce to O(N).
SELECT w.work_id, w.service_id, w.total_cost,
       (SELECT AVG(w2.total_cost)
        FROM working_details w2
        WHERE w2.service_id = w.service_id AND w2.status = 'completed') AS service_avg,
       w.total_cost - (
         SELECT AVG(w3.total_cost)
         FROM working_details w3
         WHERE w3.service_id = w.service_id AND w3.status = 'completed'
       ) AS delta
FROM working_details w
WHERE w.status = 'completed'
ORDER BY w.work_id DESC
LIMIT 50;
