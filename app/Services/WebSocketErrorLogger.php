<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebSocketErrorLogger
{
    /**
     * Log WebSocket connection failure with detailed information
     */
    public static function logConnectionFailure(array $context = []): void
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'websocket_connection_failure',
            'client_info' => [
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'origin' => request()->header('Origin'),
            ],
            'server_info' => [
                'host' => config('app.url'),
                'environment' => config('app.env'),
                'debug_mode' => config('app.debug'),
            ],
            'websocket_config' => [
                'hmr_port' => 5173,
                'hmr_host' => 'localhost',
                'proxy_target' => 'http://localhost:8000',
            ],
            'context' => $context,
        ];

        Log::channel('websocket')->error('WebSocket connection failed', $errorData);

        // Track failure rate for monitoring
        self::trackFailureRate();
    }

    /**
     * Log WebSocket authentication failure
     */
    public static function logAuthenticationFailure(string $token = null, array $context = []): void
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'websocket_authentication_failure',
            'token_provided' => !empty($token),
            'token_length' => $token ? strlen($token) : 0,
            'client_info' => [
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'origin' => request()->header('Origin'),
            ],
            'context' => $context,
        ];

        Log::channel('websocket')->warning('WebSocket authentication failed', $errorData);
    }

    /**
     * Log WebSocket HMR failure
     */
    public static function logHMRFailure(string $reason, array $context = []): void
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'websocket_hmr_failure',
            'reason' => $reason,
            'vite_config' => [
                'hmr_port' => 5173,
                'hmr_host' => 'localhost',
                'overlay' => false,
                'client_port' => 5173,
            ],
            'context' => $context,
        ];

        Log::channel('websocket')->error('WebSocket HMR failed', $errorData);
    }

    /**
     * Log successful WebSocket connection for monitoring
     */
    public static function logSuccessfulConnection(array $context = []): void
    {
        $successData = [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'websocket_connection_success',
            'client_info' => [
                'user_agent' => request()->header('User-Agent'),
                'ip_address' => request()->ip(),
                'origin' => request()->header('Origin'),
            ],
            'context' => $context,
        ];

        Log::channel('websocket')->info('WebSocket connection successful', $successData);

        // Track success rate for monitoring
        self::trackSuccessRate();
    }

    /**
     * Track WebSocket failure rate for monitoring
     */
    private static function trackFailureRate(): void
    {
        $key = 'websocket_failures_' . now()->format('Y-m-d-H');
        $failures = Cache::get($key, 0);
        Cache::put($key, $failures + 1, now()->addHours(2));
    }

    /**
     * Track WebSocket success rate for monitoring
     */
    private static function trackSuccessRate(): void
    {
        $key = 'websocket_successes_' . now()->format('Y-m-d-H');
        $successes = Cache::get($key, 0);
        Cache::put($key, $successes + 1, now()->addHours(2));
    }

    /**
     * Get WebSocket connection statistics
     */
    public static function getConnectionStats(): array
    {
        $currentHour = now()->format('Y-m-d-H');
        $failures = Cache::get("websocket_failures_{$currentHour}", 0);
        $successes = Cache::get("websocket_successes_{$currentHour}", 0);
        $total = $failures + $successes;

        return [
            'current_hour' => $currentHour,
            'failures' => $failures,
            'successes' => $successes,
            'total_attempts' => $total,
            'success_rate' => $total > 0 ? round(($successes / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failures / $total) * 100, 2) : 0,
        ];
    }
}
