<?php
namespace App\Controllers;

use App\Services\GeofencingService;
use PDO;

/**
 * Tracking Controller
 * REST API endpoints for real-time volunteer GPS tracking.
 * Designed to be easily upgraded to WebSocket transport later.
 */
class TrackingController {
    
    private PDO $pdo;
    private GeofencingService $geofencing;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->geofencing = new GeofencingService($pdo);
    }

    /**
     * POST /api/tracking/update - Update volunteer GPS location
     */
    public function updateLocation(int $volunteerUserId, float $lat, float $lng, array $data = []): array {
        $deliveryId = (int)($data['delivery_id'] ?? 0);
        $accuracy = $data['accuracy'] ?? null;
        $heading = $data['heading'] ?? null;
        $speed = $data['speed'] ?? null;

        // Insert location record
        $stmt = $this->pdo->prepare(
            "INSERT INTO volunteer_locations (volunteer_user_id, delivery_id, latitude, longitude, accuracy, heading, speed, is_sharing) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([$volunteerUserId, $deliveryId ?: null, $lat, $lng, $accuracy, $heading, $speed]);

        // Cleanup old locations (keep last 24h)
        $this->pdo->prepare(
            "DELETE FROM volunteer_locations WHERE volunteer_user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        )->execute([$volunteerUserId]);

        $actions = [];
        // Process geo-fences if delivery_id provided
        if ($deliveryId > 0) {
            try {
                $actions = $this->geofencing->processGpsUpdate($deliveryId, $volunteerUserId, $lat, $lng);
            } catch (\Exception $e) {
                // Geofencing is non-critical
            }
        }

        return [
            'success' => true,
            'geofence_actions' => $actions,
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * GET /api/tracking/{deliveryId} - Get volunteer's current location
     */
    public function getLocation(int $deliveryId, int $requestorId): array {
        // Get delivery details with volunteer info
        $stmt = $this->pdo->prepare("
            SELECT vd.*, v.tracking_enabled, vu.name AS volunteer_name, 
                   vol.vehicle_type, d.pickup_address, d.latitude AS pickup_lat, d.longitude AS pickup_lng
            FROM volunteer_deliveries vd
            LEFT JOIN volunteers v ON vd.volunteer_user_id = v.user_id
            LEFT JOIN users vu ON vd.volunteer_user_id = vu.id
            LEFT JOIN volunteers vol ON vd.volunteer_user_id = vol.user_id
            JOIN donations d ON vd.donation_id = d.id
            WHERE vd.id = ?
        ");
        $stmt->execute([$deliveryId]);
        $delivery = $stmt->fetch();

        if (!$delivery || !$delivery['volunteer_user_id']) {
            return ['success' => false, 'error' => 'No volunteer assigned'];
        }

        // Verify requestor authorization
        $isAuthorized = (
            (int)$requestorId === (int)$delivery['donor_id'] ||
            (int)$requestorId === (int)$delivery['consumer_id'] ||
            (int)$requestorId === (int)$delivery['volunteer_user_id']
        );

        if (!$isAuthorized) {
            return ['success' => false, 'error' => 'Not authorized'];
        }

        // Get latest location
        $locStmt = $this->pdo->prepare("
            SELECT latitude, longitude, accuracy, heading, speed, created_at 
            FROM volunteer_locations 
            WHERE volunteer_user_id = ? AND is_sharing = 1
            ORDER BY created_at DESC LIMIT 1
        ");
        $locStmt->execute([$delivery['volunteer_user_id']]);
        $location = $locStmt->fetch();

        $sharing = !empty($delivery['tracking_enabled']);

        return [
            'success' => true,
            'sharing' => $sharing,
            'volunteer_name' => $delivery['volunteer_name'],
            'vehicle_type' => $delivery['vehicle_type'],
            'delivery_status' => $delivery['status'],
            'location' => $location ? [
                'lat' => (float)$location['latitude'],
                'lng' => (float)$location['longitude'],
                'accuracy' => $location['accuracy'] ? (float)$location['accuracy'] : null,
                'heading' => $location['heading'] ? (float)$location['heading'] : null,
                'speed' => $location['speed'] ? (float)$location['speed'] : null,
                'updated_at' => $location['created_at'],
                'seconds_ago' => $location ? time() - strtotime($location['created_at']) : null,
            ] : null,
            'pickup' => [
                'address' => $delivery['pickup_address'],
                'lat' => $delivery['pickup_lat'],
                'lng' => $delivery['pickup_lng'],
            ],
        ];
    }

    /**
     * POST /api/tracking/toggle - Enable/disable location sharing
     */
    public function toggleSharing(int $userId, bool $enabled): array {
        $this->pdo->prepare("UPDATE volunteers SET tracking_enabled = ? WHERE user_id = ?")
            ->execute([$enabled ? 1 : 0, $userId]);

        if (!$enabled) {
            $this->pdo->prepare("UPDATE volunteer_locations SET is_sharing = 0 WHERE volunteer_user_id = ?")
                ->execute([$userId]);
        }

        return [
            'success' => true,
            'tracking_enabled' => $enabled,
        ];
    }
}
