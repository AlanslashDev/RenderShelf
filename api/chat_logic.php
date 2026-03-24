<?php
// api/chat_logic.php
require_once '../config.php';

header('Content-Type: application/json');

// Get raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data || !isset($data['message'])) {
    echo json_encode(['response' => "I'm sorry, I didn't catch that. Could you repeat?"]);
    exit;
}

$user_message = strtolower(trim($data['message']));
$role = $data['role'] ?? 'guest';
$user_id = $_SESSION['user_id'] ?? null;

$response = "";
$action = null;

// Routing logic
if (strpos($user_message, 'balance') !== false || strpos($user_message, 'wallet') !== false) {
    if (!$user_id) {
        $response = "You need to log in to check your balance.";
    } else {
        $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $balance = $res['wallet_balance'] ?? 0;
        $response = "Your current wallet balance is **₹" . number_format($balance, 2) . "**. You're doing great!";
        $action = ['text' => 'View Wallet', 'url' => 'wallet.php'];
    }
} 
elseif (strpos($user_message, 'library') !== false || strpos($user_message, 'my assets') !== false) {
    if (!$user_id) {
        $response = "Log in to see your library of purchased assets.";
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as c FROM transactions WHERE type='purchase' AND user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['c'];
        $response = "You have **$count** items in your library. Ready to download some more?";
        $action = ['text' => 'Go to Library', 'url' => 'library.php'];
    }
}
elseif (strpos($user_message, 'popular') !== false || strpos($user_message, 'trending') !== false) {
    $res = $conn->query("SELECT title FROM assets WHERE is_approved = 1 ORDER BY download_count DESC LIMIT 3");
    $items = [];
    while($row = $res->fetch_assoc()) $items[] = $row['title'];
    $response = "Our top trending assets right now are:\n1. **" . ($items[0] ?? 'N/A') . "**\n2. **" . ($items[1] ?? 'N/A') . "**\n3. **" . ($items[2] ?? 'N/A') . "**";
    $action = ['text' => 'Explore All', 'url' => 'browse.php'];
}
elseif (strpos($user_message, 'how to upload') !== false || strpos($user_message, 'selling') !== false || strpos($user_message, 'creator') !== false) {
    $response = "Becoming a creator is easy! Just go to our Upload page, add your assets, and once approved, they'll be live for the world to buy.";
    $action = ['text' => 'Upload Now', 'url' => 'upload.php'];
}
elseif ($role === 'admin' && (strpos($user_message, 'revenue') !== false || strpos($user_message, 'stats') !== false)) {
    $total_sales = $conn->query("SELECT SUM(amount) as s FROM transactions WHERE type='purchase'")->fetch_assoc()['s'] ?? 0;
    $response = "Platform total volume is currently **₹" . number_format($total_sales, 2) . "**. The marketplace is thriving!";
    $action = ['text' => 'Admin Panel', 'url' => 'admin_dashboard.php'];
}
elseif ($role === 'admin' && (strpos($user_message, 'pending') !== false || strpos($user_message, 'approval') !== false)) {
    $pending = $conn->query("SELECT COUNT(*) as c FROM assets WHERE is_approved = 0")->fetch_assoc()['c'];
    $response = "There are **$pending** assets waiting for your review. Efficiency is key!";
    $action = ['text' => 'Review Now', 'url' => 'admin_approvals.php'];
}
else {
    $response = "I'm **ShelfBot**, your personal guide to RenderShelf! I can help you with:\n• Checking your **wallet balance**\n• Viewing your **personal library**\n• Discovering **trending assets**\n• Learning how to **sell your work**\n\nHow can I help you right now?";
}

echo json_encode([
    'response' => $response,
    'action' => $action
]);
