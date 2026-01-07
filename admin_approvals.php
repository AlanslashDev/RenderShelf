<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Handle Action
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE assets SET status='approved' WHERE id=$id");
    header("Location: admin_approvals.php?msg=Asset Approved");
    exit;
}
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE assets SET status='rejected' WHERE id=$id");
    header("Location: admin_approvals.php?msg=Asset Rejected");
    exit;
}

$result = $conn->query("SELECT a.*, u.username FROM assets a JOIN users u ON a.creator_id = u.id WHERE a.status='pending'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvals - RenderShelf</title>
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
                <h1>Approvals Queue</h1>
                <p>Review and moderate content submitted by the community.</p>
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
                        <th>Price</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding-left: 0;">
                            <img src="<?php echo htmlspecialchars($row['thumbnail_path'] ?: ($row['preview_path'] ?: 'img/auth_header_geo.png')); ?>" style="width:80px; height:50px; object-fit:cover; border-radius:8px;">
                        </td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td style="color: #aaa;"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td style="font-weight: 600;">$<?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <div style="display:flex; gap:10px; align-items:center; justify-content: flex-end;">
                                <a href="?approve=<?php echo $row['id']; ?>" class="btn-primary" style="padding:8px 16px; font-size:12px; background:#38ef7d; color:black; text-decoration:none; border-radius:8px; font-weight:bold; margin-top: 0; width: auto; box-shadow: none;">Approve</a>
                                <a href="admin_delete_asset.php?id=<?php echo $row['id']; ?>" class="btn-primary" style="padding:8px 16px; font-size:12px; background:#ff4444; text-decoration:none; border-radius:8px; font-weight:bold; margin-top: 0; width: auto; box-shadow: none;">Delete</a>
                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="view-btn" style="width: 36px; height: 36px;">
                                    <ion-icon name="download-outline"></ion-icon>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($result->num_rows == 0): ?>
                <div style="text-align:center; padding:80px 0; color:#444;">
                    <ion-icon name="checkmark-done-circle-outline" style="font-size:64px; margin-bottom: 15px; color: #333;"></ion-icon>
                    <p style="font-size: 14px; font-weight: 500;">All caught up! The queue is empty.</p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </main>
</body>
</html>
