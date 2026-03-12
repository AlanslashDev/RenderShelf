<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$msg = '';
$error = '';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
    }
    $msg = "Settings updated successfully.";
    unset($_SESSION['platform_settings']); // Clear cache for refresh

    // Refresh local settings for display
    // (They will be re-fetched below)
}

// Fetch all settings
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - RenderShelf Admin</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .settings-box {
            background: #111;
            border: 1px solid #222;
            padding: 25px;
            border-radius: 20px;
        }

        .settings-box h3 {
            margin-bottom: 20px;
            font-size: 16px;
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .setting-group {
            margin-bottom: 20px;
        }

        .setting-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #aaa;
        }

        .setting-group input,
        .setting-group select {
            width: 100%;
            padding: 12px;
            background: #0a0a0a;
            border: 1px solid #333;
            color: white;
            border-radius: 10px;
            font-size: 14px;
        }

        .setting-group input:focus {
            border-color: var(--accent-color);
        }

        .setting-desc {
            font-size: 11px;
            color: #555;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
            <header class="admin-header">
                <div class="header-title">
                    <h1>Platform Settings</h1>
                    <p>Manage global configuration and platform behavior.</p>
                </div>
            </header>

            <?php if ($msg): ?>
                <div class="success-message" style="margin-bottom:20px;"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="settings-grid">
                    <!-- General Settings -->
                    <div class="settings-box">
                        <h3><ion-icon name="globe-outline"></ion-icon> General</h3>

                        <div class="setting-group">
                            <label>Site Name</label>
                            <input type="text" name="settings[site_name]"
                                value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                        </div>

                        <div class="setting-group">
                            <label>Support Email</label>
                            <input type="email" name="settings[support_email]"
                                value="<?php echo htmlspecialchars($settings['support_email'] ?? ''); ?>">
                        </div>

                        <div class="setting-group">
                            <label>Currency Symbol</label>
                            <input type="text" name="settings[currency_symbol]"
                                value="<?php echo htmlspecialchars($settings['currency_symbol'] ?? '$'); ?>">
                        </div>
                    </div>

                    <!-- Financial & Limits -->
                    <div class="settings-box">
                        <h3><ion-icon name="card-outline"></ion-icon> Financial & Limits</h3>

                        <div class="setting-group">
                            <label>Platform Fee (%)</label>
                            <input type="number" name="settings[platform_fee]"
                                value="<?php echo htmlspecialchars($settings['platform_fee'] ?? '10'); ?>">
                            <div class="setting-desc">Percentage taken from each sale.</div>
                        </div>

                        <div class="setting-group">
                            <label>Payment Gateway Test Mode</label>
                            <select name="settings[payment_test_mode]">
                                <option value="1" <?php echo (($settings['payment_test_mode'] ?? '1') == '1') ? 'selected' : ''; ?>>Enabled (Mock Cards Allowed)</option>
                                <option value="0" <?php echo (($settings['payment_test_mode'] ?? '1') == '0') ? 'selected' : ''; ?>>Disabled (Strict Real)</option>
                            </select>
                            <div class="setting-desc">Allows using a test card to mock purchases. If disabled, fake
                                cards will be rejected.</div>
                        </div>

                        <div class="setting-group">
                            <label>Max Upload Size (MB)</label>
                            <input type="number" name="settings[max_upload_size]"
                                value="<?php echo htmlspecialchars($settings['max_upload_size'] ?? '50'); ?>">
                        </div>
                    </div>

                    <!-- Payment Gateways -->
                    <div class="settings-box">
                        <h3><ion-icon name="wallet-outline"></ion-icon> Payment Gateways</h3>

                        <div class="setting-group">
                            <label>Razorpay Key ID</label>
                            <input type="text" name="settings[razorpay_key_id]"
                                value="<?php echo htmlspecialchars($settings['razorpay_key_id'] ?? ''); ?>"
                                placeholder="rzp_test_XXXXXX">
                        </div>

                        <div class="setting-group">
                            <label>Razorpay Key Secret</label>
                            <input type="password" name="settings[razorpay_key_secret]"
                                value="<?php echo htmlspecialchars($settings['razorpay_key_secret'] ?? ''); ?>"
                                placeholder="Secret Key">
                        </div>
                    </div>

                    <!-- Platform Behavior -->
                    <div class="settings-box">
                        <h3><ion-icon name="options-outline"></ion-icon> Behavior</h3>

                        <div class="setting-group">
                            <label>Maintenance Mode</label>
                            <select name="settings[maintenance_mode]">
                                <option value="0" <?php echo ($settings['maintenance_mode'] == '0') ? 'selected' : ''; ?>>
                                    Disabled (Live)</option>
                                <option value="1" <?php echo ($settings['maintenance_mode'] == '1') ? 'selected' : ''; ?>>
                                    Enabled</option>
                            </select>
                        </div>

                        <div class="setting-group">
                            <label>Allow New Registrations</label>
                            <select name="settings[allow_registration]">
                                <option value="1" <?php echo ($settings['allow_registration'] == '1') ? 'selected' : ''; ?>>Yes</option>
                                <option value="0" <?php echo ($settings['allow_registration'] == '0') ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>

                        <div class="setting-group">
                            <label>Auto-Approve Assets</label>
                            <select name="settings[auto_approve_assets]">
                                <option value="0" <?php echo ($settings['auto_approve_assets'] == '0') ? 'selected' : ''; ?>>No (Manual Review)</option>
                                <option value="1" <?php echo ($settings['auto_approve_assets'] == '1') ? 'selected' : ''; ?>>Yes (Instant Live)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-primary"
                        style="width:auto; padding:15px 40px; border-radius:15px;">Save All Changes</button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>