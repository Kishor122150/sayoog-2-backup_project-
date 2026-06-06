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
</head>
<body>
    <header class="site-header">
        <div class="site-branding">
            <a href="index.php" class="site-logo"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</a>
        </div>
        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="donations.php">Food Listings</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <main class="site-main">
        <?php if (!$donation): ?>
            <section class="section-block">
                <div class="empty-state">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <h3>Donation not found</h3>
                    <p>The requested food listing is no longer available or does not exist.</p>
                    <a href="donations.php" class="btn btn-primary">Back to Food Listings</a>
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
                                <div class="product-placeholder" style="min-height: 240px;"><i class="fa-solid fa-bowl-food"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="detail-card-body">
                            <h1><?php echo htmlspecialchars($donation['food_item']); ?></h1>
                            <p><?php echo nl2br(htmlspecialchars($donation['description'])); ?></p>
                            <div class="detail-meta">
                                <div><strong>Quantity:</strong> <?php echo htmlspecialchars($donation['quantity']); ?></div>
                                <div><strong>Expires:</strong> <?php echo date('M d, Y H:i', strtotime($donation['expiry_time'])); ?></div>
                                <div><strong>Pickup Address:</strong> <?php echo htmlspecialchars($donation['pickup_address']); ?></div>
                                <div><strong>Contact Phone:</strong> <?php echo htmlspecialchars($donation['phone']); ?></div>
                                <div><strong>Donor:</strong> <?php echo htmlspecialchars($donation['donor_name']); ?></div>
                                <div><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($donation['status'])); ?></div>
                            </div>

                            <?php if (!is_logged_in()): ?>
                                <div class="alert alert-info" style="margin-top: 20px;">
                                    <p>You need to log in to request this food donation.</p>
                                    <a href="login.php?redirect=<?php echo urlencode($redirect); ?>" class="btn btn-primary">Log in to Request</a>
                                </div>
                            <?php elseif ($_SESSION['user_id'] === $donation['donor_id']): ?>
                                <div class="alert alert-warning" style="margin-top: 20px;">
                                    <p>This is your own donation listing and cannot be requested by you.</p>
                                    <a href="dashboard.php?page=manage-donation" class="btn btn-outline">Manage My Donations</a>
                                </div>
                            <?php else: ?>
                                <div class="request-form-section" style="margin-top: 20px;">
                                    <h2>Request this donation</h2>
                                    <form action="dashboard.php?page=request-donation" method="POST" novalidate>
                                        <input type="hidden" name="action" value="request_donation">
                                        <input type="hidden" name="donation_id" value="<?php echo $donation['id']; ?>">
                                        <div class="form-group">
                                            <label for="quantity_requested" class="form-label">Quantity needed</label>
                                            <input type="text" id="quantity_requested" name="quantity_requested" class="form-control" placeholder="e.g. 5 kg, 10 packs" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="message" class="form-label">Message to donor</label>
                                            <textarea id="message" name="message" class="form-control" rows="4" placeholder="Optional note for the donor"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Request</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Sayog. Connecting surplus food with communities.</p>
    </footer>
</body>
</html>
