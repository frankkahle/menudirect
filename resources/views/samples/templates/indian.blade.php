@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
    $coverPhoto = $site['cover_photo'] ?? $site['hero_image'] ?? null;
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Yeseva+One&display=swap');
    body { background-color: #fdf6ee; }
    .indian-heading { font-family: 'Yeseva One', Georgia, serif; }
    .indian-saffron { color: #d97706; }
    .indian-burgundy { color: #7f1d1d; }
    .indian-cream { color: #fdf6ee; }
    .indian-ornament-border {
        border-top: 3px solid #d97706;
        border-bottom: 3px solid #d97706;
        position: relative;
        padding: 4px 0;
    }
    .indian-ornament-border::before,
    .indian-ornament-border::after {
        content: '';
        display: block;
        height: 1px;
        background: repeating-linear-gradient(90deg, #d97706 0, #d97706 6px, transparent 6px, transparent 12px);
    }
    .indian-geo-pattern {
        background-image:
            repeating-linear-gradient(0deg, transparent, transparent 30px, rgba(217,119,6,0.05) 30px, rgba(217,119,6,0.05) 31px),
            repeating-linear-gradient(90deg, transparent, transparent 30px, rgba(217,119,6,0.05) 30px, rgba(217,119,6,0.05) 31px);
    }
    .indian-diamond {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #d97706;
        transform: rotate(45deg);
    }
    .indian-banner-pattern {
        background:
            repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(127,29,29,0.08) 10px, rgba(127,29,29,0.08) 20px),
            linear-gradient(135deg, #7f1d1d 0%, #991b1b 50%, #7f1d1d 100%);
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
<section class="indian-banner-pattern py-16 md:py-24 relative overflow-hidden">
    {{-- Decorative top border --}}
    <div class="absolute top-0 left-0 right-0 h-2" style="background: repeating-linear-gradient(90deg, #d97706 0, #d97706 20px, #fdf6ee 20px, #fdf6ee 24px, #d97706 24px);"></div>
    <div class="absolute bottom-0 left-0 right-0 h-2" style="background: repeating-linear-gradient(90deg, #d97706 0, #d97706 20px, #fdf6ee 20px, #fdf6ee 24px, #d97706 24px);"></div>

    <div class="relative max-w-4xl mx-auto px-6 text-center z-10">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-24 md:h-32 mx-auto mb-8 drop-shadow-lg">
        @endif

        <div class="flex items-center justify-center gap-4 mb-6">
            <span class="indian-diamond"></span>
            <span class="indian-diamond" style="width:6px;height:6px;"></span>
            <span class="indian-diamond"></span>
        </div>

        <h1 class="indian-heading text-5xl md:text-7xl lg:text-8xl text-[#fdf6ee] mb-6 leading-tight drop-shadow-lg">
            {{ $site['name'] }}
        </h1>

        @if(!empty($site['tagline']))
        <p class="text-lg md:text-2xl text-amber-200 tracking-wide mb-10 font-light">
            {{ $site['tagline'] }}
        </p>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-5 mt-8">
            <a href="#full-menu" class="inline-block bg-[#d97706] text-white px-10 py-3 text-sm tracking-[0.2em] uppercase font-bold hover:bg-[#b45309] transition-all duration-300 shadow-lg">
                Explore Our Menu
            </a>
            <a href="#reservations" class="inline-block border-2 border-[#d97706] text-[#d97706] bg-[#fdf6ee] px-10 py-3 text-sm tracking-[0.2em] uppercase font-bold hover:bg-[#d97706] hover:text-white transition-all duration-300">
                Reserve a Table
            </a>
        </div>
    </div>
</section>

{{-- Photo Grid --}}
@if($coverPhoto || !empty($site['settings']['gallery']))
<section class="bg-[#fdf6ee] py-6">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @if($coverPhoto)
            <div class="col-span-2 row-span-2 overflow-hidden">
                <img src="{{ $coverPhoto }}" alt="{{ $site['name'] }}" class="w-full h-full object-cover min-h-[280px]">
            </div>
            @endif
            @if(!empty($site['settings']['gallery']))
                @foreach(array_slice($site['settings']['gallery'], 0, $coverPhoto ? 4 : 6) as $image)
                @php
                    $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                @endphp
                <div class="overflow-hidden">
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
<section class="bg-[#7f1d1d] py-8">
    <div class="max-w-4xl mx-auto px-6">
        <div class="flex flex-wrap justify-center gap-x-10 gap-y-3 text-xs tracking-[0.3em] uppercase text-amber-200">
            @foreach($site['settings']['features'] as $feature)
            <span class="flex items-center">
                <span class="indian-diamond mr-3" style="width:5px;height:5px;"></span>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About --}}
<section class="indian-geo-pattern py-20 md:py-28">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="flex items-center justify-center gap-4 mb-8">
            <span class="inline-block w-16 h-px bg-[#d97706]"></span>
            <span class="text-xs tracking-[0.5em] uppercase indian-saffron">Our Story</span>
            <span class="inline-block w-16 h-px bg-[#d97706]"></span>
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
<section class="bg-[#fdf6ee] indian-geo-pattern py-20 md:py-28" id="hours">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="flex items-center justify-center gap-4 mb-8">
            <span class="inline-block w-16 h-px bg-[#d97706]"></span>
            <span class="text-xs tracking-[0.5em] uppercase indian-saffron">Hours</span>
            <span class="inline-block w-16 h-px bg-[#d97706]"></span>
        </div>
        <h2 class="indian-heading text-4xl md:text-5xl indian-burgundy mb-12">Opening Hours</h2>

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
        <div class="inline-block text-left bg-white/60 p-8 shadow-sm border border-[#d97706]/20">
            @foreach($sortedHours as $day => $time)
            <div class="flex items-baseline py-2 {{ $day === $today ? 'indian-saffron font-bold' : 'indian-burgundy' }}">
                <span class="text-sm tracking-wide w-28">{{ $day }}</span>
                <span class="flex-1 border-b border-dotted border-[#d97706]/30 mx-4" style="min-width: 3rem;"></span>
                <span class="text-sm {{ strtolower($time) === 'closed' ? 'text-gray-400 italic' : '' }}">{{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['settings']['gallery']))
<section class="bg-[#7f1d1d] py-20 md:py-28" id="gallery">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-14">
            <h2 class="indian-heading text-4xl md:text-5xl text-[#fdf6ee]">Gallery</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($site['settings']['gallery'] as $image)
            @php
                $imageUrl = is_array($image) ? ($image['url'] ?? $image['src'] ?? '') : $image;
                $imageAlt = is_array($image) ? ($image['alt'] ?? $site['name'] . ' gallery') : ($site['name'] . ' gallery');
            @endphp
            <figure class="overflow-hidden border-2 border-[#d97706]/30 group">
                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact / Location --}}
<section class="bg-[#fdf6ee] indian-geo-pattern py-20 md:py-28" id="contact">
    <div class="max-w-2xl mx-auto px-6 text-center">
        <div class="flex items-center justify-center gap-4 mb-8">
            <span class="inline-block w-16 h-px bg-[#d97706]"></span>
            <span class="text-xs tracking-[0.5em] uppercase indian-saffron">Visit</span>
            <span class="inline-block w-16 h-px bg-[#d97706]"></span>
        </div>
        <h2 class="indian-heading text-4xl md:text-5xl indian-burgundy mb-14">Find Us</h2>

        @if(!empty($site['address']))
        <div class="mb-12">
            <p class="text-xs tracking-[0.3em] uppercase indian-saffron mb-3">Address</p>
            <p class="indian-burgundy text-lg md:text-xl leading-relaxed">
                {{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}
            </p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-center items-center gap-10 mb-12">
            @if(!empty($site['phone']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase indian-saffron mb-2">Telephone</p>
                <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="indian-burgundy text-lg hover:text-[#d97706] transition">
                    {{ $site['phone'] }}
                </a>
            </div>
            @endif
            @if(!empty($site['email']))
            <div>
                <p class="text-xs tracking-[0.3em] uppercase indian-saffron mb-2">Email</p>
                <a href="mailto:{{ $site['email'] }}" class="indian-burgundy text-lg hover:text-[#d97706] transition">
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
<footer class="indian-banner-pattern py-10 relative">
    <div class="absolute top-0 left-0 right-0 h-1" style="background: repeating-linear-gradient(90deg, #d97706 0, #d97706 20px, transparent 20px, transparent 24px);"></div>
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="flex items-center justify-center gap-3 mb-4">
            <span class="indian-diamond" style="width:5px;height:5px;background:#d97706;"></span>
            <span class="indian-diamond" style="width:4px;height:4px;background:#d97706;"></span>
            <span class="indian-diamond" style="width:5px;height:5px;background:#d97706;"></span>
        </div>
        <p class="text-amber-200/60 text-sm">
            &copy; {{ date('Y') }} {{ $site['name'] }}. All rights reserved.
        </p>
    </div>
</footer>

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
