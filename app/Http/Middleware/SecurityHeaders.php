<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add modern security headers to every response.
     * Defense-in-depth on top of HTTPS + cookie flags + CSRF.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://challenges.cloudflare.com https://www.google.com https://www.gstatic.com https://static.cloudflareinsights.com https://unpkg.com; "
             . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
             . "img-src 'self' data: blob: https:; "
             . "font-src 'self' data: https://fonts.gstatic.com; "
             . "connect-src 'self' https://challenges.cloudflare.com https://api.indexnow.org; "
             . "frame-src 'self' https://challenges.cloudflare.com https://www.google.com; "
             . "object-src 'none'; "
             . "base-uri 'self'; "
             . "form-action 'self'; "
             . "frame-ancestors 'self'; "
             . "upgrade-insecure-requests";

        $response->headers->set('Content-Security-Policy', $csp, false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $response->headers->set('Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), '
            . 'magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()',
            false
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin', false);
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);

        return $response;
    }
}
