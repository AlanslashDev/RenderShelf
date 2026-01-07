<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Combine Transactions and Custom Notifications
$sql = "SELECT 'transaction' as type, description as message, created_at FROM transactions WHERE user_id = ? 
        UNION 
        SELECT type, message, created_at FROM notifications WHERE user_id = ?
        UNION
        SELECT 'system' as type, 'Welcome to RenderShelf!' as message, created_at FROM users WHERE id = ?
        ORDER BY created_at DESC LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
if (!$stmt->execute()) {
    // Fallback if UNION fails
    $stmt = $conn->prepare("SELECT type, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark as read after fetching
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

function time_elapsed_string($datetime, $full = false) {
    if ($datetime == '0000-00-00 00:00:00') return 'Just now';
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - RenderShelf</title>
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
            <h2>Notifications</h2>
             <a href="?mark_read=all" style="font-size:12px; color:#8a2be2; text-decoration:none;">Mark all as read</a>
        </header>

        <div style="margin-top:20px; max-width:800px; margin-left:auto; margin-right:auto;">
            <?php if (count($notifs) > 0): ?>
                <?php foreach ($notifs as $row): ?>
                    <div style="background:#1e1e1e; padding:15px; border-radius:10px; margin-bottom:10px; display:flex; align-items:center; gap:15px;">
                        <div style="
                            width:40px; height:40px; border-radius:50%; 
                            background: <?php echo $row['type'] == 'system' ? '#8a2be2' : ($row['type'] == 'deposit' ? '#38ef7d' : '#333'); ?>;
                            display:flex; align-items:center; justify-content:center;
                        ">
                            <ion-icon name="<?php echo $row['type'] == 'system' ? 'information' : 'wallet'; ?>"></ion-icon>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:14px;"><?php echo htmlspecialchars($row['message']); ?></div>
                            <div style="font-size:12px; color:#aaa; margin-top:3px;"><?php echo time_elapsed_string($row['created_at']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#aaa;">
                    <ion-icon name="notifications-off-outline" style="font-size:48px; margin-bottom:10px;"></ion-icon>
                    <p>No new notifications.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
