<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Http\Controllers\Controller;
use App\Models\RestaurantSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RestaurantPaymentsController extends Controller
{
    use AuthorizesRestaurantSite;

    /**
     * Base URL for the centralized Payment API on the Manager server.
     */
    protected function paymentApiBase(): string
    {
        return rtrim(config('manager.api_url', 'https://api.sos-tech.ca/api/v1'), '/') . '/payments';
    }

    protected function paymentApiToken(): string
    {
        return \App\Models\ApiKey::get('manager', 'api_token') ?? config('manager.api_token', '');
    }

    protected function apiClient()
    {
        return Http::withToken($this->paymentApiToken())
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Show the Online Payments settings page for a restaurant site.
     * This is the single entry point — it handles all the states:
     *   - Plan doesn't support online payments (upgrade CTA)
     *   - Plan supports it but not enabled (enable + onboard CTA)
     *   - Enabled but onboarding incomplete (continue onboarding)
     *   - Fully active (status card + dashboard link)
     */
    public function show(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $plan = $site->currentPlan();
        $canEnable = $site->canEnableOnlinePayments();
        $requiresAddon = $site->onlinePaymentsRequiresAddon();
        $platformFeePercent = $site->platformFeePercent();

        // If they already have a Stripe account, refresh its status from the Manager API
        $accountStatus = null;
        if ($site->stripe_account_id) {
            try {
                $resp = $this->apiClient()->get(
                    $this->paymentApiBase() . '/connect/accounts/' . $site->stripe_account_id
                );
                if ($resp->successful()) {
                    $accountStatus = $resp->json();

                    // Update local flags if Stripe state has changed
                    $updates = [
                        'stripe_charges_enabled' => $accountStatus['charges_enabled'] ?? false,
                        'stripe_payouts_enabled' => $accountStatus['payouts_enabled'] ?? false,
                    ];

                    if ($accountStatus['charges_enabled'] ?? false) {
                        $updates['stripe_account_status'] = 'active';
                        if (!$site->stripe_onboarded_at) {
                            $updates['stripe_onboarded_at'] = now();
                        }
                    } elseif ($accountStatus['details_submitted'] ?? false) {
                        $updates['stripe_account_status'] = 'pending_review';
                    } else {
                        $updates['stripe_account_status'] = 'onboarding';
                    }

                    $site->update($updates);
                    $site->refresh();
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch Stripe account status for site {$site->id}: {$e->getMessage()}");
            }
        }

        return view('client.restaurant.payments.show', [
            'site' => $site,
            'plan' => $plan,
            'canEnable' => $canEnable,
            'requiresAddon' => $requiresAddon,
            'platformFeePercent' => $platformFeePercent,
            'accountStatus' => $accountStatus,
        ]);
    }

    /**
     * Enable online payments on the restaurant site — creates Stripe Connect
     * account and redirects to Stripe-hosted onboarding.
     *
     * Validates plan eligibility and acknowledges the $10/mo add-on if on Pro.
     */
    public function enable(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        if (!$site->canEnableOnlinePayments()) {
            return redirect()
                ->route('client.restaurant.payments.show', $site)
                ->withErrors(['plan' => 'Your current plan does not support online payments. Upgrade to SiteFresh Pro or MenuDirect Max to enable this feature.']);
        }

        // If Pro, require explicit acknowledgement of the $10/mo add-on charge
        if ($site->onlinePaymentsRequiresAddon() && !$request->has('acknowledge_addon')) {
            return redirect()
                ->route('client.restaurant.payments.show', $site)
                ->withErrors(['acknowledge' => 'Please acknowledge the $10/month add-on fee before enabling online payments.']);
        }

        // Step 1: Create the Stripe Connect account if we don't have one
        if (!$site->stripe_account_id) {
            try {
                $resp = $this->apiClient()->post($this->paymentApiBase() . '/connect/accounts', [
                    'email' => $site->email ?: $site->client->email,
                    'business_name' => $site->business_name,
                    'country' => 'CA',
                    'source' => 'menudirect',
                ]);

                if (!$resp->successful()) {
                    Log::error("Failed to create Stripe Connect account for site {$site->id}", [
                        'response' => $resp->json(),
                    ]);
                    return back()->withErrors(['stripe' => 'Failed to create Stripe account: ' . ($resp->json()['error'] ?? 'Unknown error')]);
                }

                $data = $resp->json();

                $site->update([
                    'stripe_account_id' => $data['account_id'],
                    'stripe_account_status' => 'onboarding',
                    'online_payments_enabled' => true,
                ]);

                // Track add-on on the service record so it shows on next invoice
                if ($site->onlinePaymentsRequiresAddon()) {
                    $this->enableOnlinePaymentsAddon($site);
                }
            } catch (\Exception $e) {
                Log::error("Stripe Connect account creation failed: {$e->getMessage()}");
                return back()->withErrors(['stripe' => 'Could not create Stripe account. Please try again.']);
            }
        }

        // Step 2: Generate an onboarding link and redirect
        return $this->redirectToOnboarding($site);
    }

    /**
     * Generate a fresh onboarding link and redirect. Called both for initial
     * onboarding and to resume if the user bailed partway through.
     */
    public function redirectToOnboarding(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        if (!$site->stripe_account_id) {
            return back()->withErrors(['stripe' => 'No Stripe account to onboard.']);
        }

        try {
            $resp = $this->apiClient()->post(
                $this->paymentApiBase() . "/connect/accounts/{$site->stripe_account_id}/onboarding-link",
                [
                    'return_url' => route('client.restaurant.payments.return', $site),
                    'refresh_url' => route('client.restaurant.payments.refresh', $site),
                ]
            );

            if (!$resp->successful()) {
                return back()->withErrors(['stripe' => 'Could not generate onboarding link.']);
            }

            return redirect()->away($resp->json()['url']);
        } catch (\Exception $e) {
            Log::error("Onboarding link failed: {$e->getMessage()}");
            return back()->withErrors(['stripe' => 'Could not start onboarding. Please try again.']);
        }
    }

    /**
     * Handle the return from Stripe-hosted onboarding.
     * Refresh account status and redirect back to the settings page.
     */
    public function return(RestaurantSite $site)
    {
        $this->authorizeSite($site);
        return redirect()->route('client.restaurant.payments.show', $site)
            ->with('status', 'Welcome back! We\'re checking your Stripe account status...');
    }

    /**
     * Handle the refresh URL from Stripe — user clicked the refresh link
     * during onboarding (e.g., their link expired).
     */
    public function refresh(RestaurantSite $site)
    {
        $this->authorizeSite($site);
        return $this->redirectToOnboarding($site);
    }

    /**
     * Open the Stripe Express dashboard for the connected account.
     */
    public function openDashboard(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        if (!$site->stripe_account_id) {
            return back()->withErrors(['stripe' => 'No Stripe account connected.']);
        }

        try {
            $resp = $this->apiClient()->post(
                $this->paymentApiBase() . "/connect/accounts/{$site->stripe_account_id}/dashboard-link"
            );

            if (!$resp->successful()) {
                return back()->withErrors(['stripe' => 'Could not open Stripe dashboard.']);
            }

            return redirect()->away($resp->json()['url']);
        } catch (\Exception $e) {
            return back()->withErrors(['stripe' => 'Could not open Stripe dashboard.']);
        }
    }

    /**
     * Disable online payments — doesn't delete the Stripe account, just
     * stops routing orders through it and removes the add-on fee.
     */
    public function disable(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $site->update(['online_payments_enabled' => false]);
        $this->disableOnlinePaymentsAddon($site);

        return redirect()->route('client.restaurant.payments.show', $site)
            ->with('status', 'Online payments disabled. Your Stripe account is still connected — you can re-enable anytime.');
    }

    /**
     * Find or create the Service record for this restaurant and mark the
     * online_payments add-on as enabled in its addons JSON.
     */
    protected function enableOnlinePaymentsAddon(RestaurantSite $site): void
    {
        $service = \App\Models\Service::where('serviceable_type', RestaurantSite::class)
            ->where('serviceable_id', $site->id)
            ->first();

        if (!$service) {
            Log::warning("No Service record for site {$site->id} — cannot enable add-on billing");
            return;
        }

        $addons = $service->addons ?? [];
        $addons['online_payments'] = [
            'enabled' => true,
            'price' => 10.00,
            'enabled_at' => now()->toIso8601String(),
        ];
        $service->update(['addons' => $addons]);
    }

    protected function disableOnlinePaymentsAddon(RestaurantSite $site): void
    {
        $service = \App\Models\Service::where('serviceable_type', RestaurantSite::class)
            ->where('serviceable_id', $site->id)
            ->first();

        if (!$service) return;

        $addons = $service->addons ?? [];
        if (isset($addons['online_payments'])) {
            $addons['online_payments']['enabled'] = false;
            $addons['online_payments']['disabled_at'] = now()->toIso8601String();
        }
        $service->update(['addons' => $addons]);
    }
}
