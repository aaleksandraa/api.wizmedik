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

        // Never publicly cache time-sensitive endpoints (live availability,
        // on-duty data, etc.). Stale slot data here could cause double-booking.
        $neverCache = [
            'available-slots',
            'booked-slots',
            'guest-visits',
        ];
        foreach ($neverCache as $fragment) {
            if (str_contains($path, $fragment)) {
                $response->headers->set('Cache-Control', 'no-cache, private');
                return $response;
            }
        }

        // Images - 1 year cache
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $path)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            return $response;
        }

        // Public data endpoints - 5 minutes cache.
        // NOTE: these must match the real API route prefixes in routes/api.php
        // (which are English). They previously used Bosnian names that never
        // matched, so these endpoints were silently served as no-cache.
        $publicEndpoints = [
            'api/doctors',
            'api/clinics',
            'api/laboratorije',
            'api/banje',
            'api/domovi-njega',
            'api/specialties',
            'api/cities',
            'api/apoteke',
            'api/lijekovi',
            'api/mkb10',
            'api/blog',
        ];

        foreach ($publicEndpoints as $endpoint) {
            if (str_starts_with($path, $endpoint)) {
                if (app()->environment('local')) {
                    $response->headers->set('Cache-Control', 'no-cache, private');
                    return $response;
                }

                $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=600');
                $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
                return $response;
            }
        }

        // iCal subscription feed - keep it cacheable for calendar clients.
        if (str_starts_with($path, 'api/calendar/ical/')) {
            if ($response->getStatusCode() === 200) {
                $response->headers->set('Cache-Control', 'public, max-age=300, must-revalidate');
                $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
            } else {
                $response->headers->set('Cache-Control', 'no-cache, private');
            }

            return $response;
        }

        // Profile/detail pages - 10 minutes cache.
        // Real detail routes use slugs/ids under the English prefixes.
        $profileEndpoints = [
            'api/doctors/',
            'api/clinics/',
            'api/laboratorije/',
            'api/banje/',
            'api/domovi-njega/',
            'api/apoteke/',
            'api/lijekovi/',
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
            if (app()->environment('local')) {
                $response->headers->set('Cache-Control', 'no-cache, private');
                return $response;
            }

            $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=600');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
            return $response;
        }

        // Default - no cache for other API endpoints
        $response->headers->set('Cache-Control', 'no-cache, private');

        return $response;
    }
}
