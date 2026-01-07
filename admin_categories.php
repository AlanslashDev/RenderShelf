<?php
require_once 'config.php';

// Check Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$msg = '';
$error = '';

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type']; // 'asset' or 'tutorial'
    $icon = $_POST['icon'] ?: 'folder-outline';

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, type, icon_class) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $type, $icon);
        if ($stmt->execute()) {
            $msg = "Category added successfully.";
        } else {
            $error = "Category already exists.";
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $cat_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $cat_id);
    if ($stmt->execute()) {
        $msg = "Category deleted.";
    }
}

// Fetch Categories
$categories = $conn->query("SELECT * FROM categories ORDER BY type, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - RenderShelf</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <?php include 'includes/admin_styles.php'; ?>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-container">
        <header class="admin-header">
            <div class="header-title">
                <h1>Categories</h1>
                <p>Organize your assets and tutorials with meaningful categories.</p>
            </div>
        </header>

        <?php if ($msg): ?>
            <div class="success-message"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <section class="content-box">
                <div class="box-header">
                    <h3>Existing Categories</h3>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 0;">Icon</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                        <tr>
                            <td style="padding-left: 0; font-size:20px; color: var(--accent-color);">
                                <ion-icon name="<?php echo $cat['icon_class']; ?>"></ion-icon>
                            </td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td>
                                <span class="status-pill <?php echo $cat['type'] === 'asset' ? 'status-approved' : 'status-rejected'; ?>" style="font-size: 10px;">
                                    <?php echo strtoupper($cat['type']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; justify-content: flex-end;">
                                    <a href="?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Deleting this category might affect assets/tutorials using it. Continue?')" class="view-btn" style="background: rgba(255, 68, 68, 0.1); color: #ff4444; width: 32px; height: 32px;">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>

            <section class="content-box">
                <div class="box-header">
                    <h3>Add New</h3>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Category Name</label>
                        <div class="input-wrapper">
                            <ion-icon name="text-outline" class="input-icon"></ion-icon>
                            <input type="text" name="name" placeholder="e.g. 3D Elements" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <div class="input-wrapper">
                             <ion-icon name="list-outline" class="input-icon"></ion-icon>
                            <select name="type" style="width:100%; padding: 14px 16px 14px 44px; background-color: var(--input-bg); border: 1px solid var(--input-border); border-radius: 12px; color: white; appearance: none;">
                                <option value="asset">Asset</option>
                                <option value="tutorial">Tutorial</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Icon (Ionicon Name)</label>
                        <div class="input-wrapper">
                            <ion-icon name="happy-outline" class="input-icon"></ion-icon>
                            <input type="text" name="icon" placeholder="e.g. cube-outline">
                        </div>
                        <p style="font-size: 10px; color: #555; margin-top: 5px;">Use names from <a href="https://ionic.io/ionicons" target="_blank" style="color: var(--accent-color);">ionicons.io</a></p>
                    </div>
                    <button type="submit" name="add_category" class="btn-primary">Create Category</button>
                </form>
            </section>
        </div>
        </div>
    </main>
</body>
</html>
