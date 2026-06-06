<?php
require_once 'config.php';

if (!is_admin_logged_in()) {
    redirect('admin-login.php');
}

$section = sanitize($_GET['section'] ?? 'dashboard');
$valid_sections = ['dashboard', 'users', 'products', 'cms'];
if (!in_array($section, $valid_sections)) {
    $section = 'dashboard';
}

$errors = [];
$flash = get_flash_message();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_product' || $action === 'update_product') {
        $title = sanitize($_POST['title'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $image_path = sanitize($_POST['image_path'] ?? '');
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'inactive';

        if (empty($title)) $errors[] = 'Product title is required.';
        if (empty($slug)) $errors[] = 'Product slug is required.';
        if ($price < 0) $errors[] = 'Product price cannot be negative.';

        if (empty($errors)) {
            if ($action === 'create_product') {
                $stmt = $pdo->prepare("INSERT INTO products (title, slug, description, price, image_path, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $description, $price, $image_path ?: null, $status]);
                set_flash_message('success', 'Product created successfully.');
            } else {
                $product_id = intval($_POST['product_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE products SET title = ?, slug = ?, description = ?, price = ?, image_path = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $description, $price, $image_path ?: null, $status, $product_id]);
                set_flash_message('success', 'Product updated successfully.');
            }
            redirect('admin.php?section=products');
        }
    }

    if ($action === 'delete_product') {
        $product_id = intval($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            set_flash_message('success', 'Product deleted.');
        }
        redirect('admin.php?section=products');
    }

    if ($action === 'create_page' || $action === 'update_page') {
        $title = sanitize($_POST['title'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $content = $_POST['content'] ?? '';
        $meta_description = sanitize($_POST['meta_description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) $errors[] = 'Page title is required.';
        if (empty($slug)) $errors[] = 'Page slug is required.';
        if (empty($content)) $errors[] = 'Page content is required.';

        if (empty($errors)) {
            if ($action === 'create_page') {
                $stmt = $pdo->prepare("INSERT INTO cms_pages (slug, title, content, meta_description, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$slug, $title, $content, $meta_description, $is_active]);
                set_flash_message('success', 'CMS page created successfully.');
            } else {
                $page_id = intval($_POST['page_id'] ?? 0);
                $stmt = $pdo->prepare("UPDATE cms_pages SET slug = ?, title = ?, content = ?, meta_description = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$slug, $title, $content, $meta_description, $is_active, $page_id]);
                set_flash_message('success', 'CMS page updated successfully.');
            }
            redirect('admin.php?section=cms');
        }
    }

    if ($action === 'delete_page') {
        $page_id = intval($_POST['page_id'] ?? 0);
        if ($page_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM cms_pages WHERE id = ?");
            $stmt->execute([$page_id]);
            set_flash_message('success', 'CMS page deleted.');
        }
        redirect('admin.php?section=cms');
    }

    if ($action === 'update_user_role') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $role = sanitize($_POST['role'] ?? 'user');
        if ($user_id > 0 && in_array($role, ['user', 'admin', 'donor', 'consumer'], true)) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user_id]);
            set_flash_message('success', 'User role updated.');
            redirect('admin.php?section=users');
        }
    }

    if ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            set_flash_message('success', 'User deleted.');
        }
        redirect('admin.php?section=users');
    }
}

$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$cms_count = $pdo->query("SELECT COUNT(*) FROM cms_pages")->fetchColumn();
$donation_count = $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
$pages = $pdo->query("SELECT * FROM cms_pages ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-branding">
            <a href="admin.php" class="site-logo"><i class="fa-solid fa-shield-halved"></i> Admin</a>
        </div>
        <nav class="admin-nav">
            <a href="admin.php?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="admin.php?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>">Users</a>
            <a href="admin.php?section=products" class="<?php echo $section === 'products' ? 'active' : ''; ?>">Products</a>
            <a href="admin.php?section=cms" class="<?php echo $section === 'cms' ? 'active' : ''; ?>">CMS</a>
            <a href="dashboard.php">User Dashboard</a>
            <a href="logout.php" class="btn btn-secondary">Sign Out</a>
        </nav>
    </header>

    <main class="admin-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($section === 'dashboard'): ?>
            <section class="section-block admin-grid">
                <div class="summary-card">
                    <h3>Users</h3>
                    <p><?php echo $user_count; ?> total</p>
                </div>
                <div class="summary-card">
                    <h3>Products</h3>
                    <p><?php echo $product_count; ?> total</p>
                </div>
                <div class="summary-card">
                    <h3>CMS Pages</h3>
                    <p><?php echo $cms_count; ?> total</p>
                </div>
                <div class="summary-card">
                    <h3>Donations</h3>
                    <p><?php echo $donation_count; ?> total</p>
                </div>
            </section>
        <?php elseif ($section === 'users'): ?>
            <section class="section-block">
                <div class="section-heading">
                    <h1>Manage Users</h1>
                    <p>Change user roles or remove test accounts. Admin cannot update their own role here.</p>
                </div>

                <div class="table-responsive admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="table-actions">
                                            <form action="admin.php?section=users" method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="update_user_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role" class="form-control form-control-inline">
                                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    <option value="donor" <?php echo $user['role'] === 'donor' ? 'selected' : ''; ?>>Donor</option>
                                                    <option value="consumer" <?php echo $user['role'] === 'consumer' ? 'selected' : ''; ?>>Consumer</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                            </form>
                                            <form action="admin.php?section=users" method="POST" class="inline-form" onsubmit="return confirm('Delete this user?');">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm">Delete</button>
                                            </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($section === 'products'): ?>
            <section class="section-block">
                <div class="section-heading">
                    <h1>Manage Products</h1>
                    <p>Create, update, or remove products shown on the public storefront.</p>
                </div>

                <div class="admin-panel-grid">
                    <div class="panel-card">
                        <h3>Add New Product</h3>
                        <form action="admin.php?section=products" method="POST">
                            <input type="hidden" name="action" value="create_product">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" name="slug" class="form-control" placeholder="product-slug" required>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Price</label>
                                    <input type="number" step="0.01" name="price" class="form-control" value="0.00" required>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Image path or URL</label>
                                <input type="text" name="image_path" class="form-control" placeholder="uploads/file.jpg or https://...">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Product</button>
                        </form>
                    </div>

                    <div class="panel-card table-card">
                        <h3>Existing Products</h3>
                        <div class="table-responsive admin-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Slug</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['title']); ?></td>
                                            <td><?php echo htmlspecialchars($product['slug']); ?></td>
                                            <td>Rs <?php echo number_format($product['price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($product['status']); ?></td>
                                            <td class="table-actions">
                                                <form action="admin.php?section=products" method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        <?php elseif ($section === 'cms'): ?>
            <section class="section-block">
                <div class="section-heading">
                    <h1>Manage CMS Pages</h1>
                    <p>Create or edit the public website pages managed from the database.</p>
                </div>

                <div class="admin-panel-grid">
                    <div class="panel-card">
                        <h3>Create New Page</h3>
                        <form action="admin.php?section=cms" method="POST">
                            <input type="hidden" name="action" value="create_page">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" name="slug" class="form-control" placeholder="page-slug" required>
                            </div>
                            <div class="form-group">
                                <label>Meta Description</label>
                                <input type="text" name="meta_description" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Content (HTML allowed)</label>
                                <textarea name="content" class="form-control" rows="6" required></textarea>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" name="is_active" checked> Active</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Page</button>
                        </form>
                    </div>

                    <div class="panel-card table-card">
                        <h3>CMS Pages</h3>
                        <div class="table-responsive admin-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Slug</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pages as $pageItem): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($pageItem['title']); ?></td>
                                            <td><?php echo htmlspecialchars($pageItem['slug']); ?></td>
                                            <td><?php echo $pageItem['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                            <td class="table-actions">
                                                <form action="admin.php?section=cms" method="POST" style="display:inline-block;">
                                                    <input type="hidden" name="action" value="delete_page">
                                                    <input type="hidden" name="page_id" value="<?php echo $pageItem['id']; ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
