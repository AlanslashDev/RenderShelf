<?php
require_once 'config.php';

// If not logged in, try to auto-login the first user or ask to login
if (!isset($_SESSION['user_id'])) {
    // Try to find a user to auto-login (for dev convenience)
    $res = $conn->query("SELECT * FROM users ORDER BY id ASC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
    } else {
        die("No users found in database. Please register first.");
    }
}

// Force Update Role to Admin
$user_id = $_SESSION['user_id'];
$update = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
$update->bind_param("i", $user_id);

if ($update->execute()) {
    // Update Session immediately
    $_SESSION['role'] = 'admin';

    echo "<h1>Success!</h1>";
    echo "<p>User ID #$user_id has been promoted to ADMIN.</p>";
    echo "<p>Session role updated.</p>";
    echo "<p>Redirecting to Dashboard in 3 seconds...</p>";
    echo "<meta http-equiv='refresh' content='3;url=admin_dashboard.php'>";
    echo "<br><a href='admin_dashboard.php'>Click here if not redirected</a>";
} else {
    echo "Error updating role: " . $conn->error;
}
?>