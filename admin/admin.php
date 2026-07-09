<?php
require_once '../config.php';

// if (!is_admin_logged_in()) {
//     redirect('admin-login.php');
// }

$section = sanitize($_GET['section'] ?? 'dashboard');
$valid_sections = ['dashboard', 'users', 'products', 'cms', 'listing_requests', 'contact_messages', 'donations'];
if (!in_array($section, $valid_sections)) {
    $section = 'dashboard';
}

$errors = [];
$flash = get_flash_message();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve_donation') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $note = sanitize($_POST['verification_note'] ?? '');
        if ($donation_id > 0) {
            $stmt = $pdo->prepare("UPDATE donations SET verification_status = 'approved', verification_note = ?, verified_at = NOW(), verified_by = ?, status = 'available' WHERE id = ?");
            $stmt->execute([$note, $_SESSION['user_id'] ?? 0, $donation_id]);
            set_flash_message('success', 'Donation approved and published.');
        }
        redirect('admin.php?section=donations');
    }

    if ($action === 'reject_donation') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $note = sanitize($_POST['verification_note'] ?? '');
        if ($donation_id > 0) {
            $stmt = $pdo->prepare("UPDATE donations SET verification_status = 'rejected', verification_note = ?, verified_at = NOW(), verified_by = ?, status = 'rejected' WHERE id = ?");
            $stmt->execute([$note, $_SESSION['user_id'] ?? 0, $donation_id]);
            set_flash_message('warning', 'Donation rejected.');
        }
        redirect('admin.php?section=donations');
    }

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

    if ($action === 'mark_contact_read') {
        $message_id = intval($_POST['message_id'] ?? 0);
        if ($message_id > 0) {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'Read' WHERE id = ?");
            $stmt->execute([$message_id]);
            set_flash_message('success', 'Contact message marked as read.');
        }
        redirect('admin.php?section=contact_messages');
    }

    if ($action === 'mark_contact_replied') {
        $message_id = intval($_POST['message_id'] ?? 0);
        if ($message_id > 0) {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'Replied' WHERE id = ?");
            $stmt->execute([$message_id]);
            set_flash_message('success', 'Contact message marked as replied.');
        }
        redirect('admin.php?section=contact_messages');
    }

    if ($action === 'delete_contact_message') {
        $message_id = intval($_POST['message_id'] ?? 0);
        if ($message_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            set_flash_message('warning', 'Contact message deleted.');
        }
        redirect('admin.php?section=contact_messages');
    }
}

$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$unread_notification_count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
$pending_request_count = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetchColumn();
$active_donation_count = $pdo->query("SELECT COUNT(*) FROM donations WHERE verification_status = 'approved' AND status IN ('available', 'requested', 'accepted')")->fetchColumn();
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
$pages = $pdo->query("SELECT * FROM cms_pages ORDER BY created_at DESC")->fetchAll();
$pending_donations = $pdo->query("SELECT d.*, u.name AS donor_name, u.email AS donor_email, u.address AS donor_address, u.phone AS donor_phone FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.verification_status = 'pending' ORDER BY d.created_at DESC")->fetchAll();
$all_donations = $pdo->query("SELECT d.*, u.name AS donor_name, u.email AS donor_email, u.address AS donor_address, u.phone AS donor_phone FROM donations d JOIN users u ON d.donor_id = u.id ORDER BY d.created_at DESC")->fetchAll();
$recent_donations = $pdo->query("SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id ORDER BY d.created_at DESC LIMIT 6")->fetchAll();
?>

<?php
if ($section == "cms" && isset($_POST['save_homepage'])) {

    $stmt = $pdo->prepare("UPDATE cms_homepage SET

    hero_heading=?,
    hero_subheading=?,
    hero_button1_text=?,
    hero_button1_link=?,
    hero_button2_text=?,
    hero_button2_link=?,

    works_title=?,
    works_description=?,

    work1_icon=?,
    work1_heading=?,
    work1_description=?,

    work2_icon=?,
    work2_heading=?,
    work2_description=?,

    work3_icon=?,
    work3_heading=?,
    work3_description=?,

    work4_icon=?,
    work4_heading=?,
    work4_description=?,

    quick_title=?,
    quick_description=?,

    quick1_icon=?,
    quick1_title=?,
    quick1_description=?,
    quick1_link=?,

    quick2_icon=?,
    quick2_title=?,
    quick2_description=?,
    quick2_link=?,

    quick3_icon=?,
    quick3_title=?,
    quick3_description=?,
    quick3_link=?,

    quick4_icon=?,
    quick4_title=?,
    quick4_description=?,
    quick4_link=?

    WHERE id=1");

    $stmt->execute([

        $_POST['hero_heading'],
        $_POST['hero_subheading'],
        $_POST['hero_button1_text'],
        $_POST['hero_button1_link'],
        $_POST['hero_button2_text'],
        $_POST['hero_button2_link'],

        $_POST['works_title'],
        $_POST['works_description'],

        $_POST['work1_icon'],
        $_POST['work1_heading'],
        $_POST['work1_description'],

        $_POST['work2_icon'],
        $_POST['work2_heading'],
        $_POST['work2_description'],

        $_POST['work3_icon'],
        $_POST['work3_heading'],
        $_POST['work3_description'],

        $_POST['work4_icon'],
        $_POST['work4_heading'],
        $_POST['work4_description'],

        $_POST['quick_title'],
        $_POST['quick_description'],

        $_POST['quick1_icon'],
        $_POST['quick1_title'],
        $_POST['quick1_description'],
        $_POST['quick1_link'],

        $_POST['quick2_icon'],
        $_POST['quick2_title'],
        $_POST['quick2_description'],
        $_POST['quick2_link'],

        $_POST['quick3_icon'],
        $_POST['quick3_title'],
        $_POST['quick3_description'],
        $_POST['quick3_link'],

        $_POST['quick4_icon'],
        $_POST['quick4_title'],
        $_POST['quick4_description'],
        $_POST['quick4_link']

    ]);

    echo "<script>alert('Homepage CMS Updated Successfully');</script>";
}

if ($section == "cms" && isset($_POST['save_aboutpage'])) {
    $stmt = $pdo->prepare("UPDATE cms_aboutpage SET
        hero_badge=?,
        hero_title=?,
        hero_description=?,
        highlight1=?,
        highlight2=?,
        highlight3=?,
        mission_title=?,
        mission_description=?,
        stat1_value=?,
        stat1_label=?,
        stat2_value=?,
        stat2_label=?,
        stat3_value=?,
        stat3_label=?,
        panel1_title=?,
        panel1_description=?,
        panel2_title=?,
        panel2_description=?,
        feature1_icon=?,
        feature1_title=?,
        feature1_description=?,
        feature2_icon=?,
        feature2_title=?,
        feature2_description=?,
        feature3_icon=?,
        feature3_title=?,
        feature3_description=?,
        feature4_icon=?,
        feature4_title=?,
        feature4_description=?,
        footer_copyright=?
        WHERE id=1
    ");

    $stmt->execute([
        $_POST['hero_badge'],
        $_POST['hero_title'],
        $_POST['hero_description'],
        $_POST['highlight1'],
        $_POST['highlight2'],
        $_POST['highlight3'],
        $_POST['mission_title'],
        $_POST['mission_description'],
        $_POST['stat1_value'],
        $_POST['stat1_label'],
        $_POST['stat2_value'],
        $_POST['stat2_label'],
        $_POST['stat3_value'],
        $_POST['stat3_label'],
        $_POST['panel1_title'],
        $_POST['panel1_description'],
        $_POST['panel2_title'],
        $_POST['panel2_description'],
        $_POST['feature1_icon'],
        $_POST['feature1_title'],
        $_POST['feature1_description'],
        $_POST['feature2_icon'],
        $_POST['feature2_title'],
        $_POST['feature2_description'],
        $_POST['feature3_icon'],
        $_POST['feature3_title'],
        $_POST['feature3_description'],
        $_POST['feature4_icon'],
        $_POST['feature4_title'],
        $_POST['feature4_description'],
        $_POST['footer_copyright']
    ]);

    echo "<script>alert('About Page CMS Updated Successfully');</script>";
}

$home = $pdo->query("SELECT * FROM cms_homepage WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$about = $pdo->query("SELECT * FROM cms_aboutpage WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$cms_tab = sanitize($_GET['cms_tab'] ?? 'homepage');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Sayog</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="../js/app.js"></script>
    <style>
        .cms-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--admin-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .cms-tab {
            padding: 12px 24px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--admin-text-muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .cms-tab:hover {
            color: var(--admin-primary);
            background: var(--admin-bg-light);
        }
        .cms-tab.active {
            color: var(--admin-primary);
            border-bottom-color: var(--admin-primary);
            background: var(--admin-bg-light);
        }
        .cms-tab i {
            font-size: 1rem;
        }
    </style>
</head>
<body>

<div class="admin-layout">

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ========== SIDEBAR ========== -->
    <aside class="admin-sidebar" id="adminSidebar">

        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <a href="admin.php" class="sidebar-logo-text">Sayog <span>Admin</span></a>
        </div>

        <div class="sidebar-profile">
            <div class="sidebar-avatar">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">Administrator</div>
                <div class="sidebar-user-role"><i class="fa-solid fa-crown"></i> Super Admin</div>
            </div>
        </div>

        <nav class="sidebar-nav">

            <div class="nav-section-label">Main</div>

            <a href="admin.php?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>

            <a href="admin.php?section=donations" class="<?php echo $section === 'donations' ? 'active' : ''; ?>">
                <i class="fa-solid fa-hand-holding-heart"></i> Donations
                <?php if (count($pending_donations) > 0): ?>
                    <span class="nav-badge"><?php echo count($pending_donations); ?></span>
                <?php endif; ?>
            </a>

            <div class="nav-section-label">Management</div>

            <a href="admin.php?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Users
            </a>

            <a href="admin.php?section=contact_messages" class="<?php echo $section === 'contact_messages' ? 'active' : ''; ?>">
                <i class="fa-solid fa-envelope"></i> Contact Messages
            </a>

            <a href="admin.php?section=cms" class="<?php echo $section === 'cms' ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-lines"></i> CMS Editor
            </a>

            <div class="nav-section-label">Other</div>

            <a href="../dashboard.php">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> User Dashboard
            </a>

        </nav>

        <div class="sidebar-logout">
            <a href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </a>
        </div>

    </aside>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="admin-main">

        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="menu-toggle" id="menuToggle" aria-label="Toggle sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="page-title">
                    <?php
                    $page_titles = [
                        'dashboard' => 'Dashboard',
                        'users' => 'Users',
                        'products' => 'Products',
                        'cms' => 'CMS Editor',
                        'contact_messages' => 'Contact Messages',
                        'donations' => 'Donation Review',
                    ];
                    echo $page_titles[$section] ?? 'Dashboard';
                    ?>
                </div>
            </div>
            <div class="admin-topbar-right">
                <a href="../index.php" class="topbar-btn" title="View site">
                    <i class="fa-solid fa-eye"></i>
                </a>
                <?php if ($unread_notification_count > 0): ?>
                    <button class="topbar-btn" title="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <span class="dot"></span>
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- Content Area -->
        <div class="admin-content">

            <!-- Flash Messages -->
            <?php if ($flash): ?>
                <div class="admin-alert admin-alert-<?php echo $flash['type']; ?>">
                    <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'); ?>"></i>
                    <span><?php echo htmlspecialchars($flash['message']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="admin-alert admin-alert-danger">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ============================== -->
            <!-- DASHBOARD SECTION              -->
            <!-- ============================== -->
            <?php if ($section === 'dashboard'): ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-icon green">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$user_count; ?></div>
                            <div class="stat-card-label">Total Users</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon blue">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$active_donation_count; ?></div>
                            <div class="stat-card-label">Active Donations</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon amber">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$pending_request_count; ?></div>
                            <div class="stat-card-label">Pending Requests</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon purple">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$unread_notification_count; ?></div>
                            <div class="stat-card-label">Unread Notifications</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Donations Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Donations</h3>
                        <a href="admin.php?section=donations" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Donor</th>
                                        <th>Food Item</th>
                                        <th>Qty</th>
                                        <th>Photo</th>
                                        <th>Status</th>
                                        <th>Verification</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1; foreach ($recent_donations as $donation): ?>
                                        <tr>
                                            <td data-label="#"><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                            <td data-label="Donor">
                                                <div class="user-cell">
                                                    <div class="user-avatar"><?php echo strtoupper(substr($donation['donor_name'], 0, 1)); ?></div>
                                                    <div>
                                                        <div class="user-name"><?php echo htmlspecialchars($donation['donor_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Food Item"><strong><?php echo htmlspecialchars($donation['food_item']); ?></strong></td>
                                            <td data-label="Qty"><?php echo htmlspecialchars($donation['quantity']); ?></td>
                                            <td data-label="Photo">
                                                <?php if (!empty($donation['image_path'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($donation['image_path']); ?>" alt="Donation" class="donation-thumb">
                                                <?php else: ?>
                                                    <span class="badge badge-neutral">No image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Status"><span class="status-tag <?php echo $donation['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $donation['status'])); ?></span></td>
                                            <td data-label="Verification">
                                                <span class="badge badge-<?php echo $donation['verification_status'] === 'approved' ? 'success' : ($donation['verification_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($donation['verification_status']); ?>
                                                </span>
                                            </td>
                                            <td data-label="Date" style="white-space: nowrap;"><?php echo date('d M Y H:i', strtotime($donation['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- ============================== -->
            <!-- USERS SECTION                 -->
            <!-- ============================== -->
            <?php elseif ($section === 'users'): ?>

                <div class="section-header">
                    <div>
                        <h1>Manage Users</h1>
                        <p>View, manage roles, and remove test accounts.</p>
                    </div>
                    <span class="badge badge-info"><i class="fa-solid fa-users"></i> <?php echo count($users); ?> total</span>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-users"></i> All Users</h3>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1; foreach ($users as $user): ?>
                                        <tr>
                                            <td data-label="#"><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                            <td data-label="Name">
                                                <div class="user-cell">
                                                    <div class="user-avatar <?php echo $user['role'] === 'admin' ? 'purple' : 'blue'; ?>">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                                </div>
                                            </td>
                                            <td data-label="Email"><span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span></td>
                                            <td data-label="Role">
                                                <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'purple' : ($user['role'] === 'donor' ? 'success' : 'info'); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                                </span>
                                            </td>
                                            <td data-label="Joined"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td data-label="Actions">
                                                <div class="table-actions">
                                                    <form action="admin.php?section=users" method="POST" class="inline-form" onsubmit="return confirm('Delete this user?');">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- ============================== -->
            <!-- CONTACT MESSAGES SECTION      -->
            <!-- ============================== -->
            <?php elseif ($section === 'contact_messages'): ?>

                <div class="section-header">
                    <div>
                        <h1>Contact Messages</h1>
                        <p>Manage incoming inquiries from the contact form.</p>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-envelope"></i> Inbox</h3>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Name</th>
                                        <th>Email / Phone</th>
                                        <th>Subject</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
                                    $sn = 1;
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                        <tr>
                                            <td data-label="#"><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                            <td data-label="Name"><span class="user-name"><?= htmlspecialchars($row['name']); ?></span></td>
                                            <td data-label="Email / Phone">
                                                <div class="user-email"><?= htmlspecialchars($row['email']); ?></div>
                                                <div style="font-size:12px;color:var(--admin-text-muted);"><?= htmlspecialchars($row['phone']); ?></div>
                                            </td>
                                            <td data-label="Subject"><strong><?= htmlspecialchars($row['subject']); ?></strong></td>
                                            <td data-label="Message" style="max-width:260px;font-size:13px;">
                                                <div style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                                    <?= nl2br(htmlspecialchars($row['message'])); ?>
                                                </div>
                                            </td>
                                            <td data-label="Status">
                                                <?php if ($row['status'] === 'Unread'): ?>
                                                    <span class="badge badge-danger">Unread</span>
                                                <?php elseif ($row['status'] === 'Read'): ?>
                                                    <span class="badge badge-info">Read</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Replied</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Date" style="white-space:nowrap;font-size:13px;"><?= date('d M Y h:i A', strtotime($row['created_at'])); ?></td>
                                            <td data-label="Actions">
                                                <div class="table-actions">
                                                    <?php if ($row['status'] !== 'Read' && $row['status'] !== 'Replied'): ?>
                                                        <form action="admin.php?section=contact_messages" method="POST" class="inline-form">
                                                            <input type="hidden" name="action" value="mark_contact_read">
                                                            <input type="hidden" name="message_id" value="<?= $row['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline"><i class="fa-regular fa-eye"></i> Read</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($row['status'] !== 'Replied'): ?>
                                                        <form action="admin.php?section=contact_messages" method="POST" class="inline-form">
                                                            <input type="hidden" name="action" value="mark_contact_replied">
                                                            <input type="hidden" name="message_id" value="<?= $row['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary"><i class="fa-regular fa-check-circle"></i> Replied</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form action="admin.php?section=contact_messages" method="POST" class="inline-form" onsubmit="return confirm('Delete this message permanently?');">
                                                        <input type="hidden" name="action" value="delete_contact_message">
                                                        <input type="hidden" name="message_id" value="<?= $row['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fa-regular fa-trash-can"></i> Del</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- ============================== -->
            <!-- DONATIONS SECTION             -->
            <!-- ============================== -->
            <?php elseif ($section === 'donations'): ?>

                <div class="section-header">
                    <div>
                        <h1>Donation Verification</h1>
                        <p>Review, approve, or reject donation submissions. Only approved donations appear publicly.</p>
                    </div>
                </div>

                <!-- Pending Reviews -->
                <div class="admin-card" style="margin-bottom: 24px;">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-clock"></i> Pending Review <span class="badge badge-warning" style="margin-left:8px;"><?php echo count($pending_donations); ?></span></h3>
                    </div>
                    <div class="admin-card-body">

                        <?php if (empty($pending_donations)): ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <h3>No donations waiting for review</h3>
                                <p>New donation submissions will appear here for your approval.</p>
                            </div>
                        <?php else: ?>
                            <div class="donation-review-list">
                                <?php $sn = 1; foreach ($pending_donations as $donation): ?>
                                    <div class="donation-review-card">
                                        <div class="review-card-top">
                                            <div>
                                                <h4><span class="badge badge-neutral" style="margin-right:8px;">#<?php echo $sn++; ?></span><i class="fa-solid fa-utensils" style="color:var(--admin-primary);margin-right:6px;"></i> <?php echo htmlspecialchars($donation['food_item']); ?></h4>
                                                <div class="review-card-meta">
                                                    <span><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($donation['donor_name']); ?></span>
                                                    <span><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($donation['donor_email']); ?></span>
                                                    <span><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($donation['donor_phone'] ?: $donation['phone']); ?></span>
                                                    <span><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y H:i', strtotime($donation['created_at'])); ?></span>
                                                </div>
                                            </div>
                                            <span class="badge badge-danger"><i class="fa-solid fa-hourglass-half"></i> Pending Review</span>
                                        </div>

                                        <div class="review-card-body">
                                            <div class="detail-item"><strong>Quantity:</strong> <?php echo htmlspecialchars($donation['quantity']); ?></div>
                                            <div class="detail-item"><strong>Expiry:</strong> <span class="countdown-badge" data-expiry="<?php echo $donation['expiry_time']; ?>">⏳ Loading...</span></div>
                                            <div class="detail-item"><strong>Pickup:</strong> <?php echo htmlspecialchars($donation['pickup_address']); ?></div>
                                            <div class="detail-item" style="grid-column:1/-1;"><strong>Donor Address:</strong> <?php echo htmlspecialchars($donation['donor_address']); ?></div>
                                            <?php if (!empty($donation['description'])): ?>
                                                <div class="detail-item" style="grid-column:1/-1;"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($donation['description'])); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($donation['image_path']) || !empty($donation['video_path'])): ?>
                                            <div class="review-card-media">
                                                <?php if (!empty($donation['image_path'])): ?>
                                                    <div>
                                                        <div style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fa-solid fa-image"></i> Photo</div>
                                                        <img src="../<?php echo htmlspecialchars($donation['image_path']); ?>" alt="Donation image">
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($donation['video_path'])): ?>
                                                    <div>
                                                        <div style="font-size:11px;font-weight:700;color:var(--admin-text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fa-solid fa-video"></i> Video</div>
                                                        <video controls>
                                                            <source src="../<?php echo htmlspecialchars($donation['video_path']); ?>" type="video/mp4">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="review-card-actions">
                                            <form action="admin.php?section=donations" method="POST" class="inline-form" style="flex-wrap:wrap;gap:8px;">
                                                <input type="hidden" name="action" value="approve_donation">
                                                <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                                <input type="text" name="verification_note" class="form-control" placeholder="Approval note (optional)" style="min-width:160px;">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i> Approve</button>
                                            </form>
                                            <form action="admin.php?section=donations" method="POST" class="inline-form" style="flex-wrap:wrap;gap:8px;" onsubmit="return confirm('Reject this donation?');">
                                                <input type="hidden" name="action" value="reject_donation">
                                                <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                                <input type="text" name="verification_note" class="form-control" placeholder="Rejection reason" style="min-width:160px;">
                                                <button type="submit" class="btn btn-sm btn-secondary"><i class="fa-solid fa-xmark"></i> Reject</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- All Reviewed Donations -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-list-check"></i> All Donations</h3>
                    </div>
                    <div class="admin-card-body" style="padding:0;">
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Donor</th>
                                        <th>Food Item</th>
                                        <th>Status</th>
                                        <th>Verification</th>
                                        <th>Review Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1; foreach ($all_donations as $donation): ?>
                                        <tr>
                                            <td data-label="#"><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                            <td data-label="Donor">
                                                <div class="user-cell">
                                                    <div class="user-avatar"><?php echo strtoupper(substr($donation['donor_name'], 0, 1)); ?></div>
                                                    <span class="user-name"><?php echo htmlspecialchars($donation['donor_name']); ?></span>
                                                </div>
                                            </td>
                                            <td data-label="Food Item"><strong><?php echo htmlspecialchars($donation['food_item']); ?></strong></td>
                                            <td data-label="Status"><span class="status-tag <?php echo $donation['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $donation['status'])); ?></span></td>
                                            <td data-label="Verification">
                                                <span class="badge badge-<?php echo $donation['verification_status'] === 'approved' ? 'success' : ($donation['verification_status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                                    <?php echo ucfirst($donation['verification_status']); ?>
                                                </span>
                                            </td>
                                            <td data-label="Review Note" style="color:var(--admin-text-muted);font-size:13px;"><?php echo htmlspecialchars($donation['verification_note'] ?? '&mdash;'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- ============================== -->
            <!-- CMS SECTION                   -->
            <!-- ============================== -->
            <?php elseif ($section === 'cms'): ?>

                <div class="section-header">
                    <div>
                        <h1>Content Management System</h1>
                        <p>Manage content for your homepage and about page from a single interface.</p>
                    </div>
                </div>

                <!-- CMS Tab Navigation -->
                <div class="cms-tabs">
                    <a href="admin.php?section=cms&cms_tab=homepage" class="cms-tab <?php echo $cms_tab === 'homepage' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-house"></i> Homepage
                    </a>
                    <a href="admin.php?section=cms&cms_tab=about" class="cms-tab <?php echo $cms_tab === 'about' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-circle-info"></i> About Page
                    </a>
                </div>

                <!-- ===== HOMEPAGE CMS FORM ===== -->
                <?php if ($cms_tab === 'homepage'): ?>

                <form action="admin.php?section=cms&cms_tab=homepage" method="POST">
                    <input type="hidden" name="save_homepage" value="1">

                    <!-- HERO SECTION -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-house"></i>
                            <h2>Hero Section</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Hero Heading</label>
                                    <input type="text" name="hero_heading" class="form-control" value="<?= htmlspecialchars($home['hero_heading']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Hero Sub Heading</label>
                                    <textarea name="hero_subheading" rows="4" class="form-control"><?= htmlspecialchars($home['hero_subheading']); ?></textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Button 1 Text</label>
                                    <input type="text" name="hero_button1_text" class="form-control" value="<?= htmlspecialchars($home['hero_button1_text']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Button 1 Link</label>
                                    <input type="text" name="hero_button1_link" class="form-control" value="<?= htmlspecialchars($home['hero_button1_link']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Button 2 Text</label>
                                    <input type="text" name="hero_button2_text" class="form-control" value="<?= htmlspecialchars($home['hero_button2_text']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Button 2 Link</label>
                                    <input type="text" name="hero_button2_link" class="form-control" value="<?= htmlspecialchars($home['hero_button2_link']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- HOW SAYOG WORKS -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-gears"></i>
                            <h2>How Sayog Works</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Section Title</label>
                                    <input type="text" name="works_title" class="form-control" value="<?= htmlspecialchars($home['works_title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Section Description</label>
                                    <textarea name="works_description" rows="4" class="form-control"><?= htmlspecialchars($home['works_description']); ?></textarea>
                                </div>
                            </div>

                            <hr style="margin:24px 0;border:none;border-top:1px solid var(--admin-border);">

                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                                <!-- Step 1 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Step 1</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="work1_icon" class="form-control" placeholder="fas fa-user-plus" value="<?= htmlspecialchars($home['work1_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Heading</label>
                                        <input type="text" name="work1_heading" class="form-control" value="<?= htmlspecialchars($home['work1_heading']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="4" name="work1_description" class="form-control"><?= htmlspecialchars($home['work1_description']); ?></textarea>
                                    </div>
                                </div>

                                <!-- Step 2 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Step 2</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="work2_icon" class="form-control" placeholder="fas fa-hand-holding-heart" value="<?= htmlspecialchars($home['work2_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Heading</label>
                                        <input type="text" name="work2_heading" class="form-control" value="<?= htmlspecialchars($home['work2_heading']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="4" name="work2_description" class="form-control"><?= htmlspecialchars($home['work2_description']); ?></textarea>
                                    </div>
                                </div>

                                <!-- Step 3 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Step 3</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="work3_icon" class="form-control" placeholder="fas fa-box-open" value="<?= htmlspecialchars($home['work3_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Heading</label>
                                        <input type="text" name="work3_heading" class="form-control" value="<?= htmlspecialchars($home['work3_heading']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="4" name="work3_description" class="form-control"><?= htmlspecialchars($home['work3_description']); ?></textarea>
                                    </div>
                                </div>

                                <!-- Step 4 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Step 4</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="work4_icon" class="form-control" placeholder="fas fa-check-circle" value="<?= htmlspecialchars($home['work4_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Heading</label>
                                        <input type="text" name="work4_heading" class="form-control" value="<?= htmlspecialchars($home['work4_heading']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="4" name="work4_description" class="form-control"><?= htmlspecialchars($home['work4_description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QUICK ACTIONS -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-bolt"></i>
                            <h2>Quick Actions</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Section Title</label>
                                    <input type="text" name="quick_title" class="form-control" value="<?= htmlspecialchars($home['quick_title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Section Description</label>
                                    <textarea rows="4" name="quick_description" class="form-control"><?= htmlspecialchars($home['quick_description']); ?></textarea>
                                </div>
                            </div>

                            <hr style="margin:24px 0;border:none;border-top:1px solid var(--admin-border);">

                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                                <!-- Quick Action 1 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Quick Action 1</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="quick1_icon" class="form-control" placeholder="fas fa-user-plus" value="<?= htmlspecialchars($home['quick1_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="quick1_title" class="form-control" value="<?= htmlspecialchars($home['quick1_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="quick1_description" class="form-control"><?= htmlspecialchars($home['quick1_description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Button Text</label>
                                        <input type="text" name="quick1_button" class="form-control" value="<?= htmlspecialchars($home['quick1_button']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Button Link</label>
                                        <input type="text" name="quick1_link" class="form-control" value="<?= htmlspecialchars($home['quick1_link']); ?>">
                                    </div>
                                </div>

                                <!-- Quick Action 2 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Quick Action 2</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="quick2_icon" class="form-control" placeholder="fas fa-right-to-bracket" value="<?= htmlspecialchars($home['quick2_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="quick2_title" class="form-control" value="<?= htmlspecialchars($home['quick2_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="quick2_description" class="form-control"><?= htmlspecialchars($home['quick2_description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Button Text</label>
                                        <input type="text" name="quick2_button" class="form-control" value="<?= htmlspecialchars($home['quick2_button']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Button Link</label>
                                        <input type="text" name="quick2_link" class="form-control" value="<?= htmlspecialchars($home['quick2_link']); ?>">
                                    </div>
                                </div>

                                <!-- Quick Action 3 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Quick Action 3</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="quick3_icon" class="form-control" placeholder="fas fa-bowl-food" value="<?= htmlspecialchars($home['quick3_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="quick3_title" class="form-control" value="<?= htmlspecialchars($home['quick3_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="quick3_description" class="form-control"><?= htmlspecialchars($home['quick3_description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Button Text</label>
                                        <input type="text" name="quick3_button" class="form-control" value="<?= htmlspecialchars($home['quick3_button']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Button Link</label>
                                        <input type="text" name="quick3_link" class="form-control" value="<?= htmlspecialchars($home['quick3_link']); ?>">
                                    </div>
                                </div>

                                <!-- Quick Action 4 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Quick Action 4</h3>
                                    <div class="form-group">
                                        <label>Icon</label>
                                        <input type="text" name="quick4_icon" class="form-control" placeholder="fas fa-envelope" value="<?= htmlspecialchars($home['quick4_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="quick4_title" class="form-control" value="<?= htmlspecialchars($home['quick4_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="quick4_description" class="form-control"><?= htmlspecialchars($home['quick4_description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Button Text</label>
                                        <input type="text" name="quick4_button" class="form-control" value="<?= htmlspecialchars($home['quick4_button']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Button Link</label>
                                        <input type="text" name="quick4_link" class="form-control" value="<?= htmlspecialchars($home['quick4_link']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER SECTION -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-shoe-prints"></i>
                            <h2>Footer Section</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="footer_phone" class="form-control" value="<?= htmlspecialchars($home['footer_phone']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" name="footer_email" class="form-control" value="<?= htmlspecialchars($home['footer_email']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Office Address</label>
                                    <input type="text" name="footer_address" class="form-control" value="<?= htmlspecialchars($home['footer_address']); ?>">
                                </div>
                                <div class="form-group form-full">
                                    <label>Footer Description</label>
                                    <textarea rows="4" name="footer_description" class="form-control"><?= htmlspecialchars($home['footer_description']); ?></textarea>
                                </div>
                            </div>

                            <hr style="margin:24px 0;border:none;border-top:1px solid var(--admin-border);">

                            <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;color:var(--admin-text);">Social Media Links</h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fa-brands fa-facebook" style="color:#1877f2;"></i> Facebook</label>
                                    <input type="text" name="facebook" class="form-control" value="<?= htmlspecialchars($home['facebook']); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-brands fa-instagram" style="color:#e4405f;"></i> Instagram</label>
                                    <input type="text" name="instagram" class="form-control" value="<?= htmlspecialchars($home['instagram']); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-brands fa-whatsapp" style="color:#25d366;"></i> WhatsApp</label>
                                    <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($home['whatsapp']); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-brands fa-linkedin" style="color:#0a66c2;"></i> LinkedIn</label>
                                    <input type="text" name="linkedin" class="form-control" value="<?= htmlspecialchars($home['linkedin']); ?>">
                                </div>
                            </div>

                            <div class="form-group" style="margin-top:8px;">
                                <label>Copyright Text</label>
                                <input type="text" name="copyright" class="form-control" value="<?= htmlspecialchars($home['copyright']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;">
                        <button type="submit" class="btn btn-lg btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Save Homepage
                        </button>
                    </div>

                </form>

                <!-- ===== ABOUT PAGE CMS FORM ===== -->
                <?php elseif ($cms_tab === 'about'): ?>

                <form action="admin.php?section=cms&cms_tab=about" method="POST">
                    <input type="hidden" name="save_aboutpage" value="1">

                    <!-- HERO SECTION -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-circle-info"></i>
                            <h2>Hero Section</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Badge Text</label>
                                    <input type="text" name="hero_badge" class="form-control" value="<?= htmlspecialchars($about['hero_badge']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Hero Title</label>
                                    <input type="text" name="hero_title" class="form-control" value="<?= htmlspecialchars($about['hero_title']); ?>">
                                </div>
                            </div>
                            <div class="form-group form-full">
                                <label>Hero Description (HTML allowed)</label>
                                <textarea name="hero_description" rows="5" class="form-control"><?= htmlspecialchars($about['hero_description']); ?></textarea>
                            </div>

                            <hr style="margin:24px 0;border:none;border-top:1px solid var(--admin-border);">

                            <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;color:var(--admin-text);">Feature Highlights</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Highlight 1</label>
                                    <input type="text" name="highlight1" class="form-control" value="<?= htmlspecialchars($about['highlight1']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Highlight 2</label>
                                    <input type="text" name="highlight2" class="form-control" value="<?= htmlspecialchars($about['highlight2']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Highlight 3</label>
                                    <input type="text" name="highlight3" class="form-control" value="<?= htmlspecialchars($about['highlight3']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MISSION CARD -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                            <h2>Mission Card</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Mission Title</label>
                                    <input type="text" name="mission_title" class="form-control" value="<?= htmlspecialchars($about['mission_title']); ?>">
                                </div>
                                <div class="form-group form-full">
                                    <label>Mission Description</label>
                                    <textarea name="mission_description" rows="4" class="form-control"><?= htmlspecialchars($about['mission_description']); ?></textarea>
                                </div>
                            </div>

                            <hr style="margin:24px 0;border:none;border-top:1px solid var(--admin-border);">

                            <h3 style="font-size:15px;font-weight:700;margin-bottom:16px;color:var(--admin-text);">Statistics</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Stat 1 Value</label>
                                    <input type="text" name="stat1_value" class="form-control" value="<?= htmlspecialchars($about['stat1_value']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Stat 1 Label</label>
                                    <input type="text" name="stat1_label" class="form-control" value="<?= htmlspecialchars($about['stat1_label']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Stat 2 Value</label>
                                    <input type="text" name="stat2_value" class="form-control" value="<?= htmlspecialchars($about['stat2_value']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Stat 2 Label</label>
                                    <input type="text" name="stat2_label" class="form-control" value="<?= htmlspecialchars($about['stat2_label']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Stat 3 Value</label>
                                    <input type="text" name="stat3_value" class="form-control" value="<?= htmlspecialchars($about['stat3_value']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Stat 3 Label</label>
                                    <input type="text" name="stat3_label" class="form-control" value="<?= htmlspecialchars($about['stat3_label']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANELS SECTION -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-layer-group"></i>
                            <h2>Info Panels</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Panel 1 Title</label>
                                    <input type="text" name="panel1_title" class="form-control" value="<?= htmlspecialchars($about['panel1_title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Panel 1 Description</label>
                                    <textarea name="panel1_description" rows="4" class="form-control"><?= htmlspecialchars($about['panel1_description']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Panel 2 Title</label>
                                    <input type="text" name="panel2_title" class="form-control" value="<?= htmlspecialchars($about['panel2_title']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Panel 2 Description</label>
                                    <textarea name="panel2_description" rows="4" class="form-control"><?= htmlspecialchars($about['panel2_description']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FEATURE CARDS -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-table-cells-large"></i>
                            <h2>Feature Cards</h2>
                        </div>
                        <div class="form-section-body">

                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                                <!-- Card 1 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Card 1</h3>
                                    <div class="form-group">
                                        <label>Icon Class</label>
                                        <input type="text" name="feature1_icon" class="form-control" placeholder="fas fa-hand-holding-heart" value="<?= htmlspecialchars($about['feature1_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="feature1_title" class="form-control" value="<?= htmlspecialchars($about['feature1_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="feature1_description" class="form-control"><?= htmlspecialchars($about['feature1_description']); ?></textarea>
                                    </div>
                                </div>

                                <!-- Card 2 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Card 2</h3>
                                    <div class="form-group">
                                        <label>Icon Class</label>
                                        <input type="text" name="feature2_icon" class="form-control" placeholder="fas fa-utensils" value="<?= htmlspecialchars($about['feature2_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="feature2_title" class="form-control" value="<?= htmlspecialchars($about['feature2_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="feature2_description" class="form-control"><?= htmlspecialchars($about['feature2_description']); ?></textarea>
                                    </div>
                                </div>

                                <!-- Card 3 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Card 3</h3>
                                    <div class="form-group">
                                        <label>Icon Class</label>
                                        <input type="text" name="feature3_icon" class="form-control" placeholder="fas fa-location-dot" value="<?= htmlspecialchars($about['feature3_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="feature3_title" class="form-control" value="<?= htmlspecialchars($about['feature3_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="feature3_description" class="form-control"><?= htmlspecialchars($about['feature3_description']); ?></textarea>
                                    </div>
                                </div>

                                <!-- Card 4 -->
                                <div style="background:#f8fafc;border-radius:12px;padding:20px;border:1px solid var(--admin-border);">
                                    <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;color:var(--admin-primary);">Card 4</h3>
                                    <div class="form-group">
                                        <label>Icon Class</label>
                                        <input type="text" name="feature4_icon" class="form-control" placeholder="fas fa-users" value="<?= htmlspecialchars($about['feature4_icon']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="feature4_title" class="form-control" value="<?= htmlspecialchars($about['feature4_title']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea rows="3" name="feature4_description" class="form-control"><?= htmlspecialchars($about['feature4_description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa-solid fa-shoe-prints"></i>
                            <h2>Footer</h2>
                        </div>
                        <div class="form-section-body">
                            <div class="form-group form-full">
                                <label>Copyright Text</label>
                                <input type="text" name="footer_copyright" class="form-control" value="<?= htmlspecialchars($about['footer_copyright']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px;">
                        <button type="submit" class="btn btn-lg btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Save About Page
                        </button>
                    </div>

                </form>

                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>
</div>

<!-- Mobile Sidebar Toggle Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    const openSidebar = () => {
        sidebar.classList.add('active');
        overlay.classList.add('active');
    };

    const closeSidebar = () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    };

    if (toggle) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.contains('active') ? closeSidebar() : openSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar?.classList.contains('active')) {
            closeSidebar();
        }
    });
});
</script>

</body>
</html>
