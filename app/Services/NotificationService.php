<?php
namespace App\Services;

use PDO;

/**
 * Notification Service
 * Channel-agnostic notification pipeline with queue, retry, and priority management.
 * Channels: in_app (DB notifications), email, sms, whatsapp
 * All notifications are queued and processed asynchronously.
 */
class NotificationService {
    
    private PDO $pdo;
    
    // Channel priority: higher = more urgent
    const CHANNEL_PRIORITY = [
        'in_app' => 0,
        'email'  => 1,
        'sms'    => 2,
        'whatsapp' => 2,
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Send a notification. Queues to appropriate channels.
     */
    public function notify(
        int $userId,
        string $type,
        string $message,
        ?string $link = null,
        array $options = []
    ): void {
        $priority = (int)($options['priority'] ?? 0);
        $channels = $options['channels'] ?? ['in_app'];
        $subject = $options['subject'] ?? ucfirst(str_replace('_', ' ', $type));

        foreach ($channels as $channel) {
            $this->queue($userId, $channel, $type, $subject, $message, $link, $priority);
        }
    }

    /**
     * Queue a notification for async delivery.
     */
    public function queue(
        int $userId, string $channel, string $type,
        ?string $subject, string $body, ?string $link = null,
        int $priority = 0
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notification_queue (user_id, channel, type, subject, body, link, priority, status, scheduled_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        $stmt->execute([$userId, $channel, $type, $subject, $body, $link, $priority]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Process the notification queue. Call from cron job.
     * Processes high-priority first, respects rate limits.
     */
    public function processQueue(int $batchSize = 50): array {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        $stmt = $this->pdo->prepare(
            "SELECT nq.*, u.email, u.phone, u.name 
             FROM notification_queue nq 
             JOIN users u ON nq.user_id = u.id 
             WHERE nq.status = 'pending' 
               AND (nq.scheduled_at IS NULL OR nq.scheduled_at <= NOW())
               AND nq.retry_count < nq.max_retries
             ORDER BY nq.priority DESC, nq.created_at ASC 
             LIMIT ?"
        );
        $stmt->bindValue(1, $batchSize, \PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll();

        foreach ($notifications as $n) {
            try {
                $success = match ($n['channel']) {
                    'in_app' => $this->sendInApp($n),
                    'email'  => $this->sendEmail($n),
                    'sms'    => $this->sendSms($n),
                    'whatsapp' => $this->sendWhatsApp($n),
                    default => false,
                };

                if ($success) {
                    $this->pdo->prepare("UPDATE notification_queue SET status = 'sent', sent_at = NOW() WHERE id = ?")
                        ->execute([$n['id']]);
                    $results['sent']++;
                } else {
                    throw new \Exception("Channel {$n['channel']} returned false");
                }
            } catch (\Exception $e) {
                $retryCount = (int)$n['retry_count'] + 1;
                $status = $retryCount >= (int)$n['max_retries'] ? 'failed' : 'pending';
                $this->pdo->prepare(
                    "UPDATE notification_queue SET retry_count = ?, status = ?, last_error = ? WHERE id = ?"
                )->execute([$retryCount, $status, $e->getMessage(), $n['id']]);
                $results['failed']++;
                $results['errors'][] = "{$n['id']}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Mark old notifications as cancelled to prevent queue bloat.
     */
    public function cleanOldQueued(int $olderThanHours = 48): int {
        $stmt = $this->pdo->prepare(
            "UPDATE notification_queue SET status = 'cancelled' 
             WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)"
        );
        $stmt->execute([$olderThanHours]);
        return $stmt->rowCount();
    }

    // ── Channel Senders ──

    private function sendInApp(array $n): bool {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$n['user_id'], $n['type'], $n['body'], $n['link']]);
        return true;
    }

    private function sendEmail(array $n): bool {
        // Use existing send_email_smtp function from config.php
        if (function_exists('send_email_smtp')) {
            return send_email_smtp($this->pdo, $n['email'], $n['subject'], $n['body'], null);
        }
        return false;
    }

    private function sendSms(array $n): bool {
        // SMS integration placeholder — implement with Nepal SMS provider
        // e.g., https://sparrowsms.com or https://bulksmsnepal.com
        static $configChecked = false;
        static $smsProvider = null;
        
        if (!$configChecked) {
            try {
                $stmt = $this->pdo->query("SELECT value FROM settings WHERE key = 'sms_provider'");
                $smsProvider = $stmt ? $stmt->fetchColumn() : null;
            } catch (\Exception $e) {
                $smsProvider = null;
            }
            $configChecked = true;
        }
        
        if (empty($smsProvider)) return false;
        
        // Future: dispatch to SMS provider API
        return false;
    }

    private function sendWhatsApp(array $n): bool {
        // WhatsApp integration — requires WhatsApp Business API setup
        // Check if configured before attempting
        static $configChecked = false;
        static $whatsappConfigured = null;
        
        if (!$configChecked) {
            try {
                $stmt = $this->pdo->query("SELECT value FROM settings WHERE key = 'whatsapp_api_key'");
                $whatsappConfigured = $stmt ? (bool)$stmt->fetchColumn() : false;
            } catch (\Exception $e) {
                $whatsappConfigured = false;
            }
            $configChecked = true;
        }
        
        if (!$whatsappConfigured) return false;
        
        $phone = preg_replace('/[^0-9]/', '', $n['phone'] ?? '');
        if (empty($phone)) return false;
        
        // Future: call WhatsApp Business API
        return false;
    }

    /**
     * Get notification statistics for analytics.
     */
    public function getStats(): array {
        return [
            'pending' => (int)$this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE status = 'pending'")->fetchColumn(),
            'sent_today' => (int)$this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE status = 'sent' AND DATE(sent_at) = CURDATE()")->fetchColumn(),
            'failed' => (int)$this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE status = 'failed'")->fetchColumn(),
            'by_channel' => $this->pdo->query("SELECT channel, COUNT(*) as count, SUM(CASE WHEN status='sent' THEN 1 ELSE 0 END) as sent FROM notification_queue GROUP BY channel")->fetchAll(),
        ];
    }
}
