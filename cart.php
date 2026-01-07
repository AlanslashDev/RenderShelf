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
    <div class="dashboard-container">
        <header class="dash-header">
            <div class="logo-area" style="margin:0;">
                <a href="welcome.php" style="color:white; text-decoration:none;"><h3><ion-icon name="arrow-back-outline"></ion-icon> Back</h3></a>
            </div>
            <h2>Shopping Cart</h2>
        </header>

        <div style="text-align:center; padding:50px; color:#aaa;">
            <ion-icon name="cart-outline" style="font-size:48px; margin-bottom:10px;"></ion-icon>
            <p>Your cart is empty.</p>
            <p style="font-size:12px;">(Cart functionality is a placeholder. You can Buy items directly.)</p>
            <a href="browse.php" class="btn-primary" style="display:inline-block; width:auto; margin-top:20px;">Browse Store</a>
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
