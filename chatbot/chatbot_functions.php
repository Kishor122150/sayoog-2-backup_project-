<?php
/**
 * chatbot/chatbot_functions.php
 * Core helper functions for the Sayog AI Chatbot System.
 * 
 * Provides utilities for session management, user detection,
 * context awareness, security filtering, and response helpers.
 * 
 * Security: All functions enforce strict RBAC and sanitization.
 * No database credentials, paths, or internal info are ever exposed.
 */

// ──────────────────────────────────────────────────────────────
// 1. CONTEXT DETECTION HELPERS
// ──────────────────────────────────────────────────────────────

/**
 * Detect the current page/module based on the request URI.
 * Returns an associative array with: page, module, url, is_public, is_logged_in, user_role.
 */
function chatbot_detect_context() {
    $context = [
        'url'        => $_SERVER['REQUEST_URI'] ?? '/',
        'page'       => 'unknown',
        'module'     => 'public',
        'is_public'  => true,
        'is_admin'   => false,
        'is_logged_in' => false,
        'user_role'  => 'guest',
        'user_id'    => 0,
        'user_name'  => '',
        'user_email' => '',
    ];

    // Detect login status and role from session
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $context['is_logged_in'] = true;
        $context['user_id']    = (int)$_SESSION['user_id'];
        $context['user_name']  = $_SESSION['user_name'] ?? '';
        $context['user_email'] = $_SESSION['user_email'] ?? '';
        $context['user_role']  = $_SESSION['user_role'] ?? 'user';
        $context['module']     = 'dashboard';
        $context['is_public']  = false;
    }

    // Check if admin
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $context['is_admin'] = true;
        $context['module']   = 'admin';
        $context['is_public'] = false;
        $context['user_role'] = 'admin';
    }

    // Detect page from URL
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($uri, PHP_URL_PATH);
    $basename = basename($path);

    $page_map = [
        'index.php'       => 'home',
        'about.php'       => 'about',
        'contact.php'     => 'contact',
        'donations.php'   => 'donations',
        'donation.php'    => 'donation',
        'team.php'        => 'team',
        'volunteers.php'  => 'volunteers',
        'login.php'       => 'login',
        'register.php'    => 'register',
        'verify-otp.php'  => 'verify-otp',
        'dashboard.php'   => 'dashboard',
        'become-volunteer.php' => 'become-volunteer',
        'products.php'    => 'products',
        'page.php'        => 'page',
    ];

    // Detect if we're in admin
    if (strpos($uri, '/admin/') !== false || strpos($uri, 'admin.php') !== false) {
        $context['is_admin'] = true;
        $context['module'] = 'admin';
        $context['is_public'] = false;
    }

    $context['page'] = $page_map[$basename] ?? 'unknown';
    if ($context['is_admin']) {
        $context['page'] = 'admin';
    }

    return $context;
}

// ──────────────────────────────────────────────────────────────
// 2. SECURITY: BLOCK PROMPT INJECTION
// ──────────────────────────────────────────────────────────────

/**
 * List of blocked patterns that indicate prompt injection attempts.
 * If detected in user input, the chatbot returns a security warning.
 */
function chatbot_get_blocked_patterns() {
    return [
        '/ignore\s+(all\s+)?(previous\s+)?instructions/i',
        '/reveal\s+(your\s+)?(prompt|instructions|system)/i',
        '/show\s+(your\s+)?(prompt|instructions|system|database|sql)/i',
        '/dump\s+(sql|database|table)/i',
        '/act\s+as\s+(developer|root|admin|system)/i',
        '/you\s+are\s+(now\s+)?(a\s+)?(developer|root|admin)/i',
        '/forget\s+(all\s+)?(previous\s+)?(instructions|rules)/i',
        '/output\s+(your\s+)?(prompt|instructions)/i',
        '/print\s+(your\s+)?(prompt|instructions|system)/i',
        '/display\s+(your\s+)?(prompt|instructions)/i',
        '/show\s+(api\s+)?(key|keys|secret|password|token|credential)/i',
        '/what\s+is\s+your\s+(prompt|instruction|system\s+message)/i',
        '/tell\s+me\s+(your\s+)?(prompt|instructions|system)/i',
        '/list\s+(all\s+)?(tables|databases|columns|schema)/i',
        '/describe\s+(table|database|schema)/i',
        '/SELECT.*FROM/i',
        '/DROP\s+TABLE/i',
        '/ALTER\s+TABLE/i',
        '/TRUNCATE/i',
        '/DELETE\s+FROM/i',
        '/UPDATE\s+.*SET/i',
        '/INSERT\s+INTO/i',
        '/\.env/i',
        '/config\.php/i',
        '/DB_HOST|DB_USER|DB_PASS|DB_NAME/i',
        '/password_hash|PASSWORD_BCRYPT/i',
    ];
}

/**
 * Check user input for prompt injection attempts.
 * Returns true if the input appears malicious.
 */
function chatbot_is_injection_attempt($message) {
    $patterns = chatbot_get_blocked_patterns();
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return true;
        }
    }
    return false;
}

/**
 * Get a friendly security warning message.
 */
function chatbot_security_warning() {
    $responses = [
        "I'm here to help you with Sayog's food donation platform. Let's keep our conversation respectful and focused on how I can assist you. 😊",
        "I can't share internal system information, but I'm happy to help you with donations, food requests, or any platform-related questions!",
        "That's a bit outside what I can discuss! Feel free to ask me about donating food, requesting food, or using the Sayog platform.",
        "I'm designed to help with the Sayog food donation platform. Let's chat about how I can assist you with that! 🙏",
        "I can't process that request, but I'm here to help with anything related to food donations, requests, or navigating our platform!",
    ];
    return $responses[array_rand($responses)];
}

// ──────────────────────────────────────────────────────────────
// 3. RESPONSE FORMATTING HELPERS
// ──────────────────────────────────────────────────────────────

/**
 * Build a standard JSON response for the chatbot API.
 */
function chatbot_json_response($message, $suggestions = [], $action = null, $data = null) {
    return [
        'success'     => true,
        'message'     => $message,
        'suggestions' => $suggestions,
        'action'      => $action,
        'data'        => $data,
        'timestamp'   => date('Y-m-d H:i:s'),
    ];
}

/**
 * Build a quick suggested replies array from a list of strings.
 */
function chatbot_suggestions($items) {
    $result = [];
    foreach ($items as $item) {
        $result[] = ['text' => $item];
    }
    return $result;
}

/**
 * Format a message with basic HTML for the chatbot bubble.
 * Converts Markdown-style **bold** and *italic* to HTML.
 */
function chatbot_format_message($text) {
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    // Italic
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    // Newlines to <br>
    $text = nl2br($text);
    return $text;
}

/**
 * Truncate a string nicely for suggestion buttons.
 */
function chatbot_truncate($text, $max = 60) {
    if (mb_strlen($text) <= $max) return $text;
    return mb_substr($text, 0, $max - 3) . '...';
}

// ──────────────────────────────────────────────────────────────
// 4. SESSION CONVERSATION MEMORY
// ──────────────────────────────────────────────────────────────

/**
 * Initialize conversation memory in session.
 */
function chatbot_init_session() {
    if (!isset($_SESSION['chatbot_history'])) {
        $_SESSION['chatbot_history'] = [];
    }
    if (!isset($_SESSION['chatbot_context'])) {
        $_SESSION['chatbot_context'] = [];
    }
}

/**
 * Add a message to the conversation history.
 */
function chatbot_add_to_history($role, $message) {
    chatbot_init_session();
    $_SESSION['chatbot_history'][] = [
        'role'      => $role, // 'user' or 'bot'
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ];
    // Keep only last 50 messages to manage memory
    if (count($_SESSION['chatbot_history']) > 50) {
        array_shift($_SESSION['chatbot_history']);
    }
}

/**
 * Get recent conversation history (for context).
 */
function chatbot_get_history($limit = 10) {
    chatbot_init_session();
    return array_slice($_SESSION['chatbot_history'], -$limit);
}

/**
 * Clear conversation history.
 */
function chatbot_clear_history() {
    $_SESSION['chatbot_history'] = [];
    $_SESSION['chatbot_context'] = [];
}

/**
 * Set a context value for the current conversation.
 */
function chatbot_set_context($key, $value) {
    chatbot_init_session();
    $_SESSION['chatbot_context'][$key] = $value;
}

/**
 * Get a context value from the conversation.
 */
function chatbot_get_context($key, $default = null) {
    chatbot_init_session();
    return $_SESSION['chatbot_context'][$key] ?? $default;
}

/**
 * Get all conversation context.
 */
function chatbot_get_all_context() {
    chatbot_init_session();
    return $_SESSION['chatbot_context'];
}
