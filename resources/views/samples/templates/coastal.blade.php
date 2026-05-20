@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;500;600;700&display=swap');
    body { background-color: #faf9f6; }
    .coastal-heading { font-family: 'Josefin Sans', sans-serif; font-weight: 300; }
    .coastal-heading-bold { font-family: 'Josefin Sans', sans-serif; font-weight: 600; }
    .coastal-line {
        height: 1px;
        background: linear-gradient(to right, transparent, #93c5fd, #38bdf8, #93c5fd, transparent);
    }
    .coastal-line-sand {
        height: 1px;
        background: linear-gradient(to right, transparent, #d6cfc4, transparent);
    }
    .coastal-masonry {
        column-count: 1;
        column-gap: 1.5rem;
    }
    @media (min-width: 640px) { .coastal-masonry { column-count: 2; } }
    @media (min-width: 1024px) { .coastal-masonry { column-count: 3; } }
    .coastal-masonry > * {
        break-inside: avoid;
        margin-bottom: 1.5rem;
    }
    .coastal-nav-link {
        position: relative;
        padding-bottom: 4px;
    }
    .coastal-nav-link::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 0;
        height: 1px;
        background-color: #38bdf8;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    .coastal-nav-link:hover::after { width: 100%; }
    .coastal-wave {
        position: relative;
        overflow: hidden;
    }
    .coastal-wave::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        width: 100%;
        height: 40px;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 40'%3E%3Cpath fill='%23faf9f6' d='M0,20 C360,40 720,0 1080,20 C1260,30 1350,35 1440,20 L1440,40 L0,40 Z'/%3E%3C/svg%3E") no-repeat bottom center;
        background-size: cover;
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
<section class="relative min-h-screen flex items-center justify-center overflow-hidden coastal-wave">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $site['hero_image'] ?? $site['cover_photo'] ?? '/images/templates/coastal/hero.jpg' }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-sky-900/40 via-sky-800/30 to-sky-950/60"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-28 md:h-36 mx-auto mb-10 drop-shadow-lg">
        @endif

        <p class="coastal-heading text-sm md:text-base tracking-[0.5em] uppercase text-sky-200 mb-6">
            ~ {{ $site['address']['city'] ?? 'Seaside Dining' }} ~
        </p>

        <h1 class="coastal-heading text-5xl md:text-7xl lg:text-8xl text-white mb-6 leading-tight">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="coastal-heading text-xl md:text-2xl text-sky-100/90 tracking-wide mb-10">
            {{ $site['tagline'] }}
        </p>
        @endif

        {{-- Open/Closed Banner --}}
        @if($orderingEnabled)
        <div class="mb-10">
            <span x-show="isRestaurantOpen" class="inline-block bg-white/20 backdrop-blur-sm text-white px-8 py-2 text-sm tracking-[0.2em] uppercase rounded-full border border-white/30">
                Open Now &mdash; Order Online
            </span>
            <span x-show="!isRestaurantOpen" x-cloak class="inline-block bg-black/20 backdrop-blur-sm text-sky-200 px-8 py-2 text-sm tracking-[0.2em] uppercase rounded-full border border-white/20">
                Currently Closed
                <template x-if="todayHours">
                    <span> &mdash; Today: <span x-text="todayHours"></span></span>
                </template>
            </span>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
            <a href="#full-menu" class="inline-block bg-sky-500 hover:bg-sky-400 text-white px-10 py-3 coastal-heading-bold text-sm tracking-[0.2em] uppercase transition-all duration-300 rounded-full shadow-lg">
                View Menu
            </a>
            <a href="#contact" class="inline-block border border-white/60 text-white px-10 py-3 coastal-heading-bold text-sm tracking-[0.2em] uppercase hover:bg-white hover:text-sky-800 transition-all duration-300 rounded-full">
                Visit Us
            </a>
        </div>
    </div>

    <div class="absolute bottom-16 left-1/2 transform -translate-x-1/2 text-white/50">
        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- Sticky Navigation --}}
<nav class="bg-white/90 backdrop-blur-md border-b border-sky-100 sticky top-0 z-30 shadow-sm">
    <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center items-center gap-8 md:gap-14 py-4 coastal-heading-bold text-xs md:text-sm tracking-[0.2em] uppercase text-sky-800">
            <li><a href="#full-menu" class="coastal-nav-link hover:text-sky-500 transition">Menu</a></li>
            @if(!empty($site['reservations']['enabled']))
            <li><a href="#reservations" class="coastal-nav-link hover:text-sky-500 transition">Reservations</a></li>
            @endif
            @if(!empty($site['gallery'] ?? $site['settings']['gallery'] ?? null))
            <li><a href="#gallery" class="coastal-nav-link hover:text-sky-500 transition">Gallery</a></li>
            @endif
            <li><a href="#hours" class="coastal-nav-link hover:text-sky-500 transition">Hours</a></li>
            <li><a href="#contact" class="coastal-nav-link hover:text-sky-500 transition">Contact</a></li>
        </ul>
    </div>
</nav>

{{-- Features Strip --}}
@php $features = $site['settings']['features'] ?? $site['features'] ?? []; @endphp
@if(!empty($features))
<section class="bg-sky-50 py-6 border-b border-sky-100">
    <div class="max-w-6xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-10 gap-y-3 text-sm tracking-wide text-sky-700">
            @foreach($features as $feature)
            <span class="flex items-center gap-2 coastal-heading-bold">
                <span class="text-sky-400">&#10070;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About --}}
<section class="bg-white py-24 md:py-32">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <p class="coastal-heading text-sm tracking-[0.4em] uppercase text-sky-400 mb-4">Welcome</p>
        <div class="w-16 mx-auto mb-8 coastal-line"></div>
        @if(!empty($site['about']))
        <p class="coastal-heading text-sky-900 text-lg md:text-xl leading-relaxed">{{ $site['about'] }}</p>
        @elseif(!empty($site['tagline']))
        <p class="coastal-heading text-sky-900 text-lg md:text-xl leading-relaxed">{{ $site['tagline'] }}</p>
        @endif
    </div>
</section>

<div class="max-w-4xl mx-auto px-6"><div class="coastal-line"></div></div>

{{-- Menu Section --}}
@include('samples.partials.menu-section')

<div class="max-w-4xl mx-auto px-6"><div class="coastal-line"></div></div>

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
<section class="bg-white py-24 md:py-32" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <p class="coastal-heading text-sm tracking-[0.4em] uppercase text-sky-400 mb-4">Visit Us</p>
        <h2 class="coastal-heading text-4xl md:text-5xl text-sky-900 mb-10">Hours</h2>
        <div class="w-16 mx-auto mb-10 coastal-line"></div>
        <div class="inline-block text-left">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-center py-3 {{ $day === $today ? 'text-sky-600' : 'text-sky-800/70' }}">
                <span class="coastal-heading-bold text-sm tracking-widest w-32 uppercase">{{ $day }}</span>
                <span class="flex-1 mx-4 border-b border-dotted border-sky-200"></span>
                <span class="coastal-heading text-base {{ strtolower($time) === 'closed' ? 'text-sky-300 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>

        @if(!empty($site['holiday_hours']))
        <div class="mt-10 max-w-md mx-auto">
            <p class="coastal-heading-bold text-xs tracking-[0.3em] uppercase text-sky-400 mb-3">Special Hours</p>
            @foreach($site['holiday_hours'] as $holiday)
            <div class="flex justify-between text-sm text-sky-700/60 py-1">
                <span>{{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}@if(!empty($holiday['label'])) &mdash; {{ $holiday['label'] }}@endif</span>
                <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'text-sky-300 italic' : '' }}">{{ $holiday['hours'] }}</span>
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
<section class="bg-sky-50 py-24 md:py-32" id="gallery">
    <div class="max-w-6xl mx-auto px-6">
        <p class="coastal-heading text-sm tracking-[0.4em] uppercase text-sky-400 mb-4 text-center">Snapshots</p>
        <h2 class="coastal-heading text-4xl md:text-5xl text-sky-900 text-center mb-12">Gallery</h2>
        <div class="w-16 mx-auto mb-12 coastal-line"></div>
        <div class="coastal-masonry">
            @foreach($gallery as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
                $imageCaption = is_array($image) ? ($image['caption'] ?? null) : null;
            @endphp
            <figure class="overflow-hidden rounded-xl shadow-md hover:shadow-xl transition duration-500 group bg-white">
                <img src="{{ $imageUrl }}" alt="{{ $imageCaption ?: $imageAlt }}" class="w-full block group-hover:scale-105 transition duration-700" loading="lazy">
                @if($imageCaption)
                <figcaption class="px-4 py-3 text-sm text-sky-800 text-center coastal-heading">{{ $imageCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact --}}
<section class="bg-white py-24 md:py-32" id="contact">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <p class="coastal-heading text-sm tracking-[0.4em] uppercase text-sky-400 mb-4">Come Visit</p>
        <h2 class="coastal-heading text-4xl md:text-5xl text-sky-900 mb-12">Find Us</h2>
        <div class="w-16 mx-auto mb-12 coastal-line"></div>

        @if(!empty($site['address']))
        <div class="mb-10">
            <p class="coastal-heading-bold text-xs tracking-[0.3em] uppercase text-sky-400 mb-3">Address</p>
            <p class="coastal-heading text-sky-800 text-lg md:text-xl">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
            @if(!empty($site['secondary_cta_url']))
            <a href="{{ $site['secondary_cta_url'] }}" target="_blank" class="inline-block mt-3 coastal-heading-bold text-sm tracking-widest uppercase text-sky-500 hover:text-sky-400 transition">
                Get Directions &rarr;
            </a>
            @endif
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-10">
            @if(!empty($site['phone']))
            <div>
                <p class="coastal-heading-bold text-xs tracking-[0.3em] uppercase text-sky-400 mb-2">Phone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="coastal-heading text-sky-800 text-lg hover:text-sky-500 transition">{{ $site['phone'] }}</a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="coastal-heading-bold text-xs tracking-[0.3em] uppercase text-sky-400 mb-2">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="coastal-heading text-sky-800 text-lg hover:text-sky-500 transition">{{ $site['email'] }}</a>
            </div>
            @endif
        </div>

        @if(!empty($site['social_links']))
        <div class="flex justify-center items-center gap-6 mb-10">
            @foreach($site['social_links'] as $platform => $url)
            @if($url)
            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-sky-300 hover:text-sky-500 transition" aria-label="{{ ucfirst($platform) }}">
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
        <a href="#full-menu" class="inline-block bg-sky-500 hover:bg-sky-400 text-white px-12 py-4 coastal-heading-bold text-sm tracking-[0.2em] uppercase transition-all duration-300 rounded-full shadow-lg">
            Order Online
        </a>
        @elseif(!empty($site['phone']))
        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-block bg-sky-500 hover:bg-sky-400 text-white px-12 py-4 coastal-heading-bold text-sm tracking-[0.2em] uppercase transition-all duration-300 rounded-full shadow-lg">
            Call to Reserve
        </a>
        @endif
    </div>
</section>

{{-- Standard Partials --}}
<div class="bg-sky-50">
    @include('samples.partials.reservations-section')
    @include('samples.partials.catering-section')
    @include('samples.partials.google-reviews-section')
    @include('samples.partials.sister-sites-section')
</div>

{{-- Footer --}}
<footer class="bg-white border-t border-sky-100 py-8">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="w-16 mx-auto mb-4 coastal-line"></div>
        <p class="coastal-heading text-sky-400 text-sm">
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
