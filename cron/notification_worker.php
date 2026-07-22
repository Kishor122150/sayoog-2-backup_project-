<?php
/**
 * Notification Queue Worker Cron Job
 * 
 * Processes queued notifications across all channels.
 * Should run every 1-2 minutes for timely delivery.
 * 
 * Recommended cron: * /1 * * * * php /path/to/cron/notification_worker.php
 * 
 * Usage: php cron/notification_worker.php [--batch=50]
 */

// ── File Locking (prevents concurrent cron processes) ──
$lockFile = __DIR__ . '/notification_worker.lock';
$fp = fopen($lockFile, 'c');
if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another notification worker is already running. Exiting.\n";
    exit(0);
}
register_shutdown_function(function() use ($fp) {
    flock($fp, LOCK_UN);
    fclose($fp);
});

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/autoload.php';

use App\Services\NotificationService;

$batchSize = 50;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--batch=')) {
        $batchSize = (int)substr($arg, 8);
    }
}

$startTime = microtime(true);
$notifier = new NotificationService($pdo);

// Process high-priority notifications first
$results = $notifier->processQueue($batchSize);

// Clean old queued notifications (older than 48h)
$cleaned = $notifier->cleanOldQueued(48);

$elapsed = round((microtime(true) - $startTime) * 1000, 2);

echo "[" . date('Y-m-d H:i:s') . "] Notification worker: sent={$results['sent']}, failed={$results['failed']}, cleaned={$cleaned} ({$elapsed}ms)\n";
if (!empty($results['errors'])) {
    foreach ($results['errors'] as $err) {
        echo "  ERROR: {$err}\n";
    }
}
