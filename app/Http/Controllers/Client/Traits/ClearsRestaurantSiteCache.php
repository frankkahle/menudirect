<?php

namespace App\Http\Controllers\Client\Traits;

use App\Models\RestaurantSite;
use Illuminate\Support\Facades\Cache;

trait ClearsRestaurantSiteCache
{
    /**
     * Clear the cached restaurant site data. Post-cutover this just clears
     * the local Laravel cache — the public-site renderer is on this same VM
     * now, so no cross-host HTTP cache-clear call is needed.
     */
    protected function clearRestaurantSiteCache(RestaurantSite $site): void
    {
        Cache::forget("restaurant_site:{$site->slug}");
        if ($site->custom_domain) {
            Cache::forget("custom_domain:{$site->custom_domain}");
        }
    }
}
