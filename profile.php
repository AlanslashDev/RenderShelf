<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_username = trim($_POST['username']);
    $new_bio = trim($_POST['bio']);
    
    // Optional: Password Update
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_username)) {
        $error = "Username cannot be empty.";
    } else {
        // Update basic info
        $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_username, $new_bio, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $new_username; // Update session
            $success = "Profile updated successfully.";
            
            // Handle Password Change if provided
            if (!empty($new_password)) {
                if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $pwd_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $pwd_stmt->bind_param("si", $hashed, $user_id);
                    if ($pwd_stmt->execute()) {
                        $success .= " Password changed.";
                    } else {
                        $error = "Error updating password.";
                    }
                } else {
                    $error = "Passwords do not match or too short.";
                }
            }
        } else {
            $error = "Error updating profile. Username might be taken (if unique enforced).";
        }
    }
}

// Fetch Current Data
$stmt = $conn->prepare("SELECT username, bio, email, wallet_balance, role, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="dashboard-container">
        
        <header class="dash-header">
            <div class="logo-area" style="margin:0;">
                <a href="welcome.php" style="color:white; text-decoration:none;"><h3><ion-icon name="arrow-back-outline"></ion-icon> Back</h3></a>
            </div>
            <div style="display:flex; align-items:center; gap:15px;">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php" style="color:#8a2be2; font-size:14px; text-decoration:none; font-weight:bold; display:flex; align-items:center; gap:5px;"><ion-icon name="shield-checkmark-outline"></ion-icon> Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-primary" style="background:#ff4444; width:auto; padding:8px 15px; font-size:14px; text-decoration:none;">Log Out</a>
            </div>
        </header>

        <div class="auth-card" style="width:100%; max-width:600px; margin:40px auto; padding:30px;">
            
            <div style="text-align:center; margin-bottom:30px;">
                <div style="width:100px; height:100px; background:#8a2be2; border-radius:50%; margin:0 auto; display:flex; align-items:center; justify-content:center; font-size:40px; font-weight:bold; color:white; overflow:hidden; border: 3px solid rgba(138, 43, 226, 0.2);">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div style="margin-top:10px; color:#aaa;"><?php echo htmlspecialchars($user['email']); ?></div>
                <div style="margin-top:5px; font-size:12px; text-transform:uppercase; letter-spacing:1px; background:#333; display:inline-block; padding:2px 8px; border-radius:4px;"><?php echo htmlspecialchars($user['role']); ?></div>
            </div>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <h3 style="margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">Public Info</h3>
                
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <ion-icon name="person-outline" class="input-icon"></ion-icon>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="3" style="width:100%; background:#2a2a2a; border:none; color:white; padding:10px; border-radius:8px;"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <h3 style="margin:30px 0 15px; border-bottom:1px solid #333; padding-bottom:10px;">Security</h3>

                <div class="form-group">
                    <label>New Password (Optional)</label>
                    <div class="input-wrapper">
                        <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current">
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrapper">
                         <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                        <input type="password" name="confirm_password" placeholder="Confirm new password">
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="margin-top:20px;">Save Changes</button>
            </form>

            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #333; text-align: center;">
                <p style="font-size: 13px; color: #555; margin-bottom: 15px;">Danger Zone</p>
                <a href="delete_account.php" style="color: #ff4444; font-size: 14px; text-decoration: none; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <ion-icon name="trash-outline"></ion-icon> Delete My Account
                </a>
            </div>
        </div>

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
        <a href="manage_uploads.php" class="nav-item">
            <ion-icon name="cloud-upload-outline"></ion-icon>
            Uploads
        </a>
        <a href="library.php" class="nav-item">
            <ion-icon name="play-circle-outline"></ion-icon>
            Library
        </a>
        <a href="profile.php" class="nav-item active">
            <ion-icon name="person"></ion-icon>
            Profile
        </a>
    </nav>
</body>
</html>
