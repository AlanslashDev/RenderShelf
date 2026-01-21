<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

// Fetch all transactions
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
                    <h1>Transaction History</h1>
                    <p>View all platform financial movements.</p>
                </div>
            </header>

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
