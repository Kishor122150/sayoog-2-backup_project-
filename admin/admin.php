<?php
require_once '../config.php';

// if (!is_admin_logged_in()) {
//     redirect('admin-login.php');
// }

$section = sanitize($_GET['section'] ?? 'dashboard');
$valid_sections = ['dashboard', 'users', 'products', 'cms', 'listing_requests', 'contact_messages',
    'smtp', 'donations', 'volunteers', 'volunteer_deliveries', 'team', 'chatbot'];
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
    // ── VOLUNTEER ACTIONS ──
    if ($action === 'approve_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        if ($vid > 0) {
            approve_volunteer($pdo, $vid, $_SESSION['user_id'] ?? 0);
            set_flash_message('success', 'Volunteer approved successfully.');
            redirect('admin.php?section=volunteers&vol_tab=approved');
        }
    }
    if ($action === 'reject_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        $reason = sanitize($_POST['reject_reason'] ?? 'Other');
        if ($vid > 0) {
            reject_volunteer($pdo, $vid, $reason);
            set_flash_message('warning', 'Volunteer rejected.');
            redirect('admin.php?section=volunteers&vol_tab=rejected');
        }
    }
    if ($action === 'suspend_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        $reason = sanitize($_POST['suspend_reason'] ?? 'Policy Violation');
        if ($vid > 0) {
            suspend_volunteer($pdo, $vid, $reason);
            set_flash_message('warning', 'Volunteer suspended.');
            redirect('admin.php?section=volunteers&vol_tab=suspended');
        }
    }
    if ($action === 'delete_volunteer') {
        $vid = intval($_POST['volunteer_id'] ?? 0);
        if ($vid > 0) {
            $pdo->prepare("DELETE FROM volunteers WHERE id = ?")->execute([$vid]);
            set_flash_message('success', 'Volunteer record deleted.');
            redirect('admin.php?section=volunteers');
        }
    }

    // ── TEAM MEMBER ACTIONS ──
    if ($action === 'create_team_member' || $action === 'update_team_member') {
        $name = sanitize($_POST['name'] ?? '');
        $role = sanitize($_POST['role'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $linkedin = sanitize($_POST['linkedin'] ?? '');
        $github = sanitize($_POST['github'] ?? '');
        $website = sanitize($_POST['website'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'active');
        // Handle file upload for photo
        $photo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            // Use server-side MIME detection for security
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $file_type = finfo_file($finfo, $_FILES['photo']['tmp_name']);
            finfo_close($finfo);
            if (in_array($file_type, $allowed)) {
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($_FILES['photo']['size'] <= $max_size) {
                    $teamDir = __DIR__ . '/../uploads/team';
                    if (!is_dir($teamDir)) mkdir($teamDir, 0755, true);
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'team_' . uniqid() . '_' . time() . '.' . $ext;
                    $dest = $teamDir . '/' . $filename;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                        $photo = 'uploads/team/' . $filename;
                    }
                } else {
                    $errors[] = 'Photo file size must be less than 5MB.';
                }
            } else {
                $errors[] = 'Photo must be a JPEG, PNG, GIF, or WebP image.';
            }
        }

        if (empty($name)) $errors[] = 'Name is required.';
        if (empty($role)) $errors[] = 'Role is required.';

        if (empty($errors)) {
            if ($action === 'create_team_member') {
                $stmt = $pdo->prepare("INSERT INTO team_members (name, role, bio, photo, email, linkedin, github, website, display_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $role, $bio, $photo ?: null, $email ?: null, $linkedin ?: null, $github ?: null, $website ?: null, $display_order, $status]);
                set_flash_message('success', 'Team member added successfully.');
                redirect('admin.php?section=team');
            } else {
                $member_id = intval($_POST['member_id'] ?? 0);
                // Keep existing photo if no new file uploaded
                if (!$photo && $member_id > 0) {
                    $stmt = $pdo->prepare("SELECT photo FROM team_members WHERE id = ?");
                    $stmt->execute([$member_id]);
                    $photo = $stmt->fetchColumn();
                } else {
                    // Delete old photo file when replacing
                    $stmt = $pdo->prepare("SELECT photo FROM team_members WHERE id = ?");
                    $stmt->execute([$member_id]);
                    $oldPhoto = $stmt->fetchColumn();
                    if ($oldPhoto) {
                        $oldPath = __DIR__ . '/../' . $oldPhoto;
                        if (file_exists($oldPath)) @unlink($oldPath);
                    }
                }
                $stmt = $pdo->prepare("UPDATE team_members SET name = ?, role = ?, bio = ?, photo = ?, email = ?, linkedin = ?, github = ?, website = ?, display_order = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $role, $bio, $photo ?: null, $email ?: null, $linkedin ?: null, $github ?: null, $website ?: null, $display_order, $status, $member_id]);
                set_flash_message('success', 'Team member updated successfully.');
                redirect('admin.php?section=team');
            }
        }
    }

    if ($action === 'delete_team_member') {
        $member_id = intval($_POST['member_id'] ?? 0);
        if ($member_id > 0) {
            // Delete photo file before removing record
            $stmt = $pdo->prepare("SELECT photo FROM team_members WHERE id = ?");
            $stmt->execute([$member_id]);
            $oldPhoto = $stmt->fetchColumn();
            if ($oldPhoto) {
                $oldPath = __DIR__ . '/../' . $oldPhoto;
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
            $stmt->execute([$member_id]);
            set_flash_message('success', 'Team member deleted.');
        }
        redirect('admin.php?section=team');
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
$volunteer_counts = get_volunteer_counts($pdo);
$volunteers_pending = get_volunteers_by_status($pdo, 'pending');
$volunteers_approved = get_volunteers_by_status($pdo, 'approved');
$volunteers_rejected = get_volunteers_by_status($pdo, 'rejected');
$volunteers_suspended = get_volunteers_by_status($pdo, 'suspended');
$team_members = $pdo->query("SELECT * FROM team_members WHERE status = 'active' ORDER BY display_order ASC, created_at DESC")->fetchAll();
$all_team_members = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC, created_at DESC")->fetchAll();
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

            <a href="admin.php?section=volunteers" class="<?php echo $section === 'volunteers' ? 'active' : ''; ?>">
                <i class="fa-solid fa-hand-holding-heart"></i> Volunteers
                <?php if (isset($volunteer_counts['pending']) && $volunteer_counts['pending'] > 0): ?>
                    <span class="nav-badge"><?php echo (int)$volunteer_counts['pending']; ?></span>
                <?php endif; ?>
            </a>

            <a href="admin.php?section=volunteer_deliveries" class="<?php echo $section === 'volunteer_deliveries' ? 'active' : ''; ?>">
                <i class="fa-solid fa-truck-fast"></i> Volunteer Deliveries
            </a>


            <a href="admin.php?section=team" class="<?php echo $section === 'team' ? 'active' : ''; ?>">
                <i class="fa-solid fa-people-group"></i> Team
            </a>

            <a href="admin.php?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Users
            </a>

            <a href="admin.php?section=contact_messages" class="<?php echo $section === 'contact_messages' ? 'active' : ''; ?>">
                <i class="fa-solid fa-envelope"></i> Contact Messages
            </a>

            <a href="admin.php?section=cms" class="<?php echo $section === 'cms' ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-lines"></i> CMS Editor
            </a>

            <a href="admin.php?section=smtp" class="<?php echo $section === 'smtp' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> SMTP Config
            </a>

            <div class="nav-section-label">Chatbot</div>

            <a href="admin.php?section=chatbot" class="<?php echo $section === 'chatbot' ? 'active' : ''; ?>">
                <i class="fa-solid fa-robot"></i> AI Chatbot
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
                        'volunteers' => 'Volunteer Management',
                        'smtp' => 'SMTP Configuration',
                        'chatbot' => 'AI Chatbot',
                        'volunteer_deliveries' => 'Volunteer Deliveries',
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
                                                        <button type="submit" class="btn btn-sm btn-danger" style="color:red"><i class="fa-solid fa-trash-can"></i> Delete</button>
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
                                                        <button type="submit" class="btn btn-sm btn-danger" style="color:red"><i class="fa-regular fa-trash-can"></i> Delete</button>
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

            <!-- ============================== -->
            <!-- VOLUNTEER MANAGEMENT SECTION   -->
            <!-- ============================== -->
            <?php elseif ($section === 'volunteers'):
                $vol_tab = sanitize($_GET['vol_tab'] ?? 'pending');
                $valid_vol_tabs = ['pending', 'approved', 'rejected', 'suspended', 'detail'];
                if (!in_array($vol_tab, $valid_vol_tabs)) $vol_tab = 'pending';
                $vol_detail_id = intval($_GET['detail_id'] ?? 0);

                $vol_list = [];
                $list_title = '';
                if ($vol_tab === 'pending') { $vol_list = $volunteers_pending; $list_title = 'Pending Applications'; }
                elseif ($vol_tab === 'approved') { $vol_list = $volunteers_approved; $list_title = 'Approved Volunteers'; }
                elseif ($vol_tab === 'rejected') { $vol_list = $volunteers_rejected; $list_title = 'Rejected Applications'; }
                elseif ($vol_tab === 'suspended') { $vol_list = $volunteers_suspended; $list_title = 'Suspended Volunteers'; }
            ?>

                <?php if ($vol_tab !== 'detail'): ?>
                <div class="section-header">
                    <div>
                        <h1><i class="fa-solid fa-hand-holding-heart"></i> Volunteer Management</h1>
                        <p>Review, verify, and manage volunteer applications</p>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
                    <div class="stat-card">
                        <div class="stat-card-icon green"><i class="fa-solid fa-clock"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$volunteer_counts['pending']; ?></div>
                            <div class="stat-card-label">Pending</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon blue"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$volunteer_counts['approved']; ?></div>
                            <div class="stat-card-label">Approved</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon amber"><i class="fa-solid fa-ban"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$volunteer_counts['rejected']; ?></div>
                            <div class="stat-card-label">Rejected</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon purple"><i class="fa-solid fa-pause"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$volunteer_counts['suspended']; ?></div>
                            <div class="stat-card-label">Suspended</div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="cms-tabs" style="margin-bottom:24px;">
                    <a href="admin.php?section=volunteers&vol_tab=pending" class="cms-tab <?php echo $vol_tab === 'pending' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clock"></i> Pending <span class="badge badge-warning" style="margin-left:6px;"><?php echo (int)$volunteer_counts['pending']; ?></span>
                    </a>
                    <a href="admin.php?section=volunteers&vol_tab=approved" class="cms-tab <?php echo $vol_tab === 'approved' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-circle-check"></i> Approved <span class="badge badge-success" style="margin-left:6px;"><?php echo (int)$volunteer_counts['approved']; ?></span>
                    </a>
                    <a href="admin.php?section=volunteers&vol_tab=rejected" class="cms-tab <?php echo $vol_tab === 'rejected' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-ban"></i> Rejected <span class="badge badge-danger" style="margin-left:6px;"><?php echo (int)$volunteer_counts['rejected']; ?></span>
                    </a>
                    <a href="admin.php?section=volunteers&vol_tab=suspended" class="cms-tab <?php echo $vol_tab === 'suspended' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-pause"></i> Suspended <span class="badge badge-warning" style="margin-left:6px;"><?php echo (int)$volunteer_counts['suspended']; ?></span>
                    </a>
                </div>

                <!-- Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-list"></i> <?php echo $list_title; ?></h3>
                        <span class="badge badge-info"><?php echo count($vol_list); ?> records</span>
                    </div>
                    <div class="admin-card-body" style="padding:0;">
                        <?php if (empty($vol_list)): ?>
                            <div class="empty-state" style="padding:40px 20px;">
                                <i class="fa-solid fa-inbox"></i>
                                <h3>No <?php echo strtolower($list_title); ?></h3>
                                <p>There are no volunteer applications in this category.</p>
                            </div>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Volunteer</th>
                                        <th>Contact</th>
                                        <th>Vehicle</th>
                                        <th>Documents</th>
                                        <th>Applied</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1; foreach ($vol_list as $v): ?>
                                    <tr>
                                        <td data-label="#"><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                        <td data-label="Volunteer">
                                            <div class="user-cell">
                                                <div class="user-avatar"><?php echo strtoupper(substr($v['full_name'],0,1)); ?></div>
                                                <div>
                                                    <div class="user-name"><?php echo htmlspecialchars($v['full_name']); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($v['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Contact" style="font-size:13px;">
                                            <?php echo htmlspecialchars($v['phone']); ?>
                                        </td>
                                        <td data-label="Vehicle">
                                            <span class="badge badge-info"><?php echo ucfirst($v['vehicle_type']); ?></span>
                                            <div style="font-size:11px;color:var(--admin-text-muted);margin-top:2px;"><?php echo $v['delivery_radius']; ?>km</div>
                                        </td>
                                        <td data-label="Documents">
                                            <div style="display:flex;gap:3px;">
                                                <?php $hasDoc = false; ?>
                                                <?php foreach (['citizenship_front','citizenship_back','national_id','college_id','driving_license'] as $dk): ?>
                                                    <?php if (!empty($v[$dk])): $hasDoc = true; ?>
                                                        <a href="../<?php echo htmlspecialchars($v[$dk]); ?>" target="_blank" title="<?php echo $dk; ?>" style="padding:2px 6px;background:var(--admin-bg-light);border-radius:4px;font-size:10px;color:var(--admin-text);text-decoration:none;"><i class="fa-solid fa-file"></i></a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (!$hasDoc): ?><span style="font-size:11px;color:var(--admin-text-muted);">None</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Applied" style="white-space:nowrap;font-size:13px;"><?php echo date('d M Y',strtotime($v['created_at'])); ?></td>
                                        <td data-label="Actions">
                                            <div class="table-actions">
                                                <a href="admin.php?section=volunteers&vol_tab=detail&detail_id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline" title="View Details"><i class="fa-solid fa-eye"></i></a>
                                                <?php if ($v['status'] === 'pending'): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="approve_volunteer">
                                                        <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fa-solid fa-check"></i></button>
                                                    </form>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reject?');">
                                                        <input type="hidden" name="action" value="reject_volunteer">
                                                        <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                                                        <input type="hidden" name="reject_reason" value="Other">
                                                        <button type="submit" class="btn btn-sm btn-secondary" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                                    </form>
                                                <?php elseif ($v['status'] === 'approved'): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Suspend?');">
                                                        <input type="hidden" name="action" value="suspend_volunteer">
                                                        <input type="hidden" name="volunteer_id" value="<?php echo $v['id']; ?>">
                                                        <input type="hidden" name="suspend_reason" value="Policy Violation">
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Suspend"><i class="fa-solid fa-pause"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- ============================== -->
            <!-- CMS SECTION                   -->
            <!-- ============================== -->

                <?php endif; ?>
            <?php if ($vol_tab === 'detail' && $vol_detail_id > 0):
                $vol = get_volunteer_by_id($pdo, $vol_detail_id);
                if (!$vol): ?>
                    <div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i><h3>Volunteer not found</h3></div>
                <?php else: ?>
                <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div>
                        <h1 style="display:flex;align-items:center;gap:12px;">
                            <a href="admin.php?section=volunteers" class="btn btn-sm btn-outline"><i class="fa-solid fa-arrow-left"></i></a>
                            <?php echo htmlspecialchars($vol['full_name']); ?>
                        </h1>
                        <p>Volunteer application details &amp; verification</p>
                    </div>
                    <span class="badge badge-<?php echo $vol['status']==='approved'?'success':($vol['status']==='rejected'||$vol['status']==='suspended'?'danger':'warning'); ?>" style="font-size:14px;padding:8px 20px;"><?php echo strtoupper($vol['status']); ?></span>
                </div>

                <div class="admin-card" style="margin-bottom:20px;">
                    <div class="admin-card-header"><h3><i class="fa-solid fa-user"></i> Personal Information</h3></div>
                    <div class="admin-card-body">
                        <table class="modern-table" style="border:none;">
                            <tr><td style="width:160px;font-weight:600;">Full Name</td><td><?php echo htmlspecialchars($vol['full_name']); ?></td></tr>
                            <tr><td style="font-weight:600;">Email</td><td><?php echo htmlspecialchars($vol['email']); ?></td></tr>
                            <tr><td style="font-weight:600;">Phone</td><td><?php echo htmlspecialchars($vol['phone']); ?></td></tr>
                            <tr><td style="font-weight:600;">Date of Birth</td><td><?php echo htmlspecialchars($vol['date_of_birth'] ?? 'N/A'); ?></td></tr>
                            <tr><td style="font-weight:600;">Gender</td><td><?php echo ucfirst($vol['gender'] ?? 'N/A'); ?></td></tr>
                            <tr><td style="font-weight:600;">Emergency Contact</td><td><?php echo htmlspecialchars($vol['emergency_contact'] ?? 'N/A'); ?></td></tr>
                            <tr><td style="font-weight:600;">Address</td><td><?php echo htmlspecialchars($vol['address'] ?? 'N/A'); ?>, Wd-<?php echo htmlspecialchars($vol['ward_number'] ?? ''); ?>, <?php echo htmlspecialchars($vol['municipality'] ?? ''); ?>, <?php echo htmlspecialchars($vol['district'] ?? ''); ?></td></tr>
                        </table>
                    </div>
                </div>

                <div class="admin-card" style="margin-bottom:20px;">
                    <div class="admin-card-header"><h3><i class="fa-solid fa-truck"></i> Vehicle &amp; Availability</h3></div>
                    <div class="admin-card-body">
                        <table class="modern-table" style="border:none;">
                            <tr><td style="width:160px;font-weight:600;">Vehicle</td><td><span class="badge badge-info"><?php echo ucfirst($vol['vehicle_type']); ?></span> &middot; <?php echo $vol['delivery_radius']; ?>km</td></tr>
                            <tr><td style="font-weight:600;">Availability</td><td><?php echo str_replace(',', ', ', ucfirst($vol['availability'])); ?></td></tr>
                            <tr><td style="font-weight:600;">Languages</td><td><?php echo htmlspecialchars($vol['languages'] ?? 'N/A'); ?></td></tr>
                            <tr><td style="font-weight:600;">Occupation</td><td><?php echo htmlspecialchars($vol['occupation'] ?? 'N/A'); ?></td></tr>
                        </table>
                    </div>
                </div>

                <div class="admin-card" style="margin-bottom:20px;">
                    <div class="admin-card-header"><h3><i class="fa-solid fa-file-shield"></i> Identity Documents</h3></div>
                    <div class="admin-card-body">
                        <table class="modern-table" style="border:none;">
                            <?php $docF = ['citizenship_front'=>'Citizenship Front','citizenship_back'=>'Citizenship Back','national_id'=>'National ID','college_id'=>'College ID','driving_license'=>'Driving License']; ?>
                            <?php foreach ($docF as $dk => $dl): ?>
                                <tr><td style="width:160px;font-weight:600;"><?php echo $dl; ?></td>
                                    <td><?php if (!empty($vol[$dk])): ?>
                                        <a href="../<?php echo htmlspecialchars($vol[$dk]); ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fa-solid fa-eye"></i> View</a>
                                        <a href="../<?php echo htmlspecialchars($vol[$dk]); ?>" download class="btn btn-sm btn-outline"><i class="fa-solid fa-download"></i> Download</a>
                                    <?php else: ?><span style="color:#94a3b8;">Not uploaded</span><?php endif; ?></td></tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>

                <div class="admin-card" style="margin-bottom:20px;">
                    <div class="admin-card-header"><h3><i class="fa-solid fa-clock"></i> Application Info</h3></div>
                    <div class="admin-card-body">
                        <table class="modern-table" style="border:none;">
                            <tr><td style="width:160px;font-weight:600;">Applied</td><td><?php echo date('d M Y h:i A', strtotime($vol['created_at'])); ?></td></tr>
                            <tr><td style="font-weight:600;">Volunteer ID</td><td><?php echo htmlspecialchars($vol['volunteer_id'] ?? '-'); ?></td></tr>
                            <?php if (!empty($vol['rejected_reason'])): ?>
                                <tr><td style="font-weight:600;color:#dc2626;">Rejection</td><td style="color:#dc2626;"><?php echo htmlspecialchars($vol['rejected_reason']); ?></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;padding:20px;background:#fff;border-radius:14px;border:1px solid var(--admin-border);margin-bottom:24px;">
                    <?php if ($vol['status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="approve_volunteer">
                            <input type="hidden" name="volunteer_id" value="<?php echo $vol['id']; ?>">
                            <button type="submit" class="btn btn-success" style="padding:10px 24px;"><i class="fa-solid fa-check"></i> Approve Volunteer</button>
                        </form>
                        <form method="POST" style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;" onsubmit="return confirm('Reject this applicant?');">
                            <input type="hidden" name="action" value="reject_volunteer">
                            <input type="hidden" name="volunteer_id" value="<?php echo $vol['id']; ?>">
                            <select name="reject_reason" style="padding:8px 12px;border:1px solid var(--admin-border);border-radius:8px;font-size:13px;min-width:160px;" required>
                                <option value="">Select reason...</option>
                                <option value="Incomplete Information">Incomplete Info</option>
                                <option value="Invalid Documents">Invalid Documents</option>
                                <option value="Duplicate Account">Duplicate</option>
                                <option value="Fake Information">Fake Info</option>
                                <option value="Other">Other</option>
                            </select>
                            <button type="submit" class="btn btn-secondary" style="padding:10px 24px;"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </form>
                    <?php elseif ($vol['status'] === 'approved'): ?>
                        <form method="POST" style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap;" onsubmit="return confirm('Suspend this volunteer?');">
                            <input type="hidden" name="action" value="suspend_volunteer">
                            <input type="hidden" name="volunteer_id" value="<?php echo $vol['id']; ?>">
                            <select name="suspend_reason" style="padding:8px 12px;border:1px solid var(--admin-border);border-radius:8px;font-size:13px;min-width:160px;" required>
                                <option value="">Select reason...</option>
                                <option value="Misconduct">Misconduct</option>
                                <option value="Fake Delivery">Fake Delivery</option>
                                <option value="Policy Violation">Policy Violation</option>
                            </select>
                            <button type="submit" class="btn btn-warning" style="padding:10px 24px;"><i class="fa-solid fa-pause"></i> Suspend</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this record?');">
                        <input type="hidden" name="action" value="delete_volunteer">
                        <input type="hidden" name="volunteer_id" value="<?php echo $vol['id']; ?>">
                        <button type="submit" class="btn btn-danger" style="padding:10px 24px;"><i class="fa-solid fa-trash"></i> Delete Record</button>
                    </form>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ============================== -->
            <!-- TEAM MANAGEMENT SECTION        -->
            <!-- ============================== -->
            <!-- ============================== -->
            <!-- VOLUNTEER DELIVERIES SECTION   -->
            <!-- ============================== -->
            <?php elseif ($section === 'volunteer_deliveries'):
                $del_status = sanitize($_GET['del_status'] ?? '');
                $deliveries = get_all_volunteer_deliveries($pdo, $del_status, 100);
                $vol_activity = get_volunteer_activity_stats($pdo);
            ?>
                <div class="section-header">
                    <div>
                        <h1><i class="fa-solid fa-truck-fast"></i> Volunteer Deliveries</h1>
                        <p>Monitor all volunteer delivery requests, assignments, and activity tracking.</p>
                    </div>
                </div>

                <!-- Stats Dashboard -->
                <div class="stats-grid" style="grid-template-columns:repeat(5,1fr);margin-bottom:24px;">
                    <div class="stat-card">
                        <div class="stat-card-icon blue"><i class="fa-solid fa-truck"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$vol_activity['total']; ?></div>
                            <div class="stat-card-label">Total Deliveries</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon amber"><i class="fa-solid fa-spinner"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$vol_activity['active']; ?></div>
                            <div class="stat-card-label">Active Now</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon green"><i class="fa-solid fa-check-double"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$vol_activity['delivered']; ?></div>
                            <div class="stat-card-label">Completed</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon purple"><i class="fa-solid fa-user-check"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$vol_activity['accepted']; ?></div>
                            <div class="stat-card-label">Accepted by Volunteers</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon red"><i class="fa-solid fa-ban"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo (int)$vol_activity['cancelled']; ?></div>
                            <div class="stat-card-label">Cancelled</div>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="cms-tabs" style="margin-bottom:24px;">
                    <a href="admin.php?section=volunteer_deliveries" class="cms-tab <?php echo empty($del_status) ? 'active' : ''; ?>"><i class="fa-solid fa-list"></i> All</a>
                    <a href="admin.php?section=volunteer_deliveries&del_status=assigned" class="cms-tab <?php echo $del_status==='assigned'?'active':''; ?>"><i class="fa-solid fa-clock"></i> Assigned</a>
                    <a href="admin.php?section=volunteer_deliveries&del_status=accepted" class="cms-tab <?php echo $del_status==='accepted'?'active':''; ?>"><i class="fa-solid fa-check-circle"></i> Accepted</a>
                    <a href="admin.php?section=volunteer_deliveries&del_status=picked_up" class="cms-tab <?php echo $del_status==='picked_up'?'active':''; ?>"><i class="fa-solid fa-box-open"></i> Picked Up</a>
                    <a href="admin.php?section=volunteer_deliveries&del_status=in_transit" class="cms-tab <?php echo $del_status==='in_transit'?'active':''; ?>"><i class="fa-solid fa-truck"></i> In Transit</a>
                    <a href="admin.php?section=volunteer_deliveries&del_status=delivered" class="cms-tab <?php echo $del_status==='delivered'?'active':''; ?>"><i class="fa-solid fa-check-double"></i> Delivered</a>
                </div>

                <!-- Main Delivery Records Table -->
                <div class="admin-card" style="margin-bottom:24px;">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-list"></i> Delivery Records — Who Accepted Whose Request?</h3>
                        <span class="badge badge-info"><?php echo count($deliveries); ?> records</span>
                    </div>
                    <div class="admin-card-body" style="padding:0;">
                        <?php if (empty($deliveries)): ?>
                            <div class="empty-state" style="padding:40px 20px;">
                                <i class="fa-solid fa-inbox"></i>
                                <h3>No deliveries found</h3>
                                <p>There are no volunteer delivery records matching this filter.</p>
                            </div>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Food Item</th>
                                        <th>Donor → Consumer</th>
                                        <th>Volunteer</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Timeline</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn=1; foreach ($deliveries as $d):
                                        $statusColors = ['assigned'=>'#f59e0b','accepted'=>'#3b82f6','picked_up'=>'#8b5cf6','in_transit'=>'#06b6d4','delivered'=>'#059669','cancelled'=>'#ef4444'];
                                        $statusIcons = ['assigned'=>'fa-clock','accepted'=>'fa-check-circle','picked_up'=>'fa-box-open','in_transit'=>'fa-truck','delivered'=>'fa-check-double','cancelled'=>'fa-ban'];
                                        $color = $statusColors[$d['status']] ?? '#6b7280';
                                        $icon = $statusIcons[$d['status']] ?? 'fa-circle';
                                        // Build timeline status indicators
                                        $timeline_steps = ['assigned','accepted','picked_up','in_transit','delivered'];
                                        $current_idx = array_search($d['status'], $timeline_steps);
                                    ?>
                                    <tr>
                                        <td><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($d['food_item']); ?></strong></td>
                                        <td>
                                            <div style="display:flex;flex-direction:column;gap:2px;font-size:13px;">
                                                <span><i class="fa-solid fa-user" style="color:#059669;width:16px;"></i> <?php echo htmlspecialchars($d['donor_name'] ?? 'N/A'); ?></span>
                                                <span style="color:#9ca3af;"><i class="fa-solid fa-arrow-down" style="font-size:10px;"></i></span>
                                                <span><i class="fa-solid fa-user" style="color:#3b82f6;width:16px;"></i> <?php echo htmlspecialchars($d['consumer_name'] ?? 'N/A'); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($d['volunteer_name'])): ?>
                                                <span style="font-weight:600;color:var(--admin-text);"><?php echo htmlspecialchars($d['volunteer_name']); ?></span>
                                            <?php else: ?>
                                                <span style="color:#9ca3af;font-style:italic;">Not yet assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo !empty($d['vehicle_type']) ? ucfirst($d['vehicle_type']) : '—'; ?></td>
                                        <td><span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;background:<?php echo $color; ?>20;color:<?php echo $color; ?>;"><i class="fa-solid <?php echo $icon; ?>" style="font-size:10px;"></i> <?php echo ucfirst(str_replace('_',' ',$d['status'])); ?></span></td>
                                        <td style="font-size:12px;">
                                            <div style="display:flex;align-items:center;gap:2px;">
                                                <?php foreach ($timeline_steps as $i => $step): 
                                                    $stepColor = ($i <= $current_idx) ? $statusColors[$step] : '#d1d5db';
                                                ?>
                                                    <span style="width:14px;height:14px;border-radius:50%;background:<?php echo $stepColor; ?>;display:inline-flex;align-items:center;justify-content:center;">
                                                        <i class="fa-solid fa-check" style="font-size:7px;color:#fff;"></i>
                                                    </span>
                                                    <?php if ($i < count($timeline_steps) - 1): ?>
                                                        <span style="width:8px;height:2px;background:<?php echo $stepColor; ?>;display:inline-block;"></span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td style="white-space:nowrap;font-size:13px;"><?php echo date('d M Y',strtotime($d['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity Feed & Top Volunteers -->
                <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-bottom:24px;">
                    <!-- Activity Feed -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</h3>
                            <span class="badge badge-info"><?php echo count($vol_activity['recent_activity']); ?> events</span>
                        </div>
                        <div class="admin-card-body" style="max-height:320px;overflow-y:auto;padding:0;">
                            <?php if (empty($vol_activity['recent_activity'])): ?>
                                <div class="empty-state" style="padding:24px;">
                                    <i class="fa-solid fa-inbox"></i>
                                    <p>No recent activity.</p>
                                </div>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;">
                                    <?php foreach ($vol_activity['recent_activity'] as $event): 
                                        $eventColors = ['assigned'=>'#f59e0b','accepted'=>'#3b82f6','picked_up'=>'#8b5cf6','in_transit'=>'#06b6d4','delivered'=>'#059669','cancelled'=>'#ef4444'];
                                        $eventIcons = ['assigned'=>'fa-clock','accepted'=>'fa-check-circle','picked_up'=>'fa-box-open','in_transit'=>'fa-truck','delivered'=>'fa-check-double','cancelled'=>'fa-ban'];
                                        $ec = $eventColors[$event['status']] ?? '#6b7280';
                                        $ei = $eventIcons[$event['status']] ?? 'fa-circle';
                                    ?>
                                        <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 16px;border-bottom:1px solid var(--admin-border);transition:background 0.2s;" onmouseover="this.style.background='var(--admin-bg-light)'" onmouseout="this.style.background=''">
                                            <div style="width:32px;height:32px;border-radius:50%;background:<?php echo $ec; ?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i class="fa-solid <?php echo $ei; ?>" style="color:<?php echo $ec; ?>;font-size:14px;"></i>
                                            </div>
                                            <div style="flex:1;min-width:0;font-size:13px;">
                                                <strong><?php echo htmlspecialchars($event['volunteer_name'] ?? 'System'); ?></strong>
                                                <span style="color:var(--admin-text-muted);">
                                                    <?php echo $event['status'] === 'assigned' ? 'assigned to deliver' : ($event['status'] === 'accepted' ? 'accepted delivery of' : ($event['status'] === 'delivered' ? 'completed delivery of' : 'updated status to ' . $event['status'] . ' for')); ?>
                                                </span>
                                                <strong><?php echo htmlspecialchars($event['food_item']); ?></strong>
                                                <div style="margin-top:2px;font-size:11px;color:#9ca3af;">
                                                    Donor: <?php echo htmlspecialchars($event['donor_name']); ?> 
                                                    — Consumer: <?php echo htmlspecialchars($event['consumer_name']); ?>
                                                </div>
                                            </div>
                                            <span style="font-size:11px;color:#9ca3af;white-space:nowrap;flex-shrink:0;"><?php echo date('d M H:i',strtotime($event['updated_at'])); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Volunteers -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3><i class="fa-solid fa-trophy"></i> Top Volunteers</h3>
                            <span class="badge badge-success">Leaderboard</span>
                        </div>
                        <div class="admin-card-body">
                            <?php if (empty($vol_activity['top_volunteers'])): ?>
                                <div class="empty-state" style="padding:24px;">
                                    <i class="fa-solid fa-trophy"></i>
                                    <p>No completed deliveries yet.</p>
                                </div>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;gap:0;">
                                    <?php $rank = 1; foreach ($vol_activity['top_volunteers'] as $tv): 
                                        $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#' . $rank));
                                    ?>
                                        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 8px;border-bottom:1px solid var(--admin-border);">
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <span style="font-size:18px;font-weight:700;"><?php echo $medal; ?></span>
                                                <div>
                                                    <div style="font-weight:600;font-size:14px;"><?php echo htmlspecialchars($tv['volunteer_name']); ?></div>
                                                </div>
                                            </div>
                                            <span style="background:#059669;color:#fff;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;">
                                                <?php echo (int)$tv['completed_count']; ?> delivered
                                            </span>
                                        </div>
                                    <?php $rank++; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <!-- ============================== -->
            <!-- TEAM SECTION                 -->
            <!-- ============================== -->
            <?php elseif ($section === 'team'): ?>

                <?php
                $edit_member = null;
                if (isset($_GET['edit_id'])) {
                    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
                    $stmt->execute([intval($_GET['edit_id'])]);
                    $edit_member = $stmt->fetch();
                }
                ?>

                <div class="section-header">
                    <div>
                        <h1><i class="fa-solid fa-people-group"></i> Team Members</h1>
                        <p>Manage the development team members displayed on the Our Team page.</p>
                    </div>
                </div>

                <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px;">
                    <div class="stat-card">
                        <div class="stat-card-icon green"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo count($all_team_members); ?></div>
                            <div class="stat-card-label">Total Members</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon blue"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo count($team_members); ?></div>
                            <div class="stat-card-label">Active Members</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon amber"><i class="fa-solid fa-user-plus"></i></div>
                        <div class="stat-card-body">
                            <div class="stat-card-value"><?php echo count($all_team_members) - count($team_members); ?></div>
                            <div class="stat-card-label">Inactive</div>
                        </div>
                    </div>
                </div>

                <!-- Add/Edit Form -->
                <div class="admin-card" style="margin-bottom:20px;">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-<?php echo $edit_member ? 'pen-to-square' : 'plus'; ?>"></i> <?php echo $edit_member ? 'Edit Team Member' : 'Add New Team Member'; ?></h3>
                    </div>
                    <div class="admin-card-body">
                        <form action="admin.php?section=team" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $edit_member ? 'update_team_member' : 'create_team_member'; ?>">
                            <?php if ($edit_member): ?>
                                <input type="hidden" name="member_id" value="<?php echo $edit_member['id']; ?>">
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_member['name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Role / Title *</label>
                                    <input type="text" name="role" class="form-control" placeholder="E.g., Lead Developer, Designer" value="<?php echo htmlspecialchars($edit_member['role'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Bio</label>
                                <textarea name="bio" class="form-control" rows="3" placeholder="Brief description of the team member"><?php echo htmlspecialchars($edit_member['bio'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Photo</label>
                                    <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                    <div class="validation-hint">Accepted: JPEG, PNG, GIF, WebP (max 5MB)</div>
                                    <?php if (!empty($edit_member['photo'])): ?>
                                        <div style="margin-top:8px;display:flex;align-items:center;gap:10px;">
                                            <img src="../<?php echo htmlspecialchars($edit_member['photo']); ?>" alt="Current photo" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;">
                                            <span style="font-size:12px;color:var(--admin-text-muted);">Current photo</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="member@sayog.org" value="<?php echo htmlspecialchars($edit_member['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fa-brands fa-linkedin"></i> LinkedIn</label>
                                    <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/..." value="<?php echo htmlspecialchars($edit_member['linkedin'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label><i class="fa-brands fa-github"></i> GitHub</label>
                                    <input type="url" name="github" class="form-control" placeholder="https://github.com/..." value="<?php echo htmlspecialchars($edit_member['github'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fa-solid fa-globe"></i> Website</label>
                                    <input type="url" name="website" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($edit_member['website'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Display Order</label>
                                    <input type="number" name="display_order" class="form-control" value="<?php echo (int)($edit_member['display_order'] ?? 0); ?>" min="0">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?php echo ($edit_member['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($edit_member['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display:flex;gap:12px;margin-top:8px;">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?php echo $edit_member ? 'Update Member' : 'Add Member'; ?></button>
                                <?php if ($edit_member): ?>
                                    <a href="admin.php?section=team" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Team Members Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-list"></i> All Team Members</h3>
                        <span class="badge badge-info"><?php echo count($all_team_members); ?> members</span>
                    </div>
                    <div class="admin-card-body" style="padding:0;">
                        <?php if (empty($all_team_members)): ?>
                            <div class="empty-state" style="padding:40px 20px;">
                                <i class="fa-solid fa-people-group"></i>
                                <h3>No team members yet</h3>
                                <p>Add your first team member using the form above.</p>
                            </div>
                        <?php else: ?>
                        <div class="table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Member</th>
                                        <th>Role</th>
                                        <th>Social</th>
                                        <th>Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $sn = 1; foreach ($all_team_members as $m): ?>
                                    <tr>
                                        <td data-label="#"><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                        <td data-label="Member">
                                            <div class="user-cell">
                                                <?php if (!empty($m['photo'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($m['photo']); ?>" alt="" style="width:34px;height:34px;border-radius:50%;object-fit:cover;">
                                                <?php else: ?>
                                                    <div class="user-avatar"><?php echo strtoupper(substr($m['name'], 0, 1)); ?></div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="user-name"><?php echo htmlspecialchars($m['name']); ?></div>
                                                    <?php if (!empty($m['email'])): ?>
                                                        <div class="user-email"><?php echo htmlspecialchars($m['email']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Role"><span class="badge badge-info"><?php echo htmlspecialchars($m['role']); ?></span></td>
                                        <td data-label="Social">
                                            <div style="display:flex;gap:6px;">
                                                <?php if (!empty($m['linkedin'])): ?><a href="<?php echo htmlspecialchars($m['linkedin']); ?>" target="_blank" title="LinkedIn" style="color:#0a66c2;"><i class="fa-brands fa-linkedin"></i></a><?php endif; ?>
                                                <?php if (!empty($m['github'])): ?><a href="<?php echo htmlspecialchars($m['github']); ?>" target="_blank" title="GitHub" style="color:#333;"><i class="fa-brands fa-github"></i></a><?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Order"><span class="badge badge-neutral"><?php echo (int)$m['display_order']; ?></span></td>
                                        <td data-label="Status">
                                            <span class="badge badge-<?php echo $m['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($m['status']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="table-actions">
                                                <a href="admin.php?section=team&edit_id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i> Edit</a>
                                                <form action="admin.php?section=team" method="POST" class="inline-form" onsubmit="return confirm('Delete this team member?');">
                                                    <input type="hidden" name="action" value="delete_team_member">
                                                    <input type="hidden" name="member_id" value="<?php echo $m['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" style="color:red"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- ============================== -->
            <!-- CHATBOT SECTION               -->
            <!-- ============================== -->
            <?php elseif ($section === 'chatbot'):
                $chatbot_tab = sanitize($_GET['tab'] ?? 'dashboard');
                $valid_chatbot_tabs = ['dashboard', 'faq', 'settings', 'logs', 'analytics'];
                if (!in_array($chatbot_tab, $valid_chatbot_tabs)) $chatbot_tab = 'dashboard';
                ?>
                <div style="display:flex;gap:0;margin-bottom:24px;background:#fff;border-radius:12px;overflow:hidden;border:1px solid var(--admin-border);box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                    <a href="admin.php?section=chatbot&tab=dashboard" class="cms-tab <?php echo $chatbot_tab === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                    <a href="admin.php?section=chatbot&tab=faq" class="cms-tab <?php echo $chatbot_tab === 'faq' ? 'active' : ''; ?>"><i class="fa-solid fa-book"></i> FAQ Manager</a>
                    <a href="admin.php?section=chatbot&tab=settings" class="cms-tab <?php echo $chatbot_tab === 'settings' ? 'active' : ''; ?>"><i class="fa-solid fa-sliders"></i> Settings</a>
                    <a href="admin.php?section=chatbot&tab=logs" class="cms-tab <?php echo $chatbot_tab === 'logs' ? 'active' : ''; ?>"><i class="fa-solid fa-clock-rotate-left"></i> Logs</a>
                    <a href="admin.php?section=chatbot&tab=analytics" class="cms-tab <?php echo $chatbot_tab === 'analytics' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-line"></i> Analytics</a>
                </div>

                <?php
                if ($chatbot_tab === 'dashboard') {
                    require_once __DIR__ . '/../chatbot/admin/chatbot_dashboard.php';
                } elseif ($chatbot_tab === 'faq') {
                    require_once __DIR__ . '/../chatbot/admin/faq_manager.php';
                } elseif ($chatbot_tab === 'settings') {
                    require_once __DIR__ . '/../chatbot/admin/chatbot_settings.php';
                } elseif ($chatbot_tab === 'logs') {
                    require_once __DIR__ . '/../chatbot/admin/conversation_logs.php';
                } elseif ($chatbot_tab === 'analytics') {
                    require_once __DIR__ . '/../chatbot/admin/chatbot_analytics.php';
                }
                ?>

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

            <?php if ($section === 'smtp'): 
                require_once __DIR__ . '/../smtp_config.php';
                
                // Fetch current settings
                $stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE id = 1");
                $stmt->execute();
                $smtp_settings = $stmt->fetch();
                if (!$smtp_settings) {
                    $smtp_settings = [
                        'id' => 1, 'host' => '', 'port' => 587, 'username' => '', 'password' => '',
                        'encryption' => 'tls', 'from_email' => '', 'from_name' => 'Sayog', 'is_active' => 0
                    ];
                }
                
                // Handle save action
                $smtp_saved = false;
                $smtp_tested = null;
                
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $smtp_action = $_POST['smtp_action'] ?? '';
                    
                    if ($smtp_action === 'save_settings') {
                        $saveData = [
                            'id'         => 1,
                            'host'       => sanitize($_POST['host'] ?? ''),
                            'port'       => (int)($_POST['port'] ?? 587),
                            'username'   => sanitize($_POST['username'] ?? ''),
                            'password'   => $_POST['password'] ?? '',
                            'encryption' => sanitize($_POST['encryption'] ?? 'tls'),
                            'from_email' => sanitize($_POST['from_email'] ?? ''),
                            'from_name'  => sanitize($_POST['from_name'] ?? 'Sayog'),
                            'is_active'  => !empty($_POST['is_active']) ? 1 : 0,
                        ];
                        // Only update password if provided
                        if (empty($saveData['password'])) {
                            unset($saveData['password']);
                        }
                        if (save_smtp_settings($pdo, $saveData)) {
                            $smtp_saved = true;
                            // Re-fetch to show updated values
                            $stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE id = 1");
                            $stmt->execute();
                            $smtp_settings = $stmt->fetch();
                        }
                    }
                    
                    if ($smtp_action === 'test_connection') {
                        $test_email = sanitize($_POST['test_email'] ?? '');
                        if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                            $smtp_tested = test_smtp_connection($pdo, $test_email);
                        } else {
                            $smtp_tested = ['success' => false, 'message' => 'Please enter a valid email address to test.'];
                        }
                    }
                }
                ?>
                
                <div class="section-header">
                    <div>
                        <h1><i class="fa-solid fa-envelope"></i> SMTP Configuration</h1>
                        <p>Configure your email server settings for sending OTPs and notifications</p>
                    </div>
                </div>

                <?php if ($smtp_saved): ?>
                    <div class="admin-alert admin-alert-success">
                        <i class="fa-solid fa-circle-check"></i> SMTP settings saved successfully!
                    </div>
                <?php endif; ?>

                <?php if ($smtp_tested): ?>
                    <div class="admin-alert admin-alert-<?php echo $smtp_tested['success'] ? 'success' : 'danger'; ?>">
                        <i class="fa-solid <?php echo $smtp_tested['success'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                        <?php echo htmlspecialchars($smtp_tested['message']); ?>
                    </div>
                <?php endif; ?>

                <div class="admin-card" style="margin-bottom:24px;">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-server"></i> SMTP Server Settings</h3>
                        <span class="badge badge-<?php echo !empty($smtp_settings['is_active']) && !empty($smtp_settings['host']) ? 'success' : 'danger'; ?>">
                            <?php echo !empty($smtp_settings['is_active']) && !empty($smtp_settings['host']) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="admin.php?section=smtp" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <input type="hidden" name="smtp_action" value="save_settings">
                            
                            <div style="grid-column:1/-1;">
                                <label style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;cursor:pointer;">
                                    <input type="checkbox" name="is_active" value="1" <?php echo !empty($smtp_settings['is_active']) ? 'checked' : ''; ?> style="width:18px;height:18px;">
                                    Enable SMTP Email Sending
                                </label>
                                <p style="margin:4px 0 0 0;font-size:12px;color:#6b7280;">When enabled, all emails will be sent via SMTP instead of PHP mail().</p>
                            </div>

                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;color:var(--admin-text);">SMTP Host *</label>
                                <input type="text" name="host" value="<?php echo htmlspecialchars($smtp_settings['host'] ?? ''); ?>" placeholder="e.g., smtp.gmail.com" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;color:var(--admin-text);">Port *</label>
                                <input type="number" name="port" value="<?php echo (int)($smtp_settings['port'] ?? 587); ?>" placeholder="587" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;color:var(--admin-text);">Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($smtp_settings['username'] ?? ''); ?>" placeholder="your-email@gmail.com" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;color:var(--admin-text);">Password</label>
                                <input type="password" name="password" value="" placeholder="Enter new password" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                                <p style="margin:2px 0 0;font-size:11px;color:#9ca3af;">Leave empty to keep current password.</p>
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;color:var(--admin-text);">Encryption</label>
                                <select name="encryption" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff;">
                                    <option value="tls" <?php echo ($smtp_settings['encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                                    <option value="ssl" <?php echo ($smtp_settings['encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo ($smtp_settings['encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;color:var(--admin-text);">From Email *</label>
                                <input type="email" name="from_email" value="<?php echo htmlspecialchars($smtp_settings['from_email'] ?? ''); ?>" placeholder="noreply@yourdomain.com" required style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:4px;color:var(--admin-text);">From Name</label>
                                <input type="text" name="from_name" value="<?php echo htmlspecialchars($smtp_settings['from_name'] ?? 'Sayog'); ?>" placeholder="Sayog" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                            </div>

                            <div style="grid-column:1/-1;display:flex;gap:12px;justify-content:flex-end;padding-top:8px;border-top:1px solid #e5e7eb;">
                                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Test Connection -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-vial"></i> Test Connection</h3>
                    </div>
                    <div class="admin-card-body">
                        <form method="POST" action="admin.php?section=smtp" onsubmit="return testSmtpConnection(this);">
                            <input type="hidden" name="smtp_action" value="test_connection">
                            <p style="margin:0 0 12px;font-size:14px;color:#6b7280;">Send a test email to verify your SMTP configuration is working correctly.</p>
                            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                                <input type="email" name="test_email" id="testEmail" value="<?php echo htmlspecialchars($smtp_settings['from_email'] ?? ''); ?>" placeholder="Enter email to send test to" required style="flex:1;min-width:220px;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;">
                                <button type="submit" class="btn btn-secondary" style="padding:10px 24px;">
                                    <i class="fa-solid fa-paper-plane"></i> Send Test Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Provider Quick Reference -->
                <div class="admin-card" style="margin-top:24px;">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-book"></i> Common SMTP Provider Settings</h3>
                    </div>
                    <div class="admin-card-body">
                        <div style="overflow-x:auto;">
                            <table class="detail-table" style="width:100%;font-size:13px;">
                                <thead>
                                    <tr style="background:#f9fafb;">
                                        <th style="padding:10px 12px;text-align:left;font-weight:600;">Provider</th>
                                        <th style="padding:10px 12px;text-align:left;font-weight:600;">Host</th>
                                        <th style="padding:10px 12px;text-align:left;font-weight:600;">Port</th>
                                        <th style="padding:10px 12px;text-align:left;font-weight:600;">Encryption</th>
                                        <th style="padding:10px 12px;text-align:left;font-weight:600;">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td style="padding:8px 12px;"><strong>Gmail</strong></td><td style="padding:8px 12px;">smtp.gmail.com</td><td style="padding:8px 12px;">587</td><td style="padding:8px 12px;">TLS</td><td style="padding:8px 12px;">Use App Password (2FA required)</td></tr>
                                    <tr style="background:#f9fafb;"><td style="padding:8px 12px;"><strong>Outlook</strong></td><td style="padding:8px 12px;">smtp.office365.com</td><td style="padding:8px 12px;">587</td><td style="padding:8px 12px;">TLS</td><td style="padding:8px 12px;">Microsoft 365 / Hotmail</td></tr>
                                    <tr><td style="padding:8px 12px;"><strong>Yahoo</strong></td><td style="padding:8px 12px;">smtp.mail.yahoo.com</td><td style="padding:8px 12px;">587</td><td style="padding:8px 12px;">TLS</td><td style="padding:8px 12px;">Use App Password</td></tr>
                                    <tr style="background:#f9fafb;"><td style="padding:8px 12px;"><strong>Zoho</strong></td><td style="padding:8px 12px;">smtp.zoho.com</td><td style="padding:8px 12px;">587</td><td style="padding:8px 12px;">TLS</td><td style="padding:8px 12px;">Zoho Mail accounts</td></tr>
                                    <tr><td style="padding:8px 12px;"><strong>SendGrid</strong></td><td style="padding:8px 12px;">smtp.sendgrid.net</td><td style="padding:8px 12px;">587</td><td style="padding:8px 12px;">TLS</td><td style="padding:8px 12px;">Username: apikey / Password: your API key</td></tr>
                                    <tr style="background:#f9fafb;"><td style="padding:8px 12px;"><strong>cPanel</strong></td><td style="padding:8px 12px;">mail.yourdomain.com</td><td style="padding:8px 12px;">587</td><td style="padding:8px 12px;">TLS</td><td style="padding:8px 12px;">Use your full email as username</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p style="margin:12px 0 0;font-size:12px;color:#9ca3af;">
                            <i class="fa-solid fa-info-circle"></i> After saving settings, click "Send Test Email" to verify everything works.
                        </p>
                    </div>
                </div>
                <script>
                function testSmtpConnection(form) {
                    var btn = form.querySelector('button[type="submit"]');
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
                    return true;
                }
                </script>
            <?php endif; ?>

            <!-- Enhanced volunteer section now renders above (inside admin-content) -->
        </html>
