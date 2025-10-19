<?php
// includes/db.php
// Centralized PDO connection for MySQL 8.x using UTF8MB4.
// Reads configuration from environment variables with safe defaults for XAMPP/WAMP.
// Reuse this file everywhere via: require_once __DIR__ . '/db.php';

// Configuration: prefer environment variables, fallback to reasonable local defaults.
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'mechfleet';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS'); // On XAMPP/WAMP, root often has empty password
if ($DB_PASS === false) { $DB_PASS = ''; }

// Build DSN
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $DB_HOST, $DB_PORT, $DB_NAME);

// PDO options: exceptions, real prepared statements, persistent off by default.
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    // Uncomment to enable persistent connections if desired:
    // PDO::ATTR_PERSISTENT => true,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    // Ensure strict SQL mode and UTC timezone for consistency
    $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';");
    $pdo->exec("SET time_zone = '+00:00';");
} catch (PDOException $e) {
    // Friendly fatal error page for local dev; avoid leaking credentials
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Database connection failed. Check includes/db.php configuration and that MySQL is running.\n";
    echo "Error: " . $e->getMessage();
    exit;
}
