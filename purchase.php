<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['asset_id'])) {
    $user_id = $_SESSION['user_id'];
    $asset_id = intval($_POST['asset_id']);

    // Fetch Asset Price and Creator
    $stmt = $conn->prepare("SELECT price, creator_id, title FROM assets WHERE id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows == 0) die("Asset not found");
    $asset = $res->fetch_assoc();
    $stmt->close();

    $price = $asset['price'];
    $creator_id = $asset['creator_id'];
    $payment_method = $_POST['payment_method'] ?? 'wallet';

    // Validation for card payment
    if ($payment_method === 'card') {
        if (empty($_POST['card_number']) || empty($_POST['expiry_date']) || empty($_POST['cvv']) || empty($_POST['cardholder_name'])) {
            die("Invalid payment details.");
        }
        // In a real app, you'd process the card here. Since it's a mock, we just proceed.
        $can_proceed = true;
    } else {
        // Check Wallet Balance
        $can_proceed = ($_SESSION['wallet_balance'] >= $price);
    }

    if ($can_proceed) {
        // Start Transaction
        $conn->begin_transaction();

        try {
            if ($payment_method === 'wallet') {
                // Deduct from User Wallet
                $conn->query("UPDATE users SET wallet_balance = wallet_balance - $price WHERE id=$user_id");
                $_SESSION['wallet_balance'] -= $price;
                $description = "Purchased (Wallet): {$asset['title']}";
            } else {
                $description = "Purchased (Card): {$asset['title']}";
                // For card payment, we don't deduct from wallet, but we record the purchase.
                // In some systems, you might want to record it as an external deposit + purchase.
            }
            
            // Add to Creator (with 10% commission deduction)
            $commission_rate = 0.10;
            $earnings = $price * (1 - $commission_rate);
            $conn->query("UPDATE users SET wallet_balance = wallet_balance + $earnings WHERE id=$creator_id");

            // Record Transactions
            $conn->query("INSERT INTO transactions (user_id, amount, type, description, related_asset_id) VALUES ($user_id, -$price, 'purchase', '$description', $asset_id)");
            $conn->query("INSERT INTO transactions (user_id, amount, type, description, related_asset_id) VALUES ($creator_id, $earnings, 'sale', 'sold: {$asset['title']}', $asset_id)");

            // Add download record/ownership logic
            $conn->query("UPDATE assets SET download_count = download_count + 1 WHERE id=$asset_id");

            $conn->commit();
            
            header("Location: asset_details.php?id=$asset_id&msg=Purchase Successful");
        } catch (Exception $e) {
            $conn->rollback();
            die("Transaction failed: " . $e->getMessage());
        }
    } else {
        header("Location: wallet.php?msg=Insufficient Funds");
    }
} else {
    header("Location: browse.php");
}
?>
