<?php
/**
 * Volunteer Tracking API
 * 
 * REST entry point for the TrackingController.
 * Routes: POST /api/tracking.php?action=update — GPS location update
 *         GET  /api/tracking.php?action=location&delivery_id=X — Get volunteer location
 *         POST /api/tracking.php?action=toggle — Enable/disable sharing
 * 
 * Designed to be replaced with WebSocket transport when infrastructure is available.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

// ── Auth Check ──
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

use App\Controllers\TrackingController;

try {
    $controller = new TrackingController($pdo);
    
    match ($action) {
        'update' => handleUpdate($controller, $userId),
        'location' => handleLocation($controller, $userId),
        'toggle' => handleToggle($controller, $userId),
        default => throw new InvalidArgumentException("Unknown action: {$action}"),
    };
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ── Handlers ──

function handleUpdate(TrackingController $ctrl, int $userId): void {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST required']);
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $lat = (float)($data['latitude'] ?? 0);
    $lng = (float)($data['longitude'] ?? 0);
    
    if ($lat === 0.0 && $lng === 0.0) {
        echo json_encode(['success' => false, 'error' => 'Valid coordinates required']);
        return;
    }
    
    echo json_encode($ctrl->updateLocation($userId, $lat, $lng, $data));
}

function handleLocation(TrackingController $ctrl, int $userId): void {
    $deliveryId = (int)($_GET['delivery_id'] ?? 0);
    if ($deliveryId <= 0) {
        echo json_encode(['success' => false, 'error' => 'delivery_id required']);
        return;
    }
    echo json_encode($ctrl->getLocation($deliveryId, $userId));
}

function handleToggle(TrackingController $ctrl, int $userId): void {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST required']);
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $enabled = !empty($data['enabled']);
    echo json_encode($ctrl->toggleSharing($userId, $enabled));
}
