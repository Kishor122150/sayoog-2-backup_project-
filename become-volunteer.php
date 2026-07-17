<?php
require_once 'config.php';

// Must be logged in
if (!is_logged_in()) {
    set_flash_message('warning', 'Please log in to apply as a volunteer.');
    redirect('login.php?redirect=become-volunteer.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_phone = $_SESSION['user_phone'];
$user_address = $_SESSION['user_address'];
$user_photo = $_SESSION['user_photo'] ?? null;

// Compute user initials
$name_words = explode(" ", $user_name);
$initials = "";
foreach ($name_words as $w) {
    $initials .= strtoupper(substr($w, 0, 1));
}
$initials = substr($initials, 0, 2);

$unread_notification_count = get_unread_notifications_count($pdo, $user_id);

// Check if already an approved volunteer
$existing = get_volunteer_status($pdo, $user_id);
if ($existing && $existing['status'] === 'approved') {
    set_flash_message('info', 'You are already an approved volunteer!');
    redirect('dashboard.php?page=volunteer');
}

// Check if already has a pending application
$has_pending = has_pending_volunteer_application($pdo, $user_id);
$rejected_data = null;
if ($existing && $existing['status'] === 'rejected') {
    $rejected_data = $existing;
}

// Handle cancel application request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_application'])) {
    if ($has_pending) {
        cancel_volunteer_application($pdo, $user_id);
        set_flash_message('info', 'Your volunteer application has been cancelled. You can reapply anytime.');
        redirect('dashboard.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_pending && !isset($_POST['cancel_application'])) {
    $full_name = sanitize($_POST['full_name'] ?? $user_name);
    $email = sanitize($_POST['email'] ?? $user_email);
    $phone = sanitize($_POST['phone'] ?? $user_phone);
    $address = sanitize($_POST['address'] ?? $user_address);
    $municipality = sanitize($_POST['municipality'] ?? '');
    $ward_number = sanitize($_POST['ward_number'] ?? '');
    $district = sanitize($_POST['district'] ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $date_of_birth = sanitize($_POST['date_of_birth'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $emergency_contact = sanitize($_POST['emergency_contact'] ?? '');
    $occupation = sanitize($_POST['occupation'] ?? '');
    $vehicle_type = sanitize($_POST['vehicle_type'] ?? 'walking');
    $vehicle_number = sanitize($_POST['vehicle_number'] ?? '');
    $license_number = sanitize($_POST['license_number'] ?? '');
    $delivery_radius = intval($_POST['delivery_radius'] ?? 5);
    $availability = isset($_POST['availability']) ? implode(',', $_POST['availability']) : 'always';
    $languages = sanitize($_POST['languages'] ?? '');
    $agree_terms = isset($_POST['agree_terms']) ? 1 : 0;

    // Validation
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($date_of_birth)) $errors[] = "Date of birth is required.";
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($emergency_contact)) $errors[] = "Emergency contact is required.";
    if (!$agree_terms) $errors[] = "You must agree to the terms and conditions.";

    if (!in_array($delivery_radius, [5, 10, 15, 20])) {
        $errors[] = "Invalid delivery radius selected.";
    }

    // Handle file uploads
    $upload_dir = UPLOADS_DIR . '/volunteer_docs';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $profile_photo_path = null;
    $citizenship_front_path = null;
    $citizenship_back_path = null;
    $national_id_path = null;
    $college_id_path = null;
    $driving_license_path = null;

    // Profile photo
    if (!empty($_FILES['profile_photo']['name'])) {
        $file = $_FILES['profile_photo'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (in_array($mime, $allowed) && $file['size'] <= 2 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $name = 'vol_profile_' . uniqid() . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $name)) {
                    $profile_photo_path = 'uploads/volunteer_docs/' . $name;
                }
            } else {
                $errors[] = "Invalid profile photo. JPG, PNG, WEBP only. Max 2MB.";
            }
        }
    }

    // Identity document uploads - at least one is required
    $identity_uploaded = false;

    if (!empty($_FILES['citizenship_front']['name'])) {
        $file = $_FILES['citizenship_front'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (in_array($mime, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $name = 'cit_front_' . uniqid() . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $name)) {
                    $citizenship_front_path = 'uploads/volunteer_docs/' . $name;
                    $identity_uploaded = true;
                }
            } else {
                $errors[] = "Invalid citizenship front file.";
            }
        }
    }

    if (!empty($_FILES['citizenship_back']['name'])) {
        $file = $_FILES['citizenship_back'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (in_array($mime, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $name = 'cit_back_' . uniqid() . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $name)) {
                    $citizenship_back_path = 'uploads/volunteer_docs/' . $name;
                    $identity_uploaded = true;
                }
            } else {
                $errors[] = "Invalid citizenship back file.";
            }
        }
    }

    if (!empty($_FILES['national_id']['name'])) {
        $file = $_FILES['national_id'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (in_array($mime, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $name = 'nid_' . uniqid() . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $name)) {
                    $national_id_path = 'uploads/volunteer_docs/' . $name;
                    $identity_uploaded = true;
                }
            } else {
                $errors[] = "Invalid National ID file.";
            }
        }
    }

    if (!empty($_FILES['college_id']['name'])) {
        $file = $_FILES['college_id'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (in_array($mime, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $name = 'college_id_' . uniqid() . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $name)) {
                    $college_id_path = 'uploads/volunteer_docs/' . $name;
                    $identity_uploaded = true;
                }
            } else {
                $errors[] = "Invalid College ID file.";
            }
        }
    }

    if (!empty($_FILES['driving_license']['name'])) {
        $file = $_FILES['driving_license'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (in_array($mime, $allowed) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $name = 'dl_' . uniqid() . '.' . strtolower($ext);
                if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $name)) {
                    $driving_license_path = 'uploads/volunteer_docs/' . $name;
                    $identity_uploaded = true;
                }
            } else {
                $errors[] = "Invalid Driving License file.";
            }
        }
    }

    if (!$identity_uploaded) {
        $errors[] = "Please upload at least one identity document (Citizenship, National ID, College ID, or Driving License).";
    }

    if (empty($errors)) {
        try {
            if ($rejected_data) {
                $stmt = $pdo->prepare("UPDATE volunteers SET 
                    profile_photo = COALESCE(?, profile_photo),
                    full_name = ?, email = ?, phone = ?, address = ?,
                    municipality = ?, ward_number = ?, district = ?, province = ?,
                    date_of_birth = ?, gender = ?, emergency_contact = ?, occupation = ?,
                    citizenship_front = COALESCE(?, citizenship_front),
                    citizenship_back = COALESCE(?, citizenship_back),
                    national_id = COALESCE(?, national_id),
                    college_id = COALESCE(?, college_id),
                    driving_license = COALESCE(?, driving_license),
                    vehicle_type = ?, vehicle_number = ?, license_number = ?,
                    delivery_radius = ?, availability = ?,
                    languages = ?,
                    status = 'pending', rejected_reason = NULL
                    WHERE user_id = ?");
                $stmt->execute([
                    $profile_photo_path, $full_name, $email, $phone, $address,
                    $municipality, $ward_number, $district, $province,
                    $date_of_birth, $gender, $emergency_contact, $occupation,
                    $citizenship_front_path, $citizenship_back_path,
                    $national_id_path, $college_id_path, $driving_license_path,
                    $vehicle_type, $vehicle_number ?: null, $license_number ?: null,
                    $delivery_radius, $availability,
                    $languages ?: null,
                    $user_id
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO volunteers (
                    user_id, profile_photo, full_name, email, phone, address,
                    municipality, ward_number, district, province,
                    date_of_birth, gender, emergency_contact, occupation,
                    citizenship_front, citizenship_back, national_id, college_id, driving_license,
                    vehicle_type, vehicle_number, license_number,
                    delivery_radius, availability,
                    languages, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([
                    $user_id, $profile_photo_path, $full_name, $email, $phone, $address,
                    $municipality ?: null, $ward_number ?: null, $district ?: null, $province ?: null,
                    $date_of_birth, $gender, $emergency_contact, $occupation ?: null,
                    $citizenship_front_path, $citizenship_back_path,
                    $national_id_path, $college_id_path, $driving_license_path,
                    $vehicle_type, $vehicle_number ?: null, $license_number ?: null,
                    $delivery_radius, $availability,
                    $languages ?: null
                ]);
            }

            create_notification($pdo, $user_id, 'volunteer_applied',
                'Your volunteer application has been submitted successfully. The admin team will review your application and you will be notified once a decision is made.',
                'dashboard.php', true);

            set_flash_message('success', 'Your volunteer application has been submitted successfully! The admin will review it shortly.');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            $errors[] = "Failed to submit application: " . $e->getMessage();
        }
    }
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Volunteer | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/app.js"></script>
    <style>
        .volunteer-application-wrapper {
            max-width: 820px;
            margin: 0 auto;
            padding: 0;
        }
        .vol-app-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .vol-app-header .badge-icon {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 30px;
            color: #fff;
            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.25);
        }
        .vol-app-header h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .vol-app-header p {
            color: var(--text-secondary);
            max-width: 520px;
            margin: 0 auto;
            font-size: 14.5px;
        }

        /* ─── Step Progress Bar ─── */
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 36px;
            padding: 0 10px;
        }
        .step-progress .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
            text-align: center;
        }
        .step-progress .step .step-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            background: var(--border);
            color: var(--text-muted);
            border: 3px solid transparent;
            transition: all 0.35s ease;
            z-index: 2;
            position: relative;
        }
        .step-progress .step.active .step-circle {
            background: #059669;
            color: #fff;
            border-color: rgba(5, 150, 105, 0.2);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.15);
        }
        .step-progress .step.completed .step-circle {
            background: #059669;
            color: #fff;
        }
        .step-progress .step .step-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            transition: color 0.3s;
        }
        .step-progress .step.active .step-label,
        .step-progress .step.completed .step-label {
            color: #059669;
        }
        .step-progress .step-connector {
            flex: 1;
            height: 3px;
            background: var(--border);
            margin: 0 -2px;
            margin-bottom: 28px;
            border-radius: 4px;
            transition: background 0.4s;
            z-index: 1;
        }
        .step-progress .step-connector.done {
            background: #059669;
        }

        /* ─── Step Panels ─── */
        .step-panel {
            display: none;
            animation: fadeSlideUp 0.4s ease;
        }
        .step-panel.active {
            display: block;
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }
        .form-section-card .section-title {
            padding: 18px 24px;
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.04), transparent);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .form-section-card .section-title i {
            color: #059669;
            font-size: 18px;
        }
        .form-section-card .section-body {
            padding: 24px;
        }

        /* Identity upload cards */
        .identity-option-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }
        .identity-option-card {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 18px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .identity-option-card:hover {
            border-color: #059669;
            background: rgba(5, 150, 105, 0.03);
        }
        .identity-option-card.has-file {
            border-color: #059669;
            border-style: solid;
            background: rgba(5, 150, 105, 0.05);
        }
        .identity-option-card i {
            font-size: 26px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .identity-option-card.has-file i {
            color: #059669;
        }
        .identity-option-card .id-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        .identity-option-card .id-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: #059669;
            color: #fff;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 6px;
            display: none;
        }
        .identity-option-card.has-file .id-badge {
            display: block;
        }

        /* Terms */
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 16px;
            background: rgba(5, 150, 105, 0.04);
            border-radius: 12px;
            border: 1px solid rgba(5, 150, 105, 0.1);
        }
        .terms-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            accent-color: #059669;
            flex-shrink: 0;
        }
        .terms-checkbox label {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Navigation buttons */
        .step-nav {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 20px 0;
            border-top: 1px solid var(--border);
            margin-top: 8px;
        }
        .step-nav .btn {
            min-width: 140px;
        }
        .step-nav .btn-prev {
            min-width: 120px;
        }

        @media (max-width: 768px) {
            .volunteer-application-wrapper { margin: 0 auto; }
            .vol-app-header h1 { font-size: 22px; }
            .form-section-card .section-body { padding: 16px; }
            .form-section-card .section-title { padding: 14px 16px; font-size: 15px; }
            .identity-option-group { grid-template-columns: 1fr 1fr; }
            .step-progress .step .step-circle { width: 36px; height: 36px; font-size: 13px; }
            .step-progress .step .step-label { font-size: 9px; }
            .step-nav { flex-direction: column; }
            .step-nav .btn { width: 100%; min-width: 0; }
        }
        @media (max-width: 480px) {
            .identity-option-group { grid-template-columns: 1fr 1fr; }
            .step-progress .step .step-label { font-size: 8px; letter-spacing: 0; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation (matches dashboard.php exactly) -->
        <aside class="app-sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <div class="sidebar-logo-icon">
                        <i class="fa-solid fa-hand-holding-heart"></i>
                    </div>
                    <span>SAYOG</span>
                </a>
            </div>

            <!-- Profile Info Card -->
            <div class="sidebar-profile">
                <div class="profile-avatar">
                    <?php if (!empty($user_photo)): ?>
                        <img src="<?php echo htmlspecialchars($user_photo); ?>" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <?php echo $initials; ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <span class="profile-role-badge role-donor" style="background-color: rgba(16, 185, 129, 0.1); color: var(--primary);">
                        <span>Member</span>
                    </span>
                    <?php $sidebar_rating = get_user_rating($pdo, $user_id); ?>
                    <?php if ($sidebar_rating['total_ratings'] > 0): ?>
                        <div class="user-rating-stars" style="margin-top:2px;">
                            <?php echo render_stars($sidebar_rating['average'], 10); ?>
                            <span style="font-size:10px;font-weight:600;color:var(--text-muted);"><?php echo number_format($sidebar_rating['average'],1); ?> (<?php echo $sidebar_rating['total_ratings']; ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="sidebar-nav">
                <a href="dashboard.php?page=home" class="nav-item">
                    <i class="fa-solid fa-house-chimney"></i>
                    <span>Home Feed</span>
                </a>
                <a href="dashboard.php?page=create-donation" class="nav-item">
                    <i class="fa-solid fa-circle-plus"></i>
                    <span>Create Donation</span>
                </a>
                <a href="dashboard.php?page=donation_approval" class="nav-item">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Approval Tracking</span>
                </a>
                <a href="dashboard.php?page=request-donation" class="nav-item">
                    <i class="fa-solid fa-hand-holding-hand"></i>
                    <span>Request Food</span>
                </a>
                <a href="dashboard.php?page=manage-donation" class="nav-item">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Manage incoming Request</span>
                </a>
                <a href="dashboard.php?page=manage-request" class="nav-item">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span>Our Requests</span>
                </a>
                <a href="dashboard.php?page=track-donation" class="nav-item">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Track our Donations</span>
                </a>
                <a href="dashboard.php?page=track-request" class="nav-item">
                    <i class="fa-solid fa-route"></i>
                    <span>Track our Requests</span>
                </a>
                <a href="dashboard.php?page=notifications" class="nav-item">
                    <i class="fa-solid fa-bell"></i>
                    <span>Notifications</span>
                </a>
                <a href="dashboard.php?page=profile" class="nav-item">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>My Profile</span>
                </a>

                <?php
                    $vol_status = get_volunteer_status($pdo, $user_id);
                    if ($vol_status && $vol_status['status'] === 'approved'): ?>
                    <a href="dashboard.php?page=volunteer" class="nav-item" style="border-top: 1px solid var(--border); margin-top: 8px; padding-top: 12px;">
                        <i class="fa-solid fa-hand-holding-heart" style="color: #059669;"></i>
                        <span style="font-weight: 700; color: #059669;">🧭 Volunteer Hub</span>
                    </a>
                    <?php elseif (!$vol_status): ?>
                    <a href="become-volunteer.php" class="nav-item active" style="background: rgba(5, 150, 105, 0.1); border: 1px solid rgba(5, 150, 105, 0.3); border-radius: 10px; margin-top: 4px;">
                        <i class="fa-solid fa-user-plus" style="color: #059669;"></i>
                        <span style="font-weight: 700; color: #059669;">Become a Volunteer</span>
                    </a>
                    <?php elseif ($vol_status['status'] === 'pending'): ?>
                    <a href="become-volunteer.php" class="nav-item" style="opacity: 0.7;">
                        <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
                        <span>⏳ Application Pending</span>
                    </a>
                    <?php elseif ($vol_status['status'] === 'rejected'): ?>
                    <a href="become-volunteer.php" class="nav-item active" style="opacity: 0.7;">
                        <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i>
                        <span>Reapply as Volunteer</span>
                    </a>
                    <?php endif; ?>
                <a href="logout.php" class="nav-item nav-item-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Log Out</span>
                </a>
            </nav>
        </aside>

        <!-- Main Dashboard Viewport -->
        <main class="app-main">
            <!-- Header Bar (matches dashboard.php) -->
            <header class="app-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1 class="header-title">
                        <i class="fa-solid fa-hand-holding-heart" style="color: #059669; margin-right: 8px;"></i>
                        Become a Volunteer
                    </h1>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php?page=notifications" class="header-notifications" aria-label="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notification-badge" aria-label="Unread notifications" <?php echo empty($unread_notification_count) ? 'style="display:none;"' : ''; ?>>
                            <?php echo (int)$unread_notification_count; ?>
                        </span>
                    </a>

                    <script>
                        (function () {
                            const link = document.querySelector('.header-notifications');
                            if (!link) return;
                            const badge = link.querySelector('.notification-badge');
                            async function refreshBadge() {
                                try {
                                    const res = await fetch('dashboard.php?ajax=unread_notifications_count', { cache: 'no-store' });
                                    if (!res.ok) return;
                                    const data = await res.json();
                                    const count = Number(data.count || 0);
                                    badge.textContent = count;
                                    badge.style.display = count > 0 ? 'inline-flex' : 'none';
                                    link.setAttribute('aria-label', count > 0 ? `Notifications (${count} unread)` : 'Notifications');
                                } catch (e) {}
                            }
                            window.refreshNotifBadge = refreshBadge;
                            refreshBadge();
                            setInterval(refreshBadge, 5000);
                        })();
                    </script>
                    <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                    <button class="lang-toggle" onclick="toggleLanguage()" style="background:rgba(59,130,246,0.1);">
                        <span>नेपाली</span>
                    </button>
                    <div style="font-size: 13.5px; font-weight: 600; color: var(--text-secondary);">
                        <i class="fa-solid fa-calendar-day" style="margin-right: 4px; color: var(--primary);"></i>
                        Today: <?php echo date('M d, Y'); ?>
                    </div>
                </div>
            </header>

            <!-- Main Work Area Content -->
            <div class="app-content">
                <!-- Flash messages -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                        <span><?php echo $flash['message']; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <div>
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul style="margin-top: 5px; padding-left: 20px; font-size: 13px;">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo $err; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($has_pending): ?>
                    <div class="alert alert-info" style="text-align:center;">
                        <i class="fa-solid fa-clock"></i>
                        <strong>Application Pending Review</strong>
                        <p style="margin-top:8px;">You have already submitted a volunteer application. Please wait for the admin to review it. You will be notified once a decision is made.</p>
                        <div style="display:flex;gap:12px;justify-content:center;margin-top:16px;flex-wrap:wrap;">
                            <a href="dashboard.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel your volunteer application? This action cannot be undone.');">
                                <input type="hidden" name="cancel_application" value="1">
                                <button type="submit" class="btn btn-danger" style="background:#ef4444;color:#fff;padding:10px 22px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="fa-solid fa-ban"></i> Cancel Request</button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($rejected_data): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <strong>Previous Application Rejected</strong>
                        <p style="margin-top:4px;font-size:13px;">Reason: <?php echo htmlspecialchars($rejected_data['rejected_reason'] ?? 'Not specified'); ?></p>
                        <p style="margin-top:4px;font-size:13px;">You can update your information below and reapply.</p>
                    </div>
                <?php endif; ?>

                <?php if (!$has_pending): ?>
                <div class="volunteer-application-wrapper">
                    <div class="vol-app-header">
                        <div class="badge-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
                        <h1>Become a Volunteer</h1>
                        <p>Complete the 3-step application to join our volunteer team and help deliver surplus food in your community.</p>
                    </div>

                    <!-- ═══ STEP PROGRESS ═══ -->
                    <div class="step-progress" id="stepProgress">
                        <div class="step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Personal Info</div>
                        </div>
                        <div class="step-connector" data-connector="1"></div>
                        <div class="step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Identity &amp; Vehicle</div>
                        </div>
                        <div class="step-connector" data-connector="2"></div>
                        <div class="step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Availability &amp; Submit</div>
                        </div>
                    </div>

                    <form action="become-volunteer.php" method="POST" enctype="multipart/form-data" id="volForm" novalidate>

                        <!-- ════════════════════════════════════════════════ -->
                        <!-- STEP 1: PERSONAL INFORMATION                   -->
                        <!-- ════════════════════════════════════════════════ -->
                        <div class="step-panel active" data-step="1">
                            <div class="form-section-card">
                                <div class="section-title">
                                    <i class="fa-solid fa-user"></i> Personal Information
                                </div>
                                <div class="section-body">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Full Name <span style="color:#ef4444;">*</span></label>
                                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user_name); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Email <span style="color:#ef4444;">*</span></label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? $user_email); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" name="profile_photo" class="form-control" accept="image/*">
                                        <div class="validation-hint">Optional. JPG, PNG, WEBP. Max 2MB.</div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Phone Number <span style="color:#ef4444;">*</span></label>
                                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $user_phone); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Date of Birth <span style="color:#ef4444;">*</span></label>
                                            <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Gender <span style="color:#ef4444;">*</span></label>
                                            <select name="gender" class="form-control" required>
                                                <option value="">Select Gender</option>
                                                <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Occupation</label>
                                            <input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars($_POST['occupation'] ?? ''); ?>" placeholder="E.g., Student, Teacher, Driver">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($_POST['address'] ?? $user_address); ?>" placeholder="Full address">
                                    </div>

                                    <div class="form-row" style="grid-template-columns: repeat(4, 1fr);">
                                        <div class="form-group">
                                            <label class="form-label">Municipality</label>
                                            <input type="text" name="municipality" class="form-control" value="<?php echo htmlspecialchars($_POST['municipality'] ?? ''); ?>" placeholder="Municipality">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Ward No.</label>
                                            <input type="text" name="ward_number" class="form-control" value="<?php echo htmlspecialchars($_POST['ward_number'] ?? ''); ?>" placeholder="Ward">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">District</label>
                                            <input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>" placeholder="District">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Province</label>
                                            <select name="province" class="form-control">
                                                <option value="">Select</option>
                                                <?php
                                                $provinces = ['Province 1', 'Province 2', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'];
                                                foreach ($provinces as $p) {
                                                    $sel = (isset($_POST['province']) && $_POST['province'] === $p) ? 'selected' : '';
                                                    echo "<option value=\"$p\" $sel>$p</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Emergency Contact <span style="color:#ef4444;">*</span></label>
                                            <input type="text" name="emergency_contact" class="form-control" value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>" placeholder="Name and phone number" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Languages Spoken</label>
                                            <input type="text" name="languages" class="form-control" value="<?php echo htmlspecialchars($_POST['languages'] ?? ''); ?>" placeholder="E.g., Nepali, English, Newari">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="step-nav">
                                <a href="dashboard.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
                                <button type="button" class="btn btn-primary btn-next" onclick="goStep(2)">
                                    Next Step <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ════════════════════════════════════════════════ -->
                        <!-- STEP 2: IDENTITY VERIFICATION + VEHICLE         -->
                        <!-- ════════════════════════════════════════════════ -->
                        <div class="step-panel" data-step="2">
                            <div class="form-section-card">
                                <div class="section-title">
                                    <i class="fa-solid fa-id-card"></i> Identity Verification <span style="font-size:12px;font-weight:500;color:var(--text-muted);margin-left:8px;">(Upload at least one)</span>
                                </div>
                                <div class="section-body">
                                    <div class="identity-option-group">
                                        <div class="identity-option-card" onclick="document.getElementById('cit_front_input').click()">
                                            <i class="fa-solid fa-passport"></i>
                                            <div class="id-label">Citizenship (Front)</div>
                                            <div class="id-badge"><i class="fa-solid fa-check"></i></div>
                                            <input type="file" id="cit_front_input" name="citizenship_front" accept="image/*,application/pdf" style="display:none;" onchange="this.closest('.identity-option-card').classList.add('has-file')">
                                        </div>
                                        <div class="identity-option-card" onclick="document.getElementById('cit_back_input').click()">
                                            <i class="fa-solid fa-passport"></i>
                                            <div class="id-label">Citizenship (Back)</div>
                                            <div class="id-badge"><i class="fa-solid fa-check"></i></div>
                                            <input type="file" id="cit_back_input" name="citizenship_back" accept="image/*,application/pdf" style="display:none;" onchange="this.closest('.identity-option-card').classList.add('has-file')">
                                        </div>
                                        <div class="identity-option-card" onclick="document.getElementById('nid_input').click()">
                                            <i class="fa-solid fa-address-card"></i>
                                            <div class="id-label">National ID</div>
                                            <div class="id-badge"><i class="fa-solid fa-check"></i></div>
                                            <input type="file" id="nid_input" name="national_id" accept="image/*,application/pdf" style="display:none;" onchange="this.closest('.identity-option-card').classList.add('has-file')">
                                        </div>
                                        <div class="identity-option-card" onclick="document.getElementById('college_input').click()">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                            <div class="id-label">College ID</div>
                                            <div class="id-badge"><i class="fa-solid fa-check"></i></div>
                                            <input type="file" id="college_input" name="college_id" accept="image/*,application/pdf" style="display:none;" onchange="this.closest('.identity-option-card').classList.add('has-file')">
                                        </div>
                                        <div class="identity-option-card" onclick="document.getElementById('dl_input').click()">
                                            <i class="fa-solid fa-car"></i>
                                            <div class="id-label">Driving License</div>
                                            <div class="id-badge"><i class="fa-solid fa-check"></i></div>
                                            <input type="file" id="dl_input" name="driving_license" accept="image/*,application/pdf" style="display:none;" onchange="this.closest('.identity-option-card').classList.add('has-file')">
                                        </div>
                                    </div>
                                    <div class="validation-hint" style="text-align:center;">Accepted formats: JPG, PNG, WEBP, PDF. Max 5MB each.</div>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="section-title">
                                    <i class="fa-solid fa-truck"></i> Vehicle Information
                                </div>
                                <div class="section-body">
                                    <div class="form-group">
                                        <label class="form-label">Vehicle Type <span style="color:#ef4444;">*</span></label>
                                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;">
                                            <?php
                                            $vehicles = [
                                                'walking' => ['fa-solid fa-person-walking', 'Walking'],
                                                'bicycle' => ['fa-solid fa-bicycle', 'Bicycle'],
                                                'motorcycle' => ['fa-solid fa-motorcycle', 'Motorcycle'],
                                                'scooter' => ['fa-solid fa-moped', 'Scooter'],
                                                'car' => ['fa-solid fa-car', 'Car']
                                            ];
                                            $selected_vehicle = $_POST['vehicle_type'] ?? 'walking';
                                            foreach ($vehicles as $val => [$icon, $label]):
                                            ?>
                                            <label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:16px 8px;border:2px solid <?php echo $selected_vehicle === $val ? '#059669' : 'var(--border)'; ?>;border-radius:12px;cursor:pointer;background:<?php echo $selected_vehicle === $val ? 'rgba(5,150,105,0.05)' : 'transparent'; ?>;transition:all 0.2s;">
                                                <input type="radio" name="vehicle_type" value="<?php echo $val; ?>" <?php echo $selected_vehicle === $val ? 'checked' : ''; ?> style="display:none;" onchange="document.querySelectorAll('label:has(input[name=vehicle_type])').forEach(function(l){l.style.borderColor=l.contains(this)?'#059669':'var(--border)';l.style.background=l.contains(this)?'rgba(5,150,105,0.05)':'transparent'}.bind(this))">
                                                <i class="<?php echo $icon; ?>" style="font-size:24px;color:<?php echo $selected_vehicle === $val ? '#059669' : 'var(--text-muted)'; ?>;"></i>
                                                <span style="font-size:12px;font-weight:600;color:var(--text-secondary);"><?php echo $label; ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">Vehicle Number (Optional)</label>
                                            <input type="text" name="vehicle_number" class="form-control" value="<?php echo htmlspecialchars($_POST['vehicle_number'] ?? ''); ?>" placeholder="E.g., BA 1 PA 1234">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Driving License Number (Optional)</label>
                                            <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>" placeholder="License number">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Maximum Delivery Radius <span style="color:#ef4444;">*</span></label>
                                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                            <?php foreach ([5, 10, 15, 20] as $km): 
                                                $sel = (isset($_POST['delivery_radius']) ? intval($_POST['delivery_radius']) : 5) === $km;
                                            ?>
                                            <label style="flex:1;min-width:80px;text-align:center;padding:12px 16px;border:2px solid <?php echo $sel ? '#059669' : 'var(--border)'; ?>;border-radius:12px;cursor:pointer;background:<?php echo $sel ? 'rgba(5,150,105,0.05)' : 'transparent'; ?>;transition:all 0.2s;">
                                                <input type="radio" name="delivery_radius" value="<?php echo $km; ?>" <?php echo $sel ? 'checked' : ''; ?> style="display:none;" onchange="document.querySelectorAll('label:has(input[name=delivery_radius])').forEach(function(l){l.style.borderColor=l.querySelector('input').checked?'#059669':'var(--border)';l.style.background=l.querySelector('input').checked?'rgba(5,150,105,0.05)':'transparent'}.bind(this))">
                                                <div style="font-size:18px;font-weight:800;color:<?php echo $sel ? '#059669' : 'var(--text-primary)'; ?>;"><?php echo $km; ?> km</div>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="step-nav">
                                <button type="button" class="btn btn-secondary btn-prev" onclick="goStep(1)">
                                    <i class="fa-solid fa-arrow-left"></i> Previous
                                </button>
                                <button type="button" class="btn btn-primary btn-next" onclick="goStep(3)">
                                    Next Step <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- ════════════════════════════════════════════════ -->
                        <!-- STEP 3: AVAILABILITY + DECLARATION + SUBMIT     -->
                        <!-- ════════════════════════════════════════════════ -->
                        <div class="step-panel" data-step="3">
                            <div class="form-section-card">
                                <div class="section-title">
                                    <i class="fa-solid fa-clock"></i> Availability
                                </div>
                                <div class="section-body">
                                    <div class="form-group">
                                        <label class="form-label">When are you available? <span style="color:#ef4444;">*</span></label>
                                        <div style="display:flex;flex-wrap:wrap;gap:10px;">
                                            <?php
                                            $avail_options = [
                                                'morning' => '🌅 Morning',
                                                'afternoon' => '☀️ Afternoon',
                                                'evening' => '🌆 Evening',
                                                'weekend' => '📅 Weekend Only',
                                                'always' => '🔄 Always Available'
                                            ];
                                            $selected_avail = isset($_POST['availability']) ? (is_array($_POST['availability']) ? $_POST['availability'] : explode(',', $_POST['availability'])) : ['always'];
                                            foreach ($avail_options as $val => $label):
                                                $checked = in_array($val, $selected_avail);
                                            ?>
                                            <label style="display:flex;align-items:center;gap:6px;padding:10px 16px;border:1.5px solid <?php echo $checked ? '#059669' : 'var(--border)'; ?>;border-radius:10px;cursor:pointer;background:<?php echo $checked ? 'rgba(5,150,105,0.05)' : 'transparent'; ?>;transition:all 0.2s;">
                                                <input type="checkbox" name="availability[]" value="<?php echo $val; ?>" <?php echo $checked ? 'checked' : ''; ?> style="accent-color:#059669;width:16px;height:16px;" onchange="this.closest('label').style.borderColor=this.checked?'#059669':'var(--border)';this.closest('label').style.background=this.checked?'rgba(5,150,105,0.05)':'transparent'">
                                                <span style="font-size:13px;font-weight:500;"><?php echo $label; ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="section-title">
                                    <i class="fa-solid fa-file-signature"></i> Declaration
                                </div>
                                <div class="section-body">
                                    <div class="terms-checkbox">
                                        <input type="checkbox" name="agree_terms" id="agree_terms" value="1" <?php echo isset($_POST['agree_terms']) ? 'checked' : ''; ?> required>
                                        <label for="agree_terms">
                                            <strong>I agree to the following:</strong><br>
                                            • I will transport donated food responsibly and maintain proper hygiene.<br>
                                            • I will follow all Sayog policies and guidelines during food delivery.<br>
                                            • I will not misuse donor or receiver information for any purpose.<br>
                                            • I accept the terms and conditions of being a Sayog volunteer.
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="step-nav">
                                <button type="button" class="btn btn-secondary btn-prev" onclick="goStep(2)">
                                    <i class="fa-solid fa-arrow-left"></i> Previous
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa-solid fa-paper-plane"></i> Submit Application
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- ═══ STEP NAVIGATION SCRIPT ═══ -->
    <script>
    var currentStep = 1;

    function goStep(step) {
        var panels = document.querySelectorAll('.step-panel');
        var steps = document.querySelectorAll('.step-progress .step');
        var connectors = document.querySelectorAll('.step-progress .step-connector');

        // Validate current step before moving forward
        if (step > currentStep) {
            if (!validateStep(currentStep)) return;
        }

        currentStep = step;

        // Show/hide panels
        panels.forEach(function(p) {
            p.classList.toggle('active', parseInt(p.dataset.step) === step);
        });

        // Update progress indicators
        steps.forEach(function(s) {
            var sNum = parseInt(s.dataset.step);
            s.classList.remove('active', 'completed');
            if (sNum === step) s.classList.add('active');
            else if (sNum < step) s.classList.add('completed');
        });

        connectors.forEach(function(c) {
            var cNum = parseInt(c.dataset.connector);
            c.classList.toggle('done', cNum < step);
        });

        // Scroll to top of form
        document.querySelector('.step-panel.active').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function validateStep(step) {
        var panel = document.querySelector('.step-panel[data-step="' + step + '"]');
        var required = panel.querySelectorAll('[required]');
        var valid = true;

        required.forEach(function(el) {
            if (!el.value || el.value.trim() === '') {
                el.style.borderColor = '#ef4444';
                el.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.15)';
                valid = false;
            } else {
                el.style.borderColor = '';
                el.style.boxShadow = '';
            }
        });

        if (!valid) {
            var firstInvalid = panel.querySelector('[style*="border-color: rgb(239, 68, 68)"]');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }

        return valid;
    }

    // Real-time validation clearing
    document.querySelectorAll('.step-panel [required]').forEach(function(el) {
        el.addEventListener('input', function() {
            if (this.value && this.value.trim() !== '') {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });
    });
    </script>

</body>
</html>
