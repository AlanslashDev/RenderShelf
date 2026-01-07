<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Access denied.");
}

$user_id = $_SESSION['user_id'];
$asset_id = intval($_GET['id']);
$user_role = $_SESSION['role'] ?? 'user';

// Check if user owns the asset or is creator/admin
// 1. Check ownership via purchases
$owned = false;
$stmt = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND related_asset_id = ? AND type = 'purchase'");
$stmt->bind_param("ii", $user_id, $asset_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $owned = true;
}

// 2. Fetch asset info to check creator & price
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) die("Asset not found.");

if ($asset['creator_id'] == $user_id || $user_role == 'admin' || $asset['price'] == 0.00) {
    $owned = true;
}

if ($owned) {
    $file_path = $asset['file_path'];
    if (file_exists($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        
        // Clear output buffer
        flush(); 
        readfile($file_path);
        
        // Optional: Update download count again if we want to track every download, 
        // but typically we track on Purchase. Up to preference.
        exit;
    } else {
        die("File not found on server.");
    }
} else {
    // Redirect to purchase if trying to access directly
    header("Location: asset_details.php?id=$asset_id&error=access_denied");
    exit;
}
?>
