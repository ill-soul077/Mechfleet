<?php
// includes/config.php
// Database configuration for Mechfleet.
// Update these constants with your local/production values.

// Database connection settings
define('DB_HOST', '127.0.0.1');     // MySQL host (localhost or 127.0.0.1)
define('DB_PORT', '3306');           // MySQL port
define('DB_NAME', 'mechfleet');      // Database name
define('DB_USER', 'root');           // Database user (XAMPP default: root)
define('DB_PASS', '');               // Database password (XAMPP default: empty string)
define('DB_CHARSET', 'utf8mb4');     // Character set

// Development mode: set to false in production to hide detailed error messages
define('DEV_MODE', true);
