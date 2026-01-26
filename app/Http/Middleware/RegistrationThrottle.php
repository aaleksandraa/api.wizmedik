<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RegistrationThrottle
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '60'): Response
    {
        $key = $this->resolveRequestSignature($request);

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($key, (int) $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Previše pokušaja registracije. Pokušajte ponovo za ' . ceil($seconds / 60) . ' minuta.',
                'retry_after' => $seconds,
            ], 429);
        }

        // Increment attempts
        RateLimiter::hit($key, (int) $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, (int) $maxAttempts),
        ]);

        return $response;
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        // Use IP + User Agent for better bot detection
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? 'unknown';

        return 'registration:' . sha1($ip . '|' . $userAgent);
    }
}
