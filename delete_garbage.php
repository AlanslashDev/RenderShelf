<?php
require_once 'config.php';

// Titles of dummy data to remove
$titles_to_remove = ['3', '0206'];

echo "<h2>Cleaning Up Garbage Data...</h2>";

foreach ($titles_to_remove as $t) {
    // Get list of files associated with this title
    $res = $conn->query("SELECT id, file_path, preview_path, thumbnail_path FROM assets WHERE title = '$t'");

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $id = $row['id'];
            $file = $row['file_path'];

            // Delete from DB
            $conn->query("DELETE FROM assets WHERE id = $id");
            echo "Deleted DB Record: $t (ID: $id)<br>";

            // Delete physical file so it doesn't get imported again
            if (file_exists($file)) {
                unlink($file); // DANGER: Effectively deletes the file
                echo "<div style='color:red'>Deleted File: $file</div>";
            }

            // Optional: Delete preview/thumb if unique? 
            // Safer to leave them or check if other assets use them, but for now just removing the main asset file prevents re-import.
        }
    } else {
        echo "No assets found with title: $t<br>";
    }
}

echo "<hr><h3>Cleanup Complete.</h3>";
echo "The '3' assets are gone and will not return.";
echo "<br><a href='welcome.php'>Go to Home</a>";
?>