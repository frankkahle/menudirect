<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO Meta Tags --}}
    <title>{{ $site['seo_title'] ?? ($site['name'] . (!empty($site['tagline']) ? ' - ' . $site['tagline'] : '') . ' | ' . ($site['address']['city'] ?? 'Restaurant')) }}</title>
    <meta name="description" content="{{ $site['seo_description'] ?? ($site['name'] . ' - ' . ($site['tagline'] ?? 'Restaurant') . ' in ' . ($site['address']['city'] ?? 'Canada') . '. View our menu, hours, and order online.') }}">
    @if(!empty($site['seo_keywords']))
    <meta name="keywords" content="{{ $site['seo_keywords'] }}">
    @endif

    {{-- Allow indexing for all active and demo restaurant sites - demos showcase the SaaS product --}}
    <meta name="robots" content="index, follow">

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="restaurant">
    <meta property="og:title" content="{{ $site['seo_title'] ?? $site['name'] }}">
    <meta property="og:description" content="{{ $site['seo_description'] ?? ($site['tagline'] ?? 'View our menu and order online') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if(!empty($site['og_image']))
    <meta property="og:image" content="{{ $site['og_image'] }}">
    @elseif(!empty($site['logo']))
    <meta property="og:image" content="{{ $site['logo'] }}">
    @elseif(!empty($site['hero_image']))
    <meta property="og:image" content="{{ $site['hero_image'] }}">
    @endif
    <meta property="og:locale" content="en_CA">
    <meta property="og:site_name" content="{{ $site['name'] }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $site['seo_title'] ?? $site['name'] }}">
    <meta name="twitter:description" content="{{ $site['seo_description'] ?? ($site['tagline'] ?? 'View our menu and order online') }}">

    {{-- Geographic --}}
    @if(!empty($site['address']['city']))
    <meta name="geo.placename" content="{{ $site['address']['city'] }}, {{ $site['address']['province'] ?? 'Canada' }}">
    @endif

    {{-- AI Discovery Profile --}}
    <link rel="llms-txt" href="/llms.txt">

    <script src="https://cdn.tailwindcss.com"></script>
    @stack('head-scripts')
    <script defer src="https://unpkg.com/alpinejs@3.15.11/dist/cdn.min.js"
            integrity="sha384-WPtu0YHhJ3arcykfnv1JgUffWDSKRnqnDeTpJUbOc2os2moEmLkIdaeR0trPN4be"
            crossorigin="anonymous"></script>
    {{-- Mapbox Search (for delivery address autocomplete) --}}
    @if(config('services.mapbox.access_token'))
    <script id="search-js" defer src="https://api.mapbox.com/search-js/v1.0.0-beta.22/web.js"></script>
    <script>
        window.mapboxAccessToken = '{{ config('services.mapbox.access_token') }}';
        window.mapboxEnabled = true;
    </script>
    @else
    <script>window.mapboxEnabled = false;</script>
    @endif
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand': '{{ $site['colors']['primary'] ?? '#2563eb' }}',
                        'brand-secondary': '{{ $site['colors']['secondary'] ?? '#7c3aed' }}',
                        'brand-accent': '{{ $site['colors']['accent'] ?? '#f59e0b' }}',
                    }
                }
            }
        }
    </script>
    <style>
        .brand-gradient { background: linear-gradient(135deg, {{ $site['colors']['primary'] ?? '#2563eb' }} 0%, {{ $site['colors']['secondary'] ?? '#7c3aed' }} 100%); }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    {{-- Sales Banner — only show on demo sites, not active/paying clients --}}
    @if(!empty($salesBanner['enabled']) && ($site['status'] ?? 'demo') === 'demo')
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white py-3 px-4 text-center sticky top-0 z-50">
        <div class="max-w-4xl mx-auto">
            <p class="text-sm md:text-base font-medium mb-2">
                {{ $salesBanner['message'] ?? '🚀 This could be YOUR restaurant\'s website — rank higher on Google & own your customers' }}
            </p>
            <div class="flex flex-wrap justify-center items-center gap-2 md:gap-4">
                <a href="{{ $salesBanner['cta_url'] ?? '#' }}" class="inline-block bg-white text-emerald-700 px-4 py-1.5 rounded-full font-semibold text-sm hover:bg-gray-100 transition shadow">
                    {{ $salesBanner['cta_text'] ?? 'Get Started — $15/mo' }}
                </a>
                <a href="{{ $salesBanner['preview_url'] ?? 'https://menudirect.ca/#try-demo' }}" class="inline-block bg-emerald-700 text-white px-4 py-1.5 rounded-full font-semibold text-sm hover:bg-emerald-800 transition border border-emerald-500">
                    Get a Free Preview
                </a>
                @if(!empty($salesBanner['phone']))
                <span class="text-sm hidden md:inline">or call <strong>{{ $salesBanner['phone'] }}</strong></span>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Force-closed banner (seasonal / emergency closure) — shows regardless of ordering status --}}
    @if(!empty($site['force_closed']))
    <div class="bg-red-600 text-white py-3 text-center font-semibold text-sm md:text-base uppercase tracking-wide">
        <div class="max-w-6xl mx-auto px-4 flex flex-wrap items-center justify-center gap-2">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-white animate-pulse"></span>
            <span>Currently Closed</span>
            @if(!empty($site['closure_message']))
            <span class="hidden sm:inline opacity-75">·</span>
            <span class="font-medium normal-case opacity-95">{{ $site['closure_message'] }}</span>
            @endif
        </div>
    </div>
    @endif

    @yield('content')

    {{-- Demo Site Banner - Shows on demo sites (except Demo Bistro itself) --}}
    @if(($site['status'] ?? '') === 'demo' && ($site['slug'] ?? '') !== 'demo-bistro')
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-4 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <p class="text-sm md:text-base mb-3">
                <strong>Want to see all the features?</strong> Check out our fully-featured demo with online ordering, dietary badges, and more.
            </p>
            <a href="/s/demo-bistro" class="inline-flex items-center bg-white text-blue-700 px-5 py-2 rounded-full font-semibold text-sm hover:bg-gray-100 transition shadow">
                <span>View Demo Bistro</span>
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
    @endif

    {{-- Footer with SOS Tech branding + cross-linking for SEO authority --}}
    <footer class="bg-gray-900 text-gray-400 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center">
            {{-- Made in NB Badge --}}
            <div class="inline-flex items-center gap-2 bg-gray-800 text-gray-300 px-4 py-2 rounded-full text-sm mb-4">
                <span class="text-red-500">🍁</span>
                <span>Proudly built & supported in <strong class="text-white">New Brunswick, Canada</strong></span>
            </div>

            @if(($site['status'] ?? '') !== 'active')
            {{-- Marketing footer — only on demo sites --}}
            <p class="text-sm mb-2">
                Powered by <a href="https://menudirect.ca" class="text-orange-400 hover:text-orange-300 font-semibold">MenuDirect</a>
                — Restaurant websites with built-in online ordering.
            </p>
            <p class="text-sm mb-4">
                Want a site like this for your restaurant?
                <a href="https://sos-tech.ca/restaurant-websites" class="text-emerald-400 hover:text-emerald-300">See plans from $15/mo</a>
                or call <strong class="text-white">{{ $salesBanner['phone'] ?? '(506) 910-5547' }}</strong>
            </p>

            {{-- Cross-links for trust web --}}
            <div class="flex flex-wrap justify-center gap-4 text-xs text-gray-500 mb-4">
                <a href="https://sos-tech.ca" class="hover:text-gray-300">SOS Tech</a>
                <span>|</span>
                <a href="https://sos-tech.ca/restaurant-websites" class="hover:text-gray-300">Restaurant Websites</a>
                <span>|</span>
                <a href="https://menudirect.ca" class="hover:text-gray-300">MenuDirect.ca</a>
                <span>|</span>
                <a href="https://sos-tech.ca/pricing" class="hover:text-gray-300">Web Hosting</a>
                <span>|</span>
                <a href="https://sos-tech.ca/helpdesk" class="hover:text-gray-300">SOSDesk Helpdesk</a>
                <span>|</span>
                <a href="https://sos-tech.ca/about" class="hover:text-gray-300">About Us</a>
            </div>

            <p class="text-xs text-gray-500">
                Local support from Hampton, NB — no overseas call centers. 45+ years IT experience.
            </p>
            @endif
            <p class="mt-3 text-xs text-gray-600">
                &copy; {{ date('Y') }} {{ $site['name'] }} |
                <a href="https://sos-tech.ca" class="hover:text-gray-400">Powered by SOS Technical Services</a>
            </p>
        </div>
    </footer>

@php
    // Build the restaurant's full address string and Google Maps URL once
    $addr = $site['address'] ?? null;
    $addrFull = '';
    if (is_array($addr)) {
        $addrFull = trim(($addr['street'] ?? '') . ', ' . ($addr['city'] ?? '') . ', ' . ($addr['province'] ?? '') . ' ' . ($addr['postal'] ?? ''));
    } elseif (is_string($addr)) {
        $addrFull = $addr;
    }
    $addrFull = trim($addrFull, ', ');
@endphp
@if($addrFull)
<script>
(function () {
    // Auto-link restaurant address text to Google Maps across any template
    var fullAddress = {!! json_encode($addrFull) !!};
    var streetOnly  = {!! json_encode(is_array($site['address'] ?? null) ? ($site['address']['street'] ?? '') : '') !!};
    var mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(fullAddress);

    function isInLink(el) { while (el) { if (el.tagName === 'A') return true; el = el.parentElement; } return false; }
    function normalize(s) { return (s || '').replace(/\s+/g, ' ').trim(); }

    function wrapInMapsLink(el) {
        // Safely wrap an element's existing children inside a new anchor (no innerHTML)
        if (el.dataset.mapsLinked === '1') return;
        var link = document.createElement('a');
        link.href = mapsUrl;
        link.target = '_blank';
        link.rel = 'noopener';
        link.title = 'Get directions';
        link.className = 'hover:underline';
        // Move existing children into the link
        while (el.firstChild) link.appendChild(el.firstChild);
        el.appendChild(link);
        el.dataset.mapsLinked = '1';
    }

    function linkifyAddresses() {
        var tags = ['p', 'span', 'div', 'address'];
        var candidates = document.querySelectorAll(tags.join(','));
        var matchTexts = [normalize(fullAddress)];
        if (streetOnly) matchTexts.push(normalize(streetOnly));

        candidates.forEach(function (el) {
            if (isInLink(el)) return;
            // Only wrap leaf-ish elements (avoid wrapping huge sections)
            if (el.children.length > 2) return;
            var txt = normalize(el.textContent);
            if (!txt) return;
            for (var i = 0; i < matchTexts.length; i++) {
                if (matchTexts[i] && (txt === matchTexts[i] || txt.indexOf(matchTexts[i]) !== -1)) {
                    wrapInMapsLink(el);
                    return;
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', linkifyAddresses);
    } else {
        linkifyAddresses();
    }
})();
</script>
@endif

@stack('scripts')
@if(($site['status'] ?? '') === 'demo')
{{-- Demo buttons — kitchen view + server tablet --}}
<div class="fixed top-4 right-4 z-50 flex gap-2">
    <a href="#" onclick="window.open('/demo-kitchen/{{ $site['slug'] }}/server', 'server', 'width=900,height=650,left=' + (screen.width - 940) + ',top=60,toolbar=no,menubar=no,location=no,status=no'); return false;"
       class="flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-amber-400 border border-amber-500/30 px-3 py-2 rounded-lg shadow-lg transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span class="hidden sm:inline">Server Tablet</span>
    </a>
    <a href="/demo-kitchen/{{ $site['slug'] }}" target="kitchen"
       class="flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-gray-400 border border-white/10 px-3 py-2 rounded-lg shadow-lg transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span class="hidden sm:inline">Kitchen</span>
    </a>
</div>
@endif
</body>
</html>
