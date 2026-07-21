<?php
require_once '../config.php';

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
<?php
$page_title = 'About | Sayog';
$active_page = 'about';
require_once '../header.php';
?>
    <style>
        :root { --primary-color: #059669; --primary-hover: #047857; --primary-light: rgba(5,150,105,0.08); --accent-green: #10b981; --text-main: #0f172a; --text-muted: #4b5563; --card-shadow: 0 10px 30px -5px rgba(4,120,87,0.05), 0 8px 15px -6px rgba(0,0,0,0.04); --transition-smooth: all 0.3s cubic-bezier(0.4,0,0.2,1); }
        body { background-color: #f4fbf7; color: var(--text-main); font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .about-hero-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 48px; align-items: center; padding: 60px 0; }
        @media (max-width: 991px) { .about-hero-grid { grid-template-columns: 1fr; gap: 40px; } }
        .about-badge { background: var(--primary-light); color: var(--primary-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 6px 16px; border-radius: 50px; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; border: 1px solid rgba(5,150,105,0.15); }
        .hero-title { font-size: 2.8rem; font-weight: 800; line-height: 1.2; letter-spacing: -0.02em; margin: 0 0 20px; background: linear-gradient(135deg,#0f172a 0%,#047857 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .hero-desc { font-size: 1.15rem; line-height: 1.7; color: var(--text-muted); margin-bottom: 32px; }
        .about-actions { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 40px; }
        .about-highlights { list-style: none; padding: 0; margin: 0; }
        .about-highlights li { display: flex; align-items: flex-start; gap: 12px; font-size: 1rem; color: #334155; margin-bottom: 14px; font-weight: 500; }
        .mission-card { background: #fff; border-radius: 24px; padding: 40px; box-shadow: var(--card-shadow); border: 1px solid #e6f4ea; position: relative; overflow: hidden; }
        .mission-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg,#10b981,#059669); }
        .mission-icon-box { width: 56px; height: 56px; background: linear-gradient(135deg,#10b981 0%,#059669 100%); color: #fff; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 24px; box-shadow: 0 8px 16px rgba(5,150,105,0.2); }
        .mission-card h3 { font-size: 1.5rem; font-weight: 700; margin: 0 0 12px; color: #0f172a; }
        .stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; border-top: 1px solid #ecfdf5; padding-top: 24px; }
        .stat-item strong { font-size: 1.6rem; font-weight: 800; color: var(--primary-color); }
        .panels-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin: 60px 0; }
        .overview-panel { background: #fff; padding: 32px; border-radius: 20px; box-shadow: var(--card-shadow); border: 1px solid #e6f4ea; }
        .feature-grid-wrapper { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 24px; margin-bottom: 40px; }
        .feature-box-card { background: #fff; padding: 36px 24px; border-radius: 20px; text-align: center; box-shadow: var(--card-shadow); border: 1px solid #e6f4ea; transition: var(--transition-smooth); }
        .feature-box-card:hover { transform: translateY(-6px); border-color: #a7f3d0; }
        .feature-box-card i { font-size: 1.8rem; width: 64px; height: 64px; display: inline-flex; align-items: center; justify-content: center; border-radius: 18px; margin-bottom: 22px; }
        .feature-box-card h3 { font-size: 1.25rem; font-weight: 700; margin: 0 0 12px; color: #0f172a; }
        .feature-box-card p { font-size: 0.92rem; color: var(--text-muted); line-height: 1.5; margin: 0; }

        /* About page green theme overrides */
        .site-header { border-bottom: 1px solid #e6f4ea; background: #ffffff; }
        .site-footer { background: #ffffff; padding: 24px; text-align: center; border-top: 1px solid #e6f4ea; color: var(--text-muted); font-size: 0.9rem; margin-top: 60px; }
        .site-footer .footer-content { max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 32px; padding: 0 24px; }
        .site-footer .site-logo { font-size: 1.25rem; }
        .site-footer h4 { font-size: 0.9rem; font-weight: 700; margin-bottom: 12px; color: #111827; }
        .site-footer a { color: #6b7280; text-decoration: none; font-size: 0.85rem; }
        .site-footer p { color: #6b7280; margin-top: 12px; font-size: 0.9rem; line-height: 1.6; max-width: 360px; }
    </style>


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

    <?php require_once '../footer.php'; ?>
