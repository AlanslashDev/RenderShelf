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
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="studio-container" style="max-width: 800px;">
        
        <header class="dash-header" style="border:none; margin-bottom: 10px;">
            <div class="logo-container">
                <a href="welcome.php" style="text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <div class="logo-icon" style="width:30px; height:30px; font-size: 18px;">
                        <ion-icon name="layers"></ion-icon>
                    </div>
                    <h2 style="color:white; margin:0; letter-spacing: -0.5px; font-size: 20px;">RenderShelf</h2>
                </a>
            </div>
            <a href="?mark_read=all" style="font-size:13px; color:var(--accent-color); text-decoration:none; font-weight: 600;">Mark all as read</a>
        </header>

        <a href="welcome.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Dashboard
        </a>

        <div class="section-header" style="margin-bottom: 25px;">
            <h3 class="section-title" style="font-size: 24px;">Notifications</h3>
        </div>

        <div>
            <?php if (count($notifs) > 0): ?>
                <?php foreach ($notifs as $row): ?>
                    <div style="background:var(--card-bg); padding:20px; border-radius:16px; margin-bottom:12px; display:flex; align-items:center; gap:18px; border: 1px solid rgba(255,255,255,0.05); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div style="
                            min-width:48px; height:48px; border-radius:14px; 
                            background: <?php echo $row['type'] == 'system' ? 'rgba(138, 43, 226, 0.1)' : ($row['type'] == 'deposit' ? 'rgba(56, 239, 125, 0.1)' : 'rgba(255,255,255,0.05)'); ?>;
                            display:flex; align-items:center; justify-content:center;
                            color: <?php echo $row['type'] == 'system' ? '#a45eff' : ($row['type'] == 'deposit' ? '#38ef7d' : '#888'); ?>;
                            font-size: 22px;
                        ">
                            <ion-icon name="<?php echo $row['type'] == 'system' ? 'information-circle' : ($row['type'] == 'deposit' ? 'wallet' : 'notifications'); ?>"></ion-icon>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:15px; color: white; line-height: 1.4;"><?php echo htmlspecialchars($row['message']); ?></div>
                            <div style="font-size:12px; color:#555; margin-top:5px; font-weight: 600;"><?php echo time_elapsed_string($row['created_at']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding:100px 20px; background: rgba(255,255,255,0.02); border-radius: 30px; border: 1px dashed rgba(255,255,255,0.1);">
                    <ion-icon name="notifications-off-outline" style="font-size:64px; margin-bottom:20px; color: #333;"></ion-icon>
                    <h3 style="color: white; margin-bottom: 10px;">Clear Inbox</h3>
                    <p style="color: #555;">No new updates for you right now.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
