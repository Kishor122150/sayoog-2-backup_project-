<?php
require_once 'config.php';

// Auto-expire past donations
$pdo->exec("UPDATE donations SET status = 'cancelled' WHERE status IN ('available', 'requested', 'accepted') AND expiry_time < NOW()");

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
    <script src="js/app.js"></script>
    <style>
      /* ===== MOBILE NAVIGATION — Hamburger Menu ===== */
      .mobile-nav-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        color: var(--text-primary, #0f172a);
        cursor: pointer;
        padding: 8px;
        line-height: 1;
        z-index: 1100;
        position: relative;
        transition: color 0.3s ease;
      }
      .mobile-nav-toggle:hover {
        color: var(--primary, #10b981);
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
          background: var(--surface, #ffffff);
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

        [data-theme="dark"] .site-nav {
          background: var(--surface, #1e293b);
          box-shadow: 0 0 40px rgba(0, 0, 0, 0.4);
        }
      }
    </style>
</head>
<body>
    <!-- Mobile Nav Overlay -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

    <header class="site-header">
        <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="site-branding">
            <a href="index.php" class="site-logo"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</a>
        </div>
        <nav class="site-nav" id="mobileNav">
            <a href="index.php" data-i18n="nav.home">Home</a>
            <a href="donations.php" class="active" style="color: #059669;" data-i18n="nav.food_listings">Food Listings</a>
            <a href="about.php" data-i18n="nav.about">About</a>
            <a href="contact.php" data-i18n="nav.contact">Contact</a>
            <a href="login.php" data-i18n="nav.login">Login</a>
            <!-- <a href="register.php">Get Started</a> -->
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
        <section class="section-block">
            <div class="section-heading">
                <h1 data-i18n="donations.heading">Available Food Listings</h1>
                <p data-i18n="donations.description">Browse recent food donations that are ready for pickup or request.</p>
            </div>

            <?php if (!empty($donations)): ?>
                <!-- Search Toolbar -->
                <div class="home-toolbar" style="margin-bottom:24px;">
                    <div class="home-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchFoodName" class="form-control" placeholder="Search by food name..." style="padding-left:40px;">
                    </div>
                    <div class="home-search">
                        <i class="fa-solid fa-location-dot"></i>
                        <input type="text" id="searchLocation" class="form-control" placeholder="Search by location..." style="padding-left:40px;">
                    </div>
                    <div class="filter-chips">
                        <button class="filter-chip active" data-filter="all" onclick="window.filterDonations('all')">All</button>
                        <button class="filter-chip" data-filter="nearby" onclick="window.filterDonations('nearby')">Nearby</button>
                    </div>
                </div>

                <!-- Interactive Map Section -->
                <div class="map-section">
                    <div class="map-section-header">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <h3>Donation Locations <span style="font-weight:400;color:var(--text-muted);font-size:13px;">— Click markers for details &amp; directions</span></h3>
                        <span id="mapResultsCount" class="badge badge-success" style="margin-left:auto;font-size:12px;"><?php echo count($donations); ?> donations</span>
                    </div>
                    <div class="map-container" id="donationsMap"></div>
                </div>
            <?php endif; ?>

            <?php if (empty($donations)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bowl-food"></i>
                    <h3>No active food listings right now.</h3>
                    <p>Donors can create donation entries from their dashboard.</p>
                </div>
            <?php else: ?>
                <div id="donationsGrid" class="product-grid">
                    <?php foreach ($donations as $d): ?>
                        <article class="product-card" data-donation-id="<?php echo $d['id']; ?>" data-food-item="<?php echo htmlspecialchars(strtolower($d['food_item'])); ?>" data-pickup-address="<?php echo htmlspecialchars(strtolower($d['pickup_address'])); ?>">
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
                                    <span> | <span class="countdown-badge" data-expiry="<?php echo $d['expiry_time']; ?>">⏳ Loading...</span></span>
                                </div>
                                <div style="margin-top:8px; font-size:13px; color:#666;">
                                    Donor: <?php echo htmlspecialchars($d['donor_name']); ?> | Pickup: <?php echo htmlspecialchars($d['pickup_address']); ?>
                                    <span class="donation-distance"><i class="fa-solid fa-location-dot"></i> Locating...</span>
                                </div>
                                <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                    <a href="donation.php?id=<?php echo $d['id']; ?>" class="btn btn-primary">View Details</a>
                                    <a href="login.php?redirect=donation.php?id=<?php echo $d['id']; ?>" class="btn btn-outline">Request Pickup</a>
                                    <?php if (!empty($d['phone'])): ?>
                                        <?php
                                        $wa_msg = 'Hello ' . $d['donor_name'] . ', I am interested in your food donation: ' . $d['food_item'] . ' on Sayog.';
                                        echo '<a href="' . get_whatsapp_link($d['phone'], $wa_msg) . '" target="_blank" class="btn btn-whatsapp btn-whatsapp-sm"><i class="fa-brands fa-whatsapp"></i> Chat</a>';
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php if (!empty($donations)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var donations = [
            <?php foreach ($donations as $d): ?>
            {
                id: <?php echo $d['id']; ?>,
                food_item: <?php echo json_encode($d['food_item']); ?>,
                address: <?php echo json_encode($d['pickup_address']); ?>,
                pickup_address: <?php echo json_encode($d['pickup_address']); ?>
            },
            <?php endforeach; ?>
        ];
        if (typeof window.initDonationsMap === 'function') {
            window.initDonationsMap('donationsMap', donations);
        }
    });
    </script>
    <?php endif; ?>

    <footer class="site-footer">
        <p>&copy; <?php echo date('Y'); ?> Sayog. Connecting surplus food with communities.</p>
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
