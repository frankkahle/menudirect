<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SampleSiteController extends Controller
{
    /**
     * Resolve a custom domain to a restaurant site and render it.
     * Called when the request hostname matches a restaurant's custom_domain.
     */
    public function showByDomain(Request $request, ?string $path = null)
    {
        $domain = strtolower($request->getHost());

        // Look up slug by custom domain via Portal API
        $slug = $this->resolveCustomDomain($domain);

        if (!$slug) {
            abort(404, "No restaurant site found for {$domain}");
        }

        return $this->show($slug, $path);
    }

    /**
     * Resolve a custom domain to a restaurant slug via Portal API.
     * Caches the mapping for 1 hour.
     */
    protected function resolveCustomDomain(string $domain): ?string
    {
        return Cache::remember("custom_domain:{$domain}", 3600, function () use ($domain) {
            try {
                $portalUrl = config('services.portal.url', 'https://portal.sos-tech.ca');
                $response = Http::timeout(5)->get("{$portalUrl}/api/restaurant-domain/{$domain}");

                if ($response->successful()) {
                    return $response->json()['slug'] ?? null;
                }
            } catch (\Exception $e) {
                Log::warning("Failed to resolve custom domain {$domain}: {$e->getMessage()}");
            }
            return null;
        });
    }

    /**
     * Display a sample site
     */
    public function show(string $slug, ?string $path = null)
    {
        $sites = config('samples.sites', []);

        // First check if sample site exists in config (static samples)
        if (isset($sites[$slug])) {
            return $this->renderFromConfig($slug, $sites[$slug], $path);
        }

        // If not found in config, try to fetch from Portal API (dynamic restaurant sites)
        $site = $this->fetchFromPortalApi($slug);

        if ($site) {
            return $this->renderFromApi($site, $path);
        }

        // Not found in either source
        abort(404);
    }

    /**
     * Render a site from config data (existing behavior)
     */
    protected function renderFromConfig(string $slug, array $siteConfig, ?string $path = null)
    {
        $defaults = config('samples.defaults', []);

        // Merge with defaults
        $site = array_merge($defaults, $siteConfig);
        $site['slug'] = $slug;
        $site['colors'] = array_merge($defaults['colors'] ?? [], $siteConfig['colors'] ?? []);

        // Build structured address from API pre-parsed fields (or parse string fallback)
        $rawAddress = $site['address'] ?? '';
        if (is_string($rawAddress) && !empty($rawAddress)) {
            $site['address'] = [
                'full' => $rawAddress,
                'street' => $site['address_street'] ?? explode(',', $rawAddress)[0] ?? '',
                'city' => $site['address_city'] ?? trim(explode(',', $rawAddress)[1] ?? ''),
                'province' => $site['address_province'] ?? 'NB',
                'postal' => $site['address_postal'] ?? '',
                'country' => $site['address_country'] ?? 'CA',
            ];
        }

        // Get sales banner config
        $salesBanner = config('samples.sales_banner', []);

        // Handle sub-pages
        if ($path === 'reviews') {
            return view('samples.pages.reviews', [
                'site' => $site,
                'salesBanner' => $salesBanner,
            ]);
        }

        // Determine template
        $template = $site['template'] ?? 'generic';
        $viewName = "samples.templates.{$template}";

        // Fallback to generic if template doesn't exist
        if (!view()->exists($viewName)) {
            $viewName = 'samples.templates.generic';
        }

        return view($viewName, [
            'site' => $site,
            'salesBanner' => $salesBanner,
        ]);
    }

    /**
     * Fetch site data from Portal API with caching
     */
    protected function fetchFromPortalApi(string $slug): ?array
    {
        $cacheKey = "restaurant_site:{$slug}";

        return Cache::remember($cacheKey, 300, function () use ($slug) {
            try {
                $portalUrl = config('services.portal.url', 'https://portal.sos-tech.ca');
                $response = Http::timeout(5)->get("{$portalUrl}/api/restaurant/{$slug}");

                if ($response->successful()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::warning("Failed to fetch restaurant site from Portal API: {$slug}", [
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Render a site from API data (restaurant sites from Portal)
     */
    protected function renderFromApi(array $site, ?string $path = null)
    {
        $defaults = config('samples.defaults', []);

        // Merge API data with defaults for any missing fields
        $site['colors'] = array_merge($defaults['colors'] ?? [], $site['colors'] ?? []);

        // Build structured address from API pre-parsed fields (or parse string fallback)
        $rawAddress = $site['address'] ?? '';
        if (is_string($rawAddress) && !empty($rawAddress)) {
            $site['address'] = [
                'full' => $rawAddress,
                'street' => $site['address_street'] ?? explode(',', $rawAddress)[0] ?? '',
                'city' => $site['address_city'] ?? trim(explode(',', $rawAddress)[1] ?? ''),
                'province' => $site['address_province'] ?? 'NB',
                'postal' => $site['address_postal'] ?? '',
                'country' => $site['address_country'] ?? 'CA',
            ];
        }

        // Get sales banner config
        $salesBanner = config('samples.sales_banner', []);

        // Handle sub-pages
        if ($path === 'reviews') {
            return view('samples.pages.reviews', [
                'site' => $site,
                'salesBanner' => $salesBanner,
            ]);
        }

        // Determine template (API data includes template type)
        $template = $site['template'] ?? 'restaurant';
        $viewName = "samples.templates.{$template}";

        // Fallback to restaurant template for API-sourced sites
        if (!view()->exists($viewName)) {
            $viewName = 'samples.templates.restaurant';
        }

        return view($viewName, [
            'site' => $site,
            'salesBanner' => $salesBanner,
        ]);
    }

    /**
     * Preview any template using demo-bistro data.
     * GET /template-preview/{template}
     */
    public function templatePreview(string $template)
    {
        $viewName = "samples.templates.{$template}";
        if (!view()->exists($viewName)) {
            abort(404, "Template '{$template}' not found");
        }

        // Load demo-bistro as the sample data source
        $site = $this->fetchFromPortalApi('demo-bistro');
        if (!$site) {
            abort(500, 'Could not load demo site data');
        }

        $defaults = config('samples.defaults', []);
        $site['colors'] = array_merge($defaults['colors'] ?? [], $site['colors'] ?? []);

        $rawAddress = $site['address'] ?? '';
        if (is_string($rawAddress) && !empty($rawAddress)) {
            $site['address'] = [
                'full' => $rawAddress,
                'street' => $site['address_street'] ?? explode(',', $rawAddress)[0] ?? '',
                'city' => $site['address_city'] ?? trim(explode(',', $rawAddress)[1] ?? ''),
                'province' => $site['address_province'] ?? 'NB',
                'postal' => $site['address_postal'] ?? '',
                'country' => $site['address_country'] ?? 'CA',
            ];
        }

        // Override the template
        $site['template'] = $template;

        return view($viewName, [
            'site' => $site,
            'salesBanner' => config('samples.sales_banner', []),
        ]);
    }

    /**
     * List all sample sites (admin view)
     */
    public function index()
    {
        $sites = config('samples.sites', []);

        return view('samples.index', [
            'sites' => $sites,
        ]);
    }

    /**
     * Display reservation status page
     */
    public function reservationStatus(string $token)
    {
        try {
            $portalUrl = config('services.portal.url', 'https://portal.sos-tech.ca');
            $response = Http::timeout(10)->get("{$portalUrl}/api/reservations/{$token}");

            if (!$response->successful()) {
                abort(404, 'Reservation not found');
            }

            $data = $response->json();

            if (!$data['success']) {
                abort(404, 'Reservation not found');
            }

            return view('samples.reservation-status', [
                'reservation' => $data['reservation'],
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch reservation: {$token}", [
                'error' => $e->getMessage(),
            ]);
            abort(404, 'Reservation not found');
        }
    }

    /**
     * Display order tracking page
     */
    public function trackOrder(string $token)
    {
        try {
            $portalUrl = config('services.portal.url', 'https://portal.sos-tech.ca');
            $response = Http::timeout(10)->get("{$portalUrl}/api/orders/{$token}");

            if (!$response->successful()) {
                abort(404, 'Order not found');
            }

            $data = $response->json();

            if (!$data['success']) {
                abort(404, 'Order not found');
            }

            return view('samples.order-tracking', [
                'order' => $data['order'],
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to fetch order: {$token}", [
                'error' => $e->getMessage(),
            ]);
            abort(404, 'Order not found');
        }
    }
}
