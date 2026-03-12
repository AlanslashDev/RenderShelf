<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: browse.php");
    exit;
}

$asset_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch Asset Details
$sql = "SELECT a.*, u.username as creator_name, c.name as category_name 
        FROM assets a 
        JOIN users u ON a.creator_id = u.id 
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Asset not found.");
}

$asset = $result->fetch_assoc();
$stmt->close();

// Check if user already owns it (if paid) or if it's their own
$has_access = false;
if ($asset['price'] == 0 || $asset['creator_id'] == $user_id) {
    $has_access = true;
} else {
    // Check transactions/purchases
    // For now, simple check
    $check_sql = "SELECT id FROM transactions WHERE user_id = ? AND related_asset_id = ? AND type = 'purchase'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $asset_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $has_access = true;
    }
}

// Fetch user favourites
$fav_query = $conn->prepare("SELECT COUNT(*) as is_fav FROM favourites WHERE user_id = ? AND asset_id = ?");
$fav_query->bind_param("ii", $user_id, $asset_id);
$fav_query->execute();
$is_favourite = $fav_query->get_result()->fetch_assoc()['is_fav'] > 0;

// Fetch Average Rating and Count
$rating_query = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE asset_id = ?");
$rating_query->bind_param("i", $asset_id);
$rating_query->execute();
$rating_data = $rating_query->get_result()->fetch_assoc();
$avg_rating = $rating_data['avg_rating'] ? number_format($rating_data['avg_rating'], 1) : 'No Ratings';
$total_reviews = $rating_data['total_reviews'];

// Fetch Related Tutorials (Priority: Explicitly Linked > Category Matching)
$related_tuts = [];
$tut_sql = "SELECT t.* FROM tutorials t 
            LEFT JOIN categories c ON t.category_id = c.id 
            WHERE t.related_asset_id = ? 
            OR (c.name = ? AND t.related_asset_id IS NULL)
            ORDER BY (t.related_asset_id = ?) DESC, t.created_at DESC LIMIT 2";
$tut_stmt = $conn->prepare($tut_sql);
$tut_stmt->bind_param("isi", $asset_id, $asset['category_name'], $asset_id);
$tut_stmt->execute();
$related_tuts = $tut_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asset['title']); ?> - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 50px;
        }

        @media (max-width: 900px) {
            .details-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        .preview-area {
            background: #000;
            border-radius: 32px;
            overflow: hidden;
            aspect-ratio: 16/9;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
        }

        .preview-media {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .details-sidebar {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(40px);
            padding: 35px;
            border-radius: 32px;
            height: fit-content;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 40px;
        }

        .asset-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 25px 0;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .asset-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.03);
            padding: 8px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .asset-price {
            font-size: 42px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 25px;
            letter-spacing: -1.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-btn {
            width: 100%;
            padding: 18px;
            border-radius: 18px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 18px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            letter-spacing: 0.3px;
        }

        .action-btn:hover {
            transform: translateY(-4px) scale(1.02);
        }

        .btn-buy {
            background: var(--accent-color);
            color: white;
            box-shadow: 0 15px 30px rgba(138, 43, 226, 0.4);
        }

        .btn-download {
            background: #38ef7d;
            color: #000;
            box-shadow: 0 15px 30px rgba(56, 239, 125, 0.3);
        }

        .creator-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.04);
            padding: 18px;
            border-radius: 20px;
            margin-top: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }

        .creator-card:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(138, 43, 226, 0.3);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 30px;
            transition: all 0.3s;
            padding: 12px 20px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .back-btn:hover {
            color: white;
            background: rgba(138, 43, 226, 0.1);
            border-color: rgba(138, 43, 226, 0.2);
            transform: translateX(-5px);
        }

        .section-tag {
            background: rgba(138, 43, 226, 0.1);
            color: var(--accent-color);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <div class="studio-container" style="padding-top: 0;">
        <header class="dash-header" style="border:none; margin-bottom: 20px; padding: 0;">
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

        <div class="details-container" style="padding: 0;">
            <!-- Main Content -->
            <div>
                <a href="welcome.php"
                    onclick="if(document.referrer && document.referrer.includes(window.location.host) && !document.referrer.includes('payment_gateway.php')) { history.back(); return false; }"
                    class="back-btn">
                    <ion-icon name="arrow-back"></ion-icon> Back
                </a>

                <div class="main-preview"
                    style="background: #000; border-radius: 24px; overflow: hidden; position: relative; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center;">
                    <?php
                    $preview_path = $asset['preview_path'];
                    if (!empty($preview_path)):
                        $ext = pathinfo($preview_path, PATHINFO_EXTENSION);
                        $is_audio = in_array(strtolower($ext), ['mp3', 'wav', 'ogg', 'm4a']);
                        $is_video = in_array(strtolower($ext), ['mp4', 'webm', 'mov', 'avi']);
                    ?>

                        <?php if ($is_video): ?>
                            <video controls muted loop src="<?php echo htmlspecialchars($preview_path); ?>"
                                class="preview-media" poster="<?php echo htmlspecialchars($asset['thumbnail_path'] ?? ''); ?>"
                                onmouseover="this.play()" onmouseout="this.pause()" playsinline></video>
                        <?php elseif ($is_audio): ?>
                            <div
                                style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(45deg, #1a1a1d, #000);">
                                <div
                                    style="font-size: 80px; color: var(--accent-color); margin-bottom: 20px; filter: drop-shadow(0 0 20px rgba(138,43,226,0.3));">
                                    <ion-icon name="musical-notes"></ion-icon>
                                </div>
                                <audio controls src="<?php echo htmlspecialchars($preview_path); ?>"
                                    style="width: 80%; max-width: 400px; height: 40px; border-radius: 30px;"></audio>
                                <p
                                    style="margin-top: 15px; font-size: 13px; color: #555; text-transform: uppercase; letter-spacing: 2px;">
                                    Audio Preview Mode</p>
                            </div>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($preview_path); ?>" class="preview-media">
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Fallback to thumbnail if no preview -->
                        <img src="<?php echo htmlspecialchars($asset['thumbnail_path'] ?? 'img/placeholder.png'); ?>" class="preview-media" style="opacity: 0.7;">
                        <div style="position: absolute; bottom: 20px; right: 20px; background: rgba(0,0,0,0.6); padding: 8px 15px; border-radius: 10px; font-size: 12px; color: white; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px);">
                            <ion-icon name="image-outline"></ion-icon> Thumbnail Preview
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 30px;">
                    <div class="section-tag">Asset Details</div>
                    <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 20px; letter-spacing: -0.5px;">
                        <?php echo htmlspecialchars($asset['title']); ?>
                    </h1>

                    <div class="asset-meta-row">
                        <div class="asset-meta-item">
                            <ion-icon name="folder-outline"></ion-icon>
                            <?php echo htmlspecialchars($asset['category_name']); ?>
                        </div>
                        <div class="asset-meta-item">
                            <ion-icon name="star" style="color: #ffbf00;"></ion-icon>
                            <?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> Reviews)
                        </div>
                        <div class="asset-meta-item">
                            <ion-icon name="cloud-download-outline"></ion-icon>
                            <?php echo $asset['download_count']; ?> Downloads
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: white;">Description</h3>
                        <p style="line-height: 1.7; color: #aaa; font-size: 15px;">
                            <?php echo nl2br(htmlspecialchars($asset['description'])); ?>
                        </p>
                    </div>

                    <!-- Related Tutorials Section -->
                    <?php if (count($related_tuts) > 0): ?>
                        <div style="margin-top: 40px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                                <h4
                                    style="font-size: 15px; font-weight:700; margin:0; display:flex; align-items:center; gap:8px;">
                                    <ion-icon name="school-outline" style="color:var(--accent-color);"></ion-icon> Master This
                                    Asset
                                </h4>
                                <a href="tutorials.php"
                                    style="font-size:11px; color:var(--accent-color); text-decoration:none;">View All</a>
                            </div>
                            <div style="display:flex; flex-direction:column; gap:15px;">
                                <?php foreach ($related_tuts as $rt): ?>
                                    <a href="<?php echo htmlspecialchars($rt['video_url']); ?>" target="_blank"
                                        style="text-decoration:none; display:flex; gap:12px; background:rgba(255,255,255,0.02); padding:10px; border-radius:15px; border:1px solid rgba(255,255,255,0.05); transition:all 0.3s;"
                                        onmouseover="this.style.background='rgba(255,255,255,0.05)'"
                                        onmouseout="this.style.background='rgba(255,255,255,0.02)'">
                                        <div
                                            style="width:80px; height:50px; border-radius:8px; overflow:hidden; flex-shrink:0; background:#000; position:relative;">
                                            <?php if ($rt['thumbnail_path']): ?>
                                                <img src="<?php echo htmlspecialchars($rt['thumbnail_path']); ?>"
                                                    style="width:100%; height:100%; object-fit:cover; opacity:0.8;">
                                            <?php else: ?>
                                                <div style="width:100%; height:100%; background:linear-gradient(45deg, #1a1a1a, #000);">
                                                </div>
                                            <?php endif; ?>
                                            <div
                                                style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.2);">
                                                <ion-icon name="play" style="font-size:16px; color:white;"></ion-icon>
                                            </div>
                                        </div>
                                        <div style="overflow:hidden;">
                                            <div
                                                style="font-size:12px; font-weight:600; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?php echo htmlspecialchars($rt['title']); ?>
                                            </div>
                                            <div style="font-size:10px; color:#555; margin-top:2px;">Learn with
                                                <?php echo htmlspecialchars($rt['author_name']); ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php 
                        $cat_name = strtolower(trim($asset['category_name']));
                        $basics_title = ""; $basics_what = ""; $basics_how = []; $basics_icon = "information-circle";

                        switch ($cat_name) {
                            case 'sfx': case 'sound effects':
                                $basics_title = "SFX Basics & Usage";
                                $basics_what = "Sound Effects (SFX) are artificially created or enhanced sounds used to emphasize artistic or other content of films, television shows, animation, video games, music, or other media.";
                                $basics_how = [
                                    '<strong style="color: #ccc;">Video Editing:</strong> Import the audio file directly into your timeline (Premiere Pro, DaVinci Resolve, Final Cut).',
                                    '<strong style="color: #ccc;">Game Dev:</strong> Use engines like Unity or Unreal to trigger SFX via Audio Source components.',
                                    '<strong style="color: #ccc;">Layering:</strong> Combine multiple sound effects to create unique, complex sounds.'
                                ];
                                $basics_icon = "musical-notes-outline"; break;
                            case 'motion graphics':
                                $basics_title = "Motion Graphics Basics & Usage";
                                $basics_what = "Motion Graphics are digital animations or pre-rendered elements used to enhance video content, often involving abstract shapes or typography.";
                                $basics_how = [
                                    '<strong style="color: #ccc;">Importing:</strong> Place the video file on a layer above your main footage.',
                                    '<strong style="color: #ccc;">Blending Modes:</strong> Set the blending mode of the clip to "Screen" or "Add" for black backgrounds.',
                                    '<strong style="color: #ccc;">Color Correction:</strong> Use effects like Tint to match the graphic\'s colors to your project.'
                                ];
                                $basics_icon = "layers-outline"; break;
                            case '3d models':
                                $basics_title = "3D Models Basics & Usage";
                                $basics_what = "3D Models are digital representations of objects in three dimensions, commonly used in games, VFX, or architecture.";
                                $basics_how = [
                                    '<strong style="color: #ccc;">Importing:</strong> Import formats (.obj, .fbx, .blend) into software like Blender or Unreal.',
                                    '<strong style="color: #ccc;">Materials:</strong> Re-link texture maps (Albedo, Normal, Roughness) in your material editor.',
                                    '<strong style="color: #ccc;">Optimization:</strong> Use LOD tools if the polycount is too high for your game engine.'
                                ];
                                $basics_icon = "cube-outline"; break;
                            case 'transitions':
                                $basics_title = "Transitions Basics & Usage";
                                $basics_what = "Transitions are visual effects used to seamlessly link two separate video clips.";
                                $basics_how = [
                                    '<strong style="color: #ccc;">Placement:</strong> Place the transition exactly over the cut point between two adjacent clips.',
                                    '<strong style="color: #ccc;">Mattes/Luma:</strong> Use "Track Matte Key" or "Luma Key" on the second clip for matte transitions.',
                                    '<strong style="color: #ccc;">Audio Sync:</strong> Accompany transitions with sound effects like whooshes for immersion.'
                                ];
                                $basics_icon = "shuffle-outline"; break;
                            case 'luts':
                                $basics_title = "LUTs Basics & Usage";
                                $basics_what = "LUTs are mathematical formulas that transform the color and tone of an image for quick color grading.";
                                $basics_how = [
                                    '<strong style="color: #ccc;">Premiere Pro:</strong> Go to Lumetri Color panel > Creative tab and browse for the .cube file.',
                                    '<strong style="color: #ccc;">DaVinci Resolve:</strong> Place the LUT in the Resolve LUT folder and apply it in the Color page.',
                                    '<strong style="color: #ccc;">Intensity:</strong> Dial back the opacity/intensity to 50-70% for a natural look.'
                                ];
                                $basics_icon = "color-palette-outline"; break;
                            case 'vfx':
                                $basics_title = "VFX Basics & Usage";
                                $basics_what = "VFX elements like smoke or magic dust are pre-rendered visual assets to be composited onto footage.";
                                $basics_how = [
                                    '<strong style="color: #ccc;">Compositing:</strong> Place the VFX clip above your main video track.',
                                    '<strong style="color: #ccc;">Blending:</strong> Change the blend mode to "Screen" if it has a black background.',
                                    '<strong style="color: #ccc;">Color Matching:</strong> Adjust temperature and brightness to match your scene.'
                                ];
                                $basics_icon = "flame-outline"; break;
                            default:
                                $basics_title = htmlspecialchars($asset['category_name']) . " Basics & Usage";
                                $basics_what = "This " . htmlspecialchars($asset['category_name']) . " asset is ready for your creative projects.";
                                $basics_how = [
                                    '<strong style="color: #ccc;">Extracting:</strong> Unzip the downloaded file to access project files.',
                                    '<strong style="color: #ccc;">Importing:</strong> Load compatible files into your preferred software.',
                                    '<strong style="color: #ccc;">Customizing:</strong> Tweak properties or swap media as needed.'
                                ];
                                $basics_icon = "cube-outline"; break;
                        }
                        ?>
                        
                        <div style="margin-top: 40px; padding: 30px; background: rgba(255,255,255,0.02); border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; border-bottom: 1px solid rgba(255,255,255,0.03); padding-bottom: 15px;">
                                <ion-icon name="<?php echo htmlspecialchars($basics_icon); ?>" style="font-size:28px; color:var(--accent-color);"></ion-icon>
                                <h4 style="font-size: 18px; font-weight:800; margin:0; color: white;"><?php echo htmlspecialchars($basics_title); ?></h4>
                            </div>
                            <div style="color: #aaa; font-size: 14px; line-height: 1.6;">
                                <p style="margin-bottom: 15px;"><strong style="color: white;">What is this?</strong><br><?php echo $basics_what; ?></p>
                                <p style="margin-bottom: 10px;"><strong style="color: white;">Usage Guide:</strong></p>
                                <ul style="padding-left: 20px; margin-bottom: 20px;">
                                    <?php foreach ($basics_how as $tip): ?>
                                        <li style="margin-bottom: 8px;"><?php echo $tip; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); font-size: 13px; color: #777;">
                                    Need help? <a href="tutorials.php" style="color:var(--accent-color); text-decoration:none; font-weight: 700;">Explore our detailed masterclasses.</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div style="margin-top: 40px;">
                <div class="details-sidebar">
                    <div class="asset-price">
                        <?php echo $asset['price'] > 0 ? '₹' . number_format($asset['price'], 2) : 'Free'; ?>
                    </div>

                    <?php if ($has_access): ?>
                        <a href="download.php?id=<?php echo $asset['id']; ?>" class="action-btn btn-download"
                            style="text-decoration:none;">
                            <ion-icon name="cloud-download-outline"></ion-icon> Download Now
                        </a>
                        
                        <!-- Leave Review Button (if owned) -->
                        <button onclick="document.getElementById('review-form-section').scrollIntoView({behavior: 'smooth'})" class="action-btn"
                            style="background: rgba(255,191,0,0.1); color: #ffbf00; border: 1px solid rgba(255,191,0,0.3);">
                            <ion-icon name="star-outline"></ion-icon> Write a Review
                        </button>
                    <?php else: ?>
                        <button id="add-to-cart-btn" class="action-btn btn-buy" data-id="<?php echo $asset['id']; ?>"
                            onclick="toggleCart(this)">
                            <ion-icon name="cart-outline"></ion-icon>
                            <?php echo (isset($_SESSION['cart']) && in_array($asset['id'], $_SESSION['cart'])) ? 'Remove from Cart' : 'Add to Cart'; ?>
                        </button>

                        <a href="payment_gateway.php?asset_id=<?php echo $asset['id']; ?>" class="action-btn"
                            style="background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.1); text-decoration: none;">
                            Buy Now (₹<?php echo number_format($asset['price'], 0); ?>)
                        </a>

                        <div
                            style="font-size: 12px; color: #555; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px;">
                            <ion-icon name="shield-checkmark-outline"></ion-icon>
                            Secure transaction via Wallet
                        </div>
                    <?php endif; ?>

                    <button class="action-btn" onclick="toggleSaveDetail(this, <?php echo $asset['id']; ?>)"
                        style="background: transparent; color: <?php echo $is_favourite ? '#8a2be2' : 'white'; ?>; border: 1px solid rgba(255,255,255,0.1);">
                        <ion-icon name="<?php echo $is_favourite ? 'bookmark' : 'bookmark-outline'; ?>"></ion-icon> 
                        <span class="btn-text"><?php echo $is_favourite ? 'Saved to Favourites' : 'Save to Favourites'; ?></span>
                    </button>

                    <div class="creator-card">
                        <div
                            style="width: 44px; height: 44px; background: #222; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; font-size: 18px; border: 2px solid rgba(138, 43, 226, 0.2);">
                            <?php echo strtoupper(substr($asset['creator_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 15px; color: white;">
                                <?php echo htmlspecialchars($asset['creator_name']); ?>
                            </div>
                            <div
                                style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
                                Creator</div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <div style="margin-top: 20px;">
                        <a href="admin_delete_asset.php?id=<?php echo $asset['id']; ?>" class="action-btn"
                            style="background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); text-decoration: none;">
                            <ion-icon name="trash-outline"></ion-icon> Admin Delete (With Reason)
                        </a>
                    </div>
                <?php elseif ($asset['creator_id'] == $user_id): ?>
                    <div style="margin-top: 20px;">
                        <a href="delete_asset_user.php?id=<?php echo $asset['id']; ?>"
                            onclick="event.preventDefault(); showDeleteModal(<?php echo $asset['id']; ?>, '<?php echo addslashes($asset['title']); ?>')"
                            class="action-btn"
                            style="background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); text-decoration: none;">
                            <ion-icon name="trash-outline"></ion-icon> Delete My Asset
                        </a>
                    </div>
                <?php endif; ?>

                <div
                    style="margin-top: 30px; background: rgba(138, 43, 226, 0.03); border: 1px dashed rgba(138, 43, 226, 0.1); border-radius: 20px; padding: 25px;">
                    <h4 style="font-size: 14px; margin-bottom: 10px;">License Agreement</h4>
                    <p style="font-size: 12px; color: #666; line-height: 1.5;">This asset includes a standard commercial
                        license. You can use it in unlimited personal and commercial projects.</p>
                </div>

            </div>
        </div>
    <!-- Reviews Section -->
    <div style="max-width: 1100px; margin: 40px auto 100px; padding: 0 20px;" id="review-form-section">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px;">
            <h3 style="font-size: 24px; margin:0;"><ion-icon name="chatbubbles-outline" style="color:var(--accent-color); vertical-align:middle;"></ion-icon> User Reviews</h3>
            
            <div style="font-size: 20px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                <ion-icon name="star" style="color: #ffbf00;"></ion-icon> <?php echo $avg_rating; ?> <span style="font-size: 14px; font-weight: normal; color: #888;">(<?php echo $total_reviews; ?>)</span>
            </div>
        </div>

        <?php if ($has_access): ?>
            <!-- Write Review Form -->
            <div style="background: rgba(255,255,255,0.02); border-radius: 20px; padding: 25px; margin-bottom: 40px; border: 1px solid rgba(255,255,255,0.05);">
                <h4 style="margin-top:0; margin-bottom:15px; font-size: 16px;">Write a Review</h4>
                <form id="submit-review-form" onsubmit="event.preventDefault(); submitReview();">
                    <input type="hidden" name="asset_id" id="review_asset_id" value="<?php echo $asset['id']; ?>">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 8px; font-size: 13px; color:#aaa;">Rating (1-5)</label>
                        <select name="rating" id="review_rating" style="width: 100%; padding: 12px; border-radius: 12px; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); color: white; cursor: pointer;" required>
                            <option value="5">5 - Excellent (Highly Recommend)</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Terrible (Do Not Buy)</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom: 8px; font-size: 13px; color:#aaa;">Review Message</label>
                        <textarea name="review_text" id="review_text" rows="4" placeholder="What did you like or dislike about this asset? How did you use it?" style="width: 100%; padding: 15px; border-radius: 12px; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); color: white; resize: vertical;" required></textarea>
                    </div>
                    <button type="submit" class="action-btn" style="background:var(--accent-color); color:white; width: auto; padding: 12px 30px; margin:0;"><ion-icon name="send-outline"></ion-icon> Post Review</button>
                    <div id="review-status-msg" style="margin-top: 10px; font-size: 13px; display: none;"></div>
                </form>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 40px; padding: 20px; background: rgba(255,191,0,0.05); border: 1px dashed rgba(255,191,0,0.2); border-radius: 15px; text-align: center;">
                <ion-icon name="lock-closed-outline" style="font-size: 24px; color: #ffbf00; margin-bottom: 10px;"></ion-icon>
                <p style="margin:0; font-size: 14px; color: #ccc;">You must own this asset to write a review.</p>
            </div>
        <?php endif; ?>

        <!-- List Reviews -->
        <div id="reviews-list">
            <?php
            $fetch_reviews = $conn->prepare("SELECT r.*, u.username, u.profile_pic FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.asset_id = ? ORDER BY r.created_at DESC");
            $fetch_reviews->bind_param("i", $asset_id);
            $fetch_reviews->execute();
            $reviews_result = $fetch_reviews->get_result();

            if ($reviews_result->num_rows > 0):
                while ($rev = $reviews_result->fetch_assoc()):
            ?>
                <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; gap: 20px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #222; overflow: hidden; flex-shrink: 0; display: flex; align-items:center; justify-content:center;">
                        <?php if(!empty($rev['profile_pic']) && $rev['profile_pic'] !== 'default_profile.png'): ?>
                            <img src="<?php echo htmlspecialchars($rev['profile_pic']); ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <span style="font-weight:bold; color:white;"><?php echo strtoupper(substr($rev['username'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="flex-grow: 1;">
                        <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                            <strong style="color: white; font-size: 15px;"><?php echo htmlspecialchars($rev['username']); ?></strong>
                            <span style="font-size: 12px; color: #666;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                        <div style="color: #ffbf00; font-size: 12px; margin-bottom: 10px;">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <ion-icon name="<?php echo $i <= $rev['rating'] ? 'star' : 'star-outline'; ?>"></ion-icon>
                            <?php endfor; ?>
                        </div>
                        <p style="margin:0; color: #aaa; font-size: 14px; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($rev['review_text'])); ?>
                        </p>
                    </div>
                </div>
            <?php 
                endwhile;
            else: 
            ?>
                <div style="text-align:center; padding: 40px 20px; color:#666;">
                    <ion-icon name="chatbox-ellipses-outline" style="font-size: 40px; margin-bottom: 10px; opacity:0.5;"></ion-icon>
                    <p style="margin:0;">No reviews yet. Be the first to share your experience!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Custom Delete Modal -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-icon-circle">
                <ion-icon name="trash-outline"></ion-icon>
            </div>
            <h3>Delete Asset?</h3>
            <p>Are you sure you want to delete "<span id="delete-asset-title"
                    style="color:white; font-weight:600;"></span>"? This action cannot be undone.</p>
            <div class="modal-actions">
                <a id="confirm-delete-btn" href="#" class="modal-btn-confirm">Delete Asset</a>
                <button onclick="closeDeleteModal()" class="modal-btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function toggleCart(btn) {
            const assetId = btn.getAttribute('data-id');
            const isRemoving = btn.innerText.includes('Remove');
            const action = isRemoving ? 'remove' : 'add';

            const formData = new FormData();
            formData.append('action', action);
            formData.append('asset_id', assetId);

            fetch('api/cart_actions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        btn.innerHTML = `<ion-icon name="cart-outline"></ion-icon> ${isRemoving ? 'Add to Cart' : 'Remove from Cart'}`;
                        // Optional: Update cart counter in header if you have one
                        showToast(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showToast(msg) {
            // Simple toast implementation or just alert for now
            const toast = document.createElement('div');
            toast.style.cssText = "position:fixed; bottom:100px; left:50%; transform:translateX(-50%); background:var(--accent-color); color:white; padding:12px 24px; border-radius:30px; z-index:9999; box-shadow:0 10px 30px rgba(0,0,0,0.5); font-weight:600; font-size:14px; animation: slideUp 0.3s ease-out;";
            toast.innerHTML = `<div style="display:flex; align-items:center; gap:8px;"><ion-icon name="checkmark-circle" style="font-size:20px;"></ion-icon> ${msg}</div>`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        function showDeleteModal(assetId, assetTitle) {
            document.getElementById('delete-asset-title').textContent = assetTitle;
            document.getElementById('confirm-delete-btn').href = 'delete_asset_user.php?id=' + assetId;
            const modal = document.getElementById('delete-modal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('active'), 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            modal.classList.remove('active');
            setTimeout(() => modal.style.display = 'none', 300);
        }

        // Close modal on click outside card
        window.onclick = function (event) {
            const modal = document.getElementById('delete-modal');
            if (event.target == modal) {
                closeDeleteModal();
            }
        }

        function toggleSaveDetail(btn, assetId) {
            const icon = btn.querySelector('ion-icon');
            const txt = btn.querySelector('.btn-text');
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
                        btn.style.color = 'white';
                        txt.textContent = 'Save to Favourites';
                    } else {
                        icon.name = 'bookmark';
                        btn.style.color = '#8a2be2';
                        txt.textContent = 'Saved to Favourites';
                    }
                    showToast(data.message);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function submitReview() {
            const assetId = document.getElementById('review_asset_id').value;
            const rating = document.getElementById('review_rating').value;
            const text = document.getElementById('review_text').value;
            const statusMsg = document.getElementById('review-status-msg');

            const formData = new FormData();
            formData.append('asset_id', assetId);
            formData.append('rating', rating);
            formData.append('review_text', text);

            fetch('api/submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                statusMsg.style.display = 'block';
                if (data.success) {
                    statusMsg.style.color = '#38ef7d';
                    statusMsg.textContent = data.message;
                    document.getElementById('submit-review-form').reset();
                    // Optionally refresh the page after 2 seconds to show the new review
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    statusMsg.style.color = '#ff4444';
                    statusMsg.textContent = data.message;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusMsg.style.display = 'block';
                statusMsg.style.color = '#ff4444';
                statusMsg.textContent = "An error occurred submitting the review.";
            });
        }
    </script>
</body>

</html>