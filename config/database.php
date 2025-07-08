<?php
/**
 * Database Configuration
 * Support for both MySQL and SQLite databases
 */

// Get database type from environment variable or default to SQLite for development
$db_type = $_ENV['DB_TYPE'] ?? 'mysql';

if ($db_type === 'mysql') {
    // MySQL Configuration
    define('DB_TYPE', 'mysql');
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'silent_connect');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);
    define('DB_CHARSET', 'utf8mb4');
    
    // MySQL DSN
    define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
} else {
    // SQLite Configuration
    define('DB_TYPE', 'sqlite');
    define('DB_PATH', $_ENV['DB_PATH'] ?? __DIR__ . '/../database/silent_connect.db');
    
    // SQLite DSN
    define('DB_DSN', 'sqlite:' . DB_PATH);
    define('DB_USER', null);
    define('DB_PASS', null);
}

// PDO Options
if ($db_type === 'mysql') {
    // MySQL PDO Options
    define('DB_OPTIONS', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
} else {
    // SQLite PDO Options
    define('DB_OPTIONS', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}
?>
