<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$msg = '';
$error = '';

// Handle Role Change
if (isset($_GET['toggle_role'])) {
    $target_id = intval($_GET['toggle_role']);
    // Prevent admin from demoting themselves
    if ($target_id == $_SESSION['user_id']) {
        $error = "You cannot change your own role.";
    } else {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        $new_role = ($res['role'] === 'admin') ? 'user' : 'admin';
        
        $update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $update->bind_param("si", $new_role, $target_id);
        if ($update->execute()) {
            $msg = "User role updated to $new_role.";
        }
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $target_id = intval($_GET['delete']);
    if ($target_id == $_SESSION['user_id']) {
        $error = "You cannot delete yourself.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            $msg = "User deleted successfully.";
        }
    }
}

// Fetch all users
$users = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
        <header class="admin-header">
            <div class="header-title">
                <h1>User Management</h1>
                <p>Manage platform users and their administrative privileges.</p>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="success-message" style="margin-top:20px;"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message" style="margin-top:20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-box">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="padding-left: 0;">User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td style="padding-left: 0;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:32px; height:32px; background:var(--accent-color); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; color:white;">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <span style="font-weight:600;"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                        </td>
                        <td style="color:#aaa;"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="status-pill <?php echo $user['role'] === 'admin' ? 'status-approved' : 'status-pending'; ?>" style="font-size: 10px;">
                                <?php echo strtoupper($user['role']); ?>
                            </span>
                        </td>
                        <td style="color:#666; font-size:13px;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div style="display:flex; gap:10px; justify-content: flex-end;">
                                <a href="?toggle_role=<?php echo $user['id']; ?>" class="view-btn <?php echo $user['role'] === 'admin' ? 'active' : ''; ?>" title="Toggle Admin Role" style="width: 32px; height: 32px;">
                                    <ion-icon name="shield-outline"></ion-icon>
                                </a>
                                <a href="?delete=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')" title="Delete User" style="width: 32px; height: 32px; background: rgba(255, 68, 68, 0.1); color: #ff4444;" class="view-btn">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        </div>
    </main>
</body>
</html>
