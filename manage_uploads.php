<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch User's Own Uploads
$my_uploads_result = $conn->query("SELECT a.*, c.name as category_name FROM assets a LEFT JOIN categories c ON a.category_id = c.id WHERE a.creator_id = $user_id ORDER BY a.created_at DESC");
$my_uploads = $my_uploads_result->fetch_all(MYSQLI_ASSOC);

// Fetch Generated Income
$income_query = $conn->prepare("SELECT SUM(amount) as total_income FROM transactions WHERE type='purchase' AND related_asset_id IN (SELECT id FROM assets WHERE creator_id = ?)");
$income_query->bind_param("i", $user_id);
$income_query->execute();
$income_res = $income_query->get_result()->fetch_assoc();
$total_income = $income_res['total_income'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Uploads - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="dashboard-container">
        
        <!-- Header -->
        <header class="dash-header">
            <div class="logo-container">
                <a href="welcome.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px;">
                    <ion-icon name="arrow-back-outline" style="font-size:24px;"></ion-icon>
                    <h2 style="margin:0; letter-spacing: -0.5px;">Creator Studio</h2>
                </a>
            </div>
            <div class="header-right">
                <div class="header-icons">
                    <a href="notifications.php"><ion-icon name="notifications-outline"></ion-icon></a>
                    <div class="avatar" style="background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer;" onclick="location.href='profile.php'">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Earnings Summary Card -->
        <div class="featured-card" style="height:auto; padding: 25px; margin-top:20px; background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div>
                    <span class="bundle-badge" style="background: #38ef7d; color: black;">Revenue</span>
                    <h1 style="font-size: 32px; margin-top: 10px;">$<?php echo number_format($total_income, 2); ?></h1>
                    <p style="color: #aaa; font-size: 13px;">Total generated from asset sales</p>
                </div>
                <a href="upload.php" class="btn-primary" style="width: auto; padding: 12px 20px; display: flex; align-items: center; gap: 8px; text-decoration: none; margin: 0;">
                    <ion-icon name="add-circle-outline" style="font-size: 20px;"></ion-icon>
                    New Upload
                </a>
            </div>
        </div>

        <div class="section-header" style="margin-top: 30px;">
            <h3 class="section-title">Manage Uploads</h3>
            <span style="font-size: 13px; color: #666;"><?php echo count($my_uploads); ?> items</span>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="success-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message" style="margin-bottom: 20px;"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if(count($my_uploads) > 0): ?>
            <div class="asset-grid">
                <?php foreach($my_uploads as $asset): ?>
                    <div class="asset-card" style="padding: 0; position: relative;">
                        <div class="trending-thumb" style="height: 140px; border-radius: 16px;">
                            <img src="<?php echo $asset['thumbnail_path'] ?: ($asset['preview_path'] ?: 'img/auth_header_geo.png'); ?>" alt="">
                            <div style="position: absolute; top: 10px; right: 10px; padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; background: <?php echo $asset['status'] == 'approved' ? '#38ef7d' : ($asset['status'] == 'pending' ? '#ffc107' : '#ff4444'); ?>; color: black;">
                                <?php echo $asset['status']; ?>
                            </div>
                        </div>
                        <div class="asset-content-wrap" style="padding: 12px;">
                            <div class="asset-title" style="font-size: 14px;"><?php echo htmlspecialchars($asset['title']); ?></div>
                            <div class="asset-meta" style="margin-top: 5px;">
                                <span style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($asset['category_name']); ?></span>
                                <span class="asset-price-new" style="margin-left: auto;">$<?php echo number_format($asset['price'], 2); ?></span>
                            </div>
                            <div style="margin-top: 12px; display: flex; gap: 8px;">
                                <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="btn-secondary" style="padding: 6px; font-size: 11px; flex: 1; text-decoration: none; text-align: center; border-radius: 8px;">View</a>
                                <a href="#" class="btn-secondary" style="padding: 6px; font-size: 11px; flex: 1; text-decoration: none; text-align: center; border-radius: 8px;">Edit</a>
                                <a href="delete_asset_user.php?id=<?php echo $asset['id']; ?>" onclick="return confirm('Are you sure you want to delete this asset?')" class="btn-secondary" style="padding: 6px; font-size: 11px; flex: 1; text-decoration: none; text-align: center; border-radius: 8px; background: rgba(255, 68, 68, 0.1); color: #ff4444; border-color: rgba(255, 68, 68, 0.2);">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; color: #444;">
                <ion-icon name="cloud-upload-outline" style="font-size: 64px; margin-bottom: 20px;"></ion-icon>
                <p>You haven't uploaded any assets yet.</p>
                <a href="upload.php" class="btn-primary" style="width: auto; display: inline-block; margin-top: 20px; padding: 12px 30px;">Start Creating</a>
            </div>
        <?php endif; ?>

    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="welcome.php" class="nav-item">
            <ion-icon name="home-outline"></ion-icon>
            Home
        </a>
        <a href="browse.php" class="nav-item">
            <ion-icon name="search-outline"></ion-icon>
            Search
        </a>
        <a href="manage_uploads.php" class="nav-item active">
            <ion-icon name="cloud-upload"></ion-icon>
            Uploads
        </a>
        <a href="library.php" class="nav-item">
            <ion-icon name="play-circle-outline"></ion-icon>
            Library
        </a>
        <a href="profile.php" class="nav-item">
            <ion-icon name="person-outline"></ion-icon>
            Profile
        </a>
    </nav>
</body>
</html>
