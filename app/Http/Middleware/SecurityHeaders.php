<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the security response headers that HAProxy/Cloudflare do not already
 * set. The edge already supplies HSTS, X-Frame-Options and X-Content-Type-Options,
 * so those are intentionally left alone here to avoid conflicting duplicates.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), payment=(), geolocation=(self), browsing-topics=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        // Permissive by design: the front-end currently loads Tailwind, Alpine
        // and Mapbox from CDNs and relies on inline scripts/styles, so the
        // policy must allow those. It still locks down object-src, base-uri and
        // frame-ancestors. Tighten it once the CDN assets are bundled locally.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://unpkg.com https://challenges.cloudflare.com https://api.mapbox.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://api.mapbox.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net https://cdn.jsdelivr.net",
            "connect-src 'self' https://api.mapbox.com https://challenges.cloudflare.com",
            "worker-src 'self' blob:",
            "child-src 'self' blob:",
            "frame-src 'self' https://challenges.cloudflare.com https://www.google.com https://www.google.ca",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "form-action 'self'",
        ]);

        $headerName = config('security.csp_enforce')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';
        $headers->set($headerName, $csp);

        return $response;
    }
}
