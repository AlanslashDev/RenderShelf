<?php
// Ensure admin pic is set if this is the admin
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $current_id = $_SESSION['user_id'];
    $conn->query("UPDATE users SET profile_pic = 'img/admin_profile.jpg' WHERE id = $current_id AND (profile_pic IS NULL OR profile_pic = '' OR profile_pic = 'default_avatar.png')");
    
    // Refresh session data if needed
    if (empty($_SESSION['profile_pic']) || $_SESSION['profile_pic'] === 'default_avatar.png') {
        $u_data = $conn->query("SELECT profile_pic FROM users WHERE id = $current_id")->fetch_assoc();
        $_SESSION['profile_pic'] = $u_data['profile_pic'];
    }
}
?>
<aside class="sidebar">
    <a href="welcome.php" class="sidebar-logo">
        <div class="logo-icon">
            <ion-icon name="layers"></ion-icon>
        </div>
        <span style="font-weight: 800; font-size: 20px; letter-spacing: -0.5px;">RenderShelf</span>
    </a>

    <nav class="sidebar-nav">
        <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
            <ion-icon name="grid-outline"></ion-icon>
            <span>Dashboard</span>
        </a>
        <a href="admin_approvals.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_approvals.php' ? 'active' : ''; ?>">
            <ion-icon name="checkmark-circle-outline"></ion-icon>
            <span>Approvals</span>
            <?php 
            $pending_count_sidebar = $conn->query("SELECT COUNT(*) as c FROM assets WHERE status='pending'")->fetch_assoc()['c'];
            if($pending_count_sidebar > 0): 
            ?>
                <span style="margin-left: auto; background: var(--accent-color); color: white; padding: 2px 6px; border-radius: 6px; font-size: 10px;"><?php echo $pending_count_sidebar; ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : ''; ?>">
            <ion-icon name="people-outline"></ion-icon>
            <span>Users</span>
        </a>
        <a href="admin_assets.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_assets.php' ? 'active' : ''; ?>">
            <ion-icon name="cube-outline"></ion-icon>
            <span>Assets</span>
        </a>
        <a href="admin_categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_categories.php' ? 'active' : ''; ?>">
            <ion-icon name="folder-outline"></ion-icon>
            <span>Categories</span>
        </a>
        <a href="#" class="nav-link">
            <ion-icon name="card-outline"></ion-icon>
            <span>Transactions</span>
        </a>
        <a href="#" class="nav-link">
            <ion-icon name="settings-outline"></ion-icon>
            <span>Settings</span>
        </a>
    </nav>

    <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; flex-direction: column; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 12px; padding: 0 10px;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--accent-color); overflow: hidden; border: 2px solid rgba(138, 43, 226, 0.3);">
                <?php if (isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="overflow: hidden;">
                <div style="font-size: 13px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                <div style="font-size: 11px; color: #555;">Super Admin</div>
            </div>
        </div>
        <a href="logout.php" class="nav-link" style="color: #ff4444;">
            <ion-icon name="log-out-outline"></ion-icon>
            <span>Logout</span>
        </a>
    </div>
</aside>
