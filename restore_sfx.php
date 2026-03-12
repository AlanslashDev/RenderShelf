<?php
require_once 'config.php';

echo "--- RESTORING SFX AUDIO PREVIEWS ---\n";

// The Thumbnail user wants for all SFX
$sfx_thumb = 'uploads/thumbnails/1768797355_thumb_Subscribe to motionmixtapes on Gumroad.gif';

$cat_id = $conn->query("SELECT id FROM categories WHERE name='SFX'")->fetch_assoc()['id'] ?? 0;

if ($cat_id) {
    // 1. Get all SFX assets
    $res = $conn->query("SELECT id, title, file_path FROM assets WHERE category_id = $cat_id");

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $id = $row['id'];
            $file_path = $row['file_path'];
            $title = $row['title'];

            // 2. Set Preview = File Path (The Audio)
            // 3. Set Thumbnail = The GIF (The Image)
            $stmt = $conn->prepare("UPDATE assets SET preview_path = ?, thumbnail_path = ? WHERE id = ?");
            $stmt->bind_param("ssi", $file_path, $sfx_thumb, $id);

            if ($stmt->execute()) {
                echo "Fixed: $title (Preview -> Audio, Thumb -> GIF)\n";
            } else {
                echo "Failed: $title\n";
            }
        }
    } else {
        echo "No SFX assets found.\n";
    }
} else {
    echo "SFX Category not found.\n";
}
echo "--- DONE ---\n";
?>