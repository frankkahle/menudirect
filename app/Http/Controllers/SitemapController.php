<?php

namespace App\Http\Controllers;

use App\Models\RestaurantSite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SitemapController extends Controller
{
    /**
     * Sitemap index — points crawlers at the per-section sitemaps.
     * https://menudirect.ca/sitemap.xml
     */
    public function index(): Response
    {
        return Cache::remember('sitemap.index', 3600, function () {
            $now = now()->toAtomString();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            $xml .= "  <sitemap>\n    <loc>https://menudirect.ca/sitemap-marketing.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
            $xml .= "  <sitemap>\n    <loc>https://menudirect.ca/sitemap-restaurants.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
            $xml .= '</sitemapindex>' . "\n";
            return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
        });
    }

    /**
     * Marketing apex pages.
     * https://menudirect.ca/sitemap-marketing.xml
     */
    public function marketing(): Response
    {
        $now = now()->toAtomString();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ([
            ['/', '1.0', 'weekly'],
        ] as [$path, $priority, $changefreq]) {
            $xml .= "  <url>\n    <loc>https://menudirect.ca{$path}</loc>\n    <lastmod>{$now}</lastmod>\n    <changefreq>{$changefreq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
        }
        $xml .= '</urlset>' . "\n";
        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * One <url> per active restaurant subdomain + any registered custom domain.
     * https://menudirect.ca/sitemap-restaurants.xml
     */
    public function restaurants(): Response
    {
        return Cache::remember('sitemap.restaurants', 600, function () {
            $now = now()->toAtomString();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $sites = RestaurantSite::query()
                ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
                ->whereNull('archived_at')
                ->with('customDomains')
                ->get();

            foreach ($sites as $site) {
                $url = htmlspecialchars($site->getPublicUrl(), ENT_XML1);
                $xml .= "  <url>\n    <loc>{$url}</loc>\n    <lastmod>{$now}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";

                // Also list the subdomain URL even if custom_domain exists — Google can canonicalize
                if ($site->custom_domain || $site->customDomains->isNotEmpty()) {
                    $subUrl = htmlspecialchars("https://{$site->slug}.menudirect.ca", ENT_XML1);
                    $xml .= "  <url>\n    <loc>{$subUrl}</loc>\n    <lastmod>{$now}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.6</priority>\n  </url>\n";
                }
            }
            $xml .= '</urlset>' . "\n";
            return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
        });
    }

    /**
     * Per-restaurant sitemap served from the restaurant's own subdomain.
     * https://{slug}.menudirect.ca/sitemap.xml
     */
    public function bySlug(string $slug): Response
    {
        $site = Cache::remember("sitemap.site.{$slug}", 600, fn () => RestaurantSite::where('slug', $slug)
            ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
            ->first());

        if (!$site) {
            abort(404);
        }

        $now = now()->toAtomString();
        $base = rtrim($site->getPublicUrl(), '/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= "  <url>\n    <loc>{$base}/</loc>\n    <lastmod>{$now}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
        $xml .= '</urlset>' . "\n";
        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Tell IndexNow that URLs have changed.
     * Usage: POST /api/indexnow/submit with optional ?slug= or no params for full reindex.
     * Secret-protected via INDEXNOW_KEY in .env.
     */
    public function submitIndexNow(Request $request)
    {
        $key = config('services.indexnow.key');
        if (!$key) {
            return response()->json(['error' => 'INDEXNOW_KEY not configured'], 503);
        }

        $urls = ['https://menudirect.ca/'];
        $sites = RestaurantSite::query()
            ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
            ->whereNull('archived_at')
            ->get();
        foreach ($sites as $site) {
            $urls[] = $site->getPublicUrl();
        }

        $payload = [
            'host' => 'menudirect.ca',
            'key' => $key,
            'keyLocation' => "https://menudirect.ca/{$key}.txt",
            'urlList' => $urls,
        ];

        try {
            $resp = Http::timeout(15)->post('https://api.indexnow.org/IndexNow', $payload);
            return response()->json([
                'submitted' => count($urls),
                'indexnow_status' => $resp->status(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
