<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $response;
        }

        // Don't cache authenticated requests
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-cache, private');
            return $response;
        }

        // Cache public API responses
        $path = $request->path();

        // Images - 1 year cache
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $path)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            return $response;
        }

        // Public data endpoints - 5 minutes cache
        $publicEndpoints = [
            'api/doktori',
            'api/klinike',
            'api/laboratorije',
            'api/banje',
            'api/domovi-njega',
            'api/specijalnosti',
            'api/gradovi',
            'api/blog/posts',
        ];

        foreach ($publicEndpoints as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=600');
                $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
                return $response;
            }
        }

        // Profile pages - 10 minutes cache
        $profileEndpoints = [
            'api/doktor/',
            'api/klinika/',
            'api/laboratorija/',
            'api/banja/',
            'api/dom-njega/',
        ];

        foreach ($profileEndpoints as $endpoint) {
            if (str_contains($path, $endpoint)) {
                $response->headers->set('Cache-Control', 'public, max-age=600, s-maxage=1200');
                $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 600) . ' GMT');
                return $response;
            }
        }

        // Homepage data - 5 minutes cache
        if (str_contains($path, 'api/homepage')) {
            $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=600');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
            return $response;
        }

        // Default - no cache for other API endpoints
        $response->headers->set('Cache-Control', 'no-cache, private');

        return $response;
    }
}
