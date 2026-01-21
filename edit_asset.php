<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($asset_id <= 0) {
    header("Location: manage_uploads.php");
    exit;
}

// Check if asset exists and belongs to the user
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ? AND creator_id = ?");
$stmt->bind_param("ii", $asset_id, $user_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    header("Location: manage_uploads.php?error=Asset not found or access denied.");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_SANITIZE_NUMBER_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // File Upload Handling (Optional for Edit)
    $upload_dir = 'uploads/assets/';
    $preview_dir = 'uploads/previews/';
    $thumb_dir = 'uploads/thumbnails/';

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    if (!is_dir($preview_dir)) mkdir($preview_dir, 0777, true);
    if (!is_dir($thumb_dir)) mkdir($thumb_dir, 0777, true);

    $asset_path = $asset['file_path'];
    $preview_path = $asset['preview_path'];
    $thumbnail_path = $asset['thumbnail_path'];

    $allowed_asset = ['zip', 'rar', '7z'];
    $allowed_preview_video = ['mp4', 'webm', 'mov'];
    $allowed_preview_audio = ['mp3', 'wav', 'ogg'];
    $allowed_thumb = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    // Check Category to decide preview validation
    $cat_check = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_check->bind_param("i", $category_id);
    $cat_check->execute();
    $cat_name = strtolower($cat_check->get_result()->fetch_assoc()['name'] ?? '');
    $is_audio_cat = (strpos($cat_name, 'music') !== false || strpos($cat_name, 'audio') !== false || strpos($cat_name, 'sound') !== false || strpos($cat_name, 'sfx') !== false);

    $max_asset_size = 100 * 1024 * 1024;
    $max_preview_size = 50 * 1024 * 1024;

    // Handle Asset File
    if (isset($_FILES['asset_file']) && $_FILES['asset_file']['error'] == 0) {
        if ($_FILES['asset_file']['size'] > $max_asset_size) {
            $error = "Asset file too large (Max 100MB).";
        } else {
            $new_asset_path = $upload_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['asset_file']['name']));
            if (move_uploaded_file($_FILES['asset_file']['tmp_name'], $new_asset_path)) {
                if ($asset_path && file_exists($asset_path)) @unlink($asset_path);
                $asset_path = $new_asset_path;
            }
        }
    }

    // Handle Preview File
    if (empty($error) && isset($_FILES['preview_file']) && $_FILES['preview_file']['error'] == 0) {
        $preview_ext = strtolower(pathinfo($_FILES['preview_file']['name'], PATHINFO_EXTENSION));
        if ($is_audio_cat && !in_array($preview_ext, $allowed_preview_audio)) {
            $error = "Invalid audio preview format.";
        } elseif (!$is_audio_cat && !in_array($preview_ext, $allowed_preview_video)) {
            $error = "Invalid video preview format.";
        } elseif ($_FILES['preview_file']['size'] > $max_preview_size) {
            $error = "Preview file too large (Max 50MB).";
        } else {
            $new_preview_path = $preview_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['preview_file']['name']));
            if (move_uploaded_file($_FILES['preview_file']['tmp_name'], $new_preview_path)) {
                if ($preview_path && file_exists($preview_path)) @unlink($preview_path);
                $preview_path = $new_preview_path;
            }
        }
    }

    // Handle Thumbnail
    if (empty($error) && isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] == 0) {
        $thumb_ext = strtolower(pathinfo($_FILES['thumbnail_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($thumb_ext, $allowed_thumb)) {
            $error = "Invalid thumbnail format.";
        } else {
            $new_thumb_path = $thumb_dir . time() . '_thumb_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['thumbnail_file']['name']));
            if (move_uploaded_file($_FILES['thumbnail_file']['tmp_name'], $new_thumb_path)) {
                if ($thumbnail_path && file_exists($thumbnail_path)) @unlink($thumbnail_path);
                $thumbnail_path = $new_thumb_path;
            }
        }
    }

    if (empty($error)) {
        // Update Database
        $status = 'pending'; 
        $stmt = $conn->prepare("UPDATE assets SET title = ?, description = ?, category_id = ?, price = ?, file_path = ?, preview_path = ?, thumbnail_path = ?, status = ? WHERE id = ? AND creator_id = ?");
        $stmt->bind_param("sssidssssi", $title, $description, $category_id, $price, $asset_path, $preview_path, $thumbnail_path, $status, $asset_id, $user_id);
        
        if ($stmt->execute()) {
            $success = "Asset updated successfully! Pending re-approval.";
            // Refresh asset data for the form
            $asset['title'] = $title;
            $asset['description'] = $description;
            $asset['category_id'] = $category_id;
            $asset['price'] = $price;
            $asset['thumbnail_path'] = $thumbnail_path;
            $asset['file_path'] = $asset_path;
            $asset['preview_path'] = $preview_path;
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Asset - RenderShelf</title>
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
                    <a href="notifications.php"><ion-icon name="notifications-outline"></ion-icon></a>
                </div>
            </div>
        </header>

        <a href="manage_uploads.php" class="back-btn">
            <ion-icon name="arrow-back"></ion-icon> Back to Studio
        </a>

        <div class="section-header" style="margin-bottom: 20px;">
            <h3 class="section-title" style="font-size: 24px;">Edit Asset</h3>
        </div>

        <div class="glass-form-card" style="max-width: 800px; margin: 40px auto;">
            <div class="form-header">
                <h2>Edit Asset Details</h2>
                <p>Update your asset information or replace files.</p>
            </div>

            <?php if ($success): ?>
                <div class="success-message" style="margin-bottom: 25px;"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message" style="margin-bottom: 25px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                
                <div class="form-group" style="margin-bottom: 25px;">
                    <label class="label-text">Asset Title</label>
                    <input type="text" name="title" class="input-field" required value="<?php echo htmlspecialchars($asset['title']); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label class="label-text">Description</label>
                    <textarea name="description" rows="4" class="input-field" style="resize: vertical; min-height: 100px;"><?php echo htmlspecialchars($asset['description']); ?></textarea>
                </div>

                <div class="studio-form-grid">
                    <div class="form-group">
                        <label class="label-text">Category</label>
                        <select name="category_id" class="input-field" style="appearance: none;">
                            <?php
                            $cats = $conn->query("SELECT * FROM categories WHERE type='asset'");
                            while($row = $cats->fetch_assoc()) {
                                $selected = ($row['id'] == $asset['category_id']) ? 'selected' : '';
                                echo "<option value='".$row['id']."' $selected>".$row['name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label-text">Price (₹)</label>
                        <input type="number" name="price" step="0.01" min="0" value="<?php echo number_format($asset['price'], 2, '.', ''); ?>" class="input-field" required>
                    </div>
                </div>

                <div class="studio-form-grid">
                    <div class="form-group">
                        <label class="label-text">Update Asset File <small style="color:#555;">(Optional)</small></label>
                        <div class="upload-zone" id="asset-edit-zone">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                            <span class="upload-text-main">Replace Asset</span>
                            <span class="upload-text-sub">Upload any format (Max 100MB)</span>
                            <input type="file" name="asset_file" onchange="updateFileName(this, 'asset-edit-zone')">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="label-text" id="preview-label">Update Preview <small style="color:#555;">(Optional)</small></label>
                        <div class="upload-zone" id="preview-edit-zone">
                            <ion-icon name="videocam-outline" id="preview-icon"></ion-icon>
                            <span class="upload-text-main" id="preview-text-main">Replace Video</span>
                            <span class="upload-text-sub" id="preview-text-sub">Video preview will be updated</span>
                            <input type="file" name="preview_file" id="preview_input" accept="video/*" onchange="updateFileName(this, 'preview-edit-zone')">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:25px;">
                    <label class="label-text">Update Thumbnail Image</label>
                    <div class="upload-zone" id="thumb-edit-zone" style="display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 20px; padding: 20px;">
                        <?php if ($asset['thumbnail_path']): ?>
                            <img src="<?php echo htmlspecialchars($asset['thumbnail_path']); ?>" style="width:100px; height:60px; object-fit:cover; border-radius:8px; border: 2px solid rgba(255,255,255,0.1);">
                        <?php else: ?>
                            <ion-icon name="image-outline" style="margin: 0;"></ion-icon>
                        <?php endif; ?>
                        <div style="text-align: left;">
                            <span class="upload-text-main">Replace Cover</span>
                            <span class="upload-text-sub">Click to choose a new image</span>
                        </div>
                        <input type="file" name="thumbnail_file" accept="image/*" onchange="updateFileName(this, 'thumb-edit-zone')">
                    </div>
                </div>


                <button type="submit" class="btn-primary" style="margin-top: 40px; width: 100%; padding: 18px; border-radius: 14px; font-size: 16px;">
                    Update Asset Status
                </button>
            </form>
        </div>

    </div>

    <script>
        function updateFileName(input, zoneId) {
            const zone = document.getElementById(zoneId);
            const textMain = zone.querySelector('.upload-text-main');
            if (input.files && input.files.length > 0) {
                textMain.textContent = input.files[0].name;
                textMain.style.color = '#38ef7d';
                zone.style.borderColor = '#38ef7d';
            }
        }

        const categorySelect = document.querySelector('select[name="category_id"]');
        const previewLabel = document.getElementById('preview-label');
        const previewIcon = document.getElementById('preview-icon');
        const previewTextMain = document.getElementById('preview-text-main');
        const previewTextSub = document.getElementById('preview-text-sub');
        const previewInput = document.getElementById('preview_input');

        categorySelect.addEventListener('change', function() {
            const selectedText = this.options[this.selectedIndex].text.toLowerCase();
            const isAudio = selectedText.includes('music') || selectedText.includes('audio') || selectedText.includes('sound') || selectedText.includes('sfx');

            if (isAudio) {
                previewLabel.innerHTML = 'Update Preview <small style="color:#555;">(Optional)</small>';
                previewIcon.setAttribute('name', 'musical-notes-outline');
                previewTextMain.textContent = "Replace MP3";
                previewTextSub.textContent = "MP3/Audio preview will be updated";
                previewInput.setAttribute('accept', 'audio/*');
            } else {
                previewLabel.innerHTML = 'Update Preview <small style="color:#555;">(Optional)</small>';
                previewIcon.setAttribute('name', 'videocam-outline');
                previewTextMain.textContent = "Replace Video";
                previewTextSub.textContent = "Video preview will be updated";
                previewInput.setAttribute('accept', 'video/*');
            }
        });

        // Trigger on load for initial value
        categorySelect.dispatchEvent(new Event('change'));
    </script>
</body>
</html>
