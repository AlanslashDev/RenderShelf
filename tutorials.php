<?php
require_once 'config.php';

// Check Auth
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch Tutorials
$tutorials = $conn->query("SELECT t.*, c.name as category_name FROM tutorials t LEFT JOIN categories c ON t.category_id = c.id ORDER BY t.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorials - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
    <div class="dashboard-container">
        
        <header class="dash-header">
            <div class="logo-area" style="margin:0;">
                <a href="welcome.php" style="color:white; text-decoration:none;"><h3><ion-icon name="arrow-back-outline"></ion-icon> Back</h3></a>
            </div>
            <h2>Tutorial Library</h2>
        </header>

        <div class="asset-grid" style="margin-top:20px;">
            <?php if ($tutorials->num_rows > 0): ?>
                <?php while($tut = $tutorials->fetch_assoc()): ?>
                    <div class="learning-card" style="width:100%;">
                        <div class="thumb-box" style="background:#111; position:relative; overflow:hidden;">
                            <?php if ($tut['thumbnail_path']): ?>
                                <img src="<?php echo htmlspecialchars($tut['thumbnail_path']); ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background: linear-gradient(45deg, #444, #222);"></div>
                            <?php endif; ?>
                            <span class="duration-badge"><?php echo htmlspecialchars($tut['duration']); ?></span>
                            <!-- Play Overlay -->
                            <div style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.3);">
                                <ion-icon name="play-circle" style="font-size:48px; color:white;"></ion-icon>
                            </div>
                        </div>
                        <div class="card-title"><?php echo htmlspecialchars($tut['title']); ?></div>
                        <div class="card-author">By <?php echo htmlspecialchars($tut['author_name']); ?> • <?php echo htmlspecialchars($tut['category_name']); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Placeholder content if empty -->
                 <div class="learning-card">
                    <div class="thumb-box" style="background: #222;">
                        <div style="width:100%; height:100%; background: linear-gradient(45deg, #2b5876 0%, #4e4376 100%);"></div>
                        <span class="duration-badge">12:04</span>
                    </div>
                    <div class="card-title">Mastering DaVinci Resolve</div>
                    <div class="card-author">By Sarah J.</div>
                </div>
                <div class="learning-card">
                    <div class="thumb-box" style="background: #222;">
                        <div style="width:100%; height:100%; background: linear-gradient(45deg, #134e5e 0%, #71b280 100%);"></div>
                        <span class="duration-badge">08:30</span>
                    </div>
                    <div class="card-title">Sound Design Basics</div>
                    <div class="card-author">By AudioLab</div>
                </div>
                <div class="learning-card">
                    <div class="thumb-box" style="background: #222;">
                        <div style="width:100%; height:100%; background: linear-gradient(45deg, #cc2b5e 0%, #753a88 100%);"></div>
                        <span class="duration-badge">25:10</span>
                    </div>
                    <div class="card-title">Advanced Color Grading</div>
                    <div class="card-author">By ColoristPro</div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
