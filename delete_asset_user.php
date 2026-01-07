<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($asset_id > 0) {
    // Ensure the asset belongs to the user
    $stmt = $conn->prepare("SELECT id FROM assets WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $asset_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // Delete the asset
        $delete = $conn->prepare("DELETE FROM assets WHERE id = ?");
        $delete->bind_param("i", $asset_id);
        if ($delete->execute()) {
            header("Location: manage_uploads.php?msg=Asset deleted successfully.");
            exit;
        } else {
            header("Location: manage_uploads.php?error=Error deleting asset.");
            exit;
        }
    } else {
        header("Location: manage_uploads.php?error=Access Denied.");
        exit;
    }
} else {
    header("Location: manage_uploads.php");
    exit;
}
?>
