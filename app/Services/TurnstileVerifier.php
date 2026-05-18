<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerifier
{
    public static function isConfigured(): bool
    {
        return !empty(config('services.turnstile.site_key'))
            && !empty(config('services.turnstile.secret_key'));
    }

    public static function siteKey(): ?string
    {
        return config('services.turnstile.site_key');
    }

    public static function verify(?string $token, ?string $ip = null): bool
    {
        if (!self::isConfigured()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::timeout(5)->asForm()->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                array_filter([
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ])
            );

            $data = $response->json();
            $success = (bool) ($data['success'] ?? false);

            if (!$success) {
                Log::warning('Turnstile verification failed', [
                    'ip' => $ip,
                    'errors' => $data['error-codes'] ?? [],
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('Turnstile verification error', [
                'error' => $e->getMessage(),
                'ip' => $ip,
            ]);
            return false;
        }
    }
}
