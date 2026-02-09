<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Perform health check on all critical services
     */
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = !in_array(false, $checks, true);
        $status = $healthy ? 200 : 503;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
        ], $status)->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Simple health check endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            DB::connection()->getDatabaseName();
            return true;
        } catch (\Exception $e) {
            logger()->error('Database health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check Redis connectivity
     */
    private function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            logger()->error('Redis health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): bool
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            return $value === 'test';
        } catch (\Exception $e) {
            logger()->error('Cache health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check storage writability
     */
    private function checkStorage(): bool
    {
        try {
            $testFile = storage_path('logs/health_check.txt');
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            unlink($testFile);
            return $content === 'test';
        } catch (\Exception $e) {
            logger()->error('Storage health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
