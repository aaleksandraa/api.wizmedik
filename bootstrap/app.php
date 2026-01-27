<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'doctor' => \App\Http\Middleware\EnsureDoctor::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'rate_limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'audit' => \App\Http\Middleware\AuditLogMiddleware::class,
            'cache.response' => \App\Http\Middleware\CacheResponseMiddleware::class,
            'compress' => \App\Http\Middleware\CompressResponse::class,
            'detect.bots' => \App\Http\Middleware\DetectBots::class,
            'registration.throttle' => \App\Http\Middleware\RegistrationThrottle::class,
        ]);

        // Return JSON for unauthenticated API requests instead of redirecting
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(response()->json(['message' => 'Unauthenticated. Please login.'], 401));
            }
            return route('login');
        });

        // Enable rate limiting for API routes
        $middleware->api(
            prepend: [
                \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            ]
        );

        // CORS for production
        $middleware->api(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\AuditLogMiddleware::class,
            \App\Http\Middleware\CompressResponse::class,
            \App\Http\Middleware\SetCacheHeaders::class, // Performance optimization - cache headers
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Production error handling - hide sensitive details
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*') && config('app.env') === 'production') {
                // Log the full error
                \Log::error('API Exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ]);

                // Return generic error to user (no stack trace)
                return response()->json([
                    'message' => 'Došlo je do greške na serveru. Molimo pokušajte ponovo.',
                    'error_id' => \Str::uuid()->toString(),
                ], 500);
            }
        });
    })->create();
