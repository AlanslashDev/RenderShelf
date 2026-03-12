<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = $_SESSION['cart'] ?? [];

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

// 1. Calculate Total
$ids = implode(',', array_map('intval', $cart_items));
$sql = "SELECT SUM(price) as total FROM assets WHERE id IN ($ids)";
$total_result = $conn->query($sql);
$total = 0;
if ($total_result) {
    $row = $total_result->fetch_assoc();
    $total = $row['total'] ?? 0;
}

$payment_method = $_POST['payment_method'] ?? 'wallet';
$can_proceed = false;

if ($payment_method === 'razorpay') {
    if (empty($_POST['razorpay_payment_id']) || empty($_POST['razorpay_order_id']) || empty($_POST['razorpay_signature'])) {
        die("Invalid Razorpay payment details.");
    }
    $key_secret = get_setting('razorpay_key_secret');
    if (empty($key_secret)) {
        die("Razorpay configuration missing.");
    }
    $expected_signature = hash_hmac('sha256', $_POST['razorpay_order_id'] . '|' . $_POST['razorpay_payment_id'], $key_secret);
    if (hash_equals($expected_signature, $_POST['razorpay_signature'])) {
        $can_proceed = true;
    } else {
        die("Fraud detected: Payment signature verification failed.");
    }
} else {
    // 2. Check Wallet Balance
    $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_balance = $stmt->get_result()->fetch_assoc()['wallet_balance'] ?? 0;

    if ($user_balance < $total) {
        header("Location: cart.php?error=insufficient_balance");
        exit;
    }
    $can_proceed = true;
}

if (!$can_proceed) {
    die("Payment rejected.");
}

// 3. Process Transaction (Atomic operation ideally, but simple here)
$conn->begin_transaction();

try {
    // Deduct from wallet if paying via wallet
    if ($payment_method === 'wallet') {
        $new_balance = $user_balance - $total;
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();
        $_SESSION['wallet_balance'] = $new_balance; // Update session
    }

    // Create Transfer records and Notifications
    foreach ($cart_items as $asset_id) {
        $asset_id = intval($asset_id);

        // Fetch asset info (price and creator)
        $asset_stmt = $conn->prepare("SELECT creator_id, price, title FROM assets WHERE id = ?");
        $asset_stmt->bind_param("i", $asset_id);
        $asset_stmt->execute();
        $asset_info = $asset_stmt->get_result()->fetch_assoc();

        // Transaction record for buyer
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, related_asset_id) VALUES (?, 'purchase', ?, ?, ?)");
        $method_label = $payment_method === 'razorpay' ? 'Razorpay' : 'Wallet';
        $desc = "Purchased ($method_label): " . $asset_info['title'];
        $amt = $asset_info['price'];
        $stmt->bind_param("idsi", $user_id, $amt, $desc, $asset_id);
        $stmt->execute();

        // Calculate Platform Fee
        $platform_fee_percent = floatval(get_setting('platform_fee', '10'));
        $fee_amount = round($amt * ($platform_fee_percent / 100), 2);
        $creator_earnings = $amt - $fee_amount;

        // Transaction record for creator (Sale)
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, related_asset_id) VALUES (?, 'sale', ?, ?, ?)");
        $sale_desc = "Sold " . $asset_info['title'] . " (Fee: ₹" . number_format($fee_amount, 2) . ")";
        $stmt->bind_param("idsi", $asset_info['creator_id'], $creator_earnings, $sale_desc, $asset_id);
        $stmt->execute();

        // Update creator wallet with earnings only
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->bind_param("di", $creator_earnings, $asset_info['creator_id']);
        $stmt->execute();

        // Send notification to buyer
        $notif_msg = "Successfully purchased " . $asset_info['title'];
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
        $stmt->bind_param("is", $user_id, $notif_msg);
        $stmt->execute();

        // Send notification to creator
        $creator_msg = "New sale! Someone purchased " . $asset_info['title'];
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'info')");
        $stmt->bind_param("is", $asset_info['creator_id'], $creator_msg);
        $stmt->execute();
    }

    $conn->commit();
    $_SESSION['cart'] = []; // Clear cart
    header("Location: library.php?status=purchase_success");
} catch (Exception $e) {
    $conn->rollback();
    header("Location: cart.php?error=transaction_failed");
}
?>