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

$key_id = get_setting('razorpay_key_id');
$key_secret = get_setting('razorpay_key_secret');

$razorpay_order_id = null;
if (!empty($key_id) && !empty($key_secret)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'amount' => $asset['price'] * 100, // in paise
        'currency' => 'INR',
        'receipt' => 'rcpt_' . $asset_id . '_' . time()
    ]));
    curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $result = curl_exec($ch);
    curl_close($ch);
    $order = json_decode($result);
    $razorpay_order_id = $order->id ?? null;
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

        <header class="dash-header"
            style="display:flex; align-items:center; justify-content:space-between; padding: 20px 40px; border-bottom: 1px solid rgba(255,255,255,0.05);">
            <div class="logo-area" style="margin:0;">
                <a href="asset_details.php?id=<?php echo $asset_id; ?>"
                    style="color:white; text-decoration:none; display:flex; align-items:center; gap:5px; font-weight:500;">
                    <ion-icon name="arrow-back-outline"></ion-icon>
                    <span>Cancel</span>
                </a>
            </div>
            <h2 style="font-size:1rem; font-weight:600; opacity:0.7; letter-spacing:0.5px; text-transform:uppercase;">
                Secure Checkout</h2>
        </header>

        <div style="max-width:500px; margin:40px auto; display:flex; gap:20px; flex-direction:column;">

            <!-- Test Mode Banner -->
            <?php if (get_setting('payment_test_mode', '1') == '1'): ?>
                <div
                    style="background: rgba(255, 191, 0, 0.1); border: 1px solid rgba(255, 191, 0, 0.2); padding: 15px; border-radius: 10px; display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <ion-icon name="flask-outline" style="font-size: 24px; color: #ffbf00;"></ion-icon>
                    <div style="flex: 1;">
                        <div style="font-weight: bold; color: #ffbf00; font-size: 14px;">Test Mode Enabled</div>
                        <div style="font-size: 11px; color: #aaa;">Use a test card to verify the purchase flow. No real
                            money will be charged.</div>
                    </div>
                    <button onclick="fillTestCard()" type="button"
                        style="background: #ffbf00; color: #000; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer; transition: 0.2s;"
                        onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                        Use Test Card
                    </button>
                </div>
            <?php endif; ?>

            <!-- Order Summary -->
            <div style="background:#1e1e1e; padding:20px; border-radius:10px;">
                <h3 style="margin-bottom:15px; color:#ddd;">Order Summary</h3>
                <div style="display:flex; gap:15px; align-items:center;">
                    <div style="width:60px; height:60px; background:#333; border-radius:8px; overflow:hidden;">
                        <?php if ($asset['thumbnail_path']): ?>
                            <img src="<?php echo htmlspecialchars($asset['thumbnail_path']); ?>"
                                style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background:linear-gradient(45deg, #444, #222);"></div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:bold;"><?php echo htmlspecialchars($asset['title']); ?></div>
                        <div style="font-size:12px; color:#aaa; margin-top:5px;">Standard License</div>
                    </div>
                    <div style="font-size:18px; font-weight:bold; color:#38ef7d;">
                        ₹<?php echo number_format($asset['price'], 2); ?>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div style="background:#1e1e1e; padding:20px; border-radius:10px;">
                <h3 style="margin-bottom:20px; color:#ddd;">Payment Details</h3>

                <?php if (empty($key_id) || empty($key_secret)): ?>
                    <div style="color:#ffbf00; font-size:14px; padding:15px; border:1px solid #ffbf00; border-radius:10px; background:rgba(255, 191, 0, 0.1);">
                        Razorpay keys are not configured. Please contact the administrator.
                    </div>
                <?php else: ?>
                    <form id="paymentForm" action="purchase.php" method="POST">
                        <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                        <input type="hidden" name="payment_method" value="razorpay">
                        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="<?php echo htmlspecialchars($razorpay_order_id); ?>">
                        <input type="hidden" name="razorpay_signature" id="razorpay_signature">

                        <button type="button" id="payButton" class="btn-primary" style="margin-top:20px; width:100%; font-size:16px;">
                            Pay ₹<?php echo number_format($asset['price'], 2); ?>
                        </button>
                        <div style="text-align:center; margin-top:15px; font-size:12px; color:#555;">
                            <ion-icon name="lock-closed"></ion-icon> Secure Payment via Razorpay
                        </div>
                    </form>

                    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                    <script>
                        var options = {
                            "key": "<?php echo htmlspecialchars($key_id); ?>",
                            "amount": "<?php echo $asset['price'] * 100; ?>", 
                            "currency": "INR",
                            "name": "RenderShelf",
                            "description": "Purchase - <?php echo addslashes($asset['title']); ?>",
                            "order_id": "<?php echo htmlspecialchars($razorpay_order_id); ?>", 
                            "handler": function (response){
                                document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                                document.getElementById('razorpay_signature').value = response.razorpay_signature;
                                document.getElementById('paymentForm').submit();
                            },
                            "prefill": {
                                "name": "<?php echo addslashes($_SESSION['username'] ?? 'User'); ?>",
                            },
                            "theme": {
                                "color": "#8a2be2"
                            }
                        };
                        var rzp1 = new Razorpay(options);
                        rzp1.on('payment.failed', function (response){
                                alert("Payment Failed: " + response.error.description);
                        });
                        document.getElementById('payButton').onclick = function(e){
                            rzp1.open();
                            e.preventDefault();
                        }
                    </script>
                <?php endif; ?>

            </div>

        </div>
    </div>
</body>

</html>