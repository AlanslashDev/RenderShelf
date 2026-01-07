<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Fetch Categories
$cat_result = $conn->query("SELECT * FROM categories WHERE type='asset' LIMIT 6");
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);

// Fetch Trending
$trending_result = $conn->query("SELECT a.*, u.username as creator_name FROM assets a JOIN users u ON a.creator_id = u.id WHERE a.status = 'approved' AND a.creator_id != $user_id ORDER BY a.download_count DESC, a.created_at DESC LIMIT 5");
$trending_assets = $trending_result->fetch_all(MYSQLI_ASSOC);

// Fetch New Arrivals (From others)
$new_result = $conn->query("SELECT a.*, u.username as creator_name FROM assets a JOIN users u ON a.creator_id = u.id WHERE a.status = 'approved' AND a.creator_id != $user_id ORDER BY a.created_at DESC LIMIT 6");
$new_assets = $new_result->fetch_all(MYSQLI_ASSOC);

// Fetch User's Own Uploads
$my_uploads_result = $conn->query("SELECT * FROM assets WHERE creator_id = $user_id ORDER BY created_at DESC");
$my_uploads = $my_uploads_result->fetch_all(MYSQLI_ASSOC);

// Fetch Generated Income (Total from sales)
$income_query = $conn->prepare("SELECT SUM(amount) as total_income FROM transactions WHERE type='purchase' AND related_asset_id IN (SELECT id FROM assets WHERE creator_id = ?)");
$income_query->bind_param("i", $user_id);
$income_query->execute();
$income_res = $income_query->get_result()->fetch_assoc();
$total_income = $income_res['total_income'] ?? 0;

// Fetch Unread Notifications count
$unread_query = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_query->bind_param("i", $user_id);
$unread_query->execute();
$unread_count = $unread_query->get_result()->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RenderShelf - Home</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .dash-header h2 { font-size: 20px; font-weight: 800; }
        .header-icons { display: flex; gap: 15px; color: white; font-size: 24px; }
        .search-bar-container input::placeholder { color: #555; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        
        <!-- Header -->
        <!-- Header -->
        <header class="dash-header">
            <div class="logo-container">
                <div class="logo-icon" style="width:30px; height:30px; font-size: 18px;">
                    <ion-icon name="layers"></ion-icon>
                </div>
                <div>
                    <h2 style="color:white; margin:0; letter-spacing: -0.5px;">RenderShelf</h2>
                    <?php if($total_income > 0): ?>
                        <div style="font-size: 10px; color: #38ef7d; font-weight: bold; margin-top: 2px;">EARNINGS: $<?php echo number_format($total_income, 2); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="header-right">
                <a href="wallet.php" class="wallet-pill">
                    <ion-icon name="wallet-outline"></ion-icon>
                    $<?php echo number_format($_SESSION['wallet_balance'] ?? 0, 2); ?>
                </a>
                <div class="header-icons">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php" style="color:#8a2be2; font-size:14px; font-weight:bold; display:flex; align-items:center; gap:5px;"><ion-icon name="shield-checkmark-outline"></ion-icon> Admin</a>
                    <?php endif; ?>
                    <a href="notifications.php" style="position: relative;">
                        <ion-icon name="notifications-outline"></ion-icon>
                        <?php if($unread_count > 0): ?>
                            <span style="position: absolute; top: -5px; right: -5px; background: #ff4444; color: white; font-size: 10px; font-weight: bold; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #000;"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php"><ion-icon name="cart-outline"></ion-icon></a>
                    <div class="avatar" style="background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer;" onclick="location.href='profile.php'">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Search Bar -->
        <form action="browse.php" method="GET" class="search-bar-container">
            <ion-icon name="search-outline" class="search-icon"></ion-icon>
            <input type="text" name="search" placeholder="Search assets, packs, or tutorials..." required>
            <button type="submit" class="search-submit-btn"><ion-icon name="arrow-forward-outline"></ion-icon></button>
        </form>

        <!-- Category Chips -->
        <div class="category-chips">
            <a href="browse.php" class="chip active">All</a>
            <?php foreach($categories as $cat): ?>
                <a href="browse.php?category=<?php echo urlencode($cat['name']); ?>" class="chip"><?php echo htmlspecialchars($cat['name']); ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Featured Bundle Banner -->
        <div class="bundle-banner">
            <img src="img/featured_bundle.png" class="bundle-image" alt="Bundle">
            <div class="bundle-content">
                <span class="bundle-badge">Bundle Deal</span>
                <h3 class="bundle-title">Artist of the Month</h3>
                <p class="bundle-desc">Get the exclusive Cyberpunk City Pack with over 500 assets at half price.</p>
                <div class="bundle-price-row">
                    <span class="bundle-price">$29.99</span>
                    <span class="bundle-old-price">$60.00</span>
                    <a href="browse.php?category=VFX" class="btn-bundle">View Bundle</a>
                </div>
            </div>
        </div>

        <!-- Trending Now -->
        <div class="section-header">
            <h3 class="section-title">Trending Now</h3>
            <a href="browse.php" class="view-all">See All</a>
        </div>
        
        <div class="horizontal-list">
            <?php if(count($trending_assets) > 0): ?>
                <?php foreach($trending_assets as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="trending-card">
                        <div class="trending-thumb">
                            <img src="<?php echo $asset['thumbnail_path'] ?: ($asset['preview_path'] ?: 'img/auth_header_geo.png'); ?>" alt="">
                            <div class="save-btn" onclick="event.preventDefault(); toggleSave(this);"><ion-icon name="bookmark-outline"></ion-icon></div>
                        </div>
                        <div class="asset-title-row">
                            <div class="asset-title" style="font-size: 14px;"><?php echo htmlspecialchars($asset['title']); ?></div>
                        </div>
                        <div class="asset-meta">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 5.0</span>
                            <span class="<?php echo $asset['price'] > 0 ? 'asset-price-new' : 'asset-price-free'; ?>">
                                <?php echo $asset['price'] > 0 ? '$' . $asset['price'] : 'Free'; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Professional Placeholders -->
                <div class="trending-card">
                    <div class="trending-thumb">
                        <img src="https://images.unsplash.com/photo-1614850523296-d8c1af93d400?auto=format&fit=crop&q=80&w=300" alt="">
                        <div class="save-btn" onclick="event.preventDefault(); toggleSave(this);"><ion-icon name="bookmark-outline"></ion-icon></div>
                    </div>
                    <div class="asset-title" style="font-size: 14px;">Cinematic LUTs Vol. 2</div>
                    <div class="asset-meta">
                        <span class="asset-rating"><ion-icon name="star"></ion-icon> 5.0 (128)</span>
                        <span class="asset-price-new">$14.00</span>
                    </div>
                </div>
                <div class="trending-card">
                    <div class="trending-thumb">
                        <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&q=80&w=300" alt="">
                        <div class="save-btn" onclick="event.preventDefault(); toggleSave(this);"><ion-icon name="bookmark-outline"></ion-icon></div>
                    </div>
                    <div class="asset-title" style="font-size: 14px;">Glitch Transitions</div>
                    <div class="asset-meta">
                        <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.5 (84)</span>
                        <span class="asset-price-free">Free</span>
                    </div>
                </div>
                <div class="trending-card">
                    <div class="trending-thumb">
                        <img src="https://images.unsplash.com/photo-1620641788421-7a1c342ea42e?auto=format&fit=crop&q=80&w=300" alt="">
                        <div class="save-btn" onclick="event.preventDefault(); toggleSave(this);"><ion-icon name="bookmark-outline"></ion-icon></div>
                    </div>
                    <div class="asset-title" style="font-size: 14px;">Abstract 3D Shapes</div>
                    <div class="asset-meta">
                        <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.9 (210)</span>
                        <span class="asset-price-new">$22.00</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- New Arrivals -->
        <div class="section-header" style="margin-top: 10px;">
            <h3 class="section-title">New Arrivals</h3>
            <div class="view-controls">
                <div id="grid-toggle" class="view-btn active" onclick="setView('grid')"><ion-icon name="grid"></ion-icon></div>
                <div id="list-toggle" class="view-btn" onclick="setView('list')"><ion-icon name="list"></ion-icon></div>
            </div>
        </div>

        <div id="arrivals-grid" class="asset-grid">
            <?php if(count($new_assets) > 0): ?>
                <?php foreach($new_assets as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="asset-card" style="padding: 0; background: transparent;">
                        <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                            <img src="<?php echo $asset['thumbnail_path'] ?: ($asset['preview_path'] ?: 'img/auth_header_geo.png'); ?>" alt="">
                        </div>
                        <div class="asset-content-wrap">
                            <div class="asset-title" style="font-size: 13px; margin-top: 8px;"><?php echo htmlspecialchars($asset['title']); ?></div>
                            <div class="asset-meta">
                                <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.8</span>
                                <span class="asset-price-new" style="margin-left: auto;"><?php echo $asset['price'] > 0 ? '$' . $asset['price'] : 'Free'; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Sample Arrivals -->
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&q=80&w=600" alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">Sci-Fi Weapon Sounds</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.7</span>
                            <span class="asset-price-new">$8.99</span>
                        </div>
                    </div>
                </div>
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&q=80&w=600" alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">VHS Textures Pack</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.2</span>
                            <span class="asset-price-new">$12.50</span>
                        </div>
                    </div>
                </div>
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&q=80&w=600" alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">Low Poly Nature</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.8</span>
                            <span class="asset-price-new">$18.00</span>
                        </div>
                    </div>
                </div>
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1542332213-31f87348057f?auto=format&fit=crop&q=80&w=600" alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">Realistic Fog Overlay</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.6</span>
                            <span class="asset-price-new">$5.00</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="welcome.php" class="nav-item active">
            <ion-icon name="home"></ion-icon>
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
        <a href="library.php" class="nav-item">
            <ion-icon name="play-circle-outline"></ion-icon>
            Library
        </a>
        <a href="profile.php" class="nav-item">
            <ion-icon name="person-outline"></ion-icon>
            Profile
        </a>
    </nav>

    <script>
        function setView(view) {
            const grid = document.getElementById('arrivals-grid');
            const gridBtn = document.getElementById('grid-toggle');
            const listBtn = document.getElementById('list-toggle');
            
            if (view === 'list') {
                grid.classList.add('list-view');
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
            } else {
                grid.classList.remove('list-view');
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            }
        }

        function toggleSave(btn) {
            const icon = btn.querySelector('ion-icon');
            if (icon.name === 'bookmark-outline') {
                icon.name = 'bookmark';
                btn.style.color = '#8a2be2';
            } else {
                icon.name = 'bookmark-outline';
                btn.style.color = 'white';
            }
        }
    </script>
</body>
</html>
