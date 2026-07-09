<?php
require_once 'config.php';

$donation_id = intval($_GET['id'] ?? 0);
$donation = get_donation_by_id($pdo, $donation_id);

if (!$donation) {
    http_response_code(404);
}

$redirect = 'donation.php?id=' . $donation_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $donation ? htmlspecialchars($donation['food_item']) : 'Donation Not Found'; ?> | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/app.js"></script>
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --text-dark: #1f2937;
            --text-light: #4b5563;
            --bg-light: #f9fafb;
            --border-color: #e5e7eb;
            --radius-md: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Clean Header Fixes */
        .site-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .site-branding .site-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .site-nav a {
            color: var(--text-light);
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            transition: var(--transition);
        }
        .site-nav a:hover {
            color: var(--primary-color);
        }

        /* Container & Layout */
        .site-main {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: calc(100vh - 230px);
        }

        .section-block {
            background: #ffffff;
            border-radius: var(--radius-md);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        /* Split Screen Grid Layout */
        .detail-card-grid {
            display: grid;
            grid-template-columns: 1fr;
        }
        @media(min-width: 768px) {
            .detail-card-grid {
                grid-template-columns: 45% 55%;
            }
        }

        /* Food Media Component */
        .detail-card-image {
            position: relative;
            background: #f3f4f6;
            min-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .detail-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            transition: var(--transition);
        }
        .detail-card-image:hover img {
            transform: scale(1.02);
        }
        .product-placeholder {
            font-size: 4rem;
            color: #9ca3af;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        /* Details Section */
        .detail-card-body {
            padding: 40px;
            display: flex;
            flex-direction: column;
        }
        .detail-card-body h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 15px;
            color: #111827;
            letter-spacing: -0.025em;
        }
        .description-text {
            color: var(--text-light);
            font-size: 1.05rem;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        /* Information Grid Cards */
        .info-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 35px;
        }
        .info-card {
            background: #fafafa;
            border: 1px solid var(--border-color);
            padding: 16px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .info-card i {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-top: 3px;
            width: 20px;
            text-align: center;
        }
        .info-card-content strong {
            display: block;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .info-card-content span {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Custom Adaptive Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-available { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-claimed { background-color: #e0e7ff; color: #3730a3; }
        .status-expired { background-color: #fee2e2; color: #991b1b; }

        /* Unified Form Styling Elements */
        .request-form-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-sm);
            padding: 25px;
            margin-top: 10px;
        }
        .request-form-section h2 {
            font-size: 1.35rem;
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            background-color: #ffffff;
            box-sizing: border-box;
            transition: var(--transition);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        /* Buttons & Dynamic Alerts */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            width: 100%;
            box-sizing: border-box;
        }
        .btn-primary {
            background: var(--primary-color);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        .btn-outline:hover {
            background: var(--primary-color);
            color: #ffffff;
        }
        .btn-whatsapp {
            margin-top: 12px;
        }

        .alert {
            padding: 16px;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-left: 4px solid transparent;
        }
        .alert p { margin: 0; font-weight: 500; }
        .alert-info {
            background-color: #eff6ff;
            color: #1e40af;
            border-left-color: #3b82f6;
        }
        .alert-warning {
            background-color: #fffbeb;
            color: #92400e;
            border-left-color: #f59e0b;
        }

        /* Empty Fallback State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
        }
        .empty-state i {
            font-size: 3.5rem;
            color: #ef4444;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: var(--text-light);
            margin-bottom: 25px;
        }

        /* Clean Footer styling */
        .site-footer {
            background: #ffffff;
            border-top: 1px solid var(--border-color);
            text-align: center;
            padding: 20px;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 60px;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="site-branding">
            <a href="index.php" class="site-logo"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</a>
        </div>
       <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="donations.php" class="active" style="color: #059669;">Food Listings</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Login</a>
            <!-- <a href="register.php">Get Started</a> -->
            <a href="register.php" style="background: #059669; color:#fff">Get Started</a>

        </nav>
    </header>

    <main class="site-main">
        <?php if (!$donation): ?>
            <section class="section-block">
                <div class="empty-state">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <h3>Donation Not Found</h3>
                    <p>The requested food listing is no longer available or does not exist.</p>
                    <a href="donations.php" class="btn btn-primary" style="width: auto;"><i class="fa-solid fa-arrow-left"></i> Back to Food Listings</a>
                </div>
            </section>
        <?php else: ?>
            <section class="section-block">
                <div class="detail-card">
                    <div class="detail-card-grid">
                        
                        <div class="detail-card-image">
                            <?php if (!empty($donation['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($donation['image_path']); ?>" alt="<?php echo htmlspecialchars($donation['food_item']); ?>">
                            <?php else: ?>
                                <div class="product-placeholder">
                                    <i class="fa-solid fa-bowl-food"></i>
                                    <span style="font-size: 1rem; font-weight: 500;">No Image Provided</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="detail-card-body">
                            <h1><?php echo htmlspecialchars($donation['food_item']); ?></h1>
                            <div class="description-text">
                                <?php echo nl2br(htmlspecialchars($donation['description'])); ?>
                            </div>
                            
                            <div class="info-cards-grid">
                                <div class="info-card">
                                    <i class="fa-solid fa-cubes" style="color:#059669"></i>
                                    <div class="info-card-content">
                                        <strong>Quantity Available</strong>
                                        <span><?php echo htmlspecialchars($donation['quantity']); ?></span>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <i class="fa-solid fa-clock" style="color:#059669"></i>
                                    <div class="info-card-content">
                                        <strong>Expires On</strong>
                                        <span class="countdown-badge" data-expiry="<?php echo $donation['expiry_time']; ?>">⏳ Loading...</span>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <i class="fa-solid fa-location-dot" style="color:#059669"></i>
                                    <div class="info-card-content">
                                        <strong>Pickup Address</strong>
                                        <span><?php echo htmlspecialchars($donation['pickup_address']); ?></span>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <i class="fa-solid fa-user" style="color:#059669"></i>
                                    <div class="info-card-content">
                                        <strong>Posted By Donor</strong>
                                        <span><?php echo htmlspecialchars($donation['donor_name']); ?></span>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <i class="fa-solid fa-phone" style="color:#059669"></i>
                                    <div class="info-card-content">
                                        <strong>Contact Number</strong>
                                        <span><?php echo htmlspecialchars($donation['phone']); ?></span>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <i class="fa-solid fa-circle-info" style="color:#059669"></i>
                                    <div class="info-card-content">
                                        <strong>Current Status</strong>
                                        <div>
                                            <?php 
                                                $status = strtolower($donation['status']);
                                                $status_class = 'status-available';
                                                if ($status === 'pending') $status_class = 'status-pending';
                                                if ($status === 'claimed') $status_class = 'status-claimed';
                                                if ($status === 'expired') $status_class = 'status-expired';
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars(ucfirst($donation['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code Verification -->
                            <div class="qr-section">
                                <h4><i class="fa-solid fa-qrcode"></i> QR Pickup Verification</h4>
                                <p>Show this QR code to the donor at pickup to verify your identity.</p>
                                <?php
                                $qr_token = get_or_create_qr_token($pdo, $donation['id']);
                                $qr_img = get_qr_image_url($qr_token, 200);
                                ?>
                                <img src="<?php echo htmlspecialchars($qr_img); ?>" alt="QR Code" class="qr-code-img" id="donationQRCode">
                                <span class="qr-token-text">Token: <?php echo htmlspecialchars($qr_token); ?></span>
                                <a href="qr-scan.php?token=<?php echo urlencode($qr_token); ?>" target="_blank" class="qr-verify-link">
                                    <i class="fa-solid fa-external-link-alt"></i> Open Verification Page
                                </a>
                            </div>

                            <!-- Location Map -->
                            <div style="margin-top:24px;">
                                <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                                    <i class="fa-solid fa-map-location-dot" style="color:#059669;"></i> Pickup Location
                                </h3>
                                <div class="map-container-single" id="singleDonationMap"></div>
                                <div style="margin-top:10px;">
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($donation['pickup_address']); ?>" target="_blank" class="btn btn-outline" style="background:rgba(59,130,246,0.08);color:#3b82f6;border-color:rgba(59,130,246,0.2);width:auto;display:inline-flex;">
                                        <i class="fa-solid fa-map-pin"></i> Open in Google Maps
                                    </a>
                                </div>
                            </div>

                            <?php if (!is_logged_in()): ?>
                                <div class="alert alert-info">
                                    <p style="color:#059669"><i class="fa-solid fa-circle-user" style="color:#059669"></i> Authentication required to process request.</p>
                                    <a href="login.php?redirect=<?php echo urlencode($redirect); ?>" class="btn btn-primary " style="background: #059669; color:#fff"><i class="fa-solid fa-right-to-bracket"></i> Log in to Request</a>
                                </div>
                            <?php elseif ($_SESSION['user_id'] === $donation['donor_id']): ?>
                                <div class="alert alert-warning">
                                    <p><i class="fa-solid fa-shield-halved"></i> Management restriction applied. You are the listing provider.</p>
                                    <a href="dashboard.php?page=manage-donation" class="btn btn-outline"><i class="fa-solid fa-sliders"></i> Manage My Donations</a>
                                </div>
                            <?php else: ?>
                                <div class="request-form-section">
                                    <h2><i class="fa-solid fa-paper-plane" style="color: var(--primary-color);"></i> Request Donation</h2>
                                    <form action="dashboard.php?page=request-donation" method="POST" novalidate>
                                        <input type="hidden" name="action" value="request_donation">
                                        <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                        
                                        <div class="form-group">
                                            <label standalone for="quantity_requested" class="form-label">Quantity Needed</label>
                                            <input type="text" id="quantity_requested" name="quantity_requested" class="form-control" placeholder="e.g., 5 kg, 10 packs" required>
                                        </div>
                                        <div class="form-group">
                                            <label standalone for="message" class="form-label">Message to Donor (Optional)</label>
                                            <textarea id="message" name="message" class="form-control" rows="3" placeholder="State your requirements or pickup window preferences..."></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Submit Claim Request</button>
                                    </form>
                                    
                                    <?php
                                    if (!empty($donation['phone'])) {
                                        $wa_msg = 'Hello ' . $donation['donor_name'] . ', I am interested in your food donation: ' . $donation['food_item'] . ' on Sayog.';
                                        echo whatsapp_button($donation['phone'], 'Chat via WhatsApp', $wa_msg);
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <?php if ($donation): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.initSingleDonationMap === 'function') {
            window.initSingleDonationMap(
                'singleDonationMap',
                <?php echo json_encode($donation['pickup_address']); ?>,
                <?php echo json_encode($donation['food_item']); ?>,
                <?php echo $donation['id']; ?>
            );
        }
    });
    </script>
    <?php endif; ?>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Sayog. Connecting surplus food with communities.</p>
    </footer>
</body>
</html>