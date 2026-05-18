@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap');
    body { background-color: #faf8f2; }
    .thai-heading { font-family: 'Cormorant Garamond', Georgia, serif; }
    .thai-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: #d4a853;
    }
    .thai-divider::before,
    .thai-divider::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 100px;
        background: linear-gradient(to right, transparent, #d4a853, transparent);
    }
    .thai-gold-line {
        width: 48px;
        height: 2px;
        background: linear-gradient(to right, #d4a853, #c49a3a);
    }
    .thai-ornament {
        display: inline-block;
        color: #d4a853;
        font-size: 1.25rem;
        letter-spacing: 0.5rem;
    }
    .thai-border-accent {
        position: relative;
    }
    .thai-border-accent::before {
        content: '';
        position: absolute;
        inset: 8px;
        border: 1px solid rgba(212, 168, 83, 0.2);
        pointer-events: none;
    }
    .thai-card {
        background: #faf8f2;
        border: 1px solid rgba(212, 168, 83, 0.25);
    }
    .thai-card-hover:hover {
        border-color: rgba(212, 168, 83, 0.5);
        box-shadow: 0 4px 20px rgba(76, 29, 149, 0.06);
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
<section class="thai-border-accent" style="background-color: #4c1d95;">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-5 gap-0 items-stretch">
            {{-- LEFT: Text block (60%) --}}
            <div class="md:col-span-3 px-6 py-12 md:px-14 md:py-20 flex flex-col justify-center relative">
                <div class="relative z-10">
                    @if(!empty($site['logo']))
                    <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-20 md:h-24 mb-6 rounded bg-white/10 p-2 backdrop-blur-sm">
                    @endif

                    <div class="thai-ornament mb-4">&loz; &loz; &loz;</div>

                    <p class="thai-heading text-xs uppercase tracking-[0.4em] text-[#d4a853] font-semibold mb-4">
                        @if(!empty($site['address']['city']))
                            Authentic Thai Cuisine &bull; {{ $site['address']['city'] }}
                        @else
                            Authentic Thai Cuisine
                        @endif
                    </p>

                    <h1 class="thai-heading text-5xl md:text-6xl lg:text-7xl font-bold text-white leading-tight mb-4">
                        {{ $site['name'] }}
                    </h1>

                    <div class="thai-gold-line mb-6"></div>

                    <p class="thai-heading text-xl md:text-2xl text-white/80 italic mb-10 leading-relaxed max-w-lg">
                        {{ $site['tagline'] ?? 'A taste of Thailand, crafted with passion.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($orderingEnabled)
                        <a href="#full-menu" class="inline-flex items-center justify-center px-8 py-4 thai-heading font-bold text-[#4c1d95] hover:opacity-90 transition shadow-lg tracking-wide" style="background: linear-gradient(135deg, #d4a853, #c49a3a);">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Order Online
                        </a>
                        @else
                        <a href="{{ $site['cta_url'] ?? '#' }}" class="inline-flex items-center justify-center px-8 py-4 thai-heading font-bold text-[#4c1d95] hover:opacity-90 transition shadow-lg tracking-wide" style="background: linear-gradient(135deg, #d4a853, #c49a3a);">
                            {{ $site['cta_text'] ?? 'Order Now' }}
                        </a>
                        @endif

                        @if(!empty($site['phone']))
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-flex items-center justify-center px-8 py-4 thai-heading font-bold text-white hover:bg-white/10 transition border border-[#d4a853]/50">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $site['phone'] }}
                        </a>
                        @endif
                    </div>

                    @if(!empty($site['address']['street']))
                    <p class="mt-8 text-sm text-white/50 thai-heading italic">
                        {{ $site['address']['street'] }}@if(!empty($site['address']['city'])), {{ $site['address']['city'] }}@endif
                    </p>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Cover photo (40%) --}}
            <div class="md:col-span-2 relative min-h-[300px] md:min-h-0">
                @if(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
                @php $heroImg = $site['hero_image'] ?? $site['cover_photo']; @endphp
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImg }}');">
                    <div class="absolute inset-0 bg-gradient-to-l from-transparent to-[#4c1d95]/30"></div>
                </div>
                @else
                <div class="absolute inset-0 flex items-center justify-center" style="background-color: #3b1578;">
                    <svg class="w-32 h-32 text-[#d4a853]/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                @endif

                {{-- Ornamental corner accent --}}
                <div class="absolute top-4 right-4 hidden md:block">
                    <div class="w-16 h-16 border-t-2 border-r-2 border-[#d4a853]/40"></div>
                </div>
                <div class="absolute bottom-4 left-4 hidden md:block">
                    <div class="w-16 h-16 border-b-2 border-l-2 border-[#d4a853]/40"></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Online Ordering Banner --}}
@if($orderingEnabled)
<section x-show="isRestaurantOpen" class="py-3 border-b text-white" style="background-color: #3b1578; border-color: rgba(212, 168, 83, 0.15);">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center thai-heading">
            <svg class="w-5 h-5 mr-2 text-[#d4a853]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="italic">Now Serving</span>
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-2 text-[#d4a853]">&loz;</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-2 text-[#d4a853]">&loz;</span> Delivery
            @endif
            <span class="mx-2 text-[#d4a853]">&loz;</span> Pay at {{ ($orderingConfig['accepts_pickup'] ?? true) ? 'Pickup' : 'Delivery' }}
        </span>
    </div>
</section>
<section x-show="!isRestaurantOpen" x-cloak class="py-3 border-b" style="background-color: #2d1055; border-color: rgba(212, 168, 83, 0.1);">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm text-white/80">
        <span class="inline-flex items-center flex-wrap justify-center thai-heading">
            <svg class="w-5 h-5 mr-2 text-[#d4a853]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <strong class="italic text-white">Currently Closed</strong>
            <span x-show="todayHours" class="mx-2 text-[#d4a853]">&loz;</span>
            <span x-show="todayHours">Today: <span x-text="todayHours"></span></span>
            <span x-show="nextOpenLabel" class="mx-2 text-[#d4a853]">&loz;</span>
            <span x-show="nextOpenLabel" x-text="nextOpenLabel"></span>
            <span class="mx-2 text-[#d4a853]">&loz;</span>
            <span class="font-medium text-[#d4a853]">Schedule Your Order</span>
        </span>
    </div>
</section>
@endif

{{-- Features Strip --}}
@if(!empty($site['features']))
<section class="py-6 border-b" style="background-color: #faf8f2; border-color: rgba(212, 168, 83, 0.15);">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-3 text-sm" style="color: #4c1d95;">
            @foreach($site['features'] as $index => $feature)
            @if($index > 0)
            <span class="text-[#d4a853] hidden sm:inline">&loz;</span>
            @endif
            <span class="flex items-center thai-heading italic text-base">
                <svg class="w-4 h-4 text-[#d4a853] mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
<section class="py-20" style="background-color: #faf8f2;" id="hours">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <div class="thai-ornament mb-4">&loz; &loz; &loz;</div>
            <h2 class="thai-heading text-4xl md:text-5xl font-bold mb-4" style="color: #4c1d95;">Visit & Hours</h2>
            <div class="thai-divider max-w-md mx-auto">
                <span class="text-lg">&loz;</span>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
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
            <div>
                <h3 class="thai-heading text-2xl font-bold mb-6 flex items-center" style="color: #4c1d95;">
                    <svg class="w-6 h-6 mr-3 text-[#d4a853]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Kitchen Hours
                </h3>
                <div class="thai-card overflow-hidden">
                    <table class="w-full thai-heading">
                        <tbody>
                            @foreach($sortedHours as $day => $time)
                            <tr class="border-b last:border-0" style="border-color: rgba(212, 168, 83, 0.15); {{ $day === $today ? 'background-color: rgba(76, 29, 149, 0.04);' : '' }}">
                                <td class="px-5 py-3 text-base {{ $day === $today ? 'font-bold' : '' }}" style="color: {{ $day === $today ? '#4c1d95' : '#6b5b7b' }};">
                                    {{ $day }}
                                    @if($day === $today)
                                    <span class="ml-2 text-xs uppercase tracking-wider text-[#d4a853] font-bold">Today</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right text-base italic {{ strtolower($time) === 'closed' ? 'text-red-700' : '' }}" style="{{ strtolower($time) !== 'closed' ? 'color: ' . ($day === $today ? '#4c1d95' : '#6b5b7b') : '' }}; {{ $day === $today ? 'font-weight: 600;' : '' }}">
                                    {{ $time }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(!empty($site['holiday_hours']))
                <div class="mt-4 thai-card p-4" style="border-color: rgba(212, 168, 83, 0.4);">
                    <h4 class="thai-heading font-bold mb-2" style="color: #4c1d95;">Special Hours</h4>
                    @foreach($site['holiday_hours'] as $holiday)
                    <div class="flex justify-between text-sm py-1 thai-heading">
                        <span style="color: #6b5b7b;">
                            {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                            @if($holiday['label']) &mdash; {{ $holiday['label'] }} @endif
                        </span>
                        <span class="italic {{ strtolower($holiday['hours']) === 'closed' ? 'text-red-700 font-medium' : '' }}" style="{{ strtolower($holiday['hours']) !== 'closed' ? 'color: #6b5b7b;' : '' }}">
                            {{ $holiday['hours'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Contact --}}
            <div>
                <h3 class="thai-heading text-2xl font-bold mb-6 flex items-center" style="color: #4c1d95;">
                    <svg class="w-6 h-6 mr-3 text-[#d4a853]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Find Us
                </h3>
                <div class="thai-card p-6 space-y-5">
                    @if(!empty($site['address']))
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-[#d4a853] mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="thai-heading">
                            <span style="color: #4c1d95;" class="text-base">{{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}</span>
                            @if(!empty($site['secondary_cta_url']))
                            <a href="{{ $site['secondary_cta_url'] }}" class="block text-[#d4a853] text-sm italic hover:underline mt-1" target="_blank">Get Directions &rarr;</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!empty($site['phone']))
                    <div class="flex items-center pt-5" style="border-top: 1px solid rgba(212, 168, 83, 0.15);">
                        <svg class="w-6 h-6 text-[#d4a853] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="thai-heading font-bold text-lg" style="color: #4c1d95;">{{ $site['phone'] }}</a>
                    </div>
                    @endif

                    @if(!empty($site['email']))
                    <div class="flex items-center pt-5" style="border-top: 1px solid rgba(212, 168, 83, 0.15);">
                        <svg class="w-6 h-6 text-[#d4a853] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $site['email'] }}" class="thai-heading italic text-[#d4a853] hover:underline">{{ $site['email'] }}</a>
                    </div>
                    @endif
                </div>

                {{-- CTA --}}
                <div class="mt-6">
                    @if($orderingEnabled)
                    <a href="#full-menu" class="block w-full text-center py-4 thai-heading font-bold hover:opacity-90 transition text-lg shadow-md text-[#4c1d95]" style="background: linear-gradient(135deg, #d4a853, #c49a3a);">
                        Order Online Now
                    </a>
                    @else
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="block w-full text-center py-4 thai-heading font-bold hover:opacity-90 transition text-lg shadow-md text-[#4c1d95]" style="background: linear-gradient(135deg, #d4a853, #c49a3a);">
                        Call to Order: {{ $site['phone'] ?? '' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Closing message --}}
        <div class="text-center mt-16 max-w-2xl mx-auto">
            <div class="thai-divider mb-6">
                <span class="text-lg">&loz;</span>
            </div>
            <p class="thai-heading italic text-lg leading-relaxed" style="color: #6b5b7b;">
                &ldquo;We bring the warmth of Thailand to every dish, honoring traditions passed down through generations.&rdquo;
            </p>
            <p class="thai-heading text-sm uppercase tracking-[0.3em] mt-4 font-bold" style="color: #d4a853;">
                &mdash; {{ $site['name'] }} &mdash;
            </p>
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['gallery']) && count($site['gallery']) > 0)
<section class="py-20" style="background-color: #f5f0e8;" id="gallery">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <div class="thai-ornament mb-4">&loz; &loz; &loz;</div>
            <h2 class="thai-heading text-4xl md:text-5xl font-bold mb-4" style="color: #4c1d95;">Gallery</h2>
            <div class="thai-divider max-w-md mx-auto">
                <span class="text-lg">&loz;</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-5">
            @foreach($site['gallery'] as $index => $photo)
            <div class="relative overflow-hidden group thai-card thai-card-hover p-1.5">
                <img src="{{ is_array($photo) ? ($photo['url'] ?? $photo['src'] ?? '') : $photo }}"
                     alt="{{ is_array($photo) ? ($photo['alt'] ?? 'Gallery photo') : 'Gallery photo' }}"
                     class="w-full h-56 md:h-64 object-cover group-hover:scale-105 transition-transform duration-500"
                     loading="lazy">
                @if(is_array($photo) && !empty($photo['caption']))
                <p class="thai-heading italic text-center text-sm mt-3 pb-1" style="color: #6b5b7b;">{{ $photo['caption'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@include('samples.partials.reservations-section')

@include('samples.partials.cart-ui')

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
