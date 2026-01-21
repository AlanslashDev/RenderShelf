<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$msg = '';
$error = '';

// Handle Add/Edit Tutorial
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author_name']);
    $duration = trim($_POST['duration']);
    $url = trim($_POST['video_url']);
    $cat_id = intval($_POST['category_id']);
    $asset_id = !empty($_POST['related_asset_id']) ? intval($_POST['related_asset_id']) : null;
    
    // Handle File Upload for Thumbnail/Preview Image
    $thumb_path = '';
    if (isset($_FILES['thumb_img']) && $_FILES['thumb_img']['error'] == 0) {
        $target_dir = "uploads/tutorials/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['thumb_img']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['thumb_img']['tmp_name'], $target_file)) {
            $thumb_path = $target_file;
        }
    }

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Edit
        $id = intval($_POST['id']);
        if ($thumb_path) {
            $stmt = $conn->prepare("UPDATE tutorials SET title=?, author_name=?, duration=?, video_url=?, category_id=?, thumbnail_path=?, related_asset_id=? WHERE id=?");
            $stmt->bind_param("ssssisii", $title, $author, $duration, $url, $cat_id, $thumb_path, $asset_id, $id);
        } else {
            $stmt = $conn->prepare("UPDATE tutorials SET title=?, author_name=?, duration=?, video_url=?, category_id=?, related_asset_id=? WHERE id=?");
            $stmt->bind_param("ssssiii", $title, $author, $duration, $url, $cat_id, $asset_id, $id);
        }
        if ($stmt->execute()) $msg = "Tutorial updated.";
    } else {
        // Add
        $stmt = $conn->prepare("INSERT INTO tutorials (title, author_name, duration, video_url, category_id, thumbnail_path, related_asset_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssisi", $title, $author, $duration, $url, $cat_id, $thumb_path, $asset_id);
        if ($stmt->execute()) $msg = "Tutorial added.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM tutorials WHERE id=$id");
    $msg = "Tutorial deleted.";
}

// Fetch Tutorials
$tutorials = $conn->query("SELECT t.*, c.name as cat_name FROM tutorials t LEFT JOIN categories c ON t.category_id = c.id ORDER BY t.created_at DESC");

// Fetch Categories (only tutorial type)
$categories = $conn->query("SELECT * FROM categories WHERE type='tutorial'");

// Fetch All Approved Assets for linking
$all_assets = $conn->query("SELECT id, title FROM assets WHERE status='approved' ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tutorials - Admin</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
    <style>
        .tut-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .tut-admin-card {
            background: #16161a;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.03);
            transition: transform 0.3s;
        }
        .tut-admin-card:hover { transform: translateY(-5px); }
        .tut-thumb {
            width: 100%;
            height: 160px;
            background: #222;
            position: relative;
        }
        .tut-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .tut-actions {
            padding: 15px;
            display: flex;
            gap: 10px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>
    <main class="main-content">
        <div class="admin-container">
            <header class="admin-header">
                <div class="header-title">
                    <h1>Tutorial Management</h1>
                    <p>Add, edit, or remove learning sessions.</p>
                </div>
                <button class="btn-primary" onclick="showModal()" style="width:auto; padding:10px 25px;">+ Add Tutorial</button>
            </header>

            <?php if ($msg): ?>
                <div class="success-message"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="tut-card-grid">
                <?php while($t = $tutorials->fetch_assoc()): ?>
                <div class="tut-admin-card">
                    <div class="tut-thumb">
                        <?php if($t['thumbnail_path']): ?>
                            <img src="<?php echo $t['thumbnail_path']; ?>">
                        <?php else: ?>
                            <div style="width:100%; height:100%; background: linear-gradient(45deg, #1a1a1a, #333);"></div>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 15px;">
                        <h4 style="margin:0;"><?php echo htmlspecialchars($t['title']); ?></h4>
                        <p style="font-size:12px; color:#666; margin: 5px 0;">By <?php echo htmlspecialchars($t['author_name']); ?> • <?php echo $t['duration']; ?></p>
                        <p style="font-size:11px; color:var(--accent-color); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($t['video_url']); ?></p>
                    </div>
                    <div class="tut-actions">
                        <button class="view-btn" onclick='editTut(<?php echo json_encode($t); ?>)' style="flex:1;"><ion-icon name="create-outline"></ion-icon> Edit</button>
                        <a href="?delete=<?php echo $t['id']; ?>" class="view-btn" style="flex:1; background:rgba(255,68,68,0.1); color:#ff4444;" onclick="return confirm('Delete this tutorial?')"><ion-icon name="trash-outline"></ion-icon> Delete</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <!-- Modal for Add/Edit -->
    <div id="tut-modal" class="modal-overlay">
        <div class="modal-card" style="max-width: 500px; text-align: left;">
            <h3 id="modal-title">Add Tutorial</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="tut-id">
                <div class="form-group" style="margin:15px 0;">
                    <label style="font-size:12px; color:#888;">Tutorial Title</label>
                    <input type="text" name="title" id="tut-title" required class="input-field" style="background:#0a0a0a; margin-top:5px;">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label style="font-size:12px; color:#888;">Author Name</label>
                        <input type="text" name="author_name" id="tut-author" required class="input-field" style="background:#0a0a0a; margin-top:5px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px; color:#888;">Duration (e.g. 15:30)</label>
                        <input type="text" name="duration" id="tut-duration" class="input-field" style="background:#0a0a0a; margin-top:5px;">
                    </div>
                </div>
                <div class="form-group" style="margin:15px 0;">
                    <label style="font-size:12px; color:#888;">YouTube / Video URL</label>
                    <input type="url" name="video_url" id="tut-url" required class="input-field" style="background:#0a0a0a; margin-top:5px;">
                </div>
                <div class="form-group" style="margin:15px 0; display: grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label style="font-size:12px; color:#888;">Category</label>
                        <select name="category_id" id="tut-cat" class="input-field" style="background:#0a0a0a; margin-top:5px;">
                            <?php 
                            $categories->data_seek(0);
                            while($c = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px; color:#888;">Related Asset (Optional)</label>
                        <select name="related_asset_id" id="tut-asset" class="input-field" style="background:#0a0a0a; margin-top:5px;">
                            <option value="">None / General</option>
                            <?php 
                            $all_assets->data_seek(0);
                            while($as = $all_assets->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $as['id']; ?>"><?php echo htmlspecialchars($as['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin:15px 0;">
                    <label style="font-size:12px; color:#888;">Thumbnail Image</label>
                    <input type="file" name="thumb_img" class="input-field" style="background:#0a0a0a; margin-top:5px;" accept="image/*">
                </div>
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn-primary" style="margin:0;">Save Session</button>
                    <button type="button" onclick="closeModal()" class="modal-btn-cancel" style="margin:0; flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal() {
            document.getElementById('modal-title').innerText = "Add Tutorial";
            document.getElementById('tut-id').value = "";
            document.getElementById('tut-title').value = "";
            document.getElementById('tut-author').value = "";
            document.getElementById('tut-duration').value = "";
            document.getElementById('tut-url').value = "";
            document.getElementById('tut-modal').style.display = 'flex';
            setTimeout(() => document.getElementById('tut-modal').classList.add('active'), 10);
        }
        function editTut(t) {
            document.getElementById('modal-title').innerText = "Edit Tutorial";
            document.getElementById('tut-id').value = t.id;
            document.getElementById('tut-title').value = t.title;
            document.getElementById('tut-author').value = t.author_name;
            document.getElementById('tut-duration').value = t.duration;
            document.getElementById('tut-url').value = t.video_url;
            document.getElementById('tut-cat').value = t.category_id;
            document.getElementById('tut-asset').value = t.related_asset_id || "";
            document.getElementById('tut-modal').style.display = 'flex';
            setTimeout(() => document.getElementById('tut-modal').classList.add('active'), 10);
        }
        function closeModal() {
            document.getElementById('tut-modal').classList.remove('active');
            setTimeout(() => document.getElementById('tut-modal').style.display = 'none', 300);
        }
    </script>
</body>
</html>
