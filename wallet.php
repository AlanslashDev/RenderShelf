<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = $_GET['msg'] ?? '';

// Handle Deposit (Mock)
if (isset($_POST['deposit'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $conn->query("UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id=$user_id");
        $conn->query("INSERT INTO transactions (user_id, amount, type, description) VALUES ($user_id, $amount, 'deposit', 'Added funds')");
        // Update session
        $_SESSION['wallet_balance'] += $amount;
        header("Location: wallet.php?msg=Deposit Successful");
        exit;
    }
}

// Handle Withdrawal Request
if (isset($_POST['withdraw'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0 && $amount <= $_SESSION['wallet_balance']) {
        $conn->query("UPDATE users SET wallet_balance = wallet_balance - $amount WHERE id=$user_id");
        $conn->query("INSERT INTO withdrawal_requests (user_id, amount, status) VALUES ($user_id, $amount, 'pending')");
        $conn->query("INSERT INTO transactions (user_id, amount, type, description) VALUES ($user_id, -$amount, 'withdrawal', 'Withdrawal Request')");
        
        $_SESSION['wallet_balance'] -= $amount;
        header("Location: wallet.php?msg=Withdrawal Requested");
        exit;
    } else {
        $msg = "Insufficient funds";
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
            <h2>My Wallet</h2>
        </header>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:40px; margin-top:40px;">
            <!-- Left: Balance & Actions -->
            <div>
                <div class="featured-card" style="height:auto; min-height:200px; display:flex; flex-direction:column; justify-content:center; align-items:flex-start;">
                    <div class="featured-title" style="font-size:16px; margin-bottom:10px;">Total Balance</div>
                    <div style="font-size:48px; font-weight:bold; color:#8a2be2;">$<?php echo number_format($_SESSION['wallet_balance'], 2); ?></div>
                    <div style="margin-top:20px; display:flex; gap:10px; width:100%;">
                        <button onclick="document.getElementById('deposit-modal').style.display='block'" class="btn-primary" style="flex:1;">Add Funds</button>
                        <button onclick="document.getElementById('withdraw-modal').style.display='block'" class="btn-primary" style="flex:1; background:#444;">Withdraw</button>
                    </div>
                </div>
                <?php if ($msg): ?>
                    <div class="success-message" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
            </div>

            <!-- Right: History -->
            <div>
                <h3>Recent Transactions</h3>
                <div style="background:#1e1e1e; border-radius:12px; padding:20px; max-height:400px; overflow-y:auto;">
                    <?php while($row = $transactions->fetch_assoc()): ?>
                        <div style="display:flex; justify-content:space-between; margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid #333;">
                            <div>
                                <div style="font-weight:bold; text-transform:capitalize;"><?php echo $row['type']; ?></div>
                                <div style="font-size:12px; color:#aaa;"><?php echo $row['created_at']; ?></div>
                                <div style="font-size:12px; color:#aaa;"><?php echo htmlspecialchars($row['description']); ?></div>
                            </div>
                            <div style="font-weight:bold; color: <?php echo $row['amount'] > 0 ? '#38ef7d' : '#ff4444'; ?>;">
                                <?php echo $row['amount'] > 0 ? '+' : ''; ?><?php echo number_format($row['amount'], 2); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Deposit Modal -->
    <div id="deposit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:100;">
        <div style="background:#1e1e1e; width:300px; margin:100px auto; padding:30px; border-radius:12px; text-align:center;">
            <h3>Add Funds</h3>
            <form method="POST">
                <input type="number" name="amount" placeholder="Amount" required style="width:100%; padding:10px; margin:20px 0; border:none; border-radius:5px;">
                <button type="submit" name="deposit" class="btn-primary">Confirm</button>
                <button type="button" onclick="document.getElementById('deposit-modal').style.display='none'" style="background:none; border:none; color:#aaa; margin-top:10px; cursor:pointer;">Cancel</button>
            </form>
        </div>
    </div>

     <!-- Withdraw Modal -->
    <div id="withdraw-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:100;">
        <div style="background:#1e1e1e; width:300px; margin:100px auto; padding:30px; border-radius:12px; text-align:center;">
            <h3>Withdraw Funds</h3>
            <form method="POST">
                <input type="number" name="amount" placeholder="Amount" required style="width:100%; padding:10px; margin:20px 0; border:none; border-radius:5px;">
                <button type="submit" name="withdraw" class="btn-primary">Confirm</button>
                <button type="button" onclick="document.getElementById('withdraw-modal').style.display='none'" style="background:none; border:none; color:#aaa; margin-top:10px; cursor:pointer;">Cancel</button>
            </form>
        </div>
    </div>
</body>
</html>
