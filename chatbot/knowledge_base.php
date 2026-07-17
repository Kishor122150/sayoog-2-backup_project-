<?php
/**
 * chatbot/knowledge_base.php
 * Knowledge Base System for the Sayog AI Chatbot.
 * 
 * Provides static knowledge (answers to common questions) and
 * database-backed dynamic knowledge that admins can manage.
 * 
 * Features:
 * - Static fallback knowledge for common questions
 * - Database-loaded knowledge entries (admin-managed)
 * - Category-based organization
 * - Fuzzy matching for question similarity
 */

class KnowledgeBase {

    private $pdo;

    /**
     * Intent alias mapping: multiple detected intents can map to the same knowledge entry.
     * This ensures that synonyms and alternate phrasings all find the right answer.
     */
    private static $intent_aliases = [
        'how_to_donate'     => 'donation_process',
        'donation_status'   => 'my_donations',
        'how_to_request'    => 'request_process',
        'request_status'    => 'my_requests',
        'how_to_register'   => 'registration',
        'how_to_login'      => 'login_help',
        'forgot_password'   => 'forgot_password',
        'donation_guidelines' => 'donation_guidelines',
        'contact_info'      => 'contact_info',
        'office_address'    => 'office_address',
        'mission_vision'    => 'mission_vision',
        'services'          => 'services',
        'about_sayog'       => 'about_sayog',
        'how_it_works'      => 'how_it_works',
        'why_use_sayog'     => 'why_use_sayog',
        'become_volunteer'  => 'become_volunteer',
        'volunteer_documents' => 'volunteer_documents',
        'team_info'              => 'team_info',
        'volunteer_delivery'     => 'volunteer_delivery',
        'how_delivery_works'     => 'volunteer_delivery',
        'delivery_process'       => 'volunteer_delivery',
        'available_deliveries'   => 'available_deliveries',
        'track_delivery'         => 'track_delivery',
        'delivery_status'        => 'delivery_status',
        'delivery_lifecycle'     => 'delivery_lifecycle',
    ];

    /**
     * Static knowledge base (fallback when DB is empty or unreachable).
     * These are the "hardcoded" answers that admins can override via the admin panel.
     */
    private static $static_knowledge = [
        'about_sayog' => [
            'question' => 'What is Sayog?',
            'answer'   => 'Sayog is a smart food donation and redistribution platform built to connect surplus food with communities in need. We make it easy for donors, volunteers, NGOs, and consumers to share food and reduce waste across Nepal.',
            'category' => 'about',
        ],
        'mission_vision' => [
            'question' => 'What is Sayog\'s mission?',
            'answer'   => 'Sayog\'s mission is to build a kinder, more sustainable community by making food sharing easier and more accessible for everyone. We aim to reduce food waste while ensuring that no one goes hungry.',
            'category' => 'about',
        ],
        'how_it_works' => [
            'question' => 'How does Sayog work?',
            'answer'   => '**How Sayog works:**<br><br>1. **Create an Account** — Register for free.<br>2. **Donate Food** — Post available food with details like quantity, pickup location, and expiry time.<br>3. **Browse Listings** — Find donations near you.<br>4. **Request Food** — Send a request for available food.<br>5. **Donor Approves** — The donor reviews and approves your request.<br>6. **Pickup & Complete** — Coordinate pickup and mark the donation complete.',
            'category' => 'about',
        ],
        'why_use_sayog' => [
            'question' => 'Why should I use Sayog?',
            'answer'   => 'Sayog offers: ✅ Free registration & usage<br>✅ Reduce food waste<br>✅ Help your local community<br>✅ Easy-to-use platform<br>✅ Secure & transparent process<br>✅ Track your donations & requests<br>✅ Certificate of Appreciation for donors<br>✅ Dedicated volunteer network',
            'category' => 'about',
        ],

        // Donation knowledge
        'donation_process' => [
            'question' => 'How do I donate food?',
            'answer'   => '**To donate food:**<br><br>1. Log in to your Sayog account.<br>2. Go to your Dashboard and click **"Create Donation"**.<br>3. Fill in the food item, quantity, expiry time, and pickup address.<br>4. Optionally upload a photo or video of the food.<br>5. Submit for admin verification.<br>6. Once approved, your donation will appear in the public listings.<br>7. When someone requests your food, review and approve their request.<br>8. Coordinate pickup and mark the donation as completed.',
            'category' => 'donation',
        ],
        'accepted_food' => [
            'question' => 'What food items can I donate?',
            'answer'   => 'You can donate: 🍚 Cooked meals (rice, curry, etc.)<br>🥫 Packaged/ canned food (unexpired)<br>🍎 Fresh fruits & vegetables<br>🥖 Bread & bakery items<br>🥛 Dairy products (milk, yogurt)<br>🥩 Meat & poultry (properly packaged)<br>🥜 Dry goods (rice, dal, spices)<br>🥤 Beverages (unopened)<br>🍱 Leftovers (properly stored)<br><br>⚠️ All food must be **edible, properly stored, and within its expiry date**.',
            'category' => 'donation',
        ],
        'donation_guidelines' => [
            'question' => 'What are the donation guidelines?',
            'answer'   => '**Food Donation Guidelines:**<br><br>✅ Food must be **fresh and safe to eat**.<br>✅ All packaged items must be **unexpired**.<br>✅ Cooked food should be **less than 4 hours old** if not refrigerated.<br>✅ Clearly mention **ingredients** if someone has allergies.<br>✅ Provide **accurate quantity** information.<br>✅ Set a **realistic expiry time**.<br>✅ Keep your **pickup address correct** for smooth collection.<br>❌ Do not donate **spoiled, moldy, or expired** food.<br>❌ No **alcoholic beverages**.<br>❌ No **homemade alcohol or illegal substances**.',
            'category' => 'donation',
        ],

        // Request knowledge
        'request_process' => [
            'question' => 'How do I request food?',
            'answer'   => '**To request food:**<br><br>1. Log in to your Sayog account.<br>2. Go to **"Request Food"** in the dashboard or browse **Food Listings** on the public site.<br>3. Click **"Request"** on an available donation.<br>4. Enter the quantity you need and a brief message.<br>5. Wait for the donor to review and approve your request.<br>6. Once approved, coordinate **pickup** with the donor.<br>7. After receiving the food, mark the request as completed.',
            'category' => 'request',
        ],

        // Registration knowledge
        'registration' => [
            'question' => 'How do I create an account?',
            'answer'   => '**Creating an account is free and easy:**<br><br>1. Click **"Get Started"** or **"Sign Up"** from the homepage.<br>2. Fill in your name, email, address, and Nepal phone number.<br>3. Create a **strong password** (8+ characters with uppercase, lowercase, number & special character).<br>4. Verify your email via OTP.<br>5. Log in and start donating or requesting food!<br><br>👉 <a href="register.php" style="color:#059669;font-weight:600;">Click here to Register</a>',
            'category' => 'auth',
        ],
        'login_help' => [
            'question' => 'How do I log in?',
            'answer'   => '**To log in:**<br><br>1. Click **"Login"** from the top navigation.<br>2. Enter your registered email address.<br>3. Enter your password.<br>4. Click **"Sign In"**.<br><br>👉 <a href="login.php" style="color:#059669;font-weight:600;">Click here to Login</a><br><br>⚠️ If you are an admin, use the <a href="admin/admin-login.php" style="color:#059669;font-weight:600;">Admin Login</a> page instead.',
            'category' => 'auth',
        ],
        'forgot_password' => [
            'question' => 'I forgot my password. What should I do?',
            'answer'   => 'If you\'ve forgotten your password, please contact our support team for assistance. We can help you reset your password and regain access to your account. You can reach us through the <a href="contact.php" style="color:#059669;font-weight:600;">Contact Us</a> page.',
            'category' => 'auth',
        ],

        // Volunteer knowledge
        'become_volunteer' => [
            'question' => 'How do I become a volunteer?',
            'answer'   => '**To become a Sayog volunteer:**<br><br>1. Log in to your account.<br>2. Click **"Become a Volunteer"** in your dashboard sidebar.<br>3. Fill in your details including:<br>   - Personal information (name, DOB, gender)<br>   - Contact & address details<br>   - Emergency contact<br>   - Vehicle information (if available)<br>   - Availability (morning, afternoon, evening, weekend)<br>4. Upload required documents (citizenship, national ID, etc.).<br>5. Submit your application.<br>6. An admin will review and approve your application.<br>7. Once approved, you can start receiving delivery assignments!',
            'category' => 'volunteer',
        ],
        'volunteer_documents' => [
            'question' => 'What documents are required to become a volunteer?',
            'answer'   => 'Required documents may include: 📄 Citizenship certificate (front & back)<br>📄 National ID card<br>📄 College/student ID (if student)<br>📄 Driving license (if using a vehicle)<br>📄 Vehicle registration document (if applicable)<br><br>All documents are securely stored and used only for verification purposes.',
            'category' => 'volunteer',
        ],

        // Contact
        'contact_info' => [
            'question' => 'How can I contact Sayog?',
            'answer'   => '**You can reach us through:**<br><br>📧 Email: <a href="mailto:info@sayog.org">info@sayog.org</a><br>📞 Phone: +977-1-4XXXXXX<br>📍 Address: Kathmandu, Nepal<br>📝 <a href="contact.php" style="color:#059669;font-weight:600;">Contact Form</a> — Send us a message directly',
            'category' => 'contact',
        ],
        'office_address' => [
            'question' => 'Where is your office located?',
            'answer'   => 'Our office is located in **Kathmandu, Nepal**. For exact directions or to schedule a visit, please reach out through our <a href="contact.php" style="color:#059669;font-weight:600;">Contact Page</a>.',
            'category' => 'contact',
        ],

        // Team
        'team_info' => [
            'question' => 'Who developed Sayog?',
            'answer'   => 'Sayog was developed by a dedicated team of developers and designers committed to reducing food waste and helping communities in Nepal. Visit our <a href="team.php" style="color:#059669;font-weight:600;">Team Page</a> to meet the people behind the platform.',
            'category' => 'general',
        ],

        // Services
        'services' => [
            'question' => 'What services does Sayog provide?',
            'answer'   => '**Sayog provides:**<br><br>🍽️ **Food Donation Platform** — Donate surplus food easily<br>📋 **Food Listing & Discovery** — Browse available donations<br>🤝 **Request System** — Request food from donors<br>✅ **Admin Verification** — Quality & safety checks<br>📊 **Tracking System** — Track donations & requests<br>🧑‍🤝‍🧑 **Volunteer Network** — Delivery coordination<br>📜 **Digital Certificates** — Certificates of appreciation<br>📱 **WhatsApp Integration** — Easy communication',
            'category' => 'general',
        ],
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Resolve an intent name to its knowledge base key using aliases.
     * Falls back to the original intent if no alias is found.
     *
     * @param string $intent The detected intent.
     * @return string The resolved knowledge base key.
     */
    private function resolveIntent($intent) {
        return self::$intent_aliases[$intent] ?? $intent;
    }

    /**
     * Get an answer from the knowledge base (DB first, then static fallback).
     * Uses intent aliases to map synonyms to the same answer.
     *
     * @param string $intent The detected intent.
     * @return array|null ['question' => string, 'answer' => string, 'source' => 'db'|'static'] or null
     */
    public function get_answer($intent) {
        // Resolve intent alias first
        $resolvedIntent = $this->resolveIntent($intent);

        // 1. Try database first (search by both original and resolved intent)
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT question, answer, category 
                    FROM chatbot_knowledge 
                    WHERE (intent = ? OR intent = ? OR MATCH(question) AGAINST(? IN NATURAL LANGUAGE MODE)) 
                      AND is_active = 1 
                    ORDER BY is_primary DESC, id ASC 
                    LIMIT 1
                ");
                $stmt->execute([$intent, $resolvedIntent, $resolvedIntent]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['answer'])) {
                    $row['source'] = 'db';
                    return $row;
                }
            } catch (PDOException $e) {
                // Fall through to static knowledge
            }
        }

        // 2. Fall back to static knowledge (try resolved intent first, then original)
        $targetIntent = isset(self::$static_knowledge[$resolvedIntent]) ? $resolvedIntent : $intent;
        if (isset(self::$static_knowledge[$targetIntent])) {
            $entry = self::$static_knowledge[$targetIntent];
            return [
                'question' => $entry['question'],
                'answer'   => $entry['answer'],
                'category' => $entry['category'],
                'source'   => 'static',
            ];
        }

        return null;
    }

    /**
     * Search the knowledge base for a query (fuzzy search).
     *
     * @param string $query The search query.
     * @return array Matching knowledge entries.
     */
    public function search($query) {
        $results = [];
        $query = mb_strtolower(trim($query));

        // Search static knowledge
        foreach (self::$static_knowledge as $intent => $entry) {
            $question_lower = mb_strtolower($entry['question']);
            $answer_lower = mb_strtolower(strip_tags($entry['answer']));
            if (strpos($question_lower, $query) !== false || strpos($answer_lower, $query) !== false) {
                $results[] = [
                    'intent'   => $intent,
                    'question' => $entry['question'],
                    'answer'   => $entry['answer'],
                    'source'   => 'static',
                ];
            }
        }

        // Search DB knowledge
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT intent, question, answer 
                    FROM chatbot_knowledge 
                    WHERE (question LIKE ? OR answer LIKE ?) AND is_active = 1 
                    LIMIT 5
                ");
                $like = '%' . $query . '%';
                $stmt->execute([$like, $like]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $row['source'] = 'db';
                    $results[] = $row;
                }
            } catch (PDOException $e) {
                // Ignore
            }
        }

        return $results;
    }

    /**
     * Get all static knowledge entries (for admin reference).
     */
    public static function get_static_entries() {
        return self::$static_knowledge;
    }

    /**
     * Get knowledge entries by category from both static and DB sources.
     */
    public function get_by_category($category) {
        $results = [];

        // Static
        foreach (self::$static_knowledge as $intent => $entry) {
            if ($entry['category'] === $category) {
                $results[] = [
                    'intent'   => $intent,
                    'question' => $entry['question'],
                    'answer'   => $entry['answer'],
                    'source'   => 'static',
                ];
            }
        }

        // DB
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("
                    SELECT intent, question, answer 
                    FROM chatbot_knowledge 
                    WHERE category = ? AND is_active = 1 
                    ORDER BY is_primary DESC, id ASC
                ");
                $stmt->execute([$category]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $row['source'] = 'db';
                    $results[] = $row;
                }
            } catch (PDOException $e) {
                // Ignore
            }
        }

        return $results;
    }
}
