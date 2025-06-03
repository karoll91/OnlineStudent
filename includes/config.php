<?php
/**
 * Online Student Registration System
 * Database Configuration File
 */

// Database connection settings
$host = 'localhost';
$dbname = 'online_student_db';
$username = 'root';
$password = '12345'; // Your MySQL password

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
session_start();

// App settings
define('APP_NAME', 'Online Student Registration System');
define('BASE_URL', 'http://localhost/online-student-system');
define('UPLOAD_PATH', 'assets/uploads/');

// Timezone
date_default_timezone_set('Asia/Tashkent');

// Security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
?>