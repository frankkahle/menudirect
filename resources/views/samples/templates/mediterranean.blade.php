@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&display=swap');
    body { background-color: #fefcf8; }
    .med-heading { font-family: 'Lora', Georgia, serif; }
    .med-blue { color: #1e40af; }
    .med-olive { color: #6b7c3e; }
    .med-terracotta { color: #c4663a; }
    .med-separator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
    }
    .med-separator::before,
    .med-separator::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 80px;
        background: linear-gradient(to right, transparent, #1e40af, transparent);
    }
    .med-tile-pattern {
        background-image:
            radial-gradient(circle at 25% 25%, rgba(30,64,175,0.04) 2px, transparent 2px),
            radial-gradient(circle at 75% 75%, rgba(107,124,62,0.04) 2px, transparent 2px);
        background-size: 40px 40px;
    }
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

{{-- Hero --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden" style="background-color: #1e40af;">
    @if($coverPhoto)
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $coverPhoto }}');"></div>
    <div class="absolute inset-0 bg-[#1e40af]/70"></div>
    @endif

    <div class="relative z-10 max-w-2xl mx-auto px-6 text-center">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-28 md:h-36 mx-auto mb-10">
        @endif

        <p class="text-sm tracking-[0.4em] uppercase text-white/70 mb-6">{{ $site['settings']['cuisine_type'] ?? 'Mediterranean Cuisine' }}</p>

        <h1 class="med-heading text-5xl md:text-7xl lg:text-8xl font-bold text-white mb-8 leading-tight">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="med-heading italic text-lg md:text-2xl text-blue-100 font-normal mb-12">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-6">
            <a href="#full-menu" class="inline-block bg-white text-[#1e40af] px-10 py-3 text-sm tracking-[0.2em] uppercase font-semibold hover:bg-blue-50 transition-all duration-300 rounded-sm">
                View Menu
            </a>
            <a href="#reservations" class="inline-block border border-white/50 text-white px-10 py-3 text-sm tracking-[0.2em] uppercase hover:bg-white/10 transition-all duration-300 rounded-sm">
                Reserve a Table
            </a>
        </div>
    </div>

    <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 text-white/60">
        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>
</section>

{{-- Sticky Nav --}}
<nav class="bg-white border-b border-blue-100 sticky top-0 z-30 shadow-sm">
    <div class="max-w-5xl mx-auto px-6">
        <ul class="flex flex-wrap justify-center items-center gap-8 md:gap-14 py-4 text-xs md:text-sm tracking-[0.2em] uppercase text-gray-500 med-heading">
            <li><a href="#full-menu" class="hover:text-[#1e40af] transition">Menu</a></li>
            <li><a href="#hours" class="hover:text-[#1e40af] transition">Hours</a></li>
            @if(!empty($site['settings']['gallery']))
            <li><a href="#gallery" class="hover:text-[#1e40af] transition">Gallery</a></li>
            @endif
            <li><a href="#contact" class="hover:text-[#1e40af] transition">Contact</a></li>
        </ul>
    </div>
</nav>

{{-- Features Strip --}}
@if(!empty($site['settings']['features']))
<section class="bg-[#f5f0e8] py-8 border-b border-[#6b7c3e]/10">
    <div class="max-w-4xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-10 gap-y-3 text-xs tracking-[0.3em] uppercase text-[#6b7c3e]">
            @foreach($site['settings']['features'] as $feature)
            <span class="flex items-center">
                <span class="med-terracotta mr-3">&#9679;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About --}}
<section class="med-tile-pattern py-24 md:py-32">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="med-separator mb-8">
            <span class="text-xs tracking-[0.4em] uppercase med-blue">Our Story</span>
        </div>
        @if(!empty($site['about']))
        <p class="med-heading text-gray-700 text-lg md:text-xl leading-relaxed italic">
            {{ $site['about'] }}
        </p>
        @elseif(!empty($site['tagline']))
        <p class="med-heading text-gray-700 text-lg md:text-xl leading-relaxed italic">
            {{ $site['tagline'] }}
        </p>
        @endif
    </div>
</section>

{{-- Menu Section --}}
@include('samples.partials.menu-section')

{{-- Hours --}}
@if(!empty($site['hours']))
<section class="bg-[#f5f0e8] py-24 md:py-32" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="med-separator mb-8">
            <span class="text-xs tracking-[0.4em] uppercase med-terracotta">Hours</span>
        </div>
        <h2 class="med-heading text-4xl md:text-5xl font-bold med-blue mb-12">When We're Open</h2>

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
        <div class="inline-block text-left med-heading">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-baseline py-2 {{ $day === $today ? 'med-terracotta font-semibold' : 'text-gray-700' }}">
                <span class="text-sm tracking-wide w-28">{{ $day }}</span>
                <span class="flex-1 border-b border-dotted border-[#1e40af]/20 mx-4" style="min-width: 3rem;"></span>
                <span class="text-sm {{ strtolower($time) === 'closed' ? 'text-gray-400 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['settings']['gallery']))
<section class="bg-white py-24 md:py-32" id="gallery">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-16">
            <div class="med-separator mb-8">
                <span class="text-xs tracking-[0.4em] uppercase med-olive">Gallery</span>
            </div>
            <h2 class="med-heading text-4xl md:text-5xl font-bold med-blue">A Taste of Our World</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($site['settings']['gallery'] as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
            @endphp
            <figure class="overflow-hidden rounded-lg shadow-md group">
                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-700" loading="lazy">
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact / Location --}}
<section class="bg-[#1e40af] py-24 md:py-32" id="contact">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="med-heading text-4xl md:text-5xl font-bold text-white mb-14">Visit Us</h2>

        @if(!empty($site['address']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase text-blue-200 mb-3">Address</p>
            <p class="med-heading text-white text-lg md:text-xl leading-relaxed">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase text-blue-200 mb-2">Telephone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="med-heading text-white text-lg hover:text-blue-200 transition">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase text-blue-200 mb-2">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="med-heading text-white text-lg hover:text-blue-200 transition">
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
<footer class="bg-[#162d6b] py-10">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <p class="med-heading text-blue-300/60 text-sm italic">
            &copy; {{ date('Y') }} {{ $site['name'] }}. All rights reserved.
        </p>
    </div>
</footer>

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
