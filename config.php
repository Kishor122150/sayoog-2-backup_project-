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
define('DB_PASS', '');
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
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createUsersTable);

    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    $columnCheck->execute(['role']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'user'");
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
            `status` ENUM('available', 'requested', 'accepted', 'completed', 'cancelled') DEFAULT 'available',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`donor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createDonationsTable);

    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'donations' AND COLUMN_NAME = ?");
    $columnCheck->execute(['image_path']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
    }

    $columnCheck->execute(['video_path']);
    if (!$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN video_path VARCHAR(255) DEFAULT NULL");
    }

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
    $stmt = $pdo->prepare("SELECT d.*, u.name AS donor_name, u.address AS donor_address, u.phone AS donor_phone FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.id = ? AND d.status IN ('available','requested')");
    $stmt->execute([$donation_id]);
    return $stmt->fetch();
}

/**
 * Get available donation listings (food donations)
 */
function get_available_donations($pdo) {
    $stmt = $pdo->prepare("SELECT d.*, u.name AS donor_name FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.status = 'available' ORDER BY d.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
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
?>
