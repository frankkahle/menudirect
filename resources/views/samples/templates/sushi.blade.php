@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap');
    body { background-color: #ffffff; }
    .sushi-heading { font-family: 'Zen Kaku Gothic New', system-ui, sans-serif; }
    .sushi-rule {
        width: 100%;
        height: 1px;
        background: #e5e7eb;
    }
    .sushi-rule-accent {
        width: 48px;
        height: 1px;
        background: #6b8e23;
    }
    .sushi-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        color: #6b8e23;
    }
    .sushi-divider::before,
    .sushi-divider::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 80px;
        background: #e5e7eb;
    }
    .sushi-card {
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    .sushi-card:hover {
        border-color: #6b8e23;
    }
    .sushi-vertical-text {
        writing-mode: vertical-rl;
        text-orientation: mixed;
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

{{-- Split Hero Section --}}
<section class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-5 gap-0 items-stretch">
            {{-- LEFT: Text block (60%) --}}
            <div class="md:col-span-3 px-6 py-12 md:px-14 md:py-20 flex flex-col justify-center bg-white relative">
                {{-- Subtle vertical accent line --}}
                <div class="absolute left-0 top-12 bottom-12 w-px bg-gray-200 hidden md:block"></div>

                <div class="relative md:pl-8">
                    @if(!empty($site['logo']))
                    <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-14 md:h-18 mb-8">
                    @endif

                    <div class="sushi-rule-accent mb-8"></div>

                    <p class="sushi-heading text-[11px] uppercase tracking-[0.5em] text-[#6b8e23] font-medium mb-6">
                        @if(!empty($site['address']['city']))
                            {{ $site['address']['city'] }} &middot; Japanese Cuisine
                        @else
                            Japanese Cuisine
                        @endif
                    </p>

                    <h1 class="sushi-heading text-5xl md:text-6xl lg:text-7xl font-black text-gray-900 leading-[0.95] mb-6 tracking-tight">
                        {{ $site['name'] }}
                    </h1>

                    <p class="sushi-heading text-lg md:text-xl text-gray-400 font-normal mb-10 leading-relaxed max-w-md">
                        {{ $site['tagline'] ?? 'Precision in every cut. Perfection in every bite.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3">
                        @if($orderingEnabled)
                        <a href="#full-menu" class="inline-flex items-center justify-center bg-gray-900 text-white px-8 py-3.5 sushi-heading font-medium hover:bg-[#6b8e23] transition-colors text-sm tracking-wider uppercase">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Order Online
                        </a>
                        @else
                        <a href="{{ $site['cta_url'] ?? '#' }}" class="inline-flex items-center justify-center bg-gray-900 text-white px-8 py-3.5 sushi-heading font-medium hover:bg-[#6b8e23] transition-colors text-sm tracking-wider uppercase">
                            {{ $site['cta_text'] ?? 'Order Now' }}
                        </a>
                        @endif

                        @if(!empty($site['phone']))
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-flex items-center justify-center bg-transparent text-gray-900 px-8 py-3.5 sushi-heading font-medium hover:bg-gray-100 transition-colors text-sm tracking-wider border border-gray-300">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $site['phone'] }}
                        </a>
                        @endif
                    </div>

                    @if(!empty($site['address']['street']))
                    <p class="mt-8 text-xs text-gray-400 sushi-heading tracking-wider">
                        {{ $site['address']['street'] }}@if(!empty($site['address']['city'])), {{ $site['address']['city'] }}@endif
                    </p>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Cover photo (40%) --}}
            <div class="md:col-span-2 relative min-h-[300px] md:min-h-0 bg-gray-50">
                @if(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
                @php $heroImg = $site['hero_image'] ?? $site['cover_photo']; @endphp
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImg }}');">
                    <div class="absolute inset-0 bg-gradient-to-l from-transparent to-white/5"></div>
                </div>
                @else
                <div class="absolute inset-0 flex items-center justify-center bg-gray-50">
                    <svg class="w-32 h-32 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                @endif

                {{-- Minimal corner accent --}}
                <div class="absolute top-6 right-6 hidden md:block">
                    <div class="w-10 h-10 border-t border-r border-[#6b8e23]/50"></div>
                </div>
                <div class="absolute bottom-6 right-6 hidden md:block">
                    <div class="w-10 h-10 border-b border-r border-[#6b8e23]/50"></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Online Ordering Banner --}}
@if($orderingEnabled)
<section x-show="isRestaurantOpen" class="bg-gray-900 text-white py-3 border-b border-gray-800">
    <div class="max-w-6xl mx-auto px-4 text-center text-xs">
        <span class="inline-flex items-center flex-wrap justify-center sushi-heading tracking-widest uppercase">
            <svg class="w-4 h-4 mr-2 text-[#6b8e23]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Now Open</span>
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-3 text-gray-600">|</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-3 text-gray-600">|</span> Delivery
            @endif
            <span class="mx-3 text-gray-600">|</span> Pay at {{ ($orderingConfig['accepts_pickup'] ?? true) ? 'Pickup' : 'Delivery' }}
        </span>
    </div>
</section>
<section x-show="!isRestaurantOpen" x-cloak class="bg-gray-100 text-gray-600 py-3 border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4 text-center text-xs">
        <span class="inline-flex items-center flex-wrap justify-center sushi-heading tracking-widest uppercase">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <strong class="text-gray-900">Currently Closed</strong>
            <span x-show="todayHours" class="mx-3 text-gray-400">|</span>
            <span x-show="todayHours">Today: <span x-text="todayHours"></span></span>
            <span x-show="nextOpenLabel" class="mx-3 text-gray-400">|</span>
            <span x-show="nextOpenLabel" x-text="nextOpenLabel"></span>
            <span class="mx-3 text-gray-400">|</span>
            <span class="text-[#6b8e23] font-medium">Schedule Order</span>
        </span>
    </div>
</section>
@endif

{{-- Features Strip --}}
@if(!empty($site['features']))
<section class="bg-white py-5 border-b border-gray-100">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-3 text-xs text-gray-500 sushi-heading tracking-wider uppercase">
            @foreach($site['features'] as $index => $feature)
            @if($index > 0)
            <span class="text-gray-300 hidden sm:inline">|</span>
            @endif
            <span class="flex items-center">
                <svg class="w-3.5 h-3.5 text-[#6b8e23] mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Menu Section (via partial) --}}
@include('samples.partials.menu-section')

{{-- Hours & Contact Section --}}
@if(!empty($site['hours']))
<section class="py-20 bg-white" id="hours">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <div class="sushi-rule-accent mx-auto mb-6"></div>
            <h2 class="sushi-heading text-4xl md:text-5xl font-black text-gray-900 mb-4 tracking-tight">Hours & Contact</h2>
            <div class="sushi-divider max-w-md mx-auto">
                <span class="text-xs tracking-widest">&middot;</span>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-px bg-gray-200">
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
            {{-- Hours --}}
            <div class="bg-white p-8">
                <h3 class="sushi-heading text-xs uppercase tracking-[0.3em] text-[#6b8e23] font-medium mb-6">Hours</h3>
                <div class="space-y-0">
                    @foreach($sortedHours as $day => $time)
                    <div class="flex justify-between py-3 border-b border-gray-100 last:border-0 {{ $day === $today ? 'bg-gray-50/50 -mx-3 px-3' : '' }}">
                        <span class="sushi-heading text-sm {{ $day === $today ? 'font-bold text-gray-900' : 'text-gray-500' }}">
                            {{ $day }}
                            @if($day === $today)
                            <span class="ml-2 text-[10px] uppercase tracking-widest text-[#6b8e23]">Today</span>
                            @endif
                        </span>
                        <span class="sushi-heading text-sm {{ strtolower($time) === 'closed' ? 'text-red-500' : ($day === $today ? 'text-gray-900 font-medium' : 'text-gray-400') }}">
                            {{ $time }}
                        </span>
                    </div>
                    @endforeach
                </div>

                @if(!empty($site['holiday_hours']))
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <h4 class="sushi-heading text-xs uppercase tracking-[0.3em] text-gray-400 font-medium mb-3">Special Hours</h4>
                    @foreach($site['holiday_hours'] as $holiday)
                    <div class="flex justify-between text-sm py-1.5 sushi-heading">
                        <span class="text-gray-500">
                            {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                            @if($holiday['label']) &mdash; {{ $holiday['label'] }} @endif
                        </span>
                        <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'text-red-500' : 'text-gray-400' }}">
                            {{ $holiday['hours'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Contact --}}
            <div class="bg-white p-8">
                <h3 class="sushi-heading text-xs uppercase tracking-[0.3em] text-[#6b8e23] font-medium mb-6">Location</h3>
                <div class="space-y-6">
                    @if(!empty($site['address']))
                    <div class="flex items-start">
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="sushi-heading">
                            <span class="text-gray-900 text-sm">{{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}</span>
                            @if(!empty($site['secondary_cta_url']))
                            <a href="{{ $site['secondary_cta_url'] }}" class="block text-[#6b8e23] text-xs hover:underline mt-1 tracking-wider uppercase" target="_blank">Directions &rarr;</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!empty($site['phone']))
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="sushi-heading text-gray-900 hover:text-[#6b8e23] font-medium text-sm transition">{{ $site['phone'] }}</a>
                    </div>
                    @endif

                    @if(!empty($site['email']))
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $site['email'] }}" class="sushi-heading text-gray-500 hover:text-[#6b8e23] text-sm transition">{{ $site['email'] }}</a>
                    </div>
                    @endif
                </div>

                {{-- CTA --}}
                <div class="mt-8">
                    @if($orderingEnabled)
                    <a href="#full-menu" class="block w-full text-center bg-gray-900 text-white py-3.5 sushi-heading font-medium hover:bg-[#6b8e23] transition-colors text-sm tracking-wider uppercase">
                        Order Online
                    </a>
                    @else
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="block w-full text-center bg-gray-900 text-white py-3.5 sushi-heading font-medium hover:bg-[#6b8e23] transition-colors text-sm tracking-wider uppercase">
                        Call: {{ $site['phone'] ?? '' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['gallery']) && count($site['gallery']) > 0)
<section class="py-20 bg-gray-50" id="gallery">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <div class="sushi-rule-accent mx-auto mb-6"></div>
            <h2 class="sushi-heading text-4xl md:text-5xl font-black text-gray-900 mb-4 tracking-tight">Gallery</h2>
            <div class="sushi-divider max-w-md mx-auto">
                <span class="text-xs tracking-widest">&middot;</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-px bg-gray-200">
            @foreach($site['gallery'] as $index => $photo)
            @php $photoCaption = is_array($photo) ? ($photo['caption'] ?? null) : null; @endphp
            <figure class="relative overflow-hidden group bg-white">
                <img src="{{ is_array($photo) ? ($photo['url'] ?? $photo['src'] ?? '') : $photo }}"
                     alt="{{ $photoCaption ?: (is_array($photo) ? ($photo['alt'] ?? 'Gallery photo') : 'Gallery photo') }}"
                     class="w-full h-56 md:h-64 object-cover group-hover:scale-105 transition-transform duration-700"
                     loading="lazy">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-500"></div>
                @if($photoCaption)
                <figcaption class="px-4 py-3 text-sm text-gray-700 text-center sushi-heading bg-white">{{ $photoCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Closing --}}
<section class="py-16 bg-white">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <div class="sushi-divider mb-6">
            <span class="text-xs tracking-widest">&middot;</span>
        </div>
        <p class="sushi-heading text-gray-400 text-base leading-relaxed">
            Every dish is prepared with care and precision. We invite you to experience the art of Japanese cuisine.
        </p>
        <p class="sushi-heading text-gray-900 text-xs uppercase tracking-[0.4em] mt-6 font-medium">
            {{ $site['name'] }}
        </p>
    </div>
</section>

@include('samples.partials.reservations-section')

@include('samples.partials.cart-ui')

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
