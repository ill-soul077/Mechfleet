-- sql/queries/queries_explain.sql
-- Concepts: EXPLAIN plans, using indexes, checking filter and join strategies
-- This demo pairs with public/sql_explain_demo.php and shows how adding an index can change the plan.

-- BEFORE: Substring search on product_details.product_name forces full scan (index cannot be used for '%q%')
EXPLAIN SELECT product_id, product_name FROM product_details WHERE product_name LIKE '%filter%';

-- ADD INDEX: This BTREE index can be used for prefix searches (e.g., 'fil%'), reducing examined rows.
-- Note: It will NOT be used for leading-wildcard patterns ('%fil%').
CREATE INDEX idx_product_name ON product_details(product_name);

-- AFTER: With a prefix predicate, MySQL can leverage the index (type=range/ref) instead of type=ALL.
EXPLAIN SELECT product_id, product_name FROM product_details WHERE product_name LIKE 'fil%';
