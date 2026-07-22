<?php
/**
 * Sayog App Bootstrap
 * 
 * Integration bridge between the old flat-function system and the new service layer.
 * Include this in any page that wants to use the new architecture.
 * 
 * Safety guarantees:
 * - Never modifies existing file behavior
 * - All new features are opt-in via function calls
 * - Falls back gracefully if services fail
 * 
 * Usage: require_once __DIR__ . '/app/bootstrap.php';
 */

require_once __DIR__ . '/autoload.php';

use App\Services\DeliveryStateMachine;
use App\Services\VolunteerMatchingService;
use App\Services\NotificationService;
use App\Services\GeofencingService;
use App\Services\AnalyticsService;
use App\Controllers\DeliveryController;
use App\Controllers\TrackingController;

/**
 * Get a service instance (singleton pattern via registry).
 */
function app($name = null) {
    static $registry = [];
    
    if ($name === null) {
        return $registry;
    }
    
    if (!isset($registry[$name])) {
        global $pdo;
        $registry[$name] = match ($name) {
            'state_machine' => new DeliveryStateMachine($pdo),
            'matcher' => new VolunteerMatchingService($pdo),
            'notifier' => new NotificationService($pdo),
            'geofencing' => new GeofencingService($pdo),
            'analytics' => new AnalyticsService($pdo),
            'delivery_controller' => new DeliveryController($pdo),
            'tracking_controller' => new TrackingController($pdo),
            default => throw new \InvalidArgumentException("Unknown service: {$name}"),
        };
    }
    
    return $registry[$name];
}

/**
 * Try to auto-assign a volunteer when a delivery is created.
 * Safe wrapper - never throws, never breaks existing flow.
 */
function try_auto_assign_volunteer(int $deliveryId): ?array {
    try {
        if (!class_exists('App\\Services\\VolunteerMatchingService')) {
            return null;
        }
        return app('matcher')->autoAssign($deliveryId);
    } catch (\Throwable $e) {
        error_log("Auto-assign failed (non-critical): {$e->getMessage()}");
        return null;
    }
}

/**
 * Try to log an event via the state machine.
 * Safe wrapper - never throws, never breaks existing flow.
 */
function try_log_delivery_event(int $deliveryId, string $fromStatus, string $toStatus, string $actorType = 'system', ?int $actorId = null, ?string $notes = null): void {
    try {
        if (!class_exists('App\\Services\\DeliveryStateMachine')) {
            return;
        }
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return;
        
        $stmt = $pdo->prepare(
            "INSERT INTO delivery_events (delivery_id, from_status, to_status, actor_type, actor_id, notes) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$deliveryId, $fromStatus, $toStatus, $actorType, $actorId, $notes]);
    } catch (\Throwable $e) {
        error_log("Event logging failed (non-critical): {$e->getMessage()}");
    }
}

/**
 * Try to send a notification via the new queue system.
 * Safe wrapper - falls back to old create_notification() if new system fails.
 */
function try_send_notification(int $userId, string $type, string $message, ?string $link = null, array $options = []): void {
    try {
        if (class_exists('App\\Services\\NotificationService')) {
            app('notifier')->notify($userId, $type, $message, $link, $options);
            return;
        }
    } catch (\Throwable $e) {
        error_log("Notification via queue failed, falling back: {$e->getMessage()}");
    }
    
    // Fallback to old notification system
    if (function_exists('create_notification')) {
        create_notification($GLOBALS['pdo'], $userId, $type, $message, $link, $options['send_email'] ?? false);
    }
}
