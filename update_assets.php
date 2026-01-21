<?php
require_once 'config.php';

$updates = [
    4 => 'img/dust_particles.png',
    5 => 'img/lens_flares.png',
    6 => 'img/glitch_displacement.png',
    7 => 'img/modern_zooms.png',
    8 => 'img/teal_orange_grade.png',
    9 => 'img/teal_orange_grade.png'
];

$output = "Update Results:\n";
foreach ($updates as $id => $path) {
    // Update both preview and thumbnail
    $stmt = $conn->prepare("UPDATE assets SET preview_path = ?, thumbnail_path = ? WHERE id = ?");
    $stmt->bind_param("ssi", $path, $path, $id);
    if ($stmt->execute()) {
        $output .= "ID: $id | Path: $path | Result: Success\n";
    } else {
        $output .= "ID: $id | Path: $path | Result: Failed ($conn->error)\n";
    }
}

file_put_contents('assets_update_log.txt', $output);
?>
