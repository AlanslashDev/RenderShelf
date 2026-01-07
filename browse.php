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
    <div class="dashboard-container">
        
        <!-- Header -->
        <header class="dash-header">
            <div class="logo-container">
                <div class="logo-icon" style="width:30px; height:30px; font-size: 18px;">
                    <ion-icon name="layers"></ion-icon>
                </div>
                <h2 style="color:white; margin:0; letter-spacing: -0.5px;">RenderShelf</h2>
            </div>
            <div class="header-right">
                <a href="wallet.php" class="wallet-pill">
                    <ion-icon name="wallet-outline"></ion-icon>
                    $<?php echo number_format($_SESSION['wallet_balance'] ?? 0, 2); ?>
                </a>
                <div class="header-icons">
                    <a href="notifications.php"><ion-icon name="notifications-outline"></ion-icon></a>
                    <a href="cart.php" style="color:inherit;"><ion-icon name="cart-outline"></ion-icon></a>
                    <div class="avatar" style="background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer;" onclick="location.href='profile.php'">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Search Bar -->
        <form action="" method="GET" class="search-bar-container">
            <ion-icon name="search-outline" class="search-icon"></ion-icon>
            <input type="text" name="search" placeholder="Search for transitions, LUTs..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="search-submit-btn"><ion-icon name="arrow-forward-outline"></ion-icon></button>
        </form>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?category=all" class="tab-pill <?php echo $category_filter == 'all' ? 'active' : ''; ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo urlencode($cat['name']); ?>" class="tab-pill <?php echo $category_filter == $cat['name'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Asset Grid -->
        <div class="section-header">
            <div class="section-title">
                <?php echo $category_filter === 'all' ? 'All Assets' : htmlspecialchars($category_filter); ?>
            </div>
        </div>

        <?php if (count($assets) > 0): ?>
            <div class="asset-grid">
                <?php foreach ($assets as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="asset-card" style="text-decoration:none; color:inherit;">
                        <div class="asset-thumb">
                            <?php if (isset($asset['thumbnail_path']) && $asset['thumbnail_path']): ?>
                                <img src="<?php echo htmlspecialchars($asset['thumbnail_path']); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php elseif ($asset['preview_path']): ?>
                                <!-- Check if video or image (simple check) -->
                                <?php $ext = pathinfo($asset['preview_path'], PATHINFO_EXTENSION); ?>
                                <?php if (in_array(strtolower($ext), ['mp4', 'webm'])): ?>
                                    <video src="<?php echo htmlspecialchars($asset['preview_path']); ?>" muted loop onmouseover="this.play()" onmouseout="this.pause()" style="width:100%; height:100%; object-fit:cover;"></video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($asset['preview_path']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(to bottom, #333, #111);"></div>
                            <?php endif; ?>
                            
                            <span class="price-tag"><?php echo $asset['price'] > 0 ? '$' . $asset['price'] : 'FREE'; ?></span>
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
