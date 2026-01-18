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
            env('APP_FRONTEND_URL', 'http://localhost:5173'),
            'http://localhost:8080', // Vite dev server
            'http://localhost:5173', // Alternative port
            'http://localhost:3000', // Alternative port
        ],

    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : ['/^http:\/\/localhost:\d+$/'], // Allow any localhost port in development only

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Cache', 'X-RateLimit-Limit', 'X-RateLimit-Remaining'],

    'max_age' => 0,

    'supports_credentials' => true,

];
