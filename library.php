<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
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

// Also include own uploads? "Library" usually implies acquisition, but "My Uploads" is for creator.
// We'll stick to purchases for now.
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
    <div class="dashboard-container">
        
        <!-- Header -->
        <header class="dash-header">
            <div class="logo-container">
                <a href="welcome.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px;">
                    <ion-icon name="arrow-back-outline" style="font-size:24px;"></ion-icon>
                    <h2 style="margin:0; letter-spacing: -0.5px;">My Library</h2>
                </a>
            </div>
            <div class="header-right">
                <div class="header-icons">
                    <a href="cart.php"><ion-icon name="cart-outline"></ion-icon></a>
                    <div class="avatar" style="background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer;" onclick="location.href='profile.php'">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="filter-tabs" style="margin-top:20px;">
            <div class="tab-pill active">Purchased</div>
            <div class="tab-pill" style="opacity:0.5; cursor:not-allowed;">Favourites (Coming Soon)</div>
        </div>

        <?php if (count($purchases) > 0): ?>
            <div class="asset-grid">
                <?php foreach ($purchases as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="asset-card" style="text-decoration:none; color:inherit;">
                        <div class="asset-thumb">
                            <?php if (isset($asset['thumbnail_path']) && $asset['thumbnail_path']): ?>
                                <img src="<?php echo htmlspecialchars($asset['thumbnail_path']); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php elseif ($asset['preview_path']): ?>
                                <?php $ext = pathinfo($asset['preview_path'], PATHINFO_EXTENSION); ?>
                                <?php if (in_array(strtolower($ext), ['mp4', 'webm'])): ?>
                                    <video src="<?php echo htmlspecialchars($asset['preview_path']); ?>" style="width:100%; height:100%; object-fit:cover;"></video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($asset['preview_path']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(to bottom, #333, #111);"></div>
                            <?php endif; ?>
                            <span class="price-tag" style="background:#38ef7d; color:black;">OWNED</span>
                        </div>
                        <div class="asset-info">
                            <div class="asset-title"><?php echo htmlspecialchars($asset['title']); ?></div>
                            <span class="asset-creator">By <?php echo htmlspecialchars($asset['creator_name']); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:50px; color:#aaa;">
                <ion-icon name="library-outline" style="font-size:48px; margin-bottom:10px;"></ion-icon>
                <p>You haven't purchased any assets yet.</p>
                <a href="browse.php" class="btn-primary" style="display:inline-block; width:auto; margin-top:10px;">Browse Store</a>
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
