<?php
// config.php - Database Configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'teacher';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($data)));
}
?>