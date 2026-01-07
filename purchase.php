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

    // Check Balance
    if ($_SESSION['wallet_balance'] >= $price) {
        // Start Transaction
        $conn->begin_transaction();

        try {
            // Deduct from User
            $conn->query("UPDATE users SET wallet_balance = wallet_balance - $price WHERE id=$user_id");
            
            // Add to Creator (with 10% commission deduction)
            $commission_rate = 0.10;
            $earnings = $price * (1 - $commission_rate);
            $conn->query("UPDATE users SET wallet_balance = wallet_balance + $earnings WHERE id=$creator_id");

            // Record Transactions
            $conn->query("INSERT INTO transactions (user_id, amount, type, description, related_asset_id) VALUES ($user_id, -$price, 'purchase', 'Purchased: {$asset['title']}', $asset_id)");
            $conn->query("INSERT INTO transactions (user_id, amount, type, description, related_asset_id) VALUES ($creator_id, $earnings, 'sale', 'sold: {$asset['title']}', $asset_id)");

            // Add download record/ownership logic if explicit table (we check transactions in asset_details.php)
             $conn->query("UPDATE assets SET download_count = download_count + 1 WHERE id=$asset_id");

            $conn->commit();
            
            // Update session
            $_SESSION['wallet_balance'] -= $price;
            
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
