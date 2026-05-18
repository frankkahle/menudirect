@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
    $accent = $site['colors']['primary'] ?? '#ef4444';
@endphp

{{-- Google Font + Global dark styling for urban/industrial aesthetic --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap');
    body { background-color: #111111; }
    .urban-heading {
        font-family: 'Bebas Neue', Impact, 'Arial Narrow', sans-serif;
        letter-spacing: 0.05em;
    }
    .urban-accent { color: {{ $accent }}; }
    .urban-accent-bg { background-color: {{ $accent }}; }
    .urban-accent-border { border-color: {{ $accent }}; }
    .urban-card {
        background-color: #1a1a1a;
        border-left: 4px solid {{ $accent }};
    }
    .urban-stripe {
        background: repeating-linear-gradient(
            -45deg,
            transparent,
            transparent 8px,
            rgba(255,255,255,0.02) 8px,
            rgba(255,255,255,0.02) 16px
        );
    }
    .urban-grid-bg {
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
    }
    .urban-nav-link {
        position: relative;
        padding-bottom: 4px;
    }
    .urban-nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 0;
        height: 3px;
        background-color: {{ $accent }};
        transition: width 0.3s ease;
    }
    .urban-nav-link:hover::after { width: 100%; }
    .urban-masonry {
        column-count: 1;
        column-gap: 0.5rem;
    }
    @media (min-width: 640px) { .urban-masonry { column-count: 2; } }
    @media (min-width: 1024px) { .urban-masonry { column-count: 3; } }
    .urban-masonry > * {
        break-inside: avoid;
        margin-bottom: 0.5rem;
    }
    .urban-glitch-line {
        height: 3px;
        background: {{ $accent }};
        width: 60px;
    }
    .urban-tag {
        display: inline-block;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 0.7rem;
        letter-spacing: 0.15em;
        padding: 0.2rem 0.6rem;
        border: 1px solid rgba(255,255,255,0.15);
        color: rgba(255,255,255,0.6);
    }
</style>

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

{{-- Announcements Bar --}}
@if(!empty($site['announcements']))
@foreach($site['announcements'] as $announcement)
<div class="py-2 text-center text-xs tracking-[0.3em] uppercase font-bold" style="background-color: {{ $accent }}; color: #000;">
    {{ $announcement['message'] }}
</div>
@endforeach
@endif

{{-- Full-Viewport Hero — Bold typography, minimal imagery --}}
<section class="relative min-h-screen flex items-end overflow-hidden urban-grid-bg" style="background-color: #111111;">
    @if(!empty($coverPhoto))
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $coverPhoto }}');"></div>
    <div class="absolute inset-0" style="background: linear-gradient(to bottom, rgba(17,17,17,0.6) 0%, rgba(17,17,17,0.95) 70%, #111111 100%);"></div>
    @else
    <div class="absolute inset-0" style="background: linear-gradient(135deg, #111111 0%, #1a1a1a 50%, #111111 100%);"></div>
    @endif

    <div class="relative z-10 w-full max-w-7xl mx-auto px-6 pb-20 md:pb-32 pt-40">
        <div class="grid md:grid-cols-12 gap-6 items-end">
            {{-- Left: Logo + tagline --}}
            <div class="md:col-span-4">
                @if(!empty($site['logo']))
                <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-16 md:h-20 mb-6 opacity-90">
                @endif
                @if(!empty($site['tagline']))
                <p class="text-sm md:text-base font-light tracking-wide mb-6" style="color: rgba(255,255,255,0.5);">
                    {{ $site['tagline'] }}
                </p>
                @endif
                <div class="urban-glitch-line mb-6"></div>
                <div class="flex gap-4">
                    <a href="#full-menu" class="inline-block px-8 py-3 text-xs tracking-[0.3em] uppercase font-bold transition-all duration-300" style="background-color: {{ $accent }}; color: #000;" onmouseover="this.style.opacity='0.8';" onmouseout="this.style.opacity='1';">
                        Menu
                    </a>
                    <a href="#reservations" class="inline-block px-8 py-3 text-xs tracking-[0.3em] uppercase font-bold transition-all duration-300 border-2" style="border-color: rgba(255,255,255,0.3); color: #fff;" onmouseover="this.style.borderColor='#fff';" onmouseout="this.style.borderColor='rgba(255,255,255,0.3)';">
                        Reserve
                    </a>
                </div>
            </div>

            {{-- Right: Giant name --}}
            <div class="md:col-span-8 text-right">
                <h1 class="urban-heading text-7xl sm:text-8xl md:text-9xl lg:text-[12rem] leading-[0.85] font-normal text-white" style="text-shadow: 0 0 80px rgba(0,0,0,0.5);">
                    {{ $site['name'] }}
                </h1>
            </div>
        </div>
    </div>

    {{-- Geometric accent bar at bottom --}}
    <div class="absolute bottom-0 left-0 right-0 h-1" style="background-color: {{ $accent }};"></div>
</section>

{{-- Sticky Navigation --}}
<nav class="sticky top-0 z-30 backdrop-blur-md" style="background-color: rgba(17,17,17,0.95); border-bottom: 1px solid rgba(255,255,255,0.08);">
    <div class="max-w-7xl mx-auto px-6">
        <ul class="flex flex-wrap justify-start items-center gap-8 md:gap-10 py-4 text-xs tracking-[0.25em] uppercase font-bold text-white">
            <li><a href="#full-menu" class="urban-nav-link transition">Menu</a></li>
            <li><a href="#hours" class="urban-nav-link transition">Hours</a></li>
            @if(!empty($site['settings']['gallery'] ?? $site['gallery'] ?? null))
            <li><a href="#gallery" class="urban-nav-link transition">Gallery</a></li>
            @endif
            <li><a href="#contact" class="urban-nav-link transition">Contact</a></li>
            <li><a href="#reservations" class="urban-nav-link transition">Reserve</a></li>
        </ul>
    </div>
</nav>

{{-- Ordering Status Banner --}}
@if($orderingEnabled)
<section x-show="isRestaurantOpen" class="py-3" style="background-color: #1a1a1a; border-bottom: 1px solid rgba(255,255,255,0.06);">
    <div class="max-w-7xl mx-auto px-6 flex items-center gap-4">
        <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: {{ $accent }};"></div>
        <span class="text-xs tracking-[0.3em] uppercase font-bold text-white">
            Ordering Open
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-2 opacity-30">/</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-2 opacity-30">/</span> Delivery
            @endif
        </span>
    </div>
</section>
<section x-show="!isRestaurantOpen" x-cloak class="py-3" style="background-color: #1a1a1a; border-bottom: 1px solid rgba(255,255,255,0.06);">
    <div class="max-w-7xl mx-auto px-6 flex items-center gap-4">
        <div class="w-2 h-2 rounded-full bg-gray-600"></div>
        <span class="text-xs tracking-[0.3em] uppercase font-bold" style="color: rgba(255,255,255,0.4);">
            Currently Closed
            <template x-if="todayHours">
                <span><span class="mx-2 opacity-30">/</span> Today: <span x-text="todayHours" class="urban-accent"></span></span>
            </template>
        </span>
    </div>
</section>
@endif

{{-- Features Strip --}}
@if(!empty($site['settings']['features'] ?? $site['features'] ?? null))
@php $features = $site['settings']['features'] ?? $site['features']; @endphp
<section class="py-10 urban-stripe" style="background-color: #151515;">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex flex-wrap gap-3">
            @foreach($features as $feature)
            <span class="urban-tag">{{ $feature }}</span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Menu Section --}}
@include('samples.partials.menu-section')

{{-- Hours Section --}}
@if(!empty($site['hours']))
<section class="py-24 md:py-32 urban-grid-bg" style="background-color: #111111;" id="hours">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-12 gap-8 items-start">
            {{-- Left: Title --}}
            <div class="md:col-span-4">
                <div class="urban-glitch-line mb-4"></div>
                <h2 class="urban-heading text-6xl md:text-7xl text-white leading-none">HOURS</h2>
            </div>

            {{-- Right: Hours grid --}}
            <div class="md:col-span-8">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-0">
                    @foreach($sortedHours as $day => $time)
                    <div class="flex items-center justify-between py-4 px-4 {{ $day === $today ? 'urban-card' : '' }}" style="{{ $day !== $today ? 'border-bottom: 1px solid rgba(255,255,255,0.06);' : '' }}">
                        <span class="urban-heading text-lg {{ $day === $today ? 'text-white' : '' }}" style="{{ $day !== $today ? 'color: rgba(255,255,255,0.6);' : '' }}">{{ strtoupper($day) }}</span>
                        <span class="text-sm font-bold {{ strtolower($time) === 'closed' ? 'opacity-30' : '' }}" style="color: {{ $day === $today ? $accent : 'rgba(255,255,255,0.8)' }};">{{ $time }}</span>
                    </div>
                    @endforeach
                </div>

                @if(!empty($site['holiday_hours']))
                <div class="mt-8 pt-6" style="border-top: 1px solid rgba(255,255,255,0.06);">
                    <p class="urban-heading text-lg mb-3 urban-accent">SPECIAL HOURS</p>
                    @foreach($site['holiday_hours'] as $holiday)
                    <div class="flex justify-between text-sm py-2" style="color: rgba(255,255,255,0.5);">
                        <span>
                            {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                            @if(!empty($holiday['label'])) &mdash; {{ $holiday['label'] }} @endif
                        </span>
                        <span class="font-bold {{ strtolower($holiday['hours']) === 'closed' ? 'opacity-30' : '' }}">{{ $holiday['hours'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@php $gallery = $site['settings']['gallery'] ?? $site['gallery'] ?? null; @endphp
@if(!empty($gallery))
<section class="py-24 md:py-32" style="background-color: #151515;" id="gallery">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-end justify-between mb-12">
            <div>
                <div class="urban-glitch-line mb-4"></div>
                <h2 class="urban-heading text-6xl md:text-7xl text-white leading-none">GALLERY</h2>
            </div>
            <span class="hidden md:block text-xs tracking-[0.3em] uppercase font-bold" style="color: rgba(255,255,255,0.3);">{{ count($gallery) }} Photos</span>
        </div>

        <div class="urban-masonry">
            @foreach($gallery as $index => $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
                $imageCaption = is_array($image) ? ($image['caption'] ?? null) : null;
            @endphp
            <figure class="overflow-hidden group relative">
                <img src="{{ $imageUrl }}"
                     alt="{{ $imageCaption ?: $imageAlt }}"
                     class="w-full block opacity-75 group-hover:opacity-100 transition duration-500 group-hover:scale-105"
                     loading="lazy">
                <div class="absolute bottom-0 left-0 right-0 h-1 transition-all duration-300 opacity-0 group-hover:opacity-100" style="background-color: {{ $accent }};"></div>
                @if($imageCaption)
                <figcaption class="px-3 py-2 text-xs tracking-[0.15em] uppercase font-bold text-white/70" style="background-color: #1a1a1a;">{{ $imageCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Location / Contact --}}
<section class="py-24 md:py-32 urban-grid-bg" style="background-color: #111111;" id="contact">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid md:grid-cols-12 gap-12">
            {{-- Left: Title + address --}}
            <div class="md:col-span-5">
                <div class="urban-glitch-line mb-4"></div>
                <h2 class="urban-heading text-6xl md:text-7xl text-white leading-none mb-8">FIND US</h2>

                @if(!empty($site['address']))
                <p class="text-white text-lg font-light leading-relaxed mb-4">
                    {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
                </p>
                @if(!empty($site['secondary_cta_url']))
                <a href="{{ $site['secondary_cta_url'] }}" target="_blank" class="inline-block text-xs tracking-[0.3em] uppercase font-bold transition urban-accent" onmouseover="this.style.opacity='0.7';" onmouseout="this.style.opacity='1';">
                    Directions &rarr;
                </a>
                @endif
                @endif
            </div>

            {{-- Right: Phone, email, social --}}
            <div class="md:col-span-7">
                <div class="grid sm:grid-cols-2 gap-8">
                    @if(!empty($site['phone']))
                    <div class="urban-card p-6">
                        <p class="urban-heading text-sm mb-2 urban-accent">PHONE</p>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-white text-xl font-light transition hover:opacity-70">
                            {{ $site['phone'] }}
                        </a>
                    </div>
                    @endif
                    @if(!empty($site['email']))
                    <div class="urban-card p-6">
                        <p class="urban-heading text-sm mb-2 urban-accent">EMAIL</p>
                        <a href="mailto:{{ $site['email'] }}" class="text-white text-lg font-light transition hover:opacity-70 break-all">
                            {{ $site['email'] }}
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Social Links --}}
                @if(!empty($site['social_links']))
                <div class="mt-8 flex items-center gap-6">
                    <span class="text-xs tracking-[0.3em] uppercase font-bold" style="color: rgba(255,255,255,0.3);">Follow</span>
                    @foreach($site['social_links'] as $platform => $url)
                    @if($url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="transition" style="color: rgba(255,255,255,0.4);" onmouseover="this.style.color='{{ $accent }}';" onmouseout="this.style.color='rgba(255,255,255,0.4)';" aria-label="{{ ucfirst($platform) }}">
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
                @endif

                {{-- CTA --}}
                <div class="mt-10">
                    @if($orderingEnabled)
                    <a href="#full-menu" class="inline-block px-10 py-4 text-xs tracking-[0.3em] uppercase font-bold transition-all duration-300" style="background-color: {{ $accent }}; color: #000;" onmouseover="this.style.opacity='0.8';" onmouseout="this.style.opacity='1';">
                        Order Now
                    </a>
                    @elseif(!empty($site['phone']))
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-block px-10 py-4 text-xs tracking-[0.3em] uppercase font-bold transition-all duration-300 border-2" style="border-color: {{ $accent }}; color: {{ $accent }};" onmouseover="this.style.backgroundColor='{{ $accent }}';this.style.color='#000';" onmouseout="this.style.backgroundColor='transparent';this.style.color='{{ $accent }}';">
                        Call Us
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Reservations / Catering / Reviews / Sister Sites --}}
<div style="background-color: #111111;">
    @include('samples.partials.reservations-section')
    @include('samples.partials.catering-section')
    @include('samples.partials.google-reviews-section')
    @include('samples.partials.sister-sites-section')
</div>

{{-- Footer --}}
<footer class="py-10" style="background-color: #111111; border-top: 1px solid rgba(255,255,255,0.06);">
    <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <p class="text-xs font-bold tracking-[0.2em] uppercase" style="color: rgba(255,255,255,0.25);">
            &copy; {{ date('Y') }} {{ $site['name'] }}
        </p>
        <div class="h-1 w-8" style="background-color: {{ $accent }};"></div>
    </div>
</footer>

@include('samples.partials.cart-ui')

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
