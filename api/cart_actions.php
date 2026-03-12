<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';
$asset_id = intval($_POST['asset_id'] ?? 0);

if (!$asset_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid asset ID']);
    exit;
}

if ($action === 'add') {
    if (!in_array($asset_id, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $asset_id;
        echo json_encode(['success' => true, 'message' => 'Added to cart', 'count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Already in cart']);
    }
} elseif ($action === 'remove') {
    if (($key = array_search($asset_id, $_SESSION['cart'])) !== false) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        echo json_encode(['success' => true, 'message' => 'Removed from cart', 'count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not in cart']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>