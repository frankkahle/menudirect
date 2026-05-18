<?php

namespace App\Jobs;

use App\Models\RestaurantSite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Pings IndexNow (covers Bing, Yandex, Naver, etc.) when a restaurant changes.
 * Google doesn't accept programmatic URL submission for general content — they
 * pull our sitemap on their own schedule. The 10-min sitemap cache TTL means
 * the next Google crawl will see the change naturally.
 */
class NotifySearchEnginesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $siteId) {}

    public function handle(): void
    {
        $site = RestaurantSite::find($this->siteId);
        if (!$site) {
            return;
        }

        $urls = $this->urlsForSite($site);

        // Coalesce: if we already pinged for this site in the last minute, skip.
        // Prevents spam during rapid edits (settings page save → 3 updates in 5s).
        $coalesceKey = "indexnow:lastping:site:{$this->siteId}";
        if (Cache::get($coalesceKey)) {
            return;
        }
        Cache::put($coalesceKey, true, 60);

        $this->pingIndexNow($urls);
        $this->pingBing($urls);
    }

    protected function urlsForSite(RestaurantSite $site): array
    {
        $urls = ["https://{$site->slug}.menudirect.ca/"];

        // If custom domain set, include it too (separate IndexNow submission per host)
        if ($site->custom_domain) {
            $urls[] = "https://{$site->custom_domain}/";
        }
        foreach ($site->customDomains->where('status', 'active') as $cd) {
            $urls[] = "https://{$cd->domain}/";
        }

        return array_unique($urls);
    }

    protected function pingIndexNow(array $urls): void
    {
        $key = config('services.indexnow.key');
        if (!$key) {
            return;
        }

        // IndexNow requires per-host submissions (host field must match URLs).
        // Group URLs by their host and submit one batch per host.
        $byHost = [];
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            $byHost[$host][] = $url;
        }

        foreach ($byHost as $host => $hostUrls) {
            $keyLocation = "https://menudirect.ca/{$key}.txt";
            try {
                $resp = Http::timeout(10)->post('https://api.indexnow.org/IndexNow', [
                    'host' => $host,
                    'key' => $key,
                    'keyLocation' => $keyLocation,
                    'urlList' => $hostUrls,
                ]);
                Log::info('IndexNow ping', [
                    'host' => $host,
                    'urls' => count($hostUrls),
                    'status' => $resp->status(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('IndexNow ping failed', ['host' => $host, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Bing's separate URL Submission API — same key + host model as IndexNow,
     * but Bing also accepts the IndexNow payload directly. Calling /IndexNow
     * already covers Bing, so this is a no-op stub kept for future expansion
     * (e.g. if we add a Bing-specific webmaster API call later).
     */
    protected function pingBing(array $urls): void
    {
        // IndexNow already notifies Bing — this is here as a placeholder for
        // future Bing-specific work (e.g. their /URLs daily quota API).
    }
}
