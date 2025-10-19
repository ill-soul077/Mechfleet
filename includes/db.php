<?php
// includes/db.php
// Centralized PDO connection for MySQL 8.x using UTF8MB4.
// Reads configuration from includes/config.php.
// Reuse this file everywhere via: require_once __DIR__ . '/db.php';
// Exports: $pdo, runQuery(), runStatement()

require_once __DIR__ . '/config.php';

// Build DSN with charset
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_PORT,
    DB_NAME,
    DB_CHARSET
);

// PDO options: exceptions, associative fetch, real prepared statements
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // Ensure strict SQL mode and UTC timezone for consistency
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';");
    $pdo->exec("SET time_zone = '+00:00';");
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    if (DEV_MODE) {
        // Development: show full error message
        echo "Database connection failed.\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Check includes/config.php and ensure MySQL is running.";
    } else {
        // Production: generic error message
        echo "Database connection failed. Please contact support.";
    }
    exit;
}

/**
 * runQuery - Execute a SELECT query with optional parameters and return the PDOStatement.
 * Useful for SELECT queries where you want to fetch results.
 *
 * @param string $sql SQL query string (use named or positional placeholders)
 * @param array $params Parameters to bind (associative or indexed array)
 * @return PDOStatement Executed statement ready for fetch/fetchAll
 * @throws PDOException on error
 */
function runQuery(string $sql, array $params = []): PDOStatement {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * runStatement - Execute a non-SELECT query (INSERT/UPDATE/DELETE) and return affected rows.
 * Useful for DML operations where you need the row count.
 *
 * @param string $sql SQL query string (use named or positional placeholders)
 * @param array $params Parameters to bind (associative or indexed array)
 * @return int Number of affected rows
 * @throws PDOException on error
 */
function runStatement(string $sql, array $params = []): int {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
