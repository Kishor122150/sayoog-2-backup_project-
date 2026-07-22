<?php
/**
 * Badge Award Cron Script
 *
 * Scans all users and auto-awards trust badges based on existing activity:
 *   - 'trusted_donor':      5+ completed donations as donor
 *   - 'top_volunteer':      10+ completed volunteer deliveries
 *   - 'community_champion': 5 completed donations AND 5 deliveries, OR 20+ impact_points
 *
 * This can be run:
 *   1. As a one-time retroactive script after badge system is deployed:
 *        php cron/award_badges.php
 *   2. As a scheduled task (cron) to catch any badges missed by the real-time hooks:
 *        # Daily at 3am — Linux:
 *        0 3 * * * php /path/to/cron/award_badges.php --quiet
 *        # Daily at 3am — Windows Task Scheduler:
 *        php C:\xampp\htdocs\sayog\cron\award_badges.php --quiet
 *
 * Options:
 *   --user=123   Only check a specific user ID (useful for testing)
 *   --dry-run    Show what would be awarded without actually inserting
 *   --quiet      Suppress per-user output, show only summary
 *   --force      Re-send notifications even if badge already exists
 */

set_time_limit(300); // 5 minutes max for large user bases

require_once __DIR__ . '/../config.php';

// ── Parse CLI arguments ──
$specificUserId = 0;
$dryRun = false;
$quiet = false;
$force = false;

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if (substr($arg, 0, 7) === '--user=') {
        $specificUserId = (int) substr($arg, 7);
    } elseif ($arg === '--dry-run') {
        $dryRun = true;
    } elseif ($arg === '--quiet') {
        $quiet = true;
    } elseif ($arg === '--force') {
        $force = true;
    }
}

// ── Log helper ──
function badge_log($message, $isError = false) {
    global $quiet;
    if ($quiet && !$isError) return;
    $prefix = $isError ? 'ERROR' : 'INFO';
    echo "[" . date('Y-m-d H:i:s') . "] [$prefix] $message\n";
}

badge_log("Badge Award Cron: Starting" . ($dryRun ? " (DRY RUN)" : "") . "...");

try {
    // Get all users (or a specific one)
    if ($specificUserId > 0) {
        $users = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
        $users->execute([$specificUserId]);
        $usersList = $users->fetchAll();
        badge_log("Checking specific user ID: $specificUserId");
    } else {
        $usersList = $pdo->query("SELECT id, name, email FROM users ORDER BY id ASC")->fetchAll();
        badge_log("Scanning " . count($usersList) . " users...");
    }

    if (empty($usersList)) {
        badge_log("No users found. Exiting.");
        exit(0);
    }

    $totalBadgesAwarded = 0;
    $totalUsersWithNewBadges = 0;
    $awardsByType = ['trusted_donor' => 0, 'top_volunteer' => 0, 'community_champion' => 0];
    $results = [];

    foreach ($usersList as $user) {
        $userId = (int) $user['id'];
        $userName = $user['name'];

        // ── Count completed donations ──
        $donStmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ? AND status = 'completed'");
        $donStmt->execute([$userId]);
        $completedDonations = (int) $donStmt->fetchColumn();

        // ── Count completed volunteer deliveries ──
        $delStmt = $pdo->prepare("SELECT COUNT(*) FROM volunteer_deliveries WHERE volunteer_user_id = ? AND status = 'delivered'");
        $delStmt->execute([$userId]);
        $completedDeliveries = (int) $delStmt->fetchColumn();

        // ── Get impact_points ──
        $impStmt = $pdo->prepare("SELECT impact_points FROM users WHERE id = ?");
        $impStmt->execute([$userId]);
        $impactPoints = (int) $impStmt->fetchColumn();

        // ── Check which badges this user already has ──
        $existingStmt = $pdo->prepare("SELECT badge_type FROM user_badges WHERE user_id = ? AND badge_type IN ('trusted_donor','top_volunteer','community_champion')");
        $existingStmt->execute([$userId]);
        $existingBadges = $existingStmt->fetchAll(PDO::FETCH_COLUMN);
        $hasBadge = array_flip($existingBadges);

        $newBadgesForUser = [];
        $userLog = "User #{$userId} {$userName}: {$completedDonations} donations, {$completedDeliveries} deliveries, {$impactPoints} pts";

        // ── Check: trusted_donor (5+ completed donations) ──
        if ($completedDonations >= 5 && !isset($hasBadge['trusted_donor'])) {
            $newBadgesForUser[] = 'trusted_donor';
            if (!$dryRun) {
                $badgeInserted = award_trust_badge($pdo, $userId, 'trusted_donor', 'Trusted Donor', 'fa-heart', '#e11d48');
                if ($force && !$badgeInserted) {
                    create_notification($pdo, $userId, 'trusted_donor', '🏅 You earned the Trusted Donor badge for completing 5+ donations!', 'dashboard.php?page=profile', true);
                    badge_log("  → Re-sent notification for existing trusted_donor badge (--force)");
                }
            }
            $awardsByType['trusted_donor']++;
        }

        // ── Check: top_volunteer (10+ completed deliveries) ──
        if ($completedDeliveries >= 10 && !isset($hasBadge['top_volunteer'])) {
            $newBadgesForUser[] = 'top_volunteer';
            if (!$dryRun) {
                $badgeInserted = award_trust_badge($pdo, $userId, 'top_volunteer', 'Top Volunteer', 'fa-truck-fast', '#2563eb');
                if ($force && !$badgeInserted) {
                    create_notification($pdo, $userId, 'top_volunteer', '🎖️ You earned the Top Volunteer badge for completing 10+ deliveries!', 'dashboard.php?page=profile', true);
                    badge_log("  → Re-sent notification for existing top_volunteer badge (--force)");
                }
            }
            $awardsByType['top_volunteer']++;
        }

        // ── Check: community_champion (5 donations AND 5 deliveries, OR 20+ impact_points) ──
        $championCriteria = ($completedDonations >= 5 && $completedDeliveries >= 5) || $impactPoints >= 20;
        if ($championCriteria && !isset($hasBadge['community_champion'])) {
            $newBadgesForUser[] = 'community_champion';
            if (!$dryRun) {
                $badgeInserted = award_trust_badge($pdo, $userId, 'community_champion', 'Community Champion', 'fa-crown', '#f59e0b');
                if ($force && !$badgeInserted) {
                    create_notification($pdo, $userId, 'community_champion', '🌟 You earned the Community Champion badge for outstanding community impact!', 'dashboard.php?page=profile', true);
                    badge_log("  → Re-sent notification for existing community_champion badge (--force)");
                }
            }
            $awardsByType['community_champion']++;
        }

        if (!empty($newBadgesForUser)) {
            $totalBadgesAwarded += count($newBadgesForUser);
            $totalUsersWithNewBadges++;
            badge_log("{$userLog} → NEW BADGES: " . implode(', ', $newBadgesForUser));
        } elseif (!$quiet) {
            badge_log("{$userLog} → No new badges");
        }

        $results[] = [
            'user_id' => $userId,
            'name' => $userName,
            'donations' => $completedDonations,
            'deliveries' => $completedDeliveries,
            'impact_points' => $impactPoints,
            'new_badges' => $newBadgesForUser,
        ];
    }

    // ── Summary ──
    echo "\n";
    badge_log(str_repeat('=', 60));
    badge_log("SUMMARY" . ($dryRun ? " (DRY RUN — no badges were actually awarded)" : ""));
    badge_log(str_repeat('=', 60));
    badge_log("Users scanned:      " . count($usersList));
    badge_log("Users with new badges: " . $totalUsersWithNewBadges);
    badge_log("Total badges awarded:  " . $totalBadgesAwarded);
    badge_log("  trusted_donor:       " . $awardsByType['trusted_donor']);
    badge_log("  top_volunteer:       " . $awardsByType['top_volunteer']);
    badge_log("  community_champion:  " . $awardsByType['community_champion']);
    badge_log(str_repeat('=', 60));
    badge_log("Badge Award Cron: Complete!");

} catch (PDOException $e) {
    badge_log("Database error: " . $e->getMessage(), true);
    exit(1);
} catch (Throwable $e) {
    badge_log("Unexpected error: " . $e->getMessage(), true);
    exit(1);
}
