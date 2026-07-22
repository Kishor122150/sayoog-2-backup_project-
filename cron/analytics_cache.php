<?php
/**
 * Analytics Cache Cron Job
 * 
 * Runs periodically to:
 * 1. Regenerate analytics cache if stale
 * 2. Record daily snapshot
 * 3. Cleanup old snapshots
 * 
 * Recommended cron: * /30 * * * * php /path/to/cron/analytics_cache.php
 * 
 * Usage: php cron/analytics_cache.php [--force] [--snapshot]
 */

// ── File Locking (prevents concurrent cron processes) ──
$lockFile = __DIR__ . '/analytics_cache.lock';
$fp = fopen($lockFile, 'c');
if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another analytics cache job is already running. Exiting.\n";
    exit(0);
}
register_shutdown_function(function() use ($fp) {
    flock($fp, LOCK_UN);
    fclose($fp);
});

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/autoload.php';

use App\Services\AnalyticsService;

$force = in_array('--force', $argv ?? []);
$snapshot = in_array('--snapshot', $argv ?? []);
$startTime = microtime(true);

$analytics = new AnalyticsService($pdo);
$results = [];

if ($snapshot || $force) {
    // Daily snapshot recording (runs once per day typically)
    $analytics->recordDailySnapshot();
    $results[] = 'daily_snapshot_recorded';
}

if ($force) {
    // Force regenerate all cached analytics
    $data = $analytics->regenerateDashboardData();
    $results[] = 'dashboard_cache_regenerated';
    $results[] = 'generated_at: ' . ($data['generated_at'] ?? 'unknown');
}

// Cleanup old snapshots (keep 90 days)
$cleaned = $analytics->cleanOldSnapshots();
if ($cleaned > 0) {
    $results[] = "cleaned_{$cleaned}_old_snapshots";
}

$elapsed = round((microtime(true) - $startTime) * 1000, 2);

echo "[" . date('Y-m-d H:i:s') . "] Analytics cache: " . implode(', ', $results) . " ({$elapsed}ms)\n";
