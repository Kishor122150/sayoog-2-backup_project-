<?php
/**
 * chatbot/database_engine.php
 * Database Query Engine for the Sayog AI Chatbot.
 * 
 * Retrieves real-time information from the database while enforcing
 * strict Role-Based Access Control (RBAC). Users can only access
 * their own data. No sensitive information is ever exposed.
 * 
 * Security principles:
 * - Never expose raw SQL, table names, or DB structure
 * - Always scope queries by user_id (authenticated users only)
 * - Never allow destructive operations (DELETE, UPDATE, INSERT, DROP)
 * - Use parameterized queries exclusively
 * - Sanitize all output
 */

class DatabaseEngine {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Execute a safe, read-only database query based on intent and context.
     *
     * @param string $intent  The detected intent.
     * @param array  $context Current context (user_id, role, etc.).
     * @return array ['success' => bool, 'data' => mixed, 'message' => string]
     */
    public function query($intent, $context) {
        $user_id = (int)($context['user_id'] ?? 0);
        $role    = $context['user_role'] ?? 'guest';

        try {
            switch ($intent) {

                // ── PUBLIC STATISTICS ──
                case 'statistics':
                    return $this->get_public_statistics();

                // ── AVAILABLE FOOD ──
                case 'available_food':
                    return $this->get_available_food($user_id, $role);

                // ── DONATION INTENTS ──
                case 'my_donations':
                    return $this->get_my_donations($user_id, $role);

                case 'donation_status':
                    return $this->get_donation_status($user_id, $role);

                // ── REQUEST INTENTS ──
                case 'my_requests':
                    return $this->get_my_requests($user_id, $role);

                case 'request_status':
                    return $this->get_request_status($user_id, $role);

                // ── VOLUNTEER INTENTS ──
                case 'volunteer_deliveries':
                    return $this->get_volunteer_deliveries($user_id, $role);

                // ── TEAM INFO ──
                case 'team_info':
                    return $this->get_team_info();

                // ── CONTACT INFO (from CMS) ──
                case 'contact_info':
                    return $this->get_contact_info();

                // ── ADMIN INTENTS ──
                case 'admin_stats':
                    return $this->get_admin_stats($role);

                case 'pending_reviews':
                    return $this->get_pending_reviews($role);

                case 'pending_volunteers':
                    return $this->get_pending_volunteers($role);

                // ── NEW: CERTIFICATE INFO ──
                case 'certificate_info':
                    return $this->get_certificate_info($user_id, $role);

                // ── NEW: NOTIFICATIONS ──
                case 'notifications':
                    return $this->get_notifications($user_id, $role);

                // ── NEW: TRACKING ──
                case 'how_to_track':
                    return $this->get_tracking_info($user_id, $role);

                // ── NEW: PICKUP INFO ──
                case 'pickup_process':
                    return $this->get_pickup_info($user_id, $role);

                case 'today_registrations':
                    return $this->get_today_registrations($role);

                default:
                    return [
                        'success' => false,
                        'data'    => null,
                        'message' => 'I don\'t have data for that specific query yet.',
                    ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'I encountered an issue retrieving that information. Please try again.',
            ];
        }
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE QUERY METHODS
    // ──────────────────────────────────────────────────────────────

    /**
     * Get public platform statistics (safe for all roles).
     */
    private function get_public_statistics() {
        $stats = [];

        $stats['total_users'] = (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_donations'] = (int)$this->pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
        $stats['total_successful'] = (int)$this->pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'completed'")->fetchColumn();
        $stats['total_donors'] = (int)$this->pdo->query("SELECT COUNT(DISTINCT donor_id) FROM donations")->fetchColumn();

        // Distinct consumer IDs from requests
        $stats['total_consumers'] = (int)$this->pdo->query("SELECT COUNT(DISTINCT consumer_id) FROM requests")->fetchColumn();
        
        // Approved volunteers
        $stats['total_volunteers'] = (int)$this->pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'approved'")->fetchColumn();

        // Available food items
        $stats['available_food'] = (int)$this->pdo->query("SELECT COUNT(*) FROM donations WHERE verification_status = 'approved' AND status IN ('available', 'requested')")->fetchColumn();

        // Pending requests
        $stats['pending_requests'] = (int)$this->pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetchColumn();

        return [
            'success' => true,
            'data'    => $stats,
            'message' => $this->format_statistics_message($stats),
        ];
    }

    /**
     * Format statistics into a friendly message.
     */
    private function format_statistics_message($stats) {
        return "📊 **Sayog Platform Statistics**<br><br>"
            . "👥 **Total Users:** {$stats['total_users']}<br>"
            . "🍽️ **Total Donations:** {$stats['total_donations']}<br>"
            . "✅ **Successful Donations:** {$stats['total_successful']}<br>"
            . "🙋 **Donors:** {$stats['total_donors']}<br>"
            . "🤝 **Consumers:** {$stats['total_consumers']}<br>"
            . "🧑‍🤝‍🧑 **Volunteers:** {$stats['total_volunteers']}<br>"
            . "📦 **Available Food Items:** {$stats['available_food']}<br>"
            . "⏳ **Pending Requests:** {$stats['pending_requests']}<br><br>"
            . "Together, we're making a difference! 🌍";
    }

    /**
     * Get available food listings (limited info for guests, full for logged-in).
     */
    private function get_available_food($user_id, $role) {
        $stmt = $this->pdo->prepare("
            SELECT d.id, d.food_item, d.quantity, d.expiry_time, d.pickup_address, 
                   d.description, d.image_path, d.created_at,
                   u.name AS donor_name
            FROM donations d 
            JOIN users u ON d.donor_id = u.id 
            WHERE d.verification_status = 'approved' 
              AND d.status IN ('available', 'requested')
            ORDER BY d.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'success' => true,
                'data'    => [],
                'message' => "No food donations are currently available. Please check back later! 🍽️",
            ];
        }

        $message = "🍽️ **Available Food Donations:**<br><br>";
        $count = 0;
        foreach ($items as $item) {
            $count++;
            $expiry = date('d M Y H:i', strtotime($item['expiry_time']));
            $message .= "{$count}. **{$item['food_item']}** — {$item['quantity']}<br>";
            $message .= "   📍 {$item['pickup_address']}<br>";
            $message .= "   ⏰ Expires: {$expiry}<br>";
            $message .= "   👤 Donor: {$item['donor_name']}<br><br>";
        }

        if ($role === 'guest') {
            $message .= "💡 **Login or Register** to request food!<br>";
            $message .= "👉 <a href='login.php' style='color:#059669;font-weight:600;'>Login</a> | <a href='register.php' style='color:#059669;font-weight:600;'>Register</a>";
        } else {
            $message .= "👉 <a href='/frontend/donations.php' style='color:#059669;font-weight:600;'>View All Listings</a> | <a href='dashboard.php?page=request-donation' style='color:#059669;font-weight:600;'>Request Food</a>";
        }

        return [
            'success' => true,
            'data'    => $items,
            'message' => $message,
        ];
    }

    /**
     * Get the current user's donations (RBAC enforced).
     */
    private function get_my_donations($user_id, $role) {
        if ($user_id <= 0 && $role !== 'admin') {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Please log in to view your donations. 🔒',
            ];
        }

        $query = "SELECT d.* FROM donations d WHERE ";
        $params = [];

        if ($role === 'admin') {
            $query .= "1=1";
        } else {
            $query .= "d.donor_id = ?";
            $params[] = $user_id;
        }
        $query .= " ORDER BY d.created_at DESC LIMIT 10";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'success' => true,
                'data'    => [],
                'message' => 'You haven\'t made any donations yet. 🍽️ Would you like to create one?',
            ];
        }

        $message = "📋 **Your Donations:**<br><br>";
        foreach ($items as $i => $item) {
            $num = $i + 1;
            $status = ucfirst(str_replace('_', ' ', $item['status']));
            $message .= "{$num}. **{$item['food_item']}** — {$item['quantity']}<br>";
            $message .= "   Status: **{$status}** | Expires: " . date('d M Y', strtotime($item['expiry_time'])) . "<br>";
        }

        $message .= "<br>👉 <a href='dashboard.php?page=create-donation' style='color:#059669;font-weight:600;'>Create New Donation</a>";

        return [
            'success' => true,
            'data'    => $items,
            'message' => $message,
        ];
    }

    /**
     * Get current user's donation status.
     */
    private function get_donation_status($user_id, $role) {
        // Same as my_donations for now - but focused on pending
        if ($user_id <= 0 && $role !== 'admin') {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Please log in to check your donation status. 🔒',
            ];
        }

        $query = "SELECT d.*, d.status FROM donations d WHERE ";
        $params = [];
        if ($role === 'admin') {
            $query .= "1=1";
        } else {
            $query .= "d.donor_id = ?";
            $params[] = $user_id;
        }
        $query .= " ORDER BY d.created_at DESC LIMIT 10";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'success' => true,
                'data'    => [],
                'message' => 'No donations found to track.',
            ];
        }

        $pending = 0;
        $approved = 0;
        $completed = 0;
        $message = "📊 **Donation Status Overview:**<br><br>";
        foreach ($items as $item) {
            switch ($item['status']) {
                case 'pending_review': $pending++; break;
                case 'available': case 'requested': case 'accepted': $approved++; break;
                case 'completed': $completed++; break;
            }
        }

        $message .= "⏳ Pending Review: **{$pending}**<br>";
        $message .= "✅ Active/Approved: **{$approved}**<br>";
        $message .= "🎉 Completed: **{$completed}**<br>";
        $message .= "<br>👉 <a href='dashboard.php?page=donation_approval' style='color:#059669;font-weight:600;'>Check Approval Status</a>";

        return [
            'success' => true,
            'data'    => ['pending' => $pending, 'approved' => $approved, 'completed' => $completed],
            'message' => $message,
        ];
    }

    /**
     * Get current user's food requests.
     */
    private function get_my_requests($user_id, $role) {
        if ($user_id <= 0 && $role !== 'admin') {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Please log in to view your requests. 🔒',
            ];
        }

        $query = "
            SELECT r.*, d.food_item, d.quantity AS donation_qty, u.name AS donor_name
            FROM requests r 
            JOIN donations d ON r.donation_id = d.id 
            JOIN users u ON d.donor_id = u.id 
            WHERE ";
        $params = [];
        if ($role === 'admin') {
            $query .= "1=1";
        } else {
            $query .= "r.consumer_id = ?";
            $params[] = $user_id;
        }
        $query .= " ORDER BY r.created_at DESC LIMIT 10";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'success' => true,
                'data'    => [],
                'message' => 'You haven\'t made any food requests yet. Browse available food and request some! 🍽️',
            ];
        }

        $message = "📋 **Your Food Requests:**<br><br>";
        foreach ($items as $i => $item) {
            $num = $i + 1;
            $status = ucfirst($item['status']);
            $message .= "{$num}. **{$item['food_item']}** from {$item['donor_name']}<br>";
            $message .= "   Status: **{$status}** | Qty: {$item['quantity_requested']}<br>";
        }

        $message .= "<br>👉 <a href='dashboard.php?page=manage-request' style='color:#059669;font-weight:600;'>Manage Requests</a>";

        return [
            'success' => true,
            'data'    => $items,
            'message' => $message,
        ];
    }

    /**
     * Get request status for current user.
     */
    private function get_request_status($user_id, $role) {
        if ($user_id <= 0 && $role !== 'admin') {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Please log in to check your request status. 🔒',
            ];
        }

        $query = "SELECT r.status FROM requests r WHERE ";
        $params = [];
        if ($role === 'admin') {
            $query .= "1=1";
        } else {
            $query .= "r.consumer_id = ?";
            $params[] = $user_id;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pending = 0;
        $approved = 0;
        $completed = 0;
        foreach ($all as $r) {
            switch ($r['status']) {
                case 'pending': $pending++; break;
                case 'approved': $approved++; break;
                case 'completed': $completed++; break;
            }
        }

        $message = "📊 **Request Status Overview:**<br><br>";
        $message .= "⏳ Pending: **{$pending}**<br>";
        $message .= "✅ Approved: **{$approved}**<br>";
        $message .= "🎉 Completed: **{$completed}**<br>";
        $message .= "<br>👉 <a href='dashboard.php?page=track-request' style='color:#059669;font-weight:600;'>Track Requests</a>";

        return [
            'success' => true,
            'data'    => ['pending' => $pending, 'approved' => $approved, 'completed' => $completed],
            'message' => $message,
        ];
    }

    /**
     * Get volunteer deliveries (for approved volunteers).
     */
    private function get_volunteer_deliveries($user_id, $role) {
        if ($user_id <= 0) {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'Please log in as a volunteer to see your deliveries. 🔒',
            ];
        }

        // Check if user is an approved volunteer
        $stmt = $this->pdo->prepare("SELECT status FROM volunteers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $vol = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vol || $vol['status'] !== 'approved') {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'You are not registered as an approved volunteer yet. <a href="become-volunteer.php" style="color:#059669;font-weight:600;">Apply here</a>.',
            ];
        }

        // For now, show nearby available donations as potential deliveries
        $stmt = $this->pdo->prepare("
            SELECT d.*, u.name AS donor_name, u.phone AS donor_phone, u.address AS donor_address
            FROM donations d
            JOIN users u ON d.donor_id = u.id
            WHERE d.status IN ('accepted', 'requested')
              AND d.verification_status = 'approved'
            ORDER BY d.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'success' => true,
                'data'    => [],
                'message' => 'No active deliveries available right now. Check back later! 🚚',
            ];
        }

        $message = "🚚 **Active Deliveries:**<br><br>";
        foreach ($items as $i => $item) {
            $num = $i + 1;
            $message .= "{$num}. **{$item['food_item']}** — {$item['quantity']}<br>";
            $message .= "   📍 Pickup: {$item['pickup_address']}<br>";
            $message .= "   👤 Donor: {$item['donor_name']}<br><br>";
        }

        $message .= "👉 <a href='dashboard.php?page=volunteer' style='color:#059669;font-weight:600;'>Volunteer Hub</a>";

        return [
            'success' => true,
            'data'    => $items,
            'message' => $message,
        ];
    }

    /**
     * Get team members info.
     */
    private function get_team_info() {
        $stmt = $this->pdo->query("
            SELECT name, role, bio 
            FROM team_members 
            WHERE status = 'active' 
            ORDER BY display_order ASC, created_at DESC
        ");
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($members)) {
            return [
                'success' => true,
                'data'    => [],
                'message' => 'Our team page is being updated. Visit <a href="/frontend/team.php" style="color:#059669;font-weight:600;">Team Page</a> for the latest information.',
            ];
        }

        $message = "👥 **Our Team:**<br><br>";
        foreach ($members as $member) {
            $message .= "**{$member['name']}** — {$member['role']}<br>";
            if (!empty($member['bio'])) {
                $message .= "_{$member['bio']}_<br>";
            }
            $message .= "<br>";
        }
        $message .= "👉 <a href='team.php' style='color:#059669;font-weight:600;'>Meet the full team</a>";

        return [
            'success' => true,
            'data'    => $members,
            'message' => $message,
        ];
    }

    /**
     * Get contact info from CMS.
     */
    private function get_contact_info() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM cms_homepage WHERE id = 1 LIMIT 1");
            $cms = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $cms = null;
        }

        $email = $cms['footer_email'] ?? 'info@sayog.org';
        $phone = $cms['footer_phone'] ?? '+977-1-4XXXXXX';
        $address = $cms['footer_address'] ?? 'Kathmandu, Nepal';
        $facebook = $cms['facebook'] ?? '#';
        $whatsapp = $cms['whatsapp'] ?? '#';

        $message = "📞 **Contact Information**<br><br>"
            . "📧 **Email:** <a href='mailto:{$email}' style='color:#059669;'>{$email}</a><br>"
            . "📞 **Phone:** {$phone}<br>"
            . "📍 **Address:** {$address}<br><br>"
            . "💬 **WhatsApp:** <a href='{$whatsapp}' target='_blank' style='color:#059669;'>Chat with us</a><br>"
            . "📝 <a href='/frontend/contact.php' style='color:#059669;font-weight:600;'>Send us a message</a>";

        return [
            'success' => true,
            'data'    => ['email' => $email, 'phone' => $phone, 'address' => $address],
            'message' => $message,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // ADMIN ONLY QUERIES
    // ──────────────────────────────────────────────────────────────

    private function require_admin($role) {
        if ($role !== 'admin') {
            return [
                'success' => false,
                'data'    => null,
                'message' => 'This information is only available to administrators. 🔒',
            ];
        }
        return null;
    }

    private function get_admin_stats($role) {
        $check = $this->require_admin($role);
        if ($check) return $check;

        $stats = [];
        $stats['total_users'] = (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_donations'] = (int)$this->pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
        $stats['pending_donations'] = (int)$this->pdo->query("SELECT COUNT(*) FROM donations WHERE verification_status = 'pending'")->fetchColumn();
        $stats['active_donations'] = (int)$this->pdo->query("SELECT COUNT(*) FROM donations WHERE verification_status = 'approved' AND status IN ('available', 'requested', 'accepted')")->fetchColumn();
        $stats['completed_donations'] = (int)$this->pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'completed'")->fetchColumn();
        $stats['pending_requests'] = (int)$this->pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetchColumn();
        $stats['total_requests'] = (int)$this->pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
        $stats['pending_volunteers'] = (int)$this->pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'pending'")->fetchColumn();
        $stats['approved_volunteers'] = (int)$this->pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'approved'")->fetchColumn();
        $stats['total_team_members'] = (int)$this->pdo->query("SELECT COUNT(*) FROM team_members WHERE status = 'active'")->fetchColumn();
        $stats['contact_messages'] = (int)$this->pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'Unread'")->fetchColumn();

        $message = "📊 **Admin Dashboard Overview**<br><br>"
            . "👥 **Total Users:** {$stats['total_users']}<br>"
            . "🍽️ **Total Donations:** {$stats['total_donations']}<br>"
            . "⏳ **Pending Review:** {$stats['pending_donations']}<br>"
            . "✅ **Active Donations:** {$stats['active_donations']}<br>"
            . "🎉 **Completed:** {$stats['completed_donations']}<br>"
            . "📋 **Requests (Total):** {$stats['total_requests']}<br>"
            . "⏳ **Pending Requests:** {$stats['pending_requests']}<br>"
            . "🧑‍🤝‍🧑 **Volunteers (Pending):** {$stats['pending_volunteers']}<br>"
            . "✅ **Volunteers (Approved):** {$stats['approved_volunteers']}<br>"
            . "👥 **Team Members:** {$stats['total_team_members']}<br>"
            . "📧 **Unread Messages:** {$stats['contact_messages']}<br>";

        return [
            'success' => true,
            'data'    => $stats,
            'message' => $message,
        ];
    }

    private function get_pending_reviews($role) {
        $check = $this->require_admin($role);
        if ($check) return $check;

        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM donations WHERE verification_status = 'pending'")->fetchColumn();

        $message = $count > 0
            ? "📋 **Pending Donation Reviews:** <strong style='color:#f59e0b;'>{$count}</strong> donation(s) waiting for your review.<br><br>👉 <a href='admin.php?section=donations' style='color:#059669;font-weight:600;'>Review Donations</a>"
            : "✅ All donations have been reviewed. No pending approvals!";

        return [
            'success' => true,
            'data'    => ['count' => $count],
            'message' => $message,
        ];
    }

    private function get_pending_volunteers($role) {
        $check = $this->require_admin($role);
        if ($check) return $check;

        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'pending'")->fetchColumn();

        $message = $count > 0
            ? "🧑‍🤝‍🧑 **Pending Volunteer Applications:** <strong style='color:#f59e0b;'>{$count}</strong> application(s) waiting.<br><br>👉 <a href='admin.php?section=volunteers' style='color:#059669;font-weight:600;'>Review Volunteers</a>"
            : "✅ No pending volunteer applications at this time.";

        return [
            'success' => true,
            'data'    => ['count' => $count],
            'message' => $message,
        ];
    }

    private function get_today_registrations($role) {
        $check = $this->require_admin($role);
        if ($check) return $check;

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();

        $message = $count > 0
            ? "📅 **Today's Registrations:** <strong style='color:#059669;'>{$count}</strong> new user(s) joined Sayog today! 🎉"
            : "📅 No new registrations today yet.";

        return [
            'success' => true,
            'data'    => ['count' => $count],
            'message' => $message,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // NEW QUERY METHODS
    // ──────────────────────────────────────────────────────────────

    /**
     * Get certificate information for the current user.
     */
    private function get_certificate_info($user_id, $role) {
        if ($user_id <= 0) {
            return [
                'success' => true,
                'data'    => null,
                'message' => '📜 Sayog awards **Certificates of Appreciation** to donors who complete food donations!<br><br>✅ Log in and complete a donation to earn your certificate.<br>👉 <a href="login.php" style="color:#059669;font-weight:600;">Login here</a>',
            ];
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM donation_certificates WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $count = (int)$stmt->fetchColumn();

        $stmtCompleted = $this->pdo->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ? AND status = 'completed'");
        $stmtCompleted->execute([$user_id]);
        $completed = (int)$stmtCompleted->fetchColumn();

        if ($count > 0) {
            return [
                'success' => true,
                'data'    => ['certificate_count' => $count, 'completed_donations' => $completed],
                'message' => "🎉 **You have {$count} certificate(s) of appreciation!**<br><br>"
                    . "📜 View and download your certificates from your **Dashboard → Certificates** section.<br><br>"
                    . "🌟 Share them on social media to inspire others to donate!<br><br>"
                    . "👉 <a href='dashboard.php?page=certificates' style='color:#059669;font-weight:600;'>View My Certificates</a>",
            ];
        }

        return [
            'success' => true,
            'data'    => ['certificate_count' => 0, 'completed_donations' => $completed],
            'message' => "📜 You don't have any certificates yet.<br><br>"
                . "✅ Complete a food donation to earn your **Certificate of Appreciation**!<br><br>"
                . "👉 <a href='dashboard.php?page=create-donation' style='color:#059669;font-weight:600;'>Create a Donation</a>",
        ];
    }

    /**
     * Get recent notifications for the current user.
     */
    private function get_notifications($user_id, $role) {
        if ($user_id <= 0) {
            return [
                'success' => true,
                'data'    => [],
                'message' => '🔔 You need to log in to see your notifications.<br><br>👉 <a href="login.php" style="color:#059669;font-weight:600;">Login here</a>',
            ];
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT message, created_at, is_read, type 
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $notifications = [];
        }

        if (empty($notifications)) {
            return [
                'success' => true,
                'data'    => [],
                'message' => '🔔 You have no recent notifications. You\'ll be notified when:<br><br>✅ Your donation is approved<br>✅ Someone requests your food<br>✅ A request is approved/rejected<br>✅ A donation is about to expire',
            ];
        }

        $message = "🔔 **Your Recent Notifications:**<br><br>";
        foreach ($notifications as $n) {
            $icon = ($n['is_read'] ?? 0) ? '💬' : '🆕';
            $time = date('d M H:i', strtotime($n['created_at']));
            $message .= "{$icon} {$n['message']}<br><span style='font-size:11px;color:#94a3b8;'>{$time}</span><br><br>";
        }
        $message .= "👉 <a href='dashboard.php?page=notifications' style='color:#059669;font-weight:600;'>View All Notifications</a>";

        return [
            'success' => true,
            'data'    => $notifications,
            'message' => $message,
        ];
    }

    /**
     * Get tracking info for the current user's donations and requests.
     */
    private function get_tracking_info($user_id, $role) {
        if ($user_id <= 0) {
            return [
                'success' => true,
                'data'    => null,
                'message' => '📊 You can track donations and requests once you log in.<br><br>👉 <a href="login.php" style="color:#059669;font-weight:600;">Login here</a>',
            ];
        }

        // Get donation stats
        $donation_counts = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_review,
                    SUM(CASE WHEN status IN ('available', 'requested', 'accepted') THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM donations WHERE donor_id = ?
            ");
            $stmt->execute([$user_id]);
            $donation_counts = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $donation_counts = ['total' => 0, 'pending_review' => 0, 'active' => 0, 'completed' => 0];
        }

        // Get request stats
        $request_counts = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM requests WHERE consumer_id = ?
            ");
            $stmt->execute([$user_id]);
            $request_counts = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $request_counts = ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0];
        }

        $don_total = (int)($donation_counts['total'] ?? 0);
        $don_pending = (int)($donation_counts['pending_review'] ?? 0);
        $don_active = (int)($donation_counts['active'] ?? 0);
        $don_completed = (int)($donation_counts['completed'] ?? 0);

        $req_total = (int)($request_counts['total'] ?? 0);
        $req_pending = (int)($request_counts['pending'] ?? 0);
        $req_approved = (int)($request_counts['approved'] ?? 0);
        $req_completed = (int)($request_counts['completed'] ?? 0);

        $message = "📊 **Your Tracking Overview**<br><br>"
            . "**🍽️ Donations:**<br>"
            . "📝 Total: {$don_total}<br>"
            . "⏳ Pending Review: {$don_pending}<br>"
            . "✅ Active: {$don_active}<br>"
            . "🎉 Completed: {$don_completed}<br><br>"
            . "**📋 Requests:**<br>"
            . "📝 Total: {$req_total}<br>"
            . "⏳ Pending: {$req_pending}<br>"
            . "✅ Approved: {$req_approved}<br>"
            . "🎉 Completed: {$req_completed}<br><br>"
            . "👉 <a href='dashboard.php' style='color:#059669;font-weight:600;'>Go to Dashboard</a> | <a href='dashboard.php?page=my-donations' style='color:#059669;font-weight:600;'>My Donations</a>";

        return [
            'success' => true,
            'data'    => ['donations' => $donation_counts, 'requests' => $request_counts],
            'message' => $message,
        ];
    }

    /**
     * Get pickup coordination information.
     */
    private function get_pickup_info($user_id, $role) {
        if ($user_id <= 0) {
            return [
                'success' => true,
                'data'    => null,
                'message' => '🚗 After your donation request is approved, coordinate pickup with the donor.<br><br>👉 <a href="login.php" style="color:#059669;font-weight:600;">Login to manage pickups</a>',
            ];
        }

        // Get recent approved donations for the donor
        $stmt = $this->pdo->prepare("
            SELECT d.id, d.food_item, d.quantity, d.pickup_address, d.expiry_time,
                   r.status AS request_status, u.name AS requester_name, u.phone AS requester_phone
            FROM donations d
            LEFT JOIN requests r ON d.id = r.donation_id AND r.status = 'approved'
            LEFT JOIN users u ON r.consumer_id = u.id
            WHERE d.donor_id = ? AND d.status IN ('available', 'accepted')
            ORDER BY d.expiry_time ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            // Check for pending requests from consumer side
            $stmt2 = $this->pdo->prepare("
                SELECT d.food_item, d.pickup_address, r.status, u.name AS donor_name
                FROM requests r
                JOIN donations d ON r.donation_id = d.id
                JOIN users u ON d.donor_id = u.id
                WHERE r.consumer_id = ? AND r.status IN ('approved', 'pending')
                LIMIT 5
            ");
            $stmt2->execute([$user_id]);
            $consumer_items = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($consumer_items)) {
                $message = "🚗 **Your Active Pickups:**<br><br>";
                foreach ($consumer_items as $item) {
                    $status = ucfirst($item['status']);
                    $message .= "🍽️ **{$item['food_item']}** from {$item['donor_name']}<br>";
                    $message .= "📍 {$item['pickup_address']}<br>";
                    $message .= "Status: **{$status}**<br><br>";
                }
                $message .= "👉 <a href='dashboard.php?page=my-requests' style='color:#059669;font-weight:600;'>View Requests</a>";
            } else {
                $message = "🚗 You have no active pickups right now.<br><br>"
                    . "When your donation is requested or your request is approved, pickup details will appear here!<br><br>"
                    . "👉 <a href='dashboard.php?page=create-donation' style='color:#059669;font-weight:600;'>Create a Donation</a>";
            }
        } else {
            $message = "🚗 **Pending Pickups:**<br><br>";
            foreach ($items as $item) {
                $message .= "🍽️ **{$item['food_item']}** — {$item['quantity']}<br>";
                $message .= "📍 {$item['pickup_address']}<br>";
                if (!empty($item['requester_name'])) {
                    $message .= "👤 Requested by: {$item['requester_name']} ({$item['requester_phone']})<br>";
                }
                $message .= "⏰ Expires: " . date('d M H:i', strtotime($item['expiry_time'])) . "<br><br>";
            }
            $message .= "👉 <a href='dashboard.php?page=my-donations' style='color:#059669;font-weight:600;'>Manage Donations</a>";
        }

        return [
            'success' => true,
            'data'    => $items ?? [],
            'message' => $message,
        ];
    }
}
