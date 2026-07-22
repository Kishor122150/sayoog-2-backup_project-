<?php
namespace App\Services;

use App\Config\DeliveryStates;
use PDO;

/**
 * Delivery State Machine Service
 * 
 * Manages all delivery state transitions with:
 * - Validation against the state machine config
 * - Event sourcing (every transition logged to delivery_events)
 * - Auto-notifications on state changes
 * - Race condition protection via row-level locking
 */
class DeliveryStateMachine {
    
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Transition a delivery to a new state.
     * Returns ['success' => bool, 'event_id' => ?int, 'error' => ?string]
     */
    public function transition(
        int $deliveryId,
        string $newStatus,
        string $actorType = 'system',
        ?int $actorId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $notes = null,
        array $metadata = []
    ): array {
        // 1. Get current delivery state with row-level lock
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM volunteer_deliveries WHERE id = ? FOR UPDATE");
            $stmt->execute([$deliveryId]);
            $delivery = $stmt->fetch();

            if (!$delivery) {
                $this->pdo->rollBack();
                return ['success' => false, 'event_id' => null, 'error' => 'Delivery not found'];
            }

            $currentStatus = $delivery['status'];

            // 2. Check if already at target status
            if ($currentStatus === $newStatus) {
                $this->pdo->rollBack();
                return ['success' => false, 'event_id' => null, 'error' => 'Already at status: ' . $newStatus];
            }

            // 3. Check if terminal
            if (DeliveryStates::isTerminal($currentStatus)) {
                $this->pdo->rollBack();
                return ['success' => false, 'event_id' => null, 'error' => 'Cannot transition from terminal status: ' . $currentStatus];
            }

            // 4. Validate transition
            if (!DeliveryStates::isValidTransition($currentStatus, $newStatus, $actorType)) {
                $this->pdo->rollBack();
                return [
                    'success' => false, 
                    'event_id' => null, 
                    'error' => "Invalid transition: {$currentStatus} → {$newStatus} for actor {$actorType}"
                ];
            }

            $config = DeliveryStates::getTransitionConfig($currentStatus, $newStatus);

            // 5. GPS required check
            if (!empty($config['requires_gps']) && (empty($latitude) || empty($longitude))) {
                $this->pdo->rollBack();
                return ['success' => false, 'event_id' => null, 'error' => 'GPS coordinates required for this transition'];
            }

            // 6. Notes required check
            if (!empty($config['notes_required']) && empty($notes)) {
                $this->pdo->rollBack();
                return ['success' => false, 'event_id' => null, 'error' => 'Notes required for this transition'];
            }

            // 7. Authorize actor for this delivery
            if ($actorType === 'volunteer' && $actorId) {
                if ((int)$delivery['volunteer_user_id'] !== (int)$actorId) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'event_id' => null, 'error' => 'Volunteer not assigned to this delivery'];
                }
            }

            // 8. Perform the state update
            $timeColumn = $newStatus . '_at';
            $updateFields = "status = ?, {$timeColumn} = NOW()";
            $params = [$newStatus];

            // 8a. Set volunteer_user_id when a volunteer accepts a delivery
            if ($newStatus === DeliveryStates::ACCEPTED && $actorType === 'volunteer' && $actorId) {
                $updateFields .= ", volunteer_user_id = ?, assignment_method = 'manual_accept'";
                $params[] = $actorId;
            }

            $params[] = $deliveryId;
            $stmt = $this->pdo->prepare("UPDATE volunteer_deliveries SET {$updateFields} WHERE id = ?");
            $stmt->execute($params);

            // 9. Log event (event sourcing)
            $eventStmt = $this->pdo->prepare(
                "INSERT INTO delivery_events (delivery_id, from_status, to_status, actor_type, actor_id, latitude, longitude, notes, metadata) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $eventStmt->execute([
                $deliveryId,
                $currentStatus,
                $newStatus,
                $actorType,
                $actorId,
                $latitude,
                $longitude,
                $notes,
                json_encode($metadata + ['transition_config' => $config])
            ]);
            $eventId = (int)$this->pdo->lastInsertId();

            // 10. Increment event counter
            $this->pdo->prepare("UPDATE volunteer_deliveries SET event_count = event_count + 1 WHERE id = ?")
                ->execute([$deliveryId]);

            // 11. Handle completion actions
            if ($newStatus === DeliveryStates::DELIVERED) {
                $this->handleDeliveryCompletion($delivery, $deliveryId, $actorId);
            }
            if ($newStatus === DeliveryStates::CANCELLED) {
                $this->handleDeliveryCancellation($delivery, $deliveryId, $notes);
            }

            $this->pdo->commit();
            return ['success' => true, 'event_id' => $eventId, 'error' => null];

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'event_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the event history for a delivery.
     */
    public function getEventHistory(int $deliveryId, int $limit = 50): array {
        $stmt = $this->pdo->prepare(
            "SELECT de.*, vu.name AS actor_name 
             FROM delivery_events de 
             LEFT JOIN users vu ON de.actor_id = vu.id 
             WHERE de.delivery_id = ? 
             ORDER BY de.created_at ASC 
             LIMIT ?"
        );
        $stmt->execute([$deliveryId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get current state info for a delivery.
     */
    public function getCurrentState(int $deliveryId): array {
        $stmt = $this->pdo->prepare(
            "SELECT vd.*, 
                    (SELECT COUNT(*) FROM delivery_events WHERE delivery_id = vd.id) AS total_events,
                    (SELECT created_at FROM delivery_events WHERE delivery_id = vd.id ORDER BY id DESC LIMIT 1) AS last_event_at
             FROM volunteer_deliveries vd WHERE vd.id = ?"
        );
        $stmt->execute([$deliveryId]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Check if a delivery has been stuck in the same state too long.
     */
    public function isStuck(int $deliveryId, string $maxAge = '2 HOUR'): bool {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM volunteer_deliveries 
             WHERE id = ? AND status IN ('accepted', 'picked_up', 'in_transit')
             AND updated_at < DATE_SUB(NOW(), INTERVAL {$maxAge})"
        );
        $stmt->execute([$deliveryId]);
        return (bool)$stmt->fetch();
    }

    // ── Private Helpers ──

    private function handleDeliveryCompletion(array $delivery, int $deliveryId, ?int $actorId): void {
        // Mark donation and request as completed
        $this->pdo->prepare("UPDATE donations SET status = 'completed' WHERE id = ?")
            ->execute([$delivery['donation_id']]);
        $this->pdo->prepare("UPDATE requests SET status = 'completed' WHERE id = ?")
            ->execute([$delivery['request_id']]);
        
        // Update volunteer stats
        if ($actorId) {
            $this->pdo->prepare(
                "UPDATE volunteers SET completed_deliveries = completed_deliveries + 1, community_points = community_points + 10 WHERE user_id = ?"
            )->execute([$actorId]);
        }

        // Queue notifications via NotificationService
        $notifier = new NotificationService($this->pdo);
        $dStmt = $this->pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
        $dStmt->execute([$delivery['donation_id']]);
        $food = $dStmt->fetchColumn();

        $notifier->notify($delivery['donor_id'], 'delivery_completed', 
            "Your donation \"{$food}\" has been delivered!", 
            'dashboard.php?page=track-donation&rate_donation=' . $delivery['donation_id'],
            ['priority' => 1, 'channels' => ['in_app', 'email']]
        );
        $notifier->notify($delivery['consumer_id'], 'delivery_completed',
            "Your food \"{$food}\" has been delivered! Please rate the experience.",
            'dashboard.php?page=track-request&rate_donation=' . $delivery['donation_id'],
            ['priority' => 1, 'channels' => ['in_app', 'email']]
        );
    }

    private function handleDeliveryCancellation(array $delivery, int $deliveryId, ?string $reason): void {
        $notifier = new NotificationService($this->pdo);
        $dStmt = $this->pdo->prepare("SELECT food_item FROM donations WHERE id = ?");
        $dStmt->execute([$delivery['donation_id']]);
        $food = $dStmt->fetchColumn();

        // Create a NEW delivery record for reassignment (so other volunteers can pick it up)
        $stmt = $this->pdo->prepare(
            "INSERT INTO volunteer_deliveries (donation_id, request_id, consumer_id, donor_id, status, assignment_method) 
             VALUES (?, ?, ?, ?, 'assigned', 'reassigned')"
        );
        $stmt->execute([
            $delivery['donation_id'],
            $delivery['request_id'],
            $delivery['consumer_id'],
            $delivery['donor_id']
        ]);
        $newId = (int)$this->pdo->lastInsertId();

        // Log the reassignment event
        $eventStmt = $this->pdo->prepare(
            "INSERT INTO delivery_events (delivery_id, from_status, to_status, actor_type, notes) 
             VALUES (?, 'cancelled', 'assigned', 'system', ?)"
        );
        $eventStmt->execute([$newId, 'Auto-created for reassignment after volunteer rejection']);

        $notifier->notify($delivery['donor_id'], 'delivery_cancelled',
            "Delivery for \"{$food}\" was cancelled" . ($reason ? ": {$reason}" : "") . '. A new delivery request has been created.',
            'dashboard.php?page=manage-donation',
            ['priority' => 1, 'channels' => ['in_app', 'email']]
        );
        $notifier->notify($delivery['consumer_id'], 'delivery_cancelled',
            "Delivery for \"{$food}\" was cancelled" . ($reason ? ": {$reason}" : "") . '. A new volunteer will be assigned shortly.',
            'dashboard.php?page=track-request',
            ['priority' => 1, 'channels' => ['in_app']]
        );
    }
}
