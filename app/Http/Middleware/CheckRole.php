<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            \Log::warning('Unauthorized access attempt', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            
            return response()->json([
                'message' => 'Unauthorized. Please login to continue.'
            ], 401);
        }

        // Check if user has the required role
        if (!$request->user()->hasRole($role)) {
            \Log::warning('Forbidden access attempt', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'user_role' => $request->user()->roles->pluck('name')->first(),
                'required_role' => $role,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'message' => 'Forbidden. You do not have permission to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}

