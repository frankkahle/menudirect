@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];

    // Build Restaurant structured data in PHP (clean JSON, no Blade in JSON)
    $restaurantSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Restaurant',
        'name' => $site['name'],
        'description' => $site['seo_description'] ?? ($site['tagline'] ?? ''),
        'url' => url()->current(),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $site['address']['street'] ?? '',
            'addressLocality' => $site['address']['city'] ?? '',
            'addressRegion' => $site['address']['province'] ?? 'NB',
            'postalCode' => $site['address']['postal'] ?? '',
            'addressCountry' => 'CA',
        ],
        'servesCuisine' => $site['cuisine_type'] ?? 'Restaurant',
        'priceRange' => $site['price_range'] ?? '$$',
    ];

    if (!empty($site['logo'])) {
        $restaurantSchema['logo'] = $site['logo'];
        $restaurantSchema['image'] = $site['logo'];
    } elseif (!empty($site['hero_image'])) {
        $restaurantSchema['image'] = $site['hero_image'];
    }
    if (!empty($site['phone'])) {
        $restaurantSchema['telephone'] = $site['phone'];
    }
    if (!empty($site['email'])) {
        $restaurantSchema['email'] = $site['email'];
    }

    // Opening hours — parse "12:00 PM - 8:00 PM" strings into Schema.org "Mo 12:00-20:00" format
    $dayMap = ['monday' => 'Mo', 'tuesday' => 'Tu', 'wednesday' => 'We', 'thursday' => 'Th', 'friday' => 'Fr', 'saturday' => 'Sa', 'sunday' => 'Su'];
    $openingHours = [];
    foreach ($site['hours'] ?? [] as $day => $hours) {
        $dayAbbr = $dayMap[strtolower($day)] ?? '';
        if (!$dayAbbr) continue;

        if (is_array($hours) && !empty($hours['open']) && !empty($hours['close'])) {
            // Structured format: {open: "12:00", close: "20:00"}
            $openingHours[] = $dayAbbr . ' ' . $hours['open'] . '-' . $hours['close'];
        } elseif (is_string($hours) && stripos($hours, 'closed') === false && preg_match('/(\d{1,2}:\d{2}\s*[AP]M)\s*-\s*(\d{1,2}:\d{2}\s*[AP]M)/i', $hours, $m)) {
            // String format: "12:00 PM - 8:00 PM" → convert to 24h
            $open24 = date('H:i', strtotime(trim($m[1])));
            $close24 = date('H:i', strtotime(trim($m[2])));
            $openingHours[] = $dayAbbr . ' ' . $open24 . '-' . $close24;
        }
    }
    if (!empty($openingHours)) {
        $restaurantSchema['openingHours'] = $openingHours;
    }

    // Menu structured data — always included for SEO, regardless of online ordering
    $menuData = $site['menu_categories'] ?? $site['menu'] ?? [];
    if (!empty($menuData)) {
        $menuSections = [];
        foreach ($menuData as $category) {
            $menuItems = [];
            foreach ($category['items'] ?? [] as $item) {
                $price = $item['price'] ?? 0;
                // Handle formatted price strings like "$22.00" or raw numeric
                if (is_string($price)) {
                    $price = (float) preg_replace('/[^0-9.]/', '', $price);
                }
                $menuItems[] = [
                    '@type' => 'MenuItem',
                    'name' => $item['name'],
                    'description' => $item['description'] ?? '',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => number_format((float) $price, 2),
                        'priceCurrency' => 'CAD',
                    ],
                ];
            }
            if (!empty($menuItems)) {
                $menuSections[] = [
                    '@type' => 'MenuSection',
                    'name' => $category['name'],
                    'description' => $category['description'] ?? '',
                    'hasMenuItem' => $menuItems,
                ];
            }
        }
        if (!empty($menuSections)) {
            $restaurantSchema['hasMenu'] = [
                '@type' => 'Menu',
                'hasMenuSection' => $menuSections,
            ];
        }
    }

    // LocalBusiness schema
    $localBusinessSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $site['name'],
        '@id' => url()->current(),
        'url' => url()->current(),
        'address' => $restaurantSchema['address'],
    ];
    if (!empty($site['logo'])) {
        $localBusinessSchema['image'] = $site['logo'];
    }
    if (!empty($site['phone'])) {
        $localBusinessSchema['telephone'] = $site['phone'];
    }
    if (!empty($site['address']['lat']) && !empty($site['address']['lng'])) {
        $localBusinessSchema['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => $site['address']['lat'],
            'longitude' => $site['address']['lng'],
        ];
    }

    // AggregateRating + individual Reviews from Google Reviews data
    $googleRating = $site['settings']['google_rating'] ?? null;
    $googleReviewCount = $site['settings']['google_review_count'] ?? null;
    $googleReviews = $site['settings']['google_reviews'] ?? $site['google_reviews'] ?? [];

    if ($googleRating) {
        $restaurantSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $googleRating,
            'reviewCount' => $googleReviewCount ?? count($googleReviews),
            'bestRating' => '5',
            'worstRating' => '1',
        ];
    }

    if (!empty($googleReviews)) {
        $restaurantSchema['review'] = [];
        foreach ($googleReviews as $review) {
            $restaurantSchema['review'][] = [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $review['author'] ?? 'Anonymous',
                ],
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $review['rating'] ?? 5,
                    'bestRating' => '5',
                ],
                'reviewBody' => $review['text'] ?? '',
            ];
        }
    }

    // Breadcrumb schema
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Restaurants', 'item' => 'https://sos-tech.ca/restaurant-websites'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $site['name'], 'item' => url()->current()],
        ],
    ];

    // SoftwareApplication schema - identifies this as a MenuDirect-powered site
    $softwareSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'MenuDirect Restaurant Website',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web-based',
        'description' => 'Restaurant website powered by MenuDirect from SOS Technical Services - Canadian restaurant website builder with online ordering',
        'url' => 'https://sos-tech.ca/restaurant-websites',
        'provider' => [
            '@type' => 'Organization',
            'name' => 'SOS Technical Services',
            'url' => 'https://sos-tech.ca',
        ],
    ];
@endphp

@push('scripts')
<script type="application/ld+json">{!! json_encode($restaurantSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
<script type="application/ld+json">{!! json_encode($localBusinessSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
<script type="application/ld+json">{!! json_encode($softwareSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
@endpush