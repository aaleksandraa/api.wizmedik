<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start timing
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Enable query logging
        DB::enableQueryLog();

        // Process request
        $response = $next($request);

        // Calculate metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Add performance headers (useful for debugging)
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', round($memoryUsed, 2) . 'MB');
        $response->headers->set('X-Query-Count', $queryCount);

        // Log slow requests (> 1 second)
        if ($executionTime > 1000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => round($executionTime, 2) . 'ms',
                'memory_used' => round($memoryUsed, 2) . 'MB',
                'query_count' => $queryCount,
                'ip' => $request->ip(),
            ]);
        }

        // Log slow queries (> 100ms)
        foreach ($queries as $query) {
            if ($query['time'] > 100) {
                Log::warning('Slow query detected', [
                    'query' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $query['time'] . 'ms',
                    'url' => $request->fullUrl(),
                ]);
            }
        }

        // Log excessive queries (N+1 problem indicator)
        if ($queryCount > 50) {
            Log::warning('Excessive queries detected (possible N+1 problem)', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'query_count' => $queryCount,
                'execution_time' => round($executionTime, 2) . 'ms',
            ]);
        }

        return $response;
    }
}
