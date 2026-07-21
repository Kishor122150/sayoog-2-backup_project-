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
        // Certificate & Recognition
        'certificate_info'       => 'certificate_info',
        'certificate'            => 'certificate_info',
        'appreciation'           => 'certificate_info',
        'appreciation_certificate' => 'certificate_info',
        // Rating & Review
        'rating_system'          => 'rating_system',
        'review_system'          => 'rating_system',
        'how_to_rate'            => 'rating_system',
        // Notifications
        'notifications'          => 'notifications',
        'notification_settings'  => 'notifications',
        // Account settings
        'account_settings'       => 'account_settings',
        'edit_profile'           => 'account_settings',
        'change_password'        => 'change_password',
        'update_password'        => 'change_password',
        // Food & Safety
        'food_safety'            => 'food_safety',
        'food_storage'           => 'food_safety',
        'food_handling'          => 'food_safety',
        // OTP & Verification
        'otp_verification'       => 'otp_verification',
        'email_verification'     => 'otp_verification',
        'verify_account'         => 'otp_verification',
        // Privacy & Terms
        'privacy_policy'         => 'privacy_policy',
        'data_protection'        => 'privacy_policy',
        'terms_of_service'       => 'terms_of_service',
        'terms_conditions'       => 'terms_of_service',
        // Pickup & Logistics
        'pickup_process'         => 'pickup_process',
        'coordinate_pickup'      => 'pickup_process',
        'schedule_pickup'        => 'pickup_process',
        // Expiry
        'expiry_management'      => 'expiry_management',
        'expiry_time'            => 'expiry_management',
        'food_expiry'            => 'expiry_management',
        // Tracking
        'how_to_track'           => 'how_to_track',
        'track_donation'         => 'how_to_track',
        'track_request'          => 'how_to_track',
        // NGO
        'ngo_partnership'         => 'ngo_partnership',
        'partner_with_sayog'     => 'ngo_partnership',
        'organization_partner'   => 'ngo_partnership',
        // Cancellation
        'cancel_donation'        => 'cancel_donation',
        'cancel_request'         => 'cancel_request',
        'how_to_cancel'          => 'cancel_donation',
        // Reporting
        'report_issue'           => 'report_issue',
        'report_problem'         => 'report_issue',
        'report_user'            => 'report_issue',
        // Miscellaneous
        'benefits_of_registration' => 'benefits_registration',
        'why_register'           => 'benefits_registration',
        'mobile_access'          => 'mobile_access',
        'whatsapp_integration'   => 'whatsapp_integration',
        'language'               => 'language_support',
        'multi_language'         => 'language_support',
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
            'answer'   => 'If you\'ve forgotten your password, please contact our support team for assistance. We can help you reset your password and regain access to your account. You can reach us through the <a href="/frontend/contact.php" style="color:#059669;font-weight:600;">Contact Us</a> page.',
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
            'answer'   => '**You can reach us through:**<br><br>📧 Email: <a href="mailto:info@sayog.org">info@sayog.org</a><br>📞 Phone: +977-1-4XXXXXX<br>📍 Address: Kathmandu, Nepal<br>📝 <a href="/frontend/contact.php" style="color:#059669;font-weight:600;">Contact Form</a> — Send us a message directly',
            'category' => 'contact',
        ],
        'office_address' => [
            'question' => 'Where is your office located?',
            'answer'   => 'Our office is located in **Kathmandu, Nepal**. For exact directions or to schedule a visit, please reach out through our <a href="/frontend/contact.php" style="color:#059669;font-weight:600;">Contact Page</a>.',
            'category' => 'contact',
        ],

        // Team
        'team_info' => [
            'question' => 'Who developed Sayog?',
            'answer'   => 'Sayog was developed by a dedicated team of developers and designers committed to reducing food waste and helping communities in Nepal. Visit our <a href="/frontend/team.php" style="color:#059669;font-weight:600;">Team Page</a> to meet the people behind the platform.',
            'category' => 'general',
        ],

        // Services
        'services' => [
            'question' => 'What services does Sayog provide?',
            'answer'   => '**Sayog provides:**<br><br>🍽️ **Food Donation Platform** — Donate surplus food easily<br>📋 **Food Listing & Discovery** — Browse available donations<br>🤝 **Request System** — Request food from donors<br>✅ **Admin Verification** — Quality & safety checks<br>📊 **Tracking System** — Track donations & requests<br>🧑‍🤝‍🧑 **Volunteer Network** — Delivery coordination<br>📜 **Digital Certificates** — Certificates of appreciation<br>📱 **WhatsApp Integration** — Easy communication',
            'category' => 'general',
        ],

        // ── CERTIFICATE & RECOGNITION ──
        'certificate_info' => [
            'question' => 'How do I get a certificate of appreciation?',
            'answer'   => '**Certificate of Appreciation:**<br><br>🎉 Sayog awards a **Certificate of Appreciation** to donors who complete food donations.<br><br>**How it works:**<br>✅ Once your donation is marked as **completed**, a certificate is automatically generated.<br>✅ You can view and download your certificate from your **Dashboard**.<br>✅ Certificates include your name, donation details, and a unique ID.<br>✅ You can share your certificate on social media to inspire others!<br><br>👉 Go to your Dashboard → **Certificates** section to view all your awards.',
            'category' => 'recognition',
        ],

        // ── RATING & REVIEW ──
        'rating_system' => [
            'question' => 'How does the rating system work?',
            'answer'   => '**Rating & Review System:**<br><br>⭐ After a donation is completed, both donors and receivers can rate each other.<br><br>**How it works:**<br>✅ Rate from **1 to 5 stars** based on your experience.<br>✅ Leave a brief **review comment**.<br>✅ Ratings help build trust in the community.<br>✅ Your average rating is displayed on your profile.<br>✅ All ratings are **verified** (only completed transactions can be rated).<br><br>🌟 **Tip:** Being polite, punctual, and communicative leads to great reviews!',
            'category' => 'general',
        ],

        // ── NOTIFICATIONS ──
        'notifications' => [
            'question' => 'How do notifications work on Sayog?',
            'answer'   => '**Notifications System:**<br><br>🔔 Stay updated with real-time alerts about your donations, requests, and platform activity.<br><br>**What you\'ll be notified about:**<br>✅ When your donation is **approved** by admin<br>✅ When someone **requests** your food<br>✅ When your request is **approved** or **rejected**<br>✅ When a donation is about to **expire**<br>✅ New **volunteer assignments**<br>✅ **Messages** from donors or receivers<br><br>You can view all notifications in your **Dashboard → Notifications** section.',
            'category' => 'general',
        ],

        // ── ACCOUNT SETTINGS ──
        'account_settings' => [
            'question' => 'How do I edit my profile or account settings?',
            'answer'   => '**Managing Your Account:**<br><br>👤 You can update your profile information anytime from your Dashboard.<br><br>**What you can edit:**<br>✅ **Profile Name** — Update your display name<br>✅ **Email** — Change your email address (requires verification)<br>✅ **Phone Number** — Update your Nepal phone number<br>✅ **Address** — Change your pickup/delivery address<br>✅ **Profile Photo** — Upload or change your photo<br><br>👉 Go to your **Dashboard → Profile** section to make changes.',
            'category' => 'auth',
        ],
        'change_password' => [
            'question' => 'How do I change my password?',
            'answer'   => '**Changing Your Password:**<br><br>🔐 Keeping your account secure is important!<br><br>**Steps to change password:**<br>1. Log in to your account.<br>2. Go to **Dashboard → Profile → Change Password**.<br>3. Enter your **current password**.<br>4. Enter your **new password** (8+ chars, mix of uppercase, lowercase, numbers & special chars).<br>5. Confirm the new password.<br>6. Click **Save Changes**.<br><br>⚠️ **Tip:** Use a unique password you don\'t use on other sites. Never share your password with anyone!',
            'category' => 'auth',
        ],

        // ── FOOD SAFETY ──
        'food_safety' => [
            'question' => 'What food safety guidelines should I follow?',
            'answer'   => '**Food Safety Tips:**<br><br>🍽️ Proper food handling ensures everyone stays safe and healthy.<br><br>**For Donors:**<br>✅ Keep **hot foods hot** (above 60°C / 140°F) and **cold foods cold** (below 4°C / 40°F)<br>✅ Package food in **clean, sealed containers**<br>✅ Label food with **name, date, and ingredients**<br>✅ Transport food in **clean, insulated bags**<br>✅ Donate food **within 2 hours of cooking** if not refrigerated<br><br>**For Receivers:**<br>✅ Check food **immediately upon pickup**<br>✅ Verify the food **looks, smells, and feels fresh**<br>✅ Reheat cooked food to **at least 75°C / 165°F** before eating<br>✅ Store food in the **refrigerator** if not consuming within 2 hours<br><br>⚠️ When in doubt, **throw it out** — better safe than sorry!',
            'category' => 'donation',
        ],

        // ── OTP & VERIFICATION ──
        'otp_verification' => [
            'question' => 'How does OTP/email verification work?',
            'answer'   => '**Account Verification:**<br><br>📧 Sayog uses OTP (One-Time Password) verification to secure your account.<br><br>**When verification is needed:**<br>✅ **New Registration** — Verify your email address after signing up<br>✅ **Password Reset** — Confirm your identity before resetting<br>✅ **Email Change** — Verify your new email address<br><br>**How it works:**<br>1. An OTP code is sent to your registered **email address**.<br>2. Enter the 6-digit code on the verification page.<br>3. Your account is verified instantly!<br><br>⏳ OTPs expire after **10 minutes** for security. Didn\'t receive it? Check your spam folder or request a new one.',
            'category' => 'auth',
        ],

        // ── PRIVACY & SECURITY ──
        'privacy_policy' => [
            'question' => 'How does Sayog protect my privacy and data?',
            'answer'   => '**Privacy & Data Protection:**<br><br>🔒 Your privacy is our priority. Sayog follows strict data protection practices.<br><br>**What we do:**<br>✅ Your personal information is **never shared** with third parties without your consent.<br>✅ Passwords are **encrypted** using industry-standard hashing.<br>✅ All data transmissions use **secure connections**.<br>✅ You control what information is visible on your profile.<br>✅ You can request **account deletion** and data removal at any time.<br><br>**What we collect:**<br>📝 Name, email, phone, address — only what\'s needed for food donations.<br><br>📖 Read our full Privacy Policy on the website for complete details.',
            'category' => 'general',
        ],
        'terms_of_service' => [
            'question' => 'What are the terms of service for using Sayog?',
            'answer'   => '**Terms of Service (Summary):**<br><br>📋 By using Sayog, you agree to:<br><br>✅ **Be respectful** — Treat all users with kindness and respect<br>✅ **Provide accurate information** — Honest listings and requests<br>✅ **Follow food safety guidelines** — Only donate safe, edible food<br>✅ **Communicate promptly** — Respond to requests and messages in a timely manner<br>✅ **Complete transactions** — Follow through on your commitments<br><br>❌ **Do not** misuse the platform, post inappropriate content, or engage in fraudulent activity.<br><br>⚠️ Violations may result in account suspension or permanent ban.<br><br>📖 Read the full Terms & Conditions on our website for complete details.',
            'category' => 'general',
        ],

        // ── PICKUP & LOGISTICS ──
        'pickup_process' => [
            'question' => 'How do I coordinate pickup of donated food?',
            'answer'   => '**Pickup Coordination:**<br><br>🚗 After a donation request is approved, here\'s how pickup works:<br><br>**Step 1: Contact**<br>🤝 The donor and receiver connect via the platform to arrange pickup time.<br><br>**Step 2: Agree on Location & Time**<br>📍 Pickup is typically at the **donor\'s address** listed in the donation.<br>⏰ Agree on a specific time that works for both parties.<br><br>**Step 3: Pickup**<br>📦 The receiver (or volunteer) arrives at the agreed location.<br>✅ Verify the food quantity and quality matches the listing.<br><br>**Step 4: Complete**<br>✅ Both parties mark the transaction as **completed** on the platform.<br>⭐ Don\'t forget to **rate each other** after completion!',
            'category' => 'general',
        ],

        // ── EXPIRY MANAGEMENT ──
        'expiry_management' => [
            'question' => 'How does expiry time work for donations?',
            'answer'   => '**Donation Expiry Management:**<br><br>⏰ Every food donation has an **expiry time** to ensure food safety.<br><br>**How it works:**<br>✅ When creating a donation, you set a **realistic expiry time** (e.g., 2-4 hours for cooked food).<br>✅ Donations are visible in listings **until they expire**.<br>✅ When a donation expires, its status automatically changes to **cancelled**.<br>✅ Receivers can only request food that is **still available** (not expired).<br><br>**Tips for Donors:**<br>🎯 Set expiry times **realistically** — too short means fewer people can request.<br>🎯 If food is still good, you can **extend** the expiry time from your Dashboard.<br>🎯 Mark donation as **completed** as soon as it\'s picked up to keep listings accurate.',
            'category' => 'donation',
        ],

        // ── HOW TO TRACK ──
        'how_to_track' => [
            'question' => 'How do I track my donations and requests?',
            'answer'   => '**Tracking Donations & Requests:**<br><br>📊 Monitor the progress of all your activities from the Dashboard.<br><br>**Track Donations:**<br>✅ View all donations you\'ve made<br>✅ See current status: Pending Review → Available → Requested → Completed<br>✅ Check who has requested your food<br>✅ See expiry countdown timers<br><br>**Track Requests:**<br>✅ View all food requests you\'ve made<br>✅ See current status: Pending → Approved → Completed<br>✅ Contact the donor directly after approval<br><br>👉 Go to your **Dashboard → My Donations** or **My Requests** sections.',
            'category' => 'general',
        ],

        // ── NGO PARTNERSHIP ──
        'ngo_partnership' => [
            'question' => 'Can NGOs or organizations partner with Sayog?',
            'answer'   => '**NGO & Organization Partnerships:**<br><br>🤝 Yes! Sayog welcomes partnerships with NGOs, community organizations, and institutions.<br><br>**How NGOs can join:**<br>✅ Register as an **NGO account** on the platform.<br>✅ Receive bulk food donations for distribution.<br>✅ Coordinate with volunteers for large-scale pickups.<br>✅ Track all donations received through the dashboard.<br>✅ Get **priority notifications** for large donations.<br><br>**Benefits for Organizations:**<br>🏢 Restaurants, hotels, and catering services can donate surplus food at scale.<br>📦 Corporate partners can organize **food drive events**.<br><br>📧 For partnership inquiries, contact us at **partners@sayog.org**.',
            'category' => 'general',
        ],

        // ── CANCELLATION ──
        'cancel_donation' => [
            'question' => 'How do I cancel a donation or request?',
            'answer'   => '**Cancelling Donations & Requests:**<br><br>❌ Sometimes plans change — here\'s how to cancel.<br><br>**Cancel a Donation:**<br>✅ Go to **Dashboard → My Donations**.<br>✅ Find the donation you want to cancel.<br>✅ Click **Cancel Donation** — only available if no requests are pending.<br>✅ If there\'s an active request, you need to **decline** it first, then cancel.<br><br>**Cancel a Request:**<br>✅ Go to **Dashboard → My Requests**.<br>✅ Find the request you want to cancel.<br>✅ Click **Cancel Request** — available for pending requests.<br>✅ If already approved, contact the donor to cancel.<br><br>⚠️ Frequent cancellations may affect your **reliability rating**.',
            'category' => 'general',
        ],
        'cancel_request' => [
            'question' => 'How do I cancel a food request?',
            'answer'   => '**To cancel a food request:**<br><br>1. Go to your **Dashboard → My Requests**.<br>2. Find the pending request you want to cancel.<br>3. Click the **Cancel** button.<br>4. Confirm the cancellation.<br><br>✅ If the request is already **approved**, contact the donor directly through the platform to coordinate cancellation.',
            'category' => 'general',
        ],

        // ── REPORT AN ISSUE ──
        'report_issue' => [
            'question' => 'How do I report an issue or problem?',
            'answer'   => '**Reporting Issues:**<br><br>🚨 If you encounter any problems on Sayog, here\'s how to report them:<br><br>**Report a User:**<br>✅ If a donor or receiver is behaving inappropriately, you can report them from your Dashboard.<br>✅ Your report is **confidential** — the reported user won\'t know who reported them.<br><br>**Report a Technical Issue:**<br>✅ Contact us through the **Contact Form** with details about the problem.<br>✅ Include screenshots if possible for faster resolution.<br><br>**Report Food Quality Issues:**<br>✅ If donated food is spoiled or unsafe, report it immediately.<br>✅ The admin team will investigate and take appropriate action.<br><br>📧 For urgent issues, email us directly at **support@sayog.org**.',
            'category' => 'general',
        ],

        // ── BENEFITS OF REGISTRATION ──
        'benefits_registration' => [
            'question' => 'Why should I register on Sayog?',
            'answer'   => '**Benefits of Registering:**<br><br>🎉 Creating a free account unlocks all features:<br><br>✅ **Donate Food** — Post food donations for others to claim<br>✅ **Request Food** — Browse and request available donations<br>✅ **Track Everything** — Monitor your donations & requests in real-time<br>✅ **Get Certificates** — Earn certificates of appreciation for donations<br>✅ **Join as Volunteer** — Apply to become a delivery volunteer<br>✅ **Rate & Review** — Build trust with ratings and reviews<br>✅ **Notifications** — Stay updated on your activities<br>✅ **Message Others** — Communicate with donors and receivers<br><br>🚀 **It\'s completely free!** Register now and start making a difference.',
            'category' => 'auth',
        ],

        // ── WHATSAPP INTEGRATION ──
        'whatsapp_integration' => [
            'question' => 'Does Sayog have WhatsApp integration?',
            'answer'   => '**WhatsApp Integration:**<br><br>💬 Yes! Sayog integrates with WhatsApp for easy communication.<br><br>**What you can do:**<br>✅ **Contact Support** via WhatsApp — quick responses to your queries<br>✅ **Share Donations** — Share food listings with friends and family<br>✅ **Notify Volunteers** — Coordinate pickups via WhatsApp groups<br><br>📱 Simply click the **WhatsApp icon** on the website or contact form to start chatting!<br><br>👉 <a href="/whatsapp_add.php" style="color:#059669;font-weight:600;">Connect via WhatsApp</a>',
            'category' => 'general',
        ],

        // ── LANGUAGE SUPPORT ──
        'language_support' => [
            'question' => 'Does Sayog support multiple languages?',
            'answer'   => '**Language Support:**<br><br>🌐 Sayog is committed to accessibility for all users in Nepal.<br><br>✅ **English** — Primary language of the platform<br>✅ **Nepali (नेपाली)** — Partial support with ongoing expansion<br><br>We are working to add full support for more languages including:<br>✅ **Maithili** <br>✅ **Bhojpuri** <br>✅ **Newari** <br><br>Use the **language toggle** in the website header to switch between available languages.',
            'category' => 'general',
        ],

        // ── VOLUNTEER DELIVERY ──
        'volunteer_delivery' => [
            'question' => 'How does volunteer food delivery work?',
            'answer'   => '**Volunteer Delivery Process:**<br><br>🚚 Sayog volunteers help transport food from donors to receivers who cannot pick up themselves.<br><br>**How it works:**<br>1️⃣ A donation request is created and approved.<br>2️⃣ If the receiver cannot pick up, a **volunteer** is assigned.<br>3️⃣ The volunteer receives pickup and drop-off details.<br>4️⃣ The volunteer picks up the food from the donor.<br>5️⃣ The volunteer delivers the food to the receiver.<br>6️⃣ Both parties confirm delivery — **completed!** ✅<br><br>**Volunteer Requirements:**<br>✅ Must be an **approved volunteer** on the platform.<br>✅ Should have a **vehicle** (bike, scooter, or car) for transport.<br>✅ Must follow **food safety** guidelines during transport.<br><br>👉 Become a volunteer from your **Dashboard → Become a Volunteer**.',
            'category' => 'volunteer',
        ],

        // ── MOBILE ACCESS ──
        'mobile_access' => [
            'question' => 'Can I use Sayog on my mobile phone?',
            'answer'   => '**Mobile Access:**<br><br>📱 Yes! Sayog is fully **mobile-responsive** and works great on all devices.<br><br>**Ways to access Sayog on mobile:**<br>✅ **Mobile Browser** — Visit the website on your phone\'s browser. The site automatically adapts to your screen size.<br>✅ **Full Functionality** — All features including donations, requests, dashboard, and chat are available on mobile.<br>✅ **WhatsApp Integration** — Quick access via WhatsApp for support.<br><br>Currently Sayog is a **web-based platform** optimized for mobile browsers. A dedicated mobile app is being explored for future release!',
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
