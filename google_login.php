<?php
require_once 'config.php';
require_once 'config_google.php';

// Check if credentials are set
if (GOOGLE_CLIENT_ID === 'YOUR_GOOGLE_CLIENT_ID_HERE') {
    die('Please configure GOOGLE_CLIENT_ID in config_google.php');
}

$params = [
    'response_type' => 'code',
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URL,
    'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
    'access_type' => 'offline',
    'prompt' => 'consent'
];

$auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);

header('Location: ' . $auth_url);
exit;
?>
