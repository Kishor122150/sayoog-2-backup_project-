<?php
/**
 * chatbot/admin/faq_manager.php
 * Admin FAQ & Knowledge Base Manager for the Sayog AI Chatbot.
 * 
 * Allows admins to:
 * - View all knowledge base entries
 * - Add new Q&A entries
 * - Edit existing entries
 * - Delete entries
 * - Toggle active/primary status
 * 
 * This file is included from admin/admin.php when section=chatbot&tab=faq.
 */

// Ensure admin access
if (!is_admin_logged_in() && !is_admin()) {
    echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-lock"></i> Admin access required.</div>';
    return;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faq_action = $_POST['faq_action'] ?? '';
    
    try {
        // Ensure table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `chatbot_knowledge` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `intent` VARCHAR(50) NOT NULL,
                `question` VARCHAR(255) NOT NULL,
                `answer` TEXT NOT NULL,
                `category` VARCHAR(50) DEFAULT 'general',
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FULLTEXT INDEX `ft_search` (`question`, `answer`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        if ($faq_action === 'create' || $faq_action === 'update') {
            $intent = sanitize($_POST['intent'] ?? '');
            $question = sanitize($_POST['question'] ?? '');
            $answer = $_POST['answer'] ?? '';
            $category = sanitize($_POST['category'] ?? 'general');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $is_primary = isset($_POST['is_primary']) ? 1 : 0;

            if (empty($question) || empty($answer)) {
                echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-circle-xmark"></i> Question and answer are required.</div>';
            } else {
                if ($faq_action === 'create') {
                    $stmt = $pdo->prepare("INSERT INTO chatbot_knowledge (intent, question, answer, category, is_active, is_primary) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$intent, $question, $answer, $category, $is_active, $is_primary]);
                    echo '<div class="admin-alert admin-alert-success"><i class="fa-solid fa-circle-check"></i> FAQ entry added successfully.</div>';
                } else {
                    $id = intval($_POST['entry_id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE chatbot_knowledge SET intent = ?, question = ?, answer = ?, category = ?, is_active = ?, is_primary = ? WHERE id = ?");
                    $stmt->execute([$intent, $question, $answer, $category, $is_active, $is_primary, $id]);
                    echo '<div class="admin-alert admin-alert-success"><i class="fa-solid fa-circle-check"></i> FAQ entry updated successfully.</div>';
                }
            }
        }

        if ($faq_action === 'delete') {
            $id = intval($_POST['entry_id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM chatbot_knowledge WHERE id = ?");
                $stmt->execute([$id]);
                echo '<div class="admin-alert admin-alert-warning"><i class="fa-solid fa-trash-can"></i> FAQ entry deleted.</div>';
            }
        }

        if ($faq_action === 'toggle_active') {
            $id = intval($_POST['entry_id'] ?? 0);
            if ($id > 0) {
                $pdo->exec("UPDATE chatbot_knowledge SET is_active = NOT is_active WHERE id = $id");
                echo '<div class="admin-alert admin-alert-info"><i class="fa-solid fa-check"></i> Entry toggled.</div>';
            }
        }
    } catch (PDOException $e) {
        echo '<div class="admin-alert admin-alert-danger"><i class="fa-solid fa-circle-xmark"></i> Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Fetch all knowledge entries
try {
    $faqs = $pdo->query("SELECT * FROM chatbot_knowledge ORDER BY category ASC, is_primary DESC, id DESC")->fetchAll();
} catch (PDOException $e) {
    $faqs = [];
}

// Get all intents from the intent detector
require_once __DIR__ . '/../intent_detector.php';
$available_intents = IntentDetector::get_available_intents('admin');
$intent_names = array_unique(array_column($available_intents, 'intent'));
?>

<div class="section-header">
    <div>
        <h1><i class="fa-solid fa-book"></i> FAQ & Knowledge Base Manager</h1>
        <p>Manage chatbot knowledge entries. These are used to answer user questions.</p>
    </div>
</div>

<!-- Add New Entry -->
<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-plus-circle"></i> Add New FAQ Entry</h3>
    </div>
    <div class="admin-card-body">
        <form method="POST" class="admin-form">
            <input type="hidden" name="faq_action" value="create">
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label class="form-label">Intent (for matching)</label>
                    <select name="intent" class="form-control">
                        <option value="">— Select or type —</option>
                        <?php foreach ($intent_names as $in): ?>
                            <option value="<?php echo htmlspecialchars($in); ?>"><?php echo htmlspecialchars($in); ?></option>
                        <?php endforeach; ?>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="general">General</option>
                        <option value="about">About</option>
                        <option value="donation">Donation</option>
                        <option value="request">Request</option>
                        <option value="auth">Authentication</option>
                        <option value="volunteer">Volunteer</option>
                        <option value="contact">Contact</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:12px;">
                <label class="form-label">Question</label>
                <input type="text" name="question" class="form-control" placeholder="e.g., How do I donate food?" required>
            </div>

            <div style="margin-bottom:12px;">
                <label class="form-label">Answer</label>
                <textarea name="answer" class="form-control" rows="5" placeholder="Enter the answer. HTML is allowed (bold, links, etc.)." required></textarea>
            </div>

            <div style="display:flex;gap:16px;align-items:center;margin-bottom:16px;">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" name="is_active" checked> Active
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                    <input type="checkbox" name="is_primary"> Primary Answer
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Add FAQ Entry
            </button>
        </form>
    </div>
</div>

<!-- All Entries -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fa-solid fa-list"></i> All Knowledge Entries (<?php echo count($faqs); ?>)</h3>
    </div>
    <div class="admin-card-body" style="padding:0;">
        <?php if (empty($faqs)): ?>
            <div class="empty-state" style="padding:40px;text-align:center;">
                <i class="fa-solid fa-book-open" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;"></i>
                <h3>No knowledge entries yet</h3>
                <p>Add your first FAQ entry above.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Intent</th>
                            <th>Category</th>
                            <th>Question</th>
                            <th>Answer (Preview)</th>
                            <th>Active</th>
                            <th>Primary</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faqs as $faq): ?>
                            <tr>
                                <td><span class="badge badge-neutral">#<?php echo $faq['id']; ?></span></td>
                                <td><code><?php echo htmlspecialchars($faq['intent'] ?: '—'); ?></code></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($faq['category']); ?></span></td>
                                <td><strong><?php echo htmlspecialchars(mb_substr($faq['question'], 0, 60)); ?></strong></td>
                                <td style="font-size:12px;color:var(--admin-text-muted);max-width:200px;">
                                    <?php echo htmlspecialchars(mb_substr(strip_tags($faq['answer']), 0, 100)); ?>...
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="faq_action" value="toggle_active">
                                        <input type="hidden" name="entry_id" value="<?php echo $faq['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $faq['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $faq['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo $faq['is_primary'] ? '⭐' : '—'; ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline" onclick="editFaq(<?php echo $faq['id']; ?>)">
                                            <i class="fa-solid fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this entry?');">
                                            <input type="hidden" name="faq_action" value="delete">
                                            <input type="hidden" name="entry_id" value="<?php echo $faq['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" style="color:red;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal Placeholder -->
<div id="editFaqModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:none;align-items:center;justify-content:center;" onclick="if(event.target===this)closeEditFaq()">
    <div style="background:#fff;border-radius:16px;max-width:700px;width:90%;max-height:80vh;overflow-y:auto;padding:32px;">
        <h3 style="margin:0 0 20px;">Edit FAQ Entry</h3>
        <form method="POST" id="editFaqForm">
            <input type="hidden" name="faq_action" value="update">
            <input type="hidden" name="entry_id" id="edit_id">
            <!-- Fields are populated by JavaScript -->
            <div id="editFaqContent"></div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditFaq()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Simple edit modal functionality
function editFaq(id) {
    // Find the row data - we reload with a fetch to keep it simple
    window.location.href = 'admin.php?section=chatbot&tab=faq&edit_id=' + id;
}

function closeEditFaq() {
    document.getElementById('editFaqModal').style.display = 'none';
}

// Check for edit parameter on load
(function() {
    var params = new URLSearchParams(window.location.search);
    var editId = params.get('edit_id');
    if (editId) {
        fetch('chatbot/api.php?action=get_faq&id=' + editId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.data) {
                    var faq = data.data;
                    var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">' +
                        '<div><label class="form-label">Intent</label><input type="text" name="intent" class="form-control" value="' + escapeHtml(faq.intent) + '"></div>' +
                        '<div><label class="form-label">Category</label><select name="category" class="form-control">' +
                            '<option value="general"' + (faq.category === 'general' ? ' selected' : '') + '>General</option>' +
                            '<option value="about"' + (faq.category === 'about' ? ' selected' : '') + '>About</option>' +
                            '<option value="donation"' + (faq.category === 'donation' ? ' selected' : '') + '>Donation</option>' +
                            '<option value="request"' + (faq.category === 'request' ? ' selected' : '') + '>Request</option>' +
                            '<option value="auth"' + (faq.category === 'auth' ? ' selected' : '') + '>Authentication</option>' +
                            '<option value="volunteer"' + (faq.category === 'volunteer' ? ' selected' : '') + '>Volunteer</option>' +
                            '<option value="contact"' + (faq.category === 'contact' ? ' selected' : '') + '>Contact</option>' +
                            '<option value="admin"' + (faq.category === 'admin' ? ' selected' : '') + '>Admin</option>' +
                        '</select></div></div>' +
                        '<div style="margin-bottom:12px;"><label class="form-label">Question</label><input type="text" name="question" class="form-control" value="' + escapeHtml(faq.question) + '"></div>' +
                        '<div style="margin-bottom:12px;"><label class="form-label">Answer</label><textarea name="answer" class="form-control" rows="5">' + escapeHtml(faq.answer) + '</textarea></div>' +
                        '<div style="display:flex;gap:16px;">' +
                            '<label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_active"' + (faq.is_active ? ' checked' : '') + '> Active</label>' +
                            '<label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_primary"' + (faq.is_primary ? ' checked' : '') + '> Primary</label>' +
                        '</div>';
                    document.getElementById('edit_id').value = faq.id;
                    document.getElementById('editFaqContent').innerHTML = html;
                    document.getElementById('editFaqModal').style.display = 'flex';
                }
            })
            .catch(function() {});
    }
})();

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
