<?php
require_once 'config.php';
require_once 'config_google.php';

if (isset($_GET['code'])) {
    $token_url = 'https://oauth2.googleapis.com/token';
    $params = [
        'code' => $_GET['code'],
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URL,
        'grant_type' => 'authorization_code'
    ];

    // Exchange code for token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['access_token'])) {
        // Get User Info
        $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $user_info_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_info = curl_exec($ch);
        curl_close($ch);

        $google_user = json_decode($user_info, true);

        if (isset($google_user['email'])) {
            $email = $google_user['email'];
            $google_id = $google_user['id'];
            $name = $google_user['name'];
            $picture = $google_user['picture'];

            // Check if user exists
            $stmt = $conn->prepare("SELECT id, username, role, wallet_balance, google_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // User exists
                $user = $result->fetch_assoc();
                
                // Link Google ID if not linked
                if (empty($user['google_id'])) {
                    $update = $conn->prepare("UPDATE users SET google_id = ?, profile_pic = ? WHERE id = ?");
                    $update->bind_param("ssi", $google_id, $picture, $user['id']);
                    $update->execute();
                }

                // Login Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $email;
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['wallet_balance'] = $user['wallet_balance'];
                $_SESSION['profile_pic'] = $user['profile_pic'] ?? null;

                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: welcome.php");
                }
                exit;

            } else {
                // Register new user
                // Generate a random password (user can reset it later if they want to use email/pass)
                $random_pass = bin2hex(random_bytes(8));
                $hashed_pass = password_hash($random_pass, PASSWORD_DEFAULT);
                // Create username from name (sanitize)
                $username = preg_replace("/[^a-zA-Z0-9]/", "", $name);
                
                // Ensure unique username
                $check_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
                if ($check_user->num_rows > 0) {
                    $username .= rand(100, 999);
                }

                $stmt = $conn->prepare("INSERT INTO users (email, password_hash, username, google_id, profile_pic) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $email, $hashed_pass, $username, $google_id, $picture);
                
                if ($stmt->execute()) {
                    $new_user_id = $conn->insert_id;
                    
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    $_SESSION['wallet_balance'] = 0.00;

                    header("Location: welcome.php");
                    exit;
                } else {
                    die("Error creating account: " . $conn->error);
                }
            }
        }
    } else {
        die("Error fetching token from Google. Please check your credentials.");
    }
} else {
    header("Location: login.php");
    exit;
}
?>
