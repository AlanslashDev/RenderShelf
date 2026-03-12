<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$asset_id = isset($_POST['asset_id']) ? intval($_POST['asset_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($asset_id <= 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if ($action === 'add') {
    $stmt = $conn->prepare("INSERT IGNORE INTO favourites (user_id, asset_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $asset_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to favourites.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} else {
    $stmt = $conn->prepare("DELETE FROM favourites WHERE user_id = ? AND asset_id = ?");
    $stmt->bind_param("ii", $user_id, $asset_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed from favourites.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
}
?>
