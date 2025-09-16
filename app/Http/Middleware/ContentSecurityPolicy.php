<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo aplicar CSP en producción
        if (app()->environment('production')) {
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                "font-src 'self' https://fonts.gstatic.com",
                "img-src 'self' data: https: blob:",
                "connect-src 'self' https://api.mapbox.com https://*.mapbox.com",
                "media-src 'self'",
                "object-src 'none'",
                "frame-src 'self' https://www.google.com https://maps.google.com",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "upgrade-insecure-requests",
                "block-all-mixed-content"
            ];

            $response->headers->set('Content-Security-Policy', implode('; ', $csp));
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        }

        return $response;
    }
}
