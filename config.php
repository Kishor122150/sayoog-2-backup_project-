<?php
// PHP session configuration for enhanced security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createUsersTable);

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
 * Redirect back helper
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}
?>
