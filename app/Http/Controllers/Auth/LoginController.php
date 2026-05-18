<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Cache\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use App\Http\Middleware\VerifyCaptcha;

class LoginController extends Controller
{
    public function __construct(
        protected RateLimiter $limiter,
        protected AuditService $audit
    ) {}

    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string']
        ]);

        if (Auth::attempt($data, $request->boolean('remember'))) {
            $user = Auth::user();

            // Clear failed login attempts on success
            VerifyCaptcha::clearAttempts($request, 'login');

            // Check if 2FA is enabled (skip for whitelisted IPs)
            if ($user->two_factor_confirmed_at && !$this->isIpWhitelistedFor2fa($request)) {
                // Log out immediately to prevent authenticated access before 2FA
                Auth::logout();

                // Regenerate session first to get a fresh CSRF token
                $request->session()->regenerate();

                // Now store 2FA data in the new session
                $request->session()->put('2fa:user:id', $user->id);
                $request->session()->put('2fa:remember', $request->boolean('remember'));
                $request->session()->put('2fa:email', $user->email);

                return redirect()->route('two-factor.challenge');
            }

            // Log successful login
            $this->audit->logLogin($user, $request);

            $request->session()->regenerate();
            return redirect()->intended(route('client.dashboard'));
        }

        // Track failed login attempt
        $this->incrementFailedAttempts($request);

        // Log failed login attempt
        $this->audit->logLoginFailed($data['email'], $request);

        return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }

    /**
     * Increment failed login attempts counter
     */
    protected function incrementFailedAttempts(Request $request): void
    {
        $ip = $request->ip();
        $identifier = $request->input('email') ?? 'anonymous';
        $key = 'captcha_check:login:' . sha1($ip . '|' . $identifier);

        // Increment with 15 minute decay
        $this->limiter->hit($key, 900);
    }

    public function showTwoFactorChallenge(Request $request)
    {
        if (!$request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyTwoFactorChallenge(Request $request)
    {
        if (!$request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('2fa:user:id');

        // Try to find user in both Client and User models
        $user = \App\Models\Client::find($userId);
        if (!$user) {
            $user = \App\Models\User::find($userId);
        }

        if (!$user || !$user->two_factor_secret) {
            return redirect()->route('login');
        }

        try {
            $secret = decrypt($user->two_factor_secret);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // 2FA secret was encrypted with a different APP_KEY
            // Disable 2FA for this user so they can log in and re-enable it
            $user->update([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]);

            // Complete login since 2FA is now disabled
            $this->completeLogin($request, $user);

            return redirect()->intended(route('client.dashboard'))
                ->with('status', 'Two-factor authentication was reset due to an encryption key change. Please re-enable 2FA in your security settings.');
        }

        $google2fa = new Google2FA();

        // Check if it's a recovery code
        if (strlen($request->code) > 6) {
            if ($this->useRecoveryCode($user, $request->code)) {
                $this->completeLogin($request, $user);
                $this->audit->log2faSuccess($user, $request);
                return redirect()->intended(route('client.dashboard'))
                    ->with('status', 'Recovery code used. Please generate new recovery codes.');
            }
        } else {
            // Verify TOTP code
            $valid = $google2fa->verifyKey($secret, $request->code);

            if ($valid) {
                $this->completeLogin($request, $user);
                $this->audit->log2faSuccess($user, $request);
                return redirect()->intended(route('client.dashboard'));
            }
        }

        // Log failed 2FA attempt
        $email = $request->session()->get('2fa:email', $user->email);
        $this->audit->log2faFailed($email, $request);

        return back()->withErrors(['code' => 'The provided code is invalid.']);
    }

    private function useRecoveryCode($user, $code)
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        try {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Recovery codes were encrypted with a different APP_KEY
            return false;
        }

        // Check if code exists
        $key = array_search(strtoupper($code), $recoveryCodes);
        if ($key === false) {
            return false;
        }

        // Remove used code
        unset($recoveryCodes[$key]);
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
        ]);

        return true;
    }

    private function completeLogin(Request $request, $user)
    {
        Auth::login($user, $request->session()->get('2fa:remember', false));
        $request->session()->forget(['2fa:user:id', '2fa:remember']);
        $request->session()->regenerate();
    }

    public function logout(Request $request)
    {
        // Log logout before clearing auth
        $user = Auth::user();
        $this->audit->logLogout($user, $request);

        // Mark current session as logged out
        $sessionId = session()->getId();
        \App\Models\ClientSession::where('session_id', $sessionId)
            ->update(['logged_out_at' => now()]);

        // Clean up any active demo session (check by cookie token, since
        // the authenticated user may be the real user, not the demo user)
        $demoToken = $request->cookie('demo_token') ?? $request->query('demo_token');
        if ($demoToken) {
            \App\Models\DemoSession::where('token', $demoToken)
                ->whereNull('cleaned_up_at')
                ->update(['cleaned_up_at' => now()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withCookie(cookie()->forget('demo_token'));
    }

    /**
     * Check if the request IP is whitelisted to skip 2FA
     */
    protected function isIpWhitelistedFor2fa(Request $request): bool
    {
        $whitelist = config('auth.2fa_ip_whitelist', []);

        if (empty($whitelist)) {
            return false;
        }

        $clientIp = $request->ip();

        foreach ($whitelist as $trustedIp) {
            // Support CIDR notation (e.g., 192.168.22.0/24)
            if (str_contains($trustedIp, '/')) {
                if ($this->ipInCidr($clientIp, $trustedIp)) {
                    return true;
                }
            } elseif ($clientIp === $trustedIp) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is within a CIDR range
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);

        return ($ip & $mask) === ($subnet & $mask);
    }
}
