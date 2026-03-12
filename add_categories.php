<?php
require_once 'config.php';

$categories = [
    ['name' => 'Transitions', 'type' => 'asset', 'icon' => 'shuffle-outline'],
    ['name' => 'SFX', 'type' => 'asset', 'icon' => 'musical-notes-outline'],
    ['name' => 'LUTs', 'type' => 'asset', 'icon' => 'color-palette-outline'],
    ['name' => 'VFX', 'type' => 'asset', 'icon' => 'flame-outline'],
    ['name' => '3D Models', 'type' => 'asset', 'icon' => 'cube-outline'],
    ['name' => 'Textures', 'type' => 'asset', 'icon' => 'image-outline'],
];

echo "<h2>Adding Categories...</h2>";

foreach ($categories as $cat) {
    $name = $cat['name'];
    $type = $cat['type'];
    $icon = $cat['icon'];

    // Check if exists
    $check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND type = ?");
    $check->bind_param("ss", $name, $type);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO categories (name, type, icon_class) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $type, $icon);
        if ($stmt->execute()) {
            echo "Added: $name ($type)<br>";
        } else {
            echo "Error adding $name: " . $conn->error . "<br>";
        }
    } else {
        echo "Skipped: $name (Already exists)<br>";
    }
}

echo "<h3>Done.</h3>";
echo "<a href='welcome.php'>Go to Home</a>";
?>