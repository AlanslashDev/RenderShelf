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
                <div class="header-icons">
                    <a href="notifications.php"><ion-icon name="notifications-outline"></ion-icon></a>
                    <a href="profile.php" class="avatar" style="width: 36px; height: 36px; background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer; overflow:hidden;">
                        <img src="<?php echo htmlspecialchars($profile_img); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </a>
                </div>
            </div>
        </header>

        <a href="welcome.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Dashboard
        </a>

        <!-- Earnings Summary Card -->
        <div class="revenue-card">
            <div class="revenue-content">
                <span class="revenue-label">Total Revenue</span>
                <h1>₹<?php echo number_format($total_income, 2); ?></h1>
                <p style="color: #666; font-size: 13px;">Earnings generated from your asset sales</p>
            </div>
            <a href="upload.php" class="btn-primary" style="width: auto; padding: 14px 25px; display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <ion-icon name="add-circle" style="font-size: 22px;"></ion-icon>
                New Upload
            </a>
        </div>

        <div class="section-header">
            <h3 class="section-title" style="font-size: 24px;">Manage Uploads</h3>
            <span style="font-size: 14px; color: #555; font-weight: 600;"><?php echo count($my_uploads); ?> items</span>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if(count($my_uploads) > 0): ?>
            <div class="asset-grid">
                <?php foreach($my_uploads as $asset): ?>
                    <div class="asset-card" style="background: var(--card-bg); border: 1px solid rgba(255,255,255,0.05);">
                        <div class="asset-thumb" style="height: 160px;">
                            <?php 
                                $preview_path = $asset['preview_path'];
                                $thumb_path = $asset['thumbnail_path'] ?? '';
                                $ext = pathinfo($preview_path, PATHINFO_EXTENSION);
                                $is_video = in_array(strtolower($ext), ['mp4', 'webm', 'mov', 'avi']);
                            ?>
                            <?php if ($is_video): ?>
                                <video src="<?php echo htmlspecialchars($preview_path); ?>" 
                                       poster="<?php echo htmlspecialchars($thumb_path ?: ''); ?>" 
                                       muted loop playsinline
                                       onmouseover="this.play()" 
                                       onmouseout="this.pause()" 
                                       style="width:100%; height:100%; object-fit:cover;"></video>
                            <?php elseif ($thumb_path || $preview_path): ?>
                                <img src="<?php echo htmlspecialchars($thumb_path ?: $preview_path); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(to bottom, #333, #111);"></div>
                            <?php endif; ?>
                            
                            <div style="position: absolute; top: 12px; right: 12px; padding: 5px 10px; border-radius: 8px; font-size: 10px; font-weight: 800; text-transform: uppercase; background: <?php echo $asset['status'] == 'approved' ? 'rgba(56, 239, 125, 0.9)' : ($asset['status'] == 'pending' ? 'rgba(255, 193, 7, 0.9)' : 'rgba(255, 68, 68, 0.9)'); ?>; color: black; backdrop-filter: blur(5px);">
                                <?php echo $asset['status']; ?>
                            </div>
                        </div>
                        <div class="asset-info">
                            <div class="asset-title"><?php echo htmlspecialchars($asset['title']); ?></div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <span style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($asset['category_name']); ?></span>
                                <span style="font-weight: 700; color: var(--accent-color);">₹<?php echo number_format($asset['price'], 2); ?></span>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <a href="asset_details.php?id=<?php echo $asset['id']; ?>" class="action-btn" style="background: rgba(255,255,255,0.05); color: white; padding: 8px; font-size: 12px; margin:0; border-radius: 10px; text-decoration: none;">View</a>
                                <a href="edit_asset.php?id=<?php echo $asset['id']; ?>" class="action-btn" style="background: rgba(255,255,255,0.05); color: white; padding: 8px; font-size: 12px; margin:0; border-radius: 10px; text-decoration: none;">Edit</a>
                                <a href="delete_asset_user.php?id=<?php echo $asset['id']; ?>" onclick="event.preventDefault(); showDeleteModal(<?php echo $asset['id']; ?>, '<?php echo addslashes($asset['title']); ?>')" class="action-btn" style="grid-column: span 2; background: rgba(255, 68, 68, 0.1); color: #ff4444; padding: 8px; font-size: 12px; margin:0; border-radius: 10px; text-decoration: none; border: 1px solid rgba(255, 68, 68, 0.1);">Delete Asset</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 100px 20px; background: rgba(255,255,255,0.02); border-radius: 30px; border: 1px dashed rgba(255,255,255,0.1);">
                <ion-icon name="cloud-upload-outline" style="font-size: 64px; margin-bottom: 20px; color: var(--accent-color); opacity: 0.5;"></ion-icon>
                <h3 style="color: white; margin-bottom: 10px;">No Uploads Yet</h3>
                <p style="color: #666; margin-bottom: 30px;">Start sharing your creative assets with the world.</p>
                <a href="upload.php" class="btn-primary" style="width: auto; display: inline-flex; padding: 14px 40px; border-radius: 15px;">Start Creating</a>
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

    <!-- Custom Delete Modal -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-icon-circle">
                <ion-icon name="trash-outline"></ion-icon>
            </div>
            <h3>Delete Asset?</h3>
            <p>Are you sure you want to delete "<span id="delete-asset-title" style="color:white; font-weight:600;"></span>"? This action cannot be undone.</p>
            <div class="modal-actions">
                <a id="confirm-delete-btn" href="#" class="modal-btn-confirm">Delete Asset</a>
                <button onclick="closeDeleteModal()" class="modal-btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
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
        window.onclick = function(event) {
            const modal = document.getElementById('delete-modal');
            if (event.target == modal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
