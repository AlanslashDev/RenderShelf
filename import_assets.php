<?php
require_once 'config.php';

// Directory paths
$base_dir = __DIR__ . '/uploads/';
$assets_dir = $base_dir . 'assets/';
$previews_dir = $base_dir . 'previews/';
$thumbs_dir = $base_dir . 'thumbnails/';

// Default thumbnail if none found
$default_thumb = 'img/auth_header_geo.png';

// Ensure directories exist
if (!is_dir($assets_dir)) {
    die("Assets directory not found: $assets_dir");
}

// Get all files in assets directory
$files = scandir($assets_dir);
$count = 0;
$imported = 0;
$updated = 0;

// Get default user (Admin)
$admin_id = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch_assoc()['id'] ?? 1;

// Get a default category
$cat_res = $conn->query("SELECT id FROM categories WHERE type='asset' LIMIT 1");
$default_cat_id = $cat_res->fetch_assoc()['id'] ?? 0;

echo "<h2>Syncing Assets & Thumbnails...</h2>";
echo "<p>Base Dir: $base_dir</p>";

foreach ($files as $file) {
    if ($file === '.' || $file === '..')
        continue;

    $file_path = 'uploads/assets/' . $file;

    // --- RESOLVE METADATA (Title, Category, Paths) ---

    // 1. Resolve Paths (Preview & Thumbnail)
    $preview_path = '';
    $thumb_path = '';

    // Check exact match in previews
    if (file_exists($previews_dir . $file)) {
        $preview_path = 'uploads/previews/' . $file;
    } else {
        // Fallback: Check for same basename
        $name_parts = pathinfo($file);
        $basename = $name_parts['filename'];

        // Preview candidates
        $preview_candidates = glob($previews_dir . $basename . ".*");
        if (!empty($preview_candidates)) {
            $preview_path = 'uploads/previews/' . basename($preview_candidates[0]);
        }

        // Thumbnail candidates
        $thumb_candidates = glob($thumbs_dir . $basename . ".*");
        if (!empty($thumb_candidates)) {
            $thumb_path = 'uploads/thumbnails/' . basename($thumb_candidates[0]);
        } else {
            // Try variations: "thumb_" prefix, etc if standard glob failed
            $thumb_candidates = glob($thumbs_dir . "*{$basename}*.*");
            if (!empty($thumb_candidates)) {
                $thumb_path = 'uploads/thumbnails/' . basename($thumb_candidates[0]);
            }
        }

        // Use preview as thumb if thumb is missing and preview is image
        if (!$thumb_path && $preview_path && in_array(strtolower(pathinfo($preview_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumb_path = $preview_path;
        }
    }

    // Force Default Thumbnail if still empty
    if (empty($thumb_path)) {
        $thumb_path = $default_thumb;
    }

    // 2. Determine Title
    $title = pathinfo($file, PATHINFO_FILENAME);
    $title = preg_replace('/^\d+_/', '', $title);
    $title = str_replace(['_', '-'], ' ', $title);
    $title = ucwords($title);

    // 3. Determine Category
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $cat_id = $default_cat_id;
    if (in_array($ext, ['mp4', 'mov', 'avi'])) {
        $c = $conn->query("SELECT id FROM categories WHERE name='VFX' OR name='Transitions' LIMIT 1")->fetch_assoc();
        if ($c)
            $cat_id = $c['id'];
    } elseif (in_array($ext, ['mp3', 'wav', 'ogg'])) {
        $c = $conn->query("SELECT id FROM categories WHERE name='SFX' LIMIT 1")->fetch_assoc();
        if ($c)
            $cat_id = $c['id'];

        // FORCE OVERRIDE for User Request: Use specific GIF for ALL SFX
        $sfx_gif = 'uploads/thumbnails/1768797355_thumb_Subscribe to motionmixtapes on Gumroad.gif';
        // Note: verify if it exists, or just assign it. User was specific.
        $preview_path = $sfx_gif;
        $thumb_path = $sfx_gif;

    } elseif (in_array($ext, ['cube', '3dl'])) {
        $c = $conn->query("SELECT id FROM categories WHERE name='LUTs' LIMIT 1")->fetch_assoc();
        if ($c)
            $cat_id = $c['id'];
    }

    // --- DB OPERATIONS ---

    // Check if exists
    $check = $conn->prepare("SELECT id FROM assets WHERE file_path = ?");
    $check->bind_param("s", $file_path);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        // UPDATE existing
        $id = $existing['id'];
        $new_price = rand(5, 25) + 0.99;

        // Update thumbnail specifically, and ensure approved
        $update = $conn->prepare("UPDATE assets SET is_approved = 1, thumbnail_path = ?, price = IF(price=0, ?, price) WHERE id = ?");
        $update->bind_param("sdi", $thumb_path, $new_price, $id);

        if ($update->execute()) {
            echo "<div style='color:orange'>Updated: <b>$title</b> (Thumb: $thumb_path)</div>";
            $updated++;
        } else {
            echo "<div style='color:red'>Failed Update: $title</div>";
        }
    } else {
        // INSERT new
        $stmt = $conn->prepare("INSERT INTO assets (creator_id, title, description, category_id, price, file_path, preview_path, thumbnail_path, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $desc = "Imported asset: $title";
        $price = rand(5, 25) + 0.99;

        $stmt->bind_param("issidsss", $admin_id, $title, $desc, $cat_id, $price, $file_path, $preview_path, $thumb_path);

        if ($stmt->execute()) {
            echo "<div style='color:green'>Imported: <b>$title</b> (Thumb: $thumb_path)</div>";
            $imported++;
        } else {
            echo "<div style='color:red'>Failed Insert: $title - " . $conn->error . "</div>";
        }
    }
}

echo "<hr>";
echo "<h3>Sync Complete</h3>";
echo "New Imports: $imported<br>";
echo "Updated Assets: $updated<br>";
echo "<br><a href='welcome.php'>Go to Home</a>";
?>