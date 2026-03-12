<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Handle Withdrawal Actions
if (isset($_GET['approve_withdrawal'])) {
    $id = intval($_GET['approve_withdrawal']);
    $stmt = $conn->prepare("UPDATE withdrawal_requests SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "Withdrawal request #$id approved.";
    }
}

if (isset($_GET['reject_withdrawal'])) {
    $id = intval($_GET['reject_withdrawal']);
    
    // Fetch request details to refund
    $stmt = $conn->prepare("SELECT user_id, amount, status FROM withdrawal_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    
    if ($req && $req['status'] === 'pending') {
        $conn->begin_transaction();
        try {
            // 1. Mark as rejected
            $conn->query("UPDATE withdrawal_requests SET status = 'rejected' WHERE id = $id");
            
            // 2. Refund wallet
            $uid = $req['user_id'];
            $amt = $req['amount'];
            $conn->query("UPDATE users SET wallet_balance = wallet_balance + $amt WHERE id = $uid");
            
            // 3. Log refund transaction
            $desc = "Refund: Withdrawal Rejected";
            $conn->query("INSERT INTO transactions (user_id, amount, type, description) VALUES ($uid, $amt, 'refund', '$desc')");
            
            $conn->commit();
            $msg = "Withdrawal rejected and funds reserved.";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error rejecting withdrawal.";
        }
    }
}

$transactions = $conn->query("
    SELECT t.*, u.username, u.email 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    ORDER BY t.created_at DESC 
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - RenderShelf Admin</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
            <header class="admin-header">
                <div class="header-title">
                    <h1>Financials & Transactions</h1>
                    <p>Manage withdrawal requests and view transaction history.</p>
                </div>
            </header>

            <?php if ($msg): ?>
                <div class="success-message" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>

            <!-- Withdrawal Requests Section -->
            <div class="section-header" style="margin-top: 30px;">
                <h3 class="section-title">Pending Withdrawals</h3>
            </div>
            
            <div class="content-box" style="margin-bottom: 40px;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Requested</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pending_withdrawals = $conn->query("
                            SELECT w.*, u.username, u.email 
                            FROM withdrawal_requests w 
                            JOIN users u ON w.user_id = u.id 
                            WHERE w.status = 'pending' 
                            ORDER BY w.created_at ASC
                        ");
                        ?>
                        <?php if ($pending_withdrawals->num_rows > 0): ?>
                            <?php while($req = $pending_withdrawals->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:#666;">#<?php echo $req['id']; ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($req['username']); ?></div>
                                        <div style="font-size:11px; color:#aaa;"><?php echo htmlspecialchars($req['email']); ?></div>
                                    </td>
                                    <td style="font-weight:700; color:white;">₹<?php echo number_format($req['amount'], 2); ?></td>
                                    <td style="color:#aaa; font-size:12px;"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                    <td>
                                        <div style="display:flex; justify-content: flex-end; gap: 10px;">
                                            <a href="?approve_withdrawal=<?php echo $req['id']; ?>" class="btn-primary" style="background: #38ef7d; color: black; padding: 6px 12px; font-size: 11px; width: auto; text-decoration: none; display: inline-block;">Approve</a>
                                            <a href="?reject_withdrawal=<?php echo $req['id']; ?>" onclick="return confirm('Rejecting this request will refund the amount to the user\'s wallet. Continue?')" class="btn-primary" style="background: #ff4444; color: white; padding: 6px 12px; font-size: 11px; width: auto; text-decoration: none; display: inline-block;">Reject</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:#555;">No pending withdrawal requests.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Transaction History Section -->
             <div class="section-header">
                <h3 class="section-title">All Transactions</h3>
            </div>

            <div class="content-box">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions->num_rows > 0): ?>
                            <?php while($row = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td style="color:#666;">#<?php echo $row['id']; ?></td>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($row['username']); ?></div>
                                        <div style="font-size:11px; color:#aaa;"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="status-pill status-<?php echo ($row['type'] == 'deposit' || $row['type'] == 'sale') ? 'approved' : 'pending'; ?>">
                                            <?php echo ucfirst($row['type']); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight:700; color:<?php echo $row['amount'] > 0 ? '#38ef7d' : '#ff4444'; ?>;">
                                        <?php echo $row['amount'] > 0 ? '+' : ''; ?>₹<?php echo number_format($row['amount'], 2); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td style="color:#aaa; font-size:12px;"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; padding:30px; color:#555;">No transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
