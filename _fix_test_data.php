<?php
require_once __DIR__ . '/config.php';

// Get test user
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute(['pagetest@sayog.com']);
$userId = $stmt->fetchColumn();
echo "Test User ID: $userId\n";

// Get another user as donor (user1@sayog.com) - or admin
$donorStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$donorStmt->execute(['user1@sayog.com']);
$donorId = $donorStmt->fetchColumn();
if (!$donorId) {
    $donorStmt->execute(['admin@123']);
    $donorId = $donorStmt->fetchColumn();
}
if (!$donorId) {
    echo "No donor found, creating one...\n";
    $hash = password_hash('Test@123', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (name, email, address, phone, password, role) VALUES ('Donor User', 'donor@sayog.com', 'Kathmandu', '9800000003', ?, 'user')")->execute([$hash]);
    $donorId = $pdo->lastInsertId();
}
echo "Donor User ID: $donorId\n";

// Create donations from donor so test user can request them
$donStmt = $pdo->prepare("INSERT INTO donations (donor_id, food_item, quantity, expiry_time, pickup_address, phone, status, verification_status, created_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 DAY), 'Kathmandu', '9800000001', 'available', 'approved', DATE_SUB(NOW(), INTERVAL ? HOUR))");
$foodItems = ['Rice', 'Dal', 'Vegetables', 'Fruits', 'Bread', 'Cooked Meal'];
for ($i = 1; $i <= 20; $i++) {
    $item = $foodItems[array_rand($foodItems)];
    $donStmt->execute([$donorId, "$item #$i", rand(1,5) . ' kg', rand(1, 720)]);
}
echo "Created 20 donations from donor.\n";

// Get the donation IDs
$dStmt = $pdo->query("SELECT id FROM donations WHERE donor_id = $donorId AND status = 'available' LIMIT 15");
$donIds = $dStmt->fetchAll(PDO::FETCH_COLUMN);

// Create requests WHERE TEST USER IS CONSUMER (so they appear in manage-request and track-request)
$reqStmt = $pdo->prepare("INSERT INTO requests (donation_id, consumer_id, quantity_requested, message, status, created_at) VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))");
$statuses = ['pending', 'approved', 'completed', 'cancelled'];
foreach ($donIds as $did) {
    $status = $statuses[array_rand($statuses)];
    $reqStmt->execute([$did, $userId, rand(1,3) . ' kg', 'Need this food please', $status, rand(1, 480)]);
}
echo "Created 15 requests for test user (as consumer).\n";

// Also create donations from test user (for track-donation page)
// Already done in the first script

// Create MORE notifications (30 more = total 50, so pagination shows if per_page=20)
$notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link, is_read, created_at) VALUES (?, 'system', ?, NULL, 0, DATE_SUB(NOW(), INTERVAL ? HOUR))");
for ($i = 1; $i <= 30; $i++) {
    $notifStmt->execute([$userId, "Additional notification #$i for pagination testing purposes.", rand(1, 168)]);
}
echo "Created 30 more notifications (total 50 = 3 pages with 20 per page).\n";

echo "\n✅ Test data created! Now visit:\n";
echo "  http://localhost:8002/dashboard.php?page=create-donation  → 25 donations = 3 pages\n";
echo "  http://localhost:8002/dashboard.php?page=manage-request   → 15 requests = 2 pages\n";
echo "  http://localhost:8002/dashboard.php?page=track-request    → 15 requests = 2 pages\n";
echo "  http://localhost:8002/dashboard.php?page=notifications    → 50 notifications = 3 pages\n";
