<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied. Admins only.");
}

// Stats
$pending_count = $conn->query("SELECT COUNT(*) as c FROM assets WHERE status='pending'")->fetch_assoc()['c'];
$users_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$assets_count = $conn->query("SELECT COUNT(*) as c FROM assets WHERE status='approved'")->fetch_assoc()['c'];
$total_sales = $conn->query("SELECT SUM(amount) as s FROM transactions WHERE type='purchase'")->fetch_assoc()['s'] ?? 0;

// Recent Assets
$recent_assets = $conn->query("SELECT a.*, u.username as creator_name FROM assets a JOIN users u ON a.creator_id = u.id ORDER BY a.created_at DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RenderShelf</title>
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
                <h1>Overview</h1>
                <p>Welcome back, Admin. Here's what's happening today.</p>
            </div>
            <div style="display: flex; gap: 15px;">
                <a href="welcome.php" class="btn-primary" style="width: auto; padding: 10px 20px; display: flex; align-items: center; gap: 8px; text-decoration: none;">
                    <ion-icon name="eye-outline"></ion-icon>
                    View Site
                </a>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(138, 43, 226, 0.1); color: var(--accent-color);">
                    <ion-icon name="wallet-outline"></ion-icon>
                </div>
                <div class="stat-info">
                    <div class="stat-value">$<?php echo number_format($total_sales, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                    <ion-icon name="time-outline"></ion-icon>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending Reviews</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(56, 175, 255, 0.1); color: #38b0ff;">
                    <ion-icon name="people-outline"></ion-icon>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $users_count; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(56, 239, 125, 0.1); color: #38ef7d;">
                    <ion-icon name="cube-outline"></ion-icon>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $assets_count; ?></div>
                    <div class="stat-label">Live Assets</div>
                </div>
            </div>
        </section>

        <div class="dashboard-grid">
            <section class="content-box">
                <div class="box-header">
                    <h3>Recent Submissions</h3>
                    <a href="admin_approvals.php" style="color: var(--accent-color); text-decoration: none; font-size: 13px; font-weight: 600;">View All</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Creator</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_assets->num_rows > 0): ?>
                            <?php while($asset = $recent_assets->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 36px; height: 36px; border-radius: 8px; background: #222; overflow: hidden;">
                                                <img src="<?php echo htmlspecialchars($asset['thumbnail_path'] ?: ($asset['preview_path'] ?: 'img/auth_header_geo.png')); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                            <span><?php echo htmlspecialchars($asset['title']); ?></span>
                                        </div>
                                    </td>
                                    <td style="color: #aaa;"><?php echo htmlspecialchars($asset['creator_name']); ?></td>
                                    <td style="font-weight: 600;">$<?php echo number_format($asset['price'], 2); ?></td>
                                    <td><span class="status-pill status-<?php echo $asset['status']; ?>"><?php echo $asset['status']; ?></span></td>
                                    <td style="color: #666; font-size: 12px;"><?php echo date('M d', strtotime($asset['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #555; padding: 40px;">No assets found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="content-box">
                <div class="box-header">
                    <h3>Quick Actions</h3>
                </div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="admin_categories.php" class="btn-primary" style="background: #111; border: 1px solid #333; color: white; box-shadow: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 16px; margin: 0; text-decoration: none;">
                        <ion-icon name="add-circle-outline" style="font-size: 20px; color: var(--accent-color);"></ion-icon>
                        Add New Category
                    </a>
                    <a href="admin_users.php" class="btn-primary" style="background: #111; border: 1px solid #333; color: white; box-shadow: none; font-size: 14px; display: flex; align-items: center; gap: 10px; padding: 16px; margin: 0; text-decoration: none;">
                        <ion-icon name="person-add-outline" style="font-size: 20px; color: var(--accent-color);"></ion-icon>
                        Review Users
                    </a>
                    <div style="margin-top: 20px; background: rgba(138, 43, 226, 0.05); padding: 20px; border-radius: 16px; border: 1px dashed rgba(138, 43, 226, 0.2);">
                        <p style="font-size: 13px; color: #a0a0a0; line-height: 1.5;">Commission settings and global discounts can be managed in the settings panel coming soon.</p>
                    </div>
                </div>
            </section>
        </div>
        </div>
    </main>
</body>
</html>
