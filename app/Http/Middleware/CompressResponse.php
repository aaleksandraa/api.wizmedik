<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only compress if client accepts gzip
        if (!str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            return $response;
        }

        // Only compress JSON responses
        if (!str_contains($response->headers->get('Content-Type', ''), 'application/json')) {
            return $response;
        }

        // Don't compress if already compressed
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        // Get the content
        $content = $response->getContent();

        // Only compress if content is large enough (> 1KB)
        if (strlen($content) < 1024) {
            return $response;
        }

        // Compress the content
        $compressed = gzencode($content, 6); // Level 6 is a good balance

        if ($compressed === false) {
            return $response;
        }

        // Set the compressed content
        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
