<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);
    
    if (empty($email) || empty($otp)) {
        $error = "Please enter both email and OTP";
    } else {
        // Verify OTP
        $stmt = $conn->prepare("SELECT token, expiry FROM password_resets WHERE email = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($stored_otp, $expiry);
            $stmt->fetch();
            
            if (strtotime($expiry) < time()) {
                $error = "OTP has expired. Please request a new one.";
            } elseif ($otp !== $stored_otp) {
                $error = "Invalid OTP. Please try again.";
            } else {
                // OTP is valid, redirect to reset password page
                $_SESSION['reset_email'] = $email;
                $_SESSION['otp_verified'] = true;
                header("Location: reset_password.php");
                exit;
            }
        } else {
            $error = "No OTP found for this email. Please request a new one.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="center-screen">
        <div class="container">
            <div style="text-align: left; margin-bottom: 20px;">
                <a href="forgot_password.php" style="color: white; text-decoration: none; font-size: 24px;"><ion-icon name="arrow-back-outline"></ion-icon></a>
            </div>

            <div class="auth-card">
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="logo-area">
                    <h1>Verify OTP</h1>
                    <p class="subtitle">Enter the 6-digit code sent to your email.</p>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <ion-icon name="mail" class="input-icon"></ion-icon>
                            <input type="email" name="email" placeholder="editor@rendershelf.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>OTP Code</label>
                        <div class="input-wrapper">
                            <ion-icon name="key" class="input-icon"></ion-icon>
                            <input type="text" name="otp" placeholder="000000" required maxlength="6" pattern="[0-9]{6}" style="letter-spacing: 8px; text-align: center; font-size: 20px; font-weight: bold;">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Verify OTP</button>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="forgot_password.php" style="color: #667eea; text-decoration: none;">Didn't receive code? Request new OTP</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
