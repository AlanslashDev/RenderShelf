<?php
require_once 'config.php';

echo "--- TRANSITIONS ---\n";
// Get Transitions Category ID
$cat_id = $conn->query("SELECT id FROM categories WHERE name='Transitions'")->fetch_assoc()['id'] ?? 0;

if ($cat_id) {
    $res = $conn->query("SELECT id, title, preview_path, thumbnail_path FROM assets WHERE category_id = $cat_id");
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Title: {$row['title']} | Preview: {$row['preview_path']} | Thumb: {$row['thumbnail_path']}\n";
    }
} else {
    echo "Transitions category not found.\n";
}

echo "\n--- GARBAGE (Title '3' or '0206') ---\n";
$res = $conn->query("SELECT id, title, file_path FROM assets WHERE title LIKE '3%' OR title = '0206'");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Title: {$row['title']} | File: {$row['file_path']}\n";
}
?>