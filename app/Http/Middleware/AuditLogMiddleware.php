<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request and log important actions
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log authenticated requests
        if ($request->user()) {
            $this->logAuditTrail($request, $response);
        }

        return $response;
    }

    /**
     * Log audit trail for important actions
     */
    protected function logAuditTrail(Request $request, Response $response): void
    {
        // Only log write operations (POST, PUT, PATCH, DELETE)
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        // Skip logging for certain routes
        $skipRoutes = [
            '/api/logout',
            '/api/user',
        ];

        if (in_array($request->path(), $skipRoutes)) {
            return;
        }

        // Prepare audit data
        $auditData = [
            'user_id' => $request->user()->id,
            'user_email' => $request->user()->email,
            'user_role' => $request->user()->getRoleNames()->first(),
            'action' => $request->method(),
            'endpoint' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'timestamp' => now()->toIso8601String(),
        ];

        // Add request data for certain operations (sanitized)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $auditData['request_data'] = $this->sanitizeRequestData($request->all());
        }

        // Log based on status code
        if ($response->getStatusCode() >= 400) {
            Log::channel('audit')->warning('Failed operation', $auditData);
        } else {
            Log::channel('audit')->info('Successful operation', $auditData);
        }

        // Log critical operations separately
        if ($this->isCriticalOperation($request)) {
            Log::channel('security')->info('Critical operation performed', $auditData);
        }
    }

    /**
     * Sanitize request data (remove sensitive fields)
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'secret', 'api_key'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Check if operation is critical
     */
    protected function isCriticalOperation(Request $request): bool
    {
        $criticalPatterns = [
            '/api/admin/',
            '/api/doctors/me/profile',
            '/api/appointments',
            '/api/2fa/',
            '/api/users/',
        ];

        foreach ($criticalPatterns as $pattern) {
            if (str_contains($request->path(), $pattern)) {
                return true;
            }
        }

        return false;
    }
}
