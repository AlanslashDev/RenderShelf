<?php
// config.php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'rendershelf';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($db_name);
} else {
    // Silent fail or die if critical. For setup, die is okay, but ensure no whitespace before this file.
    die("Error creating database: " . $conn->error);
}

// Auto-Migration Check for thumbnail_path
$chk = $conn->query("SHOW TABLES LIKE 'assets'");
if ($chk && $chk->num_rows > 0) {
    $col_chk = $conn->query("SHOW COLUMNS FROM assets LIKE 'thumbnail_path'");
    if ($col_chk && $col_chk->num_rows == 0) {
        $conn->query("ALTER TABLE assets ADD COLUMN thumbnail_path VARCHAR(255) AFTER preview_path");
    }
}

// Auto-Migration Check for role in users
$u_chk = $conn->query("SHOW TABLES LIKE 'users'");
if ($u_chk && $u_chk->num_rows > 0) {
    $r_chk = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($r_chk && $r_chk->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER password_hash");
    }
}

// Auto-Migration Check for notifications table
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'alert', 'success', 'warning') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

