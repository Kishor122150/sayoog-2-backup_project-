<?php
namespace App\Services;

use App\Config\DeliveryStates;
use PDO;

/**
 * Geofencing Service
 * 
 * Automatically detects when a volunteer enters/exits geo-fences 
 * around pickup and delivery locations.
 * 
 * - Pickup fence (~100m): Auto-trigger picked_up status
 * - Delivery fence (~100m): Auto-trigger delivered status
 * - Logs all fence events to geofence_log table
 */
class GeofencingService {
    
    private PDO $pdo;

    // Fence radii in meters
    const PICKUP_RADIUS_METERS  = 100;
    const DELIVERY_RADIUS_METERS = 100;
    const WAYPOINT_RADIUS_METERS = 200;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Process a GPS update and check all geo-fences.
     * Returns actions taken, if any.
     */
    public function processGpsUpdate(int $deliveryId, int $volunteerUserId, float $lat, float $lng): array {
        $actions = [];

        // Guard: skip if coordinates are zero/invalid
        if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
            return $actions;
        }
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return $actions;
        }

        // Get delivery with location data
        $stmt = $this->pdo->prepare("
            SELECT vd.*, d.latitude AS pickup_lat, d.longitude AS pickup_lng,
                   d.pickup_address,
                   u.address AS consumer_address
            FROM volunteer_deliveries vd
            JOIN donations d ON vd.donation_id = d.id
            JOIN users u ON vd.consumer_id = u.id
            WHERE vd.id = ? AND vd.volunteer_user_id = ?
        ");
        $stmt->execute([$deliveryId, $volunteerUserId]);
        $delivery = $stmt->fetch();

        if (!$delivery) return $actions;

        // Guard: skip if pickup location has no valid GPS coordinates
        $hasPickupCoords = !empty($delivery['pickup_lat']) && !empty($delivery['pickup_lng'])
            && (float)$delivery['pickup_lat'] !== 0.0 && (float)$delivery['pickup_lng'] !== 0.0;

        $currentStatus = $delivery['status'];

        // 1. Check pickup fence (if status is 'accepted' and valid coordinates exist)
        if ($currentStatus === DeliveryStates::ACCEPTED && $hasPickupCoords) {
            $pickupDist = $this->haversine(
                $lat, $lng,
                (float)$delivery['pickup_lat'], (float)$delivery['pickup_lng']
            );
            
            if ($pickupDist <= self::PICKUP_RADIUS_METERS) {
                $this->logFenceEvent($deliveryId, $volunteerUserId, 'pickup', $lat, $lng, $pickupDist);
                
                if (!self::wasActionTakenRecently($deliveryId, 'pickup', 5)) {
                    $sm = new DeliveryStateMachine($this->pdo);
                    $result = $sm->transition(
                        $deliveryId, DeliveryStates::PICKED_UP, 'system', $volunteerUserId,
                        $lat, $lng,
                        "Auto-detected at pickup location ({$pickupDist}m away)",
                        ['geofence' => ['type' => 'pickup', 'distance' => $pickupDist, 'radius' => self::PICKUP_RADIUS_METERS]]
                    );
                    if ($result['success']) {
                        $actions[] = 'auto_picked_up';
                    }
                }
            }
        }

        // 2. Check delivery fence (if status is 'picked_up' or 'in_transit')
        if (in_array($currentStatus, [DeliveryStates::PICKED_UP, DeliveryStates::IN_TRANSIT], true)) {
            // Try to geocode consumer address for delivery location
            $deliveryLat = $this->getConsumerLatLng($delivery['consumer_address']);
            if ($deliveryLat) {
                $deliveryDist = $this->haversine($lat, $lng, $deliveryLat['lat'], $deliveryLat['lng']);
                
                if ($deliveryDist <= self::DELIVERY_RADIUS_METERS) {
                    $this->logFenceEvent($deliveryId, $volunteerUserId, 'dropoff', $lat, $lng, $deliveryDist);
                    
                    if (!self::wasActionTakenRecently($deliveryId, 'dropoff', 5)) {
                        $sm = new DeliveryStateMachine($this->pdo);
                        $result = $sm->transition(
                            $deliveryId, DeliveryStates::DELIVERED, 'system', $volunteerUserId,
                            $lat, $lng,
                            "Auto-detected at delivery location ({$deliveryDist}m away)",
                            ['geofence' => ['type' => 'dropoff', 'distance' => $deliveryDist, 'radius' => self::DELIVERY_RADIUS_METERS]]
                        );
                        if ($result['success']) {
                            $actions[] = 'auto_delivered';
                        }
                    }
                }
            }
        }

        return $actions;
    }

    /**
     * Check if a specific fence action was taken recently (to avoid duplicate triggers).
     */
    public function wasActionTakenRecently(int $deliveryId, string $fenceType, int $minutes = 5): bool {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM geofence_log 
             WHERE delivery_id = ? AND fence_type = ? 
             AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE) 
             LIMIT 1"
        );
        $stmt->execute([$deliveryId, $fenceType, $minutes]);
        return (bool)$stmt->fetch();
    }

    /**
     * Log a geofence event.
     */
    private function logFenceEvent(int $deliveryId, int $volunteerUserId, string $fenceType, float $lat, float $lng, float $distance): void {
        $this->pdo->prepare(
            "INSERT INTO geofence_log (delivery_id, volunteer_user_id, fence_type, triggered_at, latitude, longitude, distance_meters) 
             VALUES (?, ?, ?, NOW(), ?, ?, ?)"
        )->execute([$deliveryId, $volunteerUserId, $fenceType, $lat, $lng, round($distance, 2)]);
    }

    /**
     * Haversine distance between two GPS coordinates in meters.
     */
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $R = 6371000; // Earth radius in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Get approximate lat/lng for a consumer address.
     * Uses tokenized matching against known Nepal cities.
     */
    private function getConsumerLatLng(?string $address): ?array {
        if (empty($address)) return null;
        
        $knownCities = [
            'kathmandu' => [27.7172, 85.3240],
            'lalitpur' => [27.6588, 85.3247],
            'pokhara' => [28.2096, 83.9856],
            'bhaktapur' => [27.6710, 85.4298],
            'biratnagar' => [26.4524, 87.2718],
            'chitwan' => [27.5333, 84.3333],
            'butwal' => [27.6833, 83.4500],
            'dhangadhi' => [28.6833, 80.6000],
            'nepalgunj' => [28.0500, 81.6167],
            'hetauda' => [27.4167, 85.0333],
        ];

        $lower = strtolower($address);
        foreach ($knownCities as $city => $coords) {
            if (str_contains($lower, $city)) {
                return ['lat' => $coords[0], 'lng' => $coords[1]];
            }
        }

        return null;
    }

    /**
     * Get geofence statistics.
     */
    public function getStats(): array {
        return [
            'total_events' => (int)$this->pdo->query("SELECT COUNT(*) FROM geofence_log")->fetchColumn(),
            'pickup_triggers' => (int)$this->pdo->query("SELECT COUNT(*) FROM geofence_log WHERE fence_type = 'pickup'")->fetchColumn(),
            'dropoff_triggers' => (int)$this->pdo->query("SELECT COUNT(*) FROM geofence_log WHERE fence_type = 'dropoff'")->fetchColumn(),
        ];
    }
}
