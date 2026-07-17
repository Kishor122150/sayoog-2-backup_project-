<?php
/**
 * chatbot/admin/chatbot_settings.php
 * Admin Chatbot Settings Manager for the Sayog AI Chatbot.
 * 
 * Allows admins to configure:
 * - Bot name and appearance
 * - Welcome message
 * - Default suggestions
 * - Rate limiting
 * - Log retention
 * - AI model settings (for future use)
 */

// Ensure admin access
if (!is_admin_logged_in() && !is_admin()) {
    echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-lock"></i> Admin access required.</div>';
    return;
}

// Ensure settings table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chatbot_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT DEFAULT NULL,
            `setting_type` VARCHAR(20) NOT NULL DEFAULT 'text',
            `description` VARCHAR(255) DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed default settings if empty
    $check = $pdo->query("SELECT COUNT(*) FROM chatbot_settings")->fetchColumn();
    if ($check == 0) {
        $defaults = [
            ['bot_name', 'Sayog Assistant', 'text', 'Name displayed in the chat header'],
            ['welcome_message', '👋 Hello! I\'m Sayog Assistant, your AI-powered guide. I can help you with donations, food requests, registration, and more!', 'textarea', 'Welcome message shown on first open'],
            ['default_suggestions', 'What is Sayog?, How to donate food, Available food, Platform Statistics', 'text', 'Comma-separated default suggestion buttons'],
            ['max_message_length', '500', 'number', 'Maximum message length allowed'],
            ['log_retention_days', '90', 'number', 'Days to keep conversation logs'],
            ['rate_limit_messages', '20', 'number', 'Max messages per session'],
            ['ai_model', 'rule_based', 'select', 'AI model to use (rule_based, openai, gemini)'],
            ['ai_api_key', '', 'password', 'API key for external AI service'],
            ['ai_model_name', 'gpt-4o-mini', 'text', 'Model name for external AI service (OpenAI: gpt-4o-mini, gpt-3.5-turbo | Gemini: gemini-2.0-flash, gemini-1.5-pro)'],
            ['bot_enabled', '1', 'boolean', 'Enable/disable the chatbot'],
        ];
        $stmt = $pdo->prepare("INSERT INTO chatbot_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
        foreach ($defaults as $d) {
            $stmt->execute($d);
        }
    }
} catch (PDOException $e) {
    // Table might already exist
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $stmt = $pdo->prepare("UPDATE chatbot_settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8);
                $stmt->execute([$value, $setting_key]);
            }
        }
        echo '<div class="admin-alert admin-alert-success"><i class="fa-solid fa-circle-check"></i> Settings saved successfully.</div>';
    } catch (PDOException $e) {
        echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-circle-xmark"></i> Failed to save settings: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM chatbot_settings ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row;
    }
} catch (PDOException $e) {}
?>

<div class="section-header">
    <div>
        <h1><i class="fa-solid fa-gear"></i> Chatbot Settings</h1>
        <p>Configure your AI chatbot behavior and appearance.</p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-sliders"></i> General Settings</h3>
    </div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <input type="hidden" name="save_settings" value="1">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                <div class="form-group">
                    <label class="form-label">Bot Name</label>
                    <input type="text" name="setting_bot_name" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['bot_name']['setting_value'] ?? 'Sayog Assistant'); ?>"
                           placeholder="Sayog Assistant">
                    <div class="validation-hint">Name displayed in the chat header</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bot Enabled</label>
                    <select name="setting_bot_enabled" class="form-control">
                        <option value="1" <?php echo ($settings['bot_enabled']['setting_value'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo ($settings['bot_enabled']['setting_value'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                    <div class="validation-hint">Globally enable or disable the chatbot</div>
                </div>

                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Welcome Message</label>
                    <textarea name="setting_welcome_message" class="form-control" rows="3"
                              placeholder="Welcome message shown on first open"><?php echo htmlspecialchars($settings['welcome_message']['setting_value'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Suggestions</label>
                    <input type="text" name="setting_default_suggestions" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['default_suggestions']['setting_value'] ?? ''); ?>"
                           placeholder="Comma-separated suggestion buttons">
                    <div class="validation-hint">Separate with commas</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Max Message Length</label>
                    <input type="number" name="setting_max_message_length" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['max_message_length']['setting_value'] ?? '500'); ?>"
                           min="50" max="2000">
                </div>

                <div class="form-group">
                    <label class="form-label">Log Retention (Days)</label>
                    <input type="number" name="setting_log_retention_days" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['log_retention_days']['setting_value'] ?? '90'); ?>"
                           min="7" max="365">
                    <div class="validation-hint">Auto-delete logs older than this</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Rate Limit (Messages/Session)</label>
                    <input type="number" name="setting_rate_limit_messages" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['rate_limit_messages']['setting_value'] ?? '20'); ?>"
                           min="5" max="100">
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--admin-border);margin:24px 0;">

            <h4 style="margin:0 0 16px;color:var(--admin-text);"><i class="fa-solid fa-microchip"></i> AI Model Settings</h4>
            <p style="color:var(--admin-text-muted);font-size:13px;margin-bottom:20px;">
                Currently using the built-in <strong>Rule-Based Engine</strong>. For advanced AI responses, 
                configure an external API below (OpenAI or Gemini).
            </p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label class="form-label">AI Model</label>
                    <select name="setting_ai_model" class="form-control">
                        <option value="rule_based" <?php echo ($settings['ai_model']['setting_value'] ?? 'rule_based') === 'rule_based' ? 'selected' : ''; ?>>Rule-Based Engine (Current)</option>
                        <option value="openai" <?php echo ($settings['ai_model']['setting_value'] ?? '') === 'openai' ? 'selected' : ''; ?>>OpenAI (GPT-4/GPT-3.5)</option>
                        <option value="gemini" <?php echo ($settings['ai_model']['setting_value'] ?? '') === 'gemini' ? 'selected' : ''; ?>>Google Gemini</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Model Name</label>
                    <input type="text" name="setting_ai_model_name" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['ai_model_name']['setting_value'] ?? 'gpt-4o-mini'); ?>"
                           placeholder="gpt-4o-mini, gemini-pro, etc.">
                    <div class="validation-hint">e.g., gpt-4o-mini, gemini-pro</div>
                </div>

                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">API Key</label>
                    <input type="password" name="setting_ai_api_key" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['ai_api_key']['setting_value'] ?? ''); ?>"
                           placeholder="sk-... or your API key">
                    <div class="validation-hint">Your API key is stored securely and never exposed</div>
                </div>
            </div>

            <hr style="border:none;border-top:1px solid var(--admin-border);margin:24px 0;">

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Save All Settings
            </button>
        </form>
    </div>
</div>
