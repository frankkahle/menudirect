@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
@endphp

{{-- Google Font + Global dark styling for pub aesthetic --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&display=swap');
    body { background-color: #0a1a10; }
    .pub-heading { font-family: 'Playfair Display', Georgia, serif; }
    .pub-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: #b87333;
    }
    .pub-divider::before,
    .pub-divider::after {
        content: '';
        height: 2px;
        flex: 1;
        max-width: 100px;
        background: linear-gradient(to right, transparent, #b87333, transparent);
    }
    .pub-ornament {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        color: #b87333;
        font-size: 0.7rem;
        letter-spacing: 0.15em;
    }
    .pub-ornament::before,
    .pub-ornament::after {
        content: '';
        height: 1px;
        width: 40px;
        background: #b87333;
        opacity: 0.5;
    }
    .pub-card {
        background-color: #112218;
        border: 2px solid rgba(184, 115, 51, 0.25);
    }
    .pub-card-inner {
        border: 1px solid rgba(184, 115, 51, 0.12);
        margin: 6px;
        padding: 1.5rem;
    }
    .pub-dots {
        flex: 1;
        border-bottom: 2px dotted rgba(184, 115, 51, 0.3);
        margin: 0 0.75rem;
        transform: translateY(-0.3rem);
    }
    .pub-masonry {
        column-count: 1;
        column-gap: 1rem;
    }
    @media (min-width: 640px) { .pub-masonry { column-count: 2; } }
    @media (min-width: 1024px) { .pub-masonry { column-count: 3; } }
    .pub-masonry > * {
        break-inside: avoid;
        margin-bottom: 1rem;
    }
    .pub-nav-link {
        position: relative;
        padding-bottom: 2px;
    }
    .pub-nav-link::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 0;
        height: 2px;
        background-color: #b87333;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    .pub-nav-link:hover::after { width: 100%; }
    .pub-thick-border {
        border: 3px solid rgba(184, 115, 51, 0.35);
        padding: 3px;
    }
    .pub-thick-border-inner {
        border: 1px solid rgba(184, 115, 51, 0.2);
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
<div class="bg-[#112218] border-b border-[#b87333]/30 py-2 text-center text-xs tracking-widest uppercase" style="color: #f5f0e1;">
    <span style="color: #b87333;">&#9830;</span>
    <span class="mx-3">{{ $announcement['message'] }}</span>
    <span style="color: #b87333;">&#9830;</span>
</div>
@endforeach
@endif

{{-- Full-Viewport Hero --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden" style="background-color: #0a1a10;">
    @if(!empty($coverPhoto))
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $coverPhoto }}');"></div>
    <div class="absolute inset-0" style="background-color: rgba(10, 26, 16, 0.85);"></div>
    @else
    <div class="absolute inset-0" style="background: radial-gradient(ellipse at 30% 50%, #14532d 0%, #0a1a10 70%);"></div>
    @endif

    {{-- Vignette --}}
    <div class="absolute inset-0" style="background: radial-gradient(ellipse at center, transparent 0%, rgba(10, 26, 16, 0.8) 100%);"></div>

    <div class="relative z-10 max-w-3xl mx-auto px-6 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-28 md:h-36 mx-auto mb-10 opacity-95">
        @endif

        <div class="pub-ornament mb-6">
            <span>&#9830; &#9830; &#9830;</span>
        </div>

        <h1 class="pub-heading text-5xl md:text-7xl lg:text-8xl font-bold mb-6 tracking-wide" style="color: #f5f0e1;">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="pub-heading italic text-xl md:text-2xl font-light tracking-wide mb-10" style="color: #b87333;">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="pub-divider mt-8 mb-12">
            <span style="color: #b87333; font-size: 1.25rem;">&#9827;</span>
        </div>

        <div class="flex flex-col sm:flex-row justify-center items-center gap-6">
            <a href="#full-menu" class="inline-block px-10 py-3 text-sm tracking-[0.3em] uppercase transition-all duration-500" style="border: 2px solid #b87333; color: #b87333; background: transparent;" onmouseover="this.style.backgroundColor='#b87333';this.style.color='#0a1a10';" onmouseout="this.style.backgroundColor='transparent';this.style.color='#b87333';">
                View Menu
            </a>
            <a href="#reservations" class="inline-block px-10 py-3 text-sm tracking-[0.3em] uppercase transition-all duration-500" style="border: 2px solid rgba(245,240,225,0.3); color: #f5f0e1;" onmouseover="this.style.backgroundColor='#f5f0e1';this.style.color='#0a1a10';" onmouseout="this.style.backgroundColor='transparent';this.style.color='#f5f0e1';">
                Reservations
            </a>
        </div>
    </div>

    {{-- Scroll indicator --}}
    <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2" style="color: rgba(184,115,51,0.7);">
        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- Sticky Navigation --}}
<nav class="sticky top-0 z-30 backdrop-blur-sm" style="background-color: rgba(10,26,16,0.95); border-top: 2px solid #b87333; border-bottom: 2px solid #b87333;">
    <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center items-center gap-8 md:gap-14 py-5 text-xs md:text-sm tracking-[0.3em] uppercase" style="color: #f5f0e1;">
            <li><a href="#full-menu" class="pub-nav-link hover:opacity-80 transition">Menu</a></li>
            <li><a href="#reservations" class="pub-nav-link hover:opacity-80 transition">Reservations</a></li>
            @if(!empty($site['settings']['gallery'] ?? $site['gallery'] ?? null))
            <li><a href="#gallery" class="pub-nav-link hover:opacity-80 transition">Gallery</a></li>
            @endif
            <li><a href="#contact" class="pub-nav-link hover:opacity-80 transition">Contact</a></li>
        </ul>
    </div>
</nav>

{{-- Ordering Status Banner --}}
@if($orderingEnabled)
<section x-show="isRestaurantOpen" style="background-color: #112218; border-bottom: 1px solid rgba(184,115,51,0.25);" class="py-3">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <span class="text-xs tracking-[0.3em] uppercase" style="color: #b87333;">
            Online Ordering Available
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-3" style="color: #14532d;">|</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-3" style="color: #14532d;">|</span> Delivery
            @endif
        </span>
    </div>
</section>
<section x-show="!isRestaurantOpen" x-cloak style="background-color: #112218; border-bottom: 1px solid rgba(184,115,51,0.25);" class="py-3">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <span class="text-xs tracking-[0.3em] uppercase" style="color: rgba(245,240,225,0.5);">
            Currently Closed
            <template x-if="todayHours">
                <span><span class="mx-3" style="color: #14532d;">|</span> Today: <span x-text="todayHours" style="color: #b87333;"></span></span>
            </template>
        </span>
    </div>
</section>
@endif

{{-- Features Strip --}}
@if(!empty($site['settings']['features'] ?? $site['features'] ?? null))
@php $features = $site['settings']['features'] ?? $site['features']; @endphp
<section class="py-16" style="background-color: #0d1f14;">
    <div class="max-w-5xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-12 gap-y-6 text-xs tracking-[0.3em] uppercase" style="color: rgba(245,240,225,0.6);">
            @foreach($features as $feature)
            <span class="flex items-center">
                <span class="mr-3" style="color: #b87333;">&#9830;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Menu Section --}}
@include('samples.partials.menu-section')

{{-- Hours Section --}}
@if(!empty($site['hours']))
<section class="py-24 md:py-32" style="background-color: #0d1f14; border-top: 2px solid rgba(184,115,51,0.2);" id="hours">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <div class="pub-divider mb-6">
            <span class="text-xs tracking-[0.4em] uppercase" style="color: #b87333;">Hours</span>
        </div>
        <h2 class="pub-heading text-4xl md:text-5xl font-bold mb-14" style="color: #f5f0e1;">Opening Hours</h2>

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

        <div class="pub-card max-w-md mx-auto">
            <div class="pub-card-inner">
                @foreach($sortedHours as $day => $time)
                <div class="flex items-baseline py-2 {{ $day === $today ? '' : '' }}" style="color: {{ $day === $today ? '#b87333' : '#f5f0e1' }};">
                    <span class="text-sm tracking-wide w-28 text-left pub-heading">{{ $day }}</span>
                    <span class="pub-dots mx-3" style="min-width: 3rem;"></span>
                    <span class="text-sm {{ strtolower($time) === 'closed' ? 'italic opacity-50' : '' }}">{{ $time }}</span>
                </div>
                @endforeach
            </div>
        </div>

        @if(!empty($site['holiday_hours']))
        <div class="mt-10 max-w-md mx-auto">
            <p class="text-xs tracking-[0.3em] uppercase mb-3" style="color: rgba(184,115,51,0.7);">Special Hours</p>
            @foreach($site['holiday_hours'] as $holiday)
            <div class="flex justify-between text-sm py-1" style="color: rgba(245,240,225,0.5);">
                <span class="pub-heading">
                    {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                    @if(!empty($holiday['label'])) &mdash; {{ $holiday['label'] }} @endif
                </span>
                <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'italic opacity-50' : '' }}">
                    {{ $holiday['hours'] }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Gallery --}}
@php $gallery = $site['settings']['gallery'] ?? $site['gallery'] ?? null; @endphp
@if(!empty($gallery))
<section class="py-24 md:py-32" style="background-color: #0a1a10;" id="gallery">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16">
            <div class="pub-divider mb-6">
                <span class="text-xs tracking-[0.4em] uppercase" style="color: #b87333;">Gallery</span>
            </div>
            <h2 class="pub-heading text-4xl md:text-5xl font-bold" style="color: #f5f0e1;">A Glimpse Inside</h2>
        </div>

        <div class="pub-masonry">
            @foreach($gallery as $index => $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
                $imageCaption = is_array($image) ? ($image['caption'] ?? null) : null;
            @endphp
            <figure class="pub-thick-border group overflow-hidden">
                <div class="pub-thick-border-inner overflow-hidden">
                    <img src="{{ $imageUrl }}"
                         alt="{{ $imageCaption ?: $imageAlt }}"
                         class="w-full block opacity-80 group-hover:opacity-100 transition duration-700 group-hover:scale-105"
                         loading="lazy">
                </div>
                @if($imageCaption)
                <figcaption class="px-4 py-3 text-sm text-center pub-heading" style="color: #f5f0e1; background-color: #0d1f14;">{{ $imageCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Location / Contact --}}
<section class="py-24 md:py-32" style="background-color: #0d1f14; border-top: 2px solid rgba(184,115,51,0.2);" id="contact">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <div class="pub-divider mb-6">
            <span class="text-xs tracking-[0.4em] uppercase" style="color: #b87333;">Visit</span>
        </div>
        <h2 class="pub-heading text-4xl md:text-5xl font-bold mb-14" style="color: #f5f0e1;">Find Us</h2>

        {{-- Address --}}
        @if(!empty($site['address']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase mb-3" style="color: #b87333;">Address</p>
            <p class="pub-heading text-lg md:text-xl leading-relaxed" style="color: #f5f0e1;">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
            @if(!empty($site['secondary_cta_url']))
            <a href="{{ $site['secondary_cta_url'] }}" target="_blank" class="inline-block mt-3 text-xs tracking-[0.3em] uppercase transition" style="color: #b87333;" onmouseover="this.style.opacity='0.7';" onmouseout="this.style.opacity='1';">
                Get Directions &rarr;
            </a>
            @endif
        </div>
        @endif

        {{-- Phone & Email --}}
        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase mb-2" style="color: #b87333;">Telephone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="pub-heading text-lg transition" style="color: #f5f0e1;" onmouseover="this.style.color='#b87333';" onmouseout="this.style.color='#f5f0e1';">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase mb-2" style="color: #b87333;">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="pub-heading text-lg transition" style="color: #f5f0e1;" onmouseover="this.style.color='#b87333';" onmouseout="this.style.color='#f5f0e1';">
                    {{ $site['email'] }}
                </a>
            </div>
            @endif
        </div>

        {{-- Social Links --}}
        @if(!empty($site['social_links']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase mb-4" style="color: #b87333;">Follow</p>
            <div class="flex justify-center items-center gap-6">
                @foreach($site['social_links'] as $platform => $url)
                @if($url)
                <a href="{{ $url }}" target="_blank" rel="noopener" class="transition" style="color: rgba(245,240,225,0.5);" onmouseover="this.style.color='#b87333';" onmouseout="this.style.color='rgba(245,240,225,0.5)';" aria-label="{{ ucfirst($platform) }}">
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

        {{-- CTA --}}
        <div class="pt-6">
            @if($orderingEnabled)
            <a href="#full-menu" class="inline-block px-12 py-4 text-sm tracking-[0.3em] uppercase transition-all duration-500" style="border: 2px solid #b87333; color: #b87333;" onmouseover="this.style.backgroundColor='#b87333';this.style.color='#0a1a10';" onmouseout="this.style.backgroundColor='transparent';this.style.color='#b87333';">
                Begin Your Order
            </a>
            @elseif(!empty($site['phone']))
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-block px-12 py-4 text-sm tracking-[0.3em] uppercase transition-all duration-500" style="border: 2px solid #b87333; color: #b87333;" onmouseover="this.style.backgroundColor='#b87333';this.style.color='#0a1a10';" onmouseout="this.style.backgroundColor='transparent';this.style.color='#b87333';">
                Call to Reserve
            </a>
            @endif
        </div>
    </div>
</section>

{{-- Reservations / Catering / Reviews / Sister Sites --}}
<div style="background-color: #0a1a10;">
    @include('samples.partials.reservations-section')
    @include('samples.partials.catering-section')
    @include('samples.partials.google-reviews-section')
    @include('samples.partials.sister-sites-section')
</div>

{{-- Footer --}}
<footer class="py-10" style="background-color: #0a1a10; border-top: 2px solid rgba(184,115,51,0.25);">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="pub-divider mb-4">
            <span style="color: #b87333; font-size: 1.25rem;">&#9827;</span>
        </div>
        <p class="pub-heading text-sm italic" style="color: rgba(245,240,225,0.4);">
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
