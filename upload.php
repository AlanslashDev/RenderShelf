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

        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        if (!is_dir($preview_dir))
            mkdir($preview_dir, 0777, true);
        if (!is_dir($thumb_dir))
            mkdir($thumb_dir, 0777, true);

        // Safely get file data
        $asset_file = isset($_FILES['asset_file']) ? $_FILES['asset_file'] : null;
        $preview_file = isset($_FILES['preview_file']) ? $_FILES['preview_file'] : null;
        $thumbnail_file = isset($_FILES['thumbnail_file']) ? $_FILES['thumbnail_file'] : null;

        // Get Category Data First for logic
        $cat_check = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $cat_check->bind_param("i", $category_id);
        $cat_check->execute();
        $cat_name = strtolower($cat_check->get_result()->fetch_assoc()['name'] ?? '');
        $is_audio_cat = (strpos($cat_name, 'music') !== false || strpos($cat_name, 'audio') !== false || strpos($cat_name, 'sound') !== false || strpos($cat_name, 'sfx') !== false);
        $no_preview_cat = (strpos($cat_name, 'lut') !== false || strpos($cat_name, 'sfx') !== false);

        // Basic Validation
        $asset_ok = ($asset_file && $asset_file['error'] == 0);
        $preview_ok = ($no_preview_cat || ($preview_file && $preview_file['error'] == 0));

        if ($asset_ok && $preview_ok) {

            $allowed_asset = ['zip', 'rar', '7z'];
            $allowed_preview_video = ['mp4', 'webm', 'mov'];
            $allowed_preview_audio = ['mp3', 'wav', 'ogg'];
            $allowed_thumb = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            $asset_ext = strtolower(pathinfo($asset_file['name'], PATHINFO_EXTENSION));
            $preview_ext = $preview_file ? strtolower(pathinfo($preview_file['name'], PATHINFO_EXTENSION)) : '';

            $max_asset_size = 1024 * 1024 * 1024; // 1GB
            $max_preview_size = 1024 * 1024 * 1024; // 1GB

            if (!$no_preview_cat && $is_audio_cat && !in_array($preview_ext, $allowed_preview_audio)) {
                $error = "Invalid audio preview format. Only MP3, WAV, or OGG allowed.";
            } elseif (!$no_preview_cat && !$is_audio_cat && !in_array($preview_ext, $allowed_preview_video)) {
                $error = "Invalid video preview format. Only MP4, WEBM, or MOV allowed.";
            } elseif ($asset_file['size'] > $max_asset_size) {
                $error = "Asset file too large. Max 1GB allowed.";
            } elseif ($preview_file && $preview_file['size'] > $max_preview_size) {
                $error = "Preview file too large. Max 1GB allowed.";
            } else {
                $asset_path = $upload_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($asset_file['name']));
                $preview_path = '';

                if (!$no_preview_cat && $preview_file) {
                    $preview_path = $preview_dir . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($preview_file['name']));
                    move_uploaded_file($preview_file['tmp_name'], $preview_path);
                }

                // Handle Thumbnail
                $thumbnail_path = null;
                if ($thumbnail_file && $thumbnail_file['error'] == 0) {
                    $thumb_ext = strtolower(pathinfo($thumbnail_file['name'], PATHINFO_EXTENSION));
                    if (in_array($thumb_ext, $allowed_thumb)) {
                        $thumbnail_path = $thumb_dir . time() . '_thumb_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($thumbnail_file['name']));
                        move_uploaded_file($thumbnail_file['tmp_name'], $thumbnail_path);
                    }
                }

                if (move_uploaded_file($asset_file['tmp_name'], $asset_path)) {

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
            }
        } else {
            $error = $asset_ok ? "Please select a preview file." : "Please select an asset file.";
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
            <h3 class="section-title" style="font-size: 24px;">New Upload</h3>
        </div>

        <div class="glass-form-card" style="max-width: 800px; margin: 40px auto;">
            <div class="form-header">
                <h2>Upload New Asset</h2>
                <p>Share your creative work with the RenderShelf community.</p>
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
                    <input type="text" name="title" class="input-field" required
                        placeholder="e.g. Cinematic LUT Pack Vol. 1">
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label class="label-text">Description</label>
                    <textarea name="description" rows="4" class="input-field"
                        style="resize: vertical; min-height: 100px;"
                        placeholder="Describe your asset, features, and how to use it..."></textarea>
                </div>

                <div class="studio-form-grid">
                    <div class="form-group">
                        <label class="label-text">Category</label>
                        <select name="category_id" class="input-field">
                            <?php
                            $cats = $conn->query("SELECT * FROM categories WHERE type='asset'");
                            while ($row = $cats->fetch_assoc()) {
                                echo "<option value='" . $row['id'] . "'>" . $row['name'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label-text">Price (₹)</label>
                        <input type="number" name="price" step="0.01" min="0" value="0.00" class="input-field" required>
                    </div>
                </div>

                <div class="studio-form-grid">
                    <div class="form-group">
                        <label class="label-text">Asset File</label>
                        <div class="upload-zone" id="asset-upload-zone">
                            <ion-icon name="cloud-upload-outline"></ion-icon>
                            <span class="upload-text-main">Click or Drag File</span>
                            <span class="upload-text-sub">Upload any format (Max 1GB)</span>
                            <input type="file" name="asset_file" required
                                onchange="updateFileName(this, 'asset-upload-zone')">
                        </div>
                    </div>

                    <div class="form-group" id="preview-group">
                        <label class="label-text" id="preview-label">Preview Video</label>
                        <div class="upload-zone" id="preview-upload-zone">
                            <ion-icon name="videocam-outline" id="preview-icon"></ion-icon>
                            <span class="upload-text-main" id="preview-text-main">Drop Video Here</span>
                            <span class="upload-text-sub" id="preview-text-sub">Required: MP4 or WEBM format</span>
                            <input type="file" name="preview_file" id="preview_input" required accept="video/*"
                                onchange="updateFileName(this, 'preview-upload-zone')">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 25px;">
                    <label class="label-text">Featured Thumbnail (Recommended)</label>
                    <div class="upload-zone" id="thumb-upload-zone"
                        style="display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 20px; padding: 15px 30px;">
                        <ion-icon name="image-outline" style="margin: 0; font-size: 32px;"></ion-icon>
                        <div style="text-align: left;">
                            <span class="upload-text-main" style="margin-bottom: 0;">Upload Cover Artwork</span>
                            <span class="upload-text-sub">Standard 16:9 ratio works best</span>
                        </div>
                        <input type="file" name="thumbnail_file" accept="image/*"
                            onchange="updateFileName(this, 'thumb-upload-zone')">
                    </div>
                </div>

                <button type="submit" class="btn-primary"
                    style="margin-top: 40px; width: 100%; padding: 18px; border-radius: 14px; font-size: 16px; font-weight: 800; letter-spacing: 0.5px;">
                    Publish to RenderShelf
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
        const previewGroup = document.getElementById('preview-group');
        const assetZone = document.getElementById('asset-upload-zone');
        const assetGroup = assetZone.closest('.form-group');

        categorySelect.addEventListener('change', function () {
            const selectedText = this.options[this.selectedIndex].text.toLowerCase();
            const isAudio = selectedText.includes('music') || selectedText.includes('audio') || selectedText.includes('sound') || selectedText.includes('sfx');
            const noPreview = selectedText.includes('lut') || selectedText.includes('sfx');

            if (noPreview) {
                previewGroup.style.display = 'none';
                previewInput.required = false;
                assetGroup.style.gridColumn = 'span 2';
            } else {
                previewGroup.style.display = 'block';
                previewInput.required = true;
                assetGroup.style.gridColumn = 'span 1';

                if (isAudio) {
                    previewLabel.textContent = "Preview Audio";
                    previewIcon.setAttribute('name', 'musical-notes-outline');
                    previewTextMain.textContent = "Drop MP3 Here";
                    previewTextSub.textContent = "Required: MP3 or WAV format";
                    previewInput.setAttribute('accept', 'audio/*');
                } else {
                    previewLabel.textContent = "Preview Video";
                    previewIcon.setAttribute('name', 'videocam-outline');
                    previewTextMain.textContent = "Drop Video Here";
                    previewTextSub.textContent = "Required: MP4 or WEBM format";
                    previewInput.setAttribute('accept', 'video/*');
                }
            }
        });

        // Trigger on load for initial value
        categorySelect.dispatchEvent(new Event('change'));
    </script>
</body>

</html>