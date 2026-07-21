<?php
require_once '../config.php';

$donation_id = intval($_GET['id'] ?? 0);
$donation = get_donation_by_id($pdo, $donation_id);

if (!$donation) {
    http_response_code(404);
}

$redirect = 'donation.php?id=' . $donation_id;
?>
<?php
$page_title = 'Sayog | Food Donation Details';
$active_page = 'donations';
require_once '../header.php';
?>
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-light); color: var(--text-dark); margin: 0; padding: 0; line-height: 1.6; }
        .site-main { max-width: 1100px; margin: 20px auto; padding: 0 20px; min-height: calc(100vh - 140px); display: flex; flex-direction: column; }
        .section-block { background: #ffffff; border-radius: var(--radius-md); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; border: 1px solid rgba(0, 0, 0, 0.03); flex: 1; display: flex; flex-direction: column; }
        .detail-card-grid { display: grid; grid-template-columns: 1fr; flex: 1; }
        .detail-card { display: flex; flex-direction: column; flex: 1; max-height: calc(100vh - 140px); }
        @media(min-width: 768px) { .detail-card-grid { grid-template-columns: 45% 55%; max-height: calc(100vh - 140px); } }
        .detail-card-image { position: relative; background: #f3f4f6; height: calc(100vh - 140px); min-height: 300px; max-height: 500px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        @media(max-width: 767px) { .detail-card-image { height: 280px; min-height: 200px; max-height: 280px; } }
        .detail-card-image img { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; transition: var(--transition); }
        .product-placeholder { font-size: 4rem; color: #9ca3af; display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .detail-card-body { padding: 24px 32px; display: flex; flex-direction: column; overflow-y: auto; max-height: calc(100vh - 140px); scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
        .detail-card-body h1 { font-size: 1.6rem; font-weight: 700; margin-top: 0; margin-bottom: 8px; color: #111827; }
        .description-text { color: var(--text-light); font-size: 0.95rem; margin-bottom: 16px; line-height: 1.5; }
        .info-cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 16px; }
        .info-card { background: #fafafa; border: 1px solid var(--border-color); padding: 10px 12px; border-radius: var(--radius-sm); display: flex; align-items: flex-start; gap: 8px; }
        .status-badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; }
        .status-available { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-claimed { background-color: #e0e7ff; color: #3730a3; }
        .status-expired { background-color: #fee2e2; color: #991b1b; }
        .request-form-section { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 14px 16px; margin-top: 8px; }
        .form-group { margin-bottom: 12px; }
        .form-label { display: block; font-weight: 600; font-size: 0.82rem; margin-bottom: 5px; color: var(--text-dark); }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: var(--radius-sm); font-size: 0.85rem; background-color: #ffffff; box-sizing: border-box; transition: var(--transition); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 18px; font-size: 0.85rem; font-weight: 600; border-radius: var(--radius-sm); cursor: pointer; transition: var(--transition); text-decoration: none; border: none; width: 100%; box-sizing: border-box; }
        .btn-primary { background: var(--primary-color); color: #ffffff; }
        .btn-outline { background: transparent; border: 2px solid var(--primary-color); color: var(--primary-color); }
        .alert { padding: 16px; border-radius: var(--radius-sm); font-size: 0.95rem; display: flex; flex-direction: column; gap: 12px; border-left: 4px solid transparent; }
        .alert-info { background-color: #eff6ff; color: #1e40af; border-left-color: #3b82f6; }
        .alert-warning { background-color: #fffbeb; color: #92400e; border-left-color: #f59e0b; }
        .empty-state { padding: 60px 20px; text-align: center; max-width: 500px; margin: 0 auto; }
        .site-footer { background: #ffffff; border-top: 1px solid var(--border-color); text-align: center; padding: 20px; color: var(--text-light); font-size: 0.9rem; margin-top: 60px; }
    </style>


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
                                <img src="<?php echo htmlspecialchars(asset_url($donation['image_path'])); ?>" alt="<?php echo htmlspecialchars($donation['food_item']); ?>">
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

                            <!-- QR Code Verification - Compact -->
                            <div class="qr-section" style="margin-top:12px;padding:12px;">
                                <h4 style="font-size:13px;margin-bottom:2px;"><i class="fa-solid fa-qrcode"></i> QR Pickup Verification</h4>
                                <p style="font-size:11px;margin-bottom:8px;">Show this QR code to the donor at pickup.</p>
                                <?php
                                $qr_token = get_or_create_qr_token($pdo, $donation['id']);
                                $qr_img = get_qr_image_url($qr_token, 160);
                                ?>
                                <img src="<?php echo htmlspecialchars($qr_img); ?>" alt="QR Code" class="qr-code-img" id="donationQRCode" style="width:100px;height:100px;">
                                <span class="qr-token-text" style="font-size:9px;">Token: <?php echo htmlspecialchars($qr_token); ?></span>
                                <a href="qr-scan.php?token=<?php echo urlencode($qr_token); ?>" target="_blank" class="qr-verify-link" style="font-size:11px;padding:5px 12px;">
                                    <i class="fa-solid fa-external-link-alt"></i> Verify
                                </a>
                            </div>

                            <!-- Location Map - Compact -->
                            <div style="margin-top:12px;">
                                <h4 style="font-size:13px;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                                    <i class="fa-solid fa-map-location-dot" style="color:#059669;"></i> Pickup Location
                                </h4>
                                <div class="map-container-single" id="singleDonationMap" style="height:150px;"></div>
                                <div style="margin-top:6px;">
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($donation['pickup_address']); ?>" target="_blank" class="btn btn-outline" style="background:rgba(59,130,246,0.08);color:#3b82f6;border-color:rgba(59,130,246,0.2);width:auto;display:inline-flex;padding:5px 12px;font-size:12px;">
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

    <?php require_once '../footer.php'; ?>
