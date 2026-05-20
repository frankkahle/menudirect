@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    // These variables are needed by the template and partials
    // They're also defined in schema partial for JSON-LD, but @include scope doesn't carry into @section
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

{{-- Global dark styling overrides for bistro aesthetic --}}
<style>
    body { background-color: #0c0a09; }
    .bistro-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: #fcd34d;
    }
    .bistro-divider::before,
    .bistro-divider::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 120px;
        background: linear-gradient(to right, transparent, rgba(252, 211, 77, 0.5), transparent);
    }
    .bistro-dots {
        flex: 1;
        border-bottom: 1px dotted rgba(252, 211, 77, 0.35);
        margin: 0 0.75rem;
        transform: translateY(-0.35rem);
    }
    .bistro-masonry {
        column-count: 1;
        column-gap: 1rem;
    }
    @media (min-width: 640px) { .bistro-masonry { column-count: 2; } }
    @media (min-width: 1024px) { .bistro-masonry { column-count: 3; } }
    .bistro-masonry > * {
        break-inside: avoid;
        margin-bottom: 1rem;
    }
    .bistro-nav-link {
        position: relative;
        padding-bottom: 2px;
    }
    .bistro-nav-link::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 0;
        height: 1px;
        background-color: #fcd34d;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    .bistro-nav-link:hover::after { width: 100%; }
</style>

{{-- Initialize Cart if ordering is enabled --}}
@if($orderingEnabled)
<div x-data="cart({
    apiBaseUrl: '',
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

{{-- Full-Viewport Hero --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-stone-950">
    @if(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $site['hero_image'] ?? $site['cover_photo'] }}');"></div>
    <div class="absolute inset-0 bg-stone-950/80"></div>
    @else
    <div class="absolute inset-0 bg-stone-950"></div>
    @endif

    {{-- Subtle vignette --}}
    <div class="absolute inset-0" style="background: radial-gradient(ellipse at center, transparent 0%, rgba(12, 10, 9, 0.7) 100%);"></div>

    <div class="relative z-10 max-w-3xl mx-auto px-6 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-32 md:h-40 mx-auto mb-10 opacity-95">
        @endif

        <div class="bistro-divider mb-6">
            <span class="text-xs tracking-[0.4em] uppercase text-amber-400">Est. {{ $site['established'] ?? date('Y') }}</span>
        </div>

        <h1 class="font-serif text-5xl md:text-7xl lg:text-8xl font-light text-stone-100 mb-6 tracking-wide">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="font-serif italic text-lg md:text-2xl text-stone-300 font-light tracking-wide mb-10">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="bistro-divider mt-10">
            <span class="text-amber-400 text-lg">&#10038;</span>
        </div>

        <div class="mt-12 flex flex-col sm:flex-row justify-center items-center gap-6">
            @if($orderingEnabled)
            <a href="#full-menu" class="inline-block border border-amber-400/60 text-amber-300 px-10 py-3 text-sm tracking-[0.3em] uppercase hover:bg-amber-400 hover:text-stone-950 transition-all duration-500">
                View Menu
            </a>
            @else
            <a href="#full-menu" class="inline-block border border-amber-400/60 text-amber-300 px-10 py-3 text-sm tracking-[0.3em] uppercase hover:bg-amber-400 hover:text-stone-950 transition-all duration-500">
                View Menu
            </a>
            @endif
            <a href="#reservations" class="inline-block border border-stone-500/60 text-stone-300 px-10 py-3 text-sm tracking-[0.3em] uppercase hover:bg-stone-300 hover:text-stone-950 transition-all duration-500">
                Reservations
            </a>
        </div>
    </div>

    {{-- Scroll indicator --}}
    <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 text-amber-400/70">
        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- Horizontal Anchor Navigation --}}
<nav class="bg-stone-950 border-y border-stone-800 sticky top-0 z-30 backdrop-blur-sm">
    <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center items-center gap-8 md:gap-14 py-5 text-xs md:text-sm tracking-[0.3em] uppercase text-stone-300">
            <li><a href="#full-menu" class="bistro-nav-link hover:text-amber-300 transition">Menu</a></li>
            <li><a href="#reservations" class="bistro-nav-link hover:text-amber-300 transition">Reservations</a></li>
            @if(!empty($site['gallery']))
            <li><a href="#gallery" class="bistro-nav-link hover:text-amber-300 transition">Gallery</a></li>
            @endif
            <li><a href="#contact" class="bistro-nav-link hover:text-amber-300 transition">Contact</a></li>
        </ul>
    </div>
</nav>

{{-- Ordering Status Banner (if enabled) --}}
@if($orderingEnabled)
<section x-show="isRestaurantOpen" class="bg-stone-900 border-b border-amber-900/30 py-3">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <span class="text-xs tracking-[0.3em] uppercase text-amber-300">
            Online Ordering Available
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-3 text-stone-600">|</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-3 text-stone-600">|</span> Delivery
            @endif
        </span>
    </div>
</section>
<section x-show="!isRestaurantOpen" x-cloak class="bg-stone-900 border-b border-amber-900/30 py-3">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <span class="text-xs tracking-[0.3em] uppercase text-stone-400">
            Currently Closed
            <template x-if="todayHours">
                <span><span class="mx-3 text-stone-600">|</span> Today: <span x-text="todayHours" class="text-amber-300"></span></span>
            </template>
        </span>
    </div>
</section>
@endif

{{-- About / Introduction --}}
<section class="bg-stone-950 py-24 md:py-32">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <div class="bistro-divider mb-10">
            <span class="text-xs tracking-[0.4em] uppercase text-amber-400">Welcome</span>
        </div>

        @if(!empty($site['about']))
        <p class="font-serif text-stone-300 text-lg md:text-xl leading-relaxed italic font-light">
            {{ $site['about'] }}
        </p>
        @elseif(!empty($site['tagline']))
        <p class="font-serif text-stone-300 text-lg md:text-xl leading-relaxed italic font-light">
            {{ $site['tagline'] }}
        </p>
        @endif

        @if(!empty($site['features']))
        <div class="mt-14 flex flex-wrap justify-center gap-x-10 gap-y-4 text-xs tracking-[0.3em] uppercase text-stone-400">
            @foreach($site['features'] as $feature)
            <span class="flex items-center">
                <span class="text-amber-400 mr-3">&#10038;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- Menu — Elegant Two-Column Text Layout --}}
@if(!empty($site['menu_categories']))
<section class="bg-stone-900 py-24 md:py-32" id="full-menu">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-16">
            <div class="bistro-divider mb-6">
                <span class="text-xs tracking-[0.4em] uppercase text-amber-400">La Carte</span>
            </div>
            <h2 class="font-serif text-4xl md:text-5xl font-light text-stone-100 mb-4">Our Menu</h2>
            @if($orderingEnabled)
            <p class="text-stone-400 text-sm italic font-serif">Select an item to add to your order</p>
            @else
            <p class="text-stone-400 text-sm italic font-serif">Crafted with the finest ingredients</p>
            @endif
        </div>

        @foreach($site['menu_categories'] as $categoryKey => $category)
        <div class="mb-16 last:mb-0">
            {{-- Category Header --}}
            <div class="text-center mb-10">
                <h3 class="font-serif text-2xl md:text-3xl text-amber-300 font-light tracking-wide uppercase">
                    {{ $category['name'] }}
                </h3>
                <div class="flex justify-center mt-3">
                    <span class="inline-block w-16 h-px bg-amber-400/50"></span>
                </div>
                @if(!empty($category['description']))
                <p class="mt-4 text-stone-400 text-sm italic font-serif max-w-xl mx-auto">{{ $category['description'] }}</p>
                @endif
            </div>

            {{-- Items as elegant text list --}}
            <div class="space-y-7">
                @foreach($category['items'] as $itemIndex => $item)
                @php
                    $itemId = $item['id'] ?? ($categoryKey . '_' . $itemIndex);
                    $itemPrice = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
                @endphp
                <div class="group {{ $orderingEnabled ? 'cursor-pointer' : '' }}"
                    @if($orderingEnabled)
                    @click="addItem({ id: '{{ $itemId }}', name: '{{ addslashes($item['name']) }}', price: {{ $itemPrice }} })"
                    @endif>
                    <div class="flex items-baseline">
                        <h4 class="font-serif text-lg md:text-xl text-stone-100 group-hover:text-amber-300 transition whitespace-nowrap">
                            {{ $item['name'] }}
                            @if(!empty($item['featured']))
                            <span class="ml-2 text-amber-400 text-xs align-middle">&#10038;</span>
                            @endif
                        </h4>
                        <span class="bistro-dots"></span>
                        <span class="font-serif text-lg md:text-xl text-amber-300 whitespace-nowrap">
                            {{ str_replace('$', '', $item['price']) }}
                        </span>
                    </div>
                    @if(!empty($item['description']))
                    <p class="font-serif text-sm text-stone-400 italic leading-relaxed mt-2 pr-16 max-w-2xl">
                        {{ $item['description'] }}
                    </p>
                    @endif
                    @if(!empty($item['note']))
                    <p class="font-serif text-xs text-stone-500 italic mt-1">
                        {{ $item['note'] }}
                    </p>
                    @endif
                    @if(!empty($item['dietary']))
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($item['dietary'] as $diet)
                        @php
                            $dietLabels = [
                                'vegetarian' => 'Vegetarian',
                                'vegan' => 'Vegan',
                                'gluten_free' => 'Gluten Free',
                                'dairy_free' => 'Dairy Free',
                                'nut_free' => 'Nut Free',
                                'spicy' => 'Spicy',
                                'keto' => 'Keto',
                                'low_carb' => 'Low Carb',
                                'halal' => 'Halal',
                            ];
                            $dietLabel = $dietLabels[$diet] ?? ucfirst(str_replace('_', ' ', $diet));
                        @endphp
                        <span class="text-[10px] tracking-[0.2em] uppercase text-amber-400/70 border border-amber-400/30 px-2 py-0.5">
                            {{ $dietLabel }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        @if($orderingEnabled)
        <div class="mt-16 text-center">
            <p class="text-xs text-stone-500 italic font-serif">
                All prices in {{ $site['currency'] ?? 'CAD' }}. Applicable taxes not included.
            </p>
        </div>
        @endif
    </div>
</section>
@endif

{{-- Testimonials — Centered Blockquotes --}}
@if(!empty($site['testimonials']))
<section class="bg-stone-950 py-24 md:py-32">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-16">
            <div class="bistro-divider mb-6">
                <span class="text-xs tracking-[0.4em] uppercase text-amber-400">Praise</span>
            </div>
            <h2 class="font-serif text-4xl md:text-5xl font-light text-stone-100">In Their Words</h2>
        </div>

        <div class="space-y-16">
            @foreach($site['testimonials'] as $testimonial)
            <blockquote class="text-center">
                <div class="font-serif text-6xl text-amber-400/40 leading-none mb-4">&ldquo;</div>
                <p class="font-serif text-xl md:text-2xl text-stone-200 italic font-light leading-relaxed max-w-3xl mx-auto">
                    {{ $testimonial['text'] }}
                </p>
                <div class="mt-8 flex items-center justify-center gap-4">
                    <span class="inline-block w-10 h-px bg-amber-400/50"></span>
                    <cite class="not-italic text-xs tracking-[0.3em] uppercase text-amber-300 font-serif">
                        {{ $testimonial['author'] }}
                    </cite>
                    <span class="inline-block w-10 h-px bg-amber-400/50"></span>
                </div>
            </blockquote>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Gallery — Masonry Grid --}}
@if(!empty($site['gallery']))
<section class="bg-stone-900 py-24 md:py-32" id="gallery">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16">
            <div class="bistro-divider mb-6">
                <span class="text-xs tracking-[0.4em] uppercase text-amber-400">Gallery</span>
            </div>
            <h2 class="font-serif text-4xl md:text-5xl font-light text-stone-100">Ambiance</h2>
            <p class="text-stone-400 text-sm italic font-serif mt-3">A glimpse inside</p>
        </div>

        <div class="bistro-masonry">
            @foreach($site['gallery'] as $index => $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
                $imageCaption = is_array($image) ? ($image['caption'] ?? null) : null;
            @endphp
            <figure class="overflow-hidden border border-stone-800 hover:border-amber-400/40 transition duration-500 group">
                <img src="{{ $imageUrl }}"
                     alt="{{ $imageCaption ?: $imageAlt }}"
                     class="w-full block opacity-85 group-hover:opacity-100 transition duration-700"
                     loading="lazy">
                @if($imageCaption)
                <figcaption class="px-4 py-3 text-xs tracking-wide text-stone-400 italic font-serif text-center bg-stone-950/40">{{ $imageCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Reservations / Catering / Google Reviews / Sister Sites (standard partials) --}}
<div class="bg-stone-950">
    @include('samples.partials.reservations-section')
    @include('samples.partials.catering-section')
    @include('samples.partials.google-reviews-section')
    @include('samples.partials.sister-sites-section')
</div>

{{-- Custom Bistro Hours & Contact — Minimal Centered --}}
<section class="bg-stone-950 py-24 md:py-32 border-t border-stone-800" id="contact">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <div class="bistro-divider mb-6">
            <span class="text-xs tracking-[0.4em] uppercase text-amber-400">Visit</span>
        </div>
        <h2 class="font-serif text-4xl md:text-5xl font-light text-stone-100 mb-14">Find Us</h2>

        {{-- Address --}}
        @if(!empty($site['address']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase text-amber-400 mb-3">Address</p>
            <p class="font-serif text-stone-200 text-lg md:text-xl leading-relaxed">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
            @if(!empty($site['secondary_cta_url']))
            <a href="{{ $site['secondary_cta_url'] }}" target="_blank" class="inline-block mt-3 text-xs tracking-[0.3em] uppercase text-amber-300 hover:text-amber-400 transition">
                Get Directions &rarr;
            </a>
            @endif
        </div>
        @endif

        {{-- Hours --}}
        @if(!empty($site['hours']))
        @php
            $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $sortedHours = [];
            foreach ($dayOrder as $day) {
                if (isset($site['hours'][$day])) {
                    $sortedHours[$day] = $site['hours'][$day];
                }
            }
            $today = date('l');
        @endphp
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase text-amber-400 mb-4">Hours</p>
            <div class="inline-block text-left font-serif">
                @foreach($sortedHours as $day => $time)
                <div class="flex items-baseline py-1.5 {{ $day === $today ? 'text-amber-300' : 'text-stone-300' }}">
                    <span class="text-sm tracking-wide w-28">{{ $day }}</span>
                    <span class="bistro-dots mx-3" style="min-width: 3rem;"></span>
                    <span class="text-sm {{ strtolower($time) === 'closed' ? 'text-stone-500 italic' : '' }}">{{ $time }}</span>
                </div>
                @endforeach
            </div>

            @if(!empty($site['holiday_hours']))
            <div class="mt-8 max-w-md mx-auto">
                <p class="text-xs tracking-[0.3em] uppercase text-amber-400/70 mb-3">Special Hours</p>
                @foreach($site['holiday_hours'] as $holiday)
                <div class="flex justify-between text-sm font-serif text-stone-400 py-1">
                    <span>
                        {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                        @if(!empty($holiday['label'])) &mdash; {{ $holiday['label'] }} @endif
                    </span>
                    <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'text-stone-500 italic' : '' }}">
                        {{ $holiday['hours'] }}
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- Phone & Email --}}
        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase text-amber-400 mb-2">Telephone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="font-serif text-stone-200 text-lg hover:text-amber-300 transition">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase text-amber-400 mb-2">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="font-serif text-stone-200 text-lg hover:text-amber-300 transition">
                    {{ $site['email'] }}
                </a>
            </div>
            @endif
        </div>

        {{-- Social Links --}}
        @if(!empty($site['social_links']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase text-amber-400 mb-4">Follow</p>
            <div class="flex justify-center items-center gap-6">
                @foreach($site['social_links'] as $platform => $url)
                @if($url)
                <a href="{{ $url }}" target="_blank" rel="noopener" class="text-stone-400 hover:text-amber-300 transition" aria-label="{{ ucfirst($platform) }}">
                    @if($platform === 'facebook')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    @elseif($platform === 'instagram')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    @elseif($platform === 'twitter' || $platform === 'x')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    @elseif($platform === 'youtube')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    @elseif($platform === 'tiktok')
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005.8 20.1a6.34 6.34 0 0010.86-4.43V8.81a8.16 8.16 0 004.77 1.52V6.89a4.85 4.85 0 01-1.84-.2z"/></svg>
                    @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </a>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Call to Action --}}
        <div class="pt-6">
            @if($orderingEnabled)
            <a href="#full-menu" class="inline-block border border-amber-400/60 text-amber-300 px-12 py-4 text-sm tracking-[0.3em] uppercase hover:bg-amber-400 hover:text-stone-950 transition-all duration-500">
                Begin Your Order
            </a>
            @elseif(!empty($site['phone']))
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-block border border-amber-400/60 text-amber-300 px-12 py-4 text-sm tracking-[0.3em] uppercase hover:bg-amber-400 hover:text-stone-950 transition-all duration-500">
                Call to Reserve
            </a>
            @endif
        </div>
    </div>
</section>

{{-- Footer Sign-off --}}
<footer class="bg-stone-950 border-t border-stone-800 py-10">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="bistro-divider mb-4">
            <span class="text-amber-400 text-lg">&#10038;</span>
        </div>
        <p class="font-serif text-stone-500 text-sm italic">
            &copy; {{ date('Y') }} {{ $site['name'] }}. All rights reserved.
        </p>
    </div>
</footer>

@include('samples.partials.cart-ui')

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
