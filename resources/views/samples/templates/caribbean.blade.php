@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800;900&display=swap');
    body { background-color: #fffdf7; }
    .carib-heading { font-family: 'Rubik', Arial, sans-serif; font-weight: 800; }
    .carib-body { font-family: 'Rubik', Arial, sans-serif; }
    .carib-yellow { color: #eab308; }
    .carib-teal { color: #14b8a6; }
    .carib-pink { color: #ec4899; }
    .carib-gradient-bg {
        background: linear-gradient(135deg, #eab308 0%, #14b8a6 50%, #ec4899 100%);
    }
    .carib-stripe {
        background: repeating-linear-gradient(
            -45deg,
            transparent,
            transparent 10px,
            rgba(234,179,8,0.06) 10px,
            rgba(234,179,8,0.06) 20px
        );
    }
    .carib-badge {
        display: inline-block;
        padding: 0.25rem 1rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
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
<section class="carib-gradient-bg py-16 md:py-24 relative overflow-hidden">
    {{-- Decorative circles --}}
    <div class="absolute -top-20 -left-20 w-64 h-64 rounded-full bg-white/10"></div>
    <div class="absolute -bottom-16 -right-16 w-48 h-48 rounded-full bg-white/10"></div>
    <div class="absolute top-1/2 left-1/4 w-32 h-32 rounded-full bg-white/5"></div>

    <div class="relative max-w-4xl mx-auto px-6 text-center z-10">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-24 md:h-32 mx-auto mb-8 drop-shadow-xl">
        @endif

        <h1 class="carib-heading text-5xl md:text-7xl lg:text-9xl text-white mb-6 leading-none uppercase drop-shadow-lg">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="carib-body text-lg md:text-2xl text-white/90 font-medium tracking-wide mb-10">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-5 mt-8">
            <a href="#full-menu" class="inline-block bg-white text-gray-900 px-10 py-4 text-sm tracking-[0.15em] uppercase font-bold hover:scale-105 transition-all duration-300 rounded-full shadow-xl carib-body">
                Check the Menu
            </a>
            <a href="#reservations" class="inline-block border-2 border-white text-white px-10 py-4 text-sm tracking-[0.15em] uppercase font-bold hover:bg-white/20 transition-all duration-300 rounded-full carib-body">
                Reserve
            </a>
        </div>
    </div>
</section>

{{-- Photo Grid --}}
@if($coverPhoto || !empty($site['settings']['gallery']))
<section class="bg-[#fffdf7] py-6">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @if($coverPhoto)
            <div class="col-span-2 row-span-2 overflow-hidden rounded-2xl">
                <img src="{{ $coverPhoto }}" alt="{{ $site['name'] }}" class="w-full h-full object-cover min-h-[280px]">
            </div>
            @endif
            @if(!empty($site['settings']['gallery']))
                @foreach(array_slice($site['settings']['gallery'], 0, $coverPhoto ? 4 : 6) as $image)
                @php
                    $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                @endphp
                <div class="overflow-hidden rounded-2xl">
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
        <div class="flex flex-wrap justify-center gap-3">
            @php $colors = ['bg-[#eab308] text-gray-900', 'bg-[#14b8a6] text-white', 'bg-[#ec4899] text-white']; @endphp
            @foreach($site['settings']['features'] as $i => $feature)
            <span class="carib-badge {{ $colors[$i % 3] }} carib-body">
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About --}}
<section class="carib-stripe py-20 md:py-28">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="mb-8">
            <span class="carib-badge bg-[#14b8a6] text-white carib-body">Our Vibe</span>
        </div>
        @if(!empty($site['about']))
        <p class="carib-body text-gray-700 text-lg md:text-xl leading-relaxed">
            {{ $site['about'] }}
        </p>
        @elseif(!empty($site['tagline']))
        <p class="carib-body text-gray-700 text-lg md:text-xl leading-relaxed">
            {{ $site['tagline'] }}
        </p>
        @endif
    </div>
</section>

{{-- Menu Section --}}
@include('samples.partials.menu-section')

{{-- Hours --}}
@if(!empty($site['hours']))
<section class="carib-stripe py-20 md:py-28" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="carib-heading text-4xl md:text-5xl text-gray-900 mb-12 uppercase">Hours</h2>

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
        <div class="inline-block text-left bg-white p-8 rounded-2xl shadow-lg border-2 border-[#eab308]/30 carib-body">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-baseline py-2 {{ $day === $today ? 'carib-teal font-bold' : 'text-gray-700' }}">
                <span class="text-sm tracking-wide w-28 font-semibold">{{ $day }}</span>
                <span class="flex-1 border-b border-dotted border-[#eab308]/40 mx-4" style="min-width: 3rem;"></span>
                <span class="text-sm {{ strtolower($time) === 'closed' ? 'text-gray-400 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['settings']['gallery']))
<section class="carib-gradient-bg py-20 md:py-28" id="gallery">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-14">
            <h2 class="carib-heading text-4xl md:text-5xl text-white uppercase">Gallery</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($site['settings']['gallery'] as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
            @endphp
            <figure class="overflow-hidden rounded-2xl group shadow-lg">
                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact / Location --}}
<section class="bg-gray-900 py-20 md:py-28" id="contact">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <h2 class="carib-heading text-4xl md:text-5xl text-white mb-14 uppercase">Find Us</h2>

        @if(!empty($site['address']))
        <div class="mb-12">
            <span class="carib-badge bg-[#eab308] text-gray-900 carib-body mb-3">Address</span>
            <p class="text-white text-lg md:text-xl leading-relaxed mt-3 carib-body">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <span class="carib-badge bg-[#14b8a6] text-white carib-body mb-2">Phone</span>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="block text-white text-lg hover:text-[#eab308] transition mt-2 carib-body">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <span class="carib-badge bg-[#ec4899] text-white carib-body mb-2">Email</span>
                <a href="mailto:{{ $site['email'] }}" class="block text-white text-lg hover:text-[#eab308] transition mt-2 carib-body">
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
<footer class="carib-gradient-bg py-10">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <p class="carib-body text-white/60 text-sm font-medium">
            &copy; {{ date('Y') }} {{ $site['name'] }}. All rights reserved.
        </p>
    </div>
</footer>

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
