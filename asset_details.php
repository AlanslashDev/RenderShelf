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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($asset['title']); ?> - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .details-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 40px;
        }
        @media (max-width: 768px) {
            .details-container { grid-template-columns: 1fr; }
        }
        .preview-area {
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            aspect-ratio: 16/9;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.05);
        }
        .preview-media {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .details-sidebar {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 24px;
            height: fit-content;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .asset-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #888;
            font-size: 14px;
        }
        .asset-price {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }
        .action-btn {
            width: 100%;
            padding: 16px;
            text-align: center;
            border-radius: 14px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        .btn-buy { background: var(--accent-color); color: white; box-shadow: 0 4px 15px rgba(138, 43, 226, 0.3); }
        .btn-buy:hover { background: var(--accent-hover); transform: translateY(-2px); }
        .btn-download { background: #38ef7d; color: #000; }
        .btn-download:hover { transform: translateY(-2px); opacity: 0.9; }
        .creator-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255,255,255,0.03);
            padding: 15px;
            border-radius: 16px;
            margin-top: 20px;
        }
        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #aaa;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
            transition: color 0.2s;
        }
        .back-btn:hover { color: white; }
    </style>
</head>
<body>
    <header class="dash-header" style="padding: 20px; max-width: 1100px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; background: transparent; border: none;">
        <div class="logo-container" style="gap: 8px; display: flex; align-items: center;">
             <a href="welcome.php" style="text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <div class="logo-icon" style="width:30px; height:30px; font-size: 18px;">
                    <ion-icon name="layers"></ion-icon>
                </div>
                <h2 style="color:white; margin:0; letter-spacing: -0.5px; font-size: 20px;">RenderShelf</h2>
             </a>
        </div>
        <div style="display: flex; align-items: center; gap: 20px;">
            <a href="wallet.php" style="text-decoration: none; color: white; display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.05); padding: 8px 15px; border-radius: 50px; font-size: 14px; font-weight: 600;">
                <ion-icon name="wallet-outline" style="color: #38ef7d;"></ion-icon>
                $<?php echo number_format($_SESSION['wallet_balance'] ?? 0, 2); ?>
            </a>
            <a href="profile.php" style="width: 36px; height: 36px; border-radius: 50%; background: var(--accent-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; text-decoration: none; overflow: hidden;">
                <?php if (!empty($_SESSION['profile_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div class="details-container">
        <!-- Main Content -->
        <div>
            <a href="welcome.php" class="back-btn">
                <ion-icon name="arrow-back"></ion-icon> Back
            </a>
            
            <div class="preview-area">
                 <?php $ext = pathinfo($asset['preview_path'], PATHINFO_EXTENSION); ?>
                 <?php if (in_array(strtolower($ext), ['mp4', 'webm'])): ?>
                    <video controls src="<?php echo htmlspecialchars($asset['preview_path']); ?>" class="preview-media" poster="<?php echo htmlspecialchars($asset['thumbnail_path'] ?? ''); ?>"></video>
                 <?php else: ?>
                    <img src="<?php echo htmlspecialchars($asset['preview_path']); ?>" class="preview-media">
                 <?php endif; ?>
            </div>
            
            <div style="margin-top: 30px;">
                <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 15px;"><?php echo htmlspecialchars($asset['title']); ?></h1>
                
                <div style="display: flex; flex-wrap: wrap; gap: 20px; padding-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div class="asset-meta-item">
                        <ion-icon name="folder-outline"></ion-icon>
                        <?php echo htmlspecialchars($asset['category_name']); ?>
                    </div>
                    <div class="asset-meta-item">
                        <ion-icon name="star" style="color: #ffbf00;"></ion-icon>
                        4.8 (12 Reviews)
                    </div>
                    <div class="asset-meta-item">
                        <ion-icon name="cloud-download-outline"></ion-icon>
                        <?php echo $asset['download_count']; ?> Downloads
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px; color: white;">Description</h3>
                    <p style="line-height: 1.7; color: #aaa; font-size: 15px;"><?php echo nl2br(htmlspecialchars($asset['description'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="margin-top: 40px;">
            <div class="details-sidebar">
                <div class="asset-price">
                    <?php echo $asset['price'] > 0 ? '$' . number_format($asset['price'], 2) : 'Free'; ?>
                </div>

                <?php if ($has_access): ?>
                    <a href="download.php?id=<?php echo $asset['id']; ?>" class="action-btn btn-download" style="text-decoration:none;">
                        <ion-icon name="cloud-download-outline"></ion-icon> Download Now
                    </a>
                <?php else: ?>
                    <a href="payment_gateway.php?asset_id=<?php echo $asset['id']; ?>" class="action-btn btn-buy" style="text-decoration:none;">
                        Get Access Now
                    </a>
                    <div style="font-size: 12px; color: #555; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px;">
                        <ion-icon name="shield-checkmark-outline"></ion-icon>
                        Secure transaction via Wallet
                    </div>
                <?php endif; ?>

                <div class="creator-card">
                    <div style="width: 44px; height: 44px; background: #222; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; font-size: 18px; border: 2px solid rgba(138, 43, 226, 0.2);">
                         <?php echo strtoupper(substr($asset['creator_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 15px; color: white;"><?php echo htmlspecialchars($asset['creator_name']); ?></div>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Creator</div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div style="margin-top: 20px;">
                    <a href="admin_delete_asset.php?id=<?php echo $asset['id']; ?>" class="action-btn" style="background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); text-decoration: none;">
                        <ion-icon name="trash-outline"></ion-icon> Admin Delete (With Reason)
                    </a>
                </div>
            <?php elseif ($asset['creator_id'] == $user_id): ?>
                <div style="margin-top: 20px;">
                    <a href="delete_asset_user.php?id=<?php echo $asset['id']; ?>" onclick="return confirm('Are you sure you want to delete your asset? This cannot be undone.')" class="action-btn" style="background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid rgba(255, 68, 68, 0.2); text-decoration: none;">
                        <ion-icon name="trash-outline"></ion-icon> Delete My Asset
                    </a>
                </div>
            <?php endif; ?>

            <div style="margin-top: 30px; background: rgba(138, 43, 226, 0.03); border: 1px dashed rgba(138, 43, 226, 0.1); border-radius: 20px; padding: 25px;">
                <h4 style="font-size: 14px; margin-bottom: 10px;">License Agreement</h4>
                <p style="font-size: 12px; color: #666; line-height: 1.5;">This asset includes a standard commercial license. You can use it in unlimited personal and commercial projects.</p>
            </div>
        </div>
    </div>

</body>
</html>
