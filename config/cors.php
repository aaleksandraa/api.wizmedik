<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('APP_ENV') === 'production'
        ? [env('APP_FRONTEND_URL')]
        : [
            'http://localhost:5173', // Primary Vite dev server
            env('APP_FRONTEND_URL', 'http://localhost:8080'),
            'http://localhost:8080', // Alternative Vite port
            'http://localhost:3000', // React dev server
            'http://127.0.0.1:5173', // IPv4 localhost
            'http://127.0.0.1:8080', // IPv4 localhost alternative
        ],

    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : ['/^http:\/\/localhost:\d+$/'], // Allow any localhost port in development only

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Cache', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],

    'max_age' => 0,

    'supports_credentials' => true,

];
