<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Filter Logic
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build Query
$sql = "SELECT a.*, u.username as creator_name, c.name as category_name 
        FROM assets a 
        JOIN users u ON a.creator_id = u.id 
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'approved'";

$params = [];
$types = "";

if ($category_filter !== 'all') {
    $sql .= " AND c.name = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
    $like_query = "%" . $search_query . "%";
    $params[] = $like_query;
    $params[] = $like_query;
    $types .= "ss";
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$assets = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get Categories for Sidebar/Filter
$cat_result = $conn->query("SELECT * FROM categories WHERE type='asset'");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);

// Profile logic
$profile_img = (!empty($_SESSION['profile_pic']) && file_exists($_SESSION['profile_pic'])) 
                ? $_SESSION['profile_pic'] 
                : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Assets - RenderShelf</title>
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
                    <a href="profile.php" class="avatar" style="width: 36px; height: 36px; background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer; overflow:hidden;">
                        <?php if ($profile_img): ?>
                            <img src="<?php echo htmlspecialchars($profile_img); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <ion-icon name="person"></ion-icon>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </header>

        <!-- Search Bar -->
        <form action="" method="GET" class="search-bar-container" style="margin-bottom: 30px;">
            <ion-icon name="search-outline" class="search-icon"></ion-icon>
            <input type="text" name="search" placeholder="Search for transitions, LUTs, assets..." value="<?php echo htmlspecialchars($search_query); ?>" style="border-radius: 16px;">
            <button type="submit" class="search-submit-btn"><ion-icon name="arrow-forward-outline"></ion-icon></button>
        </form>

        <!-- Filter Tabs -->
        <div class="filter-tabs" style="margin-top: 20px; margin-bottom: 40px; justify-content: flex-start; gap: 10px;">
            <a href="?category=all" class="tab-pill <?php echo $category_filter == 'all' ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat['name']); ?>" class="tab-pill <?php echo $category_filter == $cat['name'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="section-header">
            <h3 class="section-title">
                <?php echo $category_filter === 'all' ? 'All Assets' : htmlspecialchars($category_filter); ?>
            </h3>
            <span style="font-size: 14px; color: #555; font-weight: 600;"><?php echo count($assets); ?> results found</span>
        </div>

        <?php if (count($assets) > 0): ?>
            <div class="asset-grid">
                <?php foreach ($assets as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="asset-card" style="text-decoration:none; color:inherit;">
                        <div class="asset-thumb">
                            <?php 
                                $preview_path = $asset['preview_path'];
                                $thumb_path = $asset['thumbnail_path'] ?? '';
                                $ext = pathinfo($preview_path, PATHINFO_EXTENSION);
                                $is_video = in_array(strtolower($ext), ['mp4', 'webm', 'mov', 'avi']);
                                $is_audio = in_array(strtolower($ext), ['mp3', 'wav', 'ogg', 'm4a']);
                            ?>
                            <?php if ($is_video): ?>
                                <video src="<?php echo htmlspecialchars($preview_path); ?>" 
                                       poster="<?php echo htmlspecialchars($thumb_path ?: ''); ?>" 
                                       muted loop playsinline
                                       onmouseover="this.play()" 
                                       onmouseout="this.pause()" 
                                       style="width:100%; height:100%; object-fit:cover;"></video>
                            <?php elseif ($is_audio): ?>
                                <div style="width:100%; height:100%; background: linear-gradient(45deg, #1a1a1d, #222); display:flex; align-items:center; justify-content:center; position:relative;">
                                    <?php if($thumb_path): ?>
                                        <img src="<?php echo htmlspecialchars($thumb_path); ?>" style="width:100%; height:100%; object-fit:cover; opacity: 0.4;">
                                    <?php endif; ?>
                                    <div style="position:absolute; font-size: 32px; color: var(--accent-color); filter: drop-shadow(0 0 10px rgba(138,43,226,0.5));">
                                        <ion-icon name="musical-notes"></ion-icon>
                                    </div>
                                </div>
                            <?php elseif ($thumb_path || $preview_path): ?>
                                <img src="<?php echo htmlspecialchars($thumb_path ?: $preview_path); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(to bottom, #333, #111);"></div>
                            <?php endif; ?>
                            
                            <span class="price-tag"><?php echo $asset['price'] > 0 ? '₹' . number_format($asset['price'], 2) : 'Free'; ?></span>
                        </div>
                        <div class="asset-info">
                            <div class="asset-title"><?php echo htmlspecialchars($asset['title']); ?></div>
                            <span class="asset-creator">By <?php echo htmlspecialchars($asset['creator_name']); ?></span>
                            <div class="asset-rating">
                                <ion-icon name="star" style="color:#f8b500;"></ion-icon> 
                                <?php 
                                    // Pseudo random rating for now or fetch from DB
                                    echo number_format((rand(40, 50) / 10), 1); 
                                ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding:50px; color:#aaa;">
                <ion-icon name="cube-outline" style="font-size:48px; margin-bottom:10px;"></ion-icon>
                <p>No assets found in this category.</p>
                <a href="upload.php" class="btn-primary" style="display:inline-block; width:auto; margin-top:10px;">Upload an Asset</a>
            </div>
        <?php endif; ?>

    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="welcome.php" class="nav-item">
            <ion-icon name="home-outline"></ion-icon>
            Home
        </a>
        <a href="browse.php" class="nav-item active">
            <ion-icon name="search"></ion-icon>
            Search
        </a>
        <a href="manage_uploads.php" class="nav-item">
            <ion-icon name="cloud-upload-outline"></ion-icon>
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
