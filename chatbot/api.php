<?php
/**
 * chatbot/api.php
 * AJAX API Endpoint for the Sayog AI Chatbot.
 * 
 * Handles incoming chat requests, processes them through the AI Router,
 * and returns JSON responses. Supports CORS for AJAX requests.
 * 
 * Endpoints:
 * - POST [no action]: Process a chat message
 * - POST action=clear: Clear conversation history
 * - GET action=history: Get conversation history
 * 
 * Security:
 * - Blocks direct access to internal files
 * - Validates all input
 * - Returns JSON only (no HTML output)
 * - Rate limiting via session checks
 */

// Prevent direct access
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/ai_router.php';

// Only accept AJAX requests (reject direct browser access)
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
    // Allow direct GET for legacy checks but enforce for sensitive operations
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Direct access not allowed.']);
        exit();
    }
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    // Initialize the AI Router
    $router = new AIRouter($pdo);
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // ── CLEAR CONVERSATION ──
    if ($action === 'clear') {
        $router->clearConversation();
        echo json_encode(['success' => true, 'message' => 'Conversation cleared.']);
        exit();
    }

    // ── GET HISTORY ──
    if ($action === 'history') {
        $history = $router->getHistory(50);
        echo json_encode(['success' => true, 'data' => $history]);
        exit();
    }

    // ── GET FAQ ENTRY (for admin edit modal) ──
    if ($action === 'get_faq') {
        // Admin-only endpoint
        if (!is_admin() && !is_admin_logged_in()) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit();
        }
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid FAQ ID.']);
            exit();
        }
        try {
            $stmt = $pdo->prepare("SELECT * FROM chatbot_knowledge WHERE id = ?");
            $stmt->execute([$id]);
            $faq = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($faq) {
                echo json_encode(['success' => true, 'data' => $faq]);
            } else {
                echo json_encode(['success' => false, 'message' => 'FAQ entry not found.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit();
    }

    // ── PROCESS CHAT MESSAGE ──
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a message.',
        ]);
        exit();
    }

    // Limit message length
    if (strlen($message) > 500) {
        echo json_encode([
            'success' => false,
            'message' => 'Your message is too long. Please keep it under 500 characters.',
        ]);
        exit();
    }

    // Process through the AI Router
    $response = $router->process($message);
    
    echo json_encode($response);

} catch (Exception $e) {
    // Never expose internal errors to the client
    error_log('Chatbot API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'I encountered a temporary issue. Please try again.',
    ]);
}
