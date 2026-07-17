<?php
/**
 * chatbot/admin/chatbot_dashboard.php
 * Main Admin Chatbot Dashboard for the Sayog AI Chatbot.
 * 
 * Provides a quick overview of chatbot status, stats, and quick actions.
 * Serves as the landing page for admin chatbot section.
 */

// Ensure admin access
if (!is_admin_logged_in() && !is_admin()) {
    echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-lock"></i> Admin access required.</div>';
    return;
}

require_once __DIR__ . '/../conversation_logger.php';

$logger = new ConversationLogger($pdo);
$analytics = $logger->getAnalytics();

// Get settings
$bot_enabled = '1';
$bot_name = 'Sayog Assistant';
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM chatbot_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['setting_key'] === 'bot_enabled') $bot_enabled = $row['setting_value'];
        if ($row['setting_key'] === 'bot_name') $bot_name = $row['setting_value'];
    }
} catch (PDOException $e) {}
?>

<div class="section-header">
    <div>
        <h1><i class="fa-solid fa-robot"></i> AI Chatbot Dashboard</h1>
        <p>Manage and monitor your intelligent chatbot assistant for Sayog.</p>
    </div>
</div>

<!-- Status Banner -->
<div class="admin-card" style="margin-bottom:24px;border-left:4px solid <?php echo $bot_enabled === '1' ? '#059669' : '#ef4444'; ?>;">
    <div class="admin-card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="width:56px;height:56px;border-radius:14px;background:<?php echo $bot_enabled === '1' ? 'rgba(5,150,105,0.1)' : 'rgba(239,68,68,0.1)'; ?>;display:flex;align-items:center;justify-content:center;font-size:24px;color:<?php echo $bot_enabled === '1' ? '#059669' : '#ef4444'; ?>;">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div>
                <h3 style="margin:0 0 4px;font-size:18px;">
                    <?php echo htmlspecialchars($bot_name); ?>
                    <span style="font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:<?php echo $bot_enabled === '1' ? '#d1fae5' : '#fee2e2'; ?>;color:<?php echo $bot_enabled === '1' ? '#059669' : '#ef4444'; ?>;margin-left:8px;">
                        <?php echo $bot_enabled === '1' ? '🟢 Active' : '🔴 Disabled'; ?>
                    </span>
                </h3>
                <p style="margin:0;color:var(--admin-text-muted);font-size:13px;">
                    Integrated across public pages, user dashboard, and admin panel. 
                    <a href="admin.php?section=chatbot&tab=settings" style="color:#059669;font-weight:600;">Configure</a>
                </p>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="admin.php?section=chatbot&tab=faq" class="btn btn-outline">
                <i class="fa-solid fa-book"></i> Manage FAQs
            </a>
            <a href="admin.php?section=chatbot&tab=logs" class="btn btn-outline">
                <i class="fa-solid fa-list"></i> View Logs
            </a>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card-icon green"><i class="fa-solid fa-comment-dots"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['total_conversations']; ?></div>
            <div class="stat-card-label">Messages Processed</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon blue"><i class="fa-solid fa-users-gear"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['unique_users']; ?></div>
            <div class="stat-card-label">Unique Users Helped</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon amber"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo (int)$analytics['conversations_today']; ?></div>
            <div class="stat-card-label">Today's Activity</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon purple"><i class="fa-solid fa-brain"></i></div>
        <div class="stat-card-body">
            <div class="stat-card-value"><?php echo count($analytics['top_intents'] ?? []); ?></div>
            <div class="stat-card-label">Intent Categories</div>
        </div>
    </div>
</div>

<!-- Quick Action Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:24px;">
    <a href="admin.php?section=chatbot&tab=faq" style="text-decoration:none;">
        <div class="stat-card" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="stat-card-icon green"><i class="fa-solid fa-book"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-value">FAQ Manager</div>
                <div class="stat-card-label">Add, edit, or remove chatbot knowledge</div>
            </div>
        </div>
    </a>
    <a href="admin.php?section=chatbot&tab=settings" style="text-decoration:none;">
        <div class="stat-card" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="stat-card-icon blue"><i class="fa-solid fa-sliders"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-value">Settings</div>
                <div class="stat-card-label">Config UI, AI model, rate limits</div>
            </div>
        </div>
    </a>
    <a href="admin.php?section=chatbot&tab=logs" style="text-decoration:none;">
        <div class="stat-card" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="stat-card-icon amber"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-value">Conversation Logs</div>
                <div class="stat-card-label">View and manage chat history</div>
            </div>
        </div>
    </a>
    <a href="admin.php?section=chatbot&tab=analytics" style="text-decoration:none;">
        <div class="stat-card" style="cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="stat-card-icon purple"><i class="fa-solid fa-chart-line"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-value">Analytics</div>
                <div class="stat-card-label">Usage insights and reports</div>
            </div>
        </div>
    </a>
</div>

<!-- Top Intents Preview -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-chart-bar"></i> Most Asked Questions</h3>
        <a href="admin.php?section=chatbot&tab=analytics" class="btn btn-sm btn-outline">Full Analytics</a>
    </div>
    <div class="admin-card-body">
        <?php 
        $top = $analytics['top_intents'] ?? [];
        if (empty($top)): 
        ?>
            <div class="empty-state" style="padding:20px;text-align:center;">
                <i class="fa-solid fa-chart-simple" style="font-size:36px;color:#cbd5e1;margin-bottom:8px;"></i>
                <p style="color:var(--admin-text-muted);font-size:14px;">No conversation data yet. Start chatting with the bot!</p>
            </div>
        <?php else: 
            $max_val = max(array_column($top, 'count'));
            $max_val = $max_val > 0 ? $max_val : 1;
            foreach (array_slice($top, 0, 5) as $item): 
                $pct = ($item['count'] / $max_val) * 100;
        ?>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div style="font-size:13px;font-weight:600;color:var(--admin-text);width:140px;flex-shrink:0;text-transform:capitalize;">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $item['intent'])); ?>
                    </div>
                    <div style="flex:1;height:16px;background:var(--admin-bg-light);border-radius:8px;overflow:hidden;">
                        <div style="height:100%;width:<?php echo $pct; ?>%;background:linear-gradient(90deg,#059669,#34d399);border-radius:8px;transition:width 0.5s;"></div>
                    </div>
                    <div style="font-size:14px;font-weight:700;color:var(--admin-text);"><?php echo (int)$item['count']; ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
