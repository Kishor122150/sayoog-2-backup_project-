<?php
require_once 'config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

// Load about page CMS content
$aboutStmt = $pdo->query("SELECT * FROM cms_aboutpage WHERE id = 1");
$cms = $aboutStmt->fetch(PDO::FETCH_ASSOC);
if (!$cms) {
    $cms = [
        'hero_badge' => 'About Sayog',
        'hero_title' => 'Connecting surplus food with people who need it most.',
        'hero_description' => '<strong>Sayog</strong> is a simple and compassionate food donation platform that helps individuals, families, and organizations share extra food instead of letting it go to waste.',
        'highlight1' => 'Reduce food waste in your community',
        'highlight2' => 'Support donors and receivers through one trusted platform',
        'highlight3' => 'Make food sharing fast, secure, and meaningful',
        'mission_title' => 'Our Mission',
        'mission_description' => 'To build a kinder, more sustainable community by making food sharing easier and more accessible for everyone.',
        'stat1_value' => '100%', 'stat1_label' => 'Community Driven',
        'stat2_value' => '24/7', 'stat2_label' => 'Sharing Access',
        'stat3_value' => '1', 'stat3_label' => 'Unified Hub',
        'panel1_title' => 'Why Sayog matters',
        'panel1_description' => 'Every day, good food is thrown away while many families still struggle to find meals. Sayog helps close that gap by encouraging thoughtful giving and responsible redistribution.',
        'panel2_title' => 'How it works',
        'panel2_description' => 'Register once and join as a donor or receiver. Create or browse food listings in a few simple steps. Track requests and communicate easily with other users.',
        'feature1_icon' => 'fas fa-hand-holding-heart', 'feature1_title' => 'Donate Food', 'feature1_description' => 'Create food donation listings in a few clicks and help nearby families.',
        'feature2_icon' => 'fas fa-utensils', 'feature2_title' => 'Request Food', 'feature2_description' => 'Request available food from registered donors with ease and confidence.',
        'feature3_icon' => 'fas fa-location-dot', 'feature3_title' => 'Track Requests', 'feature3_description' => 'Stay updated on the progress of every donation request you make.',
        'feature4_icon' => 'fas fa-users', 'feature4_title' => 'Build Community', 'feature4_description' => 'Connect donors, receivers, and support networks in one caring place.',
        'footer_copyright' => date('Y') . ' Sayog. Connecting surplus food with communities.',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | Sayog</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/app.js"></script>
    
    <style>
      /* ===== MOBILE NAVIGATION — Hamburger Menu ===== */
      .mobile-nav-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        color: var(--text-main, #0f172a);
        cursor: pointer;
        padding: 8px;
        line-height: 1;
        z-index: 1100;
        position: relative;
        transition: color 0.3s ease;
      }
      .mobile-nav-toggle:hover {
        color: var(--primary-color, #059669);
      }

      .mobile-nav-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.45);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1050;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
      }
      .mobile-nav-overlay.mobile-nav-open {
        opacity: 1;
        pointer-events: auto;
      }

      @media (max-width: 767px) {
        .site-nav {
          position: fixed;
          top: 0;
          left: 0;
          bottom: 0;
          width: 280px;
          max-width: 85vw;
          background: #ffffff;
          box-shadow: 0 0 40px rgba(0, 0, 0, 0.15);
          flex-direction: column;
          align-items: stretch;
          justify-content: flex-start;
          padding: 80px 20px 24px;
          gap: 4px;
          z-index: 1090;
          transform: translateX(-100%);
          transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
          overflow-y: auto;
          display: flex !important;
          flex-wrap: nowrap;
        }
        .site-nav.mobile-nav-open {
          transform: translateX(0);
        }
        .site-nav a {
          padding: 12px 16px;
          font-size: 15px;
          border-radius: 10px;
          width: 100%;
          justify-content: center;
        }
        .site-nav a[style*="background: #059669"] {
          padding: 12px 16px;
          font-size: 15px;
          width: 100%;
          justify-content: center;
        }
        .site-nav .theme-toggle,
        .site-nav .lang-toggle {
          width: 100%;
          justify-content: center;
          padding: 12px 16px;
          font-size: 14px;
          margin-left: 0 !important;
          margin-top: 4px;
        }
        .mobile-nav-toggle {
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .mobile-nav-overlay {
          display: block;
        }
        .mobile-nav-overlay.mobile-nav-open {
          display: block;
        }
      }
    </style>

    <!-- Self-contained custom green theme styling with beautiful components -->
    <style>
        :root {
            --primary-color: #059669; /* Emerald Green */
            --primary-hover: #047857;
            --primary-light: rgba(5, 150, 105, 0.08);
            --accent-green: #10b981;
            --text-main: #0f172a;
            --text-muted: #4b5563;
            --card-shadow: 0 10px 30px -5px rgba(4, 120, 87, 0.05), 0 8px 15px -6px rgba(0, 0, 0, 0.04);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: #f4fbf7; /* Very subtle fresh greenish tint tint tint */
            color: var(--text-main);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Hero Layout Split Grid */
        .about-hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 48px;
            align-items: center;
            padding: 60px 0;
        }

        @media (max-width: 991px) {
            .about-hero-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        /* Elegant Modern Green Badge */
        .about-badge {
            background: var(--primary-light);
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 6px 16px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(5, 150, 105, 0.15);
        }

        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            margin: 0 0 20px 0;
            background: linear-gradient(135deg, #0f172a 0%, #047857 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 1.15rem;
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        /* Custom Actions Button Components */
        .about-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .about-actions .btn {
            padding: 14px 28px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition-smooth);
        }

        .btn-primary {
            background: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 10px 20px -5px rgba(5, 150, 105, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(5, 150, 105, 0.4);
        }

        .btn-outline {
            background: #ffffff;
            color: var(--primary-color);
            border: 1px solid #a7f3d0;
        }

        .btn-outline:hover {
            background: #ecfdf5;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(239, 68, 68, 0.3);
        }

        /* Checkbox Feature Highlights List */
        .about-highlights {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .about-highlights li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 1rem;
            color: #334155;
            margin-bottom: 14px;
            font-weight: 500;
        }

        .about-highlights li i {
            color: var(--accent-green);
            font-size: 1.2rem;
            margin-top: 2px;
        }

        /* Mission Panel Box Card Container */
        .mission-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e6f4ea;
            position: relative;
            overflow: hidden;
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .mission-icon-box {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 24px;
            box-shadow: 0 8px 16px rgba(5, 150, 105, 0.2);
        }

        .mission-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: #0f172a;
        }

        .mission-card p {
            color: var(--text-muted);
            line-height: 1.6;
            font-size: 1rem;
            margin-bottom: 32px;
        }

        /* Dynamic Real-time Counter Grid */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            border-top: 1px solid #ecfdf5;
            padding-top: 24px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-item strong {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-color);
        }

        .stat-item span {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        /* Two Panel Operational Split Row Layout */
        .panels-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin: 60px 0;
        }

        @media (max-width: 768px) {
            .panels-grid {
                grid-template-columns: 1fr;
            }
        }

        .overview-panel {
            background: #ffffff;
            padding: 32px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid #e6f4ea;
        }

        .overview-panel h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 14px 0;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .overview-panel p {
            color: var(--text-muted);
            line-height: 1.6;
            margin: 0;
        }

        .overview-panel ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .overview-panel ul li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 14px;
            color: var(--text-muted);
        }

        .overview-panel ul li:last-child {
            margin-bottom: 0;
        }

        .overview-panel ul li i {
            color: var(--primary-color);
            margin-top: 4px;
            font-size: 0.9rem;
            background: var(--primary-light);
            padding: 4px;
            border-radius: 50%;
        }

        /* Balanced Quad-Block Card Showcase Layout Grid */
        .feature-grid-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .feature-box-card {
            background: #ffffff;
            padding: 36px 24px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--card-shadow);
            border: 1px solid #e6f4ea;
            transition: var(--transition-smooth);
        }

        .feature-box-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -5px rgba(4, 120, 87, 0.1);
            border-color: #a7f3d0;
        }

        .feature-box-card i {
            font-size: 1.8rem;
            width: 64px;
            height: 64px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            margin-bottom: 22px;
            transition: var(--transition-smooth);
        }

        .feature-box-card:hover i {
            transform: scale(1.05) rotate(5deg);
        }

        .feature-box-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: #0f172a;
        }

        .feature-box-card p {
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
        }

        /* ===== RESPONSIVE ENHANCEMENTS for About Page ===== */
        @media (max-width: 768px) {
          .about-hero-grid { padding: 20px 0; gap: 28px; }
          .about-hero-grid .hero-title { font-size: 1.6rem !important; }
          .about-hero-grid .hero-desc { font-size: 0.95rem !important; }
          .about-actions { flex-direction: column; }
          .about-actions .btn { width: 100%; justify-content: center; }
          .about-highlights li { font-size: 0.85rem !important; }
          .mission-card { padding: 24px !important; }
          .mission-card h3 { font-size: 1.2rem !important; }
          .stats-row { grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
          .stat-item strong { font-size: 1.2rem !important; }
          .stat-item span { font-size: 0.7rem !important; }
          .panels-grid { grid-template-columns: 1fr !important; gap: 16px; margin: 30px 0 !important; }
          .overview-panel { padding: 20px !important; }
          .overview-panel h2 { font-size: 1.2rem !important; }
          .feature-grid-wrapper { grid-template-columns: 1fr 1fr !important; gap: 16px; }
          .feature-box-card { padding: 24px 16px !important; }
        }

        @media (max-width: 480px) {
          .about-hero-grid .hero-title { font-size: 1.3rem !important; }
          .about-hero-grid .hero-desc { font-size: 0.9rem !important; }
          .about-badge { font-size: 0.75rem; padding: 5px 12px; }
          .about-highlights li { font-size: 0.8rem !important; gap: 8px; }
          .about-highlights li i { font-size: 1rem; }
          .mission-card { padding: 20px !important; }
          .mission-card h3 { font-size: 1.1rem !important; }
          .mission-card p { font-size: 0.9rem; }
          .stats-row { grid-template-columns: 1fr; gap: 12px; }
          .stat-item { align-items: center; text-align: center; }
          .stat-item strong { font-size: 1.1rem !important; }
          .panels-grid { gap: 12px; margin: 20px 0 !important; }
          .overview-panel { padding: 16px !important; border-radius: 16px; }
          .overview-panel h2 { font-size: 1rem !important; }
          .overview-panel p { font-size: 0.85rem; line-height: 1.5; }
          .feature-grid-wrapper { grid-template-columns: 1fr !important; gap: 12px; }
          .feature-box-card { padding: 20px 16px !important; }
          .feature-box-card h3 { font-size: 1.05rem; }
          .feature-box-card p { font-size: 0.85rem; }
          .feature-box-card i { width: 52px; height: 52px; font-size: 1.4rem; }
          .hero-title { margin-bottom: 14px !important; }
          .hero-desc { margin-bottom: 24px !important; }
        }

        @media (max-width: 375px) {
          .about-hero-grid { padding: 10px 0; gap: 20px; }
          .about-hero-grid .hero-title { font-size: 1.1rem !important; }
          .about-hero-grid .hero-desc { font-size: 0.85rem !important; line-height: 1.5; }
          .about-badge { font-size: 0.7rem; padding: 4px 10px; gap: 4px; }
          .about-highlights { gap: 6px; }
          .about-highlights li { font-size: 0.75rem !important; }
          .mission-card { padding: 16px !important; }
          .mission-card h3 { font-size: 1rem !important; }
          .mission-card p { font-size: 0.85rem; }
          .stat-item strong { font-size: 1rem !important; }
          .stat-item span { font-size: 0.65rem !important; }
          .feature-box-card { padding: 16px 12px !important; }
          .feature-box-card h3 { font-size: 0.95rem; }
          .feature-box-card p { font-size: 0.8rem; }
          .feature-box-card i { width: 44px; height: 44px; font-size: 1.2rem; }
          .overview-panel { padding: 14px !important; }
          .overview-panel h2 { font-size: 0.95rem !important; gap: 6px; }
          .overview-panel p { font-size: 0.8rem; }
          .about-actions { gap: 10px; }
          .about-actions .btn { padding: 10px 20px; font-size: 0.85rem; }
        }

        @media (max-width: 320px) {
          .about-hero-grid .hero-title { font-size: 1rem !important; }
          .about-hero-grid .hero-desc { font-size: 0.8rem !important; }
          .about-badge { font-size: 0.65rem; padding: 3px 8px; }
          .about-highlights li { font-size: 0.7rem !important; }
          .mission-card { border-radius: 16px; }
          .mission-card h3 { font-size: 0.95rem !important; }
          .mission-card p { font-size: 0.8rem; }
          .stat-item strong { font-size: 0.9rem !important; }
          .stat-item span { font-size: 0.6rem !important; letter-spacing: 0.3px; }
          .feature-box-card { padding: 12px 10px !important; border-radius: 14px; }
          .feature-box-card h3 { font-size: 0.9rem; }
          .feature-box-card p { font-size: 0.75rem; }
          .feature-box-card i { width: 38px; height: 38px; font-size: 1rem; }
          .overview-panel { padding: 12px !important; border-radius: 14px; }
          .overview-panel h2 { font-size: 0.9rem !important; }
          .overview-panel p { font-size: 0.75rem; }
          .about-actions .btn { padding: 8px 16px; font-size: 0.8rem; }
          .stats-row { gap: 8px; }
        }
    </style>
</head>
<body>
    <!-- Mobile Nav Overlay -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

    <header class="site-header" style="border-bottom: 1px solid #e6f4ea; background: #ffffff;">
        <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="site-branding">
            <a href="index.php" class="site-logo" style="color: var(--primary-color);"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</a>
        </div>
        <nav class="site-nav" id="mobileNav">
            <a href="index.php" data-i18n="nav.home">Home</a>
            <a href="donations.php" data-i18n="nav.food_listings">Food Listings</a>
            <a href="about.php" class="active" style="color: var(--primary-color);" data-i18n="nav.about">About</a>
            <a href="contact.php" data-i18n="nav.contact">Contact</a>
            <a href="login.php" data-i18n="nav.login">Login</a>
            <!-- <a href="register.php" style="background: var(--primary-color); color: #fff; padding: 8px 16px; border-radius: 8px;">Get Started</a> -->
            <a href="register.php" style="background: #059669; color:#fff" data-i18n="nav.get_started">Get Started</a>
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" style="margin-left:4px;">
                <i class="fa-solid fa-moon"></i>
            </button>
            <button class="lang-toggle" onclick="toggleLanguage()" style="background:rgba(59,130,246,0.1);">
                <span>नेपाली</span>
            </button>
        </nav>
    </header>

    <main class="site-main" style="max-width: 1200px; margin: 0 auto; padding: 20px 24px;">
        <section class="about-section">
            
            <!-- Dynamic Upper Grid Split Showcase -->
            <div class="about-hero-grid">
                <div class="about-intro">
                    <span class="about-badge">
                        <i class="fas fa-leaf"></i> <?php echo htmlspecialchars($cms['hero_badge'] ?? 'About Sayog'); ?>
                    </span>
                    <h1 class="hero-title"><?php echo htmlspecialchars($cms['hero_title'] ?? 'Connecting surplus food with people who need it most.'); ?></h1>
                    <p class="hero-desc">
                        <?php echo $cms['hero_description'] ?? '<strong>Sayog</strong> is a simple and compassionate food donation platform that helps individuals, families, and organizations share extra food instead of letting it go to waste.'; ?>
                    </p>
                    <div class="about-actions">
                        <a href="donations.php" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> <span data-i18n="about.explore">Explore Donations</span></a>
                        <a href="contact.php" class="btn btn-outline"><i class="fa-solid fa-envelope"></i> <span data-i18n="about.contact_us">Contact Us</span></a>
                    </div>
                    <ul class="about-highlights">
                        <?php if (!empty($cms['highlight1'])): ?>
                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($cms['highlight1']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($cms['highlight2'])): ?>
                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($cms['highlight2']); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($cms['highlight3'])): ?>
                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($cms['highlight3']); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Mission Card Deck Column Container -->
                <div class="about-visual">
                    <div class="mission-card">
                        <div class="mission-icon-box">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($cms['mission_title'] ?? 'Our Mission'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['mission_description'] ?? ''); ?></p>
                        
                        <div class="stats-row">
                            <div class="stat-item">
                                <strong><?php echo htmlspecialchars($cms['stat1_value'] ?? '100%'); ?></strong>
                                <span><?php echo htmlspecialchars($cms['stat1_label'] ?? 'Community Driven'); ?></span>
                            </div>
                            <div class="stat-item">
                                <strong><?php echo htmlspecialchars($cms['stat2_value'] ?? '24/7'); ?></strong>
                                <span><?php echo htmlspecialchars($cms['stat2_label'] ?? 'Sharing Access'); ?></span>
                            </div>
                            <div class="stat-item">
                                <strong><?php echo htmlspecialchars($cms['stat3_value'] ?? '1'); ?></strong>
                                <span><?php echo htmlspecialchars($cms['stat3_label'] ?? 'Unified Hub'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Overview Double Column Layout Blocks -->
            <div class="panels-grid">
                <div class="overview-panel">
                    <h2><i class="fas fa-shield-heart" style="color: var(--primary-color);"></i> <?php echo htmlspecialchars($cms['panel1_title'] ?? 'Why Sayog matters'); ?></h2>
                    <p style="margin-top: 10px;"><?php echo htmlspecialchars($cms['panel1_description'] ?? ''); ?></p>
                </div>

                <div class="overview-panel">
                    <h2><i class="fas fa-circle-nodes" style="color: var(--primary-color);"></i> <?php echo htmlspecialchars($cms['panel2_title'] ?? 'How it works'); ?></h2>
                    <?php if (!empty($cms['panel2_description'])): ?>
                        <p style="margin-top: 10px;"><?php echo htmlspecialchars($cms['panel2_description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Styled Quad Box Feature Matrix Display Section -->
            <div class="feature-section" style="margin-top: 40px;">
                <div class="feature-grid-wrapper">
                    <div class="feature-box-card">
                        <i class="<?php echo htmlspecialchars($cms['feature1_icon'] ?? 'fas fa-hand-holding-heart'); ?>" style="color: #059669; background: rgba(5, 150, 105, 0.08);"></i>
                        <h3><?php echo htmlspecialchars($cms['feature1_title'] ?? 'Donate Food'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['feature1_description'] ?? ''); ?></p>
                    </div>

                    <div class="feature-box-card">
                        <i class="<?php echo htmlspecialchars($cms['feature2_icon'] ?? 'fas fa-utensils'); ?>" style="color: #10b981; background: rgba(16, 185, 129, 0.08);"></i>
                        <h3><?php echo htmlspecialchars($cms['feature2_title'] ?? 'Request Food'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['feature2_description'] ?? ''); ?></p>
                    </div>

                    <div class="feature-box-card">
                        <i class="<?php echo htmlspecialchars($cms['feature3_icon'] ?? 'fas fa-location-dot'); ?>" style="color: #047857; background: rgba(4, 120, 87, 0.08);"></i>
                        <h3><?php echo htmlspecialchars($cms['feature3_title'] ?? 'Track Requests'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['feature3_description'] ?? ''); ?></p>
                    </div>

                    <div class="feature-box-card">
                        <i class="<?php echo htmlspecialchars($cms['feature4_icon'] ?? 'fas fa-users'); ?>" style="color: #34d399; background: rgba(52, 211, 153, 0.08);"></i>
                        <h3><?php echo htmlspecialchars($cms['feature4_title'] ?? 'Build Community'); ?></h3>
                        <p><?php echo htmlspecialchars($cms['feature4_description'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer" style="background: #ffffff; padding: 24px; text-align: center; border-top: 1px solid #e6f4ea; color: var(--text-muted); font-size: 0.9rem; margin-top: 60px;">
        <p>&copy; <?php echo htmlspecialchars($cms['footer_copyright'] ?? date('Y') . ' Sayog. Connecting surplus food with communities.'); ?></p>
    </footer>

  <script>
    (function() {
      var toggle = document.getElementById('mobileNavToggle');
      var nav = document.getElementById('mobileNav');
      var overlay = document.getElementById('mobileNavOverlay');
      var icon = toggle ? toggle.querySelector('i') : null;

      if (!toggle || !nav || !overlay) return;

      function openMenu() {
        nav.classList.add('mobile-nav-open');
        overlay.classList.add('mobile-nav-open');
        if (icon) {
          icon.className = 'fa-solid fa-xmark';
        }
        toggle.setAttribute('aria-label', 'Close navigation menu');
        document.body.style.overflow = 'hidden';
      }

      function closeMenu() {
        nav.classList.remove('mobile-nav-open');
        overlay.classList.remove('mobile-nav-open');
        if (icon) {
          icon.className = 'fa-solid fa-bars';
        }
        toggle.setAttribute('aria-label', 'Toggle navigation menu');
        document.body.style.overflow = '';
      }

      toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (nav.classList.contains('mobile-nav-open')) {
          closeMenu();
        } else {
          openMenu();
        }
      });

      overlay.addEventListener('click', closeMenu);

      nav.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', closeMenu);
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && nav.classList.contains('mobile-nav-open')) {
          closeMenu();
        }
      });
    })();
  </script>
</body>
</html>