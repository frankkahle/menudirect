@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $heroImg = $site['cover_photo'] ?? $site['hero_image'] ?? null;
    $gold = $site['colors']['primary'] ?? '#c9a96e';
@endphp

{{-- Noir luxury template — pure black, gold accents, centered minimalism --}}
<style>
    body { background-color: #000 !important; color: #d4d4d4; }
    .noir-serif { font-family: 'Cormorant Garamond', 'Playfair Display', Georgia, serif; }
    .noir-sans { font-family: 'Inter', 'Helvetica Neue', sans-serif; }
    .noir-gold { color: {{ $gold }}; }
    .noir-divider {
        display: flex; align-items: center; justify-content: center; gap: 1rem;
        color: {{ $gold }}; font-size: 0.9rem;
    }
    .noir-divider::before, .noir-divider::after {
        content: ''; height: 1px; flex: 1; max-width: 80px;
        background: linear-gradient(to right, transparent, {{ $gold }}80, transparent);
    }
    .noir-section-title {
        font-family: 'Cormorant Garamond', Georgia, serif;
        letter-spacing: 0.15em; text-transform: uppercase;
        font-size: 1.5rem; font-weight: 500;
    }
    .noir-btn {
        display: inline-block; padding: 0.85rem 2.5rem;
        border: 1px solid {{ $gold }}; color: {{ $gold }};
        font-size: 0.85rem; letter-spacing: 0.2em; text-transform: uppercase;
        font-weight: 500; transition: all 0.3s;
        background: transparent;
    }
    .noir-btn:hover { background: {{ $gold }}; color: #000; }
    .noir-btn-solid {
        background: {{ $gold }}; color: #000;
    }
    .noir-btn-solid:hover { background: transparent; color: {{ $gold }}; }
    .noir-eyebrow {
        font-size: 0.75rem; letter-spacing: 0.3em; text-transform: uppercase;
        color: {{ $gold }}; font-weight: 500;
    }
    .noir-link { color: #d4d4d4; transition: color 0.2s; }
    .noir-link:hover { color: {{ $gold }}; }
    .noir-card { background: #0a0a0a; border: 1px solid #1f1f1f; }
</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

@if($orderingEnabled)
<div x-data="cart({
    apiBaseUrl: '{{ config('services.portal.url', 'https://portal.sos-tech.ca') }}',
    restaurantSlug: '{{ $site['slug'] }}',
    taxRate: {{ floatval($orderingConfig['tax_rate'] ?? 0.15) }},
    minimumOrder: {{ floatval($orderingConfig['minimum_order'] ?? 0) }},
    deliveryFee: {{ floatval($orderingConfig['delivery_fee'] ?? 0) }},
    acceptsDelivery: {{ ($orderingConfig['accepts_delivery'] ?? false) ? 'true' : 'false' }},
    acceptsPickup: {{ ($orderingConfig['accepts_pickup'] ?? true) ? 'true' : 'false' }},
    estimatedPrepTime: {{ intval($orderingConfig['estimated_prep_time_minutes'] ?? 30) }},
    restaurantPhone: '{{ $site['phone'] ?? '' }}',
    isOpen: {{ ($orderingConfig['is_open'] ?? true) ? 'true' : 'false' }},
    todayHours: '{{ addslashes($orderingConfig['today_hours'] ?? '') }}',
    nextOpenLabel: '{{ addslashes($orderingConfig['next_open_label'] ?? '') }}',
    allHours: {!! json_encode($site['hours'] ?? (object)[]) !!},
    timezone: '{{ addslashes($site['timezone'] ?? 'America/Halifax') }}',
    forceClosed: {{ !empty($site['force_closed']) ? 'true' : 'false' }},
    closureMessage: '{{ addslashes($site['closure_message'] ?? '') }}',
    hasDeliveryZones: {{ !empty($orderingConfig['delivery_zones']) ? 'true' : 'false' }}
})">
@endif

@include('samples.partials.announcements-banner', ['announcements' => $site['announcements'] ?? []])

{{-- Top Navigation --}}
<header class="bg-black border-b border-white/5">
    <div class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
        <a href="#top" class="flex items-center gap-3">
            @if(!empty($site['logo']))
            <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-10 md:h-12 w-auto">
            @endif
            <div class="hidden sm:block">
                <div class="noir-serif text-xl text-white leading-none">{{ $site['name'] }}</div>
                @if(!empty($site['tagline']))
                <div class="text-[10px] noir-gold tracking-[0.25em] uppercase mt-1 whitespace-nowrap">{{ $site['tagline'] }}</div>
                @endif
            </div>
        </a>
        <nav class="hidden md:flex items-center gap-8 text-xs uppercase tracking-[0.25em] noir-sans font-medium">
            <a href="#top" class="noir-link border-b border-{{ $gold }}/0 pb-1 noir-gold">Home</a>
            <a href="#about" class="noir-link">About</a>
            <a href="#full-menu" class="noir-link">Menu</a>
            @if(!empty($site['settings']['gallery']))
            <a href="#gallery" class="noir-link">Gallery</a>
            @endif
            <a href="#contact" class="noir-link">Contact</a>
        </nav>
    </div>
</header>

{{-- Thin status strip — Open/Closed indicator below nav --}}
@php
    $isForceClosed = !empty($site['force_closed']);
    $closureMsg = $site['closure_message'] ?? '';
    $tz = new DateTimeZone($site['timezone'] ?? 'America/Halifax');
    $localNow = new DateTime('now', $tz);
    $todayName = $localNow->format('l');
    $todayHrs = $site['hours'][$todayName] ?? null;
    $isOpenNow = false;
    if (!$isForceClosed && $todayHrs && strtolower($todayHrs) !== 'closed' && preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)?\s*-\s*(\d{1,2}):(\d{2})\s*(AM|PM)?/i', $todayHrs, $tm)) {
        $to24 = function ($h, $m, $ap) {
            $h = (int) $h; $m = (int) $m;
            if ($ap) { $u = strtoupper($ap); if ($u === 'PM' && $h !== 12) $h += 12; if ($u === 'AM' && $h === 12) $h = 0; }
            return $h * 60 + $m;
        };
        $oM = $to24($tm[1], $tm[2], $tm[3] ?? null);
        $cM = $to24($tm[4], $tm[5], $tm[6] ?? null);
        if ($cM <= $oM) $cM += 1440;
        $nM = ((int) $localNow->format('G')) * 60 + ((int) $localNow->format('i'));
        $isOpenNow = ($nM >= $oM && $nM <= $cM);
    }
@endphp
@if($orderingEnabled)
<div x-show="isRestaurantOpen" class="border-b" style="border-color: {{ $gold }}30; background: #050505;">
    <div class="max-w-7xl mx-auto px-6 py-2 text-center text-[11px] tracking-[0.25em] uppercase noir-gold">
        <span class="inline-block w-1.5 h-1.5 rounded-full mr-2 align-middle animate-pulse" style="background: {{ $gold }};"></span>
        Open Now <span x-show="todayHours" class="opacity-70 normal-case tracking-normal">&middot; Today <span x-text="todayHours"></span></span>
    </div>
</div>
<div x-show="!isRestaurantOpen" x-cloak class="border-b border-red-900/30 bg-black">
    <div class="max-w-7xl mx-auto px-6 py-2 text-center text-[11px] tracking-[0.25em] uppercase text-red-400">
        <span class="inline-block w-1.5 h-1.5 rounded-full mr-2 align-middle bg-red-500"></span>
        Currently Closed
        <template x-if="forceClosed && closureMessage">
            <span class="ml-2 normal-case tracking-normal text-gray-500" x-text="closureMessage"></span>
        </template>
        <template x-if="!forceClosed">
            <span x-show="todayHours" class="ml-2 normal-case tracking-normal text-gray-500">Today: <span x-text="todayHours"></span></span>
        </template>
    </div>
</div>
@else
{{-- Static PHP version when ordering is disabled --}}
<div class="border-b {{ $isOpenNow ? '' : 'border-red-900/30' }}" style="background: #050505; {{ $isOpenNow ? 'border-color: ' . $gold . '30;' : '' }}">
    <div class="max-w-7xl mx-auto px-6 py-2 text-center text-[11px] tracking-[0.25em] uppercase {{ $isOpenNow ? 'noir-gold' : 'text-red-400' }}">
        <span class="inline-block w-1.5 h-1.5 rounded-full mr-2 align-middle {{ $isOpenNow ? 'animate-pulse' : 'bg-red-500' }}" @if($isOpenNow) style="background: {{ $gold }};" @endif></span>
        {{ $isOpenNow ? 'Open Now' : 'Currently Closed' }}
        @if($isForceClosed && $closureMsg)
            <span class="ml-2 normal-case tracking-normal text-gray-500">{{ $closureMsg }}</span>
        @elseif($todayHrs)
            <span class="ml-2 normal-case tracking-normal text-gray-500">{{ $isOpenNow ? 'Today' : 'Today:' }} {{ $todayHrs }}</span>
        @endif
    </div>
</div>
@endif

{{-- HERO — centered, minimal, logo-focused --}}
<section id="top" class="relative">
    @if($heroImg)
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImg }}');"></div>
    <div class="absolute inset-0 bg-black/85"></div>
    @endif
    <div class="relative max-w-3xl mx-auto px-6 py-24 md:py-32 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-32 md:h-44 w-auto mx-auto mb-8">
        @endif

        <h1 class="noir-serif text-5xl md:text-7xl noir-gold mb-2 leading-tight tracking-wide">
            @php
                // If name has multiple words and there's an "on" or location indicator, split for two-line styling
                $name = $site['name'];
                $parts = preg_split('/\s+(on|at|of|the)\s+/i', $name, 2, PREG_SPLIT_DELIM_CAPTURE);
            @endphp
            @if(count($parts) === 3)
                <span class="block">{{ $parts[0] }}</span>
                <span class="block text-3xl md:text-4xl tracking-[0.3em] uppercase mt-2">{{ $parts[1] }} {{ $parts[2] }}</span>
            @else
                {{ $name }}
            @endif
        </h1>

        <div class="noir-divider my-6">
            <span style="color: {{ $gold }};">&#10070;</span>
        </div>

        @if(!empty($site['tagline']))
        <p class="noir-sans text-sm md:text-base tracking-[0.3em] uppercase text-gray-400 mb-10">{{ $site['tagline'] }}</p>
        @endif

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            @if($orderingEnabled)
            <a href="#full-menu" class="noir-btn">Order Online</a>
            @else
            <a href="#full-menu" class="noir-btn">Explore Our Menu</a>
            @endif
            @if(!empty($site['phone']))
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="noir-btn" style="border-color: rgba(255,255,255,0.2); color: #d4d4d4;">{{ $site['phone'] }}</a>
            @endif
        </div>
    </div>
</section>

{{-- About Section --}}
<section id="about" class="py-20 md:py-28 border-t border-white/5 bg-black">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="noir-serif text-3xl md:text-4xl noir-gold mb-4">About {{ $site['name'] }}</h2>
        <div class="noir-divider mb-8"><span style="color: {{ $gold }};">&#10070;</span></div>
        @php $aboutText = $site['settings']['about'] ?? $site['about'] ?? $site['tagline'] ?? null; @endphp
        @if($aboutText)
        <p class="noir-sans text-base md:text-lg leading-relaxed mb-8" style="color: #b8b3a8;">{{ $aboutText }}</p>
        @endif

        @if(!empty($site['settings']['features']) && is_array($site['settings']['features']))
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-10 text-sm tracking-[0.15em] uppercase">
            @foreach($site['settings']['features'] as $feature)
            <li class="text-gray-300 pl-4 py-1 text-left" style="border-left: 1px solid {{ $gold }}40;">{{ $feature }}</li>
            @endforeach
        </ul>
        @endif
    </div>
</section>

{{-- Menu Section --}}
<section id="full-menu" class="py-20 md:py-28 border-t border-white/5 bg-[#050505]">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 max-w-2xl mx-auto">
            <h2 class="noir-serif text-3xl md:text-4xl noir-gold mb-4">Our Menu</h2>
            <div class="noir-divider mb-5"><span style="color: {{ $gold }};">&#10070;</span></div>
            <p class="noir-sans text-sm md:text-base leading-relaxed" style="color: #b8b3a8;">
                {{ $site['settings']['menu_intro'] ?? 'Thoughtfully crafted. Beautifully presented. Explore a menu that celebrates quality, creativity, and timeless flavours.' }}
            </p>
        </div>

        @if(!empty($site['menu_categories']))
            @foreach($site['menu_categories'] as $catKey => $category)
            <div class="mb-16 last:mb-0">
                <h3 class="noir-section-title noir-gold text-center mb-2">{{ $category['name'] }}</h3>
                @if(!empty($category['description']))
                <p class="text-center text-sm text-gray-500 mb-8 italic">{{ $category['description'] }}</p>
                @else
                <div class="mb-8"></div>
                @endif

                <div class="grid md:grid-cols-2 gap-x-12 gap-y-6">
                    @foreach($category['items'] ?? [] as $item)
                    <div class="border-b border-white/5 pb-5 flex gap-4 {{ $orderingEnabled ? 'cursor-pointer hover:bg-white/[0.02] -mx-3 px-3 rounded transition' : '' }}"
                         @if($orderingEnabled)
                         @click="addItem({ id: '{{ $item['id'] ?? '' }}', name: '{{ addslashes($item['name']) }}', price: {{ floatval(preg_replace('/[^0-9.]/', '', $item['price'] ?? 0)) }} })"
                         @endif>
                        @if(!empty($item['image']))
                        <div class="flex-shrink-0">
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" loading="lazy"
                                 class="w-20 h-20 object-cover border" style="border-color: {{ $gold }}30;">
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-baseline mb-1.5 gap-3">
                                <h4 class="noir-serif text-white text-xl">{{ $item['name'] }}</h4>
                                <span class="noir-gold text-base whitespace-nowrap font-medium">{{ $item['price'] }}</span>
                            </div>
                            @if(!empty($item['description']))
                            <p class="text-sm text-gray-400 italic leading-relaxed">{{ $item['description'] }}</p>
                            @endif
                            @if(!empty($item['dietary_labels']))
                            <div class="flex gap-2 mt-2 flex-wrap">
                                @foreach($item['dietary_labels'] as $label)
                                <span class="text-[10px] tracking-widest uppercase border px-2 py-0.5" style="border-color: {{ $gold }}40; color: {{ $gold }};">{{ $label }}</span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        @endif
    </div>
</section>

{{-- Gallery --}}
@php $galleryImages = $site['gallery'] ?? $site['settings']['gallery'] ?? []; @endphp
@if(!empty($galleryImages))
<section id="gallery" class="py-20 md:py-28 border-t border-white/5">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="noir-eyebrow mb-4">Gallery</p>
            <div class="noir-divider"><span style="color: {{ $gold }};">&#10070;</span></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-1">
            @foreach($galleryImages as $image)
            @php
                $imgUrl = is_array($image) ? ($image['url'] ?? '') : $image;
                $imgCaption = is_array($image) ? ($image['caption'] ?? null) : null;
            @endphp
            @if($imgUrl)
            <figure class="overflow-hidden">
                <div class="aspect-square overflow-hidden">
                    <img src="{{ $imgUrl }}" alt="{{ $imgCaption ?: '' }}" class="w-full h-full object-cover hover:scale-105 transition duration-700" loading="lazy">
                </div>
                @if($imgCaption)
                <figcaption class="px-3 py-2 text-xs tracking-[0.2em] uppercase text-white/50 noir-eyebrow text-center">{{ $imgCaption }}</figcaption>
                @endif
            </figure>
            @endif
            @endforeach
        </div>
    </div>
</section>
@endif

@include('samples.partials.reservations-section')

{{-- Hours & Contact --}}
<section id="contact" class="py-20 md:py-28 border-t border-white/5 bg-[#050505]">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-12">
            <p class="noir-eyebrow mb-4">Visit Us</p>
            <div class="noir-divider"><span style="color: {{ $gold }};">&#10070;</span></div>
        </div>
        <div class="grid md:grid-cols-3 gap-12 text-center">
            <div>
                <h3 class="noir-section-title noir-gold mb-4">Address</h3>
                @if(!empty($site['address']['street']))
                @php
                    $fullAddress = trim(($site['address']['street'] ?? '')
                        . (!empty($site['address']['city']) ? ', ' . $site['address']['city'] : '')
                        . (!empty($site['address']['province']) ? ', ' . $site['address']['province'] : '')
                        . (!empty($site['address']['postal']) ? ' ' . $site['address']['postal'] : ''));
                    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($fullAddress);
                @endphp
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="noir-link inline-block">
                    <p class="text-gray-300 leading-relaxed hover:text-white transition">
                        {{ $site['address']['street'] }}<br>
                        @if(!empty($site['address']['city'])){{ $site['address']['city'] }}@endif
                        @if(!empty($site['address']['province'])), {{ $site['address']['province'] }}@endif
                    </p>
                    <p class="text-xs noir-gold tracking-wider mt-2">Get directions →</p>
                </a>
                @endif
            </div>
            <div>
                <h3 class="noir-section-title noir-gold mb-4">Hours</h3>
                <div class="text-gray-300 text-sm space-y-1">
                    @php
                        // Always display in Monday-Sunday order regardless of how hours were saved
                        $weekOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        $hoursMap = $site['hours'] ?? [];
                        $orderedHours = [];
                        foreach ($weekOrder as $day) {
                            if (isset($hoursMap[$day])) {
                                $orderedHours[$day] = $hoursMap[$day];
                            }
                        }
                        $todayName = (new DateTime('now', new DateTimeZone($site['timezone'] ?? 'America/Halifax')))->format('l');
                    @endphp
                    @foreach($orderedHours as $day => $h)
                    <div class="flex justify-between max-w-[220px] mx-auto py-0.5 {{ $day === $todayName ? 'noir-gold font-medium' : '' }}">
                        <span class="tracking-wider uppercase text-xs">{{ substr($day, 0, 3) }}</span>
                        <span>{{ is_array($h) ? ($h['open'].'-'.$h['close']) : $h }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div>
                <h3 class="noir-section-title noir-gold mb-4">Connect</h3>
                @if(!empty($site['phone']))
                <p class="mb-2"><a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="noir-link">{{ $site['phone'] }}</a></p>
                @endif
                @if(!empty($site['email']))
                <p class="mb-4"><a href="mailto:{{ $site['email'] }}" class="noir-link">{{ $site['email'] }}</a></p>
                @endif
                @if(!empty($site['settings']['social_links']))
                <div class="flex justify-center gap-5 mt-5">
                    @if(!empty($site['settings']['social_links']['facebook']))
                    <a href="{{ $site['settings']['social_links']['facebook'] }}" target="_blank" rel="noopener" class="noir-link" aria-label="Facebook" title="Facebook">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>
                    </a>
                    @endif
                    @if(!empty($site['settings']['social_links']['instagram']))
                    <a href="{{ $site['settings']['social_links']['instagram'] }}" target="_blank" rel="noopener" class="noir-link" aria-label="Instagram" title="Instagram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    @endif
                    @if(!empty($site['settings']['social_links']['tiktok']))
                    <a href="{{ $site['settings']['social_links']['tiktok'] }}" target="_blank" rel="noopener" class="noir-link" aria-label="TikTok" title="TikTok">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005.8 20.1a6.34 6.34 0 0010.86-4.43V8.45a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1.84-.88z"/></svg>
                    </a>
                    @endif
                    @if(!empty($site['settings']['social_links']['twitter']))
                    <a href="{{ $site['settings']['social_links']['twitter'] }}" target="_blank" rel="noopener" class="noir-link" aria-label="X (Twitter)" title="X (Twitter)">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    @endif
                    @if(!empty($site['settings']['social_links']['google']))
                    <a href="{{ $site['settings']['social_links']['google'] }}" target="_blank" rel="noopener" class="noir-link" aria-label="Google" title="Google">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

@include('samples.partials.sister-sites-section')

{{-- Bottom signature line --}}
<section class="py-8 border-t border-white/5 text-center">
    <div class="noir-divider max-w-md mx-auto px-6">
        <span style="color: {{ $gold }};">&#10070;</span>
    </div>
    <p class="noir-eyebrow mt-4 text-gray-600">{{ $site['name'] }}</p>
</section>

@include('samples.partials.cart-ui')
@include('samples.partials.cart-scripts')

@if($orderingEnabled)
</div>
@endif
@endsection
