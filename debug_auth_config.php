<?php
// debug_auth_config.php
require_once 'config.php';
require_once 'config_google.php';

echo "<h2>Google Auth Debugger</h2>";

echo "<h3>Environment Loading</h3>";
$env_path = __DIR__ . '/.env';
if (file_exists($env_path)) {
    echo "Found .env file at: $env_path <br>";
    $lines = file($env_path);
    echo "Line count: " . count($lines) . "<br>";

    // Check if GOOGLE_CLIENT_ID is in the file content (without showing value)
    $found_id = false;
    foreach ($lines as $line) {
        if (strpos($line, 'GOOGLE_CLIENT_ID') !== false) {
            $found_id = true;
            break;
        }
    }
    echo "GOOGLE_CLIENT_ID present in .env file? " . ($found_id ? 'Yes' : 'No') . "<br>";
} else {
    echo "<strong style='color:red'>.env file NOT found at: $env_path</strong><br>";
}

echo "<h3>Constants</h3>";
echo "GOOGLE_CLIENT_ID defined? " . (defined('GOOGLE_CLIENT_ID') ? 'Yes' : 'No') . "<br>";
echo "GOOGLE_CLIENT_SECRET defined? " . (defined('GOOGLE_CLIENT_SECRET') ? 'Yes' : 'No') . "<br>";

$client_id = defined('GOOGLE_CLIENT_ID') ? constant('GOOGLE_CLIENT_ID') : null;
if ($client_id) {
    echo "GOOGLE_CLIENT_ID length: " . strlen($client_id) . "<br>";
    echo "GOOGLE_CLIENT_ID start: " . substr($client_id, 0, 5) . "...<br>";
} else {
    echo "GOOGLE_CLIENT_ID value: EMPTY/FALSE<br>";
}

echo "<h3>Environment Variables (getenv)</h3>";
echo "getenv('GOOGLE_CLIENT_ID'): " . (getenv('GOOGLE_CLIENT_ID') ? 'Set' : 'Empty') . "<br>";

echo "<h3>Server Variables (\$_SERVER)</h3>";
echo "\$_SERVER['GOOGLE_CLIENT_ID']: " . (isset($_SERVER['GOOGLE_CLIENT_ID']) ? 'Set' : 'Not Set') . "<br>";

echo "<h3>ENV Variables (\$_ENV)</h3>";
echo "\$_ENV['GOOGLE_CLIENT_ID']: " . (isset($_ENV['GOOGLE_CLIENT_ID']) ? 'Set' : 'Not Set') . "<br>";
?>