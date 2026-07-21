<?php
/**
 * chatbot/intent_detector.php
 * Intent Detection Engine for the Sayog AI Chatbot.
 * 
 * Classifies user messages into predefined intents using keyword matching
 * and context-aware scoring. Designed to be easily extended with ML/AI
 * (OpenAI, Gemini) in the future.
 * 
 * Intents are organized by module: general, donation, request, volunteer,
 * auth, admin, social, and platform.
 */

class IntentDetector {
    
    /**
     * Intent definitions with keywords, priority, and required role.
     * Higher priority = matched first when multiple intents match.
     */
    private static $intents = [
        // ── GREETINGS ──
        'greeting' => [
            'keywords' => ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'namaste', 'नमस्ते', 'नमस्कार'],
            'priority' => 90,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'farewell' => [
            'keywords' => ['bye', 'goodbye', 'see you', 'thank you', 'thanks', 'exit', 'quit', 'bye bye'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── ABOUT SAYOG ──
        'about_sayog' => [
            'keywords' => ['what is sayog', 'about sayog', 'tell me about sayog', 'sayog', 'about', 'what is this', 'what does sayog do', 'how does sayog work', 'what is your mission'],
            'priority' => 60,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'mission_vision' => [
            'keywords' => ['mission', 'vision', 'goal', 'purpose', 'objective', 'what do you do', 'why sayog'],
            'priority' => 55,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── DONATION INTENTS ──
        'how_to_donate' => [
            'keywords' => ['how to donate', 'how do i donate', 'donate food', 'make a donation', 'create donation', 'post donation', 'start donating', 'donation process', 'how can i donate'],
            'priority' => 85,
            'module'   => 'donation',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'my_donations' => [
            'keywords' => ['my donations', 'my donation', 'show my donations', 'my donation history', 'list my donations', 'my donation list', 'view my donations', 'donation history'],
            'priority' => 85,
            'module'   => 'donation',
            'roles'    => ['donor', 'admin'],
        ],
        'donation_guidelines' => [
            'keywords' => ['donation guidelines', 'guidelines', 'food guidelines', 'what can i donate', 'accepted food', 'food items to donate', 'what food', 'donation rules', 'food safety'],
            'priority' => 70,
            'module'   => 'donation',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'available_food' => [
            'keywords' => ['available food', 'food listings', 'available donations', 'browse food', 'food list', 'show food', 'what food is available', 'current donations', 'active donations', 'food near me'],
            'priority' => 80,
            'module'   => 'donation',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'donation_status' => [
            'keywords' => ['donation status', 'status of my donation', 'pending donations', 'approved donations', 'donation tracking', 'track donation', 'donation progress'],
            'priority' => 75,
            'module'   => 'donation',
            'roles'    => ['donor', 'admin'],
        ],

        // ── REQUEST INTENTS ──
        'how_to_request' => [
            'keywords' => ['how to request', 'how do i request', 'request food', 'request donation', 'ask for food', 'need food', 'get food', 'receive food', 'how to get food'],
            'priority' => 85,
            'module'   => 'request',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'my_requests' => [
            'keywords' => ['my requests', 'my request', 'show my requests', 'request history', 'my request list', 'food requests', 'view my requests'],
            'priority' => 85,
            'module'   => 'request',
            'roles'    => ['consumer', 'donor', 'admin'],
        ],
        'request_status' => [
            'keywords' => ['request status', 'pending requests', 'approved requests', 'track request', 'request tracking', 'request progress', 'where is my request'],
            'priority' => 75,
            'module'   => 'request',
            'roles'    => ['consumer', 'donor', 'admin'],
        ],

        // ── VOLUNTEER INTENTS ──
        'become_volunteer' => [
            'keywords' => ['become a volunteer', 'volunteer', 'how to volunteer', 'join as volunteer', 'volunteer application', 'sign up volunteer', 'volunteer registration', 'apply volunteer', 'volunteer requirements', 'volunteer documents'],
            'priority' => 85,
            'module'   => 'volunteer',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'volunteer_deliveries' => [
            'keywords' => ['my deliveries', 'my assigned deliveries', 'delivery list', 'assigned pickups', 'volunteer tasks', 'my tasks', 'pickup instructions', 'delivery status'],
            'priority' => 85,
            'module'   => 'volunteer',
            'roles'    => ['volunteer', 'admin'],
        ],

        // ── AUTH INTENTS ──
        'how_to_register' => [
            'keywords' => ['how to register', 'create account', 'sign up', 'register account', 'how to sign up', 'create new account', 'registration', 'new account'],
            'priority' => 85,
            'module'   => 'auth',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo'],
        ],
        'how_to_login' => [
            'keywords' => ['how to login', 'login', 'sign in', 'log in', 'login help', 'cannot login', 'login problem'],
            'priority' => 85,
            'module'   => 'auth',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo'],
        ],
        'forgot_password' => [
            'keywords' => ['forgot password', 'reset password', 'change password', 'lost password', 'password reset', 'forget password', 'can\'t login', 'cannot remember password'],
            'priority' => 85,
            'module'   => 'auth',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo'],
        ],

        // ── CONTACT / SUPPORT ──
        'contact_info' => [
            'keywords' => ['contact', 'support', 'help', 'office address', 'location', 'phone number', 'email address', 'reach you', 'contact us', 'how to contact', 'where are you', 'office'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── STATISTICS ──
        'statistics' => [
            'keywords' => ['statistics', 'stats', 'total donations', 'total users', 'how many', 'platform stats', 'count', 'analytics', 'summary', 'overview', 'dashboard stats'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── TEAM ──
        'team_info' => [
            'keywords' => ['team', 'who developed', 'developer', 'creator', 'who built', 'team members', 'our team', 'founder', 'behind sayog'],
            'priority' => 60,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── ADMIN INTENTS ──
        'admin_stats' => [
            'keywords' => ['admin stats', 'admin dashboard', 'site stats', 'website stats', 'platform overview'],
            'priority' => 80,
            'module'   => 'admin',
            'roles'    => ['admin'],
        ],
        'pending_reviews' => [
            'keywords' => ['pending donations', 'pending reviews', 'pending approval', 'pending verification', 'unapproved donations', 'donations to review'],
            'priority' => 80,
            'module'   => 'admin',
            'roles'    => ['admin'],
        ],
        'pending_volunteers' => [
            'keywords' => ['pending volunteers', 'volunteer applications', 'new volunteers', 'volunteer requests', 'volunteer pending'],
            'priority' => 80,
            'module'   => 'admin',
            'roles'    => ['admin'],
        ],
        'today_registrations' => [
            'keywords' => ['today registrations', 'new users today', 'today signups', 'registrations today', 'users joined today'],
            'priority' => 80,
            'module'   => 'admin',
            'roles'    => ['admin'],
        ],
        'search_user' => [
            'keywords' => ['search user', 'find user', 'lookup user', 'user by email', 'find user by'],
            'priority' => 85,
            'module'   => 'admin',
            'roles'    => ['admin'],
        ],
        'generate_report' => [
            'keywords' => ['generate report', 'create report', 'export report', 'download report', 'report summary', 'monthly report', 'donation report'],
            'priority' => 75,
            'module'   => 'admin',
            'roles'    => ['admin'],
        ],

        // ── CERTIFICATE & RECOGNITION ──
        'certificate_info' => [
            'keywords' => ['certificate', 'appreciation', 'certificate of appreciation', 'recognition', 'award', 'donation certificate', 'get certificate', 'download certificate', 'share certificate'],
            'priority' => 80,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── RATING & REVIEW ──
        'rating_system' => [
            'keywords' => ['rating', 'review', 'rate', 'star', 'feedback', 'how to rate', 'rate donor', 'rate receiver', 'leave review', 'my rating', 'average rating'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── NOTIFICATIONS ──
        'notifications' => [
            'keywords' => ['notification', 'notifications', 'alerts', 'alert', 'notify', 'notification settings', 'turn off notifications', 'email notification', 'push notification', 'silent', 'mute'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── ACCOUNT SETTINGS ──
        'account_settings' => [
            'keywords' => ['edit profile', 'update profile', 'my profile', 'account settings', 'edit account', 'change name', 'update email', 'change phone', 'change address', 'update address', 'profile settings', 'manage account'],
            'priority' => 80,
            'module'   => 'auth',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'change_password' => [
            'keywords' => ['change password', 'update password', 'reset my password', 'new password', 'change my pass', 'change pass', 'password update', 'set password', 'create password'],
            'priority' => 85,
            'module'   => 'auth',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── FOOD SAFETY ──
        'food_safety' => [
            'keywords' => ['food safety', 'safe food', 'how to store food', 'food storage', 'food handling', 'hygiene', 'food hygiene', 'safe to eat', 'food temperature', 'food freshness', 'check food quality', 'expired food safety', 'leftover safety'],
            'priority' => 75,
            'module'   => 'donation',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── OTP & VERIFICATION ──
        'otp_verification' => [
            'keywords' => ['otp', 'verification', 'verify', 'email verification', 'verify account', 'verify email', 'otp code', 'verification code', 'did not receive otp', 'otp expired', 'resend otp', 'activate account'],
            'priority' => 85,
            'module'   => 'auth',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── PRIVACY & TERMS ──
        'privacy_policy' => [
            'keywords' => ['privacy', 'privacy policy', 'data protection', 'my data', 'personal information', 'data privacy', 'data security', 'information security', 'is my data safe', 'how is my data used', 'delete my data', 'delete account'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'terms_of_service' => [
            'keywords' => ['terms', 'terms of service', 'terms and conditions', 'terms & conditions', 'conditions', 'rules', 'platform rules', 'usage policy', 'acceptable use', 'community guidelines'],
            'priority' => 60,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── PICKUP & LOGISTICS ──
        'pickup_process' => [
            'keywords' => ['pickup', 'pick up', 'pick-up', 'coordinate pickup', 'arrange pickup', 'schedule pickup', 'pickup time', 'pickup location', 'where to pickup', 'when to pickup', 'pickup food', 'collect food', 'food collection', 'how to collect'],
            'priority' => 80,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── EXPIRY MANAGEMENT ──
        'expiry_management' => [
            'keywords' => ['expiry', 'expiration', 'expire', 'expiry time', 'how long', 'expiration date', 'food expiry', 'donation expires', 'expired donation', 'extend expiry', 'set expiry', 'time limit'],
            'priority' => 70,
            'module'   => 'donation',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── TRACKING ──
        'how_to_track' => [
            'keywords' => ['track', 'tracking', 'track my donation', 'track my request', 'donation progress', 'request progress', 'order status', 'where is my donation', 'status of donation', 'delivery status', 'check status', 'monitor'],
            'priority' => 80,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── NGO PARTNERSHIP ──
        'ngo_partnership' => [
            'keywords' => ['ngo', 'ngo partner', 'organization', 'partnership', 'partner with sayog', 'collaborate', 'institution', 'restaurant partnership', 'corporate partner', 'bulk donation', 'sponsor', 'food drive', 'charity partner'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── CANCELLATION ──
        'cancel_donation' => [
            'keywords' => ['cancel donation', 'remove donation', 'delete donation', 'donation cancel', 'stop donation', 'undo donation', 'withdraw donation'],
            'priority' => 80,
            'module'   => 'donation',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
        'cancel_request' => [
            'keywords' => ['cancel request', 'remove request', 'delete request', 'request cancel', 'stop request', 'undo request', 'withdraw request'],
            'priority' => 80,
            'module'   => 'request',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── REPORT ISSUE ──
        'report_issue' => [
            'keywords' => ['report', 'report issue', 'report problem', 'report user', 'complaint', 'file complaint', 'problem with', 'issue with', 'bad experience', 'unsafe food', 'report donation', 'report request', 'suspend user', 'block user'],
            'priority' => 75,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── BENEFITS OF REGISTRATION ──
        'benefits_registration' => [
            'keywords' => ['benefits of registering', 'why register', 'why create account', 'advantages of registration', 'what do i get', 'register benefits', 'sign up benefits', 'why join sayog', 'why should i join'],
            'priority' => 75,
            'module'   => 'auth',
            'roles'    => ['guest'],
        ],

        // ── WHATSAPP ──
        'whatsapp_integration' => [
            'keywords' => ['whatsapp', 'whatsapp support', 'chat on whatsapp', 'whatsapp group', 'whatsapp contact', 'whatsapp number', 'whatsapp integration'],
            'priority' => 70,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── LANGUAGE ──
        'language_support' => [
            'keywords' => ['language', 'nepali', 'नेपाली', 'english', 'change language', 'translation', 'multi language', 'nepali support', 'भाषा', 'switch language', 'translate'],
            'priority' => 60,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── MOBILE ACCESS ──
        'mobile_access' => [
            'keywords' => ['mobile app', 'android', 'iphone', 'ios', 'app download', 'mobile application', 'play store', 'app store', 'mobile version', 'mobile site', 'smartphone', 'phone access', 'mobile friendly'],
            'priority' => 60,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],

        // ── HELP / FALLBACK ──
        'help' => [
            'keywords' => ['help', 'what can you do', 'capabilities', 'features', 'available commands', 'what can i ask', 'how can you help', 'what do you know', 'options', 'menu', 'list of topics', 'show topics'],
            'priority' => 80,
            'module'   => 'general',
            'roles'    => ['guest', 'donor', 'consumer', 'volunteer', 'ngo', 'admin'],
        ],
    ];

    /**
     * Detect the most likely intent from a user message.
     *
     * @param string $message The user's message.
     * @param array  $context The current context (page, role, etc.).
     * @return array ['intent' => string, 'confidence' => float, 'original_message' => string]
     */
    public static function detect($message, $context = []) {
        $message = mb_strtolower(trim($message));
        if (empty($message)) {
            return [
                'intent' => 'unknown',
                'confidence' => 0,
                'original_message' => $message,
            ];
        }

        $user_role = $context['user_role'] ?? 'guest';
        $best_intent = 'unknown';
        $best_score = 0;

        foreach (self::$intents as $intent_name => $intent_config) {
            // Check role access
            if (!in_array($user_role, $intent_config['roles'])) {
                continue;
            }

            $score = 0;
            foreach ($intent_config['keywords'] as $keyword) {
                $keyword = mb_strtolower(trim($keyword));
                if (strpos($message, $keyword) !== false) {
                    // Exact match bonus
                    if ($message === $keyword) {
                        $score += 100;
                    } else {
                        // Longer keyword match = higher confidence
                        $length_bonus = min(strlen($keyword) / strlen($message) * 100, 90);
                        $score += $length_bonus;
                    }
                }
            }

            // Apply priority multiplier
            if ($score > 0) {
                $score = $score * ($intent_config['priority'] / 50);
            }

            if ($score > $best_score) {
                $best_score = $score;
                $best_intent = $intent_name;
            }
        }

        // Normalize confidence to 0-100
        $confidence = min($best_score, 100);

        return [
            'intent'          => $best_intent,
            'confidence'      => round($confidence, 1),
            'original_message' => $message,
            'context'         => $context,
        ];
    }

    /**
     * Get all available intents (for suggestions/help).
     */
    public static function get_available_intents($role = 'guest') {
        $available = [];
        foreach (self::$intents as $name => $config) {
            if (in_array($role, $config['roles'])) {
                $available[] = [
                    'intent' => $name,
                    'module' => $config['module'],
                ];
            }
        }
        return $available;
    }
}
