<?php
require_once 'config.php';
$res = $conn->query("SELECT id, title, preview_path, thumbnail_path FROM assets ORDER BY created_at DESC");
$output = "Current Assets and Paths:\n";
while($row = $res->fetch_assoc()) {
    $output .= "ID: " . $row['id'] . " | Title: " . $row['title'] . " | Preview: " . $row['preview_path'] . " | Thumb: " . $row['thumbnail_path'] . "\n";
}
file_put_contents('assets_check.txt', $output);
?>
