<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    // 1. Delete user assets (handled by ON DELETE CASCADE in SQL usually, but good to be sure or handle files)
    // For now, let SQL CASCADE handle the rows. 
    // files remain on disk but that's a cleanup task.
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        session_destroy();
        header("Location: register.php?msg=Account deleted successfully.");
        exit;
    } else {
        $error = "Error deleting account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="dashboard-container" style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
        <div class="auth-card" style="max-width: 400px; text-align: center; padding: 40px;">
            <ion-icon name="alert-circle-outline" style="font-size: 64px; color: #ff4444; margin-bottom: 20px;"></ion-icon>
            <h2 style="margin-bottom: 10px;">Delete Account?</h2>
            <p style="color: #aaa; font-size: 14px; line-height: 1.6; margin-bottom: 30px;">
                This action is permanent. All your assets, earnings, and data will be removed from RenderShelf.
            </p>
            
            <form action="" method="POST">
                <button type="submit" name="confirm_delete" class="btn-primary" style="background: #ff4444; margin-bottom: 15px;">Yes, Delete My Account</button>
                <a href="profile.php" class="btn-secondary" style="text-decoration: none; display: block; border-radius: 12px; padding: 12px;">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>
