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
            'Browse Food Listings', 'donations.php',
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
            'fas fa-bowl-food', 'Browse Donations', 'Find available food near you.', 'View Listings', 'donations.php',
            'fas fa-envelope', 'Contact Us', 'Need help? Reach out anytime.', 'Contact', 'contact.php',
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
            `rating_donor` TINYINT(1) DEFAULT NULL COMMENT 'Receiver\'s rating of the donor (1-5)',
            `rating_receiver` TINYINT(1) DEFAULT NULL COMMENT 'Donor\'s rating of the receiver (1-5)',
            `review_donor` TEXT DEFAULT NULL COMMENT 'Review left by receiver for donor',
            `review_receiver` TEXT DEFAULT NULL COMMENT 'Review left by donor for receiver',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`donation_id`) REFERENCES `donations`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            UNIQUE KEY `unique_donation_rating` (`donation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

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

    // Create certificates directory
    $certDir = __DIR__ . '/uploads/certificates';
    if (!is_dir($certDir)) {
        mkdir($certDir, 0755, true);
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
            $emailStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $emailStmt->execute([$user_id]);
            $user = $emailStmt->fetch();
            if ($user && filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                $subject = "Sayog Notification: " . ucfirst(str_replace('_', ' ', $type));
                $body = $message;
                if ($link) {
                    $body .= "\n\nView details: " . $link;
                }
                $headers = "From: no-reply@sayog.local" . "\r\n" .
                    "Content-Type: text/plain; charset=UTF-8";
                @mail($user['email'], $subject, $body, $headers);
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
               COUNT(rating_receiver) AS count_as_receiver
        FROM ratings
        WHERE donor_id = ? OR receiver_id = ?
    ");
    $stmt->execute([$user_id, $user_id]);
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
        if ($total > 0) $avg = round($sum / $total, 1);
    }
    return ['average' => $avg, 'total_ratings' => $total,
        'as_donor' => $row['avg_as_donor'] ?? 0, 'as_receiver' => $row['avg_as_receiver'] ?? 0,
        'count_as_donor' => $row['count_as_donor'] ?? 0, 'count_as_receiver' => $row['count_as_receiver'] ?? 0];
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






?>

