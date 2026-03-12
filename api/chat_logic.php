<?php
// api/chat_logic.php
header('Content-Type: application/json');

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? strtolower(trim($input['message'])) : '';
$role = isset($input['role']) ? $input['role'] : 'guest';

$response = [
    'response' => "I'm sorry, I didn't verify that. Could you try rephrasing? You can ask me about uploads, payments, or account issues.",
    'action' => null // Optional action button link
];

// Simple keyword matching logic
if (empty($message)) {
    $response['response'] = "Hi there! How can I help you today?";
} elseif (strpos($message, 'hi') !== false || strpos($message, 'hello') !== false || strpos($message, 'hey') !== false) {
    $response['response'] = "Hello! Welcome to RenderShelf Support. How can I assist you?";
} elseif (strpos($message, 'upload') !== false || strpos($message, 'sell') !== false) {
    $response['response'] = "To upload an asset, click on the **'Upload Asset'** button in the top right corner or on your dashboard. Make sure your asset meets our quality guidelines.";
    $response['action'] = ['text' => 'Go to Upload', 'url' => 'upload.php'];
} elseif (strpos($message, 'payment') !== false || strpos($message, 'money') !== false || strpos($message, 'withdraw') !== false || strpos($message, 'wallet') !== false) {
    $response['response'] = "You can manage your earnings and request withdrawals from your **Wallet** page. We process payments via PayPal and Bank Transfer.";
    $response['action'] = ['text' => 'Go to Wallet', 'url' => 'wallet.php'];
} elseif (strpos($message, 'buy') !== false || strpos($message, 'purchase') !== false) {
    $response['response'] = "To buy an asset, simply click on it and hit the **'Buy Now'** or **'Add to Cart'** button. You can pay securely using credit cards or PayPal.";
} elseif (strpos($message, 'refund') !== false) {
    $response['response'] = "Refun requests are handled on a case-by-case basis. If an asset is broken or not as described, please contact us via email at support@rendershelf.com with your order ID.";
} elseif (strpos($message, 'password') !== false || strpos($message, 'login') !== false || strpos($message, 'account') !== false) {
    $response['response'] = "You can update your profile details and password in the **Settings** or **Profile** section.";
    $response['action'] = ['text' => 'Go to Profile', 'url' => 'profile.php'];
} elseif (strpos($message, 'transitions') !== false) {
    $response['response'] = "Check out our latest **Transitions** category for smooth video editing assets.";
    $response['action'] = ['text' => 'Browse Transitions', 'url' => 'browse.php?category=Transitions'];
} elseif (strpos($message, 'sfx') !== false || strpos($message, 'sound') !== false) {
    $response['response'] = "Looking for sound effects? We have a great collection in our **SFX** category.";
    $response['action'] = ['text' => 'Browse SFX', 'url' => 'browse.php?category=SFX'];
} elseif (strpos($message, 'license') !== false || strpos($message, 'copyright') !== false) {
    $response['response'] = "All assets on RenderShelf are royalty-free for commercial use unless stated otherwise by the creator in the description.";
} elseif (strpos($message, 'admin') !== false) {
    if ($role === 'admin') {
        $response['response'] = "As an admin, you can manage users, approve assets, and view transaction history from your dashboard.";
        $response['action'] = ['text' => 'Go to Dashboard', 'url' => 'admin_dashboard.php'];
    } else {
        $response['response'] = "Admin features are restricted. If you are an admin, please log in with your admin account.";
    }
} elseif ($role === 'admin' && (strpos($message, 'pending') !== false || strpos($message, 'approval') !== false)) {
    // Admin specific: Check for pending approvals
    require_once '../config.php'; // Ensure path is correct relative to api/
    $pending = $conn->query("SELECT COUNT(*) as c FROM assets WHERE is_approved=0")->fetch_assoc()['c'];
    $response['response'] = "There are currently **$pending assets** waiting for your approval.";
    $response['action'] = ['text' => 'Review Approvals', 'url' => 'admin_approvals.php'];
} elseif ($role === 'admin' && (strpos($message, 'sales') !== false || strpos($message, 'revenue') !== false)) {
    // Admin specific: Check total revenue
    require_once '../config.php';
    $revenue = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE type='purchase'")->fetch_assoc()['total'];
    $r = number_format($revenue ?? 0, 2);
    $response['response'] = "The platform has generated a total revenue of **₹$r**.";
}

echo json_encode($response);
?>