<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCaptcha
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request with conditional CAPTCHA verification
     *
     * CAPTCHA is only required after multiple failed attempts, not on every request
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action = 'login', int $threshold = 3): Response
    {
        // Create unique key based on IP + action
        $key = $this->resolveKey($request, $action);
        $attempts = $this->limiter->attempts($key);

        // Only require CAPTCHA after threshold failed attempts
        if ($attempts >= $threshold) {
            // Skip CAPTCHA if keys not configured
            if (!config('captcha.secret') || !config('captcha.sitekey')) {
                \Log::warning('CAPTCHA required but not configured', [
                    'action' => $action,
                    'ip' => $request->ip(),
                    'attempts' => $attempts,
                ]);
                // Continue without CAPTCHA if not configured
                return $next($request);
            }

            // Verify CAPTCHA token
            $captchaToken = $request->input('g-recaptcha-response');

            if (!$captchaToken) {
                return $this->captchaRequired($request, $attempts, $action);
            }

            // Validate CAPTCHA with Google
            $isValid = $this->validateCaptcha($captchaToken, $request->ip());

            if (!$isValid) {
                \Log::warning('CAPTCHA validation failed', [
                    'action' => $action,
                    'ip' => $request->ip(),
                    'attempts' => $attempts,
                ]);

                return $this->captchaRequired($request, $attempts, $action);
            }

            // CAPTCHA passed - reset attempts counter on successful verification
            // Note: We only reset after successful login/action, not just successful CAPTCHA
        }

        return $next($request);
    }

    /**
     * Validate CAPTCHA token with Google
     */
    protected function validateCaptcha(string $token, string $ip): bool
    {
        try {
            $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
                'secret' => config('captcha.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]));

            $result = json_decode($response, true);

            // For reCAPTCHA v3, check score (0.0 - 1.0, higher is more likely human)
            if (isset($result['score'])) {
                return $result['success'] && $result['score'] >= 0.5;
            }

            // For reCAPTCHA v2, just check success
            return $result['success'] ?? false;
        } catch (\Throwable $e) {
            \Log::error('CAPTCHA verification failed', [
                'error' => $e->getMessage(),
                'ip' => $ip,
            ]);

            // Fail open - allow request if CAPTCHA service is down
            return true;
        }
    }

    /**
     * Return CAPTCHA required response
     */
    protected function captchaRequired(Request $request, int $attempts, string $action): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'CAPTCHA verification required after multiple failed attempts.',
                'captcha_required' => true,
                'sitekey' => config('captcha.sitekey'),
            ], 422);
        }

        return back()->withErrors([
            $action => 'Too many failed attempts. Please complete the CAPTCHA verification.',
        ])->with('captcha_required', true);
    }

    /**
     * Resolve unique key for tracking attempts
     */
    protected function resolveKey(Request $request, string $action): string
    {
        $ip = $request->ip();
        $identifier = $request->input('email') ?? $request->input('username') ?? 'anonymous';

        return 'captcha_check:' . $action . ':' . sha1($ip . '|' . $identifier);
    }

    /**
     * Clear attempts counter (call this after successful login/action)
     */
    public static function clearAttempts(Request $request, string $action): void
    {
        $limiter = app(RateLimiter::class);
        $ip = $request->ip();
        $identifier = $request->input('email') ?? $request->input('username') ?? 'anonymous';
        $key = 'captcha_check:' . $action . ':' . sha1($ip . '|' . $identifier);

        $limiter->clear($key);
    }
}
