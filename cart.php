<?php
require_once 'config.php';

$cart_items = $_SESSION['cart'] ?? [];
$assets = [];
$total = 0;

if (!empty($cart_items)) {
    $ids = implode(',', array_map('intval', $cart_items));
    $sql = "SELECT a.*, u.username as creator_name 
            FROM assets a 
            JOIN users u ON a.creator_id = u.id 
            WHERE a.id IN ($ids)";
    $result = $conn->query($sql);
    if ($result) {
        $assets = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($assets as $asset) {
            $total += $asset['price'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 40px;
            margin-top: 20px;
        }

        @media (max-width: 1024px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }
        }

        .cart-item-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border-radius: 28px;
            padding: 24px;
            display: flex;
            gap: 25px;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .cart-item-card:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateY(-5px);
            border-color: rgba(138, 43, 226, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .cart-item-thumb {
            width: 160px;
            height: 100px;
            border-radius: 18px;
            overflow: hidden;
            flex-shrink: 0;
            background: #000;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cart-item-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .cart-item-card:hover .cart-item-thumb img {
            transform: scale(1.1);
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-info h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
        }

        .cart-item-info .creator {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 6px;
            display: block;
        }

        .cart-item-price-area {
            text-align: right;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100px;
            padding-bottom: 5px;
        }

        .cart-item-price {
            font-size: 22px;
            font-weight: 800;
            color: white;
            letter-spacing: -1px;
        }

        .remove-btn {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.2);
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
            width: fit-content;
            margin-left: auto;
        }

        .remove-btn:hover {
            background: #ff4444;
            color: white;
            transform: scale(1.05);
        }

        .summary-card {
            background: rgba(20, 20, 25, 0.8);
            backdrop-filter: blur(30px);
            padding: 35px;
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-card h3 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 25px;
            color: white;
            letter-spacing: -0.5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            color: var(--text-secondary);
            font-size: 15px;
        }

        .summary-total {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 24px;
            font-weight: 900;
            color: white;
            display: flex;
            justify-content: space-between;
            letter-spacing: -1px;
        }

        .checkout-btn {
            margin-top: 35px;
            width: 100%;
            padding: 20px;
            border-radius: 18px;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 25px rgba(138, 43, 226, 0.4);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .checkout-btn:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 15px 35px rgba(138, 43, 226, 0.6);
        }

        .empty-cart-container {
            text-align: center;
            padding: 120px 40px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 40px;
            border: 2px dashed rgba(255, 255, 255, 0.05);
            margin: 40px 0;
        }

        .empty-cart-icon {
            font-size: 80px;
            color: #222;
            margin-bottom: 25px;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5));
        }

        .empty-cart-container h3 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 15px;
            color: white;
        }

        .empty-cart-container p {
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 40px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    <div class="studio-container">
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

        <a href="browse.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Store
        </a>

        <div class="section-header" style="margin-bottom: 30px; align-items: flex-end;">
            <div>
                <h3 class="section-title" style="font-size: 32px; margin-bottom: 5px;">Your Shopping Cart</h3>
                <p style="color: var(--text-secondary); font-size: 14px;">Review your items before proceeding to checkout.</p>
            </div>
            <span style="background: rgba(138, 43, 226, 0.1); color: var(--accent-color); padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; border: 1px solid rgba(138, 43, 226, 0.2);"><?php echo count($assets); ?> ITEMS</span>
        </div>

        <?php if (!empty($assets)): ?>
            <div class="cart-grid">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($assets as $asset): ?>
                        <div class="cart-item-card">
                            <div class="cart-item-thumb">
                                <img src="<?php echo htmlspecialchars($asset['thumbnail_path'] ?: $asset['preview_path']); ?>" alt="<?php echo htmlspecialchars($asset['title']); ?>">
                            </div>
                            <div class="cart-item-info">
                                <h4><?php echo htmlspecialchars($asset['title']); ?></h4>
                                <span class="creator">by @<?php echo htmlspecialchars($asset['creator_name']); ?></span>
                                <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px; color: #555; font-size: 12px; font-weight: 600;">
                                    <span style="display: flex; align-items: center; gap: 4px;"><ion-icon name="shield-checkmark"></ion-icon> Quality Verified</span>
                                    <span style="display: flex; align-items: center; gap: 4px;"><ion-icon name="infinite"></ion-icon> Lifetime Access</span>
                                </div>
                            </div>
                            <div class="cart-item-price-area">
                                <div class="cart-item-price">₹<?php echo number_format($asset['price'], 2); ?></div>
                                <button class="remove-btn" onclick="removeFromCart(<?php echo $asset['id']; ?>)">
                                    <ion-icon name="trash-outline"></ion-icon> Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-card">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal (<?php echo count($assets); ?> items)</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Processing Fee</span>
                        <span style="color: #38ef7d; font-weight: 700;">₹0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Platform Discount</span>
                        <span style="color: #38ef7d;">- ₹0.00</span>
                    </div>
                    <div class="summary-total">
                        <span>Total Amount</span>
                        <span style="color: var(--accent-color);">₹<?php echo number_format($total, 2); ?></span>
                    </div>

                    <?php
                    $key_id = get_setting('razorpay_key_id');
                    $key_secret = get_setting('razorpay_key_secret');

                    if (!empty($key_id) && !empty($key_secret)):
                        // Generate Order ID for Cart Total
                        $razorpay_order_id = null;
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'amount' => $total * 100, // in paise
                            'currency' => 'INR',
                            'receipt' => 'rcpt_cart_' . time()
                        ]));
                        curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                        $result = curl_exec($ch);
                        curl_close($ch);
                        $order = json_decode($result);
                        $razorpay_order_id = $order->id ?? null;
                        ?>
                        <form id="cartPaymentForm" action="purchase_checkout.php" method="POST">
                            <input type="hidden" name="payment_method" value="razorpay">
                            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id"
                                value="<?php echo htmlspecialchars($razorpay_order_id); ?>">
                            <input type="hidden" name="razorpay_signature" id="razorpay_signature">

                            <button type="button" id="payCartButton" class="btn-primary checkout-btn">
                                <ion-icon name="wallet-outline" style="font-size: 20px;"></ion-icon> Checkout (Razorpay)
                            </button>
                        </form>
                        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
                        <script>
                            var options = {
                                "key": "<?php echo htmlspecialchars($key_id); ?>",
                                "amount": "<?php echo $total * 100; ?>",
                                "currency": "INR",
                                "name": "RenderShelf Cart",
                                "description": "Purchase Cart Items",
                                "order_id": "<?php echo htmlspecialchars($razorpay_order_id); ?>",
                                "handler": function (response) {
                                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                                    document.getElementById('razorpay_signature').value = response.razorpay_signature;
                                    document.getElementById('cartPaymentForm').submit();
                                },
                                "prefill": {
                                    "name": "<?php echo addslashes($_SESSION['username'] ?? 'User'); ?>",
                                },
                                "theme": {
                                    "color": "#8a2be2"
                                }
                            };
                            var rzp1 = new Razorpay(options);
                            rzp1.on('payment.failed', function (response) {
                                alert("Payment Failed: " + response.error.description);
                            });
                            document.getElementById('payCartButton').onclick = function (e) {
                                rzp1.open();
                                e.preventDefault();
                            }
                        </script>
                    <?php else: ?>
                        <a href="purchase_checkout.php" class="btn-primary checkout-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <ion-icon name="flash-outline" style="font-size: 20px;"></ion-icon> Checkout (Wallet)
                        </a>
                    <?php endif; ?>
                    
                    <div style="margin-top: 25px; display: flex; align-items: center; justify-content: center; gap: 10px; color: #555; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                        <ion-icon name="shield-checkmark-outline" style="font-size: 16px; color: #38ef7d;"></ion-icon>
                        Secure 128-bit SSL Encrypted
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart-container">
                <ion-icon name="cart-outline" class="empty-cart-icon"></ion-icon>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added anything to your cart yet. Explore our marketplace for amazing assets!</p>
                <a href="browse.php" class="btn-primary" style="width: auto; display: inline-flex; padding: 18px 45px; border-radius: 18px; text-decoration: none; align-items: center; gap: 10px;">
                    <ion-icon name="bag-handle-outline" style="font-size: 20px;"></ion-icon> Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function removeFromCart(assetId) {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('asset_id', assetId);

            fetch('api/cart_actions.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>

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
        <a href="library.php" class="nav-item">
            <ion-icon name="play-circle-outline"></ion-icon>
            Library
        </a>
        <a href="cart.php" class="nav-item active">
            <ion-icon name="cart"></ion-icon>
            Cart
        </a>
        <a href="profile.php" class="nav-item">
            <ion-icon name="person-outline"></ion-icon>
            Profile
        </a>
    </nav>
</body>

</html>