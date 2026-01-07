<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asset_id'])) {
    $asset_id = intval($_POST['asset_id']);
    $reason = trim($_POST['reason']);
    
    if (empty($reason)) {
        die("Reason is required for deletion.");
    }

    // 1. Get Creator ID and Asset Title before deleting
    $stmt = $conn->prepare("SELECT creator_id, title FROM assets WHERE id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();

    if ($asset) {
        $creator_id = $asset['creator_id'];
        $asset_title = $asset['title'];

        // 2. Insert Notification for the owner
        $notif_msg = "Your asset '$asset_title' has been deleted by an administrator. Reason: $reason";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'alert')");
        $notif_stmt->bind_param("is", $creator_id, $notif_msg);
        $notif_stmt->execute();

        // 3. Delete Asset
        $delete_stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
        $delete_stmt->bind_param("i", $asset_id);
        
        if ($delete_stmt->execute()) {
            header("Location: admin_approvals.php?msg=Asset Deleted and Owner Notified");
            exit;
        } else {
            echo "Error deleting asset: " . $conn->error;
        }
    } else {
        echo "Asset not found.";
    }
} else {
    // Show form if accessed directly with ID
    $asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$asset_id) die("Invalid Asset ID.");
    
    $stmt = $conn->prepare("SELECT title FROM assets WHERE id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $asset = $stmt->get_result()->fetch_assoc();
    if (!$asset) die("Asset not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Asset - Admin</title>
    <link rel="stylesheet" href="style.css">
    <?php include 'includes/admin_styles.php'; ?>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
            <header class="admin-header">
                <h1>Delete Asset</h1>
            </header>
            
            <div class="content-box" style="max-width: 600px; margin-top: 20px;">
                <p style="margin-bottom: 20px;">Deleting asset: <strong><?php echo htmlspecialchars($asset['title']); ?></strong></p>
                <form action="" method="POST">
                    <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                    <div class="form-group">
                        <label>Reason for Deletion</label>
                        <textarea name="reason" rows="5" required style="width:100%; background:#1a1a1a; color:white; border:1px solid #333; padding:15px; border-radius:12px; margin-top:10px;" placeholder="Explain why this asset is being removed (this will be sent to the owner)"></textarea>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:20px;">
                        <button type="submit" class="btn-primary" style="background:#ff4444; margin:0;">Confirm Deletion</button>
                        <a href="admin_approvals.php" class="btn-secondary" style="margin:0; text-decoration:none; display:flex; align-items:center; justify-content:center;">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
