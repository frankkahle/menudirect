<?php

namespace App\Observers;

use App\Jobs\NotifySearchEnginesJob;
use App\Models\RestaurantSite;
use Illuminate\Support\Facades\Cache;

class RestaurantSiteObserver
{
    /**
     * On any RestaurantSite mutation, the sitemap may be stale. Bust the cache
     * so the next /sitemap-restaurants.xml hit rebuilds from the live DB.
     */
    public function created(RestaurantSite $site): void
    {
        $this->bustCaches($site);
        $this->notify($site);
    }

    public function updated(RestaurantSite $site): void
    {
        $this->bustCaches($site);

        // Only ping search engines on changes that actually move SEO needles:
        // slug, status (active/suspended/archived), custom_domain, archived_at.
        $watched = ['slug', 'status', 'custom_domain', 'archived_at', 'name'];
        if (!array_intersect($watched, array_keys($site->getChanges()))) {
            return;
        }
        $this->notify($site);

        // If slug changed, also bust the old-slug page cache.
        if ($site->wasChanged('slug')) {
            $oldSlug = $site->getOriginal('slug');
            if ($oldSlug) {
                Cache::forget("restaurant_site:{$oldSlug}");
                Cache::forget("sitemap.site.{$oldSlug}.xml");
            }
        }
    }

    public function deleted(RestaurantSite $site): void
    {
        $this->bustCaches($site);
    }

    public function restored(RestaurantSite $site): void
    {
        $this->bustCaches($site);
        $this->notify($site);
    }

    protected function bustCaches(RestaurantSite $site): void
    {
        // Apex listing
        Cache::forget('sitemap.restaurants.xml');

        // Per-subdomain sitemap + page render cache
        Cache::forget("sitemap.site.{$site->slug}.xml");
        Cache::forget("restaurant_site:{$site->slug}");

        // Custom-domain → slug resolver cache (used by SampleSiteController)
        if ($site->custom_domain) {
            Cache::forget("custom_domain:{$site->custom_domain}");
        }
        foreach ($site->customDomains as $cd) {
            Cache::forget("custom_domain:{$cd->domain}");
        }
    }

    protected function notify(RestaurantSite $site): void
    {
        // Skip archived sites — no point telling search engines about hidden URLs.
        if ($site->archived_at) {
            return;
        }
        // Skip suspended sites — they 404 to the public.
        if ($site->status === RestaurantSite::STATUS_SUSPENDED) {
            return;
        }
        NotifySearchEnginesJob::dispatch($site->id)->onQueue('default');
    }
}
