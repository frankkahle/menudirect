<?php

namespace App\Http\Controllers\Client\Traits;

use App\Models\RestaurantSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait ClearsRestaurantSiteCache
{
    /**
     * Invalidate every cache layer that could hold a stale snapshot of this site:
     *   - portal's own toSiteArray() Redis cache
     *   - portal's sites-list cache
     *   - sos-tech frontend file cache (via HTTP with shared secret)
     *
     * Call after any mutation that affects what the public site renders.
     */
    protected function clearSiteCache(RestaurantSite $site): void
    {
        Cache::forget("restaurant_site:{$site->slug}");
        Cache::forget('restaurant_sites_list');

        try {
            $sosUrl = config('services.sostech.url', 'https://sos-tech.ca');
            $secret = config('services.sostech.cache_clear_secret');
            if ($secret) {
                Http::timeout(3)
                    ->withHeaders(['X-Cache-Secret' => $secret])
                    ->get("{$sosUrl}/api/cache/clear/{$site->slug}");
            }
        } catch (\Exception $e) {
            Log::debug("Failed to clear sos-tech cache for {$site->slug}: " . $e->getMessage());
        }
    }
}
