<?php
/**
 * Pickup Reminder Cron Script
 * 
 * This script checks for donations where:
 *   - Status is 'accepted' (pickup is pending)
 *   - expiry_time is within the next 1 hour
 *   - pickup_reminder_sent is 0 (reminder not yet sent)
 * 
 * It sends email notifications to both the donor and the receiver
 * reminding them to coordinate and complete the pickup.
 * 
 * CRON: Run every 5-10 minutes via crontab:
 *   ~/5 * * * * php /path/to/cron/pickup_reminder.php
 * 
 * Windows Task Scheduler:
 *   php C:\xampp\htdocs\sayog\cron\pickup_reminder.php
 */

// Set script execution time limit (no timeout for cron jobs)
set_time_limit(120);

// Load the main config (database connection, helper functions)
require_once __DIR__ . '/../config.php';

echo "[" . date('Y-m-d H:i:s') . "] Pickup Reminder Cron: Starting...\n";

try {
    // Find accepted donations that are expiring within 1 hour and haven't had a reminder sent
    $stmt = $pdo->prepare("
        SELECT d.*, u.name AS donor_name, u.email AS donor_email,
               (SELECT u2.name FROM requests r JOIN users u2 ON r.consumer_id = u2.id 
                WHERE r.donation_id = d.id AND r.status = 'approved' LIMIT 1) AS receiver_name,
               (SELECT u2.email FROM requests r JOIN users u2 ON r.consumer_id = u2.id 
                WHERE r.donation_id = d.id AND r.status = 'approved' LIMIT 1) AS receiver_email,
               (SELECT r.id FROM requests r 
                WHERE r.donation_id = d.id AND r.status = 'approved' LIMIT 1) AS request_id
        FROM donations d
        JOIN users u ON d.donor_id = u.id
        WHERE d.status = 'accepted'
          AND d.expiry_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
          AND d.pickup_reminder_sent = 0
        LIMIT 20
    ");
    $stmt->execute();
    $reminders = $stmt->fetchAll();

    if (empty($reminders)) {
        echo "[" . date('Y-m-d H:i:s') . "] No upcoming pickups needing reminders.\n";
        exit(0);
    }

    $sent_count = 0;
    foreach ($reminders as $donation) {
        $donation_id = $donation['id'];
        $food_item = $donation['food_item'];
        $expiry_time = $donation['expiry_time'];
        $donor_name = $donation['donor_name'];
        $donor_email = $donation['donor_email'];
        $receiver_name = $donation['receiver_name'] ?? 'the receiver';
        $receiver_email = $donation['receiver_email'];
        $pickup_address = $donation['pickup_address'];

        $expiry_timestamp = strtotime($expiry_time);
        $minutes_until_expiry = round(($expiry_timestamp - time()) / 60);
        $time_str = $minutes_until_expiry > 0 
            ? "in about $minutes_until_expiry minutes" 
            : "very soon";

        echo "[" . date('Y-m-d H:i:s') . "] Processing donation #$donation_id: $food_item (expires $time_str)\n";

        // --- Notify the Donor ---
        $donor_message = "Reminder: Your donation \"" . $food_item . "\" at " . $pickup_address 
            . " is expiring $time_str. Please coordinate with " . $receiver_name 
            . " to complete the pickup as soon as possible!";
        
        create_notification(
            $pdo,
            $donation['donor_id'],
            'pickup_reminder',
            $donor_message,
            'dashboard.php?page=track-donation',
            true
        );

        // --- Notify the Receiver ---
        if (!empty($donation['request_id'])) {
            // Get the receiver's user ID
            $rec_stmt = $pdo->prepare("SELECT consumer_id FROM requests WHERE id = ?");
            $rec_stmt->execute([$donation['request_id']]);
            $receiver_user_id = $rec_stmt->fetchColumn();

            if ($receiver_user_id) {
                $receiver_message = "Reminder: Your requested food \"" . $food_item . "\" from " 
                    . $donor_name . " at " . $pickup_address 
                    . " is expiring $time_str. Please pick it up as soon as possible!";
                
                create_notification(
                    $pdo,
                    $receiver_user_id,
                    'pickup_reminder',
                    $receiver_message,
                    'dashboard.php?page=track-request',
                    true
                );
            }
        }

        // Mark reminder as sent
        $update_stmt = $pdo->prepare("UPDATE donations SET pickup_reminder_sent = 1 WHERE id = ?");
        $update_stmt->execute([$donation_id]);

        $sent_count++;
        echo "[" . date('Y-m-d H:i:s') . "] Reminder sent for donation #$donation_id\n";
    }

    echo "[" . date('Y-m-d H:i:s') . "] Pickup Reminder Cron: Complete! Sent $sent_count reminder(s).\n";

} catch (PDOException $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
