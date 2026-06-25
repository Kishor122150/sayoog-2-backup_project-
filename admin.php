<?php
require_once 'config.php';

// if (!is_admin_logged_in()) {
//     redirect('admin-login.php');
// }

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

   <!-- donations list  -->
    <style>
        .admin-table {
            width: 300%;
            border-collapse: collapse;
                margin-top: 20px;
        }
        .admin-table th, .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .admin-table th {
            background-color: #f2f2f2;
        }
        .admin-table h3{
            margin-bottom: 10px;
            text-align: left;
        margin-top: 20px;
        margin-left: 10px;
           }
    </style>
    <div class="table-responsive admin-table">
        <h3>Recent Donations</h3>
        <table>
            <thead>
                <tr>
                    <th>Donor</th>
                                <th>Food Item</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $donations = $pdo->query("SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id ORDER BY d.created_at DESC LIMIT 5")->fetchAll();
                            foreach ($donations as $donation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['food_item']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['status']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($donation['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="table-actions">
                                           
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
      
        <?php elseif ($section === 'cms'): ?>
            <section class="section-block">
             <!-- under cms need frontend and backend code to manage cms pages, create new pages, edit existing pages, and delete pages. This will allow the admin to control the content displayed on the website, such as the home page, about us page, and contact page. The admin can also manage the meta descriptions for SEO purposes.     -->
             
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
