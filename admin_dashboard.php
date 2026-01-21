<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Stats
$pending_count = $conn->query("SELECT COUNT(*) as c FROM assets WHERE status='pending'")->fetch_assoc()['c'];
$users_count = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$assets_count = $conn->query("SELECT COUNT(*) as c FROM assets WHERE status='approved'")->fetch_assoc()['c'];
$total_sales = $conn->query("SELECT SUM(amount) as s FROM transactions WHERE type='purchase'")->fetch_assoc()['s'] ?? 0;
$total_payouts = $conn->query("SELECT SUM(amount) as s FROM transactions WHERE type='withdrawal'")->fetch_assoc()['s'] ?? 0;

// Monthly Growth (Sample logic for visual)
$last_month_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_assoc()['c'];
$user_growth = $users_count > 0 ? (($users_count - $last_month_users) / max(1, $last_month_users)) * 100 : 0;

// Recent Assets
$recent_assets = $conn->query("SELECT a.*, u.username as creator_name, c.name as category_name FROM assets a JOIN users u ON a.creator_id = u.id LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.created_at DESC LIMIT 6");

// Recent Transactions
$recent_tx = $conn->query("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
    <style>
        .admin-nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .nav-card {
            background: #16161a;
            padding: 24px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.03);
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .nav-card:hover {
            transform: translateY(-5px);
            background: #1c1c21;
            border-color: var(--accent-color);
        }
        .nav-card .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: rgba(138, 43, 226, 0.1);
            color: var(--accent-color);
        }
        .chart-placeholder {
            height: 200px;
            background: linear-gradient(180deg, rgba(138,43,226,0.05) 0%, transparent 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .chart-placeholder::after {
            content: '';
            position: absolute;
            bottom: 30%;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--accent-color);
            box-shadow: 0 0 20px var(--accent-color);
            opacity: 0.3;
        }
        .pulse {
            animation: pulse-animation 2s infinite;
        }
        @keyframes pulse-animation {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
            <header class="admin-header">
                <div class="header-title">
                    <h1>Administrative Overview</h1>
                    <p>Metrics, management, and platform health.</p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button class="btn-primary" style="width: auto; background: #1a1a1d; border: 1px solid #333; color: #888; font-size: 13px;" onclick="location.reload()">
                        <ion-icon name="refresh-outline"></ion-icon>
                    </button>
                    <a href="welcome.php" class="btn-primary" style="width: auto; padding: 10px 20px; display: flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px;">
                        <ion-icon name="eye-outline"></ion-icon>
                        Live Site
                    </a>
                </div>
            </header>

            <!-- Main Stats -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div class="stat-icon" style="background: rgba(56, 239, 125, 0.1); color: #38ef7d;">
                            <ion-icon name="wallet-outline"></ion-icon>
                        </div>
                        <span style="font-size: 11px; color: #38ef7d; font-weight: 700; background: rgba(56,239,125,0.05); padding: 4px 8px; border-radius: 6px;">+12%</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">₹<?php echo number_format($total_sales, 2); ?></div>
                        <div class="stat-label">Total Volume</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                            <ion-icon name="hourglass-outline"></ion-icon>
                        </div>
                        <?php if($pending_count > 0): ?>
                            <span class="pulse" style="font-size: 11px; color: #ffc107; font-weight: 700; background: rgba(255,193,7,0.05); padding: 4px 8px; border-radius: 6px;">Action Required</span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $pending_count; ?></div>
                        <div class="stat-label">Pending Reviews</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div class="stat-icon" style="background: rgba(56, 175, 255, 0.1); color: #38b0ff;">
                            <ion-icon name="people-outline"></ion-icon>
                        </div>
                        <span style="font-size: 11px; color: #38b0ff; font-weight: 700; background: rgba(56,175,255,0.05); padding: 4px 8px; border-radius: 6px;">+<?php echo round($user_growth); ?>%</span>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($users_count); ?></div>
                        <div class="stat-label">Total Members</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div class="stat-icon" style="background: rgba(138, 43, 226, 0.1); color: var(--accent-color);">
                            <ion-icon name="cube-outline"></ion-icon>
                        </div>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo number_format($assets_count); ?></div>
                        <div class="stat-label">Verified Assets</div>
                    </div>
                </div>
            </section>

            <!-- Platform Sections -->
            <div style="display: flex; flex-direction: column; gap: 40px;">
                
                <!-- Section 1: Content Moderation -->
                <section class="content-box">
                    <div class="box-header">
                        <div>
                            <h3 style="margin:0; display: flex; align-items: center; gap: 10px;">
                                <ion-icon name="shield-checkmark-outline" style="color: var(--accent-color);"></ion-icon>
                                Content Moderation
                            </h3>
                            <p style="font-size: 12px; color: #555; margin-top: 4px;">Latest submissions requiring your attention.</p>
                        </div>
                        <a href="admin_approvals.php" class="btn-primary" style="width: auto; padding: 8px 16px; font-size: 13px; text-decoration: none; background: #1a1a1d; border: 1px solid #333; color: white;">Manage Approvals</a>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Asset</th>
                                    <th>Creator</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recent_assets->data_seek(0);
                                if($recent_assets->num_rows > 0): 
                                ?>
                                    <?php while($asset = $recent_assets->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <div style="width: 44px; height: 44px; border-radius: 12px; background: #222; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); flex-shrink: 0;">
                                                        <img src="<?php echo htmlspecialchars($asset['thumbnail_path'] ?: ($asset['preview_path'] ?: 'img/auth_header_geo.png')); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                    </div>
                                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($asset['title']); ?></span>
                                                </div>
                                            </td>
                                            <td style="color: #aaa;"><?php echo htmlspecialchars($asset['creator_name']); ?></td>
                                            <td><span style="font-size: 11px; background: rgba(138,43,226,0.1); padding: 4px 10px; border-radius: 20px; color: var(--accent-color); border: 1px solid rgba(138,43,226,0.2); font-weight: 600;"><?php echo htmlspecialchars($asset['category_name'] ?: 'N/A'); ?></span></td>
                                            <td style="font-weight: 600;">₹<?php echo number_format($asset['price'], 2); ?></td>
                                            <td><span class="status-pill status-<?php echo $asset['status']; ?>"><?php echo $asset['status']; ?></span></td>
                                            <td style="text-align: right;">
                                                <a href="admin_approvals.php" style="color: #666; font-size: 20px;"><ion-icon name="ellipsis-horizontal-circle-outline"></ion-icon></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align: center; color: #555; padding: 60px;">No pending submissions found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Section 2: Management Suite -->
                <section>
                    <div style="margin-bottom: 24px;">
                        <h3 style="margin:0; display: flex; align-items: center; gap: 10px;">
                            <ion-icon name="apps-outline" style="color: #38b0ff;"></ion-icon>
                            Management Suite
                        </h3>
                        <p style="font-size: 12px; color: #555; margin-top: 4px;">Global platform controls and configuration.</p>
                    </div>
                    <div class="admin-nav-cards">
                        <a href="admin_users.php" class="nav-card">
                            <div class="icon-box" style="background: rgba(56, 175, 255, 0.1); color: #38b0ff;">
                                <ion-icon name="people-outline"></ion-icon>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size: 16px;">User Control</h4>
                                <p style="font-size: 12px; color: #555; margin-top: 4px;">Manage user roles, bans, and permissions.</p>
                            </div>
                        </a>
                        <a href="admin_assets.php" class="nav-card">
                            <div class="icon-box" style="background: rgba(56, 239, 125, 0.1); color: #38ef7d;">
                                <ion-icon name="cube-outline"></ion-icon>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size: 16px;">Asset Library</h4>
                                <p style="font-size: 12px; color: #555; margin-top: 4px;">View and edit all published content.</p>
                            </div>
                        </a>
                        <a href="admin_categories.php" class="nav-card">
                            <div class="icon-box" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <ion-icon name="folder-outline"></ion-icon>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size: 16px;">Categories</h4>
                                <p style="font-size: 12px; color: #555; margin-top: 4px;">Update marketplace taxonomies.</p>
                            </div>
                        </a>
                        <a href="admin_transactions.php" class="nav-card">
                            <div class="icon-box" style="background: rgba(138, 43, 226, 0.1); color: #8a2be2;">
                                <ion-icon name="receipt-outline"></ion-icon>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size: 16px;">Financials</h4>
                                <p style="font-size: 12px; color: #555; margin-top: 4px;">Audit logs and revenue analytics.</p>
                            </div>
                        </a>
                        <a href="admin_settings.php" class="nav-card">
                            <div class="icon-box" style="background: rgba(255, 255, 255, 0.05); color: #fff;">
                                <ion-icon name="settings-outline"></ion-icon>
                            </div>
                            <div>
                                <h4 style="margin:0; font-size: 16px;">Settings</h4>
                                <p style="font-size: 12px; color: #555; margin-top: 4px;">Access system-wide preferences.</p>
                            </div>
                        </a>
                    </div>
                </section>

                <!-- Section 3: Platform Pulse -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <section class="content-box">
                        <div class="box-header">
                            <div>
                                <h3 style="margin:0; display: flex; align-items: center; gap: 10px;">
                                    <ion-icon name="pulse-outline" style="color: #ff4444;"></ion-icon>
                                    Recent Activity
                                </h3>
                                <p style="font-size: 12px; color: #555; margin-top: 4px;">Live updates from the marketplace.</p>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 10px;">
                            <?php 
                            $recent_tx->data_seek(0);
                            while($tx = $recent_tx->fetch_assoc()): 
                            ?>
                                <div style="display: flex; align-items: center; gap: 16px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid rgba(255,255,255,0.03);">
                                    <div style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $tx['type'] == 'purchase' ? '#38ef7d' : '#8a2be2'; ?>; box-shadow: 0 0 10px <?php echo $tx['type'] == 'purchase' ? '#38ef7d' : '#8a2be2'; ?>44;"></div>
                                    <div style="flex: 1;">
                                        <div style="font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($tx['username']); ?></div>
                                        <div style="font-size: 11px; color: #666; margin-top: 2px;">
                                            Performed a <span style="color:#eee;"><?php echo $tx['type']; ?></span> of <span style="color:#eee;">₹<?php echo number_format($tx['amount'], 2); ?></span>
                                        </div>
                                    </div>
                                    <div style="font-size: 11px; color: #444;"><?php echo date('M d, H:i', strtotime($tx['created_at'])); ?></div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </section>

                    <section class="content-box">
                        <div class="box-header">
                            <div>
                                <h3 style="margin:0; display: flex; align-items: center; gap: 10px;">
                                    <ion-icon name="trending-up-outline" style="color: #38ef7d;"></ion-icon>
                                    Platform Growth
                                </h3>
                                <p style="font-size: 12px; color: #555; margin-top: 4px;">User and asset analytics overview.</p>
                            </div>
                        </div>
                        <div class="chart-placeholder" style="height: 250px;">
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #444; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px;">Network Activity Pulse</div>
                                <div style="display: flex; gap: 8px; align-items: flex-end; height: 80px;">
                                    <div style="width: 5px; height: 20px; background: #222; border-radius: 10px;"></div>
                                    <div style="width: 5px; height: 45px; background: #333; border-radius: 10px;"></div>
                                    <div style="width: 5px; height: 30px; background: #444; border-radius: 10px;"></div>
                                    <div style="width: 5px; height: 60px; background: var(--accent-color); border-radius: 10px;"></div>
                                    <div style="width: 5px; height: 80px; background: var(--accent-color); border-radius: 10px; box-shadow: 0 0 15px var(--accent-color);"></div>
                                    <div style="width: 5px; height: 50px; background: #38b0ff44; border-radius: 10px;"></div>
                                    <div style="width: 5px; height: 40px; background: #333; border-radius: 10px;"></div>
                                    <div style="width: 5px; height: 25px; background: #222; border-radius: 10px;"></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </main>
</body>
</html>
        </div>
    </main>
</body>
</html>
