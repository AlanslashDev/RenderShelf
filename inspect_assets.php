<?php
require_once 'config.php';
$files = scandir('uploads/assets/');
echo "<h2>Asset Inspection</h2><table border='1'><tr><th>File</th><th>In DB?</th><th>Approved?</th><th>Price</th><th>Category</th></tr>";
foreach ($files as $file) {
    if ($file === '.' || $file === '..')
        continue;
    $path = 'uploads/assets/' . $file;
    $res = $conn->query("SELECT * FROM assets WHERE file_path = '$path'");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo "<tr>
            <td>$file</td>
            <td>YES</td>
            <td>" . ($row['is_approved'] == 1 ? 'YES' : 'NO (' . $row['is_approved'] . ')') . "</td>
            <td>{$row['price']}</td>
            <td>{$row['category_id']}</td>
        </tr>";
    } else {
        echo "<tr><td>$file</td><td>NO</td><td>-</td><td>-</td><td>-</td></tr>";
    }
}
echo "</table>";
?>