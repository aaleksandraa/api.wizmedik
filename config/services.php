<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'captcha' => [
        // Supported values: recaptcha, hcaptcha
        'provider' => env('CAPTCHA_PROVIDER', 'recaptcha'),
        'enabled' => env('CAPTCHA_ENABLED', false),
        'site_key' => env('CAPTCHA_SITE_KEY'),
        'secret' => env('CAPTCHA_SECRET'),
        'min_score' => (float) env('CAPTCHA_MIN_SCORE', 0.5),
    ],

    'bot_protection' => [
        'block_suspicious' => env('BOT_BLOCK_SUSPICIOUS', false),
        'suspicious_threshold' => (int) env('BOT_SUSPICIOUS_THRESHOLD', 2),
    ],

];
