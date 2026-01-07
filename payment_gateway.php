<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['asset_id'])) {
    header("Location: browse.php");
    exit;
}

$asset_id = intval($_GET['asset_id']);

// Fetch Asset Info
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    die("Asset not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="dashboard-container">
        
        <header class="dash-header">
            <div class="logo-area" style="margin:0;">
                <a href="asset_details.php?id=<?php echo $asset_id; ?>" style="color:white; text-decoration:none;"><h3><ion-icon name="arrow-back-outline"></ion-icon> Cancel</h3></a>
            </div>
            <h2>Secure Checkout</h2>
        </header>

        <div style="max-width:500px; margin:40px auto; display:flex; gap:20px; flex-direction:column;">
            
            <!-- Order Summary -->
            <div style="background:#1e1e1e; padding:20px; border-radius:10px;">
                <h3 style="margin-bottom:15px; color:#ddd;">Order Summary</h3>
                <div style="display:flex; gap:15px; align-items:center;">
                    <div style="width:60px; height:60px; background:#333; border-radius:8px; overflow:hidden;">
                        <?php if ($asset['thumbnail_path']): ?>
                            <img src="<?php echo htmlspecialchars($asset['thumbnail_path']); ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background:linear-gradient(45deg, #444, #222);"></div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($asset['title']); ?></div>
                        <div style="font-size:12px; color:#aaa; margin-top:5px;">Standard License</div>
                    </div>
                    <div style="font-size:18px; font-weight:bold; color:#38ef7d;">
                        $<?php echo number_format($asset['price'], 2); ?>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div style="background:#1e1e1e; padding:20px; border-radius:10px;">
                <h3 style="margin-bottom:20px; color:#ddd;">Payment Details</h3>
                
                <form action="purchase.php" method="POST">
                    <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                    
                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" placeholder="John Doe" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white;">
                    </div>

                    <div class="form-group">
                        <label>Card Number</label>
                        <div style="position:relative;">
                            <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white; letter-spacing:1px;">
                            <ion-icon name="card-outline" style="position:absolute; right:12px; top:12px; color:#aaa;"></ion-icon>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" placeholder="MM/YY" maxlength="5" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white;">
                        </div>
                        <div class="form-group">
                            <label>CVC</label>
                            <input type="text" placeholder="123" maxlength="3" required style="width:100%; padding:10px; background:#2a2a2a; border:1px solid #333; border-radius:5px; color:white;">
                        </div>
                    </div>

                    <label style="display:flex; align-items:center; gap:10px; font-size:12px; color:#aaa; margin-top:10px;">
                        <input type="checkbox" checked required>
                        I agree to the Terms of Service and Refund Policy
                    </label>

                    <button type="submit" class="btn-primary" style="margin-top:20px; width:100%; font-size:16px;">
                        Pay $<?php echo number_format($asset['price'], 2); ?>
                    </button>

                    <div style="text-align:center; margin-top:15px; font-size:12px; color:#555;">
                        <ion-icon name="lock-closed"></ion-icon> Secure Encrypted Transaction (Mock)
                    </div>
                </form>
            </div>

        </div>
    </div>
</body>
</html>
