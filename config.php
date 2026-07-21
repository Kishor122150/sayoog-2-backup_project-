<?php
// PHP session configuration for enhanced security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Upload storage path
define('UPLOADS_DIR', __DIR__ . '/uploads');
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '@Kishor122150');
define('DB_NAME', 'sayog_db');

try {
    // 1. First connect to MySQL server without specifying the DB
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 2. Create database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 3. Select database
    $pdo->exec("USE `" . DB_NAME . "`");

    // 4. Create Tables
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `address` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `role` VARCHAR(30) NOT NULL DEFAULT 'user',
            `profile_photo` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createUsersTable);

    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    $columnCheck->execute(['role']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'user'");
    }

    $columnCheck->execute(['profile_photo']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL");
    }

    $createDonationsTable = "
        CREATE TABLE IF NOT EXISTS `donations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `donor_id` INT NOT NULL,
            `food_item` VARCHAR(100) NOT NULL,
            `quantity` VARCHAR(50) NOT NULL,
            `expiry_time` DATETIME NOT NULL,
            `pickup_address` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `description` TEXT,
            `status` ENUM('pending_review', 'available', 'requested', 'accepted', 'completed', 'cancelled', 'rejected') DEFAULT 'pending_review',
            `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
            `verification_note` TEXT DEFAULT NULL,
            `verified_at` DATETIME DEFAULT NULL,
            `verified_by` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createDonationsTable);

    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donations' AND COLUMN_NAME = ?");
    try {
        $pdo->exec("ALTER TABLE donations MODIFY COLUMN status ENUM('pending_review', 'available', 'requested', 'accepted', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending_review'");
    } catch (PDOException $e) {}

    $columnCheck->execute(['image_path']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
    }

    $columnCheck->execute(['video_path']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN video_path VARCHAR(255) DEFAULT NULL");
    }

    $columnCheck->execute(['verification_status']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    $columnCheck->execute(['verification_note']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN verification_note TEXT DEFAULT NULL");
    }

    $columnCheck->execute(['verified_at']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN verified_at DATETIME DEFAULT NULL");
    }

    $columnCheck->execute(['verified_by']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN verified_by INT DEFAULT NULL");
    }

    $pdo->exec("UPDATE donations SET verification_status = 'approved' WHERE verification_status IS NULL AND status IN ('available', 'requested', 'accepted', 'completed')");
    $pdo->exec("UPDATE donations SET verification_status = 'pending' WHERE verification_status IS NULL");

    $createRequestsTable = "
        CREATE TABLE IF NOT EXISTS `requests` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `donation_id` INT NOT NULL,
            `consumer_id` INT NOT NULL,
            `quantity_requested` VARCHAR(50) NOT NULL,
            `message` TEXT,
            `status` ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`consumer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createRequestsTable);

    $createNotificationsTable = "
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `type` VARCHAR(50) NOT NULL,
            `message` TEXT NOT NULL,
            `link` VARCHAR(255) DEFAULT NULL,
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createNotificationsTable);

    $createProductsTable = "
        CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(150) NOT NULL,
            `slug` VARCHAR(150) NOT NULL UNIQUE,
            `description` TEXT NOT NULL,
            `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `image_path` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createProductsTable);

    $createCmsPagesTable = "
        CREATE TABLE IF NOT EXISTS `cms_pages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `title` VARCHAR(150) NOT NULL,
            `content` TEXT NOT NULL,
            `meta_description` VARCHAR(255) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createCmsPagesTable);

    // Create cms_homepage table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cms_homepage` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `hero_heading` VARCHAR(255) DEFAULT NULL,
            `hero_subheading` TEXT DEFAULT NULL,
            `hero_button1_text` VARCHAR(100) DEFAULT NULL,
            `hero_button1_link` VARCHAR(255) DEFAULT NULL,
            `hero_button2_text` VARCHAR(100) DEFAULT NULL,
            `hero_button2_link` VARCHAR(255) DEFAULT NULL,
            `main_heading` VARCHAR(255) DEFAULT NULL,
            `main_description` TEXT DEFAULT NULL,
            `works_title` VARCHAR(255) DEFAULT NULL,
            `works_description` TEXT DEFAULT NULL,
            `work1_icon` VARCHAR(100) DEFAULT NULL,
            `work1_heading` VARCHAR(100) DEFAULT NULL,
            `work1_description` TEXT DEFAULT NULL,
            `work2_icon` VARCHAR(100) DEFAULT NULL,
            `work2_heading` VARCHAR(100) DEFAULT NULL,
            `work2_description` TEXT DEFAULT NULL,
            `work3_icon` VARCHAR(100) DEFAULT NULL,
            `work3_heading` VARCHAR(100) DEFAULT NULL,
            `work3_description` TEXT DEFAULT NULL,
            `work4_icon` VARCHAR(100) DEFAULT NULL,
            `work4_heading` VARCHAR(100) DEFAULT NULL,
            `work4_description` TEXT DEFAULT NULL,
            `quick_title` VARCHAR(255) DEFAULT NULL,
            `quick_description` TEXT DEFAULT NULL,
            `quick1_icon` VARCHAR(100) DEFAULT NULL,
            `quick1_title` VARCHAR(100) DEFAULT NULL,
            `quick1_description` TEXT DEFAULT NULL,
            `quick1_button` VARCHAR(100) DEFAULT NULL,
            `quick1_link` VARCHAR(255) DEFAULT NULL,
            `quick2_icon` VARCHAR(100) DEFAULT NULL,
            `quick2_title` VARCHAR(100) DEFAULT NULL,
            `quick2_description` TEXT DEFAULT NULL,
            `quick2_button` VARCHAR(100) DEFAULT NULL,
            `quick2_link` VARCHAR(255) DEFAULT NULL,
            `quick3_icon` VARCHAR(100) DEFAULT NULL,
            `quick3_title` VARCHAR(100) DEFAULT NULL,
            `quick3_description` TEXT DEFAULT NULL,
            `quick3_button` VARCHAR(100) DEFAULT NULL,
            `quick3_link` VARCHAR(255) DEFAULT NULL,
            `quick4_icon` VARCHAR(100) DEFAULT NULL,
            `quick4_title` VARCHAR(100) DEFAULT NULL,
            `quick4_description` TEXT DEFAULT NULL,
            `quick4_button` VARCHAR(100) DEFAULT NULL,
            `quick4_link` VARCHAR(255) DEFAULT NULL,
            `footer_description` TEXT DEFAULT NULL,
            `footer_address` VARCHAR(255) DEFAULT NULL,
            `footer_phone` VARCHAR(30) DEFAULT NULL,
            `footer_email` VARCHAR(100) DEFAULT NULL,
            `facebook` VARCHAR(255) DEFAULT NULL,
            `instagram` VARCHAR(255) DEFAULT NULL,
            `whatsapp` VARCHAR(255) DEFAULT NULL,
            `linkedin` VARCHAR(255) DEFAULT NULL,
            `copyright` TEXT DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed cms_homepage with default row if empty
    $checkHome = $pdo->query("SELECT COUNT(*) FROM cms_homepage")->fetchColumn();
    if ($checkHome == 0) {
        $pdo->exec("INSERT INTO cms_homepage (id, hero_heading, hero_subheading, hero_button1_text, hero_button1_link, hero_button2_text, hero_button2_link, main_heading, main_description, works_title, works_description, work1_icon, work1_heading, work1_description, work2_icon, work2_heading, work2_description, work3_icon, work3_heading, work3_description, work4_icon, work4_heading, work4_description, quick_title, quick_description, quick1_icon, quick1_title, quick1_description, quick1_button, quick1_link, quick2_icon, quick2_title, quick2_description, quick2_button, quick2_link, quick3_icon, quick3_title, quick3_description, quick3_button, quick3_link, quick4_icon, quick4_title, quick4_description, quick4_button, quick4_link, footer_description, footer_address, footer_phone, footer_email, facebook, instagram, whatsapp, linkedin, copyright) VALUES (1,
            'Welcome to Sayog',
            'Share surplus food, browse donation opportunities, and support local communities.',
            'Browse Food Listings', '/frontend/donations.php',
            'Member Login', 'login.php',
            'How It Works', 'Sayog connects people with surplus food to those who need it through a simple, secure, and transparent donation process.',
            'How Sayog Works', 'Sayog connects people with surplus food to those who need it through a simple, secure, and transparent donation process.',
            'fas fa-user-plus', 'Create Account', 'Register for free and become a member of the Sayog community.',
            'fas fa-hand-holding-heart', 'Share Food', 'Post available food donations with quantity, pickup location, and expiry time.',
            'fas fa-box-open', 'Request Donation', 'Browse available donations and request the items you need.',
            'fas fa-check-circle', 'Complete Pickup', 'Donor approves the request and the receiver collects the donation.',
            'Quick Actions', 'Start helping your community today.',
            'fas fa-user-plus', 'Join Sayog', 'Create your free account.', 'Get Started', 'register.php',
            'fas fa-right-to-bracket', 'Login', 'Access your dashboard.', 'Login', 'login.php',
            'fas fa-bowl-food', 'Browse Donations', 'Find available food near you.', 'View Listings', '/frontend/donations.php',
            'fas fa-envelope', 'Contact Us', 'Need help? Reach out anytime.', 'Contact', '/frontend/contact.php',
            'Built to connect surplus food with communities.',
            'Kathmandu, Nepal',
            '+977-1-4XXXXXX',
            'info@sayog.org',
            '#', '#', '#', '#',
            '© 2025 Sayog. Built to connect surplus food with communities.'
        )");
    }

    // Create about CMS table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cms_aboutpage` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `hero_badge` VARCHAR(100) DEFAULT NULL,
            `hero_title` VARCHAR(255) DEFAULT NULL,
            `hero_description` TEXT DEFAULT NULL,
            `highlight1` VARCHAR(255) DEFAULT NULL,
            `highlight2` VARCHAR(255) DEFAULT NULL,
            `highlight3` VARCHAR(255) DEFAULT NULL,
            `mission_title` VARCHAR(255) DEFAULT NULL,
            `mission_description` TEXT DEFAULT NULL,
            `stat1_value` VARCHAR(50) DEFAULT NULL,
            `stat1_label` VARCHAR(100) DEFAULT NULL,
            `stat2_value` VARCHAR(50) DEFAULT NULL,
            `stat2_label` VARCHAR(100) DEFAULT NULL,
            `stat3_value` VARCHAR(50) DEFAULT NULL,
            `stat3_label` VARCHAR(100) DEFAULT NULL,
            `panel1_title` VARCHAR(255) DEFAULT NULL,
            `panel1_description` TEXT DEFAULT NULL,
            `panel2_title` VARCHAR(255) DEFAULT NULL,
            `panel2_description` TEXT DEFAULT NULL,
            `feature1_icon` VARCHAR(100) DEFAULT NULL,
            `feature1_title` VARCHAR(100) DEFAULT NULL,
            `feature1_description` TEXT DEFAULT NULL,
            `feature2_icon` VARCHAR(100) DEFAULT NULL,
            `feature2_title` VARCHAR(100) DEFAULT NULL,
            `feature2_description` TEXT DEFAULT NULL,
            `feature3_icon` VARCHAR(100) DEFAULT NULL,
            `feature3_title` VARCHAR(100) DEFAULT NULL,
            `feature3_description` TEXT DEFAULT NULL,
            `feature4_icon` VARCHAR(100) DEFAULT NULL,
            `feature4_title` VARCHAR(100) DEFAULT NULL,
            `feature4_description` TEXT DEFAULT NULL,
            `footer_copyright` TEXT DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Auto-seed default admin user if none exists
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($checkAdmin == 0) {
        $adminHash = password_hash('admin@123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, address, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Administrator', 'admin@123', 'Admin Office', '0000000000', $adminHash, 'admin']);
    }

    // Seed cms_aboutpage with default row if empty
    $checkAbout = $pdo->query("SELECT COUNT(*) FROM cms_aboutpage")->fetchColumn();
    if ($checkAbout == 0) {
        $pdo->exec("INSERT INTO cms_aboutpage (id, hero_badge, hero_title, hero_description, highlight1, highlight2, highlight3, mission_title, mission_description, stat1_value, stat1_label, stat2_value, stat2_label, stat3_value, stat3_label, panel1_title, panel1_description, panel2_title, panel2_description, feature1_icon, feature1_title, feature1_description, feature2_icon, feature2_title, feature2_description, feature3_icon, feature3_title, feature3_description, feature4_icon, feature4_title, feature4_description, footer_copyright) VALUES (1,
            'About Sayog',
            'Connecting surplus food with people who need it most.',
            '<strong>Sayog</strong> is a simple and compassionate food donation platform that helps individuals, families, and organizations share extra food instead of letting it go to waste.',
            'Reduce food waste in your community',
            'Support donors and receivers through one trusted platform',
            'Make food sharing fast, secure, and meaningful',
            'Our Mission',
            'To build a kinder, more sustainable community by making food sharing easier and more accessible for everyone.',
            '100%', 'Community Driven',
            '24/7', 'Sharing Access',
            '1', 'Unified Hub',
            'Why Sayog matters',
            'Every day, good food is thrown away while many families still struggle to find meals. Sayog helps close that gap by encouraging thoughtful giving and responsible redistribution.',
            'How it works',
            'Register once and join as a donor or receiver. Create or browse food listings in a few simple steps. Track requests and communicate easily with other users.',
            'fas fa-hand-holding-heart', 'Donate Food', 'Create food donation listings in a few clicks and help nearby families.',
            'fas fa-utensils', 'Request Food', 'Request available food from registered donors with ease and confidence.',
            'fas fa-location-dot', 'Track Requests', 'Stay updated on the progress of every donation request you make.',
            'fas fa-users', 'Build Community', 'Connect donors, receivers, and support networks in one caring place.',
            '© 2026 Sayog. Connecting surplus food with communities.'
        )");
    }

    // Add qr_token column to donations if not exists
    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donations' AND COLUMN_NAME = ?");
    $columnCheck->execute(['qr_token']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN qr_token VARCHAR(64) DEFAULT NULL AFTER phone");
        $pdo->exec("CREATE INDEX idx_qr_token ON donations(qr_token)");
    }

    // Create ratings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `ratings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `donation_id` INT NOT NULL,
            `donor_id` INT NOT NULL,
            `receiver_id` INT NOT NULL,
            `volunteer_id` INT DEFAULT NULL,
            `rating_donor` TINYINT(1) DEFAULT NULL COMMENT 'Receiver\'s rating of the donor (1-5)',
            `rating_receiver` TINYINT(1) DEFAULT NULL COMMENT 'Donor\'s rating of the receiver (1-5)',
            `rating_volunteer` TINYINT(1) DEFAULT NULL COMMENT 'Consumer\'s rating of the volunteer (1-5)',
            `review_donor` TEXT DEFAULT NULL COMMENT 'Review left by receiver for donor',
            `review_receiver` TEXT DEFAULT NULL COMMENT 'Review left by donor for receiver',
            `review_volunteer` TEXT DEFAULT NULL COMMENT 'Review left by consumer for volunteer',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_donation_rating` (`donation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Add volunteer_id and rating_volunteer columns if they don't exist (for existing tables)
    $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ratings' AND COLUMN_NAME = ?");
    $colCheck->execute(['volunteer_id']);
    if (!$colCheck->fetchColumn()) { $pdo->exec("ALTER TABLE ratings ADD COLUMN volunteer_id INT DEFAULT NULL AFTER receiver_id"); }
    $colCheck->execute(['rating_volunteer']);
    if (!$colCheck->fetchColumn()) { $pdo->exec("ALTER TABLE ratings ADD COLUMN rating_volunteer TINYINT(1) DEFAULT NULL COMMENT 'Consumer\'s rating of the volunteer (1-5)' AFTER rating_receiver"); }
    $colCheck->execute(['review_volunteer']);
    if (!$colCheck->fetchColumn()) { $pdo->exec("ALTER TABLE ratings ADD COLUMN review_volunteer TEXT DEFAULT NULL COMMENT 'Review left by consumer for volunteer' AFTER review_receiver"); }

    $createAdminKeysTable = "
        CREATE TABLE IF NOT EXISTS `admin_keys` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `token_hash` VARCHAR(255) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createAdminKeysTable);

    // Add certificate_path column to donations
    $columnCheck->execute(['certificate_path']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN certificate_path VARCHAR(255) DEFAULT NULL AFTER qr_token");
    }

    // Add pickup_reminder_sent column to donations
    $columnCheck->execute(['pickup_reminder_sent']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN pickup_reminder_sent TINYINT(1) NOT NULL DEFAULT 0 AFTER certificate_path");
    }

    // Add latitude and longitude columns for map pins
    $columnCheck->execute(['latitude']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN latitude DECIMAL(10,7) DEFAULT NULL AFTER phone");
    }
    $columnCheck->execute(['longitude']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN longitude DECIMAL(10,7) DEFAULT NULL AFTER latitude");
    }

    // Add city column for easier Nepal location filtering
    $columnCheck->execute(['city']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER longitude");
    }

    // Create certificates directory
    $certDir = __DIR__ . '/uploads/certificates';
    if (!is_dir($certDir)) {
        mkdir($certDir, 0755, true);
    }

    // ─── VOLUNTEERS TABLE ───
    $createVolunteersTable = "
        CREATE TABLE IF NOT EXISTS `volunteers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL UNIQUE,
            `profile_photo` VARCHAR(255) DEFAULT NULL,
            `full_name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `address` VARCHAR(255) DEFAULT NULL,
            `municipality` VARCHAR(100) DEFAULT NULL,
            `ward_number` VARCHAR(20) DEFAULT NULL,
            `district` VARCHAR(100) DEFAULT NULL,
            `province` VARCHAR(100) DEFAULT NULL,
            `date_of_birth` DATE DEFAULT NULL,
            `gender` ENUM('male','female','other') DEFAULT NULL,
            `emergency_contact` VARCHAR(50) DEFAULT NULL,
            `occupation` VARCHAR(100) DEFAULT NULL,
            `citizenship_front` VARCHAR(255) DEFAULT NULL,
            `citizenship_back` VARCHAR(255) DEFAULT NULL,
            `national_id` VARCHAR(255) DEFAULT NULL,
            `college_id` VARCHAR(255) DEFAULT NULL,
            `driving_license` VARCHAR(255) DEFAULT NULL,
            `vehicle_type` ENUM('walking','bicycle','motorcycle','scooter','car') NOT NULL DEFAULT 'walking',
            `vehicle_number` VARCHAR(50) DEFAULT NULL,
            `license_number` VARCHAR(50) DEFAULT NULL,
            `delivery_radius` INT NOT NULL DEFAULT 5,
            `availability` SET('morning','afternoon','evening','weekend','always') NOT NULL DEFAULT 'always',
            `online_status` ENUM('available','busy','offline') NOT NULL DEFAULT 'offline',
            `previous_experience` TEXT DEFAULT NULL,
            `medical_training` TEXT DEFAULT NULL,
            `first_aid` TINYINT(1) NOT NULL DEFAULT 0,
            `languages` VARCHAR(255) DEFAULT NULL,
            `motivation` TEXT DEFAULT NULL,
            `status` ENUM('pending','approved','rejected','suspended','inactive') NOT NULL DEFAULT 'pending',
            `verified_by` INT DEFAULT NULL,
            `approved_at` DATETIME DEFAULT NULL,
            `rejected_reason` TEXT DEFAULT NULL,
            `volunteer_id` VARCHAR(50) DEFAULT NULL,
            `rating` DECIMAL(2,1) NOT NULL DEFAULT 0.0,
            `completed_deliveries` INT NOT NULL DEFAULT 0,
            `community_points` INT NOT NULL DEFAULT 0,
            `certificate_path` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createVolunteersTable);

    // ─── EMAIL VERIFICATIONS TABLE (for OTP during registration) ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `email_verifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(100) NOT NULL,
            `otp` VARCHAR(6) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `verified` TINYINT(1) NOT NULL DEFAULT 0,
            `attempts` TINYINT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_email_otp` (`email`, `otp`),
            INDEX `idx_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Migration: add `attempts` column for existing tables that were created before the column was added
    try {
        $pdo->exec("ALTER TABLE email_verifications ADD COLUMN attempts TINYINT NOT NULL DEFAULT 0 AFTER verified");
    } catch (PDOException $e) {
        // Column already exists — silently ignore
    }

    // ─── SMTP SETTINGS TABLE ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `smtp_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `host` VARCHAR(255) NOT NULL DEFAULT '',
            `port` INT NOT NULL DEFAULT 587,
            `username` VARCHAR(255) NOT NULL DEFAULT '',
            `password` VARCHAR(255) NOT NULL DEFAULT '',
            `encryption` ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
            `from_email` VARCHAR(255) NOT NULL DEFAULT '',
            `from_name` VARCHAR(100) NOT NULL DEFAULT 'Sayog',
            `is_active` TINYINT(1) NOT NULL DEFAULT 0,
            `test_mode` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed default SMTP settings row if missing
    $checkSmtp = $pdo->query("SELECT COUNT(*) FROM smtp_settings")->fetchColumn();
    if ($checkSmtp == 0) {
        $pdo->exec("INSERT INTO smtp_settings (host, port, username, password, encryption, from_email, from_name, is_active) VALUES ('', 587, '', '', 'tls', 'noreply@sayog.local', 'Sayog', 0)");
    }

    // Create upload directories for volunteer documents
    $volunteerDocsDir = __DIR__ . '/uploads/volunteer_docs';
    if (!is_dir($volunteerDocsDir)) {
        mkdir($volunteerDocsDir, 0755, true);
    }

    // ─── TEAM MEMBERS TABLE ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `team_members` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `role` VARCHAR(100) NOT NULL,
            `bio` TEXT DEFAULT NULL,
            `photo` VARCHAR(255) DEFAULT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `linkedin` VARCHAR(255) DEFAULT NULL,
            `github` VARCHAR(255) DEFAULT NULL,
            `website` VARCHAR(255) DEFAULT NULL,
            `display_order` INT NOT NULL DEFAULT 0,
            `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Create upload directory for team member photos
    $teamDir = __DIR__ . '/uploads/team';
    if (!is_dir($teamDir)) {
        mkdir($teamDir, 0755, true);
    }

    // ─── VOLUNTEER DELIVERIES TABLE ───
    // Add delivery_method column to donations (after donor approves)
    $colCheckDon = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donations' AND COLUMN_NAME = ?");
    $colCheckDon->execute(['delivery_method']);
    if (!$colCheckDon->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN delivery_method ENUM('self_pickup','volunteer') DEFAULT NULL AFTER status");
    }
    // Add delivery_method to requests
    $colCheckReq = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests' AND COLUMN_NAME = ?");
    $colCheckReq->execute(['delivery_method']);
    if (!$colCheckReq->fetchColumn()) {
        $pdo->exec("ALTER TABLE requests ADD COLUMN delivery_method ENUM('self_pickup','volunteer') DEFAULT NULL AFTER status");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `volunteer_deliveries` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `donation_id` INT NOT NULL,
            `request_id` INT NOT NULL,
            `volunteer_user_id` INT DEFAULT NULL,
            `consumer_id` INT NOT NULL,
            `donor_id` INT NOT NULL,
            `status` ENUM('assigned','accepted','picked_up','in_transit','delivered','cancelled') NOT NULL DEFAULT 'assigned',
            `accepted_at` DATETIME DEFAULT NULL,
            `picked_up_at` DATETIME DEFAULT NULL,
            `in_transit_at` DATETIME DEFAULT NULL,
            `delivered_at` DATETIME DEFAULT NULL,
            `cancelled_at` DATETIME DEFAULT NULL,
            `cancellation_reason` VARCHAR(255) DEFAULT NULL,
            `donor_notes` TEXT DEFAULT NULL,
            `delivery_notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`volunteer_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`consumer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_delivery_status` (`status`),
            INDEX `idx_delivery_volunteer` (`volunteer_user_id`),
            INDEX `idx_delivery_donation` (`donation_id`),
            INDEX `idx_delivery_request` (`request_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ─── VOLUNTEER LOCATIONS TABLE (GPS Tracking) ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `volunteer_locations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `volunteer_user_id` INT NOT NULL,
            `delivery_id` INT DEFAULT NULL,
            `latitude` DECIMAL(10,7) NOT NULL,
            `longitude` DECIMAL(10,7) NOT NULL,
            `accuracy` DECIMAL(10,2) DEFAULT NULL,
            `heading` DECIMAL(5,2) DEFAULT NULL,
            `speed` DECIMAL(5,2) DEFAULT NULL,
            `is_sharing` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`volunteer_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`delivery_id`) REFERENCES `volunteer_deliveries`(`id`) ON DELETE CASCADE,
            INDEX `idx_location_volunteer` (`volunteer_user_id`),
            INDEX `idx_location_delivery` (`delivery_id`),
            INDEX `idx_location_time` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Add tracking_sharing column to volunteers table
    $colCheckTrack = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'volunteers' AND COLUMN_NAME = ?");
    $colCheckTrack->execute(['tracking_enabled']);
    if (!$colCheckTrack->fetchColumn()) {
        $pdo->exec("ALTER TABLE volunteers ADD COLUMN tracking_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER online_status");
    }

    // Add assignment_method column to volunteer_deliveries to track auto vs manual assignment
    $colCheckAssign = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'volunteer_deliveries' AND COLUMN_NAME = ?");
    $colCheckAssign->execute(['assignment_method']);
    if (!$colCheckAssign->fetchColumn()) {
        $pdo->exec("ALTER TABLE volunteer_deliveries ADD COLUMN assignment_method ENUM('auto','manual_accept','admin_assign','reassigned') DEFAULT NULL AFTER status");
    }

    // ─── MESSAGING TABLE ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `donation_id` INT NOT NULL,
            `sender_id` INT NOT NULL,
            `receiver_id` INT NOT NULL,
            `message` TEXT NOT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_msg_donation` (`donation_id`),
            INDEX `idx_msg_users` (`sender_id`, `receiver_id`),
            INDEX `idx_msg_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ─── REFERRALS TABLE ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `referrals` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `referrer_id` INT NOT NULL,
            `referred_email` VARCHAR(100) NOT NULL,
            `referred_id` INT DEFAULT NULL,
            `status` ENUM('pending','joined','completed') NOT NULL DEFAULT 'pending',
            `completed_donation_id` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `completed_at` DATETIME DEFAULT NULL,
            FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_referrer` (`referrer_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Add referral_code column to users
    $colCheckRef = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    $colCheckRef->execute(['referral_code']);
    if (!$colCheckRef->fetchColumn()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) DEFAULT NULL UNIQUE AFTER profile_photo");
        $pdo->exec("CREATE INDEX idx_referral_code ON users(referral_code)");
    }

    // ─── FOOD DRIVES / EVENTS TABLE ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `food_drives` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `organizer_id` INT NOT NULL,
            `title` VARCHAR(200) NOT NULL,
            `description` TEXT,
            `location` VARCHAR(255) NOT NULL,
            `event_date` DATETIME NOT NULL,
            `end_date` DATETIME DEFAULT NULL,
            `target_meals` INT DEFAULT 0,
            `collected_meals` INT NOT NULL DEFAULT 0,
            `image_path` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('upcoming','active','completed','cancelled') NOT NULL DEFAULT 'upcoming',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_drive_status` (`status`),
            INDEX `idx_drive_date` (`event_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ─── FOOD DRIVE REGISTRATIONS ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `food_drive_registrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `drive_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `role` ENUM('volunteer','donor','participant') NOT NULL DEFAULT 'participant',
            `status` ENUM('registered','attended','cancelled') NOT NULL DEFAULT 'registered',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`drive_id`) REFERENCES `food_drives`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_registration` (`drive_id`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Add dietary_type column to donations
    $colCheckDiet = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donations' AND COLUMN_NAME = ?");
    $colCheckDiet->execute(['dietary_type']);
    if (!$colCheckDiet->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN dietary_type ENUM('all','vegetarian','vegan','non_veg','jain') NOT NULL DEFAULT 'all' AFTER description");
    }
    // Add food_category column
    $colCheckDiet->execute(['food_category']);
    if (!$colCheckDiet->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN food_category ENUM('cooked','packaged','fresh_produce','dairy','bakery','dry_goods','beverages') DEFAULT NULL AFTER dietary_type");
    }

    // ─── USER IMPACT STATS ───
    $colCheckImpact = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    $colCheckImpact->execute(['impact_points']);
    if (!$colCheckImpact->fetchColumn()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN impact_points INT NOT NULL DEFAULT 0 AFTER referral_code");
    }

    // ─── CHATBOT TABLES ───
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chatbot_knowledge` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `intent` VARCHAR(50) NOT NULL,
            `question` VARCHAR(255) NOT NULL,
            `answer` TEXT NOT NULL,
            `category` VARCHAR(50) DEFAULT 'general',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FULLTEXT INDEX `ft_search` (`question`, `answer`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chatbot_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL DEFAULT 0,
            `user_message` TEXT NOT NULL,
            `bot_response` TEXT NOT NULL,
            `intent` VARCHAR(50) DEFAULT NULL,
            `user_role` VARCHAR(20) DEFAULT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `page_url` VARCHAR(500) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_intent` (`intent`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chatbot_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT DEFAULT NULL,
            `setting_type` VARCHAR(20) NOT NULL DEFAULT 'text',
            `description` VARCHAR(255) DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed default chatbot settings if empty
    $chatbotCheck = $pdo->query("SELECT COUNT(*) FROM chatbot_settings")->fetchColumn();
    if ($chatbotCheck == 0) {
        $defaults = [
            ['bot_name', 'Sayog Assistant', 'text', 'Name displayed in the chat header'],
            ['welcome_message', '👋 Hello! I\'m Sayog Assistant, your AI-powered guide.', 'textarea', 'Welcome message'],
            ['default_suggestions', 'What is Sayog?, How to donate food, Available food, Platform Statistics', 'text', 'Default suggestions'],
            ['max_message_length', '500', 'number', 'Max message length'],
            ['log_retention_days', '90', 'number', 'Days to keep logs'],
            ['rate_limit_messages', '20', 'number', 'Max messages per session'],
            ['ai_model', 'rule_based', 'select', 'AI model to use'],
            ['bot_enabled', '1', 'boolean', 'Enable chatbot'],
        ];
        $stmt = $pdo->prepare("INSERT INTO chatbot_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
        foreach ($defaults as $d) {
            $stmt->execute($d);
        }
    }

} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

// -------------------------------------------------------------
// Helper & Validation Functions
// -------------------------------------------------------------

/**
 * Sanitize User Input Data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Ensure an asset URL is absolute from the web root.
 * Handles paths stored as relative (e.g., 'uploads/team/photo.png')
 * so they work correctly from any subdirectory like /frontend/.
 */
function asset_url($path) {
    if (empty($path)) return '';
    // Already absolute URL (http://, https://, //)
    if (preg_match('/^(https?:)?\/\//', $path)) return $path;
    // Already starts with /
    if (isset($path[0]) && $path[0] === '/') return $path;
    // Make relative path root-absolute
    return '/' . $path;
}

/**
 * Validates Nepal Phone Numbers (Mobile starting with 98/97/96 or Landline with 01)
 */
function validate_nepal_phone($phone) {
    // Match 10-digit mobile phone numbers: 98XXXXXXXX, 97XXXXXXXX, 96XXXXXXXX
    // Or match Kathmandu landlines: 01XXXXXXX (9 digits)
    $phone = trim($phone);
    $mobile_pattern = '/^(98|97|96)\d{8}$/';
    $landline_pattern = '/^01\d{7}$/';
    return preg_match($mobile_pattern, $phone) || preg_match($landline_pattern, $phone);
}

/**
 * Validates Password (Minimum 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char)
 */
function validate_password($password) {
    // Minimum 8 characters, at least 1 uppercase letter, 1 lowercase letter, 1 number, 1 special character
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/';
    return preg_match($pattern, $password);
}

/**
 * Set a flash message to display on the next page load
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'info', 'warning'
        'message' => $message
    ];
}

/**
 * Retrieve and clear the flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Auto-expire donations that have passed their expiry_time
 * Call this on page loads to keep listings current
 */
function auto_expire_donations($pdo) {
    try {
        $stmt = $pdo->prepare("
            UPDATE donations 
            SET status = 'cancelled' 
            WHERE status IN ('available', 'requested', 'accepted') 
              AND expiry_time < NOW()
        ");
        $stmt->execute();
    } catch (PDOException $e) {
        // Silently handle - not critical
    }
}

/**
 * Send notification record and optionally attempt email delivery.
 */
function create_notification($pdo, $user_id, $type, $message, $link = null, $sendEmail = false) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $message, $link]);

        if ($sendEmail) {
            $emailStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $emailStmt->execute([$user_id]);
            $user = $emailStmt->fetch();
            if ($user && filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                $subject = "Sayog Notification: " . ucfirst(str_replace('_', ' ', $type));
                $body = $message;
                if ($link) {
                    $body .= "\n\nView details: " . $link;
                }
                $htmlBody = '
                <!DOCTYPE html>
                <html>
                <head><meta charset="UTF-8"></head>
                <body style="margin:0;padding:0;background-color:#f4f7f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7f6;padding:30px 10px;">
                        <tr><td align="center">
                            <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
                                <tr>
                                    <td style="background:linear-gradient(135deg,#059669,#047857);padding:28px 40px;text-align:center;">
                                        <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">Sayog Notification</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:32px 40px;">
                                        <p style="color:#6b7280;margin:0 0 20px 0;font-size:15px;line-height:1.6;">Hi ' . htmlspecialchars($user['name']) . ',</p>
                                        <p style="color:#374151;margin:0 0 16px 0;font-size:15px;line-height:1.6;">' . nl2br(htmlspecialchars($message)) . '</p>';
                if ($link) {
                    $htmlBody .= '<p style="margin:24px 0;"><a href="' . htmlspecialchars($link) . '" style="display:inline-block;background:#059669;color:#ffffff;padding:12px 28px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">View Details</a></p>';
                }
                $htmlBody .= '
                                        <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
                                        <p style="color:#9ca3af;margin:0;font-size:12px;line-height:1.5;">Sayog &mdash; Built to connect surplus food with communities.</p>
                                    </td>
                                </tr>
                            </table>
                        </td></tr>
                    </table>
                </body>
                </html>
                ';
                send_email_smtp($pdo, $user['email'], $subject, $body, $htmlBody);
            }
        }
    } catch (PDOException $e) {
        // ignore notification failure to avoid breaking user flow
    }
}

function get_unread_notifications_count($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

function get_user_notifications($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function mark_all_notifications_read($pdo, $user_id) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_admin_logged_in() {
    return !empty($_SESSION['admin_logged_in']);
}

function verify_admin_key($pdo, $admin_key) {
    $stmt = $pdo->prepare("SELECT * FROM admin_keys WHERE is_active = 1");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        if (password_verify($admin_key, $row['token_hash'])) {
            return $row;
        }
    }
    return false;
}

function get_active_products($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_donation_by_id($pdo, $donation_id) {
    $stmt = $pdo->prepare("SELECT d.*, u.name AS donor_name, u.address AS donor_address, u.phone AS donor_phone FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.id = ? AND d.verification_status = 'approved' AND d.status IN ('available','requested')");
    $stmt->execute([$donation_id]);
    return $stmt->fetch();
}

/**
 * Get available donation listings (food donations)
 */
function get_available_donations($pdo) {
    $stmt = $pdo->prepare("SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.verification_status = 'approved' AND d.status = 'available' ORDER BY d.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Smart Recommendations Engine — suggests donations based on location, request history, and popularity.
 *
 * Scoring factors:
 * - Location proximity (common address keywords between user and pickup)
 * - Request history (similar food items the user has previously requested)
 * - Popularity (number of total requests a donation has received)
 * - Freshness (newer donations rank higher)
 *
 * Returns an array of scored donation arrays, sorted by relevance (highest first).
 */
function get_recommendations($pdo, $user_id, $user_address, $limit = 6) {
    // 1. Get user's request history — food items they've requested before
    $stmt = $pdo->prepare("
        SELECT DISTINCT LOWER(d.food_item) AS food_item
        FROM requests r
        JOIN donations d ON r.donation_id = d.id
        WHERE r.consumer_id = ?
    ");
    $stmt->execute([$user_id]);
    $history_items = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Get user's own donation history — food items they've donated (donor may also be a consumer)
    $stmt = $pdo->prepare("
        SELECT DISTINCT LOWER(d.food_item) AS food_item
        FROM donations d
        WHERE d.donor_id = ?
    ");
    $stmt->execute([$user_id]);
    $donated_items = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Fetch all available/requested approved donations (excluding user's own)
    $stmt = $pdo->prepare("
        SELECT d.*, u.name AS donor_name,
            (SELECT COUNT(*) FROM requests WHERE donation_id = d.id AND status NOT IN ('cancelled', 'rejected')) AS request_count
        FROM donations d
        JOIN users u ON d.donor_id = u.id
        WHERE d.verification_status = 'approved'
          AND d.status IN ('available', 'requested')
          AND d.donor_id != ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $donations = $stmt->fetchAll();

    if (empty($donations)) {
        return [];
    }

    // 4. Tokenize user's address for location matching
    $user_tokens = tokenize_address($user_address);

    // 5. Score each donation
    $scored = [];
    foreach ($donations as $d) {
        $score = 0;
        $reasons = [];

        $donation_food = strtolower(trim($d['food_item']));

        // --- Location score (max ~30 points) ---
        $pickup_tokens = tokenize_address($d['pickup_address']);
        $common = array_intersect($user_tokens, $pickup_tokens);
        $common_count = count($common);
        if ($common_count > 0) {
            $location_pts = min($common_count * 7, 30);
            $score += $location_pts;
            if ($common_count >= 2) {
                $reasons[] = 'nearby';
            }
        }

        // --- History score: similar food items (max ~35 points) ---
        $best_match = 0;
        foreach ($history_items as $hist) {
            if ($donation_food === $hist) {
                $best_match = 100;
                break;
            }
            similar_text($donation_food, $hist, $percent);
            if ($percent > $best_match) {
                $best_match = $percent;
            }
        }
        // Also check against items the user has donated (they might want similar things)
        foreach ($donated_items as $donated) {
            if ($donation_food === $donated) {
                $best_match = max($best_match, 70);
            } else {
                similar_text($donation_food, $donated, $pct);
                if ($pct > $best_match) {
                    $best_match = $pct;
                }
            }
        }
        if ($best_match > 60) {
            $history_pts = min(($best_match / 100) * 35, 35);
            $score += $history_pts;
            $reasons[] = 'history';
        }

        // --- Popularity score (max ~15 points) ---
        $popularity_pts = min((int)$d['request_count'] * 4, 15);
        $score += $popularity_pts;
        if ((int)$d['request_count'] > 1) {
            $reasons[] = 'popular';
        }

        // --- Freshness score (max ~15 points, decays by ~1 per day) ---
        $hours_old = (time() - strtotime($d['created_at'])) / 3600;
        $freshness_pts = max(0, 15 - ($hours_old / 24));
        $score += $freshness_pts;

        // --- Urgency bonus: if expiring soon (within 24h), boost priority (max ~5 points) ---
        if (!empty($d['expiry_time'])) {
            $expiry_ts = strtotime($d['expiry_time']);
            $hours_until_expiry = ($expiry_ts - time()) / 3600;
            if ($hours_until_expiry > 0 && $hours_until_expiry < 24) {
                $urgent_pts = max(0, 5 - ($hours_until_expiry / 24) * 5);
                $score += $urgent_pts;
                $reasons[] = 'urgent';
            }
        }

        $scored[] = [
            'donation' => $d,
            'score'    => round($score, 1),
            'reasons'  => $reasons,
        ];
    }

    // 6. Sort by score descending
    usort($scored, function ($a, $b) {
        return $b['score'] - $a['score'];
    });

    // 7. Return top N
    return array_slice($scored, 0, $limit);
}

/**
 * Tokenize an address string into meaningful keywords for location matching.
 * Strips common words and splits on whitespace, commas, etc.
 */
function tokenize_address($address) {
    $address = strtolower(trim($address));
    // Split on whitespace, commas, hyphens, forward slashes
    $words = preg_split('/[\s,\-\/]+/', $address);
    // Remove very short words (1-2 chars) and common noise words
    $stop_words = ['a', 'an', 'the', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'nr', 'no', 'st', 'rd', 'th', 'apt', 'po', 'box'];
    $tokens = [];
    foreach ($words as $w) {
        $w = trim($w);
        if (strlen($w) > 2 && !in_array($w, $stop_words)) {
            $tokens[] = $w;
        }
    }
    return $tokens;
}

function get_product_by_slug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ? AND status = 'active'");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function get_cms_page_by_slug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE slug = ? AND is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

/**
 * Redirect back helper
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Generate a unique QR verification token for a donation.
 */
function generate_qr_token($pdo, $donation_id) {
    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE donations SET qr_token = ? WHERE id = ? AND qr_token IS NULL");
    $stmt->execute([$token, $donation_id]);
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("SELECT qr_token FROM donations WHERE id = ?");
        $stmt->execute([$donation_id]);
        $token = $stmt->fetchColumn();
    }
    return $token;
}

/**
 * Get QR code image URL from free API.
 */
function get_qr_image_url($token, $size = 200) {
    $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    $verifyUrl = $base . '/qr-scan.php?token=' . urlencode($token);
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($verifyUrl);
}

/**
 * Get or generate QR token for a donation.
 */
function get_or_create_qr_token($pdo, $donation_id) {
    $stmt = $pdo->prepare("SELECT qr_token FROM donations WHERE id = ?");
    $stmt->execute([$donation_id]);
    $token = $stmt->fetchColumn();
    if (empty($token)) {
        $token = generate_qr_token($pdo, $donation_id);
    }
    return $token;
}

/**
 * Get average rating stats for a user.
 */
function get_user_rating($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(rating_donor), 1) AS avg_as_donor,
               COUNT(rating_donor) AS count_as_donor,
               ROUND(AVG(rating_receiver), 1) AS avg_as_receiver,
               COUNT(rating_receiver) AS count_as_receiver,
               ROUND(AVG(rating_volunteer), 1) AS avg_as_volunteer,
               COUNT(rating_volunteer) AS count_as_volunteer
        FROM ratings
        WHERE donor_id = ? OR receiver_id = ? OR volunteer_id = ?
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $row = $stmt->fetch();
    $avg = 0; $total = 0; $sum = 0;
    if ($row) {
        if ($row['avg_as_donor'] && $row['count_as_donor']) {
            $sum += $row['avg_as_donor'] * $row['count_as_donor'];
            $total += $row['count_as_donor'];
        }
        if ($row['avg_as_receiver'] && $row['count_as_receiver']) {
            $sum += $row['avg_as_receiver'] * $row['count_as_receiver'];
            $total += $row['count_as_receiver'];
        }
        if ($row['avg_as_volunteer'] && $row['count_as_volunteer']) {
            $sum += $row['avg_as_volunteer'] * $row['count_as_volunteer'];
            $total += $row['count_as_volunteer'];
        }
        if ($total > 0) $avg = round($sum / $total, 1);
    }
    return ['average' => $avg, 'total_ratings' => $total,
        'as_donor' => $row['avg_as_donor'] ?? 0, 'as_receiver' => $row['avg_as_receiver'] ?? 0,
        'count_as_donor' => $row['count_as_donor'] ?? 0, 'count_as_receiver' => $row['count_as_receiver'] ?? 0,
        'as_volunteer' => $row['avg_as_volunteer'] ?? 0, 'count_as_volunteer' => $row['count_as_volunteer'] ?? 0];
}

/**
 * Render star rating HTML.
 */
function render_stars($rating, $size = 14) {
    $html = '<span class="stars-display" style="font-size:' . $size . 'px;color:#f59e0b;white-space:nowrap;">';
    $full = floor($rating); $half = ($rating - $full) >= 0.5;
    for ($i = 0; $i < 5; $i++) {
        if ($i < $full) $html .= '<i class="fa-solid fa-star"></i>';
        elseif ($i === $full && $half) $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        else $html .= '<i class="fa-regular fa-star"></i>';
    }
    return $html . '</span>';
}

/**
 * Render rating badge HTML for a user card.
 */
function render_rating_badge($pdo, $user_id) {
    $r = get_user_rating($pdo, $user_id);
    if ($r['total_ratings'] === 0) {
        return '<span class="rating-badge new-user-badge"><i class="fa-regular fa-star"></i> No ratings yet</span>';
    }
    return '<span class="rating-badge">' . render_stars($r['average'], 12)
        . ' <span class="rating-number">' . number_format($r['average'], 1) . '</span>'
        . ' <span class="rating-count">(' . $r['total_ratings'] . ')</span></span>';
}

/**
 * Generate a WhatsApp chat link with a pre-filled message.
 */
function get_whatsapp_link($phone, $message = '') {
    // Strip all non-digit characters
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // If the number doesn't start with a country code (e.g., 977 for Nepal),
    // assume it's a local Nepali number and prepend +977
    if (strlen($clean) === 10 && preg_match('/^(98|97|96)/', $clean)) {
        $clean = '977' . $clean;
    }
    
    $url = 'https://wa.me/' . $clean;
    if (!empty($message)) {
        $url .= '?text=' . urlencode($message);
    }
    return $url;
}

/**
 * Render a styled WhatsApp chat button (safe to echo in templates).
 *
 * @param string $phone   The phone number
 * @param string $label   Button label
 * @param string $message Pre-filled message
 * @return string         HTML anchor element
 */
function whatsapp_button($phone, $label = 'Chat via WhatsApp', $message = '') {
    if (empty($phone)) return '';
    $link = get_whatsapp_link($phone, $message);
    return '<a href="' . $link . '" target="_blank" class="btn btn-whatsapp">'
         . '<i class="fa-brands fa-whatsapp"></i> ' . htmlspecialchars($label)
         . '</a>';
}




/**
 * Send a message between donor and receiver for a donation.
 */
function send_message($pdo, $donation_id, $sender_id, $receiver_id, $message) {
    $stmt = $pdo->prepare("INSERT INTO messages (donation_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$donation_id, $sender_id, $receiver_id, $message]);
    create_notification($pdo, $receiver_id, 'new_message', 'You have a new message regarding a donation.', 'dashboard.php?page=messages&donation_id=' . $donation_id);
    return $pdo->lastInsertId();
}

/**
 * Get messages for a donation conversation.
 */
function get_messages($pdo, $donation_id, $user_id) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name AS sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.donation_id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$donation_id, $user_id, $user_id]);
    return $stmt->fetchAll();
}

/**
 * Get unread message count for a user.
 */
function get_unread_message_count($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Mark messages as read for a donation.
 */
function mark_messages_read($pdo, $donation_id, $user_id) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE donation_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$donation_id, $user_id]);
}

/**
 * Generate a unique referral code for a user.
 */
function generate_referral_code($pdo, $user_id) {
    $code = strtoupper(substr(md5($user_id . time()), 0, 8));
    $stmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ? AND referral_code IS NULL");
    $stmt->execute([$code, $user_id]);
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $code = $stmt->fetchColumn();
    }
    return $code;
}

/**
 * Get referral stats for a user.
 */
function get_referral_stats($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'joined' THEN 1 ELSE 0 END) as joined, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed FROM referrals WHERE referrer_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Calculate impact metrics for a user.
 */
function get_user_impact($pdo, $user_id) {
    $donations = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(CAST(quantity AS UNSIGNED)), 0) as total_qty FROM donations WHERE donor_id = ? AND status = 'completed'");
    $donations->execute([$user_id]);
    $d = $donations->fetch();
    
    $requests = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE consumer_id = ? AND status = 'completed'");
    $requests->execute([$user_id]);
    $r = (int)$requests->fetchColumn();
    
    $qty = (int)$d['total_qty'];
    if ($qty <= 0) $qty = (int)$d['total'] * 3; // estimate ~3 servings per donation
    
    $meals = $qty;
    $co2_saved = round($meals * 2.5, 1); // kg CO2 equivalent per meal saved
    $water_saved = round($meals * 500, 1); // liters of water per meal
    
    return [
        'donations_completed' => (int)$d['total'],
        'requests_completed' => $r,
        'meals_provided' => $meals,
        'co2_saved_kg' => $co2_saved,
        'water_saved_liters' => $water_saved,
        'total_actions' => (int)$d['total'] + $r,
    ];
}

/**
 * Get food safety score for a donor (A/B/C/D based on ratings, cancellations, response rate).
 */
function get_food_safety_score($pdo, $user_id) {
    $ratings = get_user_rating($pdo, $user_id);
    $avg = $ratings['average'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ? AND status = 'cancelled'");
    $stmt->execute([$user_id]);
    $cancellations = (int)$stmt->fetchColumn();
    
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ?");
    $stmt2->execute([$user_id]);
    $total_donations = (int)$stmt2->fetchColumn();
    
    $cancel_rate = $total_donations > 0 ? ($cancellations / $total_donations) * 100 : 0;
    
    if ($avg >= 4.5 && $cancel_rate < 10) return ['grade' => 'A', 'label' => 'Excellent', 'color' => '#16a34a'];
    if ($avg >= 3.5 && $cancel_rate < 20) return ['grade' => 'B', 'label' => 'Good', 'color' => '#2563eb'];
    if ($avg >= 2.5 && $cancel_rate < 35) return ['grade' => 'C', 'label' => 'Fair', 'color' => '#f59e0b'];
    return ['grade' => 'D', 'label' => 'Needs Improvement', 'color' => '#ef4444'];
}

/**
 * Get active food drives.
 */
function get_active_food_drives($pdo, $limit = 6) {
    $stmt = $pdo->prepare("SELECT fd.*, u.name AS organizer_name FROM food_drives fd JOIN users u ON fd.organizer_id = u.id WHERE fd.status IN ('upcoming', 'active') ORDER BY fd.event_date ASC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get user's food drive registrations.
 */
function get_user_drive_registrations($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT fdr.*, fd.title, fd.event_date, fd.location, fd.status AS drive_status FROM food_drive_registrations fdr JOIN food_drives fd ON fdr.drive_id = fd.id WHERE fdr.user_id = ? ORDER BY fd.event_date DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get unread messages count for display in header.
 */
function count_unread_conversations($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT donation_id) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Generate a PDF Certificate of Appreciation for a completed donation.
 *
 * @param PDO    $pdo
 * @param int    $donation_id
 * @param string $donor_name
 * @param string $food_item
 * @param string $receiver_name (optional)
 * @return string|null Path to the generated certificate file, or null on failure.
 */
function generate_donation_certificate($pdo, $donation_id, $donor_name, $food_item, $receiver_name = 'Community') {
    try {
        $certDir = __DIR__ . '/uploads/certificates';
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }

        $filename = 'certificate_' . $donation_id . '_' . uniqid() . '.pdf';
        $filepath = $certDir . '/' . $filename;

        // Include TCPDF
        require_once __DIR__ . '/tcpdf/tcpdf.php';

        // Create new PDF document (Landscape A4)
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Sayog Food Donation Platform');
        $pdf->SetAuthor('Sayog');
        $pdf->SetTitle('Certificate of Appreciation - ' . $food_item);
        $pdf->SetSubject('Donation Certificate');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Add a page
        $pdf->AddPage();

        // Certificate generation date
        $dateStr = date('F d, Y');
        $certNumber = 'SAYOG-CERT-' . str_pad($donation_id, 5, '0', STR_PAD_LEFT);
        $donor_escaped = htmlspecialchars($donor_name);
        $food_escaped = htmlspecialchars($food_item);
        $rec_escaped = htmlspecialchars($receiver_name);

        // TCPDF-compatible HTML with table-based layout (no position:absolute, no gradients)
        $html = '
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <tr><td style="border:6px solid #c9a84c; padding:10px; background-color:#fdfcf5;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <tr><td style="border:2px solid #b8943a; padding:30px 28px 24px 28px; background-color:#fefcf5;">
        <div style="text-align:center;">

        <div style="text-align:center; color:#c9a84c; font-size:18px; letter-spacing:2px; margin-bottom:2px;">&#9670; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &#9670;</div>

        <div style="font-size:40px; color:#c9a84c; margin:8px 0 4px 0;">&#9733;</div>

        <div style="color:#8b6d2c; font-size:13px; font-weight:bold; letter-spacing:4px; text-transform:uppercase; margin:0 0 4px 0;">Sayog</div>

        <div style="color:#c9a84c; font-size:14px; letter-spacing:6px; margin:2px 0;">&#9679; &#9671; &#9679; &#9671; &#9679;</div>

        <div style="color:#2d5a3d; font-size:30px; font-weight:bold; margin:4px 0 4px 0;">Certificate of Appreciation</div>

        <div style="color:#c9a84c; font-size:16px; letter-spacing:6px; margin:2px 0 8px 0;">&#9679; &#9670; &#9679; &#9670; &#9679;</div>

        <div style="color:#6b7280; font-size:12px; margin:0 0 16px 0; letter-spacing:1px;">In recognition of generous contribution to the community</div>

        <div style="color:#6b7280; font-size:14px; margin:16px 0 4px 0; font-style:italic;">This certificate is proudly presented to</div>

        <div style="color:#1e3a2f; font-size:36px; font-weight:bold; margin:0 0 4px 0; letter-spacing:1px;">' . $donor_escaped . '</div>

        <div style="border-bottom:3px solid #c9a84c; width:180px; margin:0 auto 14px auto;"></div>

        <div style="color:#6b7280; font-size:14px; margin:10px 0 4px 0;">For donating</div>
        <div style="color:#2d5a3d; font-size:20px; font-weight:bold; margin:2px 0 4px 0;">' . $food_escaped . '</div>
        <div style="color:#6b7280; font-size:12px; margin:2px 0 14px 0;">through the Sayog Food Donation Platform</div>

        <div style="border-bottom:1px solid #c9a84c; width:60%; margin:10px auto;"></div>

        <div style="color:#059669; font-size:16px; font-weight:bold; margin:14px 0 2px 0; letter-spacing:3px; text-transform:uppercase;">Sayog</div>
        <div style="color:#9ca3af; font-size:10px; margin:0 0 14px 0;">Connecting surplus food with communities</div>

        <table border="0" cellpadding="4" cellspacing="0" style="width:80%; margin:0 auto; border-collapse:collapse;">
        <tr>
        <td style="text-align:center; width:33%;">
        <div style="font-size:9px; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;">Certificate No.</div>
        <div style="font-size:12px; color:#374151; font-weight:bold;">' . $certNumber . '</div>
        </td>
        <td style="text-align:center; width:33%;">
        <div style="font-size:9px; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;">Beneficiary</div>
        <div style="font-size:12px; color:#374151; font-weight:bold;">' . $rec_escaped . '</div>
        </td>
        <td style="text-align:center; width:33%;">
        <div style="font-size:9px; color:#9ca3af; text-transform:uppercase; letter-spacing:1px;">Date Issued</div>
        <div style="font-size:12px; color:#374151; font-weight:bold;">' . $dateStr . '</div>
        </td>
        </tr>
        </table>

        <div style="border-top:2px solid #8b6d2c; width:160px; margin:28px auto 2px auto;"></div>
        <div style="font-size:10px; color:#9ca3af; font-style:italic;">Authorized Signature &#8212; Sayog</div>

        <div style="font-size:7px; color:#9ca3af; margin-top:16px; padding-top:4px;">
        This certificate is auto-generated. Your contribution helps reduce food waste and supports communities in need.
        </div>

        <div style="text-align:center; color:#c9a84c; font-size:18px; letter-spacing:2px; margin-top:6px;">&#9670; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &#9670;</div>

        </div>
        </td></tr>
        </table>
        </td></tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filepath, 'F');

        // Save path in database
        $relPath = 'uploads/certificates/' . $filename;
        $stmt = $pdo->prepare("UPDATE donations SET certificate_path = ? WHERE id = ?");
        $stmt->execute([$relPath, $donation_id]);

        return $relPath;
    } catch (Exception $e) {
        // Silently fail — certificate generation should not block the main flow
        return null;
    }
}







// ═════════════════════════════════════════════════════════════════
// NEPAL MAP GEOCODING FUNCTION
// ═════════════════════════════════════════════════════════════════

/**
 * Geocode a Nepal address using Nominatim (OpenStreetMap).
 * Falls back to known Nepal city coordinates if API fails.
 * Returns ['lat' => float, 'lng' => float, 'city' => string|null] or null.
 */
function geocode_address_nepal($address) {
    if (empty(trim($address))) return null;

    // Known Nepal city coordinates (fast fallback)
    $nepal_cities = [
        'kathmandu' => ['lat' => 27.7172, 'lng' => 85.3240],
        'lalitpur' => ['lat' => 27.6588, 'lng' => 85.3247],
        'patan' => ['lat' => 27.6588, 'lng' => 85.3247],
        'bhaktapur' => ['lat' => 27.6710, 'lng' => 85.4298],
        'pokhara' => ['lat' => 28.2096, 'lng' => 83.9856],
        'bharatpur' => ['lat' => 27.6833, 'lng' => 84.4333],
        'birgunj' => ['lat' => 27.0170, 'lng' => 84.8660],
        'biratnagar' => ['lat' => 26.4524, 'lng' => 87.2718],
        'janakpur' => ['lat' => 26.7288, 'lng' => 85.9248],
        'ghorahi' => ['lat' => 28.0330, 'lng' => 82.4830],
        'hetauda' => ['lat' => 27.4167, 'lng' => 85.0333],
        'dhangadhi' => ['lat' => 28.6833, 'lng' => 80.6000],
        'tulsipur' => ['lat' => 28.1333, 'lng' => 82.3000],
        'itahari' => ['lat' => 26.6667, 'lng' => 87.2667],
        'nepalgunj' => ['lat' => 28.0500, 'lng' => 81.6167],
        'butwal' => ['lat' => 27.6833, 'lng' => 83.4500],
        'dharan' => ['lat' => 26.8167, 'lng' => 87.2833],
        'kalaiya' => ['lat' => 27.0333, 'lng' => 85.0000],
        'jaleshwar' => ['lat' => 26.6500, 'lng' => 85.8000],
        'kamalamai' => ['lat' => 27.1500, 'lng' => 86.2333],
        'gorkha' => ['lat' => 28.0000, 'lng' => 84.6333],
        'baglung' => ['lat' => 28.2667, 'lng' => 83.6000],
        'tansen' => ['lat' => 27.8667, 'lng' => 83.5500],
        'banepa' => ['lat' => 27.6333, 'lng' => 85.5167],
        'dhulikhel' => ['lat' => 27.6167, 'lng' => 85.5500],
        'kirtipur' => ['lat' => 27.6786, 'lng' => 85.2774],
        'chitwan' => ['lat' => 27.5333, 'lng' => 84.3333],
        'bhairahawa' => ['lat' => 27.5000, 'lng' => 83.4500],
        'siddharthanagar' => ['lat' => 27.5000, 'lng' => 83.4500],
        'lekhnath' => ['lat' => 28.1833, 'lng' => 84.0000],
        'damak' => ['lat' => 26.6667, 'lng' => 87.6833],
        'trl' => ['lat' => 27.3167, 'lng' => 85.2833],
    ];

    $address_lower = strtolower(trim($address));

    // Check for known city names in the address
    foreach ($nepal_cities as $city => $coords) {
        if (strpos($address_lower, $city) !== false) {
            return ['lat' => $coords['lat'], 'lng' => $coords['lng'], 'city' => ucfirst($city)];
        }
    }

    // Try Nominatim API
    $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($address . ', Nepal') . '&limit=1';
    $ctx = stream_context_create(['http' => ['timeout' => 3, 'header' => 'User-Agent: Sayog/1.0\r\n']]);
    $result = @file_get_contents($url, false, $ctx);

    if ($result) {
        $data = json_decode($result, true);
        if (!empty($data[0])) {
            $lat = (float)$data[0]['lat'];
            $lng = (float)$data[0]['lon'];
            // Extract city from display name
            $parts = explode(',', $data[0]['display_name']);
            $city = trim($parts[0] ?? '');
            return ['lat' => $lat, 'lng' => $lng, 'city' => $city ?: null];
        }
    }

    // Final fallback: default to Kathmandu
    return ['lat' => 27.7172, 'lng' => 85.3240, 'city' => 'Kathmandu'];
}
// ═════════════════════════════════════════════════════════════════
// VOLUNTEER MODULE HELPER FUNCTIONS
// ═════════════════════════════════════════════════════════════════

/**
 * Check if a user has a volunteer record with a specific status.
 */
function get_volunteer_status($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT status, volunteer_id, rating, completed_deliveries, community_points, online_status, delivery_radius, vehicle_type, availability, profile_photo, full_name, phone FROM volunteers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Check if a user is an approved volunteer.
 */
function is_volunteer($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM volunteers WHERE user_id = ? AND status = 'approved'");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetch();
}

/**
 * Check if a user has a pending volunteer application.
 */
function has_pending_volunteer_application($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM volunteers WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetch();
}

/**
 * Get full volunteer details by user ID.
 */
function get_volunteer_details($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT v.*, u.name AS user_name, u.email AS user_email, u.address AS user_address, u.phone AS user_phone, u.created_at AS user_since FROM volunteers v JOIN users u ON v.user_id = u.id WHERE v.user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get volunteer details by volunteer record ID.
 */
function get_volunteer_by_id($pdo, $volunteer_id) {
    $stmt = $pdo->prepare("SELECT v.*, u.name AS user_name, u.email AS user_email FROM volunteers v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
    $stmt->execute([$volunteer_id]);
    return $stmt->fetch();
}

/**
 * Get all volunteers by status.
 */
function get_volunteers_by_status($pdo, $status) {
    $stmt = $pdo->prepare("SELECT v.*, u.name AS user_name, u.email AS user_email FROM volunteers v JOIN users u ON v.user_id = u.id WHERE v.status = ? ORDER BY v.created_at DESC");
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

/**
 * Get volunteer counts for admin dashboard.
 */
/**
 * Create a volunteer delivery record after consumer selects volunteer delivery.
 */
function create_volunteer_delivery($pdo, $donation_id, $request_id, $consumer_id, $donor_id) {
    // Check if delivery already exists
    $stmt = $pdo->prepare("SELECT id FROM volunteer_deliveries WHERE donation_id = ? AND request_id = ?");
    $stmt->execute([$donation_id, $request_id]);
    if ($stmt->fetch()) return false;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO volunteer_deliveries (donation_id, request_id, consumer_id, donor_id, status) VALUES (?, ?, ?, ?, 'assigned')");
        $stmt->execute([$donation_id, $request_id, $consumer_id, $donor_id]);
        $delivery_id = $pdo->lastInsertId();

        // AUTO-ASSIGN: Find the best matching volunteer and assign them
        $volunteer = find_matching_volunteers($pdo, $donation_id, 1);
        if (!empty($volunteer)) {
            $best = $volunteer[0];
            $assignStmt = $pdo->prepare("UPDATE volunteer_deliveries SET volunteer_user_id = ?, status = 'accepted', accepted_at = NOW(), assignment_method = 'auto' WHERE id = ? AND volunteer_user_id IS NULL");
            $assignStmt->execute([$best['user_id'], $delivery_id]);

            // Notify the volunteer
            $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
            $dStmt->execute([$donation_id]);
            $food = $dStmt->fetchColumn();

            create_notification($pdo, $best['user_id'], 'delivery_auto_assigned',
                '🎯 New delivery auto-assigned to you! Please pick up "' . $food . '" and deliver it to the requester. Check your active deliveries.',
                'dashboard.php?page=volunteer', true);

            // Notify donor
            create_notification($pdo, $donor_id, 'volunteer_assigned',
                'A volunteer (' . htmlspecialchars($best['full_name']) . ') has been auto-assigned to deliver "' . $food . '". They will contact you soon.',
                'dashboard.php?page=manage-donation', true);

            // Notify consumer
            create_notification($pdo, $consumer_id, 'volunteer_assigned',
                '🎉 A volunteer (' . htmlspecialchars($best['full_name']) . ') has been assigned to deliver "' . $food . '" to you!',
                'dashboard.php?page=track-request', true);
        } else {
            // No volunteers available — notify admin and donor
            $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
            $dStmt->execute([$donation_id]);
            $food = $dStmt->fetchColumn();

            create_notification($pdo, $donor_id, 'delivery_needed',
                'A volunteer delivery was requested for "' . $food . '" but no volunteers are currently available. The delivery is open for volunteers to accept.',
                'dashboard.php?page=manage-donation', true);

            create_notification($pdo, $consumer_id, 'delivery_needed',
                'A volunteer delivery was requested for "' . $food . '". No volunteers are available right now, but the delivery is listed for volunteers to pick up.',
                'dashboard.php?page=track-request', true);
        }

        $pdo->commit();
        return $delivery_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// ──────────────────────────────────────────────────────────────
// NEW: AUTO VOLUNTEER MATCHING & REJECTION/REASSIGNMENT
// ──────────────────────────────────────────────────────────────

/**
 * Find matching available volunteers for a donation based on proximity and availability.
 * Returns top-N volunteers sorted by best match.
 */
function find_matching_volunteers($pdo, $donation_id, $limit = 5) {
    // Get donation location
    $dStmt = $pdo->prepare("SELECT pickup_address, city, latitude, longitude FROM donations WHERE id = ?");
    $dStmt->execute([$donation_id]);
    $donation = $dStmt->fetch();
    if (!$donation) return [];

    $donationTokens = tokenize_address($donation['pickup_address'] ?? '');

    // Find ALL approved, available volunteers ordered by:
    // 1. Currently online (available > busy > offline)
    // 2. Higher rating
    // 3. More completed deliveries
    // 4. Location proximity (token match)
    $stmt = $pdo->prepare("
        SELECT v.*, u.name AS user_name 
        FROM volunteers v 
        JOIN users u ON v.user_id = u.id 
        WHERE v.status = 'approved'
          AND v.online_status != 'offline'
        ORDER BY 
          FIELD(v.online_status, 'available', 'busy') ASC,
          v.rating DESC,
          v.completed_deliveries DESC,
          v.delivery_radius DESC
        LIMIT ?
    ");
    $limit = (int)$limit;
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $volunteers = $stmt->fetchAll();

    if (empty($volunteers)) return [];

    // Score each volunteer by location proximity
    $scored = [];
    foreach ($volunteers as $vol) {
        $score = 0;

        // Location proximity (token matching)
        $volTokens = tokenize_address($vol['address'] ?? '');
        if (!empty($donationTokens)) {
            $common = array_intersect($donationTokens, $volTokens);
            $score += count($common) * 10;
        }

        // Online status bonus
        if ($vol['online_status'] === 'available') $score += 30;
        elseif ($vol['online_status'] === 'busy') $score += 10;

        // Rating bonus
        $score += (float)$vol['rating'] * 5;

        // Experience bonus
        $score += (int)$vol['completed_deliveries'] * 3;

        // Radius bonus (higher radius = more willing to travel)
        $score += (int)$vol['delivery_radius'] * 2;

        $scored[] = ['volunteer' => $vol, 'score' => $score];
    }

    // Sort by score descending
    usort($scored, function ($a, $b) {
        return $b['score'] - $a['score'];
    });

    return array_map(function ($s) { return $s['volunteer']; }, $scored);
}

/**
 * Auto-assign the best matching volunteer to a delivery.
 * Called automatically after create_volunteer_delivery() and after volunteer rejection.
 * Returns the assigned volunteer user_id or null if none found.
 */
function auto_assign_volunteer($pdo, $delivery_id) {
    // Get delivery info
    $stmt = $pdo->prepare("SELECT * FROM volunteer_deliveries WHERE id = ? AND volunteer_user_id IS NULL");
    $stmt->execute([$delivery_id]);
    $delivery = $stmt->fetch();
    if (!$delivery) return null;

    // Find best matching volunteer
    $volunteers = find_matching_volunteers($pdo, $delivery['donation_id'], 3);
    if (empty($volunteers)) return null;

    $best = $volunteers[0];

    // Assign the best volunteer
    $assignStmt = $pdo->prepare("UPDATE volunteer_deliveries SET volunteer_user_id = ?, status = 'accepted', accepted_at = NOW(), assignment_method = 'auto' WHERE id = ? AND volunteer_user_id IS NULL");
    $assignStmt->execute([$best['user_id'], $delivery_id]);
    if ($assignStmt->rowCount() === 0) return null;

    // Notifications
    $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
    $dStmt->execute([$delivery['donation_id']]);
    $food = $dStmt->fetchColumn();

    create_notification($pdo, $best['user_id'], 'delivery_auto_assigned',
        '🎯 New delivery auto-assigned! Please deliver "' . $food . '". Check your active deliveries.',
        'dashboard.php?page=volunteer', true);

    create_notification($pdo, $delivery['donor_id'], 'volunteer_assigned',
        'A volunteer (' . htmlspecialchars($best['full_name']) . ') has been auto-assigned to deliver "' . $food . '".',
        'dashboard.php?page=manage-donation', true);

    create_notification($pdo, $delivery['consumer_id'], 'volunteer_assigned',
        '🎉 A volunteer (' . htmlspecialchars($best['full_name']) . ') has been assigned to deliver "' . $food . '"!',
        'dashboard.php?page=track-request', true);

    return $best['user_id'];
}

/**
 * Volunteer rejects/declines a delivery. Records reason and triggers auto-reassignment.
 */
function reject_volunteer_delivery($pdo, $delivery_id, $volunteer_user_id, $reason = '') {
    $stmt = $pdo->prepare("SELECT * FROM volunteer_deliveries WHERE id = ? AND volunteer_user_id = ? AND status IN ('assigned', 'accepted')");
    $stmt->execute([$delivery_id, $volunteer_user_id]);
    $delivery = $stmt->fetch();
    if (!$delivery) return false;

    $pdo->beginTransaction();
    try {
        // Cancel this assignment
        $stmt = $pdo->prepare("UPDATE volunteer_deliveries SET status = 'cancelled', cancelled_at = NOW(), cancellation_reason = ? WHERE id = ?");
        $stmt->execute([$reason ?: 'Volunteer declined', $delivery_id]);

        $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
        $dStmt->execute([$delivery['donation_id']]);
        $food = $dStmt->fetchColumn();

        $vStmt = $pdo->prepare("SELECT full_name FROM volunteers WHERE user_id = ?");
        $vStmt->execute([$volunteer_user_id]);
        $volName = $vStmt->fetchColumn() ?: 'A volunteer';

        create_notification($pdo, $delivery['donor_id'], 'delivery_rejected',
            $volName . ' has declined the delivery for "' . $food . '". The system will try to reassign.',
            'dashboard.php?page=manage-donation', true);

        create_notification($pdo, $delivery['consumer_id'], 'delivery_rejected',
            'The volunteer declined delivery for "' . $food . '". We are finding another volunteer.',
            'dashboard.php?page=track-request', true);

        // Create a NEW delivery record for reassignment (marked as reassigned)
        $stmt = $pdo->prepare("INSERT INTO volunteer_deliveries (donation_id, request_id, consumer_id, donor_id, status, assignment_method) VALUES (?, ?, ?, ?, 'assigned', 'reassigned')");
        $stmt->execute([$delivery['donation_id'], $delivery['request_id'], $delivery['consumer_id'], $delivery['donor_id']]);
        $new_delivery_id = $pdo->lastInsertId();

        $pdo->commit();

        // AUTO-REASSIGNMENT: Try to find another volunteer
        $reassigned = auto_assign_volunteer($pdo, $new_delivery_id);
        if ($reassigned) {
            create_notification($pdo, $delivery['donor_id'], 'delivery_reassigned',
                'A new volunteer has been assigned to deliver "' . $food . '".',
                'dashboard.php?page=manage-donation', true);
            create_notification($pdo, $delivery['consumer_id'], 'delivery_reassigned',
                '🎉 A new volunteer has been assigned to deliver "' . $food . '"!',
                'dashboard.php?page=track-request', true);
        } else {
            create_notification($pdo, $delivery['donor_id'], 'delivery_unassigned',
                'No volunteers are currently available for "' . $food . '". The delivery is open for acceptance.',
                'dashboard.php?page=manage-donation', true);
            create_notification($pdo, $delivery['consumer_id'], 'delivery_unassigned',
                'No volunteers are currently available for "' . $food . '". The delivery is open for volunteers.',
                'dashboard.php?page=track-request', true);
        }

        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Admin manually assigns a volunteer to a delivery.
 */
function admin_assign_volunteer_to_delivery($pdo, $delivery_id, $volunteer_user_id) {
    $stmt = $pdo->prepare("SELECT * FROM volunteer_deliveries WHERE id = ? AND status = 'assigned'");
    $stmt->execute([$delivery_id]);
    $delivery = $stmt->fetch();
    if (!$delivery) return false;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE volunteer_deliveries SET volunteer_user_id = ?, status = 'accepted', accepted_at = NOW(), assignment_method = 'admin_assign' WHERE id = ?");
        $stmt->execute([$volunteer_user_id, $delivery_id]);

        $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
        $dStmt->execute([$delivery['donation_id']]);
        $food = $dStmt->fetchColumn();

        $vStmt = $pdo->prepare("SELECT full_name FROM volunteers WHERE user_id = ?");
        $vStmt->execute([$volunteer_user_id]);
        $volName = $vStmt->fetchColumn() ?: 'A volunteer';

        create_notification($pdo, $volunteer_user_id, 'delivery_admin_assigned',
            '🔔 You have been assigned by admin to deliver "' . $food . '". Please check your active deliveries.',
            'dashboard.php?page=volunteer', true);

        create_notification($pdo, $delivery['donor_id'], 'delivery_admin_assigned',
            'Admin assigned ' . $volName . ' to deliver "' . $food . '".',
            'dashboard.php?page=manage-donation', true);

        create_notification($pdo, $delivery['consumer_id'], 'delivery_admin_assigned',
            '🔔 ' . $volName . ' has been assigned by admin to deliver "' . $food . '"!',
            'dashboard.php?page=track-request', true);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Admin unassigns a volunteer from a delivery, resetting it to available.
 */
function admin_unassign_volunteer_from_delivery($pdo, $delivery_id) {
    $stmt = $pdo->prepare("SELECT * FROM volunteer_deliveries WHERE id = ? AND status IN ('accepted', 'assigned', 'picked_up')");
    $stmt->execute([$delivery_id]);
    $delivery = $stmt->fetch();
    if (!$delivery || !$delivery['volunteer_user_id']) return false;

    $pdo->beginTransaction();
    try {
        $oldVolunteerId = $delivery['volunteer_user_id'];

        $stmt = $pdo->prepare("UPDATE volunteer_deliveries SET volunteer_user_id = NULL, status = 'assigned', accepted_at = NULL WHERE id = ?");
        $stmt->execute([$delivery_id]);

        $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
        $dStmt->execute([$delivery['donation_id']]);
        $food = $dStmt->fetchColumn();

        create_notification($pdo, $oldVolunteerId, 'delivery_unassigned',
            'You have been unassigned from delivery of "' . $food . '" by admin.',
            'dashboard.php?page=volunteer', true);

        create_notification($pdo, $delivery['donor_id'], 'delivery_unassigned',
            'The volunteer for "' . $food . '" has been unassigned by admin. The delivery is open again.',
            'dashboard.php?page=manage-donation', true);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Get available deliveries for a volunteer within their delivery radius.
 * Filters by: approved status, online=available, within radius, donation not expired.
 */
function get_available_deliveries_for_volunteer($pdo, $volunteer_user_id) {
    $vol = get_volunteer_details($pdo, $volunteer_user_id);
    if (!$vol || $vol['status'] !== 'approved' || $vol['online_status'] !== 'available') return [];

    $radius = (int)$vol['delivery_radius'];
    $volAddress = strtolower($vol['address'] ?? '');
    $volTokens = tokenize_address($volAddress);

    $stmt = $pdo->prepare("
        SELECT vd.*, d.food_item, d.quantity, d.pickup_address, d.phone AS donor_phone,
               d.city, d.latitude, d.longitude, d.expiry_time,
               u.name AS donor_name,
               r.quantity_requested, r.message AS request_message
        FROM volunteer_deliveries vd
        JOIN donations d ON vd.donation_id = d.id
        JOIN users u ON vd.donor_id = u.id
        JOIN requests r ON vd.request_id = r.id
        WHERE vd.status = 'assigned'
          AND vd.volunteer_user_id IS NULL
          AND d.expiry_time > NOW()
          AND d.status IN ('accepted')
        ORDER BY d.created_at DESC
    ");
    $stmt->execute();
    $all = $stmt->fetchAll();

    if (empty($volTokens) || $radius <= 0) return $all;

    // Filter by location proximity (token matching)
    $filtered = [];
    foreach ($all as $del) {
        $pickupTokens = tokenize_address($del['pickup_address']);
        $common = array_intersect($volTokens, $pickupTokens);
        if (!empty($common)) {
            $del['_proximity_score'] = count($common);
            $filtered[] = $del;
        }
    }
    return !empty($filtered) ? $filtered : $all;
}

/**
 * Get active deliveries for a specific volunteer.
 */
function get_volunteer_active_deliveries($pdo, $volunteer_user_id) {
    $stmt = $pdo->prepare("
        SELECT vd.*, d.food_item, d.quantity, d.pickup_address, d.phone AS donor_phone,
               d.city, d.latitude, d.longitude,
               u.name AS donor_name, u.phone AS donor_contact,
               cu.name AS consumer_name, cu.phone AS consumer_phone, cu.address AS consumer_address
        FROM volunteer_deliveries vd
        JOIN donations d ON vd.donation_id = d.id
        JOIN users u ON vd.donor_id = u.id
        JOIN users cu ON vd.consumer_id = cu.id
        WHERE vd.volunteer_user_id = ?
          AND vd.status IN ('accepted','picked_up','in_transit')
        ORDER BY vd.updated_at DESC
    ");
    $stmt->execute([$volunteer_user_id]);
    return $stmt->fetchAll();
}

/**
 * Get delivery history for a volunteer.
 */
function get_volunteer_delivery_history($pdo, $volunteer_user_id, $limit = 20) {
    $stmt = $pdo->prepare("
        SELECT vd.*, d.food_item, d.quantity, d.pickup_address,
               u.name AS donor_name, cu.name AS consumer_name
        FROM volunteer_deliveries vd
        JOIN donations d ON vd.donation_id = d.id
        JOIN users u ON vd.donor_id = u.id
        JOIN users cu ON vd.consumer_id = cu.id
        WHERE vd.volunteer_user_id = ?
          AND vd.status IN ('delivered','cancelled')
        ORDER BY vd.updated_at DESC LIMIT ?
    ");
    $limit = (int)$limit;
    $stmt->bindValue(1, $volunteer_user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Accept a delivery as a volunteer.
 */
function accept_volunteer_delivery($pdo, $delivery_id, $volunteer_user_id) {
    $stmt = $pdo->prepare("SELECT * FROM volunteer_deliveries WHERE id = ? AND status = 'assigned' AND volunteer_user_id IS NULL");
    $stmt->execute([$delivery_id]);
    $delivery = $stmt->fetch();
    if (!$delivery) return false;

    // Prevent race condition: UPDATE checks volunteer_user_id IS NULL, so only one volunteer can accept
    $stmt = $pdo->prepare("UPDATE volunteer_deliveries SET volunteer_user_id = ?, status = 'accepted', accepted_at = NOW(), assignment_method = 'manual_accept' WHERE id = ? AND status = 'assigned' AND volunteer_user_id IS NULL");
    $stmt->execute([$volunteer_user_id, $delivery_id]);
    if ($stmt->rowCount() === 0) {
        return false;
    }

    // Notify donor
    $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
    $dStmt->execute([$delivery['donation_id']]);
    $food = $dStmt->fetchColumn();

    $vStmt = $pdo->prepare("SELECT full_name FROM volunteers WHERE user_id = ?");
    $vStmt->execute([$volunteer_user_id]);
    $volName = $vStmt->fetchColumn() ?: 'A volunteer';

    create_notification($pdo, $delivery['donor_id'], 'delivery_accepted',
        $volName . ' has accepted the delivery for "' . $food . '". They will contact you for pickup.',
        'dashboard.php?page=manage-donation', true);
    create_notification($pdo, $delivery['consumer_id'], 'delivery_accepted',
        $volName . ' has accepted the delivery for "' . $food . '". They will deliver to you.',
        'dashboard.php?page=track-request', true);

    return true;
}

/**
 * Update volunteer delivery status (picked_up, in_transit, delivered).
 */
function update_delivery_status($pdo, $delivery_id, $volunteer_user_id, $new_status, $notes = null) {
    $valid = ['picked_up', 'in_transit', 'delivered'];
    if (!in_array($new_status, $valid)) return false;

    $stmt = $pdo->prepare("SELECT * FROM volunteer_deliveries WHERE id = ? AND volunteer_user_id = ?");
    $stmt->execute([$delivery_id, $volunteer_user_id]);
    $delivery = $stmt->fetch();
    if (!$delivery) return false;

    $timeCol = $new_status . '_at';
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE volunteer_deliveries SET status = ?, $timeCol = NOW(), delivery_notes = COALESCE(?, delivery_notes) WHERE id = ?");
    $stmt->execute([$new_status, $notes, $delivery_id]);

    $dStmt = $pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
    $dStmt->execute([$delivery['donation_id']]);
    $food = $dStmt->fetchColumn();

    if ($new_status === 'picked_up') {
        create_notification($pdo, $delivery['donor_id'], 'delivery_picked_up',
            'Volunteer has picked up "' . $food . '" and is on the way.',
            'dashboard.php?page=manage-donation', true);
        create_notification($pdo, $delivery['consumer_id'], 'delivery_picked_up',
            'Your food "' . $food . '" has been picked up by the volunteer!',
            'dashboard.php?page=track-request', true);
    } elseif ($new_status === 'in_transit') {
        create_notification($pdo, $delivery['consumer_id'], 'delivery_in_transit',
            'Your food "' . $food . '" is in transit! The volunteer is on their way.',
            'dashboard.php?page=track-request', true);
    } elseif ($new_status === 'delivered') {
        // Mark donation as completed
        $pdo->prepare("UPDATE donations SET status = 'completed' WHERE id = ?")->execute([$delivery['donation_id']]);
        $pdo->prepare("UPDATE requests SET status = 'completed' WHERE id = ?")->execute([$delivery['request_id']]);

        // Update volunteer stats
        $pdo->prepare("UPDATE volunteers SET completed_deliveries = completed_deliveries + 1, community_points = community_points + 10 WHERE user_id = ?")->execute([$volunteer_user_id]);

        create_notification($pdo, $delivery['donor_id'], 'delivery_completed',
            'Your donation "' . $food . '" has been delivered by the volunteer! Please rate the receiver.',
            'dashboard.php?page=track-donation&rate_donation=' . $delivery['donation_id'], true);
        create_notification($pdo, $delivery['consumer_id'], 'delivery_completed',
            'Your food "' . $food . '" has been delivered! Please confirm and rate the donor.',
            'dashboard.php?page=track-request&rate_donation=' . $delivery['donation_id'], true);

        // Generate certificate
        $dDonor = $pdo->prepare("SELECT u.name FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.id = ?");
        $dDonor->execute([$delivery['donation_id']]);
        $donorName = $dDonor->fetchColumn() ?: 'Donor';
        $dRec = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $dRec->execute([$delivery['consumer_id']]);
        $recName = $dRec->fetchColumn() ?: 'Community';
        generate_donation_certificate($pdo, $delivery['donation_id'], $donorName, $food, $recName);
    }

    $pdo->commit();
    return true;
}

/**
 * Get delivery by donation ID.
 */
function get_delivery_by_donation($pdo, $donation_id) {
    $stmt = $pdo->prepare("SELECT * FROM volunteer_deliveries WHERE donation_id = ?");
    $stmt->execute([$donation_id]);
    return $stmt->fetch();
}

/**
 * Get all volunteer deliveries (for admin).
 */
function get_all_volunteer_deliveries($pdo, $status = null, $limit = 50) {
    $limit = (int)$limit;
    $sql = "
        SELECT vd.*, d.food_item, d.quantity, d.pickup_address,
               u.name AS donor_name, cu.name AS consumer_name,
               vu.name AS volunteer_name, vol.vehicle_type
        FROM volunteer_deliveries vd
        JOIN donations d ON vd.donation_id = d.id
        JOIN users u ON vd.donor_id = u.id
        JOIN users cu ON vd.consumer_id = cu.id
        LEFT JOIN users vu ON vd.volunteer_user_id = vu.id
        LEFT JOIN volunteers vol ON vd.volunteer_user_id = vol.user_id
    ";
    if ($status && in_array($status, ['assigned','accepted','picked_up','in_transit','delivered','cancelled'])) {
        $sql .= " WHERE vd.status = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY vd.created_at DESC LIMIT ?");
        $stmt->bindValue(1, $status, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare($sql . " ORDER BY vd.created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Get comprehensive volunteer delivery activity statistics for the admin dashboard.
 * Returns counts for each delivery status plus volunteer performance metrics.
 */
function get_volunteer_activity_stats($pdo) {
    $stats = [];
    foreach (['assigned','accepted','picked_up','in_transit','delivered','cancelled'] as $status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM volunteer_deliveries WHERE status = ?");
        $stmt->execute([$status]);
        $stats[$status] = (int)$stmt->fetchColumn();
    }
    $stats['total'] = array_sum($stats);
    $stats['active'] = $stats['accepted'] + $stats['picked_up'] + $stats['in_transit'];
    
    // Top volunteers by completed deliveries
    $stmt = $pdo->query("
        SELECT vu.name AS volunteer_name, COUNT(vd.id) AS completed_count
        FROM volunteer_deliveries vd
        JOIN users vu ON vd.volunteer_user_id = vu.id
        WHERE vd.status = 'delivered'
        GROUP BY vd.volunteer_user_id
        ORDER BY completed_count DESC
        LIMIT 5
    ");
    $stats['top_volunteers'] = $stmt->fetchAll();
    
    // Recent activity (last 10 events)
    $stmt = $pdo->query("
        SELECT vd.id, vd.status, vd.updated_at, d.food_item,
               vu.name AS volunteer_name, u.name AS donor_name, cu.name AS consumer_name
        FROM volunteer_deliveries vd
        JOIN donations d ON vd.donation_id = d.id
        LEFT JOIN users vu ON vd.volunteer_user_id = vu.id
        JOIN users u ON vd.donor_id = u.id
        JOIN users cu ON vd.consumer_id = cu.id
        ORDER BY vd.updated_at DESC
        LIMIT 10
    ");
    $stats['recent_activity'] = $stmt->fetchAll();
    
    return $stats;
}

/**
 * Comprehensive Delivery Matching Analytics.
 * Returns auto vs manual counts, avg response times, volunteer utilization, and trends.
 */
function get_delivery_matching_analytics($pdo) {
    $analytics = [];

    // ── 1. Assignment Method Distribution ──
    $methods = ['auto', 'manual_accept', 'admin_assign', 'reassigned'];
    $assignCounts = [];
    foreach ($methods as $m) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM volunteer_deliveries WHERE assignment_method = ?");
        $stmt->execute([$m]);
        $assignCounts[$m] = (int)$stmt->fetchColumn();
    }
    // Also count null/legacy records (set before tracking was added)
    $stmt = $pdo->query("SELECT COUNT(*) FROM volunteer_deliveries WHERE assignment_method IS NULL");
    $assignCounts['legacy'] = (int)$stmt->fetchColumn();
    $assignCounts['total_tracked'] = (int)$assignCounts['auto'] + (int)$assignCounts['manual_accept'] + (int)$assignCounts['admin_assign'] + (int)$assignCounts['reassigned'];
    $analytics['assignment_methods'] = $assignCounts;

    // ── 2. Average Response Time (time from 'assigned' → 'accepted' in minutes) ──
    $stmt = $pdo->query("
        SELECT 
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, accepted_at)), 1) AS avg_response_mins,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, picked_up_at)), 1) AS avg_pickup_mins,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_at)), 1) AS avg_delivery_mins,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, accepted_at, delivered_at)), 1) AS avg_transit_mins
        FROM volunteer_deliveries 
        WHERE accepted_at IS NOT NULL 
          AND accepted_at IS NOT NULL
    ");
    $analytics['response_times'] = $stmt->fetch();

    // Average response time by assignment method
    $respByMethod = [];
    foreach (['auto', 'manual_accept', 'admin_assign'] as $m) {
        $stmt = $pdo->prepare("
            SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, accepted_at)), 1) AS avg_response 
            FROM volunteer_deliveries 
            WHERE assignment_method = ? AND accepted_at IS NOT NULL
        ");
        $stmt->execute([$m]);
        $val = $stmt->fetchColumn();
        $respByMethod[$m] = $val ? (float)$val : 0;
    }
    $analytics['response_by_method'] = $respByMethod;

    // ── 3. Volunteer Utilization Rates ──
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT v.user_id) AS total_volunteers,
            COUNT(DISTINCT CASE WHEN vd.id IS NOT NULL THEN v.user_id END) AS active_volunteers,
            COUNT(DISTINCT CASE WHEN v.online_status = 'available' THEN v.user_id END) AS available_now,
            COUNT(DISTINCT CASE WHEN v.online_status = 'busy' THEN v.user_id END) AS busy_now,
            ROUND(
                COUNT(DISTINCT CASE WHEN vd.id IS NOT NULL THEN v.user_id END) 
                / NULLIF(COUNT(DISTINCT v.user_id), 0) * 100, 1
            ) AS utilization_pct
        FROM volunteers v
        LEFT JOIN volunteer_deliveries vd ON v.user_id = vd.volunteer_user_id AND vd.status IN ('accepted', 'picked_up', 'in_transit', 'delivered')
        WHERE v.status = 'approved'
    ");
    $analytics['volunteer_utilization'] = $stmt->fetch();

    // ── 4. Top performing volunteers ──
    $stmt = $pdo->query("
        SELECT 
            vu.name AS volunteer_name,
            v.user_id,
            v.rating,
            v.completed_deliveries,
            v.community_points,
            v.delivery_radius,
            v.vehicle_type,
            v.online_status,
            COUNT(vd.id) AS total_assigned,
            SUM(CASE WHEN vd.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, vd.accepted_at, vd.delivered_at)), 1) AS avg_delivery_time
        FROM volunteers v
        JOIN users vu ON v.user_id = vu.id
        LEFT JOIN volunteer_deliveries vd ON v.user_id = vd.volunteer_user_id
        WHERE v.status = 'approved'
        GROUP BY v.user_id
        ORDER BY v.completed_deliveries DESC, v.rating DESC
        LIMIT 10
    ");
    $analytics['top_volunteers'] = $stmt->fetchAll();

    // ── 5. Daily/Weekly Delivery Trends (last 14 days) ──
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) AS date,
            COUNT(*) AS total_created,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN assignment_method = 'auto' THEN 1 ELSE 0 END) AS auto_assigned,
            SUM(CASE WHEN assignment_method = 'manual_accept' THEN 1 ELSE 0 END) AS manual_accepted
        FROM volunteer_deliveries
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $analytics['daily_trends'] = $stmt->fetchAll();

    // ── 6. Completion rate (only terminal statuses: delivered or cancelled) ──
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
            ROUND(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) 
                / NULLIF(SUM(CASE WHEN status IN ('delivered', 'cancelled') THEN 1 ELSE 0 END), 0) * 100, 1) AS completion_rate
        FROM volunteer_deliveries
        WHERE status IN ('delivered', 'cancelled')
    ");
    $analytics['completion'] = $stmt->fetch();
    $analytics['completion']['total'] = (int)($analytics['completion']['completed'] ?? 0) + (int)($analytics['completion']['cancelled_count'] ?? 0);

    // ── 7. Top rejection reasons ──
    $stmt = $pdo->query("
        SELECT cancellation_reason, COUNT(*) AS count
        FROM volunteer_deliveries 
        WHERE cancellation_reason IS NOT NULL AND cancellation_reason != ''
        GROUP BY cancellation_reason
        ORDER BY count DESC
        LIMIT 5
    ");
    $analytics['rejection_reasons'] = $stmt->fetchAll();

    // ── 8. Average deliveries per volunteer ──
    $stmt = $pdo->query("
        SELECT 
            ROUND(AVG(delivery_count), 1) AS avg_per_volunteer,
            MAX(delivery_count) AS max_deliveries
        FROM (
            SELECT v.user_id, COUNT(vd.id) AS delivery_count
            FROM volunteers v
            LEFT JOIN volunteer_deliveries vd ON v.user_id = vd.volunteer_user_id
            WHERE v.status = 'approved'
            GROUP BY v.user_id
        ) counts
    ");
    $analytics['avg_deliveries_per_volunteer'] = $stmt->fetch();

    return $analytics;
}

/**
 * Get count of volunteers by status.
 */
function get_volunteer_counts($pdo) {
    $counts = [];
    foreach (['pending', 'approved', 'rejected', 'suspended'] as $status) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM volunteers WHERE status = ?");
        $stmt->execute([$status]);
        $counts[$status] = (int)$stmt->fetchColumn();
    }
    $stmt = $pdo->query("SELECT COUNT(*) FROM volunteers");
    $counts['total'] = (int)$stmt->fetchColumn();
    return $counts;
}

/**
 * Generate a unique Volunteer ID.
 */
function generate_volunteer_id($pdo) {
    $prefix = 'SV';
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'approved'");
    $count = (int)$stmt->fetchColumn() + 1;
    return $prefix . $year . str_pad($count, 5, '0', STR_PAD_LEFT);
}

/**
 * Approve a volunteer application.
 */
function approve_volunteer($pdo, $volunteer_id, $admin_id) {
    $vid = generate_volunteer_id($pdo);
    $stmt = $pdo->prepare("UPDATE volunteers SET status = 'approved', verified_by = ?, approved_at = NOW(), volunteer_id = ? WHERE id = ?");
    $stmt->execute([$admin_id, $vid, $volunteer_id]);
    
    // Get user_id to send notification
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM volunteers WHERE id = ?");
    $stmt->execute([$volunteer_id]);
    $vol = $stmt->fetch();
    
    if ($vol) {
        create_notification($pdo, $vol['user_id'], 'volunteer_approved',
            'Congratulations ' . $vol['full_name'] . '! Your volunteer application has been approved. Your Volunteer ID is ' . $vid . '. You can now access the volunteer dashboard and accept delivery requests.',
            'dashboard.php?page=volunteer', true);
    }
    
    return $vid;
}

/**
 * Reject a volunteer application.
 */
function reject_volunteer($pdo, $volunteer_id, $reason) {
    $stmt = $pdo->prepare("UPDATE volunteers SET status = 'rejected', rejected_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $volunteer_id]);
    
    // Notify user
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM volunteers WHERE id = ?");
    $stmt->execute([$volunteer_id]);
    $vol = $stmt->fetch();
    
    if ($vol) {
        create_notification($pdo, $vol['user_id'], 'volunteer_rejected',
            'Dear ' . $vol['full_name'] . ', your volunteer application has been reviewed and was not approved at this time. Reason: ' . $reason . '. You can update your details and reapply.',
            'become-volunteer.php', true);
    }
}

/**
 * Cancel a pending volunteer application (user self-service).
 */
function cancel_volunteer_application($pdo, $user_id) {
    $stmt = $pdo->prepare("DELETE FROM volunteers WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() > 0) {
        create_notification($pdo, $user_id, 'volunteer_cancelled',
            'Your volunteer application has been cancelled as requested. You can reapply whenever you are ready.',
            'become-volunteer.php', false);
        return true;
    }
    return false;
}

/**
 * Suspend a volunteer.
 */
function suspend_volunteer($pdo, $volunteer_id, $reason) {
    $stmt = $pdo->prepare("UPDATE volunteers SET status = 'suspended', rejected_reason = ? WHERE id = ?");
    $stmt->execute([$reason, $volunteer_id]);
    
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM volunteers WHERE id = ?");
    $stmt->execute([$volunteer_id]);
    $vol = $stmt->fetch();
    
    if ($vol) {
        create_notification($pdo, $vol['user_id'], 'volunteer_suspended',
            'Dear ' . $vol['full_name'] . ', your volunteer status has been suspended. Reason: ' . $reason . '. Please contact the admin for more information.',
            'dashboard.php', true);
    }
}



// ═════════════════════════════════════════════════════════════════
// SMTP EMAIL SENDING FUNCTIONS
// ═════════════════════════════════════════════════════════════════

/**
 * Send email using SMTP via PHPMailer.
 * Falls back to PHP mail() if SMTP is not configured or fails.
 *
 * @param PDO    $pdo         Database connection (needed to fetch SMTP settings)
 * @param string $to          Recipient email
 * @param string $subject     Email subject
 * @param string $body        Plain text body
 * @param string $htmlBody    Optional HTML body (if empty, plain text is used)
 * @param array  $attachments Optional file paths to attach
 * @param string $fromName    Optional sender name override
 * @return array ['success' => bool, 'method' => 'smtp'|'mail'|'none', 'message' => string]
 */
function send_email_smtp($pdo, $to, $subject, $body, $htmlBody = '', $attachments = [], $fromName = '') {
    // Try SMTP first
    $autoloadFile = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloadFile)) {
        @include_once $autoloadFile;
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $settings = false;
                try {
                    $stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE is_active = 1 AND host != '' ORDER BY id DESC LIMIT 1");
                    $stmt->execute();
                    $settings = $stmt->fetch();
                } catch (PDOException $e) {
                    $settings = false;
                }
                
                if ($settings && !empty($settings['host'])) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $settings['host'];
                    $mail->SMTPAuth   = !empty($settings['username']);
                    $mail->Username   = $settings['username'];
                    $mail->Password   = $settings['password'];
                    $mail->Port       = (int)$settings['port'];
                    $mail->CharSet    = 'UTF-8';
                    $mail->SMTPDebug  = 0;
                    
                    if ($settings['encryption'] === 'tls') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    } elseif ($settings['encryption'] === 'ssl') {
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $mail->SMTPAuth = false;
                    }
                    
                    $fromEmail = !empty($settings['from_email']) ? $settings['from_email'] : 'noreply@sayog.local';
                    $fromName  = !empty($fromName) ? $fromName : (!empty($settings['from_name']) ? $settings['from_name'] : 'Sayog');
                    
                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($to);
                    $mail->Subject = $subject;
                    
                    if (!empty($htmlBody)) {
                        $mail->isHTML(true);
                        $mail->Body = $htmlBody;
                        $mail->AltBody = $body;
                    } else {
                        $mail->isHTML(false);
                        $mail->Body = $body;
                    }
                    
                    foreach ($attachments as $file) {
                        if (file_exists($file)) {
                            $mail->addAttachment($file);
                        }
                    }
                    
                    $mail->send();
                    return ['success' => true, 'method' => 'smtp', 'message' => 'Email sent via SMTP'];
                }
            } catch (Exception $e) {
                // SMTP failed — silently fall through to PHP mail()
            }
        }
    }
    
    // Fallback to PHP mail()
    try {
        $headers = "From: noreply@sayog.local\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "Reply-To: support@sayog.local\r\n";
        $result = @mail($to, $subject, $body, $headers);
        if ($result) {
            return ['success' => true, 'method' => 'mail', 'message' => 'Email sent via PHP mail()'];
        }
        return ['success' => false, 'method' => 'mail', 'message' => 'PHP mail() failed to send'];
    } catch (Exception $e) {
        return ['success' => false, 'method' => 'none', 'message' => 'Unable to send email: ' . $e->getMessage()];
    }
}


// ═════════════════════════════════════════════════════════════════
// EMAIL OTP VERIFICATION FUNCTIONS
// ═════════════════════════════════════════════════════════════════

/**
 * Generate a random 6-digit OTP.
 */
function generate_otp() {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send OTP email to the user.
 */
function send_otp_email($pdo, $email, $otp, $name = '') {
    $subject = "Your Sayog Registration OTP";
    $body = "
Hello " . ($name ?: 'there') . ",

Thank you for registering on Sayog! Your email verification OTP is:

    " . $otp . "

This OTP is valid for 5 minutes. Please enter it on the verification page to complete your registration.

If you did not request this, you can safely ignore this email.

Best regards,
The Sayog Team
";
    
    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background-color:#f4f7f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7f6;padding:30px 10px;">
            <tr><td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#059669,#047857);padding:32px 40px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:700;">Sayog</h1>
                            <p style="color:#a7f3d0;margin:6px 0 0 0;font-size:14px;">Connecting surplus food with communities</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 40px 28px;">
                            <h2 style="color:#1f2937;margin:0 0 8px 0;font-size:20px;">Email Verification</h2>
                            <p style="color:#6b7280;margin:0 0 24px 0;font-size:15px;line-height:1.6;">Hello ' . htmlspecialchars($name ?: 'there') . ',</p>
                            <p style="color:#6b7280;margin:0 0 20px 0;font-size:15px;line-height:1.6;">Thank you for registering on <strong>Sayog</strong>! Use the OTP below to verify your email address. This code expires in <strong>5 minutes</strong>.</p>
                            <div style="background:#f9fafb;border:2px dashed #d1d5db;border-radius:12px;padding:20px;text-align:center;margin:0 0 24px 0;">
                                <div style="font-size:36px;font-weight:800;letter-spacing:8px;color:#059669;font-family:monospace;">' . $otp . '</div>
                            </div>
                            <p style="color:#9ca3af;margin:0 0 20px 0;font-size:13px;line-height:1.5;">If you did not request this verification, you can safely ignore this email.</p>
                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">
                            <p style="color:#9ca3af;margin:0;font-size:12px;line-height:1.5;">Sayog &mdash; Built to connect surplus food with communities. If you have any questions, contact support@sayog.local</p>
                        </td>
                    </tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>
    ';
    
    $result = send_email_smtp($pdo, $email, $subject, $body, $htmlBody);
    return $result['success'];
}

/**
 * Store OTP in the database for a given email.
 * Clears any existing OTPs for that email first.
 */
function store_otp($pdo, $email, $otp) {
    // Clean up expired OTPs for this email
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE email = ?");
    $stmt->execute([$email]);
    
    // Store new OTP with 10-minute expiry
    $stmt = $pdo->prepare("INSERT INTO email_verifications (email, otp, expires_at, attempts) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
    $stmt->execute([$email, $otp]);
    
    // Also clean up any other expired records
    cleanup_expired_otps($pdo);
    return true;
}

/**
 * Verify an OTP for a given email.
 * Returns true if valid, false otherwise.
 * Implements rate limiting: max 5 attempts per OTP.
 */
function verify_otp($pdo, $email, $otp) {
    // Find the latest valid OTP record
    $stmt = $pdo->prepare("SELECT id, attempts FROM email_verifications WHERE email = ? AND expires_at > NOW() AND verified = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    
    if (!$row) {
        return 'expired'; // No valid OTP found (expired or already verified)
    }
    
    // Check rate limit: max 5 attempts
    if ((int)$row['attempts'] >= 5) {
        return 'locked'; // Too many failed attempts
    }
    
    // Increment attempts
    $stmt = $pdo->prepare("UPDATE email_verifications SET attempts = attempts + 1 WHERE id = ?");
    $stmt->execute([$row['id']]);
    
    // Check if OTP matches
    $stmt = $pdo->prepare("SELECT id FROM email_verifications WHERE id = ? AND otp = ?");
    $stmt->execute([$row['id'], $otp]);
    $match = $stmt->fetch();
    
    if ($match) {
        // Valid OTP — mark as verified
        $stmt = $pdo->prepare("UPDATE email_verifications SET verified = 1 WHERE id = ?");
        $stmt->execute([$match['id']]);
        return true;
    }
    
    return false;
}

/**
 * Clean up expired OTP records.
 */
function cleanup_expired_otps($pdo) {
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE expires_at < NOW()");
    $stmt->execute();
}


?>

