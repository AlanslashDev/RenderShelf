<?php
require_once 'config.php';
// Migration: Sound Effects (9) -> SFX (4)
$update_assets = $conn->query("UPDATE assets SET category_id = 4 WHERE category_id = 9");
$delete_cat = $conn->query("DELETE FROM categories WHERE id = 9");

$output = "Sound Effects to SFX Migration Results:\n";
$output .= "Assets Updated: " . ($update_assets ? "Success" : "Failed: " . $conn->error) . "\n";
$output .= "Category Deleted: " . ($delete_cat ? "Success" : "Failed: " . $conn->error) . "\n";

file_put_contents('cats_log.txt', $output);
?>
