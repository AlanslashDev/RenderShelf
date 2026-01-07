<?php
// Google API Configuration
// Get your credentials from: https://console.cloud.google.com/apis/credentials

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URL', getenv('GOOGLE_REDIRECT_URL') ?: 'http://localhost/rendershelf/google_callback.php');
?>
