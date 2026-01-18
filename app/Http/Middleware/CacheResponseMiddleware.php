<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponseMiddleware
{
    /**
     * Cache GET requests for specified duration
     */
    public function handle(Request $request, Closure $next, int $minutes = 5): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Don't cache authenticated requests (except public data)
        if ($request->user() && !$this->isPublicEndpoint($request)) {
            return $next($request);
        }

        // Generate cache key
        $cacheKey = $this->getCacheKey($request);

        // Try to get from cache
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse) {
            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders($cachedResponse['headers'])
                ->header('X-Cache', 'HIT');
        }

        // Get fresh response
        $response = $next($request);

        // Only cache successful responses
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
            ], now()->addMinutes($minutes));
        }

        return $response->header('X-Cache', 'MISS');
    }

    /**
     * Generate cache key from request
     */
    private function getCacheKey(Request $request): string
    {
        $url = $request->fullUrl();
        $query = $request->query();
        ksort($query);

        return 'api_cache:' . md5($url . json_encode($query));
    }

    /**
     * Check if endpoint is public (can be cached even for authenticated users)
     */
    private function isPublicEndpoint(Request $request): bool
    {
        $publicEndpoints = [
            '/api/doctors',
            '/api/clinics',
            '/api/specialties',
            '/api/cities',
            '/api/settings',
        ];

        foreach ($publicEndpoints as $endpoint) {
            if (str_starts_with($request->path(), ltrim($endpoint, '/'))) {
                return true;
            }
        }

        return false;
    }
}
