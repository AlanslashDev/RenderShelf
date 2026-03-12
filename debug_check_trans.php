<?php
require_once 'config.php';

echo "--- TRANSITIONS CHECK ---\n";
// Get Transitions Category ID
$cat_id = $conn->query("SELECT id FROM categories WHERE name='Transitions'")->fetch_assoc()['id'] ?? 0;

if ($cat_id) {
    $res = $conn->query("SELECT id, title, file_path, preview_path, thumbnail_path FROM assets WHERE category_id = $cat_id");
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Title: {$row['title']}\n";
        echo "File: {$row['file_path']}\n";
        echo "Preview: {$row['preview_path']}\n";
        echo "Thumb: {$row['thumbnail_path']}\n\n";
    }
} else {
    echo "Transitions category not found.\n";
}
?>