<?php
require_once 'config.php';

echo "<h2>Updating Database for Google Auth...</h2>";

// 1. Add google_id column if not exists
$check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
if ($check_col->num_rows == 0) {
    if ($conn->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE AFTER email")) {
        echo "<p style='color:green'>[SUCCESS] Added google_id column.</p>";
    } else {
        echo "<p style='color:red'>[ERROR] Failed to add google_id: " . $conn->error . "</p>";
    }
} else {
    echo "<p>[SKIP] google_id already exists.</p>";
}

// 2. Modify profile_pic to be longer (Google URLs are long)
if ($conn->query("ALTER TABLE users MODIFY COLUMN profile_pic VARCHAR(500) DEFAULT 'default_avatar.png'")) {
    echo "<p style='color:green'>[SUCCESS] Updated profile_pic column length.</p>";
} else {
     echo "<p style='color:red'>[ERROR] Failed to update profile_pic length: " . $conn->error . "</p>";
}

echo "<p>Done.</p>";
?>
