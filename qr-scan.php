<?php
require_once 'config.php';

// This page can be accessed in two ways:
// 1. GET with ?token=XXX - The QR code links here, shows verification info
// 2. POST - Donor submits verification to mark donation as completed

$token = sanitize($_GET['token'] ?? '');

// If a token was submitted via GET (from QR scan), show the verification page
if (!empty($token) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Find the donation by token
    $stmt = $pdo->prepare("SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.qr_token = ?");
    $stmt->execute([$token]);
    $donation = $stmt->fetch();

    if (!$donation) {
        die('<html><head><title>Invalid QR</title><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;"><div class="auth-card" style="text-align:center;"><i class="fa-solid fa-circle-xmark" style="font-size:48px;color:#ef4444;margin-bottom:16px;"></i><h2>Invalid or Expired QR Code</h2><p style="color:var(--text-secondary);">This verification code could not be found. It may have expired or been used already.</p><a href="dashboard.php" class="btn btn-primary" style="margin-top:16px;">Go to Dashboard</a></div></body></html>');
    }

    // Auto-generate QR token if missing
    if (empty($donation['qr_token'])) {
        $token = generate_qr_token($pdo, $donation['id']);
    }

    $is_donor = is_logged_in() && $_SESSION['user_id'] == $donation['donor_id'];
    $is_receiver = false;
    $receiver_name = '';
    if (is_logged_in()) {
        $stmt = $pdo->prepare("SELECT consumer_id FROM requests WHERE donation_id = ? AND status IN ('approved', 'completed') LIMIT 1");
        $stmt->execute([$donation['id']]);
        $req = $stmt->fetch();
        if ($req) {
            $is_receiver = $_SESSION['user_id'] == $req['consumer_id'];
            $stmt2 = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $stmt2->execute([$req['consumer_id']]);
            $receiver_name = $stmt2->fetchColumn();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Verification | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .qr-verify-container { max-width: 520px; margin: 40px auto; padding: 0 20px; }
        .qr-verify-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 32px; text-align: center; box-shadow: var(--shadow-md); }
        .qr-verify-card h2 { font-size: 22px; font-weight: 800; margin-bottom: 8px; color: var(--text-primary); }
        .qr-verify-card .status-icon { font-size: 48px; margin-bottom: 16px; }
        .qr-detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
        .qr-detail-row strong { color: var(--text-secondary); }
        .qr-detail-row span { color: var(--text-primary); font-weight: 600; }
        .qr-verify-btn { margin-top: 24px; padding: 14px 32px; font-size: 16px; }
        .qr-verify-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="qr-verify-container">
        <div class="qr-verify-card">
            <?php if ($donation['status'] === 'completed'): ?>
                <div class="status-icon" style="color:#10b981;"><i class="fa-solid fa-circle-check"></i></div>
                <h2>Already Completed</h2>
                <p style="color:var(--text-secondary);margin-bottom:20px;">This donation has already been picked up and completed.</p>
            <?php elseif ($donation['status'] === 'accepted'): ?>
                <div class="status-icon" style="color:#3b82f6;"><i class="fa-solid fa-qrcode"></i></div>
                <h2><?php echo htmlspecialchars($donation['food_item']); ?></h2>
                <p style="color:var(--text-secondary);">Verify pickup to mark this donation as completed.</p>
                
                <div style="margin:24px 0;text-align:left;">
                    <div class="qr-detail-row"><strong>Donor:</strong> <span><?php echo htmlspecialchars($donation['donor_name']); ?></span></div>
                    <div class="qr-detail-row"><strong>Food:</strong> <span><?php echo htmlspecialchars($donation['food_item']); ?></span></div>
                    <div class="qr-detail-row"><strong>Quantity:</strong> <span><?php echo htmlspecialchars($donation['quantity']); ?></span></div>
                    <div class="qr-detail-row"><strong>Pickup:</strong> <span><?php echo htmlspecialchars($donation['pickup_address']); ?></span></div>
                </div>

                <?php if (is_logged_in() && $is_donor): ?>
                    <form method="POST" action="qr-scan.php">
                        <input type="hidden" name="action" value="verify_pickup">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                        <button type="submit" class="btn btn-primary qr-verify-btn" onclick="return confirm('Confirm pickup completion? This action cannot be undone.');">
                            <i class="fa-solid fa-check-circle"></i> Confirm Pickup Completed
                        </button>
                    </form>
                    <p style="font-size:12px;color:var(--text-muted);margin-top:12px;">Only the donor can mark this donation as picked up.</p>
                <?php elseif (is_logged_in() && $is_receiver): ?>
                    <div class="alert alert-info" style="text-align:left;">
                        <i class="fa-solid fa-info-circle"></i>
                        <span>You are the receiver for this donation. Ask the donor to scan this QR code to verify the pickup.</span>
                    </div>
                    <p style="font-size:12px;color:var(--text-muted);">Only the <strong>donor</strong> can mark this as completed.</p>
                <?php else: ?>
                    <div class="alert alert-warning" style="text-align:left;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>You need to be logged in as the donor to verify this pickup.</span>
                    </div>
                    <a href="login.php?redirect=<?php echo urlencode('qr-scan.php?token=' . urlencode($token)); ?>" class="btn btn-primary" style="margin-top:8px;">
                        <i class="fa-solid fa-right-to-bracket"></i> Login to Verify
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <div class="status-icon" style="color:#f59e0b;"><i class="fa-solid fa-clock"></i></div>
                <h2>Not Ready for Pickup</h2>
                <p style="color:var(--text-secondary);">This donation is currently <strong><?php echo $donation['status']; ?></strong>. It must be in "Accepted" status for pickup verification.</p>
            <?php endif; ?>

            <a href="dashboard.php" class="btn btn-secondary" style="margin-top:16px;width:100%;">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
<?php
    exit();
}

// POST: Handle verification and rating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    $user_id = $_SESSION['user_id'];

    // --- QR Pickup Verification ---
    if ($action === 'verify_pickup') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $token = sanitize($_POST['token'] ?? '');

        // Verify donation exists and user is the donor
        $stmt = $pdo->prepare("SELECT * FROM donations WHERE id = ? AND qr_token = ? AND donor_id = ? AND status = 'accepted'");
        $stmt->execute([$donation_id, $token, $user_id]);
        $donation = $stmt->fetch();

        if (!$donation) {
            set_flash_message('danger', 'Invalid verification. You may not be the donor or the donation is not in accepted status.');
            redirect('dashboard.php?page=track-donation');
        }

        try {
            $pdo->beginTransaction();

            // Mark donation as completed
            $stmt = $pdo->prepare("UPDATE donations SET status = 'completed' WHERE id = ?");
            $stmt->execute([$donation_id]);

            // Mark corresponding approved request as completed
            $stmt = $pdo->prepare("UPDATE requests SET status = 'completed' WHERE donation_id = ? AND status = 'approved'");
            $stmt->execute([$donation_id]);

            // Get the requester
            $stmt = $pdo->prepare("SELECT consumer_id FROM requests WHERE donation_id = ? AND status = 'completed' LIMIT 1");
            $stmt->execute([$donation_id]);
            $requester_id = $stmt->fetchColumn();

            if ($requester_id) {
                // Notify requester
                create_notification($pdo, $requester_id, 'pickup_completed',
                    'The food pickup for "' . $donation['food_item'] . '" has been completed via QR verification. Please rate the donor!',
                    'dashboard.php?page=track-request&rate_donation=' . $donation_id, true);

                // Create initial ratings record
                $stmt = $pdo->prepare("INSERT IGNORE INTO ratings (donation_id, donor_id, receiver_id) VALUES (?, ?, ?)");
                $stmt->execute([$donation_id, $user_id, $requester_id]);
            }

            // Notify donor
            create_notification($pdo, $user_id, 'pickup_verified',
                'QR pickup verified for "' . $donation['food_item'] . '". Please rate the receiver!',
                'dashboard.php?page=track-donation&rate_donation=' . $donation_id, true);

            $pdo->commit();

            // Generate certificate of appreciation
            $stmt_d = $pdo->prepare("SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.id = ?");
            $stmt_d->execute([$donation_id]);
            $don_data = $stmt_d->fetch();
            $donor_name = $don_data['donor_name'] ?? 'Donor';
            $food_name = $don_data['food_item'] ?? 'Food';
            $stmt_r = $pdo->prepare("SELECT u.name FROM requests r JOIN users u ON r.consumer_id = u.id WHERE r.donation_id = ? AND r.status = 'completed' LIMIT 1");
            $stmt_r->execute([$donation_id]);
            $rec_name = $stmt_r->fetchColumn() ?: 'Community';
            generate_donation_certificate($pdo, $donation_id, $donor_name, $food_name, $rec_name);

            set_flash_message('success', '✅ Pickup verified via QR! Donation marked as completed. Please rate the receiver below.');
            redirect('dashboard.php?page=track-donation&rate_donation=' . $donation_id);
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash_message('danger', 'Failed to complete verification: ' . $e->getMessage());
            redirect('dashboard.php?page=track-donation');
        }
    }

    // --- Submit Rating ---
    if ($action === 'submit_rating') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $review = sanitize($_POST['review'] ?? '');
        $role = sanitize($_POST['role'] ?? ''); // 'donor' or 'receiver'

        if ($rating < 1 || $rating > 5) {
            set_flash_message('danger', 'Rating must be between 1 and 5 stars.');
            redirect('dashboard.php');
        }

        // Find the rating record
        $stmt = $pdo->prepare("SELECT * FROM ratings WHERE donation_id = ?");
        $stmt->execute([$donation_id]);
        $rating_record = $stmt->fetch();

        if (!$rating_record) {
            // Create it (should already exist from verification)
            $stmt = $pdo->prepare("INSERT INTO ratings (donation_id, donor_id, receiver_id) VALUES (?, ?, ?)");
            // Need donor and receiver IDs
            $d_stmt = $pdo->prepare("SELECT donor_id FROM donations WHERE id = ?");
            $d_stmt->execute([$donation_id]);
            $donor_id = $d_stmt->fetchColumn();
            $r_stmt = $pdo->prepare("SELECT consumer_id FROM requests WHERE donation_id = ? AND status = 'completed' LIMIT 1");
            $r_stmt->execute([$donation_id]);
            $receiver_id = $r_stmt->fetchColumn();
            $stmt->execute([$donation_id, $donor_id, $receiver_id]);
        }

        // Update the appropriate rating field
        if ($role === 'donor') {
            // Receiver is rating the donor
            $stmt = $pdo->prepare("UPDATE ratings SET rating_donor = ?, review_donor = ? WHERE donation_id = ?");
            $stmt->execute([$rating, $review, $donation_id]);
            set_flash_message('success', 'Thank you! Your rating for the donor has been submitted.');
        } elseif ($role === 'receiver') {
            // Donor is rating the receiver
            $stmt = $pdo->prepare("UPDATE ratings SET rating_receiver = ?, review_receiver = ? WHERE donation_id = ?");
            $stmt->execute([$rating, $review, $donation_id]);
            set_flash_message('success', 'Thank you! Your rating for the receiver has been submitted.');
        } else {
            set_flash_message('danger', 'Invalid rating role.');
        }

        redirect('dashboard.php');
    }
}

// If no token and no POST, redirect
redirect('dashboard.php');
