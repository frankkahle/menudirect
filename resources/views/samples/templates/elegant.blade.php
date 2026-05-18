@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap');
    body { background-color: #faf8f5; }
    .elegant-heading { font-family: 'Playfair Display', Georgia, serif; }
    .elegant-gold { color: #c9a96e; }
    .elegant-gold-bg { background-color: #c9a96e; }
    .elegant-charcoal { color: #2d2d2d; }
    .elegant-charcoal-bg { background-color: #2d2d2d; }
    .elegant-ivory { color: #faf8f5; }
    .elegant-ivory-bg { background-color: #faf8f5; }
    .elegant-separator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }
    .elegant-separator::before,
    .elegant-separator::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 100px;
        background: linear-gradient(to right, transparent, #c9a96e, transparent);
    }
    .elegant-nav-link {
        position: relative;
        padding-bottom: 4px;
    }
    .elegant-nav-link::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: 0;
        width: 0;
        height: 1px;
        background-color: #c9a96e;
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    .elegant-nav-link:hover::after { width: 100%; }
</style>

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

{{-- Full-Viewport Hero --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden" style="background-color: #2d2d2d;">
    @if($coverPhoto)
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $coverPhoto }}');"></div>
    <div class="absolute inset-0" style="background: rgba(45,45,45,0.78);"></div>
    @endif

    <div class="relative z-10 max-w-2xl mx-auto px-6 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-28 md:h-36 mx-auto mb-10 opacity-95">
        @endif

        <div class="elegant-separator mb-8">
            <span class="text-xs tracking-[0.5em] uppercase elegant-gold">{{ $site['tagline'] ?? 'Fine Dining' }}</span>
        </div>

        <h1 class="elegant-heading text-5xl md:text-7xl lg:text-8xl font-normal elegant-ivory mb-8 tracking-wide leading-tight">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="elegant-heading italic text-lg md:text-2xl text-gray-300 font-normal tracking-wide mb-12">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="mt-10 flex flex-col sm:flex-row justify-center items-center gap-6">
            <a href="#full-menu" class="inline-block border border-[#c9a96e]/60 elegant-gold px-10 py-3 text-sm tracking-[0.3em] uppercase hover:bg-[#c9a96e] hover:text-[#2d2d2d] transition-all duration-500">
                View Menu
            </a>
            <a href="#reservations" class="inline-block border border-gray-400/40 text-gray-300 px-10 py-3 text-sm tracking-[0.3em] uppercase hover:bg-gray-300 hover:text-[#2d2d2d] transition-all duration-500">
                Reservations
            </a>
        </div>
    </div>

    <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 elegant-gold opacity-70">
        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- Sticky Navigation --}}
<nav class="bg-[#2d2d2d] border-y border-[#c9a96e]/20 sticky top-0 z-30">
    <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center items-center gap-8 md:gap-14 py-5 text-xs md:text-sm tracking-[0.3em] uppercase text-gray-300">
            <li><a href="#full-menu" class="elegant-nav-link hover:text-[#c9a96e] transition">Menu</a></li>
            <li><a href="#hours" class="elegant-nav-link hover:text-[#c9a96e] transition">Hours</a></li>
            @if(!empty($site['settings']['gallery']))
            <li><a href="#gallery" class="elegant-nav-link hover:text-[#c9a96e] transition">Gallery</a></li>
            @endif
            <li><a href="#contact" class="elegant-nav-link hover:text-[#c9a96e] transition">Contact</a></li>
        </ul>
    </div>
</nav>

{{-- Features Strip --}}
@if(!empty($site['settings']['features']))
<section class="elegant-charcoal-bg py-10 border-b border-[#c9a96e]/10">
    <div class="max-w-4xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-12 gap-y-4 text-xs tracking-[0.35em] uppercase text-gray-400">
            @foreach($site['settings']['features'] as $feature)
            <span class="flex items-center">
                <span class="elegant-gold mr-3 text-sm">&#9830;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About / Alternating Editorial Section --}}
<section class="elegant-ivory-bg py-24 md:py-32">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="elegant-separator mb-8">
            <span class="text-xs tracking-[0.5em] uppercase elegant-gold">Welcome</span>
        </div>
        @if(!empty($site['about']))
        <p class="elegant-heading text-[#2d2d2d] text-lg md:text-xl leading-relaxed italic font-normal">
            {{ $site['about'] }}
        </p>
        @elseif(!empty($site['tagline']))
        <p class="elegant-heading text-[#2d2d2d] text-lg md:text-xl leading-relaxed italic font-normal">
            {{ $site['tagline'] }}
        </p>
        @endif
    </div>
</section>

{{-- Menu Section --}}
@include('samples.partials.menu-section')

{{-- Hours Section --}}
@if(!empty($site['hours']))
<section class="elegant-ivory-bg py-24 md:py-32" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="elegant-separator mb-8">
            <span class="text-xs tracking-[0.5em] uppercase elegant-gold">Hours</span>
        </div>
        <h2 class="elegant-heading text-4xl md:text-5xl font-normal elegant-charcoal mb-12">Visit Us</h2>

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
        <div class="inline-block text-left elegant-heading">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-baseline py-2 {{ $day === $today ? 'elegant-gold' : 'elegant-charcoal' }}">
                <span class="text-sm tracking-wide w-28">{{ $day }}</span>
                <span class="flex-1 border-b border-dotted border-[#c9a96e]/30 mx-4" style="min-width: 3rem;"></span>
                <span class="text-sm {{ strtolower($time) === 'closed' ? 'text-gray-400 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['settings']['gallery']))
<section class="elegant-charcoal-bg py-24 md:py-32" id="gallery">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-16">
            <div class="elegant-separator mb-8">
                <span class="text-xs tracking-[0.5em] uppercase elegant-gold">Gallery</span>
            </div>
            <h2 class="elegant-heading text-4xl md:text-5xl font-normal elegant-ivory">Our Space</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($site['settings']['gallery'] as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
            @endphp
            <figure class="overflow-hidden group">
                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="w-full h-64 object-cover opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-700" loading="lazy">
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact / Location --}}
<section class="elegant-ivory-bg py-24 md:py-32 border-t border-[#c9a96e]/20" id="contact">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="elegant-separator mb-8">
            <span class="text-xs tracking-[0.5em] uppercase elegant-gold">Contact</span>
        </div>
        <h2 class="elegant-heading text-4xl md:text-5xl font-normal elegant-charcoal mb-14">Find Us</h2>

        @if(!empty($site['address']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase elegant-gold mb-3">Address</p>
            <p class="elegant-heading elegant-charcoal text-lg md:text-xl leading-relaxed">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase elegant-gold mb-2">Telephone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="elegant-heading elegant-charcoal text-lg hover:text-[#c9a96e] transition">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase elegant-gold mb-2">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="elegant-heading elegant-charcoal text-lg hover:text-[#c9a96e] transition">
                    {{ $site['email'] }}
                </a>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- Reservations --}}
@include('samples.partials.reservations-section')

{{-- Cart UI --}}
@include('samples.partials.cart-ui')

{{-- Footer --}}
<footer class="elegant-charcoal-bg border-t border-[#c9a96e]/20 py-10">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="elegant-separator mb-4">
            <span class="elegant-gold text-sm">&#9830;</span>
        </div>
        <p class="elegant-heading text-gray-500 text-sm italic">
            &copy; {{ date('Y') }} {{ $site['name'] }}. All rights reserved.
        </p>
    </div>
</footer>

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
