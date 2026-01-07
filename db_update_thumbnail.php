<?php
require_once 'config.php';

// Add thumbnail_path column to assets table if it doesn't exist
$sql = "SHOW COLUMNS FROM assets LIKE 'thumbnail_path'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Adding thumbnail_path column...<br>";
    $alter = "ALTER TABLE assets ADD COLUMN thumbnail_path VARCHAR(255) AFTER preview_path";
    if ($conn->query($alter) === TRUE) {
        echo "Column thumbnail_path added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column thumbnail_path already exists.";
}

// Also update simple fallback logic for existing rows if needed (optional)
echo "<br><br><a href='welcome.php'>Go Back to Home</a>";
?>
