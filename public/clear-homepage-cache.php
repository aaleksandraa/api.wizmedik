<?php
/**
 * Emergency Cache Clear Script
 *
 * Upload this file to: backend/public/clear-homepage-cache.php
 * Visit: https://api.wizmedik.com/clear-homepage-cache.php
 *
 * IMPORTANT: Delete this file after use for security!
 */

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Clear homepage cache
try {
    Cache::forget('homepage_data');

    // Also clear all cache if needed
    // Cache::flush();

    echo json_encode([
        'success' => true,
        'message' => 'Homepage cache cleared successfully!',
        'timestamp' => date('Y-m-d H:i:s'),
        'warning' => 'Please delete this file after use for security!'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
