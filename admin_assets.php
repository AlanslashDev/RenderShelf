<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

$result = $conn->query("SELECT a.*, u.username FROM assets a JOIN users u ON a.creator_id = u.id ORDER BY a.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assets - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
</head>

<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
            <header class="admin-header">
                <div class="header-title">
                    <h1>All Assets</h1>
                    <p>Manage all assets uploaded to the platform.</p>
                </div>
            </header>

            <?php if ($msg): ?>
                <div class="success-message" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <div class="content-box">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 0;">Preview</th>
                            <th>Title</th>
                            <th>Creator</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="padding-left: 0;">
                                    <?php if ($row['thumbnail_path']): ?>
                                        <img src="<?php echo htmlspecialchars($row['thumbnail_path']); ?>"
                                            style="width:80px; height:50px; object-fit:cover; border-radius:8px;">
                                    <?php else: ?>
                                        <?php $ext = pathinfo($row['preview_path'], PATHINFO_EXTENSION); ?>
                                        <?php if (in_array(strtolower($ext), ['mp4', 'webm', 'mov', 'avi'])): ?>
                                            <video src="<?php echo htmlspecialchars($row['preview_path']); ?>" muted loop
                                                onmouseover="this.play()" onmouseout="this.pause()"
                                                style="width:80px; height:50px; object-fit:cover; border-radius:8px;"></video>
                                        <?php else: ?>
                                            <img src="<?php echo htmlspecialchars($row['preview_path'] ?: 'img/auth_header_geo.png'); ?>"
                                                style="width:80px; height:50px; object-fit:cover; border-radius:8px;">
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td style="color: #aaa;"><?php echo htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <span
                                        class="status-pill <?php echo $row['is_approved'] == 1 ? 'status-approved' : ($row['is_approved'] == 2 ? 'status-rejected' : 'status-pending'); ?>">
                                        <?php echo $row['is_approved'] == 1 ? 'Approved' : ($row['is_approved'] == 2 ? 'Rejected' : 'Pending'); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600;">₹<?php echo number_format($row['price'], 2); ?></td>
                                <td>
                                    <div style="display:flex; gap:10px; align-items:center; justify-content: flex-end;">
                                        <a href="admin_delete_asset.php?id=<?php echo $row['id']; ?>" class="btn-primary"
                                            style="padding:8px 16px; font-size:12px; background:#ff4444; text-decoration:none; border-radius:8px; font-weight:bold; margin-top: 0; width: auto; box-shadow: none;">Delete</a>
                                        <a href="asset_details.php?id=<?php echo $row['id']; ?>" target="_blank"
                                            class="view-btn" style="width: 36px; height: 36px;">
                                            <ion-icon name="eye-outline"></ion-icon>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($result->num_rows == 0): ?>
                    <div style="text-align:center; padding:80px 0; color:#444;">
                        <p style="font-size: 14px; font-weight: 500;">No assets found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>

</html>