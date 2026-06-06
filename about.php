<?php
require_once 'config.php';

$page = get_cms_page_by_slug($pdo, 'about');
if (!$page) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page ? htmlspecialchars($page['title']) : 'About | Sayog'; ?></title>
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
        <?php if (!$page): ?>
            <section class="section-block">
                <div class="empty-state">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <h3>Page not found</h3>
                    <p>The about page is not available right now.</p>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                </div>
            </section>
        <?php else: ?>
            <section class="section-block page-content">
                <h1><?php echo htmlspecialchars($page['title']); ?></h1>
                <div class="cms-html-content">
                    <?php echo $page['content']; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Sayog. Connecting surplus food with communities.</p>
    </footer>
</body>
</html>
