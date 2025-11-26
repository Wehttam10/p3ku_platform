<?php
/**
 * DB Configuration File
 * Establishes the connection to the MySQL database using PDO.
 */

// --- 1. Database Configuration Constants ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'p3ku_platform');
define('DB_USER', 'root');
define('DB_PASS', ''); 

// --- 2. Connection Function ---
function get_db_connection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage(), 0);
        die("<h1>Application Error</h1><p>We are currently experiencing technical difficulties. Please try again later.</p>");
    }
}