<?php
/**
 * chatbot/admin/chatbot_analytics.php
 * Admin Chatbot Analytics Dashboard for the Sayog AI Chatbot.
 * 
 * Provides visual analytics:
 * - Total conversations over time
 * - Top intents
 * - User engagement metrics
 * - Daily/weekly activity
 */

// Ensure admin access
if (!is_admin_logged_in() && !is_admin()) {
    echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-lock"></i> Admin access required.</div>';
    return;
}

require_once __DIR__ . '/../conversation_logger.php';

$logger = new ConversationLogger($pdo);
$analytics = $logger->getAnalytics();

// Get daily activity for last 7 days
$daily = [];
try {
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM chatbot_logs 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
    $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Get top intents data
$top_intents = $analytics['top_intents'] ?? [];
?>

<div class="section-header">
    <div>
        <h1><i class="fa-solid fa-chart-line"></i> Chatbot Analytics</h1>
        <p>Understand how users are interacting with the AI chatbot.</p>
    </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="fa-solid fa-comment-dots"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['total_conversations']; ?></div>
            <div class="stat-card-label">Total Messages</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['unique_users']; ?></div>
            <div class="stat-card-label">Engaged Users</div>
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
        <div class="stat-card-icon purple"><i class="fa-solid fa-calendar-week"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['conversations_this_week']; ?></div>
            <div class="stat-card-label">This Week</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
    <!-- Daily Activity -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-chart-simple"></i> Daily Activity (Last 7 Days)</h3>
        </div>
        <div class="admin-card-body">
            <?php if (empty($daily)): ?>
                <div class="empty-state" style="padding:30px;text-align:center;">
                    <i class="fa-solid fa-chart-line" style="font-size:36px;color:#cbd5e1;margin-bottom:12px;"></i>
                    <p style="color:var(--admin-text-muted);">No activity data yet.</p>
                </div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php 
                    $max_count = max(array_column($daily, 'count'));
                    $max_count = $max_count > 0 ? $max_count : 1;
                    foreach ($daily as $day): 
                        $pct = ($day['count'] / $max_count) * 100;
                        $day_name = date('D', strtotime($day['date']));
                        $date_str = date('M d', strtotime($day['date']));
                    ?>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="font-size:12px;font-weight:600;color:var(--admin-text-muted);width:80px;flex-shrink:0;"><?php echo "$day_name $date_str"; ?></div>
                            <div style="flex:1;height:24px;background:var(--admin-bg-light);border-radius:6px;overflow:hidden;">
                                <div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#059669,#10b981);border-radius:6px;transition:width 0.5s;"></div>
                            </div>
                            <div style="font-size:13px;font-weight:700;color:var(--admin-text);width:30px;text-align:right;"><?php echo (int)$day['count']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Intents -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fa-solid fa-brain"></i> Top User Intents</h3>
        </div>
        <div class="admin-card-body">
            <?php if (empty($top_intents)): ?>
                <div class="empty-state" style="padding:30px;text-align:center;">
                    <i class="fa-solid fa-list" style="font-size:36px;color:#cbd5e1;margin-bottom:12px;"></i>
                    <p style="color:var(--admin-text-muted);">No intent data yet.</p>
                </div>
            <?php else: 
                $max_intent = max(array_column($top_intents, 'count'));
                $max_intent = $max_intent > 0 ? $max_intent : 1;
                foreach ($top_intents as $intent): 
                    $pct = ($intent['count'] / $max_intent) * 100;
            ?>
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                        <div style="font-size:12px;font-weight:600;color:var(--admin-text);width:140px;flex-shrink:0;text-transform:capitalize;"><?php echo htmlspecialchars(str_replace('_', ' ', $intent['intent'])); ?></div>
                        <div style="flex:1;height:20px;background:var(--admin-bg-light);border-radius:6px;overflow:hidden;">
                            <div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:6px;"></div>
                        </div>
                        <div style="font-size:13px;font-weight:700;color:var(--admin-text);width:30px;text-align:right;"><?php echo (int)$intent['count']; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- User Demographics -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-users"></i> User Engagement Summary</h3>
    </div>
    <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;">
            <div style="text-align:center;padding:20px;background:var(--admin-bg-light);border-radius:12px;">
                <div style="font-size:28px;font-weight:800;color:#059669;"><?php echo (int)$analytics['guest_percentage']; ?>%</div>
                <div style="font-size:13px;color:var(--admin-text-muted);margin-top:4px;">Guest Users</div>
            </div>
            <div style="text-align:center;padding:20px;background:var(--admin-bg-light);border-radius:12px;">
                <div style="font-size:28px;font-weight:800;color:#6366f1;"><?php echo 100 - (int)$analytics['guest_percentage']; ?>%</div>
                <div style="font-size:13px;color:var(--admin-text-muted);margin-top:4px;">Logged-in Users</div>
            </div>
            <div style="text-align:center;padding:20px;background:var(--admin-bg-light);border-radius:12px;">
                <div style="font-size:28px;font-weight:800;color:#f59e0b;"><?php echo count($top_intents); ?></div>
                <div style="font-size:13px;color:var(--admin-text-muted);margin-top:4px;">Unique Intents</div>
            </div>
            <div style="text-align:center;padding:20px;background:var(--admin-bg-light);border-radius:12px;">
                <div style="font-size:28px;font-weight:800;color:#10b981;"><?php 
                    $avg = 0;
                    if (!empty($daily)) {
                        $total_d = array_sum(array_column($daily, 'count'));
                        $days_c = count($daily);
                        $avg = $days_c > 0 ? round($total_d / $days_c, 1) : 0;
                    }
                    echo $avg;
                ?></div>
                <div style="font-size:13px;color:var(--admin-text-muted);margin-top:4px;">Avg Daily Messages</div>
            </div>
        </div>
    </div>
</div>
