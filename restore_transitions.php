<?php
require_once 'config.php';

echo "--- RESTORING TRANSITION VIDEO PREVIEWS ---\n";

// The Thumbnail user wants for all
$trans_thumb = 'uploads/thumbnails/1769754789_thumb_Screenshot_2026_01_30_120012.png';

$cat_id = $conn->query("SELECT id FROM categories WHERE name='Transitions'")->fetch_assoc()['id'] ?? 0;

if ($cat_id) {
    // 1. Get all transitions
    $res = $conn->query("SELECT id, title, file_path FROM assets WHERE category_id = $cat_id");

    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        $file_path = $row['file_path'];
        $title = $row['title'];

        // 2. Set Preview = File Path (The Video)
        // 3. Set Thumbnail = The Screenshot (The Image)
        $stmt = $conn->prepare("UPDATE assets SET preview_path = ?, thumbnail_path = ? WHERE id = ?");
        $stmt->bind_param("ssi", $file_path, $trans_thumb, $id);

        if ($stmt->execute()) {
            echo "Fixed: $title (Preview -> Video, Thumb -> Screenshot)\n";
        } else {
            echo "Failed: $title\n";
        }
    }
} else {
    echo "Category not found.\n";
}
echo "--- DONE ---\n";
?>