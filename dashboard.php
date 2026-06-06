<?php
require_once 'config.php';

// Route protection
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_address = $_SESSION['user_address'];
$user_phone = $_SESSION['user_phone'];

$errors = [];
$successes = [];

// Determine active page/tab
$page = sanitize($_GET['page'] ?? 'home');
$valid_pages = ['home', 'create-donation', 'request-donation', 'manage-donation', 'manage-request', 'track-donation', 'track-request', 'profile', 'notifications'];
if (!in_array($page, $valid_pages)) {
    $page = 'home';
}

$unread_notification_count = get_unread_notifications_count($pdo, $user_id);

// -------------------------------------------------------------
// POST ACTIONS HANDLING
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action: Create Donation (Any logged-in user can donate)
    if ($action === 'create_donation') {
        $food_item = sanitize($_POST['food_item'] ?? '');
        $quantity = sanitize($_POST['quantity'] ?? '');
        $expiry_time = sanitize($_POST['expiry_time'] ?? '');
        $pickup_address = sanitize($_POST['pickup_address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $image_path = null;
        $video_path = null;

        if (empty($food_item)) $errors[] = "Food item name is required.";
        if (empty($quantity)) $errors[] = "Quantity is required.";
        if (empty($expiry_time)) {
            $errors[] = "Expiry date & time is required.";
        } else {
            $expiry_dt = new DateTime($expiry_time);
            $now_dt = new DateTime();
            if ($expiry_dt <= $now_dt) {
                $errors[] = "Expiry time must be in the future.";
            }
        }
        if (empty($pickup_address)) $errors[] = "Pickup address is required.";
        
        if (empty($phone)) {
            $errors[] = "Contact phone number is required.";
        } elseif (!validate_nepal_phone($phone)) {
            $errors[] = "Invalid Nepalese phone number.";
        }

        // File uploads: optional image and video
        if (!empty($_FILES['image']['name'])) {
            $imageFile = $_FILES['image'];
            if ($imageFile['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Failed to upload image.";
            } else {
                $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $imageInfo = getimagesize($imageFile['tmp_name']);
                if (!$imageInfo || !in_array($imageInfo['mime'], $allowedImageTypes, true)) {
                    $errors[] = "Invalid image file. Allowed: JPG, PNG, WEBP.";
                } elseif ($imageFile['size'] > 5 * 1024 * 1024) {
                    $errors[] = "Image size must be 5MB or less.";
                } else {
                    $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('donation_img_', true) . '.' . strtolower($extension);
                    $destination = UPLOADS_DIR . '/' . $filename;
                    if (move_uploaded_file($imageFile['tmp_name'], $destination)) {
                        $image_path = 'uploads/' . $filename;
                    } else {
                        $errors[] = "Could not save uploaded image.";
                    }
                }
            }
        }

        if (!empty($_FILES['video']['name'])) {
            $videoFile = $_FILES['video'];
            if ($videoFile['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "Failed to upload video.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $videoMime = $finfo->file($videoFile['tmp_name']);
                $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
                if (!in_array($videoMime, $allowedVideoTypes, true)) {
                    $errors[] = "Invalid video file. Allowed: MP4, WEBM, OGG.";
                } elseif ($videoFile['size'] > 50 * 1024 * 1024) {
                    $errors[] = "Video size must be 50MB or less.";
                } else {
                    $extension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('donation_vid_', true) . '.' . strtolower($extension);
                    $destination = UPLOADS_DIR . '/' . $filename;
                    if (move_uploaded_file($videoFile['tmp_name'], $destination)) {
                        $video_path = 'uploads/' . $filename;
                    } else {
                        $errors[] = "Could not save uploaded video.";
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO donations (donor_id, food_item, quantity, expiry_time, pickup_address, phone, description, image_path, video_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
                $stmt->execute([$user_id, $food_item, $quantity, $expiry_time, $pickup_address, $phone, $description, $image_path, $video_path]);
                set_flash_message('success', 'Your food donation listing has been successfully posted to the feed!');
                redirect('dashboard.php?page=home');
            } catch (PDOException $e) {
                $errors[] = "Failed to submit donation: " . $e->getMessage();
            }
        }
    }

    // Action: Update Donation
    if ($action === 'update_donation') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $food_item = sanitize($_POST['food_item'] ?? '');
        $quantity = sanitize($_POST['quantity'] ?? '');
        $expiry_time = sanitize($_POST['expiry_time'] ?? '');
        $pickup_address = sanitize($_POST['pickup_address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if ($donation_id <= 0) {
            $errors[] = "Donation not found.";
        }
        if (empty($food_item)) $errors[] = "Food item name is required.";
        if (empty($quantity)) $errors[] = "Quantity is required.";
        if (empty($expiry_time)) {
            $errors[] = "Expiry date & time is required.";
        } else {
            $expiry_dt = new DateTime($expiry_time);
            $now_dt = new DateTime();
            if ($expiry_dt <= $now_dt) {
                $errors[] = "Expiry time must be in the future.";
            }
        }
        if (empty($pickup_address)) $errors[] = "Pickup address is required.";
        if (empty($phone)) {
            $errors[] = "Contact phone number is required.";
        } elseif (!validate_nepal_phone($phone)) {
            $errors[] = "Invalid Nepalese phone number.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM donations WHERE id = ? AND donor_id = ?");
            $stmt->execute([$donation_id, $user_id]);
            $donation = $stmt->fetch();
            if (!$donation) {
                $errors[] = "Donation not found or access denied.";
            }
        }

        if (empty($errors)) {
            $image_path = $donation['image_path'];
            $video_path = $donation['video_path'];

            if (!empty($_FILES['image']['name'])) {
                $imageFile = $_FILES['image'];
                if ($imageFile['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Failed to upload image.";
                } else {
                    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    $imageInfo = getimagesize($imageFile['tmp_name']);
                    if (!$imageInfo || !in_array($imageInfo['mime'], $allowedImageTypes, true)) {
                        $errors[] = "Invalid image file. Allowed: JPG, PNG, WEBP.";
                    } elseif ($imageFile['size'] > 5 * 1024 * 1024) {
                        $errors[] = "Image size must be 5MB or less.";
                    } else {
                        $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                        $filename = uniqid('donation_img_', true) . '.' . strtolower($extension);
                        $destination = UPLOADS_DIR . '/' . $filename;
                        if (move_uploaded_file($imageFile['tmp_name'], $destination)) {
                            if (!empty($image_path) && file_exists(__DIR__ . '/' . $image_path)) {
                                @unlink(__DIR__ . '/' . $image_path);
                            }
                            $image_path = 'uploads/' . $filename;
                        } else {
                            $errors[] = "Could not save uploaded image.";
                        }
                    }
                }
            }

            if (!empty($_FILES['video']['name'])) {
                $videoFile = $_FILES['video'];
                if ($videoFile['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Failed to upload video.";
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $videoMime = $finfo->file($videoFile['tmp_name']);
                    $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
                    if (!in_array($videoMime, $allowedVideoTypes, true)) {
                        $errors[] = "Invalid video file. Allowed: MP4, WEBM, OGG.";
                    } elseif ($videoFile['size'] > 50 * 1024 * 1024) {
                        $errors[] = "Video size must be 50MB or less.";
                    } else {
                        $extension = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
                        $filename = uniqid('donation_vid_', true) . '.' . strtolower($extension);
                        $destination = UPLOADS_DIR . '/' . $filename;
                        if (move_uploaded_file($videoFile['tmp_name'], $destination)) {
                            if (!empty($video_path) && file_exists(__DIR__ . '/' . $video_path)) {
                                @unlink(__DIR__ . '/' . $video_path);
                            }
                            $video_path = 'uploads/' . $filename;
                        } else {
                            $errors[] = "Could not save uploaded video.";
                        }
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE donations SET food_item = ?, quantity = ?, expiry_time = ?, pickup_address = ?, phone = ?, description = ?, image_path = ?, video_path = ? WHERE id = ?");
                $stmt->execute([$food_item, $quantity, $expiry_time, $pickup_address, $phone, $description, $image_path, $video_path, $donation_id]);
                set_flash_message('success', 'Donation updated successfully.');
                redirect('dashboard.php?page=create-donation');
            } catch (PDOException $e) {
                $errors[] = "Failed to update donation: " . $e->getMessage();
            }
        }
    }

    // Action: Toggle Donation Active / Inactive
    if ($action === 'toggle_donation_status') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        if ($donation_id <= 0) {
            $errors[] = "Donation not found.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT status FROM donations WHERE id = ? AND donor_id = ?");
            $stmt->execute([$donation_id, $user_id]);
            $donation = $stmt->fetch();
            if (!$donation) {
                $errors[] = "Donation not found or access denied.";
            }
        }

        if (empty($errors)) {
            if ($donation['status'] === 'cancelled') {
                $new_status = 'available';
            } elseif ($donation['status'] === 'available') {
                $new_status = 'cancelled';
            } else {
                $errors[] = "Only available or inactive donations can be toggled.";
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE donations SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $donation_id]);
                set_flash_message('success', 'Donation status updated successfully.');
                redirect('dashboard.php?page=create-donation');
            } catch (PDOException $e) {
                $errors[] = "Failed to update donation status: " . $e->getMessage();
            }
        }
    }

    // Action: Delete Donation
    if ($action === 'delete_donation') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        if ($donation_id <= 0) {
            $errors[] = "Donation not found.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM donations WHERE id = ? AND donor_id = ?");
            $stmt->execute([$donation_id, $user_id]);
            $donation = $stmt->fetch();
            if (!$donation) {
                $errors[] = "Donation not found or access denied.";
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM donations WHERE id = ?");
                $stmt->execute([$donation_id]);
                set_flash_message('success', 'Donation removed successfully.');
                redirect('dashboard.php?page=create-donation');
            } catch (PDOException $e) {
                $errors[] = "Failed to delete donation: " . $e->getMessage();
            }
        }
    }

    // Action: Mark Notifications Read
    if ($action === 'mark_notifications_read') {
        mark_all_notifications_read($pdo, $user_id);
        set_flash_message('success', 'All notifications have been marked as read.');
        redirect('dashboard.php?page=notifications');
    }

    // Action: Request Donation (Any user can request other users' food)
    if ($action === 'request_donation') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $quantity_requested = sanitize($_POST['quantity_requested'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if ($donation_id <= 0) $errors[] = "Invalid donation selected.";
        if (empty($quantity_requested)) $errors[] = "Please state the quantity you need.";

        // Check if donation is available
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM donations WHERE id = ?");
            $stmt->execute([$donation_id]);
            $donation = $stmt->fetch();

            if (!$donation) {
                $errors[] = "Donation does not exist.";
            } elseif ($donation['donor_id'] == $user_id) {
                $errors[] = "You cannot request your own food donation.";
            } elseif ($donation['status'] !== 'available' && $donation['status'] !== 'requested') {
                $errors[] = "This food is no longer available.";
            }

            // Check if already requested
            $stmt = $pdo->prepare("SELECT id FROM requests WHERE donation_id = ? AND consumer_id = ? AND status = 'pending'");
            $stmt->execute([$donation_id, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "You already have a pending request for this donation.";
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Insert request
                $stmt = $pdo->prepare("INSERT INTO requests (donation_id, consumer_id, quantity_requested, message, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$donation_id, $user_id, $quantity_requested, $message]);

                // Update donation status to 'requested' if it was 'available'
                $stmt = $pdo->prepare("UPDATE donations SET status = 'requested' WHERE id = ? AND status = 'available'");
                $stmt->execute([$donation_id]);

                $pdo->commit();
                set_flash_message('success', 'Request submitted successfully! The donor will review it.');
                redirect('dashboard.php?page=track-request');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "Failed to submit request: " . $e->getMessage();
            }
        }
    }

    // Action: Approve Request (Approves request for the user's donation)
    if ($action === 'approve_request') {
        $request_id = intval($_POST['request_id'] ?? 0);

        try {
            // Find request and verify ownership of donation
            $stmt = $pdo->prepare("
                SELECT r.*, d.donor_id, d.id AS donation_id 
                FROM requests r 
                JOIN donations d ON r.donation_id = d.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();

            if (!$request || $request['donor_id'] != $user_id) {
                $errors[] = "Access denied or request not found.";
            } elseif ($request['status'] !== 'pending') {
                $errors[] = "Request is already processed.";
            }

            if (empty($errors)) {
                $pdo->beginTransaction();

                // 1. Approve selected request
                $stmt = $pdo->prepare("UPDATE requests SET status = 'approved' WHERE id = ?");
                $stmt->execute([$request_id]);

                // 2. Accept donation
                $stmt = $pdo->prepare("UPDATE donations SET status = 'accepted' WHERE id = ?");
                $stmt->execute([$request['donation_id']]);

                // 3. Reject all other pending requests for this donation
                $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE donation_id = ? AND id != ? AND status = 'pending'");
                $stmt->execute([$request['donation_id'], $request_id]);

                create_notification(
                    $pdo,
                    $request['consumer_id'],
                    'request_approved',
                    'Your request has been approved and the donor will contact you for pickup.',
                    'dashboard.php?page=track-request',
                    true
                );

                $pdo->commit();
                set_flash_message('success', 'Request approved! Consumer details are now open for pickup.');
                redirect('dashboard.php?page=manage-donation');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to approve request: " . $e->getMessage();
        }
    }

    // Action: Reject Request
    if ($action === 'reject_request') {
        $request_id = intval($_POST['request_id'] ?? 0);

        try {
            $stmt = $pdo->prepare("
                SELECT r.*, d.donor_id, d.id AS donation_id, d.food_item 
                FROM requests r 
                JOIN donations d ON r.donation_id = d.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();

            if (!$request || $request['donor_id'] != $user_id) {
                $errors[] = "Access denied or request not found.";
            }

            if (empty($errors)) {
                $pdo->beginTransaction();

                // Mark request as rejected
                $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$request_id]);

                create_notification(
                    $pdo,
                    $request['consumer_id'],
                    'request_rejected',
                    'Your request for "' . $request['food_item'] . '" has been rejected.',
                    'dashboard.php?page=track-request',
                    true
                );

                // Check if there are other pending requests left for this donation
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE donation_id = ? AND status = 'pending'");
                $stmt->execute([$request['donation_id']]);
                $remaining_requests = $stmt->fetchColumn();

                if ($remaining_requests == 0) {
                    // Reset donation status back to available
                    $stmt = $pdo->prepare("UPDATE donations SET status = 'available' WHERE id = ? AND status = 'requested'");
                    $stmt->execute([$request['donation_id']]);
                }

                $pdo->commit();
                set_flash_message('success', 'Request rejected.');
                redirect('dashboard.php?page=manage-donation');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to reject request: " . $e->getMessage();
        }
    }

    // Action: Complete Donation Pickup
    if ($action === 'complete_donation') {
        $donation_id = intval($_POST['donation_id'] ?? 0);

        try {
            $stmt = $pdo->prepare("SELECT * FROM donations WHERE id = ? AND donor_id = ?");
            $stmt->execute([$donation_id, $user_id]);
            $donation = $stmt->fetch();

            if (!$donation || $donation['status'] !== 'accepted') {
                $errors[] = "Donation cannot be marked complete in its current state.";
            }

            if (empty($errors)) {
                $pdo->beginTransaction();

                // 1. Mark donation as completed
                $stmt = $pdo->prepare("UPDATE donations SET status = 'completed' WHERE id = ?");
                $stmt->execute([$donation_id]);

                // 2. Mark corresponding approved request as completed
                $stmt = $pdo->prepare("UPDATE requests SET status = 'completed' WHERE donation_id = ? AND status = 'approved'");
                $stmt->execute([$donation_id]);

                $pdo->commit();
                set_flash_message('success', 'Donation successfully marked as Completed! Thank you for sharing.');
                redirect('dashboard.php?page=track-donation');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to complete donation: " . $e->getMessage();
        }
    }

    // Action: Cancel Request
    if ($action === 'cancel_request') {
        $request_id = intval($_POST['request_id'] ?? 0);

        try {
            $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ? AND consumer_id = ?");
            $stmt->execute([$request_id, $user_id]);
            $request = $stmt->fetch();

            if (!$request || ($request['status'] !== 'pending' && $request['status'] !== 'approved')) {
                $errors[] = "Request cannot be cancelled in its current state.";
            }

            if (empty($errors)) {
                $pdo->beginTransaction();

                // 1. Mark request as cancelled
                $stmt = $pdo->prepare("UPDATE requests SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$request_id]);

                // 2. Update donation status if needed
                if ($request['status'] === 'approved') {
                    // Donation was accepted, reset to available
                    $stmt = $pdo->prepare("UPDATE donations SET status = 'available' WHERE id = ?");
                    $stmt->execute([$request['donation_id']]);
                } else {
                    // It was pending. Check if there are other pending requests left
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE donation_id = ? AND status = 'pending'");
                    $stmt->execute([$request['donation_id']]);
                    $remaining = $stmt->fetchColumn();

                    if ($remaining == 0) {
                        $stmt = $pdo->prepare("UPDATE donations SET status = 'available' WHERE id = ? AND status = 'requested'");
                        $stmt->execute([$request['donation_id']]);
                    }
                }

                $pdo->commit();
                set_flash_message('success', 'Your request has been cancelled.');
                redirect('dashboard.php?page=manage-request');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to cancel request: " . $e->getMessage();
        }
    }

    // Action: Update Profile
    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($name)) $errors[] = "Name is required.";
        if (empty($address)) $errors[] = "Address is required.";
        
        if (empty($phone)) {
            $errors[] = "Phone is required.";
        } elseif (!validate_nepal_phone($phone)) {
            $errors[] = "Invalid Nepal phone number.";
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, address = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $address, $phone, $user_id]);

                // Refresh session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_address'] = $address;
                $_SESSION['user_phone'] = $phone;
                
                // Re-bind local variables
                $user_name = $name;
                $user_address = $address;
                $user_phone = $phone;

                $successes[] = "Profile updated successfully!";
            } catch (PDOException $e) {
                $errors[] = "Failed to update profile: " . $e->getMessage();
            }
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
    <title>Dashboard | Sayog - Food Donation System</title>
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        
        <!-- Sidebar Navigation -->
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
                    <?php 
                        $words = explode(" ", $user_name);
                        $initials = "";
                        foreach ($words as $w) {
                            $initials .= strtoupper(substr($w, 0, 1));
                        }
                        echo substr($initials, 0, 2);
                    ?>
                </div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <span class="profile-role-badge role-donor" style="background-color: rgba(16, 185, 129, 0.1); color: var(--primary);">
                        Member
                    </span>
                </div>
            </div>

            <!-- Sidebar Navigation Links (All options visible to every user) -->
            <nav class="sidebar-nav">
                <a href="dashboard.php?page=home" class="nav-item <?php echo $page === 'home' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house-chimney"></i>
                    <span>Home Feed</span>
                </a>

                <a href="dashboard.php?page=create-donation" class="nav-item <?php echo $page === 'create-donation' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-circle-plus"></i>
                    <span>Create Donation</span>
                </a>

                <a href="dashboard.php?page=request-donation" class="nav-item <?php echo $page === 'request-donation' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-hand-holding-hand"></i>
                    <span>Request Food</span>
                </a>

                <a href="dashboard.php?page=manage-donation" class="nav-item <?php echo $page === 'manage-donation' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <!-- <span>Manage Donation</span> -->
                         <span>Manage incoming Request</span>
                </a>

                <a href="dashboard.php?page=manage-request" class="nav-item <?php echo $page === 'manage-request' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span>Our Requests</span>
                </a>

                <a href="dashboard.php?page=track-donation" class="nav-item <?php echo $page === 'track-donation' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Track our Donations</span>
                </a>

                <a href="dashboard.php?page=track-request" class="nav-item <?php echo $page === 'track-request' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-route"></i>
                    <span>Track our Requests</span>
                </a>

                <a href="dashboard.php?page=notifications" class="nav-item <?php echo $page === 'notifications' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>Notifications</span>
                </a>

                <a href="dashboard.php?page=profile" class="nav-item <?php echo $page === 'profile' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>My Profile</span>
                </a>

                <a href="logout.php" class="nav-item nav-item-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Log Out</span>
                </a>
            </nav>
        </aside>

        <!-- Main Dashboard Viewport -->
        <main class="app-main">
            <!-- Header Bar -->
            <header class="app-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button class="menu-toggle" id="menuToggle">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1 class="header-title">
                        <?php
                            switch ($page) {
                                case 'home': echo "Platform Feed"; break;
                                case 'create-donation': echo "Create New Donation"; break;
                                case 'request-donation': echo "Request Food Donations"; break;
                                case 'manage-donation': echo "Manage Donation Requests"; break;
                                case 'manage-request': echo "Manage Your Food Requests"; break;
                                case 'track-donation': echo "Track Active Donations"; break;
                                case 'track-request': echo "Track Food Requests"; break;
                                case 'notifications': echo "Notifications"; break;
                                case 'profile': echo "Edit Profile"; break;
                            }
                        ?>
                    </h1>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php?page=notifications" class="header-notifications">
                        <i class="fa-solid fa-bell"></i>
                        <?php if (!empty($unread_notification_count)): ?>
                            <span class="notification-badge"><?php echo $unread_notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div style="font-size: 13.5px; font-weight: 600; color: var(--text-secondary);">
                        <i class="fa-solid fa-calendar-day" style="margin-right: 4px; color: var(--primary);"></i>
                        Today: <?php echo date('M d, Y'); ?>
                    </div>
                </div>
            </header>

            <!-- Main Work Area Content -->
            <div class="app-content">
                
                <!-- Display Notification Flash Messages -->
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
                            <strong>Error processing request:</strong>
                            <ul style="margin-top: 5px; padding-left: 20px; font-size: 13px;">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo $err; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($successes)): ?>
                    <div class="alert alert-success">
                        <div>
                            <i class="fa-solid fa-circle-check"></i>
                            <strong>Success:</strong>
                            <ul style="margin-top: 5px; padding-left: 20px; font-size: 13px;">
                                <?php foreach ($successes as $succ): ?>
                                    <li><?php echo $succ; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- RENDER VIEWS -->
                <?php
                // =============================================================
                // TAB: HOME / FEED (Facebook style social portal)
                // =============================================================
                if ($page === 'home') {
                    // Fetch counts for overview cards (Unified statistics)
                    $total_active = $pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'available' OR status = 'requested'")->fetchColumn();
                    $total_completed = $pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'completed'")->fetchColumn();
                    
                    // User active donations posted
                    $my_listings_stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ? AND status != 'completed' AND status != 'cancelled'");
                    $my_listings_stmt->execute([$user_id]);
                    $my_active_listings = $my_listings_stmt->fetchColumn();

                    // User pending requests submitted
                    $my_requests_stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE consumer_id = ? AND status = 'pending'");
                    $my_requests_stmt->execute([$user_id]);
                    $my_pending_requests = $my_requests_stmt->fetchColumn();
                    ?>
                    
                    <!-- Stats Section (Shows overall and both personal metrics) -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div>
                                <div class="stat-value"><?php echo $total_active; ?></div>
                                <div class="stat-label">Active Foods Available</div>
                            </div>
                            <div class="stat-icon icon-emerald">
                                <i class="fa-solid fa-heart-circle-check"></i>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div>
                                <div class="stat-value"><?php echo $my_active_listings; ?></div>
                                <div class="stat-label">My Active Listings</div>
                            </div>
                            <div class="stat-icon icon-emerald">
                                <i class="fa-solid fa-gift"></i>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div>
                                <div class="stat-value"><?php echo $my_pending_requests; ?></div>
                                <div class="stat-label">My Pending Requests</div>
                            </div>
                            <div class="stat-icon icon-blue">
                                <i class="fa-solid fa-hand-holding-hand"></i>
                            </div>
                        </div>
                    </div>

                    <div class="home-toolbar">
                        <div class="home-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" id="homeFeedSearch" class="form-control" placeholder="Search food, donor or location...">
                        </div>
                        <div class="filter-chips">
                            <button type="button" class="filter-chip active" data-filter="all">All</button>
                            <button type="button" class="filter-chip" data-filter="available">Available</button>
                            <button type="button" class="filter-chip" data-filter="requested">Requested</button>
                            <button type="button" class="filter-chip" data-filter="accepted">Accepted</button>
                            <button type="button" class="filter-chip" data-filter="completed">Completed</button>
                        </div>
                    </div>

                    <!-- Feed Composer Trigger (Facebook Style - visible to all users) -->
                    <div class="post-creator-card" style="max-width: 680px; margin: 0 auto 24px auto;">
                        <div class="post-creator-header">
                            <div class="profile-avatar" style="width: 38px; height: 38px; font-size: 13px;">
                                <?php echo substr($initials, 0, 2); ?>
                            </div>
                            <div class="post-creator-trigger" onclick="location.href='dashboard.php?page=create-donation'">
                                What surplus food would you like to donate today, <?php echo htmlspecialchars(explode(" ", $user_name)[0]); ?>?
                            </div>
                        </div>
                    </div>

                    <!-- Feed Posts Container -->
                    <div class="feed-container">
                        <h2 style="font-size: 16px; font-weight: 700; color: var(--text-secondary); margin-bottom: 8px;">
                            Recent donation posts
                        </h2>
                        
                        <?php
                        // Fetch all posts sorted by newest
                        $feed_stmt = $pdo->prepare("
                            SELECT d.*, u.name AS donor_name, u.address AS donor_default_address
                            FROM donations d 
                            JOIN users u ON d.donor_id = u.id 
                            ORDER BY d.created_at DESC
                        ");
                        $feed_stmt->execute();
                        $posts = $feed_stmt->fetchAll();

                        if (empty($posts)): ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-store-slash"></i>
                                <h3>No food donations found</h3>
                                <p>Be the first to list surplus food to help others in the community.</p>
                                <a href="dashboard.php?page=create-donation" class="btn btn-primary">
                                    <i class="fa-solid fa-circle-plus"></i> List Food Donation
                                </a>
                            </div>
                        <?php else: 
                            foreach ($posts as $post):
                                $post_words = explode(" ", $post['donor_name']);
                                $post_initials = "";
                                foreach ($post_words as $pw) {
                                    $post_initials .= strtoupper(substr($pw, 0, 1));
                                }
                                $post_initials = substr($post_initials, 0, 2);

                                // Check if user has already requested this post
                                $has_requested = false;
                                $check_req = $pdo->prepare("SELECT id FROM requests WHERE donation_id = ? AND consumer_id = ? AND status = 'pending'");
                                $check_req->execute([$post['id'], $user_id]);
                                if ($check_req->fetch()) {
                                    $has_requested = true;
                                }
                                ?>
                                <div class="feed-card" data-status="<?php echo htmlspecialchars($post['status']); ?>" data-search="<?php echo strtolower(htmlspecialchars($post['food_item'] . ' ' . $post['donor_name'] . ' ' . $post['pickup_address'])); ?>">
                                    <div class="feed-card-header">
                                        <div class="feed-user-avatar" style="background: linear-gradient(135deg, var(--primary) 0%, #0d9488 100%);">
                                            <?php echo $post_initials; ?>
                                        </div>
                                        <div class="feed-header-info">
                                            <div class="feed-user-name">
                                                <?php echo htmlspecialchars($post['donor_name']); ?>
                                            </div>
                                            <div class="feed-post-meta">
                                                <span><i class="fa-regular fa-clock"></i> Posted <?php echo date('M d, Y h:i A', strtotime($post['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                                <?php echo $post['status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="feed-card-body">
                                        <h3 class="feed-food-title"><?php echo htmlspecialchars($post['food_item']); ?></h3>
                                        
                                        <?php if (!empty($post['description'])): ?>
                                            <p class="feed-food-description"><?php echo htmlspecialchars($post['description']); ?></p>
                                        <?php endif; ?>

                                        <?php if (!empty($post['image_path']) || !empty($post['video_path'])): ?>
                                            <div class="feed-media-preview">
                                                <?php if (!empty($post['image_path'])): ?>
                                                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Donation Image" class="feed-card-image">
                                                <?php endif; ?>

                                                <?php if (!empty($post['video_path'])): ?>
                                                    <video controls class="feed-card-video">
                                                        <source src="<?php echo htmlspecialchars($post['video_path']); ?>" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="feed-food-details">
                                            <div class="detail-item">
                                                <i class="fa-solid fa-boxes-stacked"></i>
                                                <span><strong>Quantity:</strong> <?php echo htmlspecialchars($post['quantity']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fa-solid fa-hourglass-half"></i>
                                                <span><strong>Consume Before:</strong> <?php echo date('M d, Y h:i A', strtotime($post['expiry_time'])); ?></span>
                                            </div>
                                            <div class="detail-item" style="grid-column: span 2;">
                                                <i class="fa-solid fa-map-pin"></i>
                                                <span><strong>Location:</strong> <?php echo htmlspecialchars($post['pickup_address']); ?></span>
                                            </div>
                                            <?php if ($post['status'] === 'accepted' || $post['status'] === 'completed' || $post['donor_id'] == $user_id): ?>
                                                <div class="detail-item">
                                                    <i class="fa-solid fa-phone"></i>
                                                    <span><strong>Contact Phone:</strong> <?php echo htmlspecialchars($post['phone']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="feed-card-footer">
                                        <?php if ($post['donor_id'] == $user_id): ?>
                                            <span style="font-size: 13px; color: var(--text-muted); font-weight: 500;">
                                                <i class="fa-solid fa-circle-info"></i> You listed this item.
                                            </span>
                                        <?php else: ?>
                                            <?php if ($post['status'] === 'available' || $post['status'] === 'requested'): ?>
                                                <?php if ($has_requested): ?>
                                                    <button class="btn btn-secondary" disabled>
                                                        <i class="fa-solid fa-circle-check" style="color: var(--primary);"></i> Request Submitted
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary" onclick="openRequestModal(<?php echo $post['id']; ?>, '<?php echo addslashes(htmlspecialchars($post['food_item'])); ?>', '<?php echo addslashes(htmlspecialchars($post['quantity'])); ?>')">
                                                        <i class="fa-solid fa-hand-holding-hand"></i> Request Food
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="font-size: 13px; color: var(--text-muted); font-weight: 500;">
                                                    <i class="fa-solid fa-ban"></i> No longer accepting requests.
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>

                    <!-- REQUEST MODAL (Rendered for everyone to submit requests) -->
                    <div class="modal-overlay" id="requestModal">
                        <div class="modal-container">
                            <div class="modal-header">
                                <h3 class="modal-title">Submit Food Request</h3>
                                <button class="modal-close" onclick="closeRequestModal()">&times;</button>
                            </div>
                            <form action="dashboard.php?page=home" method="POST">
                                <input type="hidden" name="action" value="request_donation">
                                <input type="hidden" name="donation_id" id="modal-donation-id" value="">
                                
                                <div class="modal-body">
                                    <p style="margin-bottom: 16px; font-size: 14px; color: var(--text-secondary);">
                                        You are requesting food from the post: <strong id="modal-food-title" style="color: var(--text-primary);"></strong>.
                                    </p>
                                    
                                    <div class="form-group">
                                        <label for="quantity_requested" class="form-label">Quantity Needed</label>
                                        <input type="text" name="quantity_requested" id="modal-quantity-requested" class="form-control" placeholder="E.g., 2 kg, 3 portions" required>
                                        <div class="validation-hint">Total listing quantity is <span id="modal-food-quantity"></span>. Please do not request more than available.</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="message" class="form-label">Message to Donor (Optional)</label>
                                        <textarea name="message" id="message" class="form-control" rows="3" placeholder="Provide details like pickup time or organization credentials..."></textarea>
                                    </div>
                                </div>

                                <div class="modal-body" style="padding-top: 0; text-align: right; border-top: 1px solid var(--border); padding-top: 16px;">
                                    <button type="button" class="btn btn-secondary" onclick="closeRequestModal()" style="margin-right: 8px;">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        function openRequestModal(id, title, maxQty) {
                            document.getElementById('modal-donation-id').value = id;
                            document.getElementById('modal-food-title').textContent = title;
                            document.getElementById('modal-food-quantity').textContent = maxQty;
                            document.getElementById('modal-quantity-requested').value = maxQty; // Default to full amount
                            document.getElementById('requestModal').classList.add('active');
                        }
                        function closeRequestModal() {
                            document.getElementById('requestModal').classList.remove('active');
                        }

                        (function() {
                            const searchInput = document.getElementById('homeFeedSearch');
                            const filterButtons = document.querySelectorAll('.home-toolbar .filter-chip');
                            const feedCards = document.querySelectorAll('.feed-card');

                            function applyFilters() {
                                const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
                                const activeFilter = document.querySelector('.home-toolbar .filter-chip.active')?.dataset.filter || 'all';

                                feedCards.forEach(card => {
                                    const matchesSearch = !query || card.dataset.search.includes(query);
                                    const matchesStatus = activeFilter === 'all' || card.dataset.status === activeFilter;
                                    card.style.display = (matchesSearch && matchesStatus) ? 'block' : 'none';
                                });
                            }

                            if (searchInput) {
                                searchInput.addEventListener('input', applyFilters);
                            }

                            filterButtons.forEach(button => {
                                button.addEventListener('click', () => {
                                    filterButtons.forEach(btn => btn.classList.remove('active'));
                                    button.classList.add('active');
                                    applyFilters();
                                });
                            });
                        })();
                    </script>

                <?php
                // =============================================================
                // TAB: CREATE DONATION
                // =============================================================
                } elseif ($page === 'create-donation') {
                    $myDonationStmt = $pdo->prepare("SELECT * FROM donations WHERE donor_id = ? ORDER BY created_at DESC");
                    $myDonationStmt->execute([$user_id]);
                    $myDonations = $myDonationStmt->fetchAll();
                    ?>
                    <div style="max-width: 920px; margin: 0 auto 24px;">
                        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:16px; align-items:center; margin-bottom:24px;">
                            <div>
                                <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px; color: var(--text-primary);">Create Donation</h2>
                                <p style="margin: 0; color: var(--text-secondary); max-width: 620px;">
                                    Click the button to open the donation form, then your donation listings are shown below for easy review and management.
                                </p>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="openDonationModal()">
                                <i class="fa-solid fa-circle-plus" style="margin-right: 8px;"></i> Add New Donation
                            </button>
                        </div>

                        <div class="info-grid">
                            <div class="info-card">
                                <span class="info-chip">Step 1</span>
                                <h4>Open the donation form</h4>
                                <p>Tap the button above to add a new food donation. The form appears in a clean popup so you can stay on this page.</p>
                            </div>
                            <div class="info-card">
                                <span class="info-chip">Step 2</span>
                                <h4>Review your listings</h4>
                                <p>Your donations appear in the section below once posted. This helps you track availability and status quickly.</p>
                            </div>
                        </div>

                        <div class="section-divider"><span>Your donation listings</span></div>
                        <div style="margin-bottom: 32px;">
                            <?php if (empty($myDonations)): ?>
                                <div class="empty-state">
                                    <i class="fa-solid fa-box-open"></i>
                                    <h3>No donation listings yet</h3>
                                    <p>Click the button above to add your first donation to the community feed.</p>
                                </div>
                            <?php else: ?>
                                <div class="feed-container">
                                    <h3 style="font-size: 18px; font-weight: 700; color: var(--text-secondary); margin-bottom: 16px;">
                                        My donation listings
                                    </h3>
                                    <?php foreach ($myDonations as $donation): ?>
                                        <div class="feed-card" style="margin-bottom: 16px;">
                                            <div class="feed-card-header">
                                                <div class="feed-user-avatar" style="background: linear-gradient(135deg, var(--primary) 0%, #0d9488 100%);">
                                                    <?php echo strtoupper(substr($initials, 0, 2)); ?>
                                                </div>
                                                <div class="feed-header-info">
                                                    <div class="feed-user-name">Your Donation</div>
                                                    <div class="feed-post-meta"><span><?php echo date('M d, Y h:i A', strtotime($donation['created_at'])); ?></span></div>
                                                </div>
                                                <div>
                                                    <span class="status-badge status-<?php echo htmlspecialchars($donation['status']); ?>">
                                                        <?php echo htmlspecialchars($donation['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="feed-card-body">
                                                <h3 class="feed-food-title"><?php echo htmlspecialchars($donation['food_item']); ?></h3>
                                                <div class="feed-food-details">
                                                    <div class="detail-item"><i class="fa-solid fa-boxes-stacked"></i><span><strong>Quantity:</strong> <?php echo htmlspecialchars($donation['quantity']); ?></span></div>
                                                    <div class="detail-item"><i class="fa-solid fa-hourglass-half"></i><span><strong>Consume Before:</strong> <?php echo date('M d, Y h:i A', strtotime($donation['expiry_time'])); ?></span></div>
                                                    <div class="detail-item" style="grid-column: span 2;"><i class="fa-solid fa-map-pin"></i><span><strong>Pickup:</strong> <?php echo htmlspecialchars($donation['pickup_address']); ?></span></div>
                                                </div>
                                            </div>
                                            <div class="feed-card-footer" style="gap: 10px; flex-wrap: wrap;">
                                                <button type="button" class="btn btn-secondary" onclick='openDonationModal("update", <?php echo json_encode($donation, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </button>
                                                <?php if (in_array($donation['status'], ['available', 'cancelled'])): ?>
                                                    <form action="dashboard.php?page=create-donation" method="POST" style="display:inline; margin:0;">
                                                        <input type="hidden" name="action" value="toggle_donation_status">
                                                        <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                                        <button type="submit" class="btn btn-secondary" style="background: <?php echo $donation['status'] === 'available' ? 'rgba(239, 68, 68, 0.1)' : 'rgba(16, 185, 129, 0.1)'; ?>; color: <?php echo $donation['status'] === 'available' ? '#b91c1c' : '#0f766e'; ?>;">
                                                            <i class="fa-solid <?php echo $donation['status'] === 'available' ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                                            <?php echo $donation['status'] === 'available' ? 'Inactivate' : 'Activate'; ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form action="dashboard.php?page=create-donation" method="POST" style="display:inline; margin:0;">
                                                    <input type="hidden" name="action" value="delete_donation">
                                                    <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this donation permanently?');">
                                                        <i class="fa-solid fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-overlay" id="createDonationModal">
                        <div class="modal-container">
                            <div class="modal-header">
                                <h3 class="modal-title"><i class="fa-solid fa-gift" style="margin-right: 8px; color: var(--primary);"></i> Share Surplus Food</h3>
                                <button class="modal-close" onclick="closeDonationModal()">&times;</button>
                            </div>
                            <form action="dashboard.php?page=create-donation" method="POST" id="createDonationForm" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="action" value="create_donation">
                            <input type="hidden" name="donation_id" id="donation_id" value="">

                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="food_item" class="form-label">Food Item / Name</label>
                                        <input type="text" id="food_item" name="food_item" class="form-control" placeholder="E.g., Cooked Rice and Veg curry, Baker's Bread" required>
                                        <div class="validation-hint">Be clear about what type of food it is.</div>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="quantity" class="form-label">Quantity</label>
                                            <input type="text" id="quantity" name="quantity" class="form-control" placeholder="E.g., 5 plates, 10 packets, 3 kg" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="expiry_time" class="form-label">Expiry / Consume Before</label>
                                            <input type="datetime-local" id="expiry_time" name="expiry_time" class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="pickup_address" class="form-label">Pickup Location Address</label>
                                        <input type="text" id="pickup_address" name="pickup_address" class="form-control" value="<?php echo htmlspecialchars($user_address); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone" class="form-label">Contact Phone Number</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_phone); ?>" required>
                                        <div class="validation-hint" id="phone-hint">Valid Nepal phone format</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="description" class="form-label">Description / Storage Details (Optional)</label>
                                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="E.g., Cooked today at 2 PM. Kept under refrigeration. Contains dairy products. Please bring your own container."></textarea>
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="image" class="form-label">Upload Food Photo (Optional)</label>
                                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                                            <div class="validation-hint">Allowed types: JPG, PNG, WEBP. Max 5MB.</div>
                                        </div>
                                        <div class="form-group">
                                            <label for="video" class="form-label">Upload Video (Optional)</label>
                                            <input type="file" id="video" name="video" class="form-control" accept="video/*">
                                            <div class="validation-hint">Allowed types: MP4, WEBM, OGG. Max 50MB.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-body" style="display:flex; justify-content:flex-end; gap:12px; border-top:1px solid var(--border); padding-top:20px;">
                                    <button type="button" class="btn btn-secondary" onclick="closeDonationModal()">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Post Food Donation Listing</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        function openDonationModal(mode = 'create', donation = null) {
                            const modal = document.getElementById('createDonationModal');
                            const title = modal.querySelector('.modal-title');
                            const submitBtn = modal.querySelector('button[type="submit"]');
                            const actionInput = document.querySelector('#createDonationForm input[name="action"]');
                            const donationIdInput = document.getElementById('donation_id');
                            const foodInput = document.getElementById('food_item');
                            const quantityInput = document.getElementById('quantity');
                            const expiryInput = document.getElementById('expiry_time');
                            const addressInput = document.getElementById('pickup_address');
                            const phoneInput = document.getElementById('phone');
                            const descriptionInput = document.getElementById('description');

                            if (mode === 'update' && donation) {
                                title.innerHTML = '<i class="fa-solid fa-pen-to-square" style="margin-right: 8px; color: var(--primary);"></i> Update Donation';
                                submitBtn.textContent = 'Save Changes';
                                actionInput.value = 'update_donation';
                                donationIdInput.value = donation.id;
                                foodInput.value = donation.food_item || '';
                                quantityInput.value = donation.quantity || '';
                                expiryInput.value = donation.expiry_time ? donation.expiry_time.replace(' ', 'T') : '';
                                addressInput.value = donation.pickup_address || '<?php echo htmlspecialchars($user_address); ?>';
                                phoneInput.value = donation.phone || '<?php echo htmlspecialchars($user_phone); ?>';
                                descriptionInput.value = donation.description || '';
                            } else {
                                title.innerHTML = '<i class="fa-solid fa-gift" style="margin-right: 8px; color: var(--primary);"></i> Share Surplus Food';
                                submitBtn.textContent = 'Post Food Donation Listing';
                                actionInput.value = 'create_donation';
                                donationIdInput.value = '';
                                foodInput.value = '';
                                quantityInput.value = '';
                                expiryInput.value = '';
                                addressInput.value = '<?php echo htmlspecialchars($user_address); ?>';
                                phoneInput.value = '<?php echo htmlspecialchars($user_phone); ?>';
                                descriptionInput.value = '';
                            }

                            modal.classList.add('active');
                        }
                        function closeDonationModal() {
                            document.getElementById('createDonationModal').classList.remove('active');
                        }

                        (function() {
                            const phoneInput = document.getElementById('phone');
                            const phoneHint = document.getElementById('phone-hint');
                            if (!phoneInput || !phoneHint) return;

                            phoneInput.addEventListener('input', function() {
                                const val = phoneInput.value.trim();
                                const mobilePattern = /^(98|97|96)\d{8}$/;
                                const landlinePattern = /^01\d{7}$/;
                                
                                if (val.length === 0) {
                                    phoneHint.style.color = '';
                                    phoneHint.textContent = 'Valid Nepal phone format';
                                } else if (mobilePattern.test(val) || landlinePattern.test(val)) {
                                    phoneHint.style.color = '#10b981';
                                    phoneHint.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid Nepalese Phone Number';
                                } else {
                                    phoneHint.style.color = '#ef4444';
                                    phoneHint.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid format. Use 98/97/96 (10 digits) or 01 (9 digits)';
                                }
                            });
                        })();
                    </script>

                <?php
                // =============================================================
                // TAB: REQUEST DONATION (Catalog of others' available foods)
                // =============================================================
                } elseif ($page === 'request-donation') {
                    ?>
                    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: space-between; align-items: center;">
                        <p style="color: var(--text-secondary); font-size: 14.5px;">Browse and request surplus food listings within your region.</p>
                        
                        <!-- Search Bar -->
                        <div style="position: relative; width: 100%; max-width: 320px;">
                            <input type="text" id="catalogSearch" class="form-control" placeholder="Search food items or locations..." style="padding-left: 36px;">
                            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 14px; color: var(--text-muted);"></i>
                        </div>
                    </div>

                    <?php
                    // Fetch available donations (not owned by the current logged-in user)
                    $catalog_stmt = $pdo->prepare("
                        SELECT d.*, u.name AS donor_name
                        FROM donations d 
                        JOIN users u ON d.donor_id = u.id 
                        WHERE d.status IN ('available', 'requested') AND d.donor_id != ?
                        ORDER BY d.created_at DESC
                    ");
                    $catalog_stmt->execute([$user_id]);
                    $cards = $catalog_stmt->fetchAll();

                    if (empty($cards)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-store-slash"></i>
                            <h3>No active food listings available</h3>
                            <p>Check back later or search the main feed for recent postings.</p>
                        </div>
                    <?php else: ?>
                        <div class="catalog-grid" id="catalogGrid">
                            <?php foreach ($cards as $card): 
                                // Check if user has requested this card
                                $check_req = $pdo->prepare("SELECT id FROM requests WHERE donation_id = ? AND consumer_id = ? AND status = 'pending'");
                                $check_req->execute([$card['id'], $user_id]);
                                $has_requested = (bool)$check_req->fetch();
                                ?>
                                <div class="catalog-card" data-search="<?php echo strtolower(htmlspecialchars($card['food_item'] . ' ' . $card['pickup_address'])); ?>">
                                    <div class="catalog-card-body">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                                Donor: <?php echo htmlspecialchars($card['donor_name']); ?>
                                            </span>
                                            <span class="status-badge status-<?php echo $card['status']; ?>">
                                                <?php echo $card['status']; ?>
                                            </span>
                                        </div>
                                        <h3 style="font-size: 16px; font-weight: 750; margin-bottom: 8px; color: var(--text-primary);">
                                            <?php echo htmlspecialchars($card['food_item']); ?>
                                        </h3>
                                        
                                        <?php if (!empty($card['description'])): ?>
                                            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 12px; height: 40px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-clamp: 2;">
                                                <?php echo htmlspecialchars($card['description']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <div style="font-size: 12.5px; display: flex; flex-direction: column; gap: 6px; padding: 10px; background-color: var(--background); border-radius: 6px; margin-bottom: 16px;">
                                            <div style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary);">
                                                <i class="fa-solid fa-boxes-stacked" style="color: var(--primary); width: 14px;"></i>
                                                <span>Qty: <?php echo htmlspecialchars($card['quantity']); ?></span>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary);">
                                                <i class="fa-solid fa-hourglass-half" style="color: var(--primary); width: 14px;"></i>
                                                <span>Exp: <?php echo date('M d, Y h:i A', strtotime($card['expiry_time'])); ?></span>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary);">
                                                <i class="fa-solid fa-map-pin" style="color: var(--primary); width: 14px;"></i>
                                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Loc: <?php echo htmlspecialchars($card['pickup_address']); ?></span>
                                            </div>
                                        </div>

                                        <?php if ($has_requested): ?>
                                            <button class="btn btn-secondary btn-block" disabled>
                                                <i class="fa-solid fa-circle-check" style="color: var(--primary);"></i> Requested
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-primary btn-block" onclick="openRequestModal(<?php echo $card['id']; ?>, '<?php echo addslashes(htmlspecialchars($card['food_item'])); ?>', '<?php echo addslashes(htmlspecialchars($card['quantity'])); ?>')">
                                                <i class="fa-solid fa-hand-holding-hand"></i> Request This Food
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- REQUEST MODAL (Catalog modal) -->
                    <div class="modal-overlay" id="requestModal">
                        <div class="modal-container">
                            <div class="modal-header">
                                <h3 class="modal-title">Submit Food Request</h3>
                                <button class="modal-close" onclick="closeRequestModal()">&times;</button>
                            </div>
                            <form action="dashboard.php?page=request-donation" method="POST">
                                <input type="hidden" name="action" value="request_donation">
                                <input type="hidden" name="donation_id" id="modal-donation-id" value="">
                                
                                <div class="modal-body">
                                    <p style="margin-bottom: 16px; font-size: 14px; color: var(--text-secondary);">
                                        You are requesting food from the post: <strong id="modal-food-title" style="color: var(--text-primary);"></strong>.
                                    </p>
                                    
                                    <div class="form-group">
                                        <label for="quantity_requested" class="form-label">Quantity Needed</label>
                                        <input type="text" name="quantity_requested" id="modal-quantity-requested" class="form-control" required>
                                        <div class="validation-hint">Total listing quantity is <span id="modal-food-quantity"></span>.</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="message" class="form-label">Message to Donor (Optional)</label>
                                        <textarea name="message" id="message" class="form-control" rows="3" placeholder="Specify organization description or collection details..."></textarea>
                                    </div>
                                </div>

                                <div class="modal-body" style="padding-top: 0; text-align: right; border-top: 1px solid var(--border); padding-top: 16px;">
                                    <button type="button" class="btn btn-secondary" onclick="closeRequestModal()" style="margin-right: 8px;">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Submit Request</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <script>
                        // Search filtering
                        document.getElementById('catalogSearch').addEventListener('input', function() {
                            const query = this.value.toLowerCase().trim();
                            const cards = document.querySelectorAll('#catalogGrid .catalog-card');
                            
                            cards.forEach(card => {
                                const searchText = card.getAttribute('data-search');
                                if (searchText.includes(query)) {
                                    card.style.display = 'flex';
                                } else {
                                    card.style.display = 'none';
                                }
                            });
                        });

                        function openRequestModal(id, title, maxQty) {
                            document.getElementById('modal-donation-id').value = id;
                            document.getElementById('modal-food-title').textContent = title;
                            document.getElementById('modal-food-quantity').textContent = maxQty;
                            document.getElementById('modal-quantity-requested').value = maxQty;
                            document.getElementById('requestModal').classList.add('active');
                        }
                        function closeRequestModal() {
                            document.getElementById('requestModal').classList.remove('active');
                        }
                    </script>

                <?php
                // =============================================================
                // TAB: NOTIFICATIONS
                // =============================================================
                } elseif ($page === 'notifications') {
                    $notifications = get_user_notifications($pdo, $user_id);
                    ?>
                    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px;">
                        <p style="color: var(--text-secondary); font-size: 14.5px; margin: 0;">Your latest activity updates are shown here. You can also email notifications if configured.</p>

                        <?php if (!empty($notifications)): ?>
                            <form action="dashboard.php?page=notifications" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="mark_notifications_read">
                                <button type="submit" class="btn btn-secondary" style="min-width: 190px;">Mark All as Read</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-bell-slash"></i>
                            <h3>No notifications yet</h3>
                            <p>Notifications about accepted or rejected requests will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                    <div class="notification-card-main">
                                        <div>
                                            <h4><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $notification['type']))); ?></h4>
                                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                        </div>
                                        <span class="notification-meta"><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></span>
                                    </div>
                                    <?php if (!empty($notification['link'])): ?>
                                        <div class="notification-card-footer">
                                            <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-outline">View Details</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php
                // =============================================================
                // TAB: MANAGE DONATION (Approve/reject requests for the user's donations)
                // =============================================================
                } elseif ($page === 'manage-donation') {
                    // Fetch requests made to current user's listings
                    $reqs_stmt = $pdo->prepare("
                        SELECT r.*, d.food_item, d.quantity AS total_quantity, u.name AS consumer_name, u.phone AS consumer_phone, u.address AS consumer_address
                        FROM requests r
                        JOIN donations d ON r.donation_id = d.id
                        JOIN users u ON r.consumer_id = u.id
                        WHERE d.donor_id = ?
                        ORDER BY r.created_at DESC
                    ");
                    $reqs_stmt->execute([$user_id]);
                    $my_requests = $reqs_stmt->fetchAll();
                    ?>
                    <p style="color: var(--text-secondary); font-size: 14.5px; margin-bottom: 20px;">Review requests from other users for your listed foods. Once you approve a request, other pending requests for the same food will automatically close.</p>
                    
                    <?php if (empty($my_requests)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-inbox"></i>
                            <h3>No requests received yet</h3>
                            <p>When other users request food from your listings, they will show up here for approval.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Food Item</th>
                                        <th>Requested By</th>
                                        <th>Quantity Asked</th>
                                        <th>Request Message</th>
                                        <th>Date Submitted</th>
                                        <th>Status</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_requests as $req): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($req['food_item']); ?></strong>
                                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">Total Post Qty: <?php echo htmlspecialchars($req['total_quantity']); ?></div>
                                            </td>
                                            <td>
                                                <div><strong><?php echo htmlspecialchars($req['consumer_name']); ?></strong></div>
                                                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                    <i class="fa-solid fa-phone" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($req['consumer_phone']); ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-secondary);">
                                                    <i class="fa-solid fa-map-pin" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($req['consumer_address']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($req['quantity_requested']); ?></span>
                                            </td>
                                            <td style="max-width: 250px;">
                                                <span style="font-size: 13px; font-style: italic; color: var(--text-secondary);">
                                                    <?php echo !empty($req['message']) ? '"' . htmlspecialchars($req['message']) . '"' : '<span style="color:var(--text-muted)">No message</span>'; ?>
                                                </span>
                                            </td>
                                            <td style="font-size: 13px; color: var(--text-muted);">
                                                <?php echo date('M d, Y', strtotime($req['created_at'])); ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $req['status']; ?>">
                                                    <?php echo $req['status']; ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <?php if ($req['status'] === 'pending'): ?>
                                                    <div class="table-action-group" style="justify-content: flex-end;">
                                                        <form action="dashboard.php?page=manage-donation" method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="approve_request">
                                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                            <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 12.5px;" onclick="return confirm('Are you sure you want to approve this request? This will decline other pending requests for the same listing.');">
                                                                <i class="fa-solid fa-circle-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form action="dashboard.php?page=manage-donation" method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="reject_request">
                                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12.5px; background: rgba(239, 68, 68, 0.1); color: #ef4444;" onclick="return confirm('Are you sure you want to reject this request?');">
                                                                <i class="fa-solid fa-circle-xmark"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php elseif ($req['status'] === 'approved'): ?>
                                                    <form action="dashboard.php?page=manage-donation" method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="complete_donation">
                                                        <input type="hidden" name="donation_id" value="<?php echo $req['donation_id']; ?>">
                                                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 12.5px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);" onclick="return confirm('Has the consumer picked up this food?');">
                                                            <i class="fa-solid fa-truck-ramp-box"></i> Picked Up
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="font-size: 12.5px; color: var(--text-muted);">Processed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                <?php
                // =============================================================
                // TAB: MANAGE REQUEST (View and cancel requests made by user)
                // =============================================================
                } elseif ($page === 'manage-request') {
                    // Fetch requests made by current user
                    $c_reqs_stmt = $pdo->prepare("
                        SELECT r.*, d.food_item, d.pickup_address, d.phone AS donor_phone, d.status AS donation_status, u.name AS donor_name
                        FROM requests r
                        JOIN donations d ON r.donation_id = d.id
                        JOIN users u ON d.donor_id = u.id
                        WHERE r.consumer_id = ?
                        ORDER BY r.created_at DESC
                    ");
                    $c_reqs_stmt->execute([$user_id]);
                    $my_requests = $c_reqs_stmt->fetchAll();
                    ?>
                    <p style="color: var(--text-secondary); font-size: 14.5px; margin-bottom: 20px;">View the status of requests you have made. You can cancel pending requests at any time.</p>
                    
                    <?php if (empty($my_requests)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                            <h3>You haven't requested any food yet</h3>
                            <p>Explore the feed and submit requests for available food items to get started.</p>
                            <a href="dashboard.php?page=home" class="btn btn-primary">
                                <i class="fa-solid fa-house-chimney"></i> View Feed
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Food Item</th>
                                        <th>Donor Details</th>
                                        <th>Quantity Requested</th>
                                        <th>Pickup Address</th>
                                        <th>Date Submitted</th>
                                        <th>Request Status</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_requests as $req): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($req['food_item']); ?></strong>
                                            </td>
                                            <td>
                                                <div><strong><?php echo htmlspecialchars($req['donor_name']); ?></strong></div>
                                                <?php if ($req['status'] === 'approved' || $req['status'] === 'completed'): ?>
                                                    <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                                        <i class="fa-solid fa-phone" style="font-size: 10px; margin-right: 3px;"></i> <?php echo htmlspecialchars($req['donor_phone']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 2px;">Phone unlocked upon approval</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-weight: 600; color: var(--secondary);"><?php echo htmlspecialchars($req['quantity_requested']); ?></span>
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; color: var(--text-secondary);"><?php echo htmlspecialchars($req['pickup_address']); ?></span>
                                            </td>
                                            <td style="font-size: 13px; color: var(--text-muted);">
                                                <?php echo date('M d, Y', strtotime($req['created_at'])); ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $req['status']; ?>">
                                                    <?php echo $req['status']; ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <?php if ($req['status'] === 'pending' || $req['status'] === 'approved'): ?>
                                                    <form action="dashboard.php?page=manage-request" method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="cancel_request">
                                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                        <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12.5px; background: rgba(239, 68, 68, 0.1); color: #ef4444;" onclick="return confirm('Are you sure you want to cancel this request?');">
                                                            <i class="fa-solid fa-ban"></i> Cancel Request
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="font-size: 12.5px; color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                <?php
                // =============================================================
                // TAB: TRACK DONATION (Track posted items visually)
                // =============================================================
                } elseif ($page === 'track-donation') {
                    // Fetch user's donations
                    $track_stmt = $pdo->prepare("
                        SELECT d.*, 
                               (SELECT COUNT(*) FROM requests r WHERE r.donation_id = d.id AND r.status = 'pending') AS pending_requests_count,
                               (SELECT u.name FROM requests r JOIN users u ON r.consumer_id = u.id WHERE r.donation_id = d.id AND r.status = 'approved' LIMIT 1) AS approved_consumer_name
                        FROM donations d 
                        WHERE d.donor_id = ?
                        ORDER BY d.created_at DESC
                    ");
                    $track_stmt->execute([$user_id]);
                    $my_donations = $track_stmt->fetchAll();
                    ?>
                    <p style="color: var(--text-secondary); font-size: 14.5px; margin-bottom: 20px;">Track the pickup and collection flow of all food donations you have posted.</p>
                    
                    <?php if (empty($my_donations)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-map-location-dot"></i>
                            <h3>No donations to track</h3>
                            <p>Once you post food listings, you will see a visual process tracker here.</p>
                            <a href="dashboard.php?page=create-donation" class="btn btn-primary">
                                <i class="fa-solid fa-circle-plus"></i> List Food Donation
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($my_donations as $don): 
                            $step = 1;
                            $bar_width = '0%';
                            
                            if ($don['status'] === 'available') {
                                $step = 1;
                                $bar_width = '0%';
                            } elseif ($don['status'] === 'requested') {
                                $step = 2;
                                $bar_width = '33%';
                            } elseif ($don['status'] === 'accepted') {
                                $step = 3;
                                $bar_width = '66%';
                            } elseif ($don['status'] === 'completed') {
                                $step = 4;
                                $bar_width = '100%';
                            }
                            ?>
                            <div class="timeline-card">
                                <div class="timeline-card-header">
                                    <div>
                                        <h3 style="font-size: 16px; font-weight: 800; color: var(--text-primary);"><?php echo htmlspecialchars($don['food_item']); ?></h3>
                                        <div style="font-size: 12.5px; color: var(--text-secondary); margin-top: 4px;">
                                            Qty: <strong><?php echo htmlspecialchars($don['quantity']); ?></strong> | 
                                            Posted: <strong><?php echo date('M d, Y', strtotime($don['created_at'])); ?></strong>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?php echo $don['status']; ?>">
                                            <?php echo $don['status']; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Horizontal timeline tracker -->
                                <?php if ($don['status'] === 'cancelled'): ?>
                                    <div style="background-color: rgba(239, 68, 68, 0.05); border: 1.5px dashed rgba(239, 68, 68, 0.2); padding: 16px; border-radius: 8px; text-align: center; color: #ef4444; font-size: 14px; font-weight: 600;">
                                        <i class="fa-solid fa-ban" style="margin-right: 6px;"></i> This listing has been cancelled.
                                    </div>
                                <?php else: ?>
                                    <div class="timeline-tracker">
                                        <div class="timeline-progress-bar" style="width: <?php echo $bar_width; ?>;"></div>
                                        
                                        <div class="timeline-step <?php echo $step >= 1 ? 'completed' : ''; ?> <?php echo $step === 1 ? 'active' : ''; ?>">
                                            <div class="timeline-dot"><i class="fa-solid fa-bullhorn"></i></div>
                                            <div class="timeline-step-label">1. Food Posted</div>
                                        </div>
                                        
                                        <div class="timeline-step <?php echo $step >= 2 ? 'completed' : ''; ?> <?php echo $step === 2 ? 'active' : ''; ?>">
                                            <div class="timeline-dot"><i class="fa-solid fa-bell"></i></div>
                                            <div class="timeline-step-label">2. Requested</div>
                                        </div>

                                        <div class="timeline-step <?php echo $step >= 3 ? 'completed' : ''; ?> <?php echo $step === 3 ? 'active' : ''; ?>">
                                            <div class="timeline-dot"><i class="fa-solid fa-handshake-angle"></i></div>
                                            <div class="timeline-step-label">3. Approved</div>
                                        </div>

                                        <div class="timeline-step <?php echo $step >= 4 ? 'completed' : ''; ?> <?php echo $step === 4 ? 'active' : ''; ?>">
                                            <div class="timeline-dot"><i class="fa-solid fa-circle-check"></i></div>
                                            <div class="timeline-step-label">4. Picked Up</div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 45px; border-top: 1px solid var(--border); padding-top: 12px; font-size: 13px; color: var(--text-secondary); display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <?php if ($don['status'] === 'available'): ?>
                                                <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> Visible on the feed. Waiting for other users to request.
                                            <?php elseif ($don['status'] === 'requested'): ?>
                                                <i class="fa-solid fa-circle-exclamation" style="color: var(--status-requested);"></i> Received <strong><?php echo $don['pending_requests_count']; ?></strong> request(s). Please review in <a href="dashboard.php?page=manage-donation" style="color: var(--primary); font-weight:600; text-decoration:none;">Manage Donation</a>.
                                            <?php elseif ($don['status'] === 'accepted'): ?>
                                                <i class="fa-solid fa-truck" style="color: var(--secondary);"></i> Approved request from <strong><?php echo htmlspecialchars($don['approved_consumer_name']); ?></strong>. Awaiting collection.
                                            <?php elseif ($don['status'] === 'completed'): ?>
                                                <i class="fa-solid fa-heart" style="color: var(--primary);"></i> Completed successfully! The food was claimed and picked up.
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($don['status'] === 'accepted'): ?>
                                            <form action="dashboard.php?page=track-donation" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="complete_donation">
                                                <input type="hidden" name="donation_id" value="<?php echo $don['id']; ?>">
                                                <button type="submit" class="btn btn-primary" style="padding: 5px 10px; font-size: 11.5px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                                                    Mark as Picked Up
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php
                // =============================================================
                // TAB: TRACK REQUEST (Track user requests visually)
                // =============================================================
                } elseif ($page === 'track-request') {
                    // Fetch requests made by this user
                    $c_track_stmt = $pdo->prepare("
                        SELECT r.*, d.food_item, d.pickup_address, d.status AS donation_status, u.name AS donor_name, u.phone AS donor_phone
                        FROM requests r 
                        JOIN donations d ON r.donation_id = d.id 
                        JOIN users u ON d.donor_id = u.id
                        WHERE r.consumer_id = ?
                        ORDER BY r.created_at DESC
                    ");
                    $c_track_stmt->execute([$user_id]);
                    $my_requests = $c_track_stmt->fetchAll();
                    ?>
                    <p style="color: var(--text-secondary); font-size: 14.5px; margin-bottom: 20px;">Track the approval and fulfillment status of food donation requests you have sent.</p>
                    
                    <?php if (empty($my_requests)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-route"></i>
                            <h3>No requests to track</h3>
                            <p>Explore the feed and submit requests for available food to start tracking.</p>
                            <a href="dashboard.php?page=home" class="btn btn-primary">
                                <i class="fa-solid fa-house-chimney"></i> Go to Feed
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($my_requests as $req): 
                            $step = 1;
                            $bar_width = '0%';
                            
                            if ($req['status'] === 'pending') {
                                $step = 1;
                                $bar_width = '0%';
                            } elseif ($req['status'] === 'approved') {
                                $step = 2;
                                $bar_width = '50%';
                            } elseif ($req['status'] === 'completed') {
                                $step = 3;
                                $bar_width = '100%';
                            }
                            ?>
                            <div class="timeline-card">
                                <div class="timeline-card-header">
                                    <div>
                                        <h3 style="font-size: 16px; font-weight: 800; color: var(--text-primary);"><?php echo htmlspecialchars($req['food_item']); ?></h3>
                                        <div style="font-size: 12.5px; color: var(--text-secondary); margin-top: 4px;">
                                            Donor: <strong><?php echo htmlspecialchars($req['donor_name']); ?></strong> | 
                                            Asked for: <strong><?php echo htmlspecialchars($req['quantity_requested']); ?></strong>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?php echo $req['status']; ?>">
                                            <?php echo $req['status']; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Horizontal timeline tracker -->
                                <?php if ($req['status'] === 'cancelled'): ?>
                                    <div style="background-color: rgba(239, 68, 68, 0.05); border: 1.5px dashed rgba(239, 68, 68, 0.2); padding: 16px; border-radius: 8px; text-align: center; color: #ef4444; font-size: 14px; font-weight: 600;">
                                        <i class="fa-solid fa-ban" style="margin-right: 6px;"></i> You cancelled this request.
                                    </div>
                                <?php elseif ($req['status'] === 'rejected'): ?>
                                    <div style="background-color: rgba(239, 68, 68, 0.05); border: 1.5px dashed rgba(239, 68, 68, 0.2); padding: 16px; border-radius: 8px; text-align: center; color: #ef4444; font-size: 14px; font-weight: 600;">
                                        <i class="fa-solid fa-circle-xmark" style="margin-right: 6px;"></i> This request was declined by the donor.
                                    </div>
                                <?php else: ?>
                                    <div class="timeline-tracker" style="max-width: 500px; margin-left: auto; margin-right: auto;">
                                        <div class="timeline-progress-bar" style="width: <?php echo $bar_width; ?>;"></div>
                                        
                                        <div class="timeline-step <?php echo $step >= 1 ? 'completed' : ''; ?> <?php echo $step === 1 ? 'active' : ''; ?>">
                                            <div class="timeline-dot"><i class="fa-solid fa-file-export"></i></div>
                                            <div class="timeline-step-label">1. Sent</div>
                                        </div>
                                        
                                        <div class="timeline-step <?php echo $step >= 2 ? 'completed' : ''; ?> <?php echo $step === 2 ? 'active' : ''; ?>">
                                            <div class="timeline-dot"><i class="fa-solid fa-handshake"></i></div>
                                            <div class="timeline-step-label">2. Approved</div>
                                        </div>

                                        <div class="timeline-step <?php echo $step >= 3 ? 'completed' : ''; ?> <?php echo $step === 3 ? 'active' : ''; ?>">
                                            <div class="timeline-dot"><i class="fa-solid fa-circle-check"></i></div>
                                            <div class="timeline-step-label">3. Completed</div>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 45px; border-top: 1px solid var(--border); padding-top: 12px; font-size: 13px; color: var(--text-secondary); display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <i class="fa-solid fa-clock" style="color: var(--status-pending);"></i> Request is pending review by the donor: <strong><?php echo htmlspecialchars($req['donor_name']); ?></strong>.
                                            <?php elseif ($req['status'] === 'approved'): ?>
                                                <i class="fa-solid fa-phone-volume" style="color: var(--primary);"></i> <strong>Approved!</strong> Please coordinate pickup at: <strong><?php echo htmlspecialchars($req['pickup_address']); ?></strong>. Contact: <strong><?php echo htmlspecialchars($req['donor_phone']); ?></strong>.
                                            <?php elseif ($req['status'] === 'completed'): ?>
                                                <i class="fa-solid fa-face-smile" style="color: var(--primary);"></i> Food pickup completed. Thank you for utilizing surplus food!
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <form action="dashboard.php?page=track-request" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                <button type="submit" class="btn btn-secondary" style="padding: 5px 10px; font-size: 11.5px; color: #ef4444; background: rgba(239, 68, 68, 0.05);" onclick="return confirm('Cancel request?');">
                                                    Cancel Request
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php
                // =============================================================
                // TAB: PROFILE
                // =============================================================
                } elseif ($page === 'profile') {
                    ?>
                    <div class="profile-grid">
                        
                        <!-- Left Info Panel -->
                        <div class="profile-card-left">
                            <div class="large-avatar">
                                <?php echo substr($initials, 0, 2); ?>
                            </div>
                            <h3 style="font-size: 18px; font-weight: 800; color: var(--text-primary);"><?php echo htmlspecialchars($user_name); ?></h3>
                            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;"><?php echo htmlspecialchars($user_email); ?></div>
                            
                            <div style="text-align: left; padding-top: 20px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 12px; font-size: 13.5px; color: var(--text-secondary);">
                                <div>
                                    <i class="fa-solid fa-map-pin" style="width: 20px; color: var(--primary);"></i>
                                    <span>Address: <strong><?php echo htmlspecialchars($user_address); ?></strong></span>
                                </div>
                                <div>
                                    <i class="fa-solid fa-phone" style="width: 20px; color: var(--primary);"></i>
                                    <span>Phone: <strong><?php echo htmlspecialchars($user_phone); ?></strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Edit Panel -->
                        <div class="profile-card-right">
                            <h3 style="font-size: 16px; font-weight: 750; color: var(--text-primary); margin-bottom: 20px; border-bottom: 1.5px solid var(--border); padding-bottom: 10px;">
                                Update Profile Settings
                            </h3>
                            
                            <form action="dashboard.php?page=profile" method="POST" id="profileForm" novalidate>
                                <input type="hidden" name="action" value="update_profile">

                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address (Read-only)</label>
                                    <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background-color: var(--background); cursor: not-allowed; color: var(--text-muted);">
                                </div>

                                <div class="form-group">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($user_address); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number (Nepal)</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_phone); ?>" required>
                                    <div class="validation-hint" id="phone-hint"> Nepal mobile (98XXXXXXXX) or landline (01XXXXXXX)</div>
                                </div>

                                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <script>
                        (function() {
                            const phoneInput = document.getElementById('phone');
                            const phoneHint = document.getElementById('phone-hint');
                            if (!phoneInput || !phoneHint) return;

                            phoneInput.addEventListener('input', function() {
                                const val = phoneInput.value.trim();
                                const mobilePattern = /^(98|97|96)\d{8}$/;
                                const landlinePattern = /^01\d{7}$/;
                                
                                if (val.length === 0) {
                                    phoneHint.style.color = '';
                                    phoneHint.textContent = 'Nepal mobile (98XXXXXXXX) or landline (01XXXXXXX)';
                                } else if (mobilePattern.test(val) || landlinePattern.test(val)) {
                                    phoneHint.style.color = '#10b981';
                                    phoneHint.innerHTML = '<i class="fa-solid fa-circle-check"></i> Valid Nepalese Phone Number';
                                } else {
                                    phoneHint.style.color = '#ef4444';
                                    phoneHint.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Invalid format. Use 98/97/96 (10 digits) or 01 (9 digits)';
                                }
                            });
                        })();
                    </script>
                    <?php
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Mobile sidebar responsive drawer toggle -->
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');

        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        // Close sidebar if clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && e.target !== menuToggle && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>
