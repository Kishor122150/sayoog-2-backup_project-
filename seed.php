<?php
require_once 'config.php';

$message = '';
$error = '';

if (isset($_POST['seed'])) {
    try {
        $pdo->beginTransaction();

        // 1. Clear existing data to allow fresh seed
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE `requests`;");
        $pdo->exec("TRUNCATE TABLE `donations`;");
        $pdo->exec("TRUNCATE TABLE `users`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // 2. Insert test users
        // Hashed passwords:
        // DonorPass123! -> for donor@sayog.com
        // ConsumerPass123! -> for consumer@sayog.com
        $donor_pwd = password_hash('DonorPass123!', PASSWORD_BCRYPT);
        $consumer_pwd = password_hash('ConsumerPass123!', PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, address, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        
        // Donors
        $stmt->execute(['Kathmandu Bakery & Cafe', 'donor@sayog.com', 'New Baneshwor, Kathmandu', '9841122334', $donor_pwd, 'donor']);
        $donor1_id = $pdo->lastInsertId();
        
        $stmt->execute(['Anupam Food Plaza', 'donor2@sayog.com', 'Battisputali, Kathmandu', '9812345678', $donor_pwd, 'donor']);
        $donor2_id = $pdo->lastInsertId();

        // Consumers
        $stmt->execute(['Hope Orphanage Center', 'consumer@sayog.com', 'Balaju, Kathmandu', '014455667', $consumer_pwd, 'consumer']);
        $consumer1_id = $pdo->lastInsertId();

        $stmt->execute(['Sahaayata NGO Nepal', 'consumer2@sayog.com', 'Koteshwor, Kathmandu', '9851122334', $consumer_pwd, 'consumer']);
        $consumer2_id = $pdo->lastInsertId();


        // 3. Insert test donations
        $don_stmt = $pdo->prepare("INSERT INTO donations (donor_id, food_item, quantity, expiry_time, pickup_address, phone, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Expiry dates in the future
        $exp1 = date('Y-m-d H:i:s', strtotime('+12 hours'));
        $exp2 = date('Y-m-d H:i:s', strtotime('+6 hours'));
        $exp3 = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $don_stmt->execute([
            $donor1_id, 
            'Assorted Fresh Pastries and Muffins', 
            '15 large pieces', 
            $exp1, 
            'New Baneshwor (Behind Eyeplex Mall), Kathmandu', 
            '9841122334', 
            'Freshly baked this morning. Surplus from yesterday\'s retail counter. Kept covered in clean boxes.', 
            'available',
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]);
        $don1_id = $pdo->lastInsertId();

        $don_stmt->execute([
            $donor2_id, 
            'Rice, Dal and Mixed Veg Curry Packets', 
            '20 hot packs', 
            $exp2, 
            'Battisputali Chowk, Kathmandu', 
            '9812345678', 
            'Prepared for a corporate lunch event. These are unused, untouched fresh lunch packs packed individually in foil boxes.', 
            'available',
            date('Y-m-d H:i:s', strtotime('-15 minutes'))
        ]);

        $don_stmt->execute([
            $donor1_id, 
            'Organic Apples and Citrus Fruits', 
            '8 kg', 
            $exp3, 
            'New Baneshwor, Kathmandu', 
            '9841122334', 
            'Fresh fruits. Safe to consume, sorted, ready for pickup.', 
            'available',
            date('Y-m-d H:i:s', strtotime('-2 hours'))
        ]);
        $don3_id = $pdo->lastInsertId();

        // 4. Create one pre-existing request from consumer1 to donor1's fruits (don3)
        $req_stmt = $pdo->prepare("INSERT INTO requests (donation_id, consumer_id, quantity_requested, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $req_stmt->execute([
            $don3_id, 
            $consumer1_id, 
            '4 kg', 
            'Hello! We would love to request these fruits for the children at the center. We can collect them by 4:00 PM today.', 
            'pending',
            date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ]);
        
        // Update the fruit donation status to 'requested'
        $pdo->exec("UPDATE donations SET status = 'requested' WHERE id = " . $don3_id);

        $pdo->commit();
        $message = "Database seeded successfully! You can now log in with the test accounts.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Seeding failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder | Sayog - Food Donation System</title>
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <a href="login.php" class="auth-logo">
                <div class="auth-logo-icon">
                    <i class="fa-solid fa-database"></i>
                </div>
                <span>SAYOG SEEDER</span>
            </a>
            <p class="auth-subtitle">Populate database tables with mock data for instant validation</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-xmark"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div style="background-color: var(--background); border: 1px solid var(--border); padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 13.5px; color: var(--text-secondary);">
            <h4 style="color: var(--text-primary); margin-bottom: 8px; font-weight: 700;">Seeded Accounts Credentials:</h4>
            <div style="margin-bottom: 8px;">
                <span class="status-badge status-available" style="padding: 2px 6px;">DONOR ACCOUNT</span>
                <div style="margin-top: 4px;">Email: <strong>donor@sayog.com</strong></div>
                <div>Password: <strong>DonorPass123!</strong></div>
            </div>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 10px 0;">
            <div>
                <span class="status-badge status-accepted" style="padding: 2px 6px; background-color: rgba(59,130,246,0.1); color:#1e40af;">CONSUMER ACCOUNT</span>
                <div style="margin-top: 4px;">Email: <strong>consumer@sayog.com</strong></div>
                <div>Password: <strong>ConsumerPass123!</strong></div>
            </div>
        </div>

        <form action="seed.php" method="POST">
            <button type="submit" name="seed" class="btn btn-primary btn-block">
                <i class="fa-solid fa-seedling"></i> Seed Sample Data & Reset Database
            </button>
        </form>

        <div class="auth-footer" style="margin-top: 20px;">
            Go to <a href="login.php">Log In Page</a>
        </div>
    </div>
</body>
</html>
