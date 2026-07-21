<?php
header('Location: /frontend/donations.php');
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="site-branding">
            <a href="/frontend/index.php" class="site-logo"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</a>
        </div>
        <nav class="site-nav">
            <a href="/frontend/index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="/frontend/about.php">About</a>
            <a href="/frontend/contact.php">Contact</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <main class="site-main">
        <section class="section-block">
            <div class="section-heading">
                <h1>Available Products</h1>
                <p>Browse public product listings that can be requested via the Sayog network.</p>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No products are currently active.</h3>
                    <p>Admin can add products to populate the public storefront.</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="product-card">
                            <div class="product-card-image">
                                <?php if (!empty($product['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                                <?php else: ?>
                                    <div class="product-placeholder"><i class="fa-solid fa-box-open"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="product-card-body">
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-card-meta">
                                    <span class="price">Rs <?php echo number_format($product['price'], 2); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Sayog. Connecting donors with people in need.</p>
    </footer>
</body>
</html>
