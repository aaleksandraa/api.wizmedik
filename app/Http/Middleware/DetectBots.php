<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DetectBots
{
    /**
     * Known bot user agents
     */
    protected array $botPatterns = [
        '/bot/i',
        '/crawler/i',
        '/spider/i',
        '/scraper/i',
        '/curl/i',
        '/wget/i',
        '/python/i',
        '/java/i',
        '/go-http-client/i',
    ];

    /**
     * Suspicious patterns in requests
     */
    protected array $suspiciousPatterns = [
        'email' => '/test@test\.com|admin@|root@|noreply@/i',
        'name' => '/test|admin|root|bot|spam/i',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check user agent
        $userAgent = $request->userAgent() ?? '';

        foreach ($this->botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                Log::warning('Bot detected in registration', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'path' => $request->path(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Registracija nije moguća. Molimo kontaktirajte podršku.',
                ], 403);
            }
        }

        // Check for suspicious data patterns
        if ($this->hasSuspiciousData($request)) {
            Log::warning('Suspicious registration data detected', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'account_email' => $request->input('account_email'),
            ]);

            // Don't block immediately, but log for review
            // You can enable blocking by uncommenting below:
            // return response()->json([
            //     'success' => false,
            //     'message' => 'Registracija nije moguća. Molimo koristite validne podatke.',
            // ], 422);
        }

        // Check submission time (if provided by frontend)
        $submissionTime = $request->header('X-Submission-Time');
        if ($submissionTime && (int)$submissionTime < 3000) {
            Log::warning('Suspiciously fast registration submission', [
                'ip' => $request->ip(),
                'submission_time' => $submissionTime,
            ]);
        }

        return $next($request);
    }

    /**
     * Check if request contains suspicious data
     */
    protected function hasSuspiciousData(Request $request): bool
    {
        // Check email
        $email = $request->input('email', '');
        $accountEmail = $request->input('account_email', '');

        if (preg_match($this->suspiciousPatterns['email'], $email) ||
            preg_match($this->suspiciousPatterns['email'], $accountEmail)) {
            return true;
        }

        // Check name fields
        $nameFields = ['ime', 'prezime', 'naziv', 'kontakt_ime'];
        foreach ($nameFields as $field) {
            $value = $request->input($field, '');
            if ($value && preg_match($this->suspiciousPatterns['name'], $value)) {
                return true;
            }
        }

        return false;
    }
}
