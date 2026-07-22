<?php
namespace App\Controllers;

use App\Services\DeliveryStateMachine;
use App\Services\VolunteerMatchingService;
use PDO;

/**
 * Delivery Controller
 * REST API endpoints for the volunteer delivery system.
 * Returns JSON responses for AJAX consumption.
 */
class DeliveryController {
    
    private PDO $pdo;
    private DeliveryStateMachine $stateMachine;
    private VolunteerMatchingService $matcher;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->stateMachine = new DeliveryStateMachine($pdo);
        $this->matcher = new VolunteerMatchingService($pdo);
    }

    /**
     * GET /api/deliveries/{id} - Get delivery details with event history
     */
    public function show(int $id): array {
        $stmt = $this->pdo->prepare("
            SELECT vd.*, d.food_item, d.quantity, d.pickup_address,
                   u.name AS donor_name, cu.name AS consumer_name,
                   vu.name AS volunteer_name, vol.vehicle_type, vol.phone AS volunteer_phone
            FROM volunteer_deliveries vd
            JOIN donations d ON vd.donation_id = d.id
            JOIN users u ON vd.donor_id = u.id
            JOIN users cu ON vd.consumer_id = cu.id
            LEFT JOIN users vu ON vd.volunteer_user_id = vu.id
            LEFT JOIN volunteers vol ON vd.volunteer_user_id = vol.user_id
            WHERE vd.id = ?
        ");
        $stmt->execute([$id]);
        $delivery = $stmt->fetch();

        if (!$delivery) {
            return ['success' => false, 'error' => 'Delivery not found'];
        }

        $delivery['events'] = $this->stateMachine->getEventHistory($id);
        $delivery['can_transition'] = $this->getAvailableTransitions($delivery);

        return ['success' => true, 'delivery' => $delivery];
    }

    /**
     * POST /api/deliveries/{id}/transition - Update delivery status
     */
    public function transition(int $id, array $data, string $actorType = 'system', ?int $actorId = null): array {
        $newStatus = $data['status'] ?? '';
        $notes = $data['notes'] ?? null;
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;

        $result = $this->stateMachine->transition(
            $id, $newStatus, $actorType, $actorId,
            $lat, $lng, $notes,
            ['source' => 'api', 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown']
        );

        return $result;
    }

    /**
     * GET /api/deliveries/{id}/match - Get matching scores
     */
    public function matchScores(int $id): array {
        $candidates = $this->matcher->scoreAllCandidates($id);
        $best = !empty($candidates) ? $candidates[0] : null;

        return [
            'success' => true,
            'candidates_count' => count($candidates),
            'best_match' => $best ? [
                'volunteer_name' => $best['volunteer']['full_name'],
                'score' => $best['total_score'],
                'scores' => $best['scores'],
            ] : null,
        ];
    }

    /**
     * POST /api/deliveries/{id}/auto-assign - Trigger auto-assignment
     */
    public function autoAssign(int $id): array {
        $result = $this->matcher->autoAssign($id);
        if ($result) {
            return ['success' => true, 'assignment' => $result];
        }
        return ['success' => false, 'error' => 'No suitable volunteer found'];
    }

    /**
     * GET /api/deliveries/available - Get all available deliveries for volunteer hub
     */
    public function availableDeliveries(?int $volunteerUserId = null): array {
        $sql = "
            SELECT vd.*, d.food_item, d.quantity, d.pickup_address, d.expiry_time,
                   u.name AS donor_name
            FROM volunteer_deliveries vd
            JOIN donations d ON vd.donation_id = d.id
            JOIN users u ON vd.donor_id = u.id
            WHERE vd.status = 'assigned' AND vd.volunteer_user_id IS NULL
              AND d.expiry_time > NOW()
              AND d.status = 'accepted'
            ORDER BY d.created_at DESC
            LIMIT 50
        ";
        $deliveries = $this->pdo->query($sql)->fetchAll();

        // If volunteer specified, filter by proximity
        if ($volunteerUserId && !empty($deliveries)) {
            $matcher = new VolunteerMatchingService($this->pdo);
            $scored = [];
            foreach ($deliveries as $d) {
                $scores = $matcher->scoreAllCandidates((int)$d['id']);
                $myScore = null;
                foreach ($scores as $s) {
                    if ((int)$s['volunteer']['user_id'] === $volunteerUserId) {
                        $myScore = $s;
                        break;
                    }
                }
                if ($myScore) {
                    $d['_match_score'] = $myScore['total_score'];
                    $d['_scores'] = $myScore['scores'];
                }
            }
            usort($deliveries, fn($a, $b) => ($b['_match_score'] ?? 0) <=> ($a['_match_score'] ?? 0));
        }

        return ['success' => true, 'deliveries' => $deliveries, 'count' => count($deliveries)];
    }

    // ── Private ──

    private function getAvailableTransitions(array $delivery): array {
        $transitions = \App\Config\DeliveryStates::getTransitions();
        $current = $delivery['status'];
        $available = [];

        foreach (($transitions[$current] ?? []) as $to => $config) {
            $available[] = [
                'status' => $to,
                'label' => \App\Config\DeliveryStates::getLabel($to),
                'requires_gps' => $config['requires_gps'],
                'notes_required' => $config['notes_required'],
            ];
        }

        return $available;
    }
}
