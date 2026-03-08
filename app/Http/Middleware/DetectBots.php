<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class DetectBots
{
    /**
     * Known bot user agents.
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
     * Suspicious patterns in requests.
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
        $userAgent = $request->userAgent() ?? '';

        foreach ($this->botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                Log::warning('Bot detected in request', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'path' => $request->path(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Zahtev nije dozvoljen.',
                ], 403);
            }
        }

        if ($this->hasSuspiciousData($request)) {
            $suspiciousKey = 'detect-bots:suspicious:' . sha1($request->ip() . '|' . ($userAgent !== '' ? $userAgent : 'unknown'));
            RateLimiter::hit($suspiciousKey, 3600);

            $attempts = RateLimiter::attempts($suspiciousKey);
            $threshold = max(1, (int) config('services.bot_protection.suspicious_threshold', 2));

            Log::warning('Suspicious request payload detected', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'email' => $request->input('email'),
                'account_email' => $request->input('account_email'),
                'attempts' => $attempts,
                'threshold' => $threshold,
            ]);

            $blockSuspicious = (bool) config('services.bot_protection.block_suspicious', false);
            if ($blockSuspicious && $attempts >= $threshold) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zahtev je odbijen zbog sumnjivih podataka.',
                ], 422);
            }
        }

        $submissionTime = $request->header('X-Submission-Time');
        if ($submissionTime !== null && (int) $submissionTime > 0 && (int) $submissionTime < 3000) {
            Log::warning('Suspiciously fast form submission', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'submission_time' => $submissionTime,
            ]);
        }

        return $next($request);
    }

    /**
     * Check if request contains suspicious data.
     */
    protected function hasSuspiciousData(Request $request): bool
    {
        $email = (string) $request->input('email', '');
        $accountEmail = (string) $request->input('account_email', '');

        if (
            preg_match($this->suspiciousPatterns['email'], $email) ||
            preg_match($this->suspiciousPatterns['email'], $accountEmail)
        ) {
            return true;
        }

        $nameFields = ['ime', 'prezime', 'naziv', 'kontakt_ime', 'guest_ime', 'guest_prezime'];
        foreach ($nameFields as $field) {
            $value = (string) $request->input($field, '');
            if ($value !== '' && preg_match($this->suspiciousPatterns['name'], $value)) {
                return true;
            }
        }

        return false;
    }
}

