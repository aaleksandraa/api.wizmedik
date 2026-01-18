<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$request->user()->hasRole('doctor') && !$request->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Forbidden. This endpoint is only accessible to doctors.'
            ], 403);
        }

        return $next($request);
    }
}

