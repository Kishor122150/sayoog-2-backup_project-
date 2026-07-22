<?php
/**
 * Delivery Matching API
 * 
 * REST entry point for the DeliveryController.
 * Routes: GET/POST /api/delivery/{action}?id={delivery_id}
 * 
 * Usage: fetch('/api/delivery.php?action=show&id=5')
 * 
 * Integrates with the existing session/auth system — no new auth layer needed.
 */
 
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

// ── Auth Check ──
$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

if ($userId <= 0 && !$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$action = $_GET['action'] ?? '';
$deliveryId = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$method = $_SERVER['REQUEST_METHOD'];

use App\Controllers\DeliveryController;

try {
    $controller = new DeliveryController($pdo);
    
    match ($action) {
        'show' => handleShow($controller, $deliveryId),
        'transition' => handleTransition($controller, $deliveryId, $userId, $isAdmin),
        'match-scores' => handleMatchScores($controller, $deliveryId),
        'auto-assign' => handleAutoAssign($controller, $deliveryId, $isAdmin),
        'available' => handleAvailable($controller, $userId),
        default => throw new InvalidArgumentException("Unknown action: {$action}"),
    };
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ── Handlers ──

function handleShow(DeliveryController $ctrl, int $id): void {
    echo json_encode($ctrl->show($id));
}

function handleTransition(DeliveryController $ctrl, int $id, int $userId, bool $isAdmin): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST required']);
        return;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $actorType = $isAdmin ? 'admin' : ($data['actor_type'] ?? 'user');
    echo json_encode($ctrl->transition($id, $data, $actorType, $userId));
}

function handleMatchScores(DeliveryController $ctrl, int $id): void {
    echo json_encode($ctrl->matchScores($id));
}

function handleAutoAssign(DeliveryController $ctrl, int $id, bool $isAdmin): void {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin only']);
        return;
    }
    echo json_encode($ctrl->autoAssign($id));
}

function handleAvailable(DeliveryController $ctrl, int $userId): void {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = in_array((int)($_GET['per_page'] ?? 20), [10, 20, 50, 100]) ? (int)$_GET['per_page'] : 20;
    $result = $ctrl->availableDeliveries($userId, $page, $perPage);
    echo json_encode($result);
}
