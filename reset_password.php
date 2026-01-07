<?php
require_once 'config.php';

// Check if user has verified OTP
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = '';
$success = '';
$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Update Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Delete used OTP
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            // Clear session variables
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);
            
            $success = "Password updated successfully! <a href='login.php' style='color: #00cc66; text-decoration: underline;'>Log In Now</a>";
        } else {
            $error = "Error updating password";
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
    <title>Set New Password - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="center-screen">
        <div class="container">
            <div class="auth-card">
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="logo-area">
                    <h1>Create New Password</h1>
                    <p class="subtitle">Enter your new password below.</p>
                </div>

                <?php if (empty($success)): ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="input-wrapper">
                            <ion-icon name="lock-closed" class="input-icon"></ion-icon>
                            <input type="password" name="password" placeholder="........" required minlength="6">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <ion-icon name="refresh" class="input-icon"></ion-icon>
                            <input type="password" name="confirm_password" placeholder="........" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Update Password</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
