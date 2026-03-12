<?php
require_once 'config.php';

echo "--- STARTING FINAL FIX ---\n";

// 1. DELETE GARBAGE (Title '3' or '0206')
$garbage_titles = ['3', '0206'];
foreach ($garbage_titles as $t) {
    $res = $conn->query("SELECT id, file_path FROM assets WHERE title = '$t'");
    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        $file = $row['file_path'];

        // Delete from DB
        $conn->query("DELETE FROM assets WHERE id = $id");
        echo "Deleted Asset: $t (ID: $id)\n";

        // Delete File
        if (file_exists($file)) {
            unlink($file);
            echo "Deleted File: $file\n";
        }
        // Also cleanup variations if possible, but mainly the DB and main file.
    }
}

// 2. RESTORE TRANSITIONS PREVIEW
// User uploaded this recently, likely the one they want for all transitions
$trans_thumb = 'uploads/thumbnails/1769754789_thumb_Screenshot_2026_01_30_120012.png';

$cat_id_trans = $conn->query("SELECT id FROM categories WHERE name='Transitions'")->fetch_assoc()['id'] ?? 0;

if ($cat_id_trans && file_exists($trans_thumb)) {
    $stmt = $conn->prepare("UPDATE assets SET preview_path = ?, thumbnail_path = ? WHERE category_id = ?");
    $stmt->bind_param("ssi", $trans_thumb, $trans_thumb, $cat_id_trans);
    if ($stmt->execute()) {
        echo "Updated Transitions to use: $trans_thumb\n";
    } else {
        echo "Failed to update Transitions: " . $conn->error . "\n";
    }
} else {
    echo "Transitions Category or Thumbnail not found.\n";
    if (!file_exists($trans_thumb))
        echo "Missing Thumb: $trans_thumb\n";
}

// 3. CONFIRM SFX GIF (Just in case)
$sfx_gif = 'uploads/thumbnails/1768797355_thumb_Subscribe to motionmixtapes on Gumroad.gif';
$cat_id_sfx = $conn->query("SELECT id FROM categories WHERE name='SFX'")->fetch_assoc()['id'] ?? 0;

if ($cat_id_sfx && file_exists($sfx_gif)) {
    $stmt = $conn->prepare("UPDATE assets SET preview_path = ?, thumbnail_path = ? WHERE category_id = ?");
    $stmt->bind_param("ssi", $sfx_gif, $sfx_gif, $cat_id_sfx);
    $stmt->execute();
    echo "Confirmed SFX GIF.\n";
}

echo "--- DONE ---\n";
?>