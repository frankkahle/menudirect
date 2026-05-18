@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Bungee&display=swap');
    body { background-color: #f5f5f5; }
    .diner-heading { font-family: 'Bungee', Impact, sans-serif; }
    .diner-red { color: #dc2626; }
    .diner-yellow { color: #fbbf24; }
    .diner-chrome { color: #9ca3af; }
    .diner-badge {
        display: inline-block;
        border: 3px solid #dc2626;
        border-radius: 9999px;
        padding: 0.5rem 1.5rem;
        font-family: 'Bungee', Impact, sans-serif;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.15em;
    }
    .diner-thick-border {
        border: 4px solid #dc2626;
    }
    .diner-checkerboard {
        background-image:
            repeating-conic-gradient(#e5e7eb 0% 25%, transparent 0% 50%);
        background-size: 20px 20px;
    }
    .diner-neon-glow {
        text-shadow: 0 0 10px rgba(220,38,38,0.3), 0 0 40px rgba(220,38,38,0.1);
    }
    .diner-stripe-bg {
        background: repeating-linear-gradient(
            0deg,
            transparent,
            transparent 40px,
            rgba(220,38,38,0.03) 40px,
            rgba(220,38,38,0.03) 42px
        );
    }
    .diner-chrome-border {
        border: 3px solid #d1d5db;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.6), 0 2px 4px rgba(0,0,0,0.1);
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

{{-- Bold Header Band --}}
<section class="bg-[#dc2626] py-16 md:py-24 relative overflow-hidden">
    {{-- Checkerboard accents --}}
    <div class="absolute top-0 left-0 right-0 h-4 diner-checkerboard"></div>
    <div class="absolute bottom-0 left-0 right-0 h-4 diner-checkerboard"></div>

    {{-- Chrome stripe --}}
    <div class="absolute top-4 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>
    <div class="absolute bottom-4 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>

    <div class="relative max-w-4xl mx-auto px-6 text-center z-10">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-24 md:h-32 mx-auto mb-8 drop-shadow-xl">
        @endif

        <div class="diner-badge text-[#fbbf24] border-[#fbbf24] mb-6 inline-block">
            Est. {{ $site['established'] ?? date('Y') }}
        </div>

        <h1 class="diner-heading text-5xl md:text-7xl lg:text-8xl text-white mb-6 leading-none uppercase diner-neon-glow">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="text-lg md:text-2xl text-red-100 tracking-wide mb-10 font-medium">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-5 mt-8">
            <a href="#full-menu" class="inline-block bg-[#fbbf24] text-gray-900 px-10 py-4 text-sm tracking-[0.15em] uppercase diner-heading hover:bg-yellow-300 transition-all duration-300 shadow-xl diner-thick-border border-[#fbbf24]">
                See the Menu
            </a>
            <a href="#reservations" class="inline-block bg-white text-[#dc2626] px-10 py-4 text-sm tracking-[0.15em] uppercase diner-heading hover:bg-gray-100 transition-all duration-300 shadow-xl diner-thick-border border-white">
                Grab a Booth
            </a>
        </div>
    </div>
</section>

{{-- Photo Grid --}}
@if($coverPhoto || !empty($site['settings']['gallery']))
<section class="bg-[#f5f5f5] py-6">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @if($coverPhoto)
            <div class="col-span-2 row-span-2 overflow-hidden diner-thick-border">
                <img src="{{ $coverPhoto }}" alt="{{ $site['name'] }}" class="w-full h-full object-cover min-h-[280px]">
            </div>
            @endif
            @if(!empty($site['settings']['gallery']))
                @foreach(array_slice($site['settings']['gallery'], 0, $coverPhoto ? 4 : 6) as $image)
                @php
                    $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                @endphp
                <div class="overflow-hidden diner-thick-border">
                    <img src="{{ $imageUrl }}" alt="{{ $site['name'] }}" class="w-full h-40 object-cover hover:scale-105 transition-transform duration-500">
                </div>
                @endforeach
            @endif
        </div>
    </div>
</section>
@endif

{{-- Features Strip --}}
@if(!empty($site['settings']['features']))
<section class="bg-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-4">
            @foreach($site['settings']['features'] as $feature)
            <span class="diner-badge text-[#fbbf24] border-[#fbbf24]">
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About --}}
<section class="diner-stripe-bg py-20 md:py-28">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="diner-badge diner-red border-[#dc2626] mb-8 inline-block">Welcome</div>
        @if(!empty($site['about']))
        <p class="text-gray-700 text-lg md:text-xl leading-relaxed">
            {{ $site['about'] }}
        </p>
        @elseif(!empty($site['tagline']))
        <p class="text-gray-700 text-lg md:text-xl leading-relaxed">
            {{ $site['tagline'] }}
        </p>
        @endif
    </div>
</section>

{{-- Menu Section --}}
@include('samples.partials.menu-section')

{{-- Hours --}}
@if(!empty($site['hours']))
<section class="diner-stripe-bg py-20 md:py-28" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="diner-heading text-4xl md:text-5xl diner-red mb-12 uppercase">Hours</h2>

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
        <div class="inline-block text-left bg-white p-8 diner-chrome-border rounded-lg">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-baseline py-2 {{ $day === $today ? 'diner-red font-bold' : 'text-gray-700' }}">
                <span class="text-sm tracking-wide w-28 font-bold uppercase" style="font-family:'Bungee',sans-serif;font-size:0.7rem;">{{ $day }}</span>
                <span class="flex-1 border-b-2 border-dotted border-[#dc2626]/20 mx-4" style="min-width: 3rem;"></span>
                <span class="text-sm font-semibold {{ strtolower($time) === 'closed' ? 'text-gray-400 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['settings']['gallery']))
<section class="bg-[#dc2626] py-20 md:py-28" id="gallery">
    <div class="absolute left-0 right-0 h-4 diner-checkerboard" style="margin-top:-5rem;"></div>
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-14">
            <h2 class="diner-heading text-4xl md:text-5xl text-white uppercase diner-neon-glow">Gallery</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($site['settings']['gallery'] as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
            @endphp
            <figure class="overflow-hidden diner-thick-border bg-white p-1 group">
                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact / Location --}}
<section class="diner-stripe-bg py-20 md:py-28" id="contact">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="diner-heading text-4xl md:text-5xl diner-red mb-14 uppercase">Find Us</h2>

        @if(!empty($site['address']))
        <div class="mb-12">
            <div class="diner-badge diner-red border-[#dc2626] mb-4 inline-block">Address</div>
            <p class="text-gray-800 text-lg md:text-xl leading-relaxed font-semibold">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <div class="diner-badge diner-red border-[#dc2626] mb-3 inline-block">Phone</div>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="block text-gray-800 text-lg font-bold hover:text-[#dc2626] transition">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <div class="diner-badge diner-red border-[#dc2626] mb-3 inline-block">Email</div>
                <a href="mailto:{{ $site['email'] }}" class="block text-gray-800 text-lg font-bold hover:text-[#dc2626] transition">
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
<footer class="bg-gray-900 py-10 relative">
    <div class="absolute top-0 left-0 right-0 h-4 diner-checkerboard"></div>
    <div class="max-w-5xl mx-auto px-6 text-center pt-4">
        <div class="diner-badge text-[#fbbf24] border-[#fbbf24] mb-4">
            {{ $site['name'] }}
        </div>
        <p class="text-gray-500 text-sm mt-4">
            &copy; {{ date('Y') }} {{ $site['name'] }}. All rights reserved.
        </p>
    </div>
</footer>

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
