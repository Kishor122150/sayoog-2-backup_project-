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
                        ['text' => 'Become a Volunteer'],
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
                        ['text' => 'My Certificates'],
                    ];
                }
                break;

            case 'about_sayog':
            case 'mission_vision':
                $suggestions = [
                    ['text' => 'How does Sayog work?'],
                    ['text' => 'Why use Sayog?'],
                    ['text' => 'Platform Statistics'],
                    ['text' => 'What services do you offer?'],
                ];
                break;

            case 'how_to_donate':
                $suggestions = [
                    ['text' => 'What can I donate?'],
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'My Donations'],
                    ['text' => 'Food Safety Tips'],
                ];
                break;

            case 'my_donations':
            case 'donation_status':
                $suggestions = [
                    ['text' => 'Create New Donation'],
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'Available Food'],
                    ['text' => 'How to track donations'],
                ];
                break;

            case 'available_food':
                $suggestions = [
                    ['text' => 'How to request food'],
                    ['text' => 'My Requests'],
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'Food Safety Tips'],
                ];
                break;

            case 'how_to_request':
            case 'my_requests':
            case 'request_status':
                $suggestions = [
                    ['text' => 'Available Food'],
                    ['text' => 'Track My Requests'],
                    ['text' => 'Create Donation'],
                    ['text' => 'How to coordinate pickup'],
                ];
                break;

            case 'how_to_register':
                $suggestions = [
                    ['text' => 'How to login'],
                    ['text' => 'What is Sayog?'],
                    ['text' => 'Available Food'],
                    ['text' => 'Why should I register?'],
                ];
                break;

            case 'become_volunteer':
                $suggestions = [
                    ['text' => 'Volunteer Documents Required'],
                    ['text' => 'How delivery works'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'contact_info':
                $suggestions = [
                    ['text' => 'Where is your office?'],
                    ['text' => 'What is Sayog?'],
                    ['text' => 'Platform Statistics'],
                    ['text' => 'WhatsApp Support'],
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
                    ['text' => 'Donation Status'],
                ];
                break;

            case 'pending_reviews':
                $suggestions = [
                    ['text' => 'Admin Dashboard Stats'],
                    ['text' => 'Pending Volunteers'],
                    ['text' => 'Donation Status'],
                ];
                break;

            // ── NEW INTENT SUGGESTIONS ──
            case 'certificate_info':
                $suggestions = [
                    ['text' => 'How to get certificate'],
                    ['text' => 'My Donations'],
                    ['text' => 'Create New Donation'],
                ];
                break;

            case 'rating_system':
                $suggestions = [
                    ['text' => 'How to rate someone'],
                    ['text' => 'My Donations'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'notifications':
                $suggestions = [
                    ['text' => 'My Donations'],
                    ['text' => 'My Requests'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'account_settings':
            case 'change_password':
                $suggestions = [
                    ['text' => 'How to login'],
                    ['text' => 'My Donations'],
                    ['text' => 'Contact Support'],
                ];
                break;

            case 'food_safety':
                $suggestions = [
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'What can I donate?'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'otp_verification':
                $suggestions = [
                    ['text' => 'How to register'],
                    ['text' => 'How to login'],
                    ['text' => 'Why should I register?'],
                ];
                break;

            case 'privacy_policy':
            case 'terms_of_service':
                $suggestions = [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Contact Support'],
                ];
                break;

            case 'pickup_process':
                $suggestions = [
                    ['text' => 'How to request food'],
                    ['text' => 'My Requests'],
                    ['text' => 'How volunteer delivery works'],
                ];
                break;

            case 'expiry_management':
                $suggestions = [
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Food Safety Tips'],
                ];
                break;

            case 'how_to_track':
                $suggestions = [
                    ['text' => 'My Donations'],
                    ['text' => 'My Requests'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'ngo_partnership':
                $suggestions = [
                    ['text' => 'How to donate food'],
                    ['text' => 'Platform Statistics'],
                    ['text' => 'Contact Info'],
                ];
                break;

            case 'cancel_donation':
            case 'cancel_request':
                $suggestions = [
                    ['text' => 'My Donations'],
                    ['text' => 'My Requests'],
                    ['text' => 'Create New Donation'],
                ];
                break;

            case 'report_issue':
                $suggestions = [
                    ['text' => 'Contact Support'],
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                ];
                break;

            case 'benefits_registration':
                $suggestions = [
                    ['text' => 'How to register'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'whatsapp_integration':
                $suggestions = [
                    ['text' => 'Contact Info'],
                    ['text' => 'What is Sayog?'],
                    ['text' => 'Platform Statistics'],
                ];
                break;

            case 'language_support':
                $suggestions = [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Platform Statistics'],
                ];
                break;

            case 'mobile_access':
                $suggestions = [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Available Food'],
                ];
                break;

            case 'volunteer_delivery':
            case 'volunteer_deliveries':
                $suggestions = [
                    ['text' => 'Become a Volunteer'],
                    ['text' => 'Volunteer Documents'],
                    ['text' => 'Available Food'],
                ];
                break;

            default:
                $suggestions = [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'How to donate food'],
                    ['text' => 'Platform Statistics'],
                    ['text' => 'Help'],
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

        // Check for positive affirmations
        if (preg_match('/\b(great|awesome|perfect|nice|good|excellent|amazing|wonderful)\b/i', $message)) {
            return chatbot_json_response(
                "I'm glad you think so! 😊 Is there anything else I can help you with?",
                $this->get_suggestions_for_intent('help', $role, $page)
            );
        }

        // Page-specific contextual help
        if ($page === 'donations' || $page === 'donation') {
            return chatbot_json_response(
                chatbot_format_message("I see you're on the **Food Listings** page! 🍽️\n\nHere you can browse all available food donations. Each listing shows the food item, quantity, expiry time, and pickup location.\n\nWould you like to know more about how to request food?"),
                [
                    ['text' => 'How to request food'],
                    ['text' => 'Donation Guidelines'],
                    ['text' => 'Food Safety Tips'],
                ]
            );
        }

        if ($page === 'login') {
            return chatbot_json_response(
                chatbot_format_message("Welcome to the **Login Page**! 🔐\n\nEnter your registered email and password to access your dashboard.\n\n**Forgot your password?** Contact us via the support page — we'll help you reset it.\n\n**Don't have an account yet?** 👉 <a href='register.php' style='color:#059669;font-weight:600;'>Sign Up here</a>"),
                [
                    ['text' => 'How to register'],
                    ['text' => 'I forgot my password'],
                    ['text' => 'OTP verification'],
                ]
            );
        }

        if ($page === 'register') {
            return chatbot_json_response(
                chatbot_format_message("Welcome to the **Registration Page**! 🎉\n\nCreating an account is free and easy. Just fill in your:\n- Full Name\n- Email Address\n- Address\n- Nepal Phone Number\n- Strong Password (8+ characters)\n\nThen verify your email via OTP and you're all set!\n\n👉 Already have an account? <a href='login.php' style='color:#059669;font-weight:600;'>Login here</a>"),
                [
                    ['text' => 'Why should I register?'],
                    ['text' => 'How OTP verification works'],
                    ['text' => 'What is Sayog?'],
                ]
            );
        }

        if ($page === 'about') {
            return chatbot_json_response(
                chatbot_format_message("You're on the **About Us** page! 🏢\n\nSayog is a smart food donation and redistribution platform built to connect surplus food with communities in need across Nepal.\n\nWe believe no food should go to waste while people go hungry."),
                [
                    ['text' => 'How does Sayog work?'],
                    ['text' => 'Platform Statistics'],
                    ['text' => 'What services do you offer?'],
                ]
            );
        }

        if ($page === 'contact') {
            return chatbot_json_response(
                chatbot_format_message("You're on the **Contact Page**! 📞\n\nYou can reach us via the contact form, email, phone, or WhatsApp. Would you like to see our contact details?"),
                [
                    ['text' => 'Show contact info'],
                    ['text' => 'Office address'],
                    ['text' => 'WhatsApp support'],
                ]
            );
        }

        if ($page === 'team') {
            return chatbot_json_response(
                chatbot_format_message("You're on the **Team Page**! 👥\n\nMeet the dedicated team of developers and designers who built Sayog, committed to reducing food waste and helping communities in Nepal."),
                [
                    ['text' => 'What is Sayog?'],
                    ['text' => 'Platform Statistics'],
                    ['text' => 'How to donate food'],
                ]
            );
        }

        if ($page === 'volunteers') {
            return chatbot_json_response(
                chatbot_format_message("You're on the **Volunteers Page**! 🧑‍🤝‍🧑\n\nMeet our wonderful volunteers who help transport food from donors to receivers. Want to join them?"),
                [
                    ['text' => 'How to become a volunteer'],
                    ['text' => 'Volunteer Documents'],
                    ['text' => 'How delivery works'],
                ]
            );
        }

        if ($page === 'become-volunteer') {
            return chatbot_json_response(
                chatbot_format_message("Ready to become a **Sayog Volunteer**? 🎉\n\nFill in your personal details, upload the required documents, and submit your application. An admin will review and approve it!"),
                [
                    ['text' => 'Volunteer Documents Required'],
                    ['text' => 'How delivery works'],
                    ['text' => 'Available Food'],
                ]
            );
        }

        if ($role !== 'guest' && $page === 'dashboard') {
            return chatbot_json_response(
                chatbot_format_message("Welcome to your **Dashboard**, {$context['user_name']}! 👋\n\nHere you can:\n- 📝 **Create Donation** — Post food to donate\n- 🍽️ **Request Food** — Find available food\n- 📋 **My Donations** — Track your listings\n- 📋 **My Requests** — Track your requests\n- 📜 **Certificates** — View your awards\n- 🔔 **Notifications** — View updates\n- 👤 **Profile** — Edit your info\n\nHow can I help you today?"),
                [
                    ['text' => 'My Donations'],
                    ['text' => 'My Requests'],
                    ['text' => 'Available Food'],
                    ['text' => 'My Certificates'],
                ]
            );
        }

        if ($page === 'verify-otp') {
            return chatbot_json_response(
                chatbot_format_message("You're on the **OTP Verification** page! 📧\n\nCheck your email for a 6-digit verification code and enter it here to activate your account. The code expires in 10 minutes."),
                [
                    ['text' => 'Did not receive OTP'],
                    ['text' => 'How to register'],
                    ['text' => 'What is Sayog?'],
                ]
            );
        }

        // Ultimate fallback
        return $this->generate_fallback_response($message, $context);
    }
}
