<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Profile image logic
$profile_img = (!empty($_SESSION['profile_pic']) && $_SESSION['profile_pic'] !== 'default_avatar.png' && $_SESSION['profile_pic'] !== 'img/admin_profile.jpg')
    ? $_SESSION['profile_pic']
    : 'https://media0.giphy.com/media/v1.Y2lkPTc5MGI3NjExYzMydmdkeXVqamc0N2RuanJmcHAwczF3eWt5b2g3b3V4cDd1OXNqcSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/jNJW9Bj6vVXIERUgK3/giphy.gif';

// Get purchases
$sql = "SELECT a.*, u.username as creator_name 
        FROM transactions t 
        JOIN assets a ON t.related_asset_id = a.id 
        JOIN users u ON a.creator_id = u.id
        WHERE t.user_id = ? AND t.type = 'purchase' 
        ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get Favourites
$fav_sql = "SELECT a.*, u.username as creator_name 
            FROM favourites f 
            JOIN assets a ON f.asset_id = a.id 
            JOIN users u ON a.creator_id = u.id
            WHERE f.user_id = ? 
            ORDER BY f.created_at DESC";

$fav_stmt = $conn->prepare($fav_sql);
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$favourites = $fav_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'purchased';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Library - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="studio-container">

        <!-- Header -->
        <header class="dash-header" style="border:none; margin-bottom: 20px;">
            <div class="logo-container">
                <a href="welcome.php" style="text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <div class="logo-icon" style="width:30px; height:30px; font-size: 18px;">
                        <ion-icon name="layers"></ion-icon>
                    </div>
                    <h2 style="color:white; margin:0; letter-spacing: -0.5px; font-size: 20px;">RenderShelf</h2>
                </a>
            </div>
            <div class="header-right">
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                    <a href="wallet.php" class="wallet-pill" style="margin:0;">
                        <ion-icon name="wallet-outline"></ion-icon>
                        ₹<?php echo number_format($_SESSION['wallet_balance'] ?? 0, 2); ?>
                    </a>
                <?php endif; ?>
                <div class="header-icons">
                    <a href="notifications.php"><ion-icon name="notifications-outline"></ion-icon></a>
                    <a href="cart.php" style="color:inherit;"><ion-icon name="cart-outline"></ion-icon></a>
                    <a href="profile.php" class="avatar"
                        style="width: 36px; height: 36px; background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer; overflow:hidden;">
                        <img src="<?php echo htmlspecialchars($profile_img); ?>"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    </a>
                </div>
            </div>
        </header>

        <a href="welcome.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Dashboard
        </a>

        <div class="section-header">
            <h3 class="section-title" style="font-size: 24px;">My Library</h3>
            <span style="font-size: 14px; color: #555; font-weight: 600;"><?php echo count($purchases); ?> assets
                owned</span>
        </div>

        <div class="filter-tabs" style="margin-top:20px; margin-bottom: 40px; justify-content: flex-start; gap: 10px;">
            <a href="?tab=purchased" class="tab-pill <?php echo $active_tab === 'purchased' ? 'active' : ''; ?>" style="text-decoration: none;">Purchased</a>
            <a href="?tab=favourites" class="tab-pill <?php echo $active_tab === 'favourites' ? 'active' : ''; ?>" style="text-decoration: none;">Favourites <span style="font-size: 11px; background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 10px; margin-left: 5px;"><?php echo count($favourites); ?></span></a>
        </div>

        <?php 
        $display_items = $active_tab === 'purchased' ? $purchases : $favourites;
        ?>

        <?php if (count($display_items) > 0): ?>
            <div class="asset-grid">
                <?php foreach ($display_items as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="asset-card"
                        style="text-decoration:none; color:inherit;">
                        <div class="asset-thumb" style="height: 160px; position: relative;">
                            <?php
                            $preview_path = $asset['preview_path'];
                            $thumb_path = $asset['thumbnail_path'] ?? '';
                            $ext = pathinfo($preview_path, PATHINFO_EXTENSION);
                            $is_video = in_array(strtolower($ext), ['mp4', 'webm', 'mov', 'avi']);
                            ?>
                            <?php if ($is_video): ?>
                                <video src="<?php echo htmlspecialchars($preview_path); ?>"
                                    poster="<?php echo htmlspecialchars($thumb_path ?: ''); ?>" muted loop playsinline
                                    onmouseover="this.play()" onmouseout="this.pause()"
                                    style="width:100%; height:100%; object-fit:cover;"></video>
                            <?php elseif ($thumb_path || $preview_path): ?>
                                <img src="<?php echo htmlspecialchars($thumb_path ?: $preview_path); ?>"
                                    style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(to bottom, #333, #111);"></div>
                            <?php endif; ?>
                            
                            <?php if ($active_tab === 'purchased'): ?>
                                <span class="price-tag" style="background:var(--accent-color); color:white; border:none; padding: 4px 10px; font-size: 10px; font-weight: 800;">OWNED</span>
                            <?php else: ?>
                                <span class="price-tag"><?php echo $asset['price'] > 0 ? '₹' . number_format($asset['price'], 2) : 'Free'; ?></span>
                            <?php endif; ?>

                            <!-- Quick Download/Action Button -->
                            <div class="card-overlay"
                                style="position: absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.3s;"
                                onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                                <?php if ($active_tab === 'purchased' || $asset['price'] == 0): ?>
                                    <a href="download.php?id=<?php echo $asset['id']; ?>" class="action-btn"
                                        style="width:auto; padding: 10px 20px; background:#38ef7d; color:#000; border-radius:10px; font-size:12px; text-decoration:none; display:flex; align-items:center; gap:5px; font-weight:bold;">
                                        <ion-icon name="cloud-download-outline"></ion-icon> Download
                                    </a>
                                <?php else: ?>
                                    <div class="action-btn"
                                        style="width:auto; padding: 10px 20px; background:var(--accent-color); color:white; border-radius:10px; font-size:12px; display:flex; align-items:center; gap:5px; font-weight:bold;">
                                        <ion-icon name="eye-outline"></ion-icon> View Details
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="asset-info">
                            <div class="asset-title"><?php echo htmlspecialchars($asset['title']); ?></div>
                            <span class="asset-creator">By <?php echo htmlspecialchars($asset['creator_name']); ?></span>
                            <div class="asset-rating">
                                <ion-icon name="star" style="color:#f8b500;"></ion-icon> 5.0
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div
                style="text-align: center; padding: 100px 20px; background: rgba(255,255,255,0.01); border-radius: 30px; border: 1px dashed rgba(255,255,255,0.05); min-height: 400px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div
                    style="width: 100px; height: 100px; background: rgba(255,255,255,0.03); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 25px;">
                    <ion-icon name="<?php echo $active_tab === 'purchased' ? 'play-circle-outline' : 'bookmark-outline'; ?>" style="font-size: 50px; color: #333;"></ion-icon>
                </div>
                <h3 style="color: white; margin-bottom: 10px; font-size: 24px;"><?php echo $active_tab === 'purchased' ? 'Library is Empty' : 'No Favourites Yet'; ?></h3>
                <p style="color: #666; margin-bottom: 30px; max-width: 300px; line-height: 1.6;">Your <?php echo $active_tab === 'purchased' ? 'purchased assets' : 'favorite items'; ?> will appear here for easy access.</p>
                <a href="browse.php" class="btn-primary"
                    style="width: auto; display: inline-flex; padding: 14px 40px; border-radius: 15px;">Explore Store</a>
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
        <a href="manage_uploads.php" class="nav-item">
            <ion-icon name="cloud-upload-outline"></ion-icon>
            Uploads
        </a>
        <a href="library.php" class="nav-item active">
            <ion-icon name="play-circle"></ion-icon>
            Library
        </a>
        <a href="profile.php" class="nav-item">
            <ion-icon name="person-outline"></ion-icon>
            Profile
        </a>
    </nav>
</body>

</html>