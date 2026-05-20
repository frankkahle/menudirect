@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap');
    body { background-color: #fefcf3; }
    .fh-heading { font-family: 'Libre Baskerville', Georgia, serif; }
    .fh-grain {
        position: relative;
    }
    .fh-grain::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.03;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
        pointer-events: none;
    }
    .fh-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: #6b7c5e;
    }
    .fh-divider::before,
    .fh-divider::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 100px;
        background: linear-gradient(to right, transparent, rgba(107, 124, 94, 0.5), transparent);
    }
    .fh-sage-line {
        width: 48px;
        height: 2px;
        background: #6b7c5e;
        border-radius: 1px;
    }
    .fh-card {
        background: #fefcf3;
        border: 1px solid rgba(107, 124, 94, 0.25);
        border-radius: 0.5rem;
    }
</style>

{{-- Initialize Cart if ordering is enabled --}}
@if($orderingEnabled)
<div x-data="cart({
    apiBaseUrl: '',
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

{{-- Split Hero Section (photo on LEFT, text on RIGHT) --}}
<section class="fh-grain border-b border-[#6b7c5e]/15" style="background-color: #fefcf3;">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-5 gap-0 items-stretch">
            {{-- LEFT: Cover photo (40%) --}}
            <div class="md:col-span-2 relative min-h-[300px] md:min-h-0 bg-[#e8e0d0] order-2 md:order-1">
                @if(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
                @php $heroImg = $site['hero_image'] ?? $site['cover_photo']; @endphp
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImg }}');">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent to-[#fefcf3]/20"></div>
                </div>
                @else
                <div class="absolute inset-0 flex items-center justify-center" style="background-color: #e8e0d0;">
                    <svg class="w-32 h-32 text-[#6b7c5e]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                @endif

                {{-- Organic accent badge --}}
                <div class="absolute bottom-6 left-6 hidden md:flex px-4 py-2 rounded-full bg-[#6b7c5e]/80 backdrop-blur-sm items-center">
                    <span class="fh-heading text-xs text-[#fefcf3] italic tracking-wide">Farm to Table</span>
                </div>
            </div>

            {{-- RIGHT: Text block (60%) --}}
            <div class="md:col-span-3 px-6 py-12 md:px-14 md:py-20 flex flex-col justify-center order-1 md:order-2" style="background-color: #fefcf3;">
                <div class="relative">
                    @if(!empty($site['logo']))
                    <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-16 md:h-20 mb-6 rounded-lg bg-white/50 p-2">
                    @endif

                    <div class="flex items-center gap-3 mb-6">
                        <span class="text-[#6b7c5e] text-lg">&#9752;</span>
                        <div class="fh-sage-line"></div>
                    </div>

                    <p class="fh-heading text-xs uppercase tracking-[0.3em] text-[#6b7c5e] font-bold mb-4">
                        @if(!empty($site['address']['city']))
                            Locally Rooted in {{ $site['address']['city'] }}
                        @else
                            Locally Sourced, Lovingly Made
                        @endif
                    </p>

                    <h1 class="fh-heading text-5xl md:text-6xl lg:text-7xl font-bold leading-tight mb-4" style="color: #5c4a32;">
                        {{ $site['name'] }}
                    </h1>

                    <div class="w-20 h-0.5 bg-[#6b7c5e]/40 mb-6 rounded"></div>

                    <p class="fh-heading text-xl md:text-2xl italic mb-10 leading-relaxed" style="color: #7a6b5a;">
                        {{ $site['tagline'] ?? 'Honest food, warm hearts.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($orderingEnabled)
                        <a href="#full-menu" class="inline-flex items-center justify-center px-8 py-4 rounded-lg fh-heading font-bold hover:opacity-90 transition shadow-md text-[#fefcf3]" style="background-color: #6b7c5e;">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Order Online
                        </a>
                        @else
                        <a href="{{ $site['cta_url'] ?? '#' }}" class="inline-flex items-center justify-center px-8 py-4 rounded-lg fh-heading font-bold hover:opacity-90 transition shadow-md text-[#fefcf3]" style="background-color: #6b7c5e;">
                            {{ $site['cta_text'] ?? 'Order Now' }}
                        </a>
                        @endif

                        @if(!empty($site['phone']))
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-flex items-center justify-center px-8 py-4 rounded-lg fh-heading font-bold transition border-2 border-[#6b7c5e] text-[#5c4a32] hover:bg-[#6b7c5e] hover:text-[#fefcf3]">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $site['phone'] }}
                        </a>
                        @endif
                    </div>

                    @if(!empty($site['address']['street']))
                    <p class="mt-8 text-sm fh-heading italic" style="color: #9a8b7a;">
                        {{ $site['address']['street'] }}@if(!empty($site['address']['city'])), {{ $site['address']['city'] }}@endif
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Online Ordering Banner --}}
@if($orderingEnabled)
<section x-show="isRestaurantOpen" class="py-3 border-b text-[#fefcf3]" style="background-color: #6b7c5e; border-color: rgba(0,0,0,0.1);">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center fh-heading">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="italic">Now Serving</span>
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-2 opacity-50">&#9752;</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-2 opacity-50">&#9752;</span> Delivery
            @endif
            <span class="mx-2 opacity-50">&#9752;</span> Pay at {{ ($orderingConfig['accepts_pickup'] ?? true) ? 'Pickup' : 'Delivery' }}
        </span>
    </div>
</section>
<section x-show="!isRestaurantOpen" x-cloak class="py-3 border-b" style="background-color: #5c4a32; border-color: rgba(0,0,0,0.1);">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm text-[#e8e0d0]">
        <span class="inline-flex items-center flex-wrap justify-center fh-heading">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <strong class="italic">Currently Closed</strong>
            <span x-show="todayHours" class="mx-2 opacity-50">&#9752;</span>
            <span x-show="todayHours">Today: <span x-text="todayHours"></span></span>
            <span x-show="nextOpenLabel" class="mx-2 opacity-50">&#9752;</span>
            <span x-show="nextOpenLabel" x-text="nextOpenLabel"></span>
            <span class="mx-2 opacity-50">&#9752;</span>
            <span class="font-medium text-[#fefcf3]">Schedule Your Order</span>
        </span>
    </div>
</section>
@endif

{{-- Features Strip --}}
@if(!empty($site['features']))
<section class="py-6 border-b" style="background-color: #f0ebe0; border-color: rgba(107, 124, 94, 0.15);">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-3 text-sm" style="color: #5c4a32;">
            @foreach($site['features'] as $index => $feature)
            @if($index > 0)
            <span class="text-[#6b7c5e] hidden sm:inline">&#9752;</span>
            @endif
            <span class="flex items-center fh-heading italic">
                <svg class="w-4 h-4 text-[#6b7c5e] mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
<section class="py-20 fh-grain" style="background-color: #f5f0e5;" id="hours">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-[#6b7c5e] text-2xl">&#9752;</span>
            <h2 class="fh-heading text-4xl md:text-5xl font-bold mt-4 mb-4" style="color: #5c4a32;">Visit Us</h2>
            <div class="fh-divider max-w-md mx-auto">
                <span class="text-sm">&#9752;</span>
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
                <h3 class="fh-heading text-2xl font-bold mb-6 flex items-center" style="color: #5c4a32;">
                    <svg class="w-6 h-6 mr-3 text-[#6b7c5e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Hours
                </h3>
                <div class="fh-card overflow-hidden">
                    <table class="w-full fh-heading">
                        <tbody>
                            @foreach($sortedHours as $day => $time)
                            <tr class="border-b last:border-0" style="border-color: rgba(107, 124, 94, 0.15); {{ $day === $today ? 'background-color: rgba(107, 124, 94, 0.08);' : '' }}">
                                <td class="px-5 py-3 {{ $day === $today ? 'font-bold' : '' }}" style="color: {{ $day === $today ? '#5c4a32' : '#7a6b5a' }};">
                                    {{ $day }}
                                    @if($day === $today)
                                    <span class="ml-2 text-xs uppercase tracking-wider text-[#6b7c5e] font-bold">Today</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right italic {{ strtolower($time) === 'closed' ? 'text-red-700' : '' }}" style="{{ strtolower($time) !== 'closed' ? 'color: ' . ($day === $today ? '#5c4a32' : '#9a8b7a') : '' }}; {{ $day === $today ? 'font-weight: 600;' : '' }}">
                                    {{ $time }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(!empty($site['holiday_hours']))
                <div class="mt-4 fh-card p-4" style="border-color: rgba(107, 124, 94, 0.35);">
                    <h4 class="fh-heading font-bold mb-2" style="color: #5c4a32;">Special Hours</h4>
                    @foreach($site['holiday_hours'] as $holiday)
                    <div class="flex justify-between text-sm py-1 fh-heading">
                        <span style="color: #7a6b5a;">
                            {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                            @if($holiday['label']) &mdash; {{ $holiday['label'] }} @endif
                        </span>
                        <span class="italic {{ strtolower($holiday['hours']) === 'closed' ? 'text-red-700 font-medium' : '' }}" style="{{ strtolower($holiday['hours']) !== 'closed' ? 'color: #7a6b5a;' : '' }}">
                            {{ $holiday['hours'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Contact --}}
            <div>
                <h3 class="fh-heading text-2xl font-bold mb-6 flex items-center" style="color: #5c4a32;">
                    <svg class="w-6 h-6 mr-3 text-[#6b7c5e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Find Us
                </h3>
                <div class="fh-card p-6 space-y-5">
                    @if(!empty($site['address']))
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-[#6b7c5e] mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="fh-heading">
                            <span style="color: #5c4a32;">{{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}</span>
                            @if(!empty($site['secondary_cta_url']))
                            <a href="{{ $site['secondary_cta_url'] }}" class="block text-[#6b7c5e] text-sm italic hover:underline mt-1" target="_blank">Get Directions &rarr;</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!empty($site['phone']))
                    <div class="flex items-center pt-5" style="border-top: 1px solid rgba(107, 124, 94, 0.15);">
                        <svg class="w-6 h-6 text-[#6b7c5e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="fh-heading font-bold text-lg" style="color: #5c4a32;">{{ $site['phone'] }}</a>
                    </div>
                    @endif

                    @if(!empty($site['email']))
                    <div class="flex items-center pt-5" style="border-top: 1px solid rgba(107, 124, 94, 0.15);">
                        <svg class="w-6 h-6 text-[#6b7c5e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $site['email'] }}" class="fh-heading italic text-[#6b7c5e] hover:underline">{{ $site['email'] }}</a>
                    </div>
                    @endif
                </div>

                {{-- CTA --}}
                <div class="mt-6">
                    @if($orderingEnabled)
                    <a href="#full-menu" class="block w-full text-center text-[#fefcf3] py-4 rounded-lg fh-heading font-bold hover:opacity-90 transition text-lg shadow-md" style="background-color: #6b7c5e;">
                        Order Online Now
                    </a>
                    @else
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="block w-full text-center text-[#fefcf3] py-4 rounded-lg fh-heading font-bold hover:opacity-90 transition text-lg shadow-md" style="background-color: #6b7c5e;">
                        Call to Order: {{ $site['phone'] ?? '' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Closing message --}}
        <div class="text-center mt-16 max-w-2xl mx-auto">
            <div class="fh-divider mb-6">
                <span class="text-sm">&#9752;</span>
            </div>
            <p class="fh-heading italic text-lg leading-relaxed" style="color: #7a6b5a;">
                &ldquo;Good food is the foundation of genuine happiness. We grow it, cook it, and share it with love.&rdquo;
            </p>
            <p class="fh-heading text-sm uppercase tracking-[0.3em] mt-4 font-bold" style="color: #6b7c5e;">
                &mdash; The {{ $site['name'] }} Kitchen &mdash;
            </p>
        </div>
    </div>
</section>
@endif

{{-- Gallery --}}
@if(!empty($site['gallery']) && count($site['gallery']) > 0)
<section class="py-20" style="background-color: #fefcf3;" id="gallery">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <span class="text-[#6b7c5e] text-2xl">&#9752;</span>
            <h2 class="fh-heading text-4xl md:text-5xl font-bold mt-4 mb-4" style="color: #5c4a32;">From the Farm</h2>
            <div class="fh-divider max-w-md mx-auto">
                <span class="text-sm">&#9752;</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-5 md:gap-6">
            @foreach($site['gallery'] as $index => $photo)
            <div class="fh-card overflow-hidden group p-2">
                <div class="overflow-hidden rounded">
                    <img src="{{ is_array($photo) ? ($photo['url'] ?? $photo['src'] ?? '') : $photo }}"
                         alt="{{ is_array($photo) ? ($photo['alt'] ?? 'Gallery photo') : 'Gallery photo' }}"
                         class="w-full h-56 md:h-64 object-cover group-hover:scale-105 transition-transform duration-500"
                         loading="lazy">
                </div>
                @if(is_array($photo) && !empty($photo['caption']))
                <p class="fh-heading italic text-center text-sm mt-3 pb-1" style="color: #7a6b5a;">{{ $photo['caption'] }}</p>
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
