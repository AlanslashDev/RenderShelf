<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - RenderShelf</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="studio-container">
        <header class="dash-header" style="border:none; margin-bottom: 20px;">
            <div class="logo-container">
                <a href="welcome.php" style="text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <div class="logo-icon" style="width:30px; height:30px; font-size: 18px;">
                        <ion-icon name="layers"></ion-icon>
                    </div>
                    <h2 style="color:white; margin:0; letter-spacing: -0.5px; font-size: 20px;">RenderShelf</h2>
                </a>
            </div>
            <div class="header-right">
                <div class="header-icons">
                    <a href="notifications.php" style="font-size: 24px;"><ion-icon name="notifications-outline"></ion-icon></a>
                </div>
            </div>
        </header>

        <a href="welcome.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Store
        </a>

        <div class="section-header" style="margin-bottom: 30px;">
            <h3 class="section-title" style="font-size: 24px;">Your Cart</h3>
        </div>

        <div style="text-align: center; padding: 100px 20px; background: rgba(255,255,255,0.02); border-radius: 30px; border: 1px dashed rgba(255,255,255,0.1);">
            <ion-icon name="cart-outline" style="font-size: 64px; margin-bottom: 20px; color: #222;"></ion-icon>
            <h3 style="color: white; margin-bottom: 10px;">Your cart is empty</h3>
            <p style="color: #555; margin-bottom: 30px;">Looks like you haven't added anything to your cart yet.</p>
            <a href="browse.php" class="btn-primary" style="width: auto; display: inline-flex; padding: 14px 40px; border-radius: 15px;">Continue Shopping</a>
        </div>
    </div>
    
    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="welcome.php" class="nav-item">
            <ion-icon name="home-outline"></ion-icon>
            Home
        </a>
        <a href="browse.php" class="nav-item">
            <ion-icon name="search-outline"></ion-icon>
            Search
        </a>
        <a href="library.php" class="nav-item">
            <ion-icon name="play-circle-outline"></ion-icon>
            Library
        </a>
         <a href="cart.php" class="nav-item active">
            <ion-icon name="cart"></ion-icon>
            Cart
        </a>
        <a href="logout.php" class="nav-item">
            <ion-icon name="person-outline"></ion-icon>
            Log Out
        </a>
    </nav>
</body>
</html>
