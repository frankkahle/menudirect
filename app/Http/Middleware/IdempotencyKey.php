<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replays the stored response for a repeated Idempotency-Key, so a re-sent
 * provisioning command (network retry) does not create duplicates.
 */
class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return $next($request);
        }

        $cacheKey = 'idem:' . sha1($request->path() . '|' . $key);
        if ($cached = Cache::get($cacheKey)) {
            return response($cached['body'], $cached['status'])->header('Content-Type', 'application/json');
        }

        $response = $next($request);

        if ($response->getStatusCode() < 400) {
            Cache::put($cacheKey, [
                'body' => $response->getContent(),
                'status' => $response->getStatusCode(),
            ], now()->addDay());
        }

        return $response;
    }
}
