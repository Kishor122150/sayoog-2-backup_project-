<?php
namespace App\Services;

use App\Config\DeliveryStates;
use PDO;

/**
 * Analytics Service
 * 
 * Time-series aggregation for delivery matching analytics.
 * Pre-computes metrics and caches them in the analytics_cache table.
 * Dashboard reads from cache, not live aggregation queries.
 */
class AnalyticsService {
    
    private PDO $pdo;
    const CACHE_TTL_HOURS = 4; // Re-generate cache every 4 hours

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get analytics dashboard data. Returns cached if fresh, regenerates if stale.
     */
    public function getDashboardData(): array {
        $cached = $this->getCached('dashboard');
        if ($cached !== null) {
            return $cached;
        }
        return $this->regenerateDashboardData();
    }

    /**
     * Force regenerate all analytics.
     */
    public function regenerateDashboardData(): array {
        $data = [
            'assignment_methods' => $this->getAssignmentMethodDistribution(),
            'response_times'     => $this->getResponseTimes(),
            'response_by_method' => $this->getResponseTimeByMethod(),
            'volunteer_utilization' => $this->getVolunteerUtilization(),
            'top_volunteers'     => $this->getTopVolunteers(),
            'daily_trends'       => $this->getDailyTrends(14),
            'completion'         => $this->getCompletionRate(),
            'rejection_reasons'  => $this->getRejectionReasons(),
            'geofence_stats'     => $this->getGeofenceStats(),
            'notification_stats' => $this->getNotificationStats(),
            'generated_at'       => date('Y-m-d H:i:s'),
        ];

        $this->cache('dashboard', $data);
        return $data;
    }

    /**
     * Generate daily aggregated snapshot for historical tracking.
     */
    public function recordDailySnapshot(): void {
        $today = date('Y-m-d');
        
        $data = [
            'date' => $today,
            'assignments' => $this->getAssignmentMethodDistribution(),
            'response_times' => $this->getResponseTimes(),
            'volunteers_active' => (int)$this->pdo->query(
                "SELECT COUNT(DISTINCT volunteer_user_id) FROM volunteer_deliveries WHERE DATE(accepted_at) = CURDATE()"
            )->fetchColumn(),
            'deliveries_completed' => (int)$this->pdo->query(
                "SELECT COUNT(*) FROM volunteer_deliveries WHERE status = 'delivered' AND DATE(delivered_at) = CURDATE()"
            )->fetchColumn(),
            'deliveries_created' => (int)$this->pdo->query(
                "SELECT COUNT(*) FROM volunteer_deliveries WHERE DATE(created_at) = CURDATE()"
            )->fetchColumn(),
        ];

        $this->cache("snapshot_{$today}", $data);
    }

    // ── Individual Metric Queries ──

    private function getAssignmentMethodDistribution(): array {
        $methods = [];
        foreach (['auto', 'manual_accept', 'admin_assign', 'reassigned'] as $m) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM volunteer_deliveries WHERE assignment_method = ?");
            $stmt->execute([$m]);
            $methods[$m] = (int)$stmt->fetchColumn();
        }
        $methods['legacy'] = (int)$this->pdo->query("SELECT COUNT(*) FROM volunteer_deliveries WHERE assignment_method IS NULL")->fetchColumn();
        return $methods;
    }

    private function getResponseTimes(): array {
        $stmt = $this->pdo->query("
            SELECT 
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, accepted_at)), 1) AS avg_response_mins,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, accepted_at, delivered_at)), 1) AS avg_transit_mins,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_at)), 1) AS avg_total_mins,
                COUNT(*) AS sample_size
            FROM volunteer_deliveries 
            WHERE accepted_at IS NOT NULL
        ");
        return $stmt->fetch() ?: [];
    }

    private function getResponseTimeByMethod(): array {
        $results = [];
        foreach (['auto', 'manual_accept', 'admin_assign'] as $m) {
            $stmt = $this->pdo->prepare("
                SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, accepted_at)), 1) 
                FROM volunteer_deliveries WHERE assignment_method = ? AND accepted_at IS NOT NULL
            ");
            $stmt->execute([$m]);
            $results[$m] = (float)($stmt->fetchColumn() ?: 0);
        }
        return $results;
    }

    private function getVolunteerUtilization(): array {
        return $this->pdo->query("
            SELECT 
                COUNT(DISTINCT v.user_id) AS total_volunteers,
                COUNT(DISTINCT CASE WHEN vd.id IS NOT NULL THEN v.user_id END) AS active_volunteers,
                SUM(CASE WHEN v.online_status = 'available' THEN 1 ELSE 0 END) AS available_now,
                SUM(CASE WHEN v.online_status = 'busy' THEN 1 ELSE 0 END) AS busy_now,
                ROUND(COUNT(DISTINCT CASE WHEN vd.id IS NOT NULL THEN v.user_id END) 
                    / NULLIF(COUNT(DISTINCT v.user_id), 0) * 100, 1) AS utilization_pct
            FROM volunteers v
            LEFT JOIN volunteer_deliveries vd ON v.user_id = vd.volunteer_user_id 
                AND vd.status IN ('accepted','picked_up','in_transit','delivered')
            WHERE v.status = 'approved'
        ")->fetch() ?: [];
    }

    private function getTopVolunteers(): array {
        return $this->pdo->query("
            SELECT vu.name AS volunteer_name, v.user_id, v.rating, v.completed_deliveries,
                   v.community_points, v.vehicle_type, v.online_status,
                   COUNT(vd.id) AS total_assigned,
                   SUM(CASE WHEN vd.status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
                   ROUND(AVG(TIMESTAMPDIFF(MINUTE, vd.accepted_at, vd.delivered_at)), 1) AS avg_delivery_time
            FROM volunteers v
            JOIN users vu ON v.user_id = vu.id
            LEFT JOIN volunteer_deliveries vd ON v.user_id = vd.volunteer_user_id
            WHERE v.status = 'approved'
            GROUP BY v.user_id
            ORDER BY v.completed_deliveries DESC, v.rating DESC
            LIMIT 15
        ")->fetchAll();
    }

    private function getDailyTrends(int $days): array {
        $stmt = $this->pdo->prepare("
            SELECT DATE(created_at) AS date, COUNT(*) AS total_created,
                   SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
                   SUM(CASE WHEN assignment_method = 'auto' THEN 1 ELSE 0 END) AS auto_assigned,
                   SUM(CASE WHEN assignment_method = 'manual_accept' THEN 1 ELSE 0 END) AS manual_accepted
            FROM volunteer_deliveries
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->bindValue(1, $days, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getCompletionRate(): array {
        $stmt = $this->pdo->query("
            SELECT 
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
                ROUND(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) 
                    / NULLIF(SUM(CASE WHEN status IN ('delivered','cancelled') THEN 1 ELSE 0 END), 0) * 100, 1) AS completion_rate
            FROM volunteer_deliveries WHERE status IN ('delivered', 'cancelled')
        ");
        $result = $stmt->fetch() ?: [];
        $result['total'] = (int)($result['completed'] ?? 0) + (int)($result['cancelled_count'] ?? 0);
        return $result;
    }

    private function getRejectionReasons(): array {
        return $this->pdo->query("
            SELECT cancellation_reason, COUNT(*) AS count
            FROM volunteer_deliveries 
            WHERE cancellation_reason IS NOT NULL AND cancellation_reason != ''
            GROUP BY cancellation_reason ORDER BY count DESC LIMIT 5
        ")->fetchAll();
    }

    private function getGeofenceStats(): array {
        return $this->pdo->query("
            SELECT COUNT(*) AS total, 
                   SUM(CASE WHEN fence_type='pickup' THEN 1 ELSE 0 END) AS pickup_triggers,
                   SUM(CASE WHEN fence_type='dropoff' THEN 1 ELSE 0 END) AS dropoff_triggers
            FROM geofence_log
        ")->fetch() ?: [];
    }

    private function getNotificationStats(): array {
        return $this->pdo->query("
            SELECT 
                COUNT(*) AS total_queued,
                SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending
            FROM notification_queue
        ")->fetch() ?: [];
    }

    // ── Cache Management ──

    private function getCached(string $key): ?array {
        $stmt = $this->pdo->prepare(
            "SELECT cache_value, generated_at FROM analytics_cache WHERE cache_key = ?"
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $generated = strtotime($row['generated_at']);
        if (time() - $generated > self::CACHE_TTL_HOURS * 3600) {
            return null; // Stale, force regenerate
        }

        return json_decode($row['cache_value'], true);
    }

    private function cache(string $key, array $data): void {
        $this->pdo->prepare(
            "INSERT INTO analytics_cache (cache_key, cache_value, period_start, period_end) 
             VALUES (?, ?, CURDATE() - INTERVAL 30 DAY, CURDATE())
             ON DUPLICATE KEY UPDATE cache_value = VALUES(cache_value), generated_at = NOW()"
        )->execute([$key, json_encode($data)]);
    }

    /**
     * Cleanup old snapshots (keep last 90 days).
     */
    public function cleanOldSnapshots(): int {
        $stmt = $this->pdo->query("
            DELETE FROM analytics_cache 
            WHERE cache_key LIKE 'snapshot_%' 
            AND generated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        return $stmt->rowCount();
    }
}
