@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Teko:wght@400;500;600;700&display=swap');
    body { background-color: #1a1008; }
    .smoke-heading { font-family: 'Teko', sans-serif; font-weight: 600; letter-spacing: 0.05em; }
    .smoke-rule {
        height: 4px;
        background: linear-gradient(to right, transparent, #d97706, #92400e, #d97706, transparent);
        border: none;
    }
    .smoke-rule-thin {
        height: 2px;
        background: linear-gradient(to right, transparent, #92400e, transparent);
        border: none;
    }
    .smoke-masonry {
        column-count: 1;
        column-gap: 1rem;
    }
    @media (min-width: 640px) { .smoke-masonry { column-count: 2; } }
    @media (min-width: 1024px) { .smoke-masonry { column-count: 3; } }
    .smoke-masonry > * {
        break-inside: avoid;
        margin-bottom: 1rem;
    }
    .smoke-nav-link {
        position: relative;
        padding-bottom: 4px;
    }
    .smoke-nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 0;
        height: 3px;
        background-color: #d97706;
        transition: width 0.3s ease;
    }
    .smoke-nav-link:hover::after { width: 100%; }
    .smoke-texture {
        background-color: #1a1008;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23422006' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
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

{{-- Full-Viewport Hero --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $site['hero_image'] ?? $site['cover_photo'] ?? '/images/templates/smokehouse/hero.jpg' }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-amber-950/30 to-black/80"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-28 md:h-36 mx-auto mb-8">
        @endif

        <div class="w-32 mx-auto mb-6 smoke-rule"></div>

        <h1 class="smoke-heading text-6xl md:text-8xl lg:text-9xl text-white uppercase leading-none">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="text-lg md:text-2xl text-amber-200/80 tracking-widest uppercase mt-4 mb-8 font-light">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="w-32 mx-auto mt-6 smoke-rule"></div>

        {{-- Open/Closed Banner --}}
        @if($orderingEnabled)
        <div class="mt-10 mb-8">
            <span x-show="isRestaurantOpen" class="inline-block bg-amber-700 text-amber-100 px-8 py-2 text-sm tracking-[0.3em] uppercase font-bold border border-amber-600">
                Smokin' &amp; Open &mdash; Order Now
            </span>
            <span x-show="!isRestaurantOpen" x-cloak class="inline-block bg-stone-800 text-stone-400 px-8 py-2 text-sm tracking-[0.3em] uppercase font-bold border border-stone-700">
                Fires are out &mdash; Currently Closed
                <template x-if="todayHours">
                    <span class="text-amber-400"> | <span x-text="todayHours"></span></span>
                </template>
            </span>
        </div>
        @endif

        <div class="mt-10 flex flex-col sm:flex-row justify-center items-center gap-4">
            <a href="#full-menu" class="inline-block bg-amber-700 hover:bg-amber-600 text-white px-10 py-3 smoke-heading text-xl tracking-widest uppercase transition-all duration-300">
                View Menu
            </a>
            <a href="#contact" class="inline-block border-2 border-amber-600/60 text-amber-200 px-10 py-3 smoke-heading text-xl tracking-widest uppercase hover:bg-amber-700 hover:text-white transition-all duration-300">
                Visit Us
            </a>
        </div>
    </div>

    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 text-amber-500/60">
        <svg class="w-6 h-6 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- Thick Rule --}}
<div class="smoke-rule"></div>

{{-- Sticky Navigation --}}
<nav class="smoke-texture border-b border-amber-900/60 sticky top-0 z-30">
    <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center items-center gap-8 md:gap-14 py-4">
            <li><a href="#full-menu" class="smoke-nav-link smoke-heading text-lg tracking-widest uppercase text-amber-200 hover:text-amber-400 transition">Menu</a></li>
            @if(!empty($site['reservations']['enabled']))
            <li><a href="#reservations" class="smoke-nav-link smoke-heading text-lg tracking-widest uppercase text-amber-200 hover:text-amber-400 transition">Reservations</a></li>
            @endif
            @if(!empty($site['gallery'] ?? $site['settings']['gallery'] ?? null))
            <li><a href="#gallery" class="smoke-nav-link smoke-heading text-lg tracking-widest uppercase text-amber-200 hover:text-amber-400 transition">Gallery</a></li>
            @endif
            <li><a href="#hours" class="smoke-nav-link smoke-heading text-lg tracking-widest uppercase text-amber-200 hover:text-amber-400 transition">Hours</a></li>
            <li><a href="#contact" class="smoke-nav-link smoke-heading text-lg tracking-widest uppercase text-amber-200 hover:text-amber-400 transition">Contact</a></li>
        </ul>
    </div>
</nav>

{{-- Features Strip --}}
@php $features = $site['settings']['features'] ?? $site['features'] ?? []; @endphp
@if(!empty($features))
<section class="bg-amber-900 py-5">
    <div class="max-w-6xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-10 gap-y-3 text-amber-100">
            @foreach($features as $feature)
            <span class="flex items-center gap-2 smoke-heading text-lg tracking-widest uppercase">
                <span class="text-amber-400">&#9670;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
<div class="smoke-rule"></div>
@endif

{{-- About --}}
<section class="smoke-texture py-20 md:py-28">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="smoke-heading text-4xl md:text-5xl text-amber-500 uppercase mb-8">The Tradition</h2>
        <div class="w-24 mx-auto mb-8 smoke-rule-thin"></div>
        @if(!empty($site['about']))
        <p class="text-amber-100/80 text-lg md:text-xl leading-relaxed">{{ $site['about'] }}</p>
        @elseif(!empty($site['tagline']))
        <p class="text-amber-100/80 text-lg md:text-xl leading-relaxed">{{ $site['tagline'] }}</p>
        @endif
    </div>
</section>

<div class="smoke-rule"></div>

{{-- Menu Section --}}
@include('samples.partials.menu-section')

<div class="smoke-rule"></div>

{{-- Hours Section --}}
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
<section class="smoke-texture py-20 md:py-28" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="smoke-heading text-4xl md:text-5xl text-amber-500 uppercase mb-10">Hours</h2>
        <div class="w-24 mx-auto mb-10 smoke-rule-thin"></div>
        <div class="inline-block text-left">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-center py-3 border-b border-amber-900/30 {{ $day === $today ? 'text-amber-400' : 'text-amber-100/70' }}">
                <span class="smoke-heading text-xl tracking-widest w-36 uppercase">{{ $day }}</span>
                <span class="flex-1 mx-4"></span>
                <span class="text-base {{ strtolower($time) === 'closed' ? 'text-stone-600 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>

        @if(!empty($site['holiday_hours']))
        <div class="mt-10 max-w-md mx-auto">
            <p class="smoke-heading text-lg tracking-widest uppercase text-amber-500 mb-4">Special Hours</p>
            @foreach($site['holiday_hours'] as $holiday)
            <div class="flex justify-between text-sm text-amber-100/60 py-1">
                <span>{{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}@if(!empty($holiday['label'])) &mdash; {{ $holiday['label'] }}@endif</span>
                <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'text-stone-600 italic' : '' }}">{{ $holiday['hours'] }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Gallery --}}
@php $gallery = $site['settings']['gallery'] ?? $site['gallery'] ?? []; @endphp
@if(!empty($gallery))
<div class="smoke-rule"></div>
<section class="smoke-texture py-20 md:py-28" id="gallery">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="smoke-heading text-4xl md:text-5xl text-amber-500 uppercase text-center mb-12">Gallery</h2>
        <div class="w-24 mx-auto mb-12 smoke-rule-thin"></div>
        <div class="smoke-masonry">
            @foreach($gallery as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
                $imageCaption = is_array($image) ? ($image['caption'] ?? null) : null;
            @endphp
            <figure class="overflow-hidden border border-amber-900/40 hover:border-amber-600 transition duration-300 group">
                <img src="{{ $imageUrl }}" alt="{{ $imageCaption ?: $imageAlt }}" class="w-full block opacity-90 group-hover:opacity-100 group-hover:scale-105 transition duration-500" loading="lazy">
                @if($imageCaption)
                <figcaption class="px-4 py-3 text-sm text-amber-200 text-center smoke-heading tracking-widest uppercase">{{ $imageCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

<div class="smoke-rule"></div>

{{-- Contact --}}
<section class="smoke-texture py-20 md:py-28" id="contact">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="smoke-heading text-4xl md:text-5xl text-amber-500 uppercase mb-12">Find Us</h2>
        <div class="w-24 mx-auto mb-12 smoke-rule-thin"></div>

        @if(!empty($site['address']))
        <div class="mb-10">
            <p class="smoke-heading text-lg tracking-widest uppercase text-amber-600 mb-3">Address</p>
            <p class="text-amber-100 text-lg md:text-xl">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
            @if(!empty($site['secondary_cta_url']))
            <a href="{{ $site['secondary_cta_url'] }}" target="_blank" class="inline-block mt-3 smoke-heading text-base tracking-widest uppercase text-amber-500 hover:text-amber-400 transition">
                Get Directions &rarr;
            </a>
            @endif
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-10">
            @if(!empty($site['phone']))
            <div>
                <p class="smoke-heading text-lg tracking-widest uppercase text-amber-600 mb-2">Phone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-amber-100 text-lg hover:text-amber-400 transition">{{ $site['phone'] }}</a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="smoke-heading text-lg tracking-widest uppercase text-amber-600 mb-2">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="text-amber-100 text-lg hover:text-amber-400 transition">{{ $site['email'] }}</a>
            </div>
            @endif
        </div>

        @if(!empty($site['social_links']))
        <div class="flex justify-center items-center gap-6 mb-10">
            @foreach($site['social_links'] as $platform => $url)
            @if($url)
            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-amber-700 hover:text-amber-400 transition" aria-label="{{ ucfirst($platform) }}">
                @if($platform === 'facebook')
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                @elseif($platform === 'instagram')
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                @elseif($platform === 'twitter' || $platform === 'x')
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                @else
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </a>
            @endif
            @endforeach
        </div>
        @endif

        @if($orderingEnabled)
        <a href="#full-menu" class="inline-block bg-amber-700 hover:bg-amber-600 text-white px-12 py-4 smoke-heading text-xl tracking-widest uppercase transition-all duration-300">
            Start Your Order
        </a>
        @elseif(!empty($site['phone']))
        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-block bg-amber-700 hover:bg-amber-600 text-white px-12 py-4 smoke-heading text-xl tracking-widest uppercase transition-all duration-300">
            Call Us
        </a>
        @endif
    </div>
</section>

{{-- Standard Partials --}}
<div class="smoke-texture">
    @include('samples.partials.reservations-section')
    @include('samples.partials.catering-section')
    @include('samples.partials.google-reviews-section')
    @include('samples.partials.sister-sites-section')
</div>

{{-- Footer --}}
<footer class="smoke-texture border-t border-amber-900/40 py-8">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="w-24 mx-auto mb-4 smoke-rule-thin"></div>
        <p class="text-amber-900 text-sm">
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
