<?php
if (!isset($page_title)) $page_title = 'Sayog | Public Food Donation Portal';
if (!isset($active_page)) $active_page = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/js/app.js"></script>
    <link rel="stylesheet" href="/chatbot/chatbot.css">
    <script src="/chatbot/chatbot.js"></script>
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
          display: flex;
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
    <!-- Mobile Nav Overlay (click to close) -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>

    <header class="site-header">
        <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="site-branding">
            <a href="/frontend/index.php" class="site-logo"><i class="fa-solid fa-hand-holding-heart"></i> Sayog</a>
        </div>
        <nav class="site-nav" id="mobileNav">
            <a href="/frontend/index.php"<?php echo $active_page === 'home' ? ' class="active" style="color: #059669;"' : ''; ?> data-i18n="nav.home">Home</a>
            <a href="/frontend/donations.php"<?php echo $active_page === 'donations' ? ' class="active" style="color: #059669;"' : ''; ?> data-i18n="nav.food_listings">Food Listings</a>
            <a href="/frontend/volunteers.php"<?php echo $active_page === 'volunteers' ? ' class="active" style="color: #059669;"' : ''; ?> data-i18n="nav.volunteers">Volunteers</a>
            <a href="/frontend/team.php"<?php echo $active_page === 'team' ? ' class="active" style="color: #059669;"' : ''; ?> data-i18n="nav.our_team">Our Team</a>
            <a href="/frontend/about.php"<?php echo $active_page === 'about' ? ' class="active" style="color: #059669;"' : ''; ?> data-i18n="nav.about">About</a>
            <a href="/frontend/contact.php"<?php echo $active_page === 'contact' ? ' class="active" style="color: #059669;"' : ''; ?> data-i18n="nav.contact">Contact</a>
            <a href="/frontend/login.php" data-i18n="nav.login">Login</a>
            <a href="/frontend/register.php" style="background: #059669; color:#fff" data-i18n="nav.get_started">Get Started</a>
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" style="margin-left:4px;">
                <i class="fa-solid fa-moon"></i>
            </button>
            <button class="lang-toggle" onclick="toggleLanguage()" style="background:rgba(59,130,246,0.1);">
                <span>नेपाली</span>
            </button>
        </nav>
    </header>
