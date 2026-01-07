<?php
require_once 'config.php';

// If user is already logged in, redirect to welcome page
if (isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RenderShelf - Unlock Your Creative Potential</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="landing-body">
    
    <nav class="landing-nav">
        <div class="logo-container">
            <div class="logo-icon">
                <ion-icon name="layers"></ion-icon>
            </div>
            <span class="brand-name">RENDERSHELF</span>
        </div>
        <!-- No other nav items as requested, user must login or sign up -->
    </nav>

    <main class="landing-hero">
        <div class="hero-visual">
            <div class="abstract-shape"></div>
            
            <!-- Floating Card -->
            <div class="glass-card">
                <div class="card-icon">
                    <ion-icon name="sparkles"></ion-icon>
                </div>
                <div class="card-text">
                    <span class="card-label">NEW ARRIVAL</span>
                    <span class="card-title">Cinematic LUTs Pack Vol. 2</span>
                </div>
            </div>
        </div>

        <div class="hero-content">
            <h1>Unlock Your <span class="text-gradient">Creative</span> <span class="text-gradient-2">Potential</span></h1>
            <p>Access thousands of cinematic LUTs, motion graphics, and masterclass tutorials from top editors worldwide.</p>
            
            <div class="cta-group">
                <a href="register.php" class="btn-primary btn-large">
                    Sign Up Free 
                    <ion-icon name="arrow-forward"></ion-icon>
                </a>
                <a href="login.php" class="btn-secondary btn-large">Log In</a>
            </div>
        </div>
    </main>

    <footer class="landing-footer">
        <p>By continuing, you agree to our <a href="terms.php">Terms</a> & <a href="privacy.php">Privacy Policy</a>.</p>
    </footer>

</body>
</html>
