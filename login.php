<?php
require_once 'config.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: welcome.php");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $stmt = $conn->prepare("SELECT id, password_hash, username, role, wallet_balance, profile_pic FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password, $username, $role, $wallet_balance, $profile_pic);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['email'] = $email;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['wallet_balance'] = $wallet_balance;
                $_SESSION['profile_pic'] = $profile_pic;

                if ($role === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: welcome.php");
                }
                exit;
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "No account found with that email";
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
    <title>Login - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="center-screen">
        <div class="container">
            <div style="text-align: left; margin-bottom: 20px;">
                <a href="index.php" style="color: white; text-decoration: none; font-size: 24px;"><ion-icon
                        name="arrow-back-outline"></ion-icon></a>
            </div>

            <div class="auth-card">
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="logo-area">
                    <img src="img/login_banner.png?v=<?php echo time(); ?>" alt="RenderShelf Logo" class="auth-banner">
                    <h1>Welcome Back</h1>
                    <p class="subtitle">Access your creative assets on RenderShelf</p>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <ion-icon name="mail" class="input-icon"></ion-icon>
                            <input type="email" name="email" placeholder="editor@rendershelf.com" required
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <ion-icon name="lock-closed" class="input-icon"></ion-icon>
                            <input type="password" name="password" placeholder="........" required>
                            <ion-icon name="eye-off-outline" style="position: absolute; right: 14px; cursor: pointer;"
                                onclick="togglePassword(this)"></ion-icon>
                        </div>
                        <div style="text-align: right; margin-top: 8px;">
                            <a href="forgot_password.php"
                                style="color: #8a2be2; font-size: 13px; text-decoration: none; font-weight: 500;">Forgot Password?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Log In</button>
                </form>

                <a href="google_login.php" class="btn-secondary"
                    style="width:100%; display:flex; justify-content:center; align-items:center; gap:10px; margin-bottom:20px; text-decoration:none; padding:12px; border-radius:12px;">
                    <ion-icon name="logo-google"></ion-icon>
                    Continue with Google
                </a>


                <p style="font-size: 13px; color: #a0a0a0;">New to RenderShelf? <a href="register.php"
                        style="color: #8a2be2;">Sign Up</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(icon) {
            const input = icon.previousElementSibling;
            if (input.type === "password") {
                input.type = "text";
                icon.name = "eye-outline";
            } else {
                input.type = "password";
                icon.name = "eye-off-outline";
            }
        }
    </script>
</body>

</html>