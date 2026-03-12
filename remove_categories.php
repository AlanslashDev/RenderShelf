<?php
require_once 'config.php';

$to_remove = ['3D Models', 'Textures'];

echo "<h2>Removing Categories...</h2>";

foreach ($to_remove as $name) {
    // Check if exists
    $check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE name = ?");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo "Removed: $name<br>";
        } else {
            echo "Error removing $name: " . $conn->error . "<br>";
        }
    } else {
        echo "Skipped: $name (Not found)<br>";
    }
}

echo "<h3>Done.</h3>";
echo "<a href='welcome.php'>Go to Home</a>";
?>