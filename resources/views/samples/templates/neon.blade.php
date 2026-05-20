@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&display=swap');
    body { background-color: #0a0a0f; }
    .neon-heading { font-family: 'Orbitron', sans-serif; font-weight: 700; }
    .neon-heading-light { font-family: 'Orbitron', sans-serif; font-weight: 400; }
    .neon-glow-pink {
        color: #ff2d95;
        text-shadow: 0 0 7px #ff2d95, 0 0 20px #ff2d9580, 0 0 40px #ff2d9540;
    }
    .neon-glow-cyan {
        color: #00f0ff;
        text-shadow: 0 0 7px #00f0ff, 0 0 20px #00f0ff80, 0 0 40px #00f0ff40;
    }
    .neon-glow-pink-subtle {
        text-shadow: 0 0 10px #ff2d9540, 0 0 30px #ff2d9520;
    }
    .neon-glow-cyan-subtle {
        text-shadow: 0 0 10px #00f0ff40, 0 0 30px #00f0ff20;
    }
    .neon-border-pink { border-color: #ff2d9560; }
    .neon-border-cyan { border-color: #00f0ff60; }
    .neon-line {
        height: 1px;
        background: linear-gradient(to right, transparent, #ff2d95, #00f0ff, transparent);
    }
    .neon-line-pink {
        height: 2px;
        background: linear-gradient(to right, transparent, #ff2d95, transparent);
        box-shadow: 0 0 8px #ff2d9560;
    }
    .neon-line-cyan {
        height: 2px;
        background: linear-gradient(to right, transparent, #00f0ff, transparent);
        box-shadow: 0 0 8px #00f0ff60;
    }
    .neon-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        backdrop-filter: blur(4px);
    }
    .neon-card:hover {
        border-color: #ff2d9540;
        box-shadow: 0 0 20px #ff2d9515;
    }
    .neon-masonry {
        column-count: 1;
        column-gap: 1rem;
    }
    @media (min-width: 640px) { .neon-masonry { column-count: 2; } }
    @media (min-width: 1024px) { .neon-masonry { column-count: 3; } }
    .neon-masonry > * {
        break-inside: avoid;
        margin-bottom: 1rem;
    }
    .neon-nav-link {
        position: relative;
        padding-bottom: 4px;
    }
    .neon-nav-link::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(to right, #ff2d95, #00f0ff);
        box-shadow: 0 0 8px #ff2d9560;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    .neon-nav-link:hover::after { width: 100%; }
    .neon-btn-pink {
        background: transparent;
        border: 2px solid #ff2d95;
        color: #ff2d95;
        box-shadow: 0 0 10px #ff2d9540, inset 0 0 10px #ff2d9515;
        transition: all 0.3s ease;
    }
    .neon-btn-pink:hover {
        background: #ff2d95;
        color: #0a0a0f;
        box-shadow: 0 0 20px #ff2d9580, inset 0 0 20px #ff2d9520;
    }
    .neon-btn-cyan {
        background: transparent;
        border: 2px solid #00f0ff;
        color: #00f0ff;
        box-shadow: 0 0 10px #00f0ff40, inset 0 0 10px #00f0ff15;
        transition: all 0.3s ease;
    }
    .neon-btn-cyan:hover {
        background: #00f0ff;
        color: #0a0a0f;
        box-shadow: 0 0 20px #00f0ff80, inset 0 0 20px #00f0ff20;
    }
    @keyframes neon-flicker {
        0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% { opacity: 1; }
        20%, 24%, 55% { opacity: 0.6; }
    }
    .neon-flicker { animation: neon-flicker 4s infinite; }
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
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $site['hero_image'] ?? $site['cover_photo'] ?? '/images/templates/neon/hero.jpg' }}');"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-purple-950/40 to-black/90"></div>

    {{-- Subtle grid overlay --}}
    <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 60px 60px;"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-24 md:h-32 mx-auto mb-10" style="filter: drop-shadow(0 0 15px rgba(255,45,149,0.4));">
        @endif

        <div class="w-48 mx-auto mb-8 neon-line"></div>

        <h1 class="neon-heading text-4xl md:text-6xl lg:text-7xl uppercase leading-tight mb-4 neon-flicker" style="color: #ff2d95; text-shadow: 0 0 10px #ff2d95, 0 0 30px #ff2d9580, 0 0 60px #ff2d9540, 0 0 100px #ff2d9520;">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="neon-heading-light text-lg md:text-xl neon-glow-cyan tracking-widest uppercase mb-8">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="w-48 mx-auto mt-4 mb-8 neon-line"></div>

        {{-- Open/Closed Banner --}}
        @if($orderingEnabled)
        <div class="mb-10">
            <span x-show="isRestaurantOpen" class="inline-block px-8 py-2 neon-heading-light text-xs tracking-[0.3em] uppercase neon-glow-cyan border neon-border-cyan rounded-sm">
                Open &mdash; Order Online
            </span>
            <span x-show="!isRestaurantOpen" x-cloak class="inline-block px-8 py-2 neon-heading-light text-xs tracking-[0.3em] uppercase text-gray-500 border border-gray-700 rounded-sm">
                Currently Closed
                <template x-if="todayHours">
                    <span class="neon-glow-pink"> | <span x-text="todayHours"></span></span>
                </template>
            </span>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-5">
            <a href="#full-menu" class="neon-btn-pink px-10 py-3 neon-heading text-xs tracking-[0.3em] uppercase rounded-sm">
                View Menu
            </a>
            <a href="#contact" class="neon-btn-cyan px-10 py-3 neon-heading text-xs tracking-[0.3em] uppercase rounded-sm">
                Find Us
            </a>
        </div>
    </div>

    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2" style="color: #00f0ff60;">
        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- Neon Line --}}
<div class="neon-line-pink"></div>

{{-- Sticky Navigation --}}
<nav class="bg-black/90 backdrop-blur-md border-b border-white/5 sticky top-0 z-30">
    <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center items-center gap-8 md:gap-14 py-4 neon-heading-light text-xs tracking-[0.25em] uppercase text-gray-400">
            <li><a href="#full-menu" class="neon-nav-link hover:text-pink-400 transition">Menu</a></li>
            @if(!empty($site['reservations']['enabled']))
            <li><a href="#reservations" class="neon-nav-link hover:text-cyan-400 transition">Reservations</a></li>
            @endif
            @if(!empty($site['gallery'] ?? $site['settings']['gallery'] ?? null))
            <li><a href="#gallery" class="neon-nav-link hover:text-pink-400 transition">Gallery</a></li>
            @endif
            <li><a href="#hours" class="neon-nav-link hover:text-cyan-400 transition">Hours</a></li>
            <li><a href="#contact" class="neon-nav-link hover:text-pink-400 transition">Contact</a></li>
        </ul>
    </div>
</nav>

{{-- Features Strip --}}
@php $features = $site['settings']['features'] ?? $site['features'] ?? []; @endphp
@if(!empty($features))
<section class="bg-black py-5 border-b border-white/5">
    <div class="max-w-6xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-10 gap-y-3 neon-heading-light text-xs tracking-[0.2em] uppercase">
            @foreach($features as $index => $feature)
            <span class="flex items-center gap-2 {{ $index % 2 === 0 ? 'neon-glow-pink' : 'neon-glow-cyan' }}">
                <span>&#9670;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About --}}
<section class="bg-[#0a0a0f] py-24 md:py-32">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="neon-heading text-2xl md:text-3xl uppercase neon-glow-cyan mb-8">Welcome</h2>
        <div class="w-24 mx-auto mb-8 neon-line"></div>
        @if(!empty($site['about']))
        <p class="text-gray-400 text-lg md:text-xl leading-relaxed neon-glow-pink-subtle">{{ $site['about'] }}</p>
        @elseif(!empty($site['tagline']))
        <p class="text-gray-400 text-lg md:text-xl leading-relaxed neon-glow-pink-subtle">{{ $site['tagline'] }}</p>
        @endif
    </div>
</section>

<div class="neon-line-cyan"></div>

{{-- Menu Section --}}
@include('samples.partials.menu-section')

<div class="neon-line-pink"></div>

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
<section class="bg-[#0a0a0f] py-24 md:py-32" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="neon-heading text-2xl md:text-3xl uppercase neon-glow-pink mb-10">Hours</h2>
        <div class="w-24 mx-auto mb-10 neon-line"></div>
        <div class="inline-block text-left">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-center py-3 border-b border-white/5 {{ $day === $today ? '' : '' }}">
                <span class="neon-heading-light text-xs tracking-[0.2em] w-32 uppercase {{ $day === $today ? 'neon-glow-cyan' : 'text-gray-500' }}">{{ $day }}</span>
                <span class="flex-1 mx-4"></span>
                <span class="text-sm {{ $day === $today ? 'neon-glow-pink' : 'text-gray-400' }} {{ strtolower($time) === 'closed' ? 'text-gray-700' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>

        @if(!empty($site['holiday_hours']))
        <div class="mt-10 max-w-md mx-auto">
            <p class="neon-heading-light text-xs tracking-[0.3em] uppercase neon-glow-cyan mb-4">Special Hours</p>
            @foreach($site['holiday_hours'] as $holiday)
            <div class="flex justify-between text-sm text-gray-600 py-1">
                <span>{{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}@if(!empty($holiday['label'])) &mdash; {{ $holiday['label'] }}@endif</span>
                <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'text-gray-700' : '' }}">{{ $holiday['hours'] }}</span>
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
<div class="neon-line-cyan"></div>
<section class="bg-[#0a0a0f] py-24 md:py-32" id="gallery">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="neon-heading text-2xl md:text-3xl uppercase neon-glow-cyan text-center mb-12">Gallery</h2>
        <div class="w-24 mx-auto mb-12 neon-line"></div>
        <div class="neon-masonry">
            @foreach($gallery as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
                $imageCaption = is_array($image) ? ($image['caption'] ?? null) : null;
            @endphp
            <figure class="overflow-hidden neon-card transition duration-500 group">
                <img src="{{ $imageUrl }}" alt="{{ $imageCaption ?: $imageAlt }}" class="w-full block opacity-80 group-hover:opacity-100 group-hover:scale-105 transition duration-500" loading="lazy" style="filter: saturate(0.8); group-hover:filter: saturate(1);">
                @if($imageCaption)
                <figcaption class="px-4 py-3 text-xs uppercase tracking-[0.2em] neon-heading neon-glow-cyan text-center" style="background-color: rgba(10,10,15,0.85);">{{ $imageCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

<div class="neon-line-pink"></div>

{{-- Contact --}}
<section class="bg-[#0a0a0f] py-24 md:py-32" id="contact">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="neon-heading text-2xl md:text-3xl uppercase neon-glow-pink mb-12">Find Us</h2>
        <div class="w-24 mx-auto mb-12 neon-line"></div>

        @if(!empty($site['address']))
        <div class="mb-10">
            <p class="neon-heading-light text-xs tracking-[0.3em] uppercase neon-glow-cyan mb-3">Address</p>
            <p class="text-gray-300 text-lg md:text-xl">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
            @if(!empty($site['secondary_cta_url']))
            <a href="{{ $site['secondary_cta_url'] }}" target="_blank" class="inline-block mt-3 neon-heading-light text-xs tracking-[0.3em] uppercase neon-glow-pink hover:text-pink-300 transition">
                Get Directions &rarr;
            </a>
            @endif
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-10">
            @if(!empty($site['phone']))
            <div>
                <p class="neon-heading-light text-xs tracking-[0.3em] uppercase neon-glow-cyan mb-2">Phone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-gray-300 text-lg hover:text-pink-400 transition">{{ $site['phone'] }}</a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="neon-heading-light text-xs tracking-[0.3em] uppercase neon-glow-cyan mb-2">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="text-gray-300 text-lg hover:text-pink-400 transition">{{ $site['email'] }}</a>
            </div>
            @endif
        </div>

        @if(!empty($site['social_links']))
        <div class="flex justify-center items-center gap-6 mb-10">
            @foreach($site['social_links'] as $platform => $url)
            @if($url)
            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-gray-600 hover:text-pink-400 transition" style="filter: drop-shadow(0 0 4px #ff2d9540);" aria-label="{{ ucfirst($platform) }}">
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
        <a href="#full-menu" class="neon-btn-pink px-12 py-4 neon-heading text-xs tracking-[0.3em] uppercase rounded-sm inline-block">
            Order Now
        </a>
        @elseif(!empty($site['phone']))
        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="neon-btn-cyan px-12 py-4 neon-heading text-xs tracking-[0.3em] uppercase rounded-sm inline-block">
            Call Us
        </a>
        @endif
    </div>
</section>

{{-- Standard Partials --}}
<div class="bg-[#0a0a0f]">
    @include('samples.partials.reservations-section')
    @include('samples.partials.catering-section')
    @include('samples.partials.google-reviews-section')
    @include('samples.partials.sister-sites-section')
</div>

{{-- Footer --}}
<footer class="bg-black border-t border-white/5 py-8">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="w-24 mx-auto mb-4 neon-line"></div>
        <p class="neon-heading-light text-xs tracking-[0.2em] uppercase text-gray-700">
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
