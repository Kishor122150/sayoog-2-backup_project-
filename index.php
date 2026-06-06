<?php
require_once 'config.php';

$homePage = get_cms_page_by_slug($pdo, 'home');
$listings = get_available_donations($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sayog | Public Food Donation Portal</title>
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
        <section class="hero-section">
            <div class="hero-copy">
                <h1><?php echo htmlspecialchars($homePage['title'] ?? 'Welcome to Sayog'); ?></h1>
                <div class="hero-text">
                    <?php if (!empty($homePage['content'])) {
                        echo $homePage['content'];
                    } else {
                        echo '<p>Share surplus food, browse donation opportunities, and support local communities.</p>';
                    }
                    ?>
                </div>
                <div class="hero-actions">
                    <a href="donations.php" class="btn btn-primary">Browse Food Listings</a>
                    <a href="login.php" class="btn btn-secondary">Member Login</a>
                </div>
            </div>
            <div class="hero-image"></div>
        </section>

        <section class="product-preview">
            <div class="section-heading">
                <h2>Featured Food Listings</h2>
                <a href="donations.php" class="btn btn-outline">View all listings</a>
            </div>

            <?php if (empty($listings)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bowl-food"></i>
                    <h3>No active food listings yet.</h3>
                    <p>Donors can create donation entries from their dashboard.</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($listings as $product): ?>
                        <article class="product-card">
                            <div class="product-card-image">
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['food_item']); ?>">
                                <?php else: ?>
                                    <div class="product-placeholder"><i class="fa-solid fa-bowl-food"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="product-card-body">
                                <h3><?php echo htmlspecialchars($product['food_item']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 120)); ?><?php echo strlen($product['description'] ?? '') > 120 ? '...' : ''; ?></p>
                                <div class="product-card-meta">
                                    <div style="font-size:13px;color:#666;">Quantity: <?php echo htmlspecialchars($product['quantity']); ?> | Expires: <?php echo date('M d, Y H:i', strtotime($product['expiry_time'])); ?></div>
                                    <a href="login.php?redirect=donation.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">Request Pickup</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Sayog. Built to connect surplus food with communities.</p>
    </footer>
</body>
</html>
