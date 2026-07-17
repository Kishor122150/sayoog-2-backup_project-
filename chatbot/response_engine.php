<?php
/**
 * chatbot/response_engine.php
 * Response Engine for the Sayog AI Chatbot.
 * 
 * Generates human-like, contextual responses based on:
 * - Detected intent
 * - User role and login status
 * - Current page context
 * - Conversation history
 * - Knowledge base answers
 * - Database query results
 * 
 * The engine provides friendly, helpful responses with suggested follow-ups.
 */

require_once __DIR__ . '/knowledge_base.php';
require_once __DIR__ . '/database_engine.php';
require_once __DIR__ . '/chatbot_functions.php';

class ResponseEngine {

    private $pdo;
    private $knowledgeBase;
    private $databaseEngine;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->knowledgeBase = new KnowledgeBase($pdo);
        $this->databaseEngine = new DatabaseEngine($pdo);
    }

    /**
     * Generate a response for a given intent and context.
     *
     * @param string $intent     The detected intent.
     * @param float  $confidence Confidence score.
     * @param string $message    Original user message.
     * @param array  $context    Current context (page, role, user_id, etc.).
     * @return array Response with message, suggestions, action, data.
     */
    public function generate($intent, $confidence, $message, $context) {
        $role = $context['user_role'] ?? 'guest';
        $page = $context['page'] ?? 'unknown';
        $is_logged_in = $context['is_logged_in'] ?? false;
        $user_id = $context['user_id'] ?? 0;

        // 1. Try knowledge base first
        $kb_entry = $this->knowledgeBase->get_answer($intent);
        if ($kb_entry && $confidence > 30) {
            $suggestions = $this->get_suggestions_for_intent($intent, $role, $page);
            return chatbot_json_response(
                chatbot_format_message($kb_entry['answer']),
                $suggestions
            );
        }

        // 2. Try database query for dynamic data intents
        $db_result = $this->databaseEngine->query($intent, $context);
        if ($db_result['success']) {
            $suggestions = $this->get_suggestions_for_intent($intent, $role, $page);
            return chatbot_json_response(
                chatbot_format_message($db_result['message']),
                $suggestions
            );
        }

        // 3. Generate contextual responses based on intent + context
        return $this->generate_contextual_response($intent, $message, $context);
    }

    /**
     * Generate a default help response when intent is unknown.
     */
    public function generate_help_response($context) {
        $role = $context['user_role'] ?? 'guest';
        $name = $context['user_name'] ?? '';

        $greeting = !empty($name) ? "Hello **{$name}!** 👋" : "Hello there! 👋";

        $message = "{$greeting} I'm Sayog's AI Assistant! I can help you with:\n\n";

        if ($role === 'guest') {
            $message .= "🔹 **About Sayog** — What we do\n"
                . "🔹 **Donate Food** — How to donate\n"
                . "🔹 **Request Food** — How to request\n"
                . "🔹 **Registration** — How to sign up\n"
                . "🔹 **Volunteer** — Become a volunteer\n"
                . "🔹 **Food Listings** — Available donations\n"
                . "🔹 **Contact Info** — How to reach us\n"
                . "🔹 **Statistics** — Platform stats\n\n"
                . "👉 <a href='register.php' style='color:#059669;font-weight:600;'>Get Started</a> or ask me anything! 😊";
        } elseif ($role === 'admin') {
            $message .= "🔹 **Admin Dashboard** — Platform overview\n"
                . "🔹 **Pending Reviews** — Donations to review\n"
                . "🔹 **Pending Volunteers** — Applications\n"
                . "🔹 **Today's Registrations** — New users\n"
                . "🔹 **Statistics** — Full platform stats\n"
                . "🔹 **Donations/Requests** — Manage content\n\n"
                . "How can I assist you today, Admin? 🛡️";
        } else {
            $message .= "🔹 **My Donations** — View your donations\n"
                . "🔹 **My Requests** — View your requests\n"
                . "🔹 **Create Donation** — Post new food\n"
                . "🔹 **Request Food** — Find food\n"
                . "🔹 **Donation Guidelines** — What to donate\n"
                . "🔹 **Become a Volunteer** — Join the team\n"
                . "🔹 **Available Food** — Browse listings\n\n"
                . "How can I help you today? 😊";
        }

        $suggestions = $this->get_suggestions_for_intent('help', $role, 'dashboard');

        return chatbot_json_response(
            chatbot_format_message($message),
            $suggestions
        );
    }

    /**
     * Generate a friendly fallback response when intent is unknown or low confidence.
     */
    public function generate_fallback_response($message, $context) {
        $role = $context['user_role'] ?? 'guest';

        $fallbacks = [
            "I'm not quite sure I understand. Could you rephrase that? I can help with donations, food requests, registration, and more! 😊",
            "Hmm, I don't have an answer for that yet. Try asking me about donating food, requesting food, or how Sayog works!",
            "I'm still learning! Could you try asking in a different way? Here are some things I can help with:\n\n🔹 How to donate food\n🔹 How to request food\n🔹 How to register\n🔹 Available food listings\n🔹 Platform statistics",
            "I didn't quite catch that. Feel free to type **\"Help\"** to see what I can do! 🙏",
        ];

        $suggestions = [
            ['text' => 'What is Sayog?'],
            ['text' => 'How to donate food'],
            ['text' => 'Available food'],
        ];

        if ($role !== 'guest') {
            $suggestions = [
                ['text' => 'My Donations'],
                ['text' => 'My Requests'],
                ['text' => 'Create Donation'],
            ];
        }

        if ($role === 'admin') {
            $suggestions = [
                ['text' => 'Admin Stats'],
                ['text' => 'Pending Reviews'],
                ['text' => 'Pending Volunteers'],
            ];
        }

        return chatbot_json_response(
            chatbot_format_message($fallbacks[array_rand($fallbacks)]),
            $suggestions
        );
    }

    /**
     * Get suggestions based on intent, role, and current page.
     */
    private function get_suggestions_for_intent($intent, $role, $page) {
        $suggestions = [];

        switch ($intent) {
            case 'greeting':
            case 'help':
                if ($role === 'guest') {
                    $suggestions = [
                        ['text' => 'What is Sayog?'],
                        ['text' => 'How to donate food'],
                        ['text' => 'Available food'],
                        ['text' => 'Platform Statistics'],
                    ];
                } elseif ($role === 'admin') {
                    $suggestions = [
                        ['text' => 'Admin Dashboard Stats'],
                        ['text' => 'Pending Donation Reviews'],
                        ['text' => 'Pending Volunteers'],
                        ['text' => 'Today\'s Registrations'],
                    ];
                } else {
                    $suggestions = [
                        ['text' => 'My Donations'],
                        ['text' => 'My Requests'],
                        ['text' => 'Available Food'],
                        ['text' => 'Create Donation'],
                    ];
                }
                break;

            case 'about_sayog':
            case 'mission_vision':
                $suggestions = [
                    ['text' => 'How does Sayog work?'],
                    ['text' => 'Why use Sayog?'],
                    ['text' => 'Platform Statistics'],
                ];
                break;

            case 'how_to_donate':
                $suggestions = [
                    ['text' => 'What can I donate?'],
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'My Donations'],
                ];
                break;

            case 'my_donations':
            case 'donation_status':
                $suggestions = [
                    ['text' => 'Create New Donation'],
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'available_food':
                $suggestions = [
                    ['text' => 'How to request food'],
                    ['text' => 'My Requests'],
                    ['text' => 'Donation Guidelines'],
                ];
                break;

            case 'how_to_request':
            case 'my_requests':
            case 'request_status':
                $suggestions = [
                    ['text' => 'Available Food'],
                    ['text' => 'Track My Requests'],
                    ['text' => 'Create Donation'],
                ];
                break;

            case 'how_to_register':
                $suggestions = [
                    ['text' => 'How to login'],
                    ['text' => 'What is Sayog?'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'become_volunteer':
                $suggestions = [
                    ['text' => 'Volunteer Documents Required'],
                    ['text' => 'Available Food'],
                    ['text' => 'Platform Statistics'],
                ];
                break;

            case 'contact_info':
                $suggestions = [
                    ['text' => 'Where is your office?'],
                    ['text' => 'What is Sayog?'],
                    ['text' => 'Platform Statistics'],
                ];
                break;

            case 'statistics':
                $suggestions = [
                    ['text' => 'Available Food'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Contact Info'],
                ];
                break;

            case 'admin_stats':
                $suggestions = [
                    ['text' => 'Pending Reviews'],
                    ['text' => 'Pending Volunteers'],
                    ['text' => 'Today\'s Registrations'],
                ];
                break;

            case 'pending_reviews':
                $suggestions = [
                    ['text' => 'Admin Dashboard Stats'],
                    ['text' => 'Pending Volunteers'],
                    ['text' => 'Donation Status'],
                ];
                break;

            default:
                $suggestions = [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Platform Statistics'],
                ];
        }

        return $suggestions;
    }

    /**
     * Generate a contextual response based on intent and page context.
     */
    private function generate_contextual_response($intent, $message, $context) {
        $role = $context['user_role'] ?? 'guest';
        $page = $context['page'] ?? 'unknown';

        // Check if the message contains greeting-like patterns
        if (preg_match('/\b(hi|hello|hey|namaste|नमस्ते)\b/i', $message)) {
            return $this->generate_help_response($context);
        }

        // Check for thank you
        if (preg_match('/\b(thank|thanks|dhanyabad|धन्यवाद)\b/i', $message)) {
            return chatbot_json_response(
                "You're very welcome! 🙏 Is there anything else I can help you with?",
                $this->get_suggestions_for_intent('help', $role, $page)
            );
        }

        // Check for bye/farewell
        if (preg_match('/\b(bye|goodbye|see you)\b/i', $message)) {
            return chatbot_json_response(
                "Goodbye! 😊 If you need help later, just click the chat button again. Have a great day! 🌟",
                [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                ]
            );
        }

        // Page-specific contextual help
        if ($page === 'donations' || $page === 'donation') {
            return chatbot_json_response(
                chatbot_format_message("I see you're on the **Food Listings** page! 🍽️\n\nHere you can browse all available food donations. Each listing shows the food item, quantity, expiry time, and pickup location.\n\nWould you like to know more about how to request food?"),
                [
                    ['text' => 'How to request food'],
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'What is Sayog?'],
                ]
            );
        }

        if ($page === 'login') {
            return chatbot_json_response(
                chatbot_format_message("Welcome to the **Login Page**! 🔐\n\nEnter your registered email and password to access your dashboard.\n\n**Don't have an account yet?** 👉 <a href='register.php' style='color:#059669;font-weight:600;'>Sign Up here</a>"),
                [
                    ['text' => 'How to register'],
                    ['text' => 'I forgot my password'],
                    ['text' => 'What is Sayog?'],
                ]
            );
        }

        if ($page === 'register') {
            return chatbot_json_response(
                chatbot_format_message("Welcome to the **Registration Page**! 🎉\n\nCreating an account is free and easy. Just fill in your:\n- Full Name\n- Email Address\n- Address\n- Nepal Phone Number\n- Strong Password\n\nThen verify your email via OTP and you're all set!"),
                [
                    ['text' => 'Why register?'],
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                ]
            );
        }

        if ($page === 'about') {
            return chatbot_json_response(
                chatbot_format_message("You're on the **About Us** page! 🏢\n\nSayog is a smart food donation and redistribution platform built to connect surplus food with communities in need across Nepal."),
                [
                    ['text' => 'How does Sayog work?'],
                    ['text' => 'Platform Statistics'],
                    ['text' => 'Why use Sayog?'],
                ]
            );
        }

        if ($page === 'contact') {
            return chatbot_json_response(
                chatbot_format_message("You're on the **Contact Page**! 📞\n\nYou can reach us via the contact form, email, or phone. Would you like to see our contact details?"),
                [
                    ['text' => 'Show contact info'],
                    ['text' => 'Office address'],
                    ['text' => 'What is Sayog?'],
                ]
            );
        }

        if ($role !== 'guest' && $page === 'dashboard') {
            return chatbot_json_response(
                chatbot_format_message("Welcome to your **Dashboard**, {$context['user_name']}! 👋\n\nHere you can:\n- 📝 **Create Donation** — Post food to donate\n- 🍽️ **Request Food** — Find available food\n- 📋 **Manage Donations** — Track your listings\n- 📋 **Manage Requests** — Track your requests\n- 🔔 **Notifications** — View updates\n- 👤 **Profile** — Edit your info\n\nHow can I help you today?"),
                [
                    ['text' => 'My Donations'],
                    ['text' => 'My Requests'],
                    ['text' => 'Available Food'],
                    ['text' => 'Create Donation'],
                ]
            );
        }

        // Ultimate fallback
        return $this->generate_fallback_response($message, $context);
    }
}
