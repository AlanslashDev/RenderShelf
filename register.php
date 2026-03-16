<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: welcome.php");
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already registered";
        } else {
            // Check if username exists
            $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
            if (empty($username)) {
                $username = explode('@', $email)[0];
            }

            $stmt_u = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_u->bind_param("s", $username);
            $stmt_u->execute();
            $stmt_u->store_result();

            if ($stmt_u->num_rows > 0) {
                $error = "Username is already taken";
            } else {
                // Register user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (email, password_hash, username) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $email, $hashed_password, $username);

                if ($stmt->execute()) {
                    $success = "Account created successfully! <a href='login.php'>Log In</a>";
                } else {
                    $error = "Error: " . $conn->error;
                }
            }
            $stmt_u->close();
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
    <title>Join RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <!-- Ionicons for inputs -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="center-screen">
        <div class="container">
            <!-- Back Button Placeholder -->
            <div style="text-align: left; margin-bottom: 20px;">
                <a href="index.php" style="color: white; text-decoration: none; font-size: 24px;"><ion-icon
                        name="arrow-back-outline"></ion-icon></a>
            </div>

            <div class="auth-card">
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="logo-area">
                    <img src="img/login_banner.png?v=<?php echo time(); ?>" alt="RenderShelf Logo" class="auth-banner">
                    <h1>Join the Shelf</h1>
                    <p class="subtitle">Access premium assets and tutorials for your next video edit.</p>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Username</label>
                        <div class="input-wrapper">
                            <ion-icon name="person" class="input-icon"></ion-icon>
                            <input type="text" name="username" placeholder="CreativeDirector" required
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>

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
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <ion-icon name="refresh" class="input-icon"></ion-icon>
                            <input type="password" name="confirm_password" placeholder="........" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" required>
                            <label for="terms" style="display:inline; color: #a0a0a0; margin:0;">I agree to the <a
                                    href="terms.php" style="color:#8a2be2">Terms of Service</a> and <a
                                    href="privacy.php" style="color:#8a2be2">Privacy Policy</a>.</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Create Account</button>
                </form>

                <!-- Google Button -->
                <a href="google_login.php" class="btn-secondary"
                    style="width:100%; display:flex; justify-content:center; align-items:center; gap:10px; margin-bottom:20px; text-decoration:none; padding:12px; border-radius:12px;">
                    <ion-icon name="logo-google"></ion-icon>
                    Continue with Google
                </a>

                <div class="divider">
                    <span>OR CONTINUE WITH EMAIL</span>
                </div>

                <p style="font-size: 13px; color: #a0a0a0;">Already have an account? <a href="login.php"
                        style="color: #8a2be2;">Log In</a></p>
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