<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the management/provisioning API. Only portal.sos-tech.ca is
 * expected to call it, so a single static bearer secret + IP allowlist is
 * sufficient. Fails closed: an empty token or empty allowlist denies all.
 */
class VerifyManagementApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.management.token');
        $provided = (string) ($request->bearerToken() ?? '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return $this->deny('unauthorized', 'Invalid or missing API token.', 401);
        }

        $allowed = config('services.management.allowed_ips', []);
        if (empty($allowed) || ! in_array($request->ip(), $allowed, true)) {
            Log::warning('Management API IP rejected', ['ip' => $request->ip()]);
            return $this->deny('ip_forbidden', 'Source IP not allowed.', 403);
        }

        return $next($request);
    }

    private function deny(string $code, string $message, int $status): Response
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
