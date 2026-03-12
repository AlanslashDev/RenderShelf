<?php
require 'config.php';

$res = $conn->query("SHOW TABLES LIKE 'favourites'");
if ($res->num_rows == 0) {
    echo "Creating favourites table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS favourites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        asset_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favourite (user_id, asset_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    )");
} else {
    echo "favourites table exists.\n";
}

$res = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($res->num_rows == 0) {
    echo "Creating reviews table...\n";
    $conn->query("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        asset_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_review (user_id, asset_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    )");
} else {
    echo "reviews table exists.\n";
}
echo "Done.";
?>
