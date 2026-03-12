<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first to write a review.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$asset_id = isset($_POST['asset_id']) ? intval($_POST['asset_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

if ($asset_id <= 0 || $rating < 1 || $rating > 5 || empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid rating and review text.']);
    exit;
}

// 1. Check if user is trying to review their own asset
$creator_check = $conn->prepare("SELECT creator_id FROM assets WHERE id = ?");
$creator_check->bind_param("i", $asset_id);
$creator_check->execute();
$c_result = $creator_check->get_result();
$asset_info = $c_result->fetch_assoc();

if (!$asset_info) {
    echo json_encode(['success' => false, 'message' => 'Asset not found.']);
    exit;
}

if ($asset_info['creator_id'] == $user_id) {
    echo json_encode(['success' => false, 'message' => 'You cannot review your own asset.']);
    exit;
}

// 2. Add or Update Review
// Use INSERT ... ON DUPLICATE KEY UPDATE to allow users to update their review
$stmt = $conn->prepare("INSERT INTO reviews (user_id, asset_id, rating, review_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = VALUES(review_text), created_at = CURRENT_TIMESTAMP");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error preparing statement.']);
    exit;
}

$stmt->bind_param("iiis", $user_id, $asset_id, $rating, $review_text);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Review successfully posted!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Could not post review.']);
}
?>
