@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    // These variables are needed by the template and partials
    // They're also defined in schema partial for JSON-LD, but @include scope doesn't carry into @section
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];

    // Pre-compute category list for the sticky nav
    $categoryList = [];
    if (!empty($site['menu_categories'])) {
        foreach ($site['menu_categories'] as $key => $cat) {
            $categoryList[$key] = $cat['name'] ?? ucfirst(str_replace('_', ' ', $key));
        }
    }
@endphp
{{-- Initialize Cart if ordering is enabled --}}
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

{{-- Announcements Banner --}}
@include('samples.partials.announcements-banner', ['announcements' => $site['announcements'] ?? []])

{{-- =================================================================== --}}
{{-- COMPACT HERO — logo, name, giant CTA. No long tagline area.          --}}
{{-- =================================================================== --}}
<section class="relative overflow-hidden py-12 md:py-16">
    {{-- Background: brand gradient with optional hero image overlay --}}
    @if(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $site['hero_image'] ?? $site['cover_photo'] }}');"></div>
    <div class="absolute inset-0" style="background: linear-gradient(135deg, {{ $site['colors']['primary'] ?? '#dc2626' }}ee 0%, {{ $site['colors']['secondary'] ?? '#f59e0b' }}dd 100%);"></div>
    @else
    <div class="absolute inset-0 brand-gradient"></div>
    @endif

    {{-- Diagonal accent stripe for energy --}}
    <div class="absolute -top-20 -right-20 w-96 h-96 rounded-full opacity-20"
         style="background: {{ $site['colors']['accent'] ?? '#fbbf24' }};"></div>
    <div class="absolute -bottom-32 -left-20 w-80 h-80 rounded-full opacity-20"
         style="background: {{ $site['colors']['accent'] ?? '#fbbf24' }};"></div>

    <div class="relative max-w-5xl mx-auto px-4 text-center z-10">
        <div class="flex flex-col md:flex-row items-center justify-center gap-6 md:gap-8">
            @if(!empty($site['logo']))
            <img src="{{ $site['logo'] }}"
                 alt="{{ $site['name'] }}"
                 class="h-24 md:h-32 rounded-2xl shadow-2xl bg-white p-2 flex-shrink-0">
            @endif
            <div class="text-center md:text-left">
                <h1 class="text-4xl md:text-6xl font-black text-white tracking-tight drop-shadow-lg uppercase leading-none">
                    {{ $site['name'] }}
                </h1>
                @if(!empty($site['tagline']))
                <p class="mt-2 text-lg md:text-xl text-white/90 font-medium drop-shadow">
                    {{ $site['tagline'] }}
                </p>
                @endif
            </div>
        </div>

        {{-- GIANT Order Now button --}}
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
            @if($orderingEnabled)
            <a href="#full-menu"
               class="group inline-flex items-center justify-center gap-3 bg-white text-gray-900 px-10 md:px-14 py-5 md:py-6 rounded-2xl text-2xl md:text-3xl font-black uppercase tracking-wide shadow-2xl hover:scale-105 hover:shadow-white/30 transition-transform">
                <svg class="w-8 h-8 md:w-10 md:h-10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Order Now
            </a>
            @else
            <a href="#full-menu"
               class="group inline-flex items-center justify-center gap-3 bg-white text-gray-900 px-10 md:px-14 py-5 md:py-6 rounded-2xl text-2xl md:text-3xl font-black uppercase tracking-wide shadow-2xl hover:scale-105 hover:shadow-white/30 transition-transform">
                View Menu
            </a>
            @if(!empty($site['phone']))
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}"
               class="inline-flex items-center justify-center gap-2 border-4 border-white text-white px-8 py-5 rounded-2xl text-xl font-bold uppercase tracking-wide backdrop-blur-sm bg-white/10 hover:bg-white hover:text-gray-900 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Call
            </a>
            @endif
            @endif
        </div>
    </div>
</section>

{{-- =================================================================== --}}
{{-- OPEN / CLOSED STATUS BAR — permanently visible                       --}}
{{-- =================================================================== --}}
@if($orderingEnabled)
<div x-show="isRestaurantOpen" class="bg-green-600 text-white py-3 font-bold text-center uppercase tracking-wide text-sm md:text-base">
    <div class="max-w-6xl mx-auto px-4 flex flex-wrap items-center justify-center gap-2">
        <span class="inline-block w-3 h-3 rounded-full bg-white animate-pulse"></span>
        <span>Open Now</span>
        <span class="hidden sm:inline opacity-75">·</span>
        <span class="hidden sm:inline font-medium normal-case opacity-90">Accepting Orders</span>
        @if($orderingConfig['accepts_pickup'] ?? true)
        <span class="hidden md:inline opacity-75">·</span>
        <span class="hidden md:inline font-medium normal-case opacity-90">Pickup</span>
        @endif
        @if($orderingConfig['accepts_delivery'] ?? false)
        <span class="hidden md:inline opacity-75">·</span>
        <span class="hidden md:inline font-medium normal-case opacity-90">Delivery</span>
        @endif
    </div>
</div>
<div x-show="!isRestaurantOpen" x-cloak class="bg-red-600 text-white py-3 font-bold text-center uppercase tracking-wide text-sm md:text-base">
    <div class="max-w-6xl mx-auto px-4 flex flex-wrap items-center justify-center gap-2">
        <span class="inline-block w-3 h-3 rounded-full bg-white"></span>
        <span>Closed</span>
        <template x-if="forceClosed && closureMessage">
            <span class="hidden sm:inline font-medium normal-case opacity-90">· <span x-text="closureMessage"></span></span>
        </template>
        <template x-if="!forceClosed">
            <span class="contents">
                <span x-show="todayHours" class="hidden sm:inline opacity-75">·</span>
                <span x-show="todayHours" class="hidden sm:inline font-medium normal-case opacity-90">Today: <span x-text="todayHours"></span></span>
                <span x-show="nextOpenLabel" class="hidden md:inline opacity-75">·</span>
                <span x-show="nextOpenLabel" class="hidden md:inline font-medium normal-case opacity-90" x-text="nextOpenLabel"></span>
            </span>
        </template>
    </div>
</div>
@else
@php
    $forceClosed = !empty($site['force_closed']);
    $closureMessage = $site['closure_message'] ?? '';
    $restaurantTz = new DateTimeZone($site['timezone'] ?? 'America/Halifax');
    $localNow = new DateTime('now', $restaurantTz);
    $today = $localNow->format('l');
    $todayHoursStr = $site['hours'][$today] ?? null;
    $isOpenNow = false;
    if (!$forceClosed && $todayHoursStr && strtolower($todayHoursStr) !== 'closed') {
        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)?\s*-\s*(\d{1,2}):(\d{2})\s*(AM|PM)?/i', $todayHoursStr, $m)) {
            $to24 = function ($h, $min, $ap) {
                $h = (int) $h; $min = (int) $min;
                if ($ap) {
                    $up = strtoupper($ap);
                    if ($up === 'PM' && $h !== 12) $h += 12;
                    if ($up === 'AM' && $h === 12) $h = 0;
                }
                return $h * 60 + $min;
            };
            $openMin = $to24($m[1], $m[2], $m[3] ?? null);
            $closeMin = $to24($m[4], $m[5], $m[6] ?? null);
            if ($closeMin <= $openMin) $closeMin += 24 * 60;
            $nowMin = ((int) $localNow->format('G')) * 60 + ((int) $localNow->format('i'));
            $isOpenNow = ($nowMin >= $openMin && $nowMin <= $closeMin);
        }
    }
@endphp
<div class="{{ $isOpenNow ? 'bg-green-600' : 'bg-red-600' }} text-white py-3 font-bold text-center uppercase tracking-wide text-sm md:text-base">
    <div class="max-w-6xl mx-auto px-4 flex flex-wrap items-center justify-center gap-2">
        <span class="inline-block w-3 h-3 rounded-full bg-white {{ $isOpenNow ? 'animate-pulse' : '' }}"></span>
        <span>{{ $isOpenNow ? 'Open Now' : 'Closed' }}</span>
        @if($forceClosed && $closureMessage)
        <span class="hidden sm:inline opacity-75">·</span>
        <span class="hidden sm:inline font-medium normal-case opacity-90">{{ $closureMessage }}</span>
        @elseif($todayHoursStr)
        <span class="hidden sm:inline opacity-75">·</span>
        <span class="hidden sm:inline font-medium normal-case opacity-90">Today: {{ $todayHoursStr }}</span>
        @endif
    </div>
</div>
@endif

{{-- =================================================================== --}}
{{-- STICKY CATEGORY NAV — scroll-spy into menu sections                  --}}
{{-- =================================================================== --}}
@if(!empty($categoryList))
<nav id="category-nav"
     class="sticky top-0 z-30 bg-white shadow-md border-b-2"
     style="border-color: {{ $site['colors']['primary'] ?? '#dc2626' }};">
    <div class="max-w-6xl mx-auto px-2">
        <div class="flex items-center gap-2 overflow-x-auto py-3 no-scrollbar" style="scrollbar-width: none;">
            @foreach($categoryList as $slug => $label)
            <a href="#cat-{{ $slug }}"
               class="flex-shrink-0 px-4 py-2 rounded-full font-bold text-sm uppercase tracking-wide whitespace-nowrap transition-colors hover:text-white"
               style="background: {{ $site['colors']['primary'] ?? '#dc2626' }}15; color: {{ $site['colors']['primary'] ?? '#dc2626' }};"
               onmouseover="this.style.background='{{ $site['colors']['primary'] ?? '#dc2626' }}'; this.style.color='#fff';"
               onmouseout="this.style.background='{{ $site['colors']['primary'] ?? '#dc2626' }}15'; this.style.color='{{ $site['colors']['primary'] ?? '#dc2626' }}';">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>
</nav>
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    html { scroll-behavior: smooth; scroll-padding-top: 80px; }
</style>
@endif

{{-- =================================================================== --}}
{{-- MENU — single scrolling feed, horizontal row cards                   --}}
{{-- =================================================================== --}}
@if(!empty($site['menu_categories']))
<section id="full-menu" class="bg-gray-50 py-10">
    <div class="max-w-5xl mx-auto px-4">
        @foreach($site['menu_categories'] as $categoryKey => $category)
        <div id="cat-{{ $categoryKey }}" class="mb-12 scroll-mt-24">
            {{-- BOLD category divider --}}
            <div class="flex items-center gap-4 mb-6">
                <h2 class="text-3xl md:text-5xl font-black uppercase tracking-tight"
                    style="color: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                    {{ $category['name'] }}
                </h2>
                <div class="flex-1 h-2 rounded-full"
                     style="background: {{ $site['colors']['primary'] ?? '#dc2626' }};"></div>
            </div>

            @if(!empty($category['description']))
            <p class="text-gray-600 mb-5 -mt-3 text-base">{{ $category['description'] }}</p>
            @endif

            {{-- Item rows --}}
            <div class="space-y-4">
                @foreach($category['items'] as $itemIndex => $item)
                @php
                    $itemId = $item['id'] ?? ($categoryKey . '_' . $itemIndex);
                    $itemPrice = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
                    $hasImage = !empty($item['image']);
                    $hasBadges = !empty($item['badges']);
                    $hasDietary = !empty($item['dietary']);
                @endphp

                <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow overflow-hidden border border-gray-100">
                    <div class="flex {{ $hasImage ? 'flex-row' : 'flex-col' }}">
                        {{-- LEFT: Image (1/3 width) --}}
                        @if($hasImage)
                        <div class="relative w-1/3 md:w-1/3 flex-shrink-0 overflow-hidden">
                            <img src="{{ $item['image'] }}"
                                 alt="{{ $item['alt_text'] ?? $item['name'] }}"
                                 class="w-full h-full object-cover aspect-square md:aspect-auto md:min-h-[140px]"
                                 loading="lazy">
                            {{-- Badges overlay --}}
                            @if($hasBadges)
                            <div class="absolute top-2 left-2 flex flex-col gap-1">
                                @foreach($item['badges'] as $badge)
                                @php
                                    $colorClasses = [
                                        'red' => 'bg-red-500 text-white',
                                        'amber' => 'bg-amber-500 text-white',
                                        'green' => 'bg-green-500 text-white',
                                        'purple' => 'bg-purple-500 text-white',
                                        'blue' => 'bg-blue-500 text-white',
                                    ];
                                    $badgeColor = $colorClasses[$badge['color'] ?? 'red'] ?? 'bg-red-500 text-white';
                                    $badgeLabel = $badge['label'] ?? 'Special';
                                @endphp
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wide rounded {{ $badgeColor }} shadow">
                                    {{ $badgeLabel }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- RIGHT: Details (2/3 width or full) --}}
                        <div class="flex-1 p-4 md:p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start gap-3 mb-1">
                                    <h3 class="text-lg md:text-xl font-black text-gray-900 leading-tight">
                                        {{ $item['name'] }}
                                    </h3>
                                    <span class="text-xl md:text-2xl font-black whitespace-nowrap"
                                          style="color: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                                        {{ $item['price'] }}
                                    </span>
                                </div>

                                {{-- Badges (inline when no image) --}}
                                @if(!$hasImage && $hasBadges)
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach($item['badges'] as $badge)
                                    @php
                                        $colorClasses = [
                                            'red' => 'bg-red-500 text-white',
                                            'amber' => 'bg-amber-500 text-white',
                                            'green' => 'bg-green-500 text-white',
                                            'purple' => 'bg-purple-500 text-white',
                                            'blue' => 'bg-blue-500 text-white',
                                        ];
                                        $badgeColor = $colorClasses[$badge['color'] ?? 'red'] ?? 'bg-red-500 text-white';
                                        $badgeLabel = $badge['label'] ?? 'Special';
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wide rounded {{ $badgeColor }}">
                                        {{ $badgeLabel }}
                                    </span>
                                    @endforeach
                                </div>
                                @endif

                                @if(!empty($item['description']))
                                <p class="text-gray-600 text-sm md:text-base leading-snug">{{ $item['description'] }}</p>
                                @endif

                                @if(!empty($item['note']))
                                <p class="text-xs text-gray-500 italic mt-1">{{ $item['note'] }}</p>
                                @endif

                                {{-- Dietary tags --}}
                                @if($hasDietary)
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($item['dietary'] as $diet)
                                    @php
                                        $dietaryIcons = [
                                            'vegetarian' => ['icon' => '🌱', 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Vegetarian'],
                                            'vegan' => ['icon' => '🌿', 'bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Vegan'],
                                            'gluten_free' => ['icon' => 'GF', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => 'Gluten Free'],
                                            'dairy_free' => ['icon' => 'DF', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Dairy Free'],
                                            'nut_free' => ['icon' => 'NF', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'label' => 'Nut Free'],
                                            'spicy' => ['icon' => '🌶️', 'bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Spicy'],
                                            'keto' => ['icon' => 'K', 'bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'label' => 'Keto'],
                                            'low_carb' => ['icon' => 'LC', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'label' => 'Low Carb'],
                                            'halal' => ['icon' => 'H', 'bg' => 'bg-teal-100', 'text' => 'text-teal-700', 'label' => 'Halal'],
                                        ];
                                        $info = $dietaryIcons[$diet] ?? ['icon' => '?', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => ucfirst(str_replace('_', ' ', $diet))];
                                    @endphp
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-bold {{ $info['bg'] }} {{ $info['text'] }}" title="{{ $info['label'] }}">
                                        <span>{{ $info['icon'] }}</span>
                                    </span>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            {{-- ORDERING: inline +/- quantity controls --}}
                            @if($orderingEnabled)
                            @php
                                $itemNameJs = addslashes($item['name']);
                                $cartLookup = "(items.find(i => i.id === '" . $itemId . "')?.quantity || 0)";
                            @endphp
                            <div class="mt-3 flex items-center justify-end">
                                {{-- When not in cart: big Add button --}}
                                <button type="button"
                                        x-show="{{ $cartLookup }} === 0"
                                        @click.stop="addItem({ id: '{{ $itemId }}', name: '{{ $itemNameJs }}', price: {{ $itemPrice }} })"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-black uppercase tracking-wide text-sm text-white shadow-md hover:scale-105 transition-transform"
                                        style="background: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Add
                                </button>
                                {{-- When in cart: +/- controls --}}
                                <div x-show="{{ $cartLookup }} > 0"
                                     x-cloak
                                     class="inline-flex items-center gap-0 rounded-full shadow-md overflow-hidden"
                                     style="background: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                                    <button type="button"
                                            @click.stop="{{ $cartLookup }} <= 1 ? removeItem('{{ $itemId }}') : updateQuantity('{{ $itemId }}', -1)"
                                            class="w-10 h-10 flex items-center justify-center text-white hover:bg-black/20 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    <span class="px-2 min-w-[2rem] text-center text-white font-black text-lg" x-text="{{ $cartLookup }}"></span>
                                    <button type="button"
                                            @click.stop="addItem({ id: '{{ $itemId }}', name: '{{ $itemNameJs }}', price: {{ $itemPrice }} })"
                                            class="w-10 h-10 flex items-center justify-center text-white hover:bg-black/20 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

@include('samples.partials.gallery-section')

@include('samples.partials.sister-sites-section')

{{-- =================================================================== --}}
{{-- CUSTOM COMPACT CONTACT BLOCK — centered, HUGE address & phone        --}}
{{-- =================================================================== --}}
<section id="contact" class="py-16 md:py-20 bg-white">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h2 class="text-sm font-black uppercase tracking-[0.3em] mb-4"
            style="color: {{ $site['colors']['primary'] ?? '#dc2626' }};">
            Find Us
        </h2>

        @if(!empty($site['address']))
        @php
            $addr = $site['address'];
            $street = is_array($addr) ? ($addr['street'] ?? '') : '';
            $cityLine = is_array($addr)
                ? trim(($addr['city'] ?? '') . (!empty($addr['province']) ? ', ' . $addr['province'] : '') . (!empty($addr['postal_code']) ? ' ' . $addr['postal_code'] : ''), ', ')
                : '';
            $fullAddr = is_array($addr) ? ($addr['full'] ?? trim($street . ' ' . $cityLine)) : $addr;
        @endphp
        <div class="mb-10">
            @if($street)
            <div class="text-3xl md:text-5xl font-black text-gray-900 leading-tight">{{ $street }}</div>
            @if($cityLine)
            <div class="text-2xl md:text-3xl font-bold text-gray-700 mt-1">{{ $cityLine }}</div>
            @endif
            @else
            <div class="text-2xl md:text-4xl font-black text-gray-900">{{ $fullAddr }}</div>
            @endif
            @if(!empty($site['secondary_cta_url']))
            <a href="{{ $site['secondary_cta_url'] }}" target="_blank"
               class="inline-block mt-4 font-bold text-lg underline hover:no-underline"
               style="color: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                Get Directions &rarr;
            </a>
            @endif
        </div>
        @endif

        @if(!empty($site['phone']))
        <div class="mb-10">
            <div class="text-xs font-black uppercase tracking-[0.3em] text-gray-500 mb-2">Call</div>
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}"
               class="block text-4xl md:text-6xl font-black hover:underline"
               style="color: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                {{ $site['phone'] }}
            </a>
        </div>
        @endif

        @if(!empty($site['email']))
        <div class="mb-10">
            <a href="mailto:{{ $site['email'] }}"
               class="text-lg md:text-xl font-semibold text-gray-700 hover:text-gray-900 underline">
                {{ $site['email'] }}
            </a>
        </div>
        @endif

        {{-- Compact hours (today highlighted) --}}
        @if(!empty($site['hours']))
        @php
            $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $today = date('l');
        @endphp
        <div class="mt-10 pt-8 border-t-2 border-gray-100">
            <div class="text-xs font-black uppercase tracking-[0.3em] text-gray-500 mb-4">Hours</div>
            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-3 text-sm">
                @foreach($dayOrder as $day)
                @if(isset($site['hours'][$day]))
                @php $isToday = $day === $today; @endphp
                <div class="{{ $isToday ? 'rounded-lg p-2 text-white' : '' }}"
                     @if($isToday) style="background: {{ $site['colors']['primary'] ?? '#dc2626' }};" @endif>
                    <div class="font-black uppercase text-xs {{ $isToday ? 'text-white' : 'text-gray-900' }}">
                        {{ substr($day, 0, 3) }}
                    </div>
                    <div class="{{ $isToday ? 'text-white/90' : (strtolower($site['hours'][$day]) === 'closed' ? 'text-red-600' : 'text-gray-600') }}">
                        {{ $site['hours'][$day] }}
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Social links --}}
        @if(!empty($site['social_links']))
        <div class="mt-10 flex justify-center gap-4">
            @foreach($site['social_links'] as $platform => $url)
            @if($url)
            <a href="{{ $url }}" target="_blank" rel="noopener"
               class="w-12 h-12 rounded-full flex items-center justify-center text-white shadow-md hover:scale-110 transition-transform"
               style="background: {{ $site['colors']['primary'] ?? '#dc2626' }};"
               aria-label="{{ ucfirst($platform) }}">
                @if($platform === 'facebook')
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                @elseif($platform === 'instagram')
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                @elseif($platform === 'twitter' || $platform === 'x')
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                @elseif($platform === 'tiktok')
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19.589 6.686a4.793 4.793 0 01-3.77-4.245V2h-3.445v13.672a2.896 2.896 0 01-5.201 1.743l-.002-.001.002.001a2.895 2.895 0 013.183-4.51v-3.5a6.329 6.329 0 00-5.394 10.692 6.33 6.33 0 0010.857-4.424V8.687a8.182 8.182 0 004.773 1.526V6.79a4.831 4.831 0 01-1.003-.104z"/></svg>
                @else
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                @endif
            </a>
            @endif
            @endforeach
        </div>
        @endif

        {{-- Final CTA --}}
        <div class="mt-10">
            @if($orderingEnabled)
            <a href="#full-menu"
               class="inline-flex items-center gap-3 px-10 py-5 rounded-2xl text-xl md:text-2xl font-black uppercase tracking-wide text-white shadow-xl hover:scale-105 transition-transform"
               style="background: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                Order Now
            </a>
            @elseif(!empty($site['phone']))
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}"
               class="inline-flex items-center gap-3 px-10 py-5 rounded-2xl text-xl md:text-2xl font-black uppercase tracking-wide text-white shadow-xl hover:scale-105 transition-transform"
               style="background: {{ $site['colors']['primary'] ?? '#dc2626' }};">
                Call to Order
            </a>
            @endif
        </div>
    </div>
</section>

@include('samples.partials.cart-ui')

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
