<?php
/**
 * SMTP Configuration for Sayog
 * 
 * This file provides helper functions to manage SMTP settings stored in the database.
 * Settings are configured via the admin panel (admin.php?section=smtp).
 * 
 * ====================================
 * HOW TO CONFIGURE SMTP:
 * ====================================
 * 
 * Option 1: Admin Panel (Recommended)
 *   - Log in to admin panel → Settings → SMTP Configuration
 *   - Fill in your SMTP host, port, username, password, encryption
 *   - Click "Test Connection" to verify before saving
 * 
 * Option 2: Direct Database (if admin panel not accessible)
 *   - Run this SQL (replace values with your SMTP credentials):
 *     
 *     UPDATE smtp_settings SET 
 *         host = 'smtp.gmail.com',
 *         port = 587,
 *         username = 'your-email@gmail.com',
 *         password = 'your-app-password',
 *         encryption = 'tls',
 *         from_email = 'your-email@gmail.com',
 *         from_name = 'Sayog',
 *         is_active = 1
 *     WHERE id = 1;
 * 
 * ====================================
 * COMMON SMTP PROVIDERS:
 * ====================================
 * 
 * Gmail:
 *   Host: smtp.gmail.com
 *   Port: 587 (TLS) or 465 (SSL)
 *   Username: your-full-email@gmail.com
 *   Password: Use an App Password (not your regular password)
 *   - Enable 2FA on your Google account
 *   - Create App Password: https://myaccount.google.com/apppasswords
 * 
 * Outlook / Hotmail:
 *   Host: smtp.office365.com
 *   Port: 587 (TLS)
 *   Username: your-email@outlook.com
 *   Password: your password or app password
 * 
 * Yahoo Mail:
 *   Host: smtp.mail.yahoo.com
 *   Port: 587 (TLS) or 465 (SSL)
 *   Username: your-email@yahoo.com
 *   Password: App password
 * 
 * Zoho Mail:
 *   Host: smtp.zoho.com
 *   Port: 587 (TLS)
 *   Username: your-email@zoho.com
 *   Password: your password
 * 
 * Custom cPanel / Shared Hosting:
 *   Host: mail.yourdomain.com
 *   Port: 587 (TLS) or 465 (SSL) or 25
 *   Username: your-full-email@yourdomain.com
 *   Password: your email password
 * 
 * SendGrid:
 *   Host: smtp.sendgrid.net
 *   Port: 587 (TLS) or 465 (SSL)
 *   Username: apikey
 *   Password: your SendGrid API key
 * 
 * Amazon SES:
 *   Host: email-smtp.region.amazonaws.com
 *   Port: 587 (TLS) or 465 (SSL)
 *   Username: SMTP username (from SES)
 *   Password: SMTP password (from SES)
 */

/**
 * Get active SMTP settings from database.
 *
 * @param PDO $pdo
 * @return array|false False if no active settings
 */
function get_smtp_settings($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $settings = $stmt->fetch();
        
        if ($settings && !empty($settings['host'])) {
            return $settings;
        }
    } catch (PDOException $e) {
        // Silently handle — table may not exist yet
    }
    return false;
}

/**
 * Save SMTP settings to database.
 *
 * @param PDO    $pdo
 * @param array  $data  Associative array with keys: host, port, username, password, encryption, from_email, from_name, is_active
 * @return bool
 */
function save_smtp_settings($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            UPDATE smtp_settings SET 
                host = :host,
                port = :port,
                username = :username,
                password = :password,
                encryption = :encryption,
                from_email = :from_email,
                from_name = :from_name,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':host'       => $data['host'] ?? '',
            ':port'       => (int)($data['port'] ?? 587),
            ':username'   => $data['username'] ?? '',
            ':password'   => $data['password'] ?? '',
            ':encryption' => $data['encryption'] ?? 'tls',
            ':from_email' => $data['from_email'] ?? '',
            ':from_name'  => $data['from_name'] ?? 'Sayog',
            ':is_active'  => !empty($data['is_active']) ? 1 : 0,
            ':id'         => (int)($data['id'] ?? 1),
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Test SMTP connection by sending a test email.
 *
 * @param PDO    $pdo
 * @param string $test_email  Email address to send the test to
 * @return array  ['success' => bool, 'message' => string]
 */
function test_smtp_connection($pdo, $test_email) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $settings = get_smtp_settings($pdo);
    if (!$settings) {
        return ['success' => false, 'message' => 'No active SMTP settings found.'];
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $settings['host'];
        $mail->SMTPAuth   = !empty($settings['username']);
        $mail->Username   = $settings['username'];
        $mail->Password   = $settings['password'];
        $mail->Port       = (int)$settings['port'];
        
        if ($settings['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($settings['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPAuth = false;
        }
        
        $mail->setFrom($settings['from_email'], $settings['from_name']);
        $mail->addAddress($test_email);
        $mail->Subject = 'SMTP Test - Sayog';
        $mail->Body    = "This is a test email from Sayog.\n\nIf you received this, your SMTP configuration is working correctly!\n\nSent at: " . date('Y-m-d H:i:s');
        
        $mail->send();
        return ['success' => true, 'message' => 'Test email sent successfully to ' . $test_email];
    } catch (PHPMailer\PHPMailer\Exception $e) {
        return ['success' => false, 'message' => 'SMTP Error: ' . $mail->ErrorInfo];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
