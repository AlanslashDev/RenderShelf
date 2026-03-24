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
$trending_result = $conn->query("SELECT a.*, u.username as creator_name FROM assets a JOIN users u ON a.creator_id = u.id WHERE a.is_approved = 1 AND a.creator_id != $user_id ORDER BY a.download_count DESC, a.created_at DESC LIMIT 5");
$trending_assets = $trending_result->fetch_all(MYSQLI_ASSOC);

// Fetch Tutorials
$tut_result = $conn->query("SELECT t.*, c.name as category_name FROM tutorials t LEFT JOIN categories c ON t.category_id = c.id ORDER BY t.created_at DESC LIMIT 5");
$tutorials_list = $tut_result->fetch_all(MYSQLI_ASSOC);

// Fetch New Arrivals (From others)
$new_result = $conn->query("SELECT a.*, u.username as creator_name FROM assets a JOIN users u ON a.creator_id = u.id WHERE a.is_approved = 1 AND a.creator_id != $user_id ORDER BY a.created_at DESC LIMIT 6");
$new_assets = $new_result->fetch_all(MYSQLI_ASSOC);

// Fetch User's Own Uploads
$my_uploads_result = $conn->query("SELECT * FROM assets WHERE creator_id = $user_id ORDER BY created_at DESC");
$my_uploads = $my_uploads_result->fetch_all(MYSQLI_ASSOC);

// Fetch Generated Income (Total from sales)
$income_query = $conn->prepare("SELECT SUM(amount) as total_income FROM transactions WHERE type='sale' AND user_id = ?");
$income_query->bind_param("i", $user_id);
$income_query->execute();
$income_res = $income_query->get_result()->fetch_assoc();
$total_income = $income_res['total_income'] ?? 0;

// Fetch Unread Notifications count
$unread_query = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_query->bind_param("i", $user_id);
$unread_query->execute();
$unread_count = $unread_query->get_result()->fetch_assoc()['c'];

// Fetch Favourites
$fav_query = $conn->prepare("SELECT asset_id FROM favourites WHERE user_id = ?");
$fav_query->bind_param("i", $user_id);
$fav_query->execute();
$fav_result = $fav_query->get_result();
$favourites = [];
while ($row = $fav_result->fetch_assoc()) {
    $favourites[] = $row['asset_id'];
}
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
        .dash-header h2 {
            font-size: 20px;
            font-weight: 800;
        }

        .header-icons {
            display: flex;
            gap: 15px;
            color: white;
            font-size: 24px;
        }

        .search-bar-container input::placeholder {
            color: #555;
        }
    </style>
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
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin_dashboard.php"
                            style="color:#8a2be2; font-size:14px; font-weight:bold; display:flex; align-items:center; gap:5px;"><ion-icon
                                name="shield-checkmark-outline"></ion-icon> Admin</a>
                    <?php endif; ?>
                    <a href="notifications.php" style="position: relative;">
                        <ion-icon name="notifications-outline"></ion-icon>
                        <?php if ($unread_count > 0): ?>
                            <span
                                style="position: absolute; top: -5px; right: -5px; background: #ff4444; color: white; font-size: 10px; font-weight: bold; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #000;"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php"><ion-icon name="cart-outline"></ion-icon></a>
                    <a href="profile.php" class="avatar"
                        style="width: 36px; height: 36px; background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer; overflow:hidden;">
                        <?php if (!empty($_SESSION['profile_pic']) && file_exists($_SESSION['profile_pic'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <ion-icon name="person"></ion-icon>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </header>

        <!-- Search Bar -->
        <form action="browse.php" method="GET" class="search-bar-container" style="margin-bottom: 25px;">
            <ion-icon name="search-outline" class="search-icon"></ion-icon>
            <input type="text" name="search" placeholder="Search for assets, packs, or tutorials..." required
                style="border-radius: 16px;">
            <button type="submit" class="search-submit-btn"><ion-icon name="arrow-forward-outline"></ion-icon></button>
        </form>

        <!-- Category Chips -->
        <div class="category-chips" style="margin-bottom: 30px;">
            <a href="browse.php" class="chip active">All Assets</a>
            <?php foreach ($categories as $cat): ?>
                <a href="browse.php?category=<?php echo urlencode($cat['name']); ?>"
                    class="chip"><?php echo htmlspecialchars($cat['name']); ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Dashboard Welcome Section -->
        <div class="user-welcome"
            style="margin-bottom: 40px; display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); padding: 30px; border-radius: 28px; border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(15px);">
            <div>
                <h3 style="margin:0; font-size: 22px; font-weight: 700;">Welcome back,
                    <?php echo htmlspecialchars($username); ?>! 👋
                </h3>
                <p style="font-size: 14px; color: #555; margin-top: 8px; font-weight: 500;">
                    Your store has generated <span
                        style="color:#38ef7d; font-weight:800;">₹<?php echo number_format($total_income, 2); ?></span>
                    in total revenue.
                </p>
            </div>
            <div style="display:flex; gap:12px;">
                <a href="upload.php" class="btn-primary"
                    style="margin:0; padding: 12px 24px; border-radius: 14px; font-size: 14px; text-decoration: none; display:flex; align-items:center; gap:8px;">
                    <ion-icon name="cloud-upload-outline" style="font-size: 18px;"></ion-icon> Upload Asset
                </a>
            </div>
        </div>

        <?php if (count($my_uploads) > 0): ?>
            <div class="section-header">
                <h3 class="section-title">Your Recent Uploads</h3>
                <a href="manage_uploads.php" class="view-all">Manage All</a>
            </div>
            <div class="horizontal-list">
                <?php foreach (array_slice($my_uploads, 0, 5) as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="trending-card">
                        <div class="trending-thumb">
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
                                <img src="<?php echo htmlspecialchars($thumb_path ?: $preview_path); ?>" alt="">
                            <?php else: ?>
                                <img src="img/auth_header_geo.png" alt="">
                            <?php endif; ?>
                            <div class="status-badge"
                                style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                                <?php echo $asset['is_approved'] == 1 ? 'Live' : ($asset['is_approved'] == 2 ? 'Rejected' : 'Pending'); ?>
                            </div>
                        </div>
                        <div class="asset-title-row">
                            <div class="asset-title" style="font-size: 14px;"><?php echo htmlspecialchars($asset['title']); ?>
                            </div>
                        </div>
                        <div class="asset-meta">
                            <span class="asset-rating"><ion-icon name="eye"></ion-icon>
                                <?php echo $asset['view_count'] ?? 0; ?></span>
                            <span class="<?php echo $asset['price'] > 0 ? 'asset-price-new' : 'asset-price-free'; ?>">
                                <?php echo $asset['price'] > 0 ? '₹' . $asset['price'] : 'Free'; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Featured Bundle Banner -->
        <div class="bundle-banner">
            <img src="img/featured_bundle.png" class="bundle-image" alt="Bundle">
            <div class="bundle-content">
                <span class="bundle-badge">Bundle Deal</span>
                <h3 class="bundle-title">Artist of the Month</h3>
                <p class="bundle-desc">Get the exclusive Cyberpunk City Pack with over 500 assets at half price.</p>
                <div class="bundle-price-row">
                    <span class="bundle-price">₹29.99</span>
                    <span class="bundle-old-price">₹60.00</span>
                    <a href="browse.php?category=VFX" class="btn-bundle">View Bundle</a>
                </div>
            </div>
        </div>

        <!-- Trending Now -->
        <div class="section-header" style="margin-top: 40px;">
            <h3 class="section-title">Trending Now</h3>
            <a href="browse.php" class="view-all">See All</a>
        </div>

        <div class="horizontal-list">
            <?php if (count($trending_assets) > 0): ?>
                <?php foreach ($trending_assets as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="trending-card">
                        <div class="trending-thumb">
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
                                <img src="<?php echo htmlspecialchars($thumb_path ?: $preview_path); ?>" alt="">
                            <?php else: ?>
                                <img src="img/auth_header_geo.png" alt="">
                            <?php endif; ?>
                            <div class="save-btn" data-id="<?php echo $asset['id']; ?>" onclick="event.preventDefault(); toggleSave(this);">
                                <ion-icon name="<?php echo in_array($asset['id'], $favourites) ? 'bookmark' : 'bookmark-outline'; ?>" style="color: <?php echo in_array($asset['id'], $favourites) ? '#8a2be2' : 'white'; ?>"></ion-icon>
                            </div>
                        </div>
                        <div class="asset-title-row">
                            <div class="asset-title" style="font-size: 14px;"><?php echo htmlspecialchars($asset['title']); ?>
                            </div>
                        </div>
                        <div class="asset-meta">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 5.0</span>
                            <span class="asset-price-new">
                                <?php echo $asset['price'] > 0 ? '₹' . $asset['price'] : 'Free'; ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Dynamic Category Sections -->
        <?php foreach ($categories as $cat): ?>
            <?php
            // Fetch assets for this category
            $cat_id = $cat['id'];
            $cat_assets_query = $conn->query("SELECT a.*, u.username as creator_name FROM assets a JOIN users u ON a.creator_id = u.id WHERE a.is_approved = 1 AND a.category_id = $cat_id ORDER BY a.created_at DESC LIMIT 5");
            $cat_assets = $cat_assets_query->fetch_all(MYSQLI_ASSOC);

            if (count($cat_assets) > 0):
                ?>
                <div class="section-header" style="margin-top: 40px;">
                    <h3 class="section-title"><?php echo htmlspecialchars($cat['name']); ?></h3>
                    <a href="browse.php?category=<?php echo urlencode($cat['name']); ?>" class="view-all">Browse
                        <?php echo htmlspecialchars($cat['name']); ?></a>
                </div>

                <div class="horizontal-list">
                    <?php foreach ($cat_assets as $asset): ?>
                        <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="trending-card">
                            <div class="trending-thumb">
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
                                    <img src="<?php echo htmlspecialchars($thumb_path ?: $preview_path); ?>" alt="">
                                <?php else: ?>
                                    <img src="img/auth_header_geo.png" alt="">
                                <?php endif; ?>
                                <div class="save-btn" data-id="<?php echo $asset['id']; ?>" onclick="event.preventDefault(); toggleSave(this);">
                                    <ion-icon name="<?php echo in_array($asset['id'], $favourites) ? 'bookmark' : 'bookmark-outline'; ?>" style="color: <?php echo in_array($asset['id'], $favourites) ? '#8a2be2' : 'white'; ?>"></ion-icon>
                                </div>
                            </div>
                            <div class="asset-title-row">
                                <div class="asset-title" style="font-size: 14px;"><?php echo htmlspecialchars($asset['title']); ?>
                                </div>
                            </div>
                            <div class="asset-meta">
                                <span class="asset-rating"><ion-icon name="star"></ion-icon> 5.0</span>
                                <span class="<?php echo $asset['price'] > 0 ? 'asset-price-new' : 'asset-price-free'; ?>">
                                    <?php echo $asset['price'] > 0 ? '₹' . $asset['price'] : 'Free'; ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Learning Center (Tutorials) -->
        <div class="section-header" style="margin-top: 40px;">
            <h3 class="section-title">Tutorial Library</h3>
            <a href="tutorials.php" class="view-all">Explore All</a>
        </div>

        <div class="horizontal-list">
            <?php if (count($tutorials_list) > 0): ?>
                <?php foreach ($tutorials_list as $tut): ?>
                    <a href="<?php echo htmlspecialchars($tut['video_url']); ?>" target="_blank" class="learning-card"
                        style="text-decoration:none; color:inherit;">
                        <div class="thumb-box" style="background:#111; position:relative; overflow:hidden;">
                            <?php if ($tut['thumbnail_path']): ?>
                                <img src="<?php echo htmlspecialchars($tut['thumbnail_path']); ?>"
                                    style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(45deg, #1a1a1a, #000);"></div>
                            <?php endif; ?>
                            <span class="duration-badge"><?php echo htmlspecialchars($tut['duration']); ?></span>
                            <div
                                style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.2);">
                                <ion-icon name="play-circle" style="font-size:40px; color:white; opacity:0.8;"></ion-icon>
                            </div>
                        </div>
                        <div class="card-title" style="font-size: 13px; margin-top: 5px;">
                            <?php echo htmlspecialchars($tut['title']); ?>
                        </div>
                        <div class="card-author" style="font-size: 11px; opacity: 0.6;">By
                            <?php echo htmlspecialchars($tut['author_name']); ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Professional Tutorial Placeholders -->
                <div class="learning-card">
                    <div class="thumb-box" style="background: linear-gradient(45deg, #2b5876 0%, #4e4376 100%);">
                        <span class="duration-badge">12:04</span>
                        <div
                            style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.1);">
                            <ion-icon name="play-circle" style="font-size:40px; color:white; opacity:0.8;"></ion-icon>
                        </div>
                    </div>
                    <div class="card-title" style="font-size: 13px;">Mastering DaVinci Resolve</div>
                    <div class="card-author" style="font-size: 11px; opacity: 0.6;">By Sarah J.</div>
                </div>
                <div class="learning-card">
                    <div class="thumb-box" style="background: linear-gradient(45deg, #cc2b5e 0%, #753a88 100%);">
                        <span class="duration-badge">25:10</span>
                        <div
                            style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.1);">
                            <ion-icon name="play-circle" style="font-size:40px; color:white; opacity:0.8;"></ion-icon>
                        </div>
                    </div>
                    <div class="card-title" style="font-size: 13px;">Advanced Color Grading</div>
                    <div class="card-author" style="font-size: 11px; opacity: 0.6;">By ColoristPro</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- New Arrivals -->
        <div class="section-header" style="margin-top: 40px;">
            <h3 class="section-title">New Arrivals</h3>
            <div class="view-controls">
                <div id="grid-toggle" class="view-btn active" onclick="setView('grid')"><ion-icon
                        name="grid"></ion-icon></div>
                <div id="list-toggle" class="view-btn" onclick="setView('list')"><ion-icon name="list"></ion-icon></div>
            </div>
        </div>

        <div id="arrivals-grid" class="asset-grid">
            <?php if (count($new_assets) > 0): ?>
                <?php foreach ($new_assets as $asset): ?>
                    <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="asset-card"
                        style="padding: 0; background: transparent;">
                        <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                            <?php
                            $preview_path = $asset['preview_path'];
                            $thumb_path = $asset['thumbnail_path'] ?? '';
                            $ext = pathinfo($preview_path, PATHINFO_EXTENSION);
                            $is_video = in_array(strtolower($ext), ['mp4', 'webm', 'mov', 'avi']);
                            $is_audio = in_array(strtolower($ext), ['mp3', 'wav', 'ogg', 'm4a']);
                            ?>
                            <?php if ($is_video): ?>
                                <video src="<?php echo htmlspecialchars($preview_path); ?>"
                                    poster="<?php echo htmlspecialchars($thumb_path ?: ''); ?>" muted loop playsinline
                                    onmouseover="this.play()" onmouseout="this.pause()"
                                    style="width:100%; height:100%; object-fit:cover;"></video>
                            <?php elseif ($thumb_path || $preview_path): ?>
                                <img src="<?php echo htmlspecialchars($thumb_path ?: $preview_path); ?>" alt="">
                            <?php else: ?>
                                <img src="img/auth_header_geo.png" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="asset-content-wrap">
                            <div class="asset-title" style="font-size: 13px; margin-top: 8px;">
                                <?php echo htmlspecialchars($asset['title']); ?>
                            </div>
                            <div class="asset-meta">
                                <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.8</span>
                                <span class="asset-price-new"
                                    style="margin-left: auto;"><?php echo $asset['price'] > 0 ? '₹' . $asset['price'] : 'Free'; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Sample Arrivals -->
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&q=80&w=600"
                            alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">Sci-Fi Weapon Sounds</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.7</span>
                            <span class="asset-price-new">₹8.99</span>
                        </div>
                    </div>
                </div>
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&q=80&w=600"
                            alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">VHS Textures Pack</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.2</span>
                            <span class="asset-price-new">₹12.50</span>
                        </div>
                    </div>
                </div>
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&q=80&w=600"
                            alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">Low Poly Nature</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.8</span>
                            <span class="asset-price-new">₹18.00</span>
                        </div>
                    </div>
                </div>
                <div class="asset-card" style="padding: 0; background: transparent;">
                    <div class="trending-thumb" style="height: 120px; border-radius: 16px;">
                        <img src="https://images.unsplash.com/photo-1542332213-31f87348057f?auto=format&fit=crop&q=80&w=600"
                            alt="">
                    </div>
                    <div class="asset-content-wrap">
                        <div class="asset-title" style="font-size: 13px; margin-top: 8px;">Realistic Fog Overlay</div>
                        <div class="asset-meta" style="display: flex; align-items: center; justify-content: space-between;">
                            <span class="asset-rating"><ion-icon name="star"></ion-icon> 4.6</span>
                            <span class="asset-price-new">₹5.00</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="welcome.php"
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'welcome.php' ? 'active' : ''; ?>">
            <ion-icon
                name="home<?php echo basename($_SERVER['PHP_SELF']) == 'welcome.php' ? '' : '-outline'; ?>"></ion-icon>
            Home
        </a>
        <a href="browse.php"
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'browse.php' ? 'active' : ''; ?>">
            <ion-icon name="search-outline"></ion-icon>
            Search
        </a>
        <a href="manage_uploads.php"
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_uploads.php' ? 'active' : ''; ?>">
            <ion-icon name="cloud-upload-outline"></ion-icon>
            Uploads
        </a>
        <a href="library.php"
            class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'library.php' ? 'active' : ''; ?>">
            <ion-icon name="play-circle-outline"></ion-icon>
            Library
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin_dashboard.php" class="nav-item">
                <ion-icon name="grid-outline"></ion-icon>
                Admin
            </a>
        <?php else: ?>
            <a href="profile.php"
                class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <ion-icon name="person-outline"></ion-icon>
                Profile
            </a>
        <?php endif; ?>
    </nav>

    <!-- Chatbot Widget -->
    <?php include 'includes/chatbot_widget.php'; ?>

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
            const assetId = btn.getAttribute('data-id');
            const icon = btn.querySelector('ion-icon');
            const isSaved = icon.name === 'bookmark';
            const action = isSaved ? 'remove' : 'add';

            const formData = new FormData();
            formData.append('action', action);
            formData.append('asset_id', assetId);

            fetch('api/toggle_favourite.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isSaved) {
                        icon.name = 'bookmark-outline';
                        icon.style.color = 'white';
                    } else {
                        icon.name = 'bookmark';
                        icon.style.color = '#8a2be2';
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>

</html>