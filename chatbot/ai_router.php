<?php
/**
 * chatbot/ai_router.php
 * AI Router for the Sayog Chatbot.
 * 
 * The central orchestrator that:
 * 1. Detects user context (page, role, login status)
 * 2. Checks for security threats (prompt injection)
 * 3. Attempts AI-powered response via external provider (OpenAI/Gemini)
 * 4. Falls back to rule-based intent detection + response engine
 * 5. Logs the conversation
 * 6. Returns a structured response
 * 
 * AI Integration Flow:
 *   Configured? → AI Provider → Success? → Return AI response
 *       ↓ No              ↓ No
 *   Rule-Based Engine ────→ Return rule-based response
 */

require_once __DIR__ . '/chatbot_functions.php';
require_once __DIR__ . '/intent_detector.php';
require_once __DIR__ . '/response_engine.php';
require_once __DIR__ . '/conversation_logger.php';
require_once __DIR__ . '/ai_provider.php';

class AIRouter {

    private $pdo;
    private $responseEngine;
    private $logger;
    private $aiProvider;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->responseEngine = new ResponseEngine($pdo);
        $this->logger = new ConversationLogger($pdo);
        $this->aiProvider = new AIProvider($pdo);
    }

    /**
     * Process a user message and return a response.
     *
     * @param string $message The user's message.
     * @param array  $context The current context (optional, auto-detected if empty).
     * @return array Response array with message, suggestions, action, etc.
     */
    public function process($message, $context = []) {
        // 1. Auto-detect context if not provided
        if (empty($context)) {
            $context = chatbot_detect_context();
        }

        // 2. Initialize conversation session
        chatbot_init_session();

        // 3. Check for prompt injection / security threats (ALWAYS FIRST)
        if (chatbot_is_injection_attempt($message)) {
            $warning = chatbot_security_warning();
            chatbot_add_to_history('user', $message);
            chatbot_add_to_history('bot', strip_tags($warning));
            
            $this->logger->log(
                $context['user_id'] ?? 0,
                $message,
                $warning,
                'security_blocked',
                $context['user_role'] ?? 'guest'
            );

            return chatbot_json_response(
                $warning,
                [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Platform Statistics'],
                ]
            );
        }

        // 4. Try AI Provider (if configured)
        $intent = 'rule_based';
        $modelType = $this->aiProvider->getModelType();
        $usedAI = false;

        if ($this->aiProvider->isAvailable()) {
            // Get full history for rate limiting, then truncate for AI context
            $fullHistory = chatbot_get_history(100);
            $aiResponse = $this->aiProvider->generateResponse($message, $context, $fullHistory);

            if ($aiResponse !== null) {
                $usedAI = true;
                $intent = 'ai_' . $modelType;
                $response = chatbot_json_response(
                    $aiResponse['message'],
                    $aiResponse['suggestions']
                );
            }
        }

        // 5. Fall back to rule-based engine if AI not available or failed
        if (!$usedAI) {
            $intent_result = IntentDetector::detect($message, $context);
            $intent = $intent_result['intent'];
            $confidence = $intent_result['confidence'];

            $response = $this->responseEngine->generate($intent, $confidence, $message, $context);
        }

        // 6. Add to conversation history
        chatbot_add_to_history('user', $message);
        chatbot_add_to_history('bot', $response['message']);

        // 7. Log the conversation
        $this->logger->log(
            $context['user_id'] ?? 0,
            $message,
            $response['message'],
            $intent,
            $context['user_role'] ?? 'guest'
        );

        // 8. Return response
        return $response;
    }

    /**
     * Maximum conversation history turns for AI context.
     */
    const MAX_HISTORY_TURNS = 6;

    /**
     * Clear the current conversation.
     */
    public function clearConversation() {
        chatbot_clear_history();
        
        // Log the clear action if user is logged in
        $context = chatbot_detect_context();
        $this->logger->log(
            $context['user_id'] ?? 0,
            '/clear',
            'Conversation cleared',
            'system',
            $context['user_role'] ?? 'guest'
        );
    }

    /**
     * Get conversation history.
     */
    public function getHistory($limit = 20) {
        return chatbot_get_history($limit);
    }
}
