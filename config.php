<?php
// config.php
// Load .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database configuration
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'rendershelf';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Select database
if (!$conn->select_db($db_name)) {
    // If DB doesn't exist, try to create it once
    $conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
    $conn->select_db($db_name);
}

/* 
// AUTO-MIGRATIONS DISABLED FOR PERFORMANCE
// Run setup_db.php manually if tables are missing.
*/

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Global Platform Settings
if (!isset($_SESSION['platform_settings']) || isset($_GET['refresh_cache'])) {
    $set_res = $conn->query("SELECT setting_key, setting_value FROM settings");
    $platform_settings = [];
    if ($set_res) {
        while ($s_row = $set_res->fetch_assoc()) {
            $platform_settings[$s_row['setting_key']] = $s_row['setting_value'];
        }
        $_SESSION['platform_settings'] = $platform_settings;
    }
}

// FORCE SYNC: Ensure Session Role matches Database Role (Fixes "Access Denied" after promotion)
if (isset($_SESSION['user_id'])) {
    $uid_sync = (int) $_SESSION['user_id'];
    $role_query = $conn->query("SELECT role FROM users WHERE id = $uid_sync");
    if ($role_query && $role_query->num_rows > 0) {
        $synced_role = $role_query->fetch_assoc()['role'];
        if ($_SESSION['role'] !== $synced_role) {
            $_SESSION['role'] = $synced_role;
        }
    }
}

// Helper to get settings
function get_setting($key, $default = '')
{
    return $_SESSION['platform_settings'][$key] ?? $default;
}

