<?php
require_once __DIR__ . '/config.php';

// Get test user
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute(['pagetest@sayog.com']);
$userId = $stmt->fetchColumn();
if (!$userId) {
    die("Test user not found. Run create user first.\n");
}
echo "User ID: $userId\n";

// Create 25 sample donations (3 pages with 10 per page)
$foodItems = ['Rice', 'Dal', 'Vegetables', 'Fruits', 'Bread', 'Milk', 'Cooked Meal', 'Snacks', 'Biscuits', 'Juice'];
$addresses = ['Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Chitwan'];
$donStmt = $pdo->prepare("INSERT INTO donations (donor_id, food_item, quantity, expiry_time, pickup_address, phone, status, verification_status, created_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 DAY), ?, '9800000001', 'available', 'approved', DATE_SUB(NOW(), INTERVAL ? HOUR))");

for ($i = 1; $i <= 25; $i++) {
    $item = $foodItems[array_rand($foodItems)];
    $addr = $addresses[array_rand($addresses)];
    $qty = rand(1, 10) . ' kg';
    $hours = rand(1, 720);
    $donStmt->execute([$userId, "$item #$i", $qty, $addr, $hours]);
}
echo "Created 25 donations.\n";

// Create 15 requests (2 pages with 10 per page)
$reqStmt = $pdo->prepare("INSERT INTO requests (donation_id, consumer_id, quantity_requested, message, status, created_at) VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))");

// Get donation IDs
$dStmt = $pdo->query("SELECT id FROM donations WHERE donor_id = $userId LIMIT 15");
$donIds = $dStmt->fetchAll(PDO::FETCH_COLUMN);

// Need another user as consumer for requests
$cStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$cStmt->execute(['user1@sayog.com']);
$consumerId = $cStmt->fetchColumn();
if (!$consumerId) {
    // Create a consumer
    $hash = password_hash('Test@123', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (name, email, address, phone, password, role) VALUES (?, ?, ?, ?, ?, 'user')")->execute(['Test Consumer 2', 'consumer2@sayog.com', 'Kathmandu', '9800000002', $hash]);
    $consumerId = $pdo->lastInsertId();
    echo "Created consumer with ID: $consumerId\n";
}

$statuses = ['pending', 'approved', 'completed', 'cancelled'];
foreach ($donIds as $did) {
    $status = $statuses[array_rand($statuses)];
    $hours = rand(1, 480);
    $reqStmt->execute([$did, $consumerId, rand(1,5) . ' kg', 'Please share this food.', $status, $hours]);
}
echo "Created 15 requests.\n";

// Create 20 notifications (2 pages with 10 per page)
$notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))");
$notifTypes = ['request_received', 'request_approved', 'request_rejected', 'pickup_completed', 'system'];
for ($i = 1; $i <= 20; $i++) {
    $type = $notifTypes[array_rand($notifTypes)];
    $msg = "Test notification #$i: Your request status has been updated.";
    $isRead = rand(0, 1);
    $hours = rand(1, 168);
    $notifStmt->execute([$userId, $type, $msg, 'dashboard.php?page=home', $isRead, $hours]);
}
echo "Created 20 notifications.\n";

echo "\nTest data created successfully! Now visit:\n";
echo "  http://localhost:8002/dashboard.php?page=create-donation\n";
echo "  http://localhost:8002/dashboard.php?page=manage-request\n";
echo "  http://localhost:8002/dashboard.php?page=track-request\n";
echo "  http://localhost:8002/dashboard.php?page=notifications\n";
echo "Each should show pagination controls.\n";
