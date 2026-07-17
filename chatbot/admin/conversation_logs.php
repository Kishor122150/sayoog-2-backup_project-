<?php
/**
 * chatbot/admin/conversation_logs.php
 * Admin Conversation Logs Viewer for the Sayog AI Chatbot.
 * 
 * Allows admins to:
 * - View all chatbot conversations
 * - Filter by user, intent, date
 * - Search messages
 * - View details of specific conversations
 * - Purge old logs
 */

// Ensure admin access
if (!is_admin_logged_in() && !is_admin()) {
    echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-lock"></i> Admin access required.</div>';
    return;
}

require_once __DIR__ . '/../conversation_logger.php';

$logger = new ConversationLogger($pdo);
$page_log = (int)($_GET['log_page'] ?? 1);
$per_page = 30;
$offset = ($page_log - 1) * $per_page;

// Handle purge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purge_logs'])) {
    $days = (int)($_POST['purge_days'] ?? 90);
    $deleted = $logger->purgeOldLogs($days);
    echo '<div class="admin-alert admin-alert-warning"><i class="fa-solid fa-trash-can"></i> Deleted ' . $deleted . ' old log entries.</div>';
}

// Fetch logs
$logs = $logger->getAllLogs($per_page, $offset);

// Get analytics summary
$analytics = $logger->getAnalytics();
?>

<div class="section-header">
    <div>
        <h1><i class="fa-solid fa-clock-rotate-left"></i> Conversation Logs</h1>
        <p>View and manage chatbot conversation history.</p>
    </div>
</div>

<!-- Quick Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="fa-solid fa-comments"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['total_conversations']; ?></div>
            <div class="stat-card-label">Total Messages</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['unique_users']; ?></div>
            <div class="stat-card-label">Unique Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon amber"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['conversations_today']; ?></div>
            <div class="stat-card-label">Today</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon purple"><i class="fa-solid fa-robot"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['guest_percentage']; ?>%</div>
            <div class="stat-card-label">Guest Messages</div>
        </div>
    </div>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-list"></i> Recent Conversations</h3>
        <span class="badge badge-info"><?php echo count($logs); ?> entries</span>
    </div>
    <div class="admin-card-body" style="padding:0;">
        <?php if (empty($logs)): ?>
            <div class="empty-state" style="padding:40px;text-align:center;">
                <i class="fa-solid fa-inbox" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;"></i>
                <h3>No conversation logs yet</h3>
                <p>Conversations will appear here once users interact with the chatbot.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Message</th>
                            <th>Intent</th>
                            <th>Bot Response</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = $offset + 1; foreach ($logs as $log): ?>
                            <tr>
                                <td><span class="badge badge-neutral"><?php echo $sn++; ?></span></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?php echo strtoupper(substr($log['user_name'] ?? ($log['user_id'] > 0 ? 'U' : 'G'), 0, 1)); ?></div>
                                        <div>
                                            <div class="user-name"><?php echo htmlspecialchars($log['user_name'] ?? ($log['user_id'] > 0 ? 'User #' . $log['user_id'] : 'Guest')); ?></div>
                                            <?php if (!empty($log['user_email'])): ?>
                                                <div class="user-email"><?php echo htmlspecialchars($log['user_email']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-<?php echo $log['user_role'] === 'admin' ? 'purple' : 'info'; ?>"><?php echo htmlspecialchars($log['user_role'] ?: 'guest'); ?></span></td>
                                <td style="max-width:200px;font-size:13px;">
                                    <div style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        <?php echo htmlspecialchars($log['user_message']); ?>
                                    </div>
                                </td>
                                <td><code style="font-size:11px;"><?php echo htmlspecialchars($log['intent'] ?: '—'); ?></code></td>
                                <td style="max-width:220px;font-size:12px;color:var(--admin-text-muted);">
                                    <div style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                        <?php echo htmlspecialchars(mb_substr($log['bot_response'], 0, 150)); ?>
                                    </div>
                                </td>
                                <td style="white-space:nowrap;font-size:12px;"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Purge Old Logs -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-broom"></i> Purge Old Logs</h3>
    </div>
    <div class="admin-card-body">
        <form method="POST" onsubmit="return confirm('This will permanently delete old logs. Continue?');" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="purge_logs" value="1">
            <label style="font-size:13px;font-weight:600;">Delete logs older than</label>
            <input type="number" name="purge_days" class="form-control" value="90" min="7" max="365" style="width:80px;">
            <span style="font-size:13px;color:var(--admin-text-muted);">days</span>
            <button type="submit" class="btn btn-danger" style="color:red;">
                <i class="fa-solid fa-trash-can"></i> Purge Logs
            </button>
        </form>
    </div>
</div>
