<?php
require_once 'config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

// Load dynamic homepage CMS content
$homeStmt = $pdo->query("SELECT * FROM cms_homepage WHERE id = 1");
$cms = $homeStmt->fetch(PDO::FETCH_ASSOC);
if (!$cms) {
    // Fallback defaults if no CMS row exists
    $cms = [
        'hero_heading' => 'Welcome to Sayog',
        'hero_subheading' => 'Share surplus food, browse donation opportunities, and support local communities.',
        'hero_button1_text' => 'Browse Food Listings',
        'hero_button1_link' => 'donations.php',
        'hero_button2_text' => 'Member Login',
        'hero_button2_link' => 'login.php',
        'works_title' => 'How Sayog Works',
        'works_description' => 'Sayog connects people with surplus food to those who need it through a simple, secure, and transparent donation process.',
        'work1_icon' => 'fas fa-user-plus',
        'work1_heading' => 'Create Account',
        'work1_description' => 'Register for free and become a member of the Sayog community.',
        'work2_icon' => 'fas fa-hand-holding-heart',
        'work2_heading' => 'Share Food',
        'work2_description' => 'Post available food donations with quantity, pickup location, and expiry time.',
        'work3_icon' => 'fas fa-box-open',
        'work3_heading' => 'Request Donation',
        'work3_description' => 'Browse available donations and request the items you need.',
        'work4_icon' => 'fas fa-check-circle',
        'work4_heading' => 'Complete Pickup',
        'work4_description' => 'Donor approves the request and the receiver collects the donation.',
        'quick_title' => 'Quick Actions',
        'quick_description' => 'Start helping your community today.',
        'quick1_icon' => 'fas fa-user-plus',
        'quick1_title' => 'Join Sayog',
        'quick1_description' => 'Create your free account.',
        'quick1_button' => 'Get Started',
        'quick1_link' => 'register.php',
        'quick2_icon' => 'fas fa-right-to-bracket',
        'quick2_title' => 'Login',
        'quick2_description' => 'Access your dashboard.',
        'quick2_button' => 'Login',
        'quick2_link' => 'login.php',
        'quick3_icon' => 'fas fa-bowl-food',
        'quick3_title' => 'Browse Donations',
        'quick3_description' => 'Find available food near you.',
        'quick3_button' => 'View Listings',
        'quick3_link' => 'donations.php',
        'quick4_icon' => 'fas fa-envelope',
        'quick4_title' => 'Contact Us',
        'quick4_description' => 'Need help? Reach out anytime.',
        'quick4_button' => 'Contact',
        'quick4_link' => 'contact.php',
        'footer_description' => 'Built to connect surplus food with communities.',
        'facebook' => '#',
        'instagram' => '#',
        'whatsapp' => '#',
        'linkedin' => '#',
        'copyright' => date('Y') . ' Sayog. Built to connect surplus food with communities.',
    ];
}

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
    <script src="js/app.js"></script>
</head>
<body>
    <header class="site-header">
        <div class="site-branding">
            <a href="index.php" class="site-logo"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</a>
        </div>
        <nav class="site-nav">
            <a href="index.php" class="active" style="color: #059669;" data-i18n="nav.home">Home</a>
            <a href="donations.php" data-i18n="nav.food_listings">Food Listings</a>
            <a href="about.php" data-i18n="nav.about">About</a>
            <a href="contact.php" data-i18n="nav.contact">Contact</a>
            <a href="login.php" data-i18n="nav.login">Login</a>
            <a href="register.php" style="background: #059669; color:#fff" data-i18n="nav.get_started">Get Started</a>
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" style="margin-left:4px;">
                <i class="fa-solid fa-moon"></i>
            </button>
            <button class="lang-toggle" onclick="toggleLanguage()" style="background:rgba(59,130,246,0.1);">
                <span>नेपाली</span>
            </button>
        </nav>
    </header>

    <main class="site-main">
   <!-- Premium Centered Hero Banner Section with Fluid Animations -->
        <section class="hero-section" style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 100px 24px; max-width: 1000px; margin: 40px auto; min-height: 75vh; box-sizing: border-box; position: relative; background: radial-gradient(circle at 50% 50%, rgba(79, 70, 229, 0.04) 0%, rgba(255, 255, 255, 0) 70%); border-radius: 24px; overflow: hidden;">
            
            <!-- Injected Keyframe Animations (Safe for mid-page placement) -->
            <style>
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @keyframes pulseBadge {
                    0% { transform: scale(1); opacity: 0.9; }
                    50% { transform: scale(1.03); opacity: 1; }
                    100% { transform: scale(1); opacity: 0.9; }
                }
                .animate-fade-in {
                    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                }
                .hero-actions .btn {
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                }
                .hero-actions .btn:hover {
                    transform: translateY(-3px) scale(1.02);
                }
            </style>

            <div class="hero-copy" style="width: 100%; display: flex; flex-direction: column; align-items: center; z-index: 2;">
                
                <!-- Decorative Modern Tagline Badge -->
                <div class="animate-fade-in" style="animation-delay: 0.1s; background: rgba(79, 70, 229, 0.08); color: var(--primary, #4f46e5); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; padding: 6px 16px; border-radius: 50px; margin-bottom: 24px; display: inline-flex; align-items: center; gap: 6px; animation: pulseBadge 3s infinite ease-in-out;">
                    <i class="fa-solid fa-heart-pulse"></i> <span data-i18n="hero.tagline">Empowering Communities</span>
                </div>

                <!-- Main Title Heading -->
                <h1 class="animate-fade-in" style="animation-delay: 0.2s; font-size: 3.5rem; font-weight: 800; line-height: 1.15; color: #111827; margin: 0 0 24px 0; letter-spacing: -0.03em; max-width: 850px; background: linear-gradient(135deg, #111827 0%, #374151 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    <?php echo htmlspecialchars($cms['hero_heading'] ?? 'Welcome to Sayog'); ?>
                </h1>
                
                <!-- Description Body Text -->
                <div class="animate-fade-in" style="animation-delay: 0.4s; font-size: 1.25rem; color: var(--text-muted, #4b5563); margin-bottom: 48px; line-height: 1.7; max-width: 700px; text-align: center;">
                    <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($cms['hero_subheading'] ?? '')); ?></p>
                </div>
                
                <!-- Interactive Action Button Group -->
                <div class="hero-actions animate-fade-in" style="animation-delay: 0.6s; display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; width: 100%;">
                    <a href="<?php echo htmlspecialchars($cms['hero_button1_link'] ?? 'donations.php'); ?>" class="btn btn-primary" style="padding: 16px 32px; font-size: 1rem; min-width: 220px; border-radius: 10px; background: var(--primary, #4f46e5); color: #fff; box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.35); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fa-solid fa-magnifying-glass"></i> <span data-i18n="hero.browse_listings"><?php echo htmlspecialchars($cms['hero_button1_text'] ?? 'Browse Food Listings'); ?></span>
                    </a>
                    <a href="<?php echo htmlspecialchars($cms['hero_button2_link'] ?? 'login.php'); ?>" class="btn btn-secondary" style="padding: 16px 32px; font-size: 1rem; min-width: 220px; border-radius: 10px; background: #ffffff; color: #111827; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 10px;">
                        <i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="hero.member_login"><?php echo htmlspecialchars($cms['hero_button2_text'] ?? 'Member Login'); ?></span>
                    </a>
                </div>

            </div>

            <!-- Subtle Decorative Geometric Background Blur Nodes -->
            <div style="position: absolute; width: 300px; height: 300px; background: rgba(79, 70, 229, 0.03); filter: blur(60px); border-radius: 50%; top: -50px; left: -50px; z-index: 1;"></div>
            <div style="position: absolute; width: 250px; height: 250px; background: rgba(16, 185, 129, 0.02); filter: blur(50px); border-radius: 50%; bottom: -30px; right: -30px; z-index: 1;"></div>
        </section>
        </section>
        
        <!-- ================= PLATFORM STATISTICS ================= -->
        <?php
// Fetch platform-wide statistics
$stats_available = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE verification_status = 'approved' AND status IN ('available', 'requested')")->fetchColumn();
$stats_completed = (int)$pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'completed'")->fetchColumn();
$stats_users = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
?>
        <style>
            /* --- Stats Section Keyframes & Animations --- */
            @keyframes statsFadeSlideUp {
                0%   { opacity: 0; transform: translateY(30px) scale(0.96); }
                100% { opacity: 1; transform: translateY(0) scale(1); }
            }
            @keyframes statsPulseGlow {
                0%, 100% { box-shadow: 0 0 20px rgba(5, 150, 105, 0.12); }
                50%      { box-shadow: 0 0 40px rgba(5, 150, 105, 0.25); }
            }
            @keyframes statsBgDrift {
                0%   { background-position: 0% 50%; }
                50%  { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            @keyframes statsFloat {
                0%, 100% { transform: translateY(0px); }
                50%      { transform: translateY(-12px); }
            }
            @keyframes statsShimmer {
                0%   { transform: translateX(-100%) skewX(-15deg); }
                100% { transform: translateX(200%) skewX(-15deg); }
            }
            @keyframes statsIconPulse {
                0%, 100% { transform: scale(1); opacity: 0.3; }
                50%      { transform: scale(1.15); opacity: 0.5; }
            }
            .stats-section {
                padding: 80px 24px;
                position: relative;
                overflow: hidden;
                background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 30%, #f4fbf7 70%, #f0fdf4 100%);
                background-size: 300% 300%;
                animation: statsBgDrift 12s ease-in-out infinite;
                isolation: isolate;
            }
            /* Dark mode overrides */
            [data-theme="dark"] .stats-section {
                background: linear-gradient(135deg, #0a1f1a 0%, #0d2b22 30%, #0f1f1a 70%, #0a1f1a 100%);
                background-size: 300% 300%;
            }
            /* Decorative floating blobs */
            .stats-blob {
                position: absolute;
                border-radius: 50%;
                filter: blur(70px);
                z-index: 0;
                pointer-events: none;
                opacity: 0.4;
            }
            .stats-blob--1 {
                width: 300px; height: 300px;
                background: rgba(5, 150, 105, 0.08);
                top: -80px; left: -60px;
                animation: statsFloat 8s ease-in-out infinite;
            }
            .stats-blob--2 {
                width: 250px; height: 250px;
                background: rgba(16, 185, 129, 0.06);
                bottom: -60px; right: -40px;
                animation: statsFloat 10s ease-in-out infinite reverse;
            }
            .stats-blob--3 {
                width: 180px; height: 180px;
                background: rgba(5, 150, 105, 0.04);
                top: 50%; left: 60%;
                animation: statsFloat 7s ease-in-out infinite 2s;
            }
            .stats-inner {
                max-width: 1100px;
                margin: 0 auto;
                position: relative;
                z-index: 1;
            }
            .stats-header {
                text-align: center;
                margin-bottom: 48px;
                animation: statsFadeSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .stats-header h2 {
                font-size: 2rem;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 12px 0;
                letter-spacing: -0.02em;
                display: inline-flex;
                align-items: center;
                gap: 12px;
            }
            [data-theme="dark"] .stats-header h2 { color: #f1f5f9; }
            .stats-header p {
                font-size: 1.05rem;
                color: #4b5563;
                margin: 0;
                line-height: 1.6;
                max-width: 580px;
                margin-left: auto;
                margin-right: auto;
            }
            [data-theme="dark"] .stats-header p { color: #94a3b8; }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 28px;
                max-width: 960px;
                margin: 0 auto;
            }
            @media (max-width: 768px) {
                .stats-grid { grid-template-columns: 1fr; max-width: 400px; }
                .stats-section { padding: 48px 20px; }
                .stats-header h2 { font-size: 1.6rem; }
            }
            @media (max-width: 480px) {
                .stats-grid { max-width: 100%; }
            }
            .stat-card-enhanced {
                position: relative;
                text-align: center;
                padding: 36px 24px;
                background: rgba(255, 255, 255, 0.75);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(5, 150, 105, 0.12);
                border-radius: 24px;
                box-shadow:
                    0 4px 12px rgba(0, 0, 0, 0.03),
                    0 12px 40px rgba(5, 150, 105, 0.04);
                transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                cursor: default;
                overflow: hidden;
                animation: statsFadeSlideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                opacity: 0;
            }
            [data-theme="dark"] .stat-card-enhanced {
                background: rgba(15, 23, 42, 0.6);
                backdrop-filter: blur(16px);
                border-color: rgba(5, 150, 105, 0.08);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }
            .stat-card-enhanced:nth-child(1) { animation-delay: 0.1s; }
            .stat-card-enhanced:nth-child(2) { animation-delay: 0.25s; }
            .stat-card-enhanced:nth-child(3) { animation-delay: 0.4s; }

            .stat-card-enhanced:hover {
                transform: translateY(-8px) scale(1.02);
                border-color: rgba(5, 150, 105, 0.3);
                box-shadow:
                    0 12px 28px rgba(5, 150, 105, 0.1),
                    0 24px 48px rgba(5, 150, 105, 0.06);
                animation: statsPulseGlow 2s ease-in-out infinite;
            }
            [data-theme="dark"] .stat-card-enhanced:hover {
                box-shadow:
                    0 12px 28px rgba(5, 150, 105, 0.06),
                    0 24px 48px rgba(0, 0, 0, 0.3);
                border-color: rgba(5, 150, 105, 0.2);
            }
            /* Shimmer overlay on hover */
            .stat-card-enhanced::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
                transform: translateX(-100%) skewX(-15deg);
                pointer-events: none;
            }
            .stat-card-enhanced:hover::after {
                animation: statsShimmer 1s ease-in-out;
            }
            .stat-icon-wrapper {
                width: 64px;
                height: 64px;
                border-radius: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 26px;
                position: relative;
                transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .stat-card-enhanced:hover .stat-icon-wrapper {
                transform: scale(1.1) rotate(-4deg);
                border-radius: 14px;
            }
            /* Icon glow ring */
            .stat-icon-wrapper::before {
                content: '';
                position: absolute;
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(5, 150, 105, 0.12) 0%, transparent 70%);
                animation: statsIconPulse 3s ease-in-out infinite;
                pointer-events: none;
            }
            .stat-icon--available { color: #059669; background: rgba(5, 150, 105, 0.1); }
            .stat-icon--completed { color: #10b981; background: rgba(16, 185, 129, 0.1); }
            .stat-icon--users { color: #047857; background: rgba(4, 120, 87, 0.1); }
            [data-theme="dark"] .stat-icon--available { background: rgba(5, 150, 105, 0.15); }
            [data-theme="dark"] .stat-icon--completed { background: rgba(16, 185, 129, 0.15); }
            [data-theme="dark"] .stat-icon--users { background: rgba(4, 120, 87, 0.15); }

            .stat-count {
                font-size: 2.6rem;
                font-weight: 900;
                line-height: 1.1;
                letter-spacing: -0.03em;
                transition: color 0.3s;
            }
            .stat-count--available { color: #059669; }
            .stat-count--completed { color: #10b981; }
            .stat-count--users { color: #047857; }

            .stat-label {
                font-size: 0.85rem;
                color: #4b5563;
                margin-top: 8px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                transition: color 0.3s;
            }
            [data-theme="dark"] .stat-label { color: #94a3b8; }
            .stat-card-enhanced:hover .stat-label { color: #111827; }
            [data-theme="dark"] .stat-card-enhanced:hover .stat-label { color: #e2e8f0; }
            /* Subtle decorative line under count */
            .stat-divider {
                width: 40px;
                height: 3px;
                border-radius: 2px;
                margin: 12px auto 0;
                background: linear-gradient(90deg, #059669, #10b981);
                transition: width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .stat-card-enhanced:hover .stat-divider { width: 56px; }
        </style>
        <section class="stats-section">
            <!-- Decorative floating background blobs -->
            <div class="stats-blob stats-blob--1"></div>
            <div class="stats-blob stats-blob--2"></div>
            <div class="stats-blob stats-blob--3"></div>

            <div class="stats-inner">
                <div class="stats-header">
                    <h2>
                        <i class="fa-solid fa-chart-simple" style="font-size: 1.6rem; background: linear-gradient(135deg, #059669, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                        <span data-i18n="section.stats_title">Our Impact</span>
                    </h2>
                    <p data-i18n="section.stats_desc">Real-time platform statistics showing our community's collective effort to reduce food waste.</p>
                </div>

                <div class="stats-grid">
                    <!-- Card 1: Donations Available -->
                    <div class="stat-card-enhanced">
                        <div class="stat-icon-wrapper stat-icon--available">
                            <i class="fa-solid fa-bowl-food"></i>
                        </div>
                        <div class="stat-count stat-count--available" ><?php echo $stats_available; ?></div>
                        <div class="stat-label" data-i18n="stats.available_donations">Donations Available</div>
                        <div class="stat-divider"></div>
                    </div>

                    <!-- Card 2: Successful Donations -->
                    <div class="stat-card-enhanced">
                        <div class="stat-icon-wrapper stat-icon--completed">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="stat-count stat-count--completed" ><?php echo $stats_completed; ?></div>
                        <div class="stat-label" data-i18n="stats.completed_donations">Successful Donations</div>
                        <div class="stat-divider"></div>
                    </div>

                    <!-- Card 3: Registered Users -->
                    <div class="stat-card-enhanced">
                        <div class="stat-icon-wrapper stat-icon--users">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="stat-count stat-count--users" ><?php echo $stats_users; ?></div>
                        <div class="stat-label" data-i18n="stats.total_users">Registered Users</div>
                        <div class="stat-divider"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="product-preview">
            <div class="section-heading">
                <h2 data-i18n="section.featured_title">Featured Food Listings</h2>
                <a href="donations.php" class="btn btn-outline" data-i18n="btn.view_all_listings">View all listings</a>
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
                                    <div style="font-size:13px;color:#666;">Quantity: <?php echo htmlspecialchars($product['quantity']); ?> | <span class="countdown-badge" data-expiry="<?php echo $product['expiry_time']; ?>">⏳ Loading...</span></div>
                                    <a href="login.php?redirect=donation.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">Request Pickup</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        
        <!-- ================= HOW SAYOG WORKS ================= -->
        <style>
            @keyframes stepsFadeUp {
                0%   { opacity: 0; transform: translateY(24px) scale(0.97); }
                100% { opacity: 1; transform: translateY(0) scale(1); }
            }
            @keyframes stepsBgDrift {
                0%   { background-position: 0% 50%; }
                50%  { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            @keyframes stepsFloat {
                0%, 100% { transform: translateY(0px); }
                50%      { transform: translateY(-10px); }
            }
            @keyframes stepsIconPulse {
                0%, 100% { transform: scale(1); opacity: 0.35; }
                50%      { transform: scale(1.2); opacity: 0.55; }
            }
            @keyframes stepsConnectorPulse {
                0%, 100% { opacity: 0.3; }
                50%      { opacity: 0.8; }
            }
            @keyframes stepsNumberPop {
                0%   { transform: scale(0); opacity: 0; }
                60%  { transform: scale(1.15); opacity: 1; }
                100% { transform: scale(1); opacity: 1; }
            }
            @keyframes stepsShimmer {
                0%   { transform: translateX(-100%) skewX(-15deg); }
                100% { transform: translateX(200%) skewX(-15deg); }
            }
            .steps-enhanced-section {
                padding: 80px 24px;
                position: relative;
                overflow: hidden;
                background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 30%, #f4fbf7 70%, #f0fdf4 100%);
                background-size: 300% 300%;
                animation: stepsBgDrift 14s ease-in-out infinite;
                isolation: isolate;
            }
            [data-theme="dark"] .steps-enhanced-section {
                background: linear-gradient(135deg, #0a1f1a 0%, #0d2b22 30%, #0f1f1a 70%, #0a1f1a 100%);
                background-size: 300% 300%;
            }
            .steps-blob {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                z-index: 0;
                pointer-events: none;
                opacity: 0.35;
            }
            .steps-blob--1 {
                width: 280px; height: 280px;
                background: rgba(5, 150, 105, 0.07);
                top: -60px; left: -40px;
                animation: stepsFloat 9s ease-in-out infinite;
            }
            .steps-blob--2 {
                width: 220px; height: 220px;
                background: rgba(16, 185, 129, 0.05);
                bottom: -50px; right: -30px;
                animation: stepsFloat 11s ease-in-out infinite reverse;
            }
            .steps-blob--3 {
                width: 160px; height: 160px;
                background: rgba(5, 150, 105, 0.04);
                top: 40%; left: 55%;
                animation: stepsFloat 7s ease-in-out infinite 3s;
            }
            .steps-enhanced-inner {
                max-width: 1100px;
                margin: 0 auto;
                position: relative;
                z-index: 1;
            }
            .steps-enhanced-header {
                text-align: center;
                margin-bottom: 56px;
                animation: stepsFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .steps-enhanced-header h2 {
                font-size: 2rem;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 12px 0;
                letter-spacing: -0.02em;
            }
            [data-theme="dark"] .steps-enhanced-header h2 { color: #f1f5f9; }
            .steps-enhanced-header p {
                font-size: 1.05rem;
                color: #4b5563;
                margin: 0;
                line-height: 1.6;
                max-width: 580px;
                margin-left: auto;
                margin-right: auto;
            }
            [data-theme="dark"] .steps-enhanced-header p { color: #94a3b8; }

            .steps-enhanced-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 24px;
                position: relative;
            }
            @media (max-width: 991px) {
                .steps-enhanced-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 540px) {
                .steps-enhanced-grid { grid-template-columns: 1fr; max-width: 360px; margin: 0 auto; }
                .steps-enhanced-section { padding: 48px 20px; }
                .steps-enhanced-header h2 { font-size: 1.6rem; }
            }

            /* Connector line between cards (visible on desktop 4-col) */
            .steps-connector {
                display: none;
            }
            @media (min-width: 992px) {
                .steps-connector {
                    display: block;
                    position: absolute;
                    top: 56px;
                    left: calc(12.5% + 40px);
                    width: calc(75% - 80px);
                    height: 2px;
                    background: linear-gradient(90deg, #059669 0%, #10b981 50%, #34d399 100%);
                    opacity: 0.25;
                    z-index: 0;
                    animation: stepsConnectorPulse 3s ease-in-out infinite;
                }
            }
            .step-card-enhanced {
                position: relative;
                text-align: center;
                padding: 40px 20px 32px;
                background: rgba(255, 255, 255, 0.72);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                border: 1px solid rgba(5, 150, 105, 0.08);
                border-radius: 24px;
                box-shadow:
                    0 4px 12px rgba(0, 0, 0, 0.02),
                    0 12px 40px rgba(5, 150, 105, 0.03);
                transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                cursor: default;
                overflow: hidden;
                animation: stepsFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                opacity: 0;
                z-index: 1;
            }
            [data-theme="dark"] .step-card-enhanced {
                background: rgba(15, 23, 42, 0.55);
                backdrop-filter: blur(16px);
                border-color: rgba(5, 150, 105, 0.06);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }
            .step-card-enhanced:nth-child(1) { animation-delay: 0.1s; }
            .step-card-enhanced:nth-child(2) { animation-delay: 0.25s; }
            .step-card-enhanced:nth-child(3) { animation-delay: 0.4s; }
            .step-card-enhanced:nth-child(4) { animation-delay: 0.55s; }

            .step-card-enhanced:hover {
                transform: translateY(-8px) scale(1.02);
                border-color: rgba(5, 150, 105, 0.2);
                box-shadow:
                    0 12px 28px rgba(5, 150, 105, 0.08),
                    0 24px 48px rgba(5, 150, 105, 0.05);
            }
            [data-theme="dark"] .step-card-enhanced:hover {
                box-shadow:
                    0 12px 28px rgba(5, 150, 105, 0.04),
                    0 24px 48px rgba(0, 0, 0, 0.3);
                border-color: rgba(5, 150, 105, 0.15);
            }
            .step-card-enhanced::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
                transform: translateX(-100%) skewX(-15deg);
                pointer-events: none;
            }
            .step-card-enhanced:hover::after {
                animation: stepsShimmer 1s ease-in-out;
            }

            /* Step number badge */
            .step-number {
                position: absolute;
                top: 12px;
                right: 16px;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background: linear-gradient(135deg, #059669, #10b981);
                color: #fff;
                font-size: 0.75rem;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: stepsNumberPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
                opacity: 0;
                box-shadow: 0 4px 8px rgba(5, 150, 105, 0.25);
            }
            .step-card-enhanced:nth-child(1) .step-number { animation-delay: 0.3s; }
            .step-card-enhanced:nth-child(2) .step-number { animation-delay: 0.45s; }
            .step-card-enhanced:nth-child(3) .step-number { animation-delay: 0.6s; }
            .step-card-enhanced:nth-child(4) .step-number { animation-delay: 0.75s; }

            .step-icon-enhanced {
                width: 64px;
                height: 64px;
                border-radius: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 24px;
                position: relative;
                transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .step-card-enhanced:hover .step-icon-enhanced {
                transform: scale(1.1) rotate(-4deg);
                border-radius: 14px;
            }
            .step-icon-enhanced::before {
                content: '';
                position: absolute;
                width: 76px;
                height: 76px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(5, 150, 105, 0.12) 0%, transparent 70%);
                animation: stepsIconPulse 3s ease-in-out infinite;
                pointer-events: none;
            }
            .step-icon--1 { color: #059669; background: rgba(5, 150, 105, 0.1); }
            .step-icon--2 { color: #10b981; background: rgba(16, 185, 129, 0.1); }
            .step-icon--3 { color: #047857; background: rgba(4, 120, 87, 0.1); }
            .step-icon--4 { color: #34d399; background: rgba(34, 211, 153, 0.1); }
            [data-theme="dark"] .step-icon--1 { background: rgba(5, 150, 105, 0.15); }
            [data-theme="dark"] .step-icon--2 { background: rgba(16, 185, 129, 0.15); }
            [data-theme="dark"] .step-icon--3 { background: rgba(4, 120, 87, 0.15); }
            [data-theme="dark"] .step-icon--4 { background: rgba(34, 211, 153, 0.15); }

            .step-card-enhanced h3 {
                font-size: 1.1rem;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 10px 0;
                transition: color 0.3s;
            }
            [data-theme="dark"] .step-card-enhanced h3 { color: #e2e8f0; }
            .step-card-enhanced:hover h3 { color: #059669; }
            [data-theme="dark"] .step-card-enhanced:hover h3 { color: #34d399; }

            .step-card-enhanced p {
                font-size: 0.92rem;
                color: #4b5563;
                line-height: 1.55;
                margin: 0;
            }
            [data-theme="dark"] .step-card-enhanced p { color: #94a3b8; }
        </style>
        <section class="steps-enhanced-section">
            <div class="steps-blob steps-blob--1"></div>
            <div class="steps-blob steps-blob--2"></div>
            <div class="steps-blob steps-blob--3"></div>

            <div class="steps-enhanced-inner">
                <div class="steps-enhanced-header">
                    <h2>
                        <i class="fa-solid fa-arrow-trend-up" style="font-size: 1.5rem; background: linear-gradient(135deg, #059669, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                        <span data-i18n="section.how_it_works"><?php echo htmlspecialchars($cms['works_title'] ?? 'How Sayog Works'); ?></span>
                    </h2>
                    <p><?php echo htmlspecialchars($cms['works_description'] ?? ''); ?></p>
                </div>

                <div class="steps-enhanced-grid">
                    <!-- Connector line between steps -->
                    <div class="steps-connector"></div>

                    <div class="step-card-enhanced">
                        <div class="step-number">1</div>
                        <div class="step-icon-enhanced step-icon--1">
                            <i class="<?php echo htmlspecialchars($cms['work1_icon'] ?? 'fas fa-user-plus'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['work1_heading'] ?? 'Create Account'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['work1_description'] ?? ''); ?></p>
                    </div>

                    <div class="step-card-enhanced">
                        <div class="step-number">2</div>
                        <div class="step-icon-enhanced step-icon--2">
                            <i class="<?php echo htmlspecialchars($cms['work2_icon'] ?? 'fas fa-hand-holding-heart'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['work2_heading'] ?? 'Share Food'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['work2_description'] ?? ''); ?></p>
                    </div>

                    <div class="step-card-enhanced">
                        <div class="step-number">3</div>
                        <div class="step-icon-enhanced step-icon--3">
                            <i class="<?php echo htmlspecialchars($cms['work3_icon'] ?? 'fas fa-box-open'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['work3_heading'] ?? 'Request Donation'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['work3_description'] ?? ''); ?></p>
                    </div>

                    <div class="step-card-enhanced">
                        <div class="step-number">4</div>
                        <div class="step-icon-enhanced step-icon--4">
                            <i class="<?php echo htmlspecialchars($cms['work4_icon'] ?? 'fas fa-check-circle'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['work4_heading'] ?? 'Complete Pickup'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['work4_description'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= QUICK ACTIONS ================= -->
        <style>
            @keyframes quickFadeUp {
                0%   { opacity: 0; transform: translateY(30px) scale(0.96); }
                100% { opacity: 1; transform: translateY(0) scale(1); }
            }
            @keyframes quickBgDrift {
                0%   { background-position: 0% 50%; }
                50%  { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            @keyframes quickFloat {
                0%, 100% { transform: translateY(0px); }
                50%      { transform: translateY(-12px); }
            }
            @keyframes quickIconRing {
                0%, 100% { transform: scale(1); opacity: 0.25; }
                50%      { transform: scale(1.25); opacity: 0.5; }
            }
            @keyframes quickShimmer {
                0%   { transform: translateX(-100%) skewX(-15deg); }
                100% { transform: translateX(200%) skewX(-15deg); }
            }
            .quick-enhanced-section {
                padding: 80px 24px 90px;
                position: relative;
                overflow: hidden;
                background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 30%, #f4fbf7 70%, #f0fdf4 100%);
                background-size: 300% 300%;
                animation: quickBgDrift 13s ease-in-out infinite;
                isolation: isolate;
            }
            [data-theme="dark"] .quick-enhanced-section {
                background: linear-gradient(135deg, #0a1f1a 0%, #0d2b22 30%, #0f1f1a 70%, #0a1f1a 100%);
                background-size: 300% 300%;
            }
            .quick-blob {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                z-index: 0;
                pointer-events: none;
                opacity: 0.35;
            }
            .quick-blob--1 {
                width: 300px; height: 300px;
                background: rgba(5, 150, 105, 0.06);
                top: -70px; right: -50px;
                animation: quickFloat 9s ease-in-out infinite;
            }
            .quick-blob--2 {
                width: 240px; height: 240px;
                background: rgba(16, 185, 129, 0.04);
                bottom: -60px; left: -40px;
                animation: quickFloat 11s ease-in-out infinite reverse;
            }
            .quick-blob--3 {
                width: 180px; height: 180px;
                background: rgba(4, 120, 87, 0.03);
                top: 30%; left: 50%;
                animation: quickFloat 8s ease-in-out infinite 2s;
            }
            .quick-enhanced-inner {
                max-width: 1100px;
                margin: 0 auto;
                position: relative;
                z-index: 1;
            }
            .quick-enhanced-header {
                text-align: center;
                margin-bottom: 48px;
                animation: quickFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            .quick-enhanced-header h2 {
                font-size: 2rem;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 12px 0;
                letter-spacing: -0.02em;
            }
            [data-theme="dark"] .quick-enhanced-header h2 { color: #f1f5f9; }
            .quick-enhanced-header p {
                font-size: 1.05rem;
                color: #4b5563;
                margin: 0;
                line-height: 1.6;
                max-width: 580px;
                margin-left: auto;
                margin-right: auto;
            }
            [data-theme="dark"] .quick-enhanced-header p { color: #94a3b8; }

            .quick-enhanced-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 24px;
            }
            @media (max-width: 991px) {
                .quick-enhanced-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 540px) {
                .quick-enhanced-grid { grid-template-columns: 1fr; max-width: 360px; margin: 0 auto; }
                .quick-enhanced-section { padding: 48px 20px 56px; }
                .quick-enhanced-header h2 { font-size: 1.6rem; }
            }

            .action-card-enhanced {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 36px 20px 28px;
                background: rgba(255, 255, 255, 0.75);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(5, 150, 105, 0.08);
                border-radius: 24px;
                text-decoration: none;
                transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
                animation: quickFadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                opacity: 0;
                overflow: hidden;
                position: relative;
                box-shadow:
                    0 4px 12px rgba(0, 0, 0, 0.02),
                    0 12px 40px rgba(5, 150, 105, 0.02);
            }
            [data-theme="dark"] .action-card-enhanced {
                background: rgba(15, 23, 42, 0.55);
                backdrop-filter: blur(16px);
                border-color: rgba(5, 150, 105, 0.05);
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            }
            .action-card-enhanced:nth-child(1) { animation-delay: 0.1s; }
            .action-card-enhanced:nth-child(2) { animation-delay: 0.2s; }
            .action-card-enhanced:nth-child(3) { animation-delay: 0.35s; }
            .action-card-enhanced:nth-child(4) { animation-delay: 0.5s; }

            .action-card-enhanced:hover {
                transform: translateY(-8px) scale(1.02);
                border-color: rgba(5, 150, 105, 0.25);
                box-shadow:
                    0 12px 28px rgba(5, 150, 105, 0.07),
                    0 24px 48px rgba(5, 150, 105, 0.04);
            }
            [data-theme="dark"] .action-card-enhanced:hover {
                box-shadow:
                    0 12px 28px rgba(5, 150, 105, 0.03),
                    0 24px 48px rgba(0, 0, 0, 0.3);
                border-color: rgba(5, 150, 105, 0.15);
            }
            .action-card-enhanced::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
                transform: translateX(-100%) skewX(-15deg);
                pointer-events: none;
            }
            .action-card-enhanced:hover::after {
                animation: quickShimmer 1s ease-in-out;
            }
            .action-card-enhanced:active {
                transform: translateY(-2px) scale(0.98);
            }

            .action-icon-enhanced {
                width: 60px;
                height: 60px;
                border-radius: 18px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 18px;
                font-size: 22px;
                position: relative;
                transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .action-card-enhanced:hover .action-icon-enhanced {
                transform: scale(1.1) rotate(-4deg);
                border-radius: 14px;
            }
            .action-icon-enhanced::before {
                content: '';
                position: absolute;
                width: 72px;
                height: 72px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(5, 150, 105, 0.1) 0%, transparent 70%);
                animation: quickIconRing 3s ease-in-out infinite;
                pointer-events: none;
            }
            .action-icon--1 { color: #059669; background: rgba(4, 120, 87, 0.08); }
            .action-icon--2 { color: #10b981; background: rgba(16, 185, 129, 0.08); }
            .action-icon--3 { color: #047857; background: rgba(16, 185, 129, 0.08); }
            .action-icon--4 { color: #34d399; background: rgba(5, 150, 105, 0.08); }
            [data-theme="dark"] .action-icon--1 { background: rgba(4, 120, 87, 0.15); }
            [data-theme="dark"] .action-icon--2 { background: rgba(16, 185, 129, 0.15); }
            [data-theme="dark"] .action-icon--3 { background: rgba(16, 185, 129, 0.15); }
            [data-theme="dark"] .action-icon--4 { background: rgba(5, 150, 105, 0.15); }

            .action-card-enhanced h3 {
                font-size: 1.1rem;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 8px 0;
                transition: color 0.3s;
            }
            [data-theme="dark"] .action-card-enhanced h3 { color: #e2e8f0; }
            .action-card-enhanced:hover h3 { color: #059669; }
            [data-theme="dark"] .action-card-enhanced:hover h3 { color: #34d399; }

            .action-card-enhanced p {
                font-size: 0.88rem;
                color: #4b5563;
                line-height: 1.5;
                margin: 0;
            }
            [data-theme="dark"] .action-card-enhanced p { color: #94a3b8; }

            /* CTA arrow on hover */
            .action-card-enhanced .action-arrow {
                margin-top: 14px;
                font-size: 0.8rem;
                color: #059669;
                opacity: 0;
                transform: translateX(-8px);
                transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            }
            .action-card-enhanced:hover .action-arrow {
                opacity: 1;
                transform: translateX(0);
            }
        </style>
        <section class="quick-enhanced-section">
            <div class="quick-blob quick-blob--1"></div>
            <div class="quick-blob quick-blob--2"></div>
            <div class="quick-blob quick-blob--3"></div>

            <div class="quick-enhanced-inner">
                <div class="quick-enhanced-header">
                    <h2>
                        <i class="fa-solid fa-bolt" style="font-size: 1.4rem; background: linear-gradient(135deg, #059669, #047857); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                        <span data-i18n="section.quick_actions"><?php echo htmlspecialchars($cms['quick_title'] ?? 'Quick Actions'); ?></span>
                    </h2>
                    <p><?php echo htmlspecialchars($cms['quick_description'] ?? 'Start helping your community today.'); ?></p>
                </div>

                <div class="quick-enhanced-grid">
                    <a href="<?php echo htmlspecialchars($cms['quick1_link'] ?? 'register.php'); ?>" class="action-card-enhanced">
                        <div class="action-icon-enhanced action-icon--1">
                            <i class="<?php echo htmlspecialchars($cms['quick1_icon'] ?? 'fas fa-user-plus'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['quick1_title'] ?? 'Join Sayog'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['quick1_description'] ?? 'Create your free account.'); ?></p>
                        <span class="action-arrow"><i class="fa-solid fa-arrow-right"></i> Get Started</span>
                    </a>

                    <a href="<?php echo htmlspecialchars($cms['quick2_link'] ?? 'login.php'); ?>" class="action-card-enhanced">
                        <div class="action-icon-enhanced action-icon--2">
                            <i class="<?php echo htmlspecialchars($cms['quick2_icon'] ?? 'fas fa-right-to-bracket'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['quick2_title'] ?? 'Login'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['quick2_description'] ?? 'Access your dashboard.'); ?></p>
                        <span class="action-arrow"><i class="fa-solid fa-arrow-right"></i> Login</span>
                    </a>

                    <a href="<?php echo htmlspecialchars($cms['quick3_link'] ?? 'donations.php'); ?>" class="action-card-enhanced">
                        <div class="action-icon-enhanced action-icon--3">
                            <i class="<?php echo htmlspecialchars($cms['quick3_icon'] ?? 'fas fa-bowl-food'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['quick3_title'] ?? 'Browse Donations'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['quick3_description'] ?? 'Find available food near you.'); ?></p>
                        <span class="action-arrow"><i class="fa-solid fa-arrow-right"></i> View Listings</span>
                    </a>

                    <a href="<?php echo htmlspecialchars($cms['quick4_link'] ?? 'contact.php'); ?>" class="action-card-enhanced">
                        <div class="action-icon-enhanced action-icon--4">
                            <i class="<?php echo htmlspecialchars($cms['quick4_icon'] ?? 'fas fa-envelope'); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['quick4_title'] ?? 'Contact Us'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['quick4_description'] ?? 'Need help? Reach out anytime.'); ?></p>
                        <span class="action-arrow"><i class="fa-solid fa-arrow-right"></i> Contact</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-content" style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 32px; padding: 0 24px;">
            <div style="flex: 1; min-width: 260px;">
                <div class="site-logo" style="font-size:1.25rem;"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</div>
                <p style="color: #6b7280; margin-top: 12px; font-size: 0.9rem; line-height: 1.6; max-width: 360px;">
                    <?php echo htmlspecialchars($cms['footer_description'] ?? 'Built to connect surplus food with communities.'); ?>
                </p>
            </div>
            <div style="min-width: 160px;">
                <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:12px; color: #111827;" data-i18n="footer.quick_links">Quick Links</h4>
                <ul style="list-style:none; padding:0; margin:0;">
                    <li style="margin-bottom:8px;"><a href="index.php" style="color: #6b7280; text-decoration:none; font-size:0.85rem;" data-i18n="nav.home">Home</a></li>
                    <li style="margin-bottom:8px;"><a href="donations.php" style="color: #6b7280; text-decoration:none; font-size:0.85rem;" data-i18n="nav.food_listings">Food Listings</a></li>
                    <li style="margin-bottom:8px;"><a href="about.php" style="color: #6b7280; text-decoration:none; font-size:0.85rem;" data-i18n="nav.about">About</a></li>
                    <li style="margin-bottom:8px;"><a href="contact.php" style="color: #6b7280; text-decoration:none; font-size:0.85rem;" data-i18n="nav.contact">Contact</a></li>
                </ul>
            </div>
            <div style="min-width: 160px;">
                <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:12px; color: #111827;" data-i18n="footer.contact">Contact</h4>
                <ul style="list-style:none; padding:0; margin:0;">
                    <?php if (!empty($cms['footer_address'])): ?>
                        <li style="margin-bottom:8px; color:#6b7280; font-size:0.85rem;"><i class="fa-solid fa-location-dot" style="margin-right:6px;color:#059669;"></i> <?php echo htmlspecialchars($cms['footer_address']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($cms['footer_phone'])): ?>
                        <li style="margin-bottom:8px; color:#6b7280; font-size:0.85rem;"><i class="fa-solid fa-phone" style="margin-right:6px;color:#059669;"></i> <?php echo htmlspecialchars($cms['footer_phone']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($cms['footer_email'])): ?>
                        <li style="margin-bottom:8px; color:#6b7280; font-size:0.85rem;"><i class="fa-solid fa-envelope" style="margin-right:6px;color:#059669;"></i> <?php echo htmlspecialchars($cms['footer_email']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div style="min-width: 120px;">
                <h4 style="font-size:0.9rem; font-weight:700; margin-bottom:12px; color: #111827;" data-i18n="footer.follow_us">Follow Us</h4>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <?php if (!empty($cms['facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($cms['facebook']); ?>" target="_blank" style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#059669';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#374151'"><i class="fa-brands fa-facebook-f"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($cms['instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($cms['instagram']); ?>" target="_blank" style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#059669';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#374151'"><i class="fa-brands fa-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($cms['whatsapp'])): ?>
                        <a href="<?php echo htmlspecialchars($cms['whatsapp']); ?>" target="_blank" style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#059669';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#374151'"><i class="fa-brands fa-whatsapp"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($cms['linkedin'])): ?>
                        <a href="<?php echo htmlspecialchars($cms['linkedin']); ?>" target="_blank" style="width:36px;height:36px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;color:#374151;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.background='#059669';this.style.color='#fff'" onmouseout="this.style.background='#f3f4f6';this.style.color='#374151'"><i class="fa-brands fa-linkedin-in"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div style="border-top: 1px solid #e5e7eb; margin-top: 32px; padding-top: 24px; text-align: center; color: #9ca3af; font-size: 0.85rem;">
            <p>&copy; <?php echo htmlspecialchars($cms['copyright'] ?? date('Y') . ' Sayog. Built to connect surplus food with communities.'); ?></p>
        </div>
    </footer>
</body>
</html>
