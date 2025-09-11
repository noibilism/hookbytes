<?php

/**
 * OPcache Preload Script for HookBytes
 * 
 * This script preloads frequently used classes and files into OPcache
 * for improved performance in production environments.
 * 
 * Usage: Set opcache.preload=/var/www/html/config/opcache-preload.php in php.ini
 */

// Only run in production
if (php_sapi_name() !== 'cli' && $_ENV['APP_ENV'] !== 'production') {
    return;
}

// Set memory limit for preloading
ini_set('memory_limit', '256M');

// Define base path
$basePath = dirname(__DIR__);

// Preload Composer autoloader
require_once $basePath . '/vendor/autoload.php';

// Preload Laravel core files
$laravelFiles = [
    '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
    '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
    '/vendor/laravel/framework/src/Illuminate/Http/Request.php',
    '/vendor/laravel/framework/src/Illuminate/Http/Response.php',
    '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
    '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php',
    '/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php',
    '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php',
    '/vendor/laravel/framework/src/Illuminate/View/View.php',
    '/vendor/laravel/framework/src/Illuminate/Cache/CacheManager.php',
    '/vendor/laravel/framework/src/Illuminate/Queue/QueueManager.php',
];

// Preload application files
$appFiles = [
    // Models
    '/app/Models/User.php',
    '/app/Models/Project.php',
    '/app/Models/WebhookEndpoint.php',
    '/app/Models/WebhookEvent.php',
    '/app/Models/WebhookDelivery.php',
    '/app/Models/WebhookRoutingRule.php',
    '/app/Models/WebhookTransformation.php',
    
    // Controllers
    '/app/Http/Controllers/Controller.php',
    '/app/Http/Controllers/DashboardController.php',
    '/app/Http/Controllers/WebhookController.php',
    '/app/Http/Controllers/WebhookApiController.php',
    '/app/Http/Controllers/ProjectController.php',
    '/app/Http/Controllers/WebhookEndpointController.php',
    '/app/Http/Controllers/WebhookRoutingRuleController.php',
    '/app/Http/Controllers/WebhookTransformationController.php',
    
    // Services
    '/app/Services/WebhookRoutingService.php',
    '/app/Services/WebhookTransformationService.php',
    
    // Jobs
    '/app/Jobs/ProcessWebhookEvent.php',
    '/app/Jobs/DeliverWebhook.php',
    
    // Middleware
    '/app/Http/Middleware/Authenticate.php',
    '/app/Http/Middleware/EncryptCookies.php',
    '/app/Http/Middleware/PreventRequestsDuringMaintenance.php',
    '/app/Http/Middleware/RedirectIfAuthenticated.php',
    '/app/Http/Middleware/TrimStrings.php',
    '/app/Http/Middleware/TrustHosts.php',
    '/app/Http/Middleware/TrustProxies.php',
    '/app/Http/Middleware/ValidateSignature.php',
    '/app/Http/Middleware/VerifyCsrfToken.php',
    
    // Providers
    '/app/Providers/AppServiceProvider.php',
    '/app/Providers/AuthServiceProvider.php',
    '/app/Providers/EventServiceProvider.php',
    '/app/Providers/RouteServiceProvider.php',
];

// Function to safely preload files
function preloadFile($file) {
    global $basePath;
    $fullPath = $basePath . $file;
    
    if (file_exists($fullPath)) {
        try {
            opcache_compile_file($fullPath);
            echo "Preloaded: $file\n";
        } catch (Throwable $e) {
            echo "Failed to preload $file: " . $e->getMessage() . "\n";
        }
    } else {
        echo "File not found: $file\n";
    }
}

// Preload Laravel core files
echo "Preloading Laravel core files...\n";
foreach ($laravelFiles as $file) {
    preloadFile($file);
}

// Preload application files
echo "Preloading application files...\n";
foreach ($appFiles as $file) {
    preloadFile($file);
}

// Preload configuration files
echo "Preloading configuration files...\n";
$configFiles = glob($basePath . '/config/*.php');
foreach ($configFiles as $file) {
    try {
        opcache_compile_file($file);
        echo "Preloaded config: " . basename($file) . "\n";
    } catch (Throwable $e) {
        echo "Failed to preload config " . basename($file) . ": " . $e->getMessage() . "\n";
    }
}

// Preload route files
echo "Preloading route files...\n";
$routeFiles = glob($basePath . '/routes/*.php');
foreach ($routeFiles as $file) {
    try {
        opcache_compile_file($file);
        echo "Preloaded route: " . basename($file) . "\n";
    } catch (Throwable $e) {
        echo "Failed to preload route " . basename($file) . ": " . $e->getMessage() . "\n";
    }
}

echo "OPcache preloading completed!\n";

// Display OPcache status
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "OPcache Status:\n";
    echo "- Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
    echo "- Memory Usage: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . "MB\n";
    echo "- Cached Scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "- Hit Rate: " . round($status['opcache_statistics']['opcache_hit_rate'], 2) . "%\n";
}