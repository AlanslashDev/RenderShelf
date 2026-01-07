<?php
// test_email.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email Configuration Diagnostic</h1>";

// 1. Check PHP INI Path
echo "<h3>1. Configuration</h3>";
echo "<strong>Loaded php.ini:</strong> " . php_ini_loaded_file() . "<br>";
echo "<strong>sendmail_path value:</strong> " . htmlspecialchars(ini_get('sendmail_path')) . "<br>";

// 2. Check Sendmail File
$sendmail_path_raw = ini_get('sendmail_path');
// Extract path attempt (rough regex)
if (preg_match('/"?([a-zA-Z]:[^"]+sendmail\.exe)"?/', $sendmail_path_raw, $matches)) {
    $exe_path = $matches[1];
    echo "<strong>Detected sendmail.exe path:</strong> " . $exe_path . "<br>";
    if (file_exists($exe_path)) {
        echo "<span style='color:green'>✔ sendmail.exe found at this path.</span><br>";
    } else {
        echo "<span style='color:red'>✘ sendmail.exe NOT found at this path. Check php.ini!</span><br>";
    }
} else {
    echo "<span style='color:orange'>⚠ Could not parse path from string. Make sure it points to sendmail.exe</span><br>";
}

// 3. Attempt Sending
echo "<h3>2. Sending Test Email</h3>";
$to = "test@example.com"; // We just want to see if it tries
$subject = "Test Email from RenderShelf Diagnostic";
$message = "If you receive this, XAMPP is configured correctly!";
$headers = "From: diagnostic@localhost";

if (isset($_GET['email'])) {
    $to = $_GET['email'];
}

echo "Attempting to send to: <strong>" . htmlspecialchars($to) . "</strong>...<br>";

// Capture output of sendmail if possible (hard with mail(), but we try to look at return)
$start = microtime(true);
$result = mail($to, $subject, $message, $headers);
$end = microtime(true);
$duration = round($end - $start, 4);

if ($result) {
    echo "<h2 style='color:green'>SUCCESS: mail() returned TRUE.</h2>";
    echo "Check your inbox (and spam folder) for the email.<br>";
    echo "Time taken: $duration seconds.<br>";
    echo "<p>If it returned TRUE but you didn't get it, check <strong>xampp/sendmail/error.log</strong> or <strong>debug.log</strong>.</p>";
} else {
    echo "<h2 style='color:red'>FAILURE: mail() returned FALSE.</h2>";
    echo "Time taken: $duration seconds.<br>";
    echo "<strong>Common Causes:</strong><br>";
    echo "<ul>";
    echo "<li>Apache was not restarted after editing php.ini.</li>";
    echo "<li>The path to sendmail.exe in php.ini is wrong.</li>";
    echo "<li>sendmail.exe crashed (check error.log).</li>";
    echo "<li>Firewall blocking sendmail.exe.</li>";
    echo "</ul>";
    
    // Check error details if available
    $error = error_get_last();
    if ($error) {
        echo "<strong>Last PHP Error:</strong> " . print_r($error, true);
    }
}
?>

<div style="margin-top: 30px; border-top: 1px solid #ccc; padding-top: 20px;">
    <h3>Test with your real email:</h3>
    <form method="get">
        Email: <input type="email" name="email" value="<?php echo htmlspecialchars($to); ?>" required>
        <button type="submit">Verify Now</button>
    </form>
</div>
