<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the POST request is empty but Content-Length is set (indicates file size limit exceeded)
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "The uploaded files exceed the maximum allowed size. Please upload smaller files or contact the administrator.";
    } else {
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $creator_id = $_SESSION['user_id'];
        
        // File Upload Handling
        $upload_dir = 'uploads/assets/';
        $preview_dir = 'uploads/previews/';
        $thumb_dir = 'uploads/thumbnails/'; // New directory

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        if (!is_dir($preview_dir)) mkdir($preview_dir, 0777, true);
        if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);

        // Safely get file data
        $asset_file = isset($_FILES['asset_file']) ? $_FILES['asset_file'] : null;
        $preview_file = isset($_FILES['preview_file']) ? $_FILES['preview_file'] : null;
        $thumbnail_file = isset($_FILES['thumbnail_file']) ? $_FILES['thumbnail_file'] : null;

        // Basic Validation
        if ($asset_file && $preview_file && $asset_file['error'] == 0 && $preview_file['error'] == 0) {
        
        $asset_path = $upload_dir . time() . '_' . basename($asset_file['name']);
        $preview_path = $preview_dir . time() . '_' . basename($preview_file['name']);
        
        // Handle Thumbnail
        $thumbnail_path = null;
        if ($thumbnail_file && $thumbnail_file['error'] == 0) {
            $thumbnail_path = $thumb_dir . time() . '_thumb_' . basename($thumbnail_file['name']);
            move_uploaded_file($thumbnail_file['tmp_name'], $thumbnail_path);
        }

        if (move_uploaded_file($asset_file['tmp_name'], $asset_path) && 
            move_uploaded_file($preview_file['tmp_name'], $preview_path)) {
            
            // Insert into Database
            // Note: thumbnail_path might be null if not uploaded (optional?), but request implies "add option". 
            // We'll treat it as optional or required? Let's make it optional but recommended.
            $stmt = $conn->prepare("INSERT INTO assets (creator_id, title, description, category_id, price, file_path, preview_path, thumbnail_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issidsss", $creator_id, $title, $description, $category_id, $price, $asset_path, $preview_path, $thumbnail_path);
            
            if ($stmt->execute()) {
                $success = "Asset uploaded successfully! Pending approval.";
            } else {
                $error = "Database Error: " . $conn->error;
            }
        } else {
            $error = "Failed to move uploaded files.";
        }
    } else {
        $error = "Please select both an asset file and a preview file.";
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Asset - RenderShelf</title>
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
            <h2>Upload Asset</h2>
        </header>

        <div class="auth-card" style="width:100%; max-width:800px; margin:40px auto; padding:30px;">
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Asset Title</label>
                    <input type="text" name="title" required placeholder="e.g. Cinematic LUT Pack Vol. 1">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" style="width:100%; background:#2a2a2a; border:none; color:white; padding:10px; border-radius:8px;" placeholder="Describe your asset..."></textarea>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" style="width:100%; padding:10px; background:#2a2a2a; color:white; border:none; border-radius:8px;">
                        <?php
                        $cats = $conn->query("SELECT * FROM categories WHERE type='asset'");
                        while($row = $cats->fetch_assoc()) {
                            echo "<option value='".$row['id']."'>".$row['name']."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" name="price" step="0.01" min="0" value="0.00" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Asset File (ZIP/RAR)</label>
                        <div style="background:#2a2a2a; padding:15px; border-radius:8px; text-align:center; border:2px dashed #444;">
                            <ion-icon name="folder-zip-outline" style="font-size:32px; color:#aaa;"></ion-icon>
                            <input type="file" name="asset_file" required style="display:block; margin:10px auto; width:90%;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Preview File (Video/Image)</label>
                        <div style="background:#2a2a2a; padding:15px; border-radius:8px; text-align:center; border:2px dashed #444;">
                            <ion-icon name="images-outline" style="font-size:32px; color:#aaa;"></ion-icon>
                            <input type="file" name="preview_file" required style="display:block; margin:10px auto; width:90%;" accept="image/*,video/*">
                            <small style="color:#777; display:block; margin-top:5px;">Main preview shown on details page</small>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:20px;">
                    <label>Cover Image (Thumbnail) <span style="font-size:12px; color:#aaa;">(Optional, Recommended)</span></label>
                    <div style="background:#2a2a2a; padding:15px; border-radius:8px; text-align:center; border:2px dashed #444;">
                         <ion-icon name="image-outline" style="font-size:32px; color:#aaa;"></ion-icon>
                        <input type="file" name="thumbnail_file" accept="image/*" style="display:block; margin:10px auto; width:90%;">
                        <small style="color:#777; display:block; margin-top:5px;">Shown in store grid listing. If empty, preview will be used.</small>
                    </div>
                </div>


                <button type="submit" class="btn-primary" style="margin-top:20px;">Upload Asset</button>
            </form>
        </div>

    </div>
</body>
</html>
