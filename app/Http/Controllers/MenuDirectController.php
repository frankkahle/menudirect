<?php

namespace App\Http\Controllers;

use App\Services\TurnstileVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class MenuDirectController extends Controller
{
    // Same lists as ContactController — keep in sync if either grows
    protected array $spamPatterns = [
        'rb\.gy', 'bit\.ly', 'tinyurl\.com', 'goo\.gl', 't\.co', 'ow\.ly',
        'is\.gd', 'buff\.ly', 'adf\.ly', 'iiil\.ink',
        'making money online', 'make money fast', 'work from home opportunity',
        'click here to unsubscribe', 'limited time offer', 'act now',
        'free money', 'cryptocurrency investment', 'bitcoin opportunity',
        'forex trading', 'SEO services', 'link building', 'backlinks',
        'guest post', 'sponsored post', 'PR article', 'increase your rankings',
        'get more traffic', 'songr', 'getsongr',
    ];

    protected array $spamDomains = [
        'tempmail', 'guerrillamail', 'mailinator', 'throwaway', 'fakeinbox',
        '10minutemail', 'getnada', 'mohmal', 'dispostable',
        '.fun', '.xyz', '.top', '.click', '.link', '.work',
        '.gq', '.ml', '.cf', '.tk', '.ga',
    ];

    public function submitLead(Request $request)
    {
        // 1. Rate limit: 3 submissions per IP per hour
        $key = 'menudirect-lead:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => 'Too many submissions. Please try again in ' . ceil($seconds / 60) . ' minutes.',
            ])->withInput();
        }

        // 2. Honeypot — bots fill hidden fields, humans don't
        if ($request->filled('website')) {
            Log::warning('MenuDirect honeypot triggered', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
            ]);
            return $this->fakeSuccess();
        }

        // 3. Time check — submissions in under 3 seconds are bots
        $formLoadedAt = $request->input('_form_token');
        if ($formLoadedAt && (time() - (int) base64_decode($formLoadedAt)) < 3) {
            Log::warning('MenuDirect form submitted too quickly', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
            ]);
            return $this->fakeSuccess();
        }

        // 4. Turnstile (if configured) — fails closed only when keys are set
        if (TurnstileVerifier::isConfigured()) {
            $token = $request->input('cf-turnstile-response');
            if (!TurnstileVerifier::verify($token, $request->ip())) {
                Log::warning('MenuDirect Turnstile failed', [
                    'ip' => $request->ip(),
                    'email' => $request->input('email'),
                ]);
                return $this->fakeSuccess();
            }
        }

        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:5000',
        ]);

        // 5. Content filter — keywords, suspicious domains, URL stuffing
        if ($this->isSpam($validated)) {
            Log::warning('MenuDirect spam content detected', [
                'ip' => $request->ip(),
                'email' => $validated['email'],
                'restaurant' => $validated['restaurant_name'],
            ]);
            return $this->fakeSuccess();
        }

        // Sanitize free-text fields
        $validated['restaurant_name'] = strip_tags($validated['restaurant_name']);
        $validated['contact_name'] = strip_tags($validated['contact_name']);
        if (!empty($validated['message'])) {
            $validated['message'] = strip_tags($validated['message']);
        }

        RateLimiter::hit($key, 3600);

        // Email the lead — always, even if API write fails
        try {
            Mail::raw(
                "New MenuDirect Lead!\n\n" .
                "Restaurant: {$validated['restaurant_name']}\n" .
                "Contact: {$validated['contact_name']}\n" .
                "Email: {$validated['email']}\n" .
                "Phone: " . ($validated['phone'] ?? 'Not provided') . "\n" .
                "Submitter IP: {$request->ip()}\n\n" .
                "Message:\n" . ($validated['message'] ?? '(none)') . "\n\n" .
                "Submitted: " . now()->format('Y-m-d H:i:s'),
                function ($message) use ($validated) {
                    $message->from('no-reply@menudirect.ca', 'MenuDirect')
                        ->to('frank@sos-tech.ca')
                        ->subject("MenuDirect Lead: {$validated['restaurant_name']}");
                }
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send MenuDirect lead email', [
                'error' => $e->getMessage(),
                'lead' => $validated,
            ]);
        }

        // Persist to portal — best-effort, do not block user response
        try {
            $portalUrl = config('services.portal.url', 'https://portal.sos-tech.ca');
            $token = config('services.portal.menudirect_intake_token');

            if (!empty($token)) {
                Http::timeout(5)
                    ->withToken($token)
                    ->post("{$portalUrl}/api/menudirect/leads", array_merge($validated, [
                        'submitter_ip' => $request->ip(),
                    ]));
            } else {
                Log::warning('MENUDIRECT_INTAKE_TOKEN not configured on sos-tech — lead not persisted');
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send lead to Portal API', [
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->back()->with('success', "Thank you! I'll review your request and create your demo within 48 hours.");
    }

    public function createDemo(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $portalUrl = config('services.portal.url', 'https://portal.sos-tech.ca');

            $response = Http::timeout(15)->post("{$portalUrl}/api/demo/create", [
                'email' => $validated['email'],
                'name' => $validated['name'] ?? null,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['dashboard_url'])) {
                    return redirect()->away($data['dashboard_url']);
                }
            }

            $errorMsg = $response->json('error') ?? 'Unable to create demo session.';

            if ($response->status() === 429) {
                $errorMsg = 'Too many demo requests. Please try again in a few minutes.';
            }

            return redirect()->back()
                ->withInput()
                ->with('demo_error', $errorMsg);

        } catch (\Exception $e) {
            Log::error('Failed to create demo sandbox', [
                'error' => $e->getMessage(),
                'email' => $validated['email'],
            ]);

            return redirect()->back()
                ->withInput()
                ->with('demo_error', 'Something went wrong. Please try again or contact us.');
        }
    }

    /**
     * Return a fake success response so bots/spammers can't tell they were caught.
     */
    protected function fakeSuccess()
    {
        return redirect()->back()->with('success', "Thank you! I'll review your request and create your demo within 48 hours.");
    }

    protected function isSpam(array $data): bool
    {
        $content = strtolower(
            ($data['restaurant_name'] ?? '') . ' ' .
            ($data['contact_name'] ?? '') . ' ' .
            ($data['message'] ?? '')
        );
        $email = strtolower($data['email'] ?? '');

        foreach ($this->spamPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                return true;
            }
        }

        foreach ($this->spamDomains as $domain) {
            if (str_contains($email, $domain)) {
                return true;
            }
        }

        if (!empty($data['message'])) {
            $urlCount = preg_match_all('/https?:\/\//i', $data['message']);
            if ($urlCount > 2) {
                return true;
            }
            if (preg_match('/https?:\/\/[a-z0-9]+\.[a-z]{2,3}\/[a-zA-Z0-9]+$/i', $data['message'])) {
                return true;
            }
        }

        return false;
    }
}
