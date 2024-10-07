<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pwlgenap2019-akademik');

// Function to get database connection
function getDbConnection()
{
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
function startSecureSession()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn()
{
    return isset($_SESSION['iduser']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ../auth/index.php');
        exit;
    }
}

function hashPassword($password)
{
    return md5($password);
}
