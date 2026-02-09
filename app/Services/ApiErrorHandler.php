<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiErrorHandler
{
    /**
     * Handle API validation errors with structured response
     */
    public static function handleValidationError(ValidationException $exception): JsonResponse
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'validation_error',
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ],
            'validation_errors' => $exception->errors(),
        ];

        Log::channel('security')->info('API validation error', $errorData);

        return response()->json([
            'message' => $exception->getMessage() ?: 'The given data was invalid.',
            'errors' => $exception->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Handle API authentication errors with structured response
     */
    public static function handleAuthenticationError(string $message = 'Unauthenticated'): JsonResponse
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'authentication_error',
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'has_auth_header' => request()->hasHeader('Authorization'),
            ],
        ];

        Log::channel('security')->warning('API authentication error', $errorData);

        return response()->json([
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Handle API authorization errors with structured response
     */
    public static function handleAuthorizationError(string $message = 'This action is unauthorized.'): JsonResponse
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'authorization_error',
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'user_id' => auth()->id(),
                'user_roles' => auth()->user()?->getRoleNames()->toArray(),
            ],
        ];

        Log::channel('security')->warning('API authorization error', $errorData);

        return response()->json([
            'message' => $message,
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Handle API not found errors with structured response
     */
    public static function handleNotFoundError(string $message = 'Resource not found.'): JsonResponse
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'not_found_error',
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ],
        ];

        Log::info('API not found error', $errorData);

        return response()->json([
            'message' => $message,
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Handle API server errors with structured response
     */
    public static function handleServerError(Throwable $exception, string $message = 'Internal server error.'): JsonResponse
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'server_error',
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'user_id' => auth()->id(),
            ],
        ];

        Log::error('API server error', $errorData);

        // Don't expose internal error details in production
        $responseMessage = config('app.debug') ? $exception->getMessage() : $message;

        return response()->json([
            'message' => $responseMessage,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Handle API rate limit errors with structured response
     */
    public static function handleRateLimitError(string $message = 'Too many requests.'): JsonResponse
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'rate_limit_error',
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'user_id' => auth()->id(),
            ],
        ];

        Log::channel('security')->warning('API rate limit exceeded', $errorData);

        return response()->json([
            'message' => $message,
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Handle API maintenance mode errors with structured response
     */
    public static function handleMaintenanceError(string $message = 'Service temporarily unavailable.'): JsonResponse
    {
        $errorData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'maintenance_error',
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ],
        ];

        Log::info('API maintenance mode access attempt', $errorData);

        return response()->json([
            'message' => $message,
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * Create a standardized error response
     */
    public static function createErrorResponse(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        array $errors = [],
        array $context = []
    ): JsonResponse {
        $response = ['message' => $message];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        // Log the error with context
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'error_type' => 'custom_api_error',
            'status_code' => $statusCode,
            'message' => $message,
            'request_info' => [
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ],
            'context' => $context,
        ];

        $logLevel = $statusCode >= 500 ? 'error' : 'warning';
        Log::$logLevel('Custom API error', $logData);

        return response()->json($response, $statusCode);
    }
}
