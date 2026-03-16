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
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username']);
        $new_bio = trim($_POST['bio']);
        
        if (empty($new_username)) {
            $error = "Username cannot be empty.";
        } else {
            // Check if username is already taken by someone else
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->bind_param("si", $new_username, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "This username is already taken by another user.";
            } else {
                // Update basic info
                $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ? WHERE id = ?");
                $stmt->bind_param("ssi", $new_username, $new_bio, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $new_username;
                    $success = "Profile updated successfully.";
                } else {
                    $error = "Error updating profile.";
                }
            }
            $check_stmt->close();
        }

        // Handle File Upload
        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_pic']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $filesize = $_FILES['profile_pic']['size'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if(!in_array($ext, $allowed)) {
                $error = "Invalid image format. Allowed: " . implode(', ', $allowed);
            } elseif ($filesize > $max_size) {
                $error = "Profile picture too large. Max 5MB allowed.";
            } else {
                $target_dir = "uploads/profiles/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $target_file = $target_dir . "user_" . $user_id . "_" . time() . "." . $ext;
                
                // Final check to ensure it's a real image
                $check = getimagesize($_FILES['profile_pic']['tmp_name']);
                if($check !== false) {
                    if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                        // Delete old pic if exists and not default
                        $old_stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
                        $old_stmt->bind_param("i", $user_id);
                        $old_stmt->execute();
                        $old_pic = $old_stmt->get_result()->fetch_assoc()['profile_pic'];
                        if ($old_pic && file_exists($old_pic) && strpos($old_pic, 'default') === false) {
                            @unlink($old_pic);
                        }

                        $conn->query("UPDATE users SET profile_pic = '$target_file' WHERE id = $user_id");
                        $_SESSION['profile_pic'] = $target_file;
                        $success .= " Profile picture updated.";
                    }
                } else {
                    $error = "Uploaded file is not a valid image.";
                }
            }
        }
    }

    // Handle Password Update
    if (isset($_POST['update_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        if (!empty($new_password)) {
            if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $pwd_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $pwd_stmt->bind_param("si", $hashed, $user_id);
                if ($pwd_stmt->execute()) {
                    $success = "Password changed successfully.";
                } else {
                    $error = "Error updating password.";
                }
            } else {
                $error = "Passwords do not match or are too short (min 6 chars).";
            }
        }
    }
}

// Fetch Current Data
$stmt = $conn->prepare("SELECT username, bio, email, wallet_balance, role, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Fetch Stats
$asset_count_res = $conn->query("SELECT COUNT(*) FROM assets WHERE creator_id = $user_id");
$asset_count = $asset_count_res->fetch_row()[0];

$download_count_res = $conn->query("SELECT SUM(download_count) FROM assets WHERE creator_id = $user_id");
$download_count = $download_count_res->fetch_row()[0] ?? 0;

$sales_count_res = $conn->query("SELECT COUNT(*) FROM transactions WHERE type='purchase' AND related_asset_id IN (SELECT id FROM assets WHERE creator_id = $user_id)");
$sales_count = $sales_count_res->fetch_row()[0];

// Chart Data: User's weekly earnings
$chart_labels = [];
$chart_revenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $display_date = date('D', strtotime("-$i days"));
    $chart_labels[] = $display_date;
    
    $daily_income_query = $conn->prepare("SELECT SUM(amount) as s FROM transactions WHERE type='purchase' AND DATE(created_at) = ? AND related_asset_id IN (SELECT id FROM assets WHERE creator_id = ?)");
    $daily_income_query->bind_param("si", $date, $user_id);
    $daily_income_query->execute();
    $daily_income = $daily_income_query->get_result()->fetch_assoc()['s'] ?? 0;
    $chart_revenue[] = (float)$daily_income;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user['role'] === 'admin' ? 'Admin Profile' : 'User Dashboard'; ?> - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php if ($user['role'] === 'admin'): ?>
        <?php include 'includes/admin_styles.php'; ?>
    <?php endif; ?>
    <style>
        :root {
            --card-glass: rgba(255, 255, 255, 0.03);
            --border-glass: rgba(255, 255, 255, 0.08);
        }
        .profile-grid { display: grid; grid-template-columns: 320px 1fr; gap: 30px; margin-top: 20px; }
        .stat-card { background: var(--card-glass); border: 1px solid var(--border-glass); border-radius: 20px; padding: 22px; display: flex; align-items: center; gap: 15px; backdrop-filter: blur(10px); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.05); }
        .stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: rgba(138, 43, 226, 0.15); color: #8a2be2; }
        .stat-value { font-size: 24px; font-weight: 800; color: white; line-height: 1; margin-bottom: 4px; }
        .stat-label { font-size: 11px; color: #777; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; }
        
        .profile-sidebar { background: var(--card-bg); border-radius: 28px; padding: 35px; border: 1px solid var(--border-glass); text-align: center; height: fit-content; position: sticky; top: 20px; }
        .profile-main { display: flex; flex-direction: column; gap: 30px; }
        .form-card { background: var(--card-bg); border-radius: 28px; padding: 35px; border: 1px solid var(--border-glass); box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        
        /* Admin Overrides */
        <?php if ($user['role'] === 'admin'): ?>
        .profile-sidebar, .form-card { background: #16161a; border-radius: 24px; }
        .profile-grid { margin-top: 0; }
        <?php endif; ?>

        .pic-container { position: relative; width: 130px; height: 130px; margin: 0 auto 25px; }
        .pic-wrapper { width: 130px; height: 130px; background: #222; border-radius: 50%; overflow: hidden; border: 4px solid #8a2be2; box-shadow: 0 0 20px rgba(138,43,226,0.3); }
        .pic-edit-btn { position: absolute; bottom: 5px; right: 5px; background: #8a2be2; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; border: 3px solid #1a1a1d; transition: all 0.3s; z-index: 5; }
        .pic-edit-btn:hover { background: #9d4ced; transform: scale(1.1) rotate(15deg); }
        #profile-pic-input { display: none; }
        
        .dashboard-nav { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid var(--border-glass); padding-bottom: 15px; }
        .nav-tab { padding: 10px 20px; border-radius: 10px; color: #777; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .nav-tab.active { color: white; background: rgba(255,255,255,0.05); }
        .nav-tab:hover:not(.active) { color: white; }

        @media (max-width: 1000px) { .profile-grid { grid-template-columns: 1fr; } .profile-sidebar { position: static; } }
    </style>
</head>
<body>
    <?php if ($user['role'] === 'admin'): ?>
        <?php include 'includes/admin_sidebar.php'; ?>
        <main class="main-content">
            <div class="admin-container">
    <?php else: ?>
        <div class="studio-container">
    <?php endif; ?>
        
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
                    <a href="notifications.php" style="font-size: 24px;"><ion-icon name="notifications-outline"></ion-icon></a>
                </div>
            </div>
        </header>

        <?php if ($user['role'] !== 'admin'): ?>
        <a href="welcome.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Dashboard
        </a>
        <?php endif; ?>

        <div class="section-header" style="margin-bottom: 30px;">
            <h3 class="section-title" style="font-size: 24px;"><?php echo $user['role'] === 'admin' ? 'Profile Settings' : 'My Account'; ?></h3>
            <?php if ($user['role'] !== 'admin'): ?>
            <a href="logout.php" style="color:#ff4444; text-decoration:none; font-size: 13px; font-weight: 700; display:flex; align-items: center; gap: 5px;">
                <ion-icon name="log-out-outline" style="font-size: 18px;"></ion-icon> Sign Out
            </a>
            <?php endif; ?>
        </div>

        <div class="stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card">
                <div class="stat-icon"><ion-icon name="cube-outline"></ion-icon></div>
                <div>
                    <div class="stat-value"><?php echo $asset_count; ?></div>
                    <div class="stat-label">My Assets</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#38ef7d; background:rgba(56,239,125,0.1);"><ion-icon name="cloud-download-outline"></ion-icon></div>
                <div>
                    <div class="stat-value"><?php echo number_format($download_count); ?></div>
                    <div class="stat-label">Total Downloads</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#ffbf00; background:rgba(255,191,0,0.1);"><ion-icon name="cash-outline"></ion-icon></div>
                <div>
                    <div class="stat-value">₹<?php echo number_format($user['wallet_balance'], 2); ?></div>
                    <div class="stat-label">Net Balance</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color:#00e5ff; background:rgba(0,229,255,0.1);"><ion-icon name="ribbon-outline"></ion-icon></div>
                <div>
                    <div class="stat-value"><?php echo ucfirst($user['role']); ?></div>
                    <div class="stat-label">Account Rank</div>
                </div>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <form action="" method="POST" enctype="multipart/form-data" id="profile-pic-form">
                    <div class="pic-container">
                        <div class="pic-wrapper" style="display:flex; align-items:center; justify-content:center; font-size:40px; color:rgba(255,255,255,0.2);">
                            <?php if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" style="width:100%; height:100%; object-fit:cover;" id="pic-preview">
                            <?php else: ?>
                                <ion-icon name="person-outline"></ion-icon>
                            <?php endif; ?>
                        </div>
                        <label for="profile-pic-input" class="pic-edit-btn">
                            <ion-icon name="camera-outline"></ion-icon>
                        </label>
                        <input type="file" name="profile_pic" id="profile-pic-input" accept="image/*" onchange="document.getElementById('profile-pic-form').submit()">
                    </div>
                </form>
                
                <h3 style="margin:0; font-size: 22px;"><?php echo htmlspecialchars($user['username']); ?></h3>
                <p style="color:#555; font-size:14px; margin:5px 0 25px;"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <a href="browse.php?creator=<?php echo urlencode($user['username']); ?>" class="action-btn primary" style="width:100%; padding:14px;">
                        <ion-icon name="shop-outline"></ion-icon> View My Store
                    </a>
                    <a href="manage_uploads.php" class="action-btn" style="width:100%; padding:14px; border-radius: 12px;">
                        <ion-icon name="settings-outline"></ion-icon> Manage Content
                    </a>
                </div>

                <div style="margin-top:40px; padding-top: 25px; border-top: 1px solid var(--border-glass); text-align:left;">
                    <h4 style="font-size:11px; color:#444; text-transform:uppercase; letter-spacing: 1px; margin-bottom:15px;">Danger Zone</h4>
                    <a href="delete_account.php" style="color:#ff4444; font-size:13px; text-decoration:none; display:flex; align-items:center; gap:8px; opacity: 0.7; transition: 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">
                        <ion-icon name="trash-outline"></ion-icon> Delete My Account
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-main">
                <?php if ($success): ?>
                    <div class="success-message" style="margin:0; border-radius: 16px;"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="error-message" style="margin:0; border-radius: 16px;"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Dashboard Statistics & Analytics -->
                <div class="form-card" style="margin-bottom: 30px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <div style="display: flex; flex-direction: column;">
                            <h4 style="margin:0; font-size: 14px; color: #aaa;">Weekly Revenue</h4>
                            <div style="font-size: 24px; font-weight: 700; color: #38ef7d; margin-top: 5px;">₹<?php echo number_format($chart_revenue[6], 2); ?></div>
                        </div>
                        <ion-icon name="trending-up-outline" style="font-size: 24px; color: #38ef7d;"></ion-icon>
                    </div>
                    <div style="height: 180px; width: 100%;">
                        <canvas id="profileRevenueChart"></canvas>
                    </div>
                </div>

                <!-- Public Info -->
                <div class="form-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                        <h3 style="margin:0;">Personal Details</h3>
                        <ion-icon name="person-circle-outline" style="font-size: 24px; color: #8a2be2;"></ion-icon>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label>Display Name</label>
                            <div class="input-wrapper">
                                <ion-icon name="person-outline" class="input-icon"></ion-icon>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>About You</label>
                            <textarea name="bio" rows="4" style="width:100%; background:rgba(0,0,0,0.2); border:1px solid var(--border-glass); color:white; padding:15px; border-radius:14px; font-family:inherit; resize:vertical; outline: none;" placeholder="Tell the world a little about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top:10px; width:auto; padding:14px 40px; border-radius: 14px;">Save Changes</button>
                    </form>
                </div>

                <!-- Security -->
                <div class="form-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                        <h3 style="margin:0;">Security & Password</h3>
                        <ion-icon name="shield-lock-outline" style="font-size: 24px; color: #38ef7d;"></ion-icon>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="update_password" value="1">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:25px;">
                            <div class="form-group" style="margin:0;">
                                <label>New Password</label>
                                <div class="input-wrapper">
                                    <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                                    <input type="password" name="new_password" placeholder="Min. 6 characters">
                                </div>
                            </div>

                            <div class="form-group" style="margin:0;">
                                <label>Confirm Password</label>
                                <div class="input-wrapper">
                                    <ion-icon name="lock-closed-outline" class="input-icon"></ion-icon>
                                    <input type="password" name="confirm_password" placeholder="Repeat password">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top:30px; width:auto; padding:14px 40px; border-radius: 14px; background: #333; box-shadow: none;">Update Security</button>
                    </form>
                </div>
            </div>
        </div>

    <?php if ($user['role'] === 'admin'): ?>
            </div>
        </main>
    <?php else: ?>
        </div>
    <?php endif; ?>

    <!-- Bottom Navigation -->
    <?php if ($user['role'] !== 'admin'): ?>
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
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('profileRevenueChart').getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 150);
            gradient.addColorStop(0, 'rgba(56, 239, 125, 0.2)');
            gradient.addColorStop(1, 'rgba(56, 239, 125, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        label: 'Revenue',
                        data: <?php echo json_encode($chart_revenue); ?>,
                        borderColor: '#38ef7d',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            display: true, 
                            min: 50,
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#444', font: { size: 9 }, callback: value => '₹' + value }
                        },
                        x: { 
                            display: true, 
                            grid: { display: false }, 
                            ticks: { color: '#444', font: { size: 9 } } 
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
