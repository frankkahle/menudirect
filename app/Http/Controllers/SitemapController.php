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
        $xml = Cache::remember('sitemap.index.xml', 3600, function () {
            $now = now()->toAtomString();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            $xml .= "  <sitemap>\n    <loc>https://menudirect.ca/sitemap-marketing.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
            $xml .= "  <sitemap>\n    <loc>https://menudirect.ca/sitemap-restaurants.xml</loc>\n    <lastmod>{$now}</lastmod>\n  </sitemap>\n";
            $xml .= '</sitemapindex>' . "\n";
            return $xml;
        });
        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
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
        $xml = Cache::remember('sitemap.restaurants.xml', 600, function () {
            $now = now()->toAtomString();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            $sites = RestaurantSite::query()
                ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
                ->whereNull('archived_at')
                ->with('customDomains')
                ->get();

            // A sitemap hosted at menudirect.ca may only list menudirect.ca URLs (sitemaps.org spec).
            // Custom-domain restaurants still get indexed: the rendered page emits
            // <link rel="canonical" href="{custom_domain}"> so Google indexes the custom-domain URL
            // even though it discovered the page via the menudirect.ca subdomain.
            foreach ($sites as $site) {
                $subUrl = htmlspecialchars("https://{$site->slug}.menudirect.ca", ENT_XML1);
                $xml .= "  <url>\n    <loc>{$subUrl}</loc>\n    <lastmod>{$now}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
            }
            $xml .= '</urlset>' . "\n";
            return $xml;
        });
        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * Per-restaurant sitemap served from the restaurant's own subdomain.
     * https://{slug}.menudirect.ca/sitemap.xml
     */
    public function bySlug(string $slug): Response
    {
        // Don't cache Eloquent models (they don't serialize cleanly to Redis/file cache).
        // Cache only the rendered XML string.
        $xml = Cache::remember("sitemap.site.{$slug}.xml", 600, function () use ($slug) {
            $site = RestaurantSite::where('slug', $slug)
                ->whereIn('status', [RestaurantSite::STATUS_ACTIVE, RestaurantSite::STATUS_DEMO])
                ->first();
            if (!$site) {
                return null;
            }
            $now = now()->toAtomString();
            $base = rtrim($site->getPublicUrl(), '/');
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            $xml .= "  <url>\n    <loc>{$base}/</loc>\n    <lastmod>{$now}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>1.0</priority>\n  </url>\n";
            $xml .= '</urlset>' . "\n";
            return $xml;
        });

        if ($xml === null) {
            abort(404);
        }

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
