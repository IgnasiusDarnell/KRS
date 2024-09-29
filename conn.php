<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'krs');

// Function to get database connection
function getDbConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}