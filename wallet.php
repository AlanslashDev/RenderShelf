<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? '';

// Fetch latest balance
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $_SESSION['wallet_balance'] = $row['wallet_balance'];
}

// Deposit logic removed as per request

// Handle Withdrawal Request
if (isset($_POST['withdraw'])) {
    $amount = floatval($_POST['amount']);

    // Spam prevention: Check if there's already a pending request
    $check_pending = $conn->prepare("SELECT id FROM withdrawal_requests WHERE user_id = ? AND status = 'pending'");
    $check_pending->bind_param("i", $user_id);
    $check_pending->execute();
    $pending_res = $check_pending->get_result();

    if ($pending_res->num_rows > 0) {
        $msg = "You already have a withdrawal request pending. Please wait for it to be processed.";
    } elseif ($amount < 1) {
        $msg = "Minimum withdrawal amount is ₹1.00";
    } elseif ($amount > $_SESSION['wallet_balance']) {
        $msg = "Insufficient funds";
    } else {
        // Start Transaction for safety
        $conn->begin_transaction();
        try {
            // Update balance
            $stmt1 = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt1->bind_param("di", $amount, $user_id);
            $stmt1->execute();

            // Insert withdrawal request
            $stmt2 = $conn->prepare("INSERT INTO withdrawal_requests (user_id, amount, status) VALUES (?, ?, 'pending')");
            $stmt2->bind_param("id", $user_id, $amount);
            $stmt2->execute();

            // Log transaction
            $desc = "Withdrawal Request";
            $stmt3 = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'withdrawal', ?)");
            $neg_amount = -$amount;
            $stmt3->bind_param("ids", $user_id, $neg_amount, $desc);
            $stmt3->execute();

            $conn->commit();
            $_SESSION['wallet_balance'] -= $amount;
            header("Location: wallet.php?msg=Withdrawal Requested successfully");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error processing withdrawal. Please try again.";
        }
    }
}

$transactions = $conn->query("SELECT * FROM transactions WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
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
                <div class="avatar"
                    style="background:var(--accent-color); color:white; display:flex; justify-content:center; align-items:center; font-weight:bold; cursor:pointer; overflow:hidden; width:36px; height:36px;"
                    onclick="location.href='profile.php'">
                    <?php if (!empty($_SESSION['profile_pic']) && file_exists($_SESSION['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_pic']); ?>"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <ion-icon name="person"></ion-icon>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <a href="welcome.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Dashboard
        </a>

        <?php if ($msg): ?>
            <div class="<?php echo strpos(strtolower($msg), 'success') !== false || strpos(strtolower($msg), 'requested') !== false ? 'success-message' : 'error-message'; ?>"
                style="margin-bottom: 25px;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="wallet-grid" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 40px;">
            <!-- Left: Balance & Actions -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="revenue-card"
                    style="flex-direction: column; align-items: flex-start; gap: 20px; padding: 40px; margin-bottom: 0;">
                    <div class="revenue-content">
                        <span class="revenue-label" style="background: rgba(138, 43, 226, 0.2); color: #a45eff;">Current
                            Balance</span>
                        <h1 style="color: white; margin: 10px 0;">
                            ₹<?php echo number_format($_SESSION['wallet_balance'], 2); ?></h1>
                        <p style="color: #444; font-size: 13px;">Available funds in your RenderShelf wallet</p>
                    </div>
                    <button onclick="document.getElementById('withdraw-modal').classList.add('active')"
                        class="btn-primary" style="width: 100%; border-radius: 12px; font-size: 15px; margin: 0;">
                        <ion-icon name="arrow-down-circle" style="font-size: 20px;"></ion-icon> Withdraw Funds
                    </button>
                    <div
                        style="font-size: 11px; color: #333; text-align: center; width: 100%; display: flex; align-items: center; justify-content: center; gap: 5px;">
                        <ion-icon name="shield-checkmark-outline"></ion-icon> 256-bit Secure Wallet
                    </div>
                </div>
            </div>

            <!-- Right: History -->
            <div>
                <div class="section-header" style="margin-top: 0; margin-bottom: 20px;">
                    <h3 class="section-title">Transaction History</h3>
                </div>

                <div style="max-height: 550px; overflow-y: auto; padding-right: 15px;" class="custom-scrollbar">
                    <?php if ($transactions->num_rows > 0): ?>
                        <?php while ($row = $transactions->fetch_assoc()): ?>
                            <?php
                            $is_positive = $row['amount'] > 0;
                            $icon = '';
                            $class = '';
                            if ($row['type'] == 'deposit') {
                                $icon = 'arrow-up-outline';
                                $class = 'deposit';
                            } elseif ($row['type'] == 'withdrawal') {
                                $icon = 'arrow-down-outline';
                                $class = 'withdrawal';
                            } else {
                                $icon = 'cart-outline';
                                $class = 'purchase';
                            }
                            ?>
                            <div class="transaction-item"
                                style="background: var(--card-bg); padding: 16px; border-radius: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.03); transition: transform 0.2s;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='translateY(0)'">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div
                                        style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-size: 20px; color: <?php echo $is_positive ? '#38ef7d' : '#ff4444'; ?>;">
                                        <ion-icon name="<?php echo $icon; ?>"></ion-icon>
                                    </div>
                                    <div>
                                        <div style="font-size: 14px; font-weight: 700; color: white;">
                                            <?php echo htmlspecialchars($row['description'] ?: $row['type']); ?></div>
                                        <div style="font-size: 11px; color: #555; font-weight: 600; margin-top: 2px;">
                                            <?php echo date('M d, Y • h:i A', strtotime($row['created_at'])); ?></div>
                                    </div>
                                </div>
                                <div
                                    style="font-size: 15px; font-weight: 800; color: <?php echo $is_positive ? '#38ef7d' : 'white'; ?>;">
                                    <?php echo $is_positive ? '+' : ''; ?>₹<?php echo number_format($row['amount'], 2); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div
                            style="text-align: center; padding: 80px 20px; background: rgba(255,255,255,0.02); border-radius: 30px; border: 1px dashed rgba(255,255,255,0.1);">
                            <ion-icon name="receipt-outline"
                                style="font-size: 48px; margin-bottom: 20px; color: #222;"></ion-icon>
                            <p style="color: #444;">No transactions recorded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>



    <!-- Withdraw Modal -->
    <div id="withdraw-modal" class="modal-overlay">
        <div class="withdrawal-modal">
            <div class="modal-header">
                <h3>Withdraw Funds</h3>
                <p>Funds will be sent to your linked payout method.</p>
            </div>

            <form method="POST">
                <div class="amount-input-container">
                    <span class="currency-symbol">₹</span>
                    <?php $max_bal = floatval($_SESSION['wallet_balance'] ?? 0); ?>
                    <input type="number" id="withdraw-amount" name="amount" class="amount-input" placeholder="0.00"
                        required min="<?php echo $max_bal >= 1 ? '1' : '0'; ?>" max="<?php echo $max_bal; ?>"
                        step="0.01" <?php echo $max_bal < 1 ? 'disabled' : ''; ?>>
                    <div class="max-btn"
                        onclick="document.getElementById('withdraw-amount').value = '<?php echo $max_bal; ?>'">MAX</div>
                </div>

                <div
                    style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-secondary); margin-bottom:20px; padding:0 5px;">
                    <span>Available Balance</span>
                    <span>₹<?php echo number_format($max_bal, 2); ?></span>
                </div>

                <button type="submit" name="withdraw" class="withdraw-btn" <?php echo $max_bal < 1 ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>Request Withdrawal</button>
                <div onclick="document.getElementById('withdraw-modal').classList.remove('active')" class="cancel-link">
                    Cancel</div>
            </form>
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