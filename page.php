<?php
require_once 'config.php';

$slug = sanitize($_GET['slug'] ?? '');
$page = null;
if ($slug !== '') {
    $page = get_cms_page_by_slug($pdo, $slug);
}

if (!$page) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page ? htmlspecialchars($page['title']) : 'Page Not Found'; ?> | Sayog</title>
    <?php if ($page && !empty($page['meta_description'])): ?>
        <meta name="description" content="<?php echo htmlspecialchars($page['meta_description']); ?>">
    <?php endif; ?>
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
            <a href="/frontend/donations.php">Food Listings</a>
            <a href="/frontend/about.php">About</a>
            <a href="/frontend/contact.php">Contact</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <main class="site-main">
        <?php if (!$page): ?>
            <section class="section-block">
                <div class="empty-state">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <h3>Page not found</h3>
                    <p>We could not locate the page you requested.</p>
                    <a href="/frontend/index.php" class="btn btn-primary">Back to Home</a>
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
        <p>&copy; <?php echo date('Y'); ?> Sayog. Built for community food sharing.</p>
    </footer>
</body>
</html>
