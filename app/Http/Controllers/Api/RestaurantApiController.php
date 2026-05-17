<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantSite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class RestaurantApiController extends Controller
{
    /**
     * Get restaurant site data by slug for public rendering.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        // Cache for 5 minutes
        $cacheKey = "restaurant_site:{$slug}";

        $data = Cache::remember($cacheKey, 300, function () use ($slug) {
            $site = RestaurantSite::where('slug', $slug)
                ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
                ->first();

            if (!$site) {
                return null;
            }

            return $site->toSiteArray();
        });

        if (!$data) {
            return response()->json([
                'error' => 'Restaurant site not found',
            ], 404);
        }

        return response()->json($data);
    }

    /**
     * Clear cache for a specific restaurant site.
     * This can be called after updates to force fresh data.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function clearCache(string $slug): JsonResponse
    {
        $cacheKey = "restaurant_site:{$slug}";
        Cache::forget($cacheKey);

        return response()->json(['success' => true]);
    }

    /**
     * List all restaurant sites for sitemap generation.
     * Returns minimal data needed for sitemap: slug, status, updated_at
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $sites = Cache::remember('restaurant_sites_list', 1800, function () {
            return RestaurantSite::whereIn('status', [
                    RestaurantSite::STATUS_ACTIVE,
                    RestaurantSite::STATUS_DEMO
                ])
                ->select('slug', 'business_name', 'status', 'updated_at')
                ->orderBy('business_name')
                ->get()
                ->map(fn ($site) => [
                    'slug' => $site->slug,
                    'name' => $site->business_name,
                    'status' => $site->status,
                    'updated_at' => $site->updated_at?->toIso8601String(),
                ]);
        });

        return response()->json([
            'data' => $sites,
            'count' => $sites->count(),
        ]);
    }

    /**
     * Resolve a custom domain to a restaurant site slug.
     * Used by sos-tech.ca to route custom domains to the correct site.
     *
     * @param string $domain
     * @return JsonResponse
     */
    public function resolveByDomain(string $domain): JsonResponse
    {
        $domain = strtolower($domain);

        $slug = Cache::remember("domain_resolve:{$domain}", 3600, function () use ($domain) {
            // Check primary custom_domain
            $site = RestaurantSite::where('custom_domain', $domain)
                ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
                ->first();

            if ($site) {
                return $site->slug;
            }

            // Check domain aliases stored in settings.domain_aliases
            $sites = RestaurantSite::whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
                ->whereNotNull('settings')
                ->get();

            foreach ($sites as $site) {
                $aliases = $site->settings['domain_aliases'] ?? [];
                if (in_array($domain, array_map('strtolower', $aliases))) {
                    return $site->slug;
                }
            }

            return null;
        });

        if (!$slug) {
            return response()->json(['error' => 'Domain not found'], 404);
        }

        return response()->json(['slug' => $slug, 'domain' => $domain]);
    }
}
