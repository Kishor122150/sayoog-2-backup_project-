<?php
/**
 * Sayog App Autoloader
 * PSR-4-style autoloader for the app/ namespace.
 * Include once at the top of any file that needs app services.
 * 
 * Usage: require_once __DIR__ . '/app/autoload.php';
 * Then: $matcher = new \App\Services\VolunteerMatchingService($pdo);
 */

spl_autoload_register(function ($class) {
    // Only handle App\ namespace
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
