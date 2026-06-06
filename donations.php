<?php
require_once 'config.php';

$donations = get_available_donations($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Listings | Sayog</title>
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
        <section class="section-block">
            <div class="section-heading">
                <h1>Available Food Listings</h1>
                <p>Browse recent food donations that are ready for pickup or request.</p>
            </div>

            <?php if (empty($donations)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bowl-food"></i>
                    <h3>No active food listings right now.</h3>
                    <p>Donors can create donation entries from their dashboard.</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($donations as $d): ?>
                        <article class="product-card">
                            <div class="product-card-image">
                                <?php if (!empty($d['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($d['image_path']); ?>" alt="<?php echo htmlspecialchars($d['food_item']); ?>">
                                <?php else: ?>
                                    <div class="product-placeholder"><i class="fa-solid fa-bowl-food"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="product-card-body">
                                <h3><?php echo htmlspecialchars($d['food_item']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($d['description'] ?? '', 0, 140)); ?><?php echo strlen($d['description'] ?? '') > 140 ? '...' : ''; ?></p>
                                <div class="product-card-meta">
                                    <span>Quantity: <?php echo htmlspecialchars($d['quantity']); ?></span>
                                    <span> | Expires: <?php echo date('M d, Y H:i', strtotime($d['expiry_time'])); ?></span>
                                </div>
                                <div style="margin-top:8px; font-size:13px; color:#666;">
                                    Donor: <?php echo htmlspecialchars($d['donor_name']); ?> | Pickup: <?php echo htmlspecialchars($d['pickup_address']); ?>
                                </div>
                                <div style="margin-top:12px; display:flex; gap:8px; align-items:center;">
                                    <a href="donation.php?id=<?php echo $d['id']; ?>" class="btn btn-primary">View Details</a>
                                    <a href="login.php?redirect=donation.php?id=<?php echo $d['id']; ?>" class="btn btn-outline">Request Pickup</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Sayog. Connecting surplus food with communities.</p>
    </footer>
</body>
</html>
