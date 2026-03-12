<?php
require_once 'config.php';

$res = $conn->query('SELECT id, file_path FROM assets ORDER BY id DESC LIMIT 5');
while ($r = $res->fetch_assoc()) {
    $raw_path = $r['file_path'];
    $absolute = __DIR__ . DIRECTORY_SEPARATOR . ltrim($raw_path, '/\\');
    echo "ID " . $r['id'] . "\n";
    echo "Raw: " . $raw_path . "\n";
    echo "Absolute: " . $absolute . "\n";
    echo "Exists: " . (file_exists($absolute) ? 'Yes' : 'No') . "\n";

    // Also try the raw path directly in case it expects relative to working dir
    echo "Raw Exists: " . (file_exists($raw_path) ? 'Yes' : 'No') . "\n";
    echo "-------\n";
}
