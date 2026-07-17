<?php
/**
 * chatbot/conversation_logger.php
 * Conversation Logger for the Sayog AI Chatbot.
 * 
 * Logs all chatbot conversations to the database for:
 * - Analytics and reporting
 * - Quality monitoring
 * - Debugging and improvement
 * - Admin review
 * 
 * Security: No sensitive data (passwords, tokens, OTPs) is logged.
 * The logger strips known sensitive patterns from messages before saving.
 */

class ConversationLogger {

    private $pdo;

    /**
     * Patterns that indicate sensitive data in messages.
     */
    private static $sensitive_patterns = [
        '/\b(\d{4}\s*){4}\b/',           // Credit card numbers
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/',  // Emails (keep for context but we log anonymized)
        '/\b\d{10}\b/',                   // Simple 10-digit numbers
        '/\b(98|97|96)\d{8}\b/',         // Nepal phone numbers
        '/\b(otp|OTP|password|Password)\s*[:=]\s*\S+/i',  // OTPs & passwords
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Log a conversation entry to the database.
     *
     * @param int    $user_id      The user ID (0 for guests).
     * @param string $user_message The user's original message.
     * @param string $bot_response The chatbot's response.
     * @param string $intent       The detected intent.
     * @param string $user_role    The user's role.
     * @return bool True on success.
     */
    public function log($user_id, $user_message, $bot_response, $intent, $user_role) {
        try {
            // Ensure table exists
            $this->ensure_table();

            // Sanitize for logging
            $user_message = $this->sanitize_for_log($user_message);
            $bot_response = strip_tags($bot_response); // Remove HTML for log storage
            $user_id = (int)$user_id;
            $intent = substr($intent, 0, 50);
            $user_role = substr($user_role, 0, 20);
            $ip_address = $this->get_anonymized_ip();

            $stmt = $this->pdo->prepare("
                INSERT INTO chatbot_logs 
                (user_id, user_message, bot_response, intent, user_role, ip_address, page_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $user_message,
                $bot_response,
                $intent,
                $user_role,
                $ip_address,
                $_SERVER['REQUEST_URI'] ?? '',
            ]);

            return true;
        } catch (PDOException $e) {
            // Silently fail - logging should never break the user experience
            return false;
        }
    }

    /**
     * Get conversation logs for a specific user.
     *
     * @param int    $user_id The user ID.
     * @param int    $limit   Number of logs to fetch.
     * @return array
     */
    public function getUserLogs($user_id, $limit = 20) {
        try {
            $this->ensure_table();
            $stmt = $this->pdo->prepare("
                SELECT * FROM chatbot_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([(int)$user_id, (int)$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get all conversation logs (admin only).
     *
     * @param int    $limit  Number of logs to fetch.
     * @param int    $offset Offset for pagination.
     * @return array
     */
    public function getAllLogs($limit = 50, $offset = 0) {
        try {
            $this->ensure_table();
            $stmt = $this->pdo->prepare("
                SELECT l.*, u.name AS user_name, u.email AS user_email 
                FROM chatbot_logs l
                LEFT JOIN users u ON l.user_id = u.id
                ORDER BY l.created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([(int)$limit, (int)$offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get conversation analytics data.
     *
     * @return array
     */
    public function getAnalytics() {
        $data = [
            'total_conversations' => 0,
            'unique_users' => 0,
            'top_intents' => [],
            'conversations_today' => 0,
            'conversations_this_week' => 0,
            'guest_percentage' => 0,
        ];

        try {
            $this->ensure_table();

            $data['total_conversations'] = (int)$this->pdo->query("
                SELECT COUNT(*) FROM chatbot_logs
            ")->fetchColumn();

            $data['unique_users'] = (int)$this->pdo->query("
                SELECT COUNT(DISTINCT user_id) FROM chatbot_logs WHERE user_id > 0
            ")->fetchColumn();

            $data['conversations_today'] = (int)$this->pdo->query("
                SELECT COUNT(*) FROM chatbot_logs WHERE DATE(created_at) = CURDATE()
            ")->fetchColumn();

            $data['conversations_this_week'] = (int)$this->pdo->query("
                SELECT COUNT(*) FROM chatbot_logs 
                WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
            ")->fetchColumn();

            // Top intents
            $stmt = $this->pdo->query("
                SELECT intent, COUNT(*) as count 
                FROM chatbot_logs 
                WHERE intent != '' 
                GROUP BY intent 
                ORDER BY count DESC 
                LIMIT 10
            ");
            $data['top_intents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Guest percentage
            $total = $data['total_conversations'];
            if ($total > 0) {
                $guest_count = (int)$this->pdo->query("
                    SELECT COUNT(*) FROM chatbot_logs WHERE user_id = 0
                ")->fetchColumn();
                $data['guest_percentage'] = round(($guest_count / $total) * 100, 1);
            }

        } catch (PDOException $e) {
            // Return defaults
        }

        return $data;
    }

    /**
     * Delete old logs (admin function).
     *
     * @param int $days_old Delete logs older than this many days.
     * @return int Number of deleted rows.
     */
    public function purgeOldLogs($days_old = 90) {
        try {
            $this->ensure_table();
            $stmt = $this->pdo->prepare("
                DELETE FROM chatbot_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([(int)$days_old]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Ensure the chatbot_logs table exists.
     */
    private function ensure_table() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `chatbot_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NOT NULL DEFAULT 0,
                `user_message` TEXT NOT NULL,
                `bot_response` TEXT NOT NULL,
                `intent` VARCHAR(50) DEFAULT NULL,
                `user_role` VARCHAR(20) DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `page_url` VARCHAR(500) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_user` (`user_id`),
                INDEX `idx_intent` (`intent`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Sanitize a message for logging (strip sensitive patterns).
     */
    private function sanitize_for_log($message) {
        foreach (self::$sensitive_patterns as $pattern) {
            $message = preg_replace($pattern, '[REDACTED]', $message);
        }
        return substr($message, 0, 1000); // Limit length
    }

    /**
     * Get anonymized IP address (last octet removed).
     */
    private function get_anonymized_ip() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '0';
                return implode('.', $parts);
            }
        }
        return '0.0.0.0';
    }
}
