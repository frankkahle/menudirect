@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Archivo+Black&display=swap');
    body { background-color: #faf5f0; }
    .cantina-heading { font-family: 'Archivo Black', Impact, sans-serif; }
    .cantina-terracotta { color: #a0522d; }
    .cantina-teal { color: #0d9488; }
    .cantina-arch {
        border-radius: 9999px 9999px 0 0;
    }
    .cantina-card {
        border-radius: 1.5rem 1.5rem 0.5rem 0.5rem;
    }
    .cantina-dot-sep {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .cantina-dot-sep span.dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #a0522d;
    }
    .cantina-stripe-bg {
        background:
            repeating-linear-gradient(135deg, transparent, transparent 20px, rgba(160,82,45,0.03) 20px, rgba(160,82,45,0.03) 40px);
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
<section class="relative py-16 md:py-24 overflow-hidden" style="background: linear-gradient(160deg, #a0522d 0%, #8b4513 60%, #0d9488 100%);">
    {{-- Arched decorative element --}}
    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-[600px] h-[300px] border-4 border-white/10 cantina-arch"></div>

    <div class="relative max-w-4xl mx-auto px-6 text-center z-10">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-24 md:h-32 mx-auto mb-8 drop-shadow-xl rounded-full bg-white/10 p-2">
        @endif

        <div class="cantina-dot-sep mb-6">
            <span class="dot"></span>
            <span class="dot" style="background:#0d9488;"></span>
            <span class="dot"></span>
        </div>

        <h1 class="cantina-heading text-5xl md:text-7xl lg:text-8xl text-white mb-6 leading-tight uppercase drop-shadow-lg">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="text-lg md:text-2xl text-orange-100 tracking-wide mb-10">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-5 mt-8">
            <a href="#full-menu" class="inline-block bg-white text-[#a0522d] px-10 py-3 text-sm tracking-[0.2em] uppercase font-bold hover:bg-orange-50 transition-all duration-300 rounded-full shadow-lg">
                See the Menu
            </a>
            <a href="#reservations" class="inline-block border-2 border-white text-white px-10 py-3 text-sm tracking-[0.2em] uppercase font-bold hover:bg-white/10 transition-all duration-300 rounded-full">
                Book a Table
            </a>
        </div>
    </div>
</section>

{{-- Photo Grid --}}
@if($coverPhoto || !empty($site['settings']['gallery']))
<section class="bg-[#faf5f0] py-6">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @if($coverPhoto)
            <div class="col-span-2 row-span-2 overflow-hidden cantina-card">
                <img src="{{ $coverPhoto }}" alt="{{ $site['name'] }}" class="w-full h-full object-cover min-h-[280px]">
            </div>
            @endif
            @if(!empty($site['settings']['gallery']))
                @foreach(array_slice($site['settings']['gallery'], 0, $coverPhoto ? 4 : 6) as $image)
                @php
                    $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                @endphp
                <div class="overflow-hidden cantina-card">
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
<section class="bg-[#0d9488] py-8">
    <div class="max-w-4xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-10 gap-y-3 text-xs tracking-[0.3em] uppercase text-white font-bold">
            @foreach($site['settings']['features'] as $feature)
            <span class="flex items-center">
                <span class="text-orange-200 mr-3 text-lg">&#9733;</span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About --}}
<section class="cantina-stripe-bg py-20 md:py-28">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="cantina-dot-sep mb-8">
            <span class="inline-block w-12 h-px bg-[#a0522d]"></span>
            <span class="text-xs tracking-[0.5em] uppercase cantina-terracotta font-bold">Welcome</span>
            <span class="inline-block w-12 h-px bg-[#a0522d]"></span>
        </div>
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
<section class="cantina-stripe-bg py-20 md:py-28" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="cantina-heading text-4xl md:text-5xl cantina-terracotta mb-12 uppercase">Hours</h2>

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
        <div class="inline-block text-left bg-white p-8 rounded-2xl shadow-md">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-baseline py-2 {{ $day === $today ? 'cantina-teal font-bold' : 'text-gray-700' }}">
                <span class="text-sm tracking-wide w-28 font-semibold">{{ $day }}</span>
                <span class="flex-1 border-b border-dotted border-[#a0522d]/20 mx-4" style="min-width: 3rem;"></span>
                <span class="text-sm {{ strtolower($time) === 'closed' ? 'text-gray-400 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['settings']['gallery']))
<section class="bg-[#a0522d] py-20 md:py-28" id="gallery">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-14">
            <h2 class="cantina-heading text-4xl md:text-5xl text-white uppercase">Gallery</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($site['settings']['gallery'] as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
            @endphp
            <figure class="overflow-hidden cantina-card group">
                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact / Location --}}
<section class="cantina-stripe-bg py-20 md:py-28" id="contact">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="cantina-heading text-4xl md:text-5xl cantina-terracotta mb-14 uppercase">Find Us</h2>

        @if(!empty($site['address']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase cantina-teal mb-3 font-bold">Address</p>
            <p class="text-gray-800 text-lg md:text-xl leading-relaxed">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase cantina-teal mb-2 font-bold">Telephone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-gray-800 text-lg hover:text-[#0d9488] transition">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase cantina-teal mb-2 font-bold">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="text-gray-800 text-lg hover:text-[#0d9488] transition">
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
<footer class="py-10" style="background: linear-gradient(160deg, #a0522d 0%, #8b4513 60%, #0d9488 100%);">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="cantina-dot-sep mb-4">
            <span class="dot" style="background:white;"></span>
            <span class="dot" style="background:white;width:4px;height:4px;"></span>
            <span class="dot" style="background:white;"></span>
        </div>
        <p class="text-white/60 text-sm">
            &copy; {{ date('Y') }} {{ $site['name'] }}. All rights reserved.
        </p>
    </div>
</footer>

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
