<?php
require_once 'config.php';

$categories = ['VFX', 'Transitions', 'LUTs', 'Sound Effects', '3D Models', 'Music'];
$type = 'asset';

foreach ($categories as $cat) {
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND type = ?");
    $stmt->bind_param("ss", $cat, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
        $insert->bind_param("ss", $cat, $type);
        if ($insert->execute()) {
            echo "Created category: $cat<br>";
        } else {
            echo "Error creating $cat: " . $conn->error . "<br>";
        }
    } else {
        echo "Category exists: $cat<br>";
    }
}
echo "<br><a href='welcome.php'>Go Home</a>";
?>
