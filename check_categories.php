<?php
require_once 'config.php';
$result = $conn->query("SELECT * FROM categories");
while ($row = $result->fetch_assoc()) {
    echo $row['name'] . " (" . $row['type'] . ")\n";
}
?>