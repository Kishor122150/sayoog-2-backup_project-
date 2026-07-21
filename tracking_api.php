<?php
/**
 * tracking_api.php
 * AJAX API endpoint for live GPS location tracking of volunteers.
 * 
 * Endpoints:
 *   POST ?action=update_location  — Volunteer updates their GPS position
 *   GET  ?action=get_location&delivery_id=X  — Donor/consumer fetches volunteer position
 *   POST ?action=toggle_sharing   — Volunteer toggles location sharing on/off
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = sanitize($_REQUEST['action'] ?? '');

// ── UPDATE VOLUNTEER LOCATION ──
if ($action === 'update_location') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $latitude = (float)($_POST['latitude'] ?? 0);
    $longitude = (float)($_POST['longitude'] ?? 0);
    $accuracy = isset($_POST['accuracy']) ? (float)$_POST['accuracy'] : null;
    $heading = isset($_POST['heading']) ? (float)$_POST['heading'] : null;
    $speed = isset($_POST['speed']) ? (float)$_POST['speed'] : null;
    $delivery_id = isset($_POST['delivery_id']) ? (int)$_POST['delivery_id'] : null;

    // Validate
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
        exit;
    }

    // Verify volunteer status
    $volStatus = get_volunteer_status($pdo, $user_id);
    if (!$volStatus || $volStatus['status'] !== 'approved') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not an approved volunteer']);
        exit;
    }

    // Verify tracking is enabled
    if (empty($volStatus['tracking_enabled'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Tracking not enabled']);
        exit;
    }

    // If delivery_id provided, verify this volunteer is assigned to it
    if ($delivery_id) {
        $stmt = $pdo->prepare("SELECT id FROM volunteer_deliveries WHERE id = ? AND volunteer_user_id = ? AND status IN ('accepted', 'picked_up', 'in_transit')");
        $stmt->execute([$delivery_id, $user_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Delivery not found or not assigned to you']);
            exit;
        }
    }

    try {
        // Clean up locations older than 24 hours to save space
        $pdo->prepare("DELETE FROM volunteer_locations WHERE volunteer_user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)")
            ->execute([$user_id]);
    } catch (PDOException $e) {
        // Cleanup is non-critical
    }

    // Insert new location
    $stmt = $pdo->prepare("INSERT INTO volunteer_locations (volunteer_user_id, delivery_id, latitude, longitude, accuracy, heading, speed, is_sharing) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$user_id, $delivery_id, $latitude, $longitude, $accuracy, $heading, $speed]);

    echo json_encode([
        'success' => true,
        'message' => 'Location updated',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// ── GET VOLUNTEER LOCATION ──
if ($action === 'get_location') {
    $delivery_id = (int)($_GET['delivery_id'] ?? 0);

    if ($delivery_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid delivery ID']);
        exit;
    }

    // Verify the requestor is associated with this delivery (donor, consumer, admin, or the volunteer themselves)
    $authenticated = false;
    if (is_logged_in()) {
        $userId = (int)$_SESSION['user_id'];
        // Check if user is admin
        $adminCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
        $adminCheck->execute([$userId]);
        if ($adminCheck->fetch()) {
            $authenticated = true;
        }
    }

    // Get the volunteer assigned to this delivery
    $stmt = $pdo->prepare("
        SELECT vd.volunteer_user_id, vd.donor_id, vd.consumer_id, vd.status as delivery_status,
               d.pickup_address, d.latitude as pickup_lat, d.longitude as pickup_lng
        FROM volunteer_deliveries vd
        JOIN donations d ON vd.donation_id = d.id
        WHERE vd.id = ? AND vd.volunteer_user_id IS NOT NULL
    ");
    $stmt->execute([$delivery_id]);
    $delivery = $stmt->fetch();

    if (!$delivery) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'No volunteer assigned to this delivery']);
        exit;
    }

    // If not admin, verify the requestor is the donor, consumer, or volunteer
    if (!$authenticated) {
        if (is_logged_in()) {
            $userId = (int)$_SESSION['user_id'];
            if ($userId === (int)$delivery['donor_id'] || $userId === (int)$delivery['consumer_id'] || $userId === (int)$delivery['volunteer_user_id']) {
                $authenticated = true;
            }
        }
    }

    if (!$authenticated) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. You are not associated with this delivery.']);
        exit;
    }

    // Get the latest location for the volunteer
    $stmt = $pdo->prepare("
        SELECT latitude, longitude, accuracy, heading, speed, created_at 
        FROM volunteer_locations 
        WHERE volunteer_user_id = ? AND (delivery_id = ? OR delivery_id IS NULL) AND is_sharing = 1
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$delivery['volunteer_user_id'], $delivery_id]);
    $location = $stmt->fetch();

    // Get volunteer info
    $vStmt = $pdo->prepare("SELECT full_name, vehicle_type, tracking_enabled FROM volunteers WHERE user_id = ?");
    $vStmt->execute([$delivery['volunteer_user_id']]);
    $volunteer = $vStmt->fetch();

    if (!$location || empty($volunteer['tracking_enabled'])) {
        echo json_encode([
            'success' => true,
            'sharing' => false,
            'message' => 'Volunteer location sharing is not active',
            'volunteer_name' => $volunteer['full_name'] ?? 'Volunteer',
            'delivery_status' => $delivery['delivery_status'],
            'pickup' => [
                'address' => $delivery['pickup_address'],
                'lat' => $delivery['pickup_lat'],
                'lng' => $delivery['pickup_lng']
            ]
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'sharing' => true,
        'volunteer_name' => $volunteer['full_name'] ?? 'Volunteer',
        'vehicle_type' => $volunteer['vehicle_type'] ?? null,
        'delivery_status' => $delivery['delivery_status'],
        'location' => [
            'lat' => (float)$location['latitude'],
            'lng' => (float)$location['longitude'],
            'accuracy' => $location['accuracy'] ? (float)$location['accuracy'] : null,
            'heading' => $location['heading'] ? (float)$location['heading'] : null,
            'speed' => $location['speed'] ? (float)$location['speed'] : null,
            'updated_at' => $location['created_at']
        ],
        'pickup' => [
            'address' => $delivery['pickup_address'],
            'lat' => $delivery['pickup_lat'],
            'lng' => $delivery['pickup_lng']
        ]
    ]);
    exit;
}

// ── TOGGLE LOCATION SHARING ──
if ($action === 'toggle_sharing') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $enabled = !empty($_POST['enabled']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE volunteers SET tracking_enabled = ? WHERE user_id = ?");
    $stmt->execute([$enabled, $user_id]);

    // If disabling, mark all active locations as not sharing
    if (!$enabled) {
        $pdo->prepare("UPDATE volunteer_locations SET is_sharing = 0 WHERE volunteer_user_id = ?")->execute([$user_id]);
    }

    echo json_encode([
        'success' => true,
        'tracking_enabled' => (bool)$enabled,
        'message' => $enabled ? 'Location sharing enabled' : 'Location sharing disabled'
    ]);
    exit;
}

// ── INVALID ACTION ──
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
