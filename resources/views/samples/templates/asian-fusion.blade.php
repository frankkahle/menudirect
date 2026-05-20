@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');
    body { background-color: #ffffff; }
    .af-heading { font-family: 'Space Grotesk', system-ui, sans-serif; }
    .af-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: #dc2626;
    }
    .af-divider::before,
    .af-divider::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 80px;
        background: #dc2626;
    }
    .af-red-line {
        width: 48px;
        height: 3px;
        background: #dc2626;
    }
    .af-card {
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    .af-card:hover {
        border-color: #dc2626;
        box-shadow: 0 4px 20px rgba(220, 38, 38, 0.08);
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

{{-- Split Hero Section --}}
<section class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-5 gap-0 items-stretch">
            {{-- LEFT: Text block (60%) --}}
            <div class="md:col-span-3 px-6 py-12 md:px-14 md:py-20 flex flex-col justify-center bg-white relative">
                <div class="relative">
                    @if(!empty($site['logo']))
                    <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-16 md:h-20 mb-8">
                    @endif

                    <div class="af-red-line mb-6"></div>

                    <p class="af-heading text-xs uppercase tracking-[0.4em] text-red-600 font-semibold mb-4">
                        Modern Asian Cuisine
                    </p>

                    <h1 class="af-heading text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 leading-[0.95] mb-6 tracking-tight">
                        {{ $site['name'] }}
                    </h1>

                    <p class="af-heading text-xl md:text-2xl text-gray-500 font-light mb-10 leading-relaxed max-w-lg">
                        {{ $site['tagline'] ?? 'Where tradition meets innovation.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($orderingEnabled)
                        <a href="#full-menu" class="inline-flex items-center justify-center bg-gray-900 text-white px-8 py-4 af-heading font-semibold hover:bg-red-600 transition-colors tracking-wide">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Order Online
                        </a>
                        @else
                        <a href="{{ $site['cta_url'] ?? '#' }}" class="inline-flex items-center justify-center bg-gray-900 text-white px-8 py-4 af-heading font-semibold hover:bg-red-600 transition-colors tracking-wide">
                            {{ $site['cta_text'] ?? 'Order Now' }}
                        </a>
                        @endif

                        @if(!empty($site['phone']))
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-flex items-center justify-center bg-transparent text-gray-900 px-8 py-4 af-heading font-semibold hover:bg-gray-900 hover:text-white transition-colors border border-gray-900">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $site['phone'] }}
                        </a>
                        @endif
                    </div>

                    @if(!empty($site['address']['street']))
                    <p class="mt-8 text-sm text-gray-400 af-heading tracking-wide">
                        {{ $site['address']['street'] }}@if(!empty($site['address']['city'])), {{ $site['address']['city'] }}@endif
                    </p>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Cover photo (40%) --}}
            <div class="md:col-span-2 relative min-h-[300px] md:min-h-0 bg-gray-100">
                @if(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
                @php $heroImg = $site['hero_image'] ?? $site['cover_photo']; @endphp
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImg }}');">
                    <div class="absolute inset-0 bg-gradient-to-l from-transparent to-white/10"></div>
                </div>
                @else
                <div class="absolute inset-0 flex items-center justify-center bg-gray-900">
                    <svg class="w-32 h-32 text-red-600/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                @endif

                {{-- Geometric accent corner --}}
                <div class="absolute top-0 right-0 w-20 h-20 hidden md:block">
                    <div class="absolute top-4 right-4 w-12 h-12 border border-red-600/40"></div>
                    <div class="absolute top-6 right-6 w-12 h-12 border border-red-600/20"></div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Online Ordering Banner --}}
@if($orderingEnabled)
<section x-show="isRestaurantOpen" class="bg-gray-900 text-white py-3 border-b border-gray-800">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center af-heading tracking-wide">
            <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="font-medium">Now Open</span>
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-3 text-red-500">|</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-3 text-red-500">|</span> Delivery
            @endif
            <span class="mx-3 text-red-500">|</span> Pay at {{ ($orderingConfig['accepts_pickup'] ?? true) ? 'Pickup' : 'Delivery' }}
        </span>
    </div>
</section>
<section x-show="!isRestaurantOpen" x-cloak class="bg-gray-800 text-gray-300 py-3 border-b border-gray-700">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center af-heading tracking-wide">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <strong>Currently Closed</strong>
            <span x-show="todayHours" class="mx-3 text-red-500">|</span>
            <span x-show="todayHours">Today: <span x-text="todayHours"></span></span>
            <span x-show="nextOpenLabel" class="mx-3 text-red-500">|</span>
            <span x-show="nextOpenLabel" x-text="nextOpenLabel"></span>
            <span class="mx-3 text-red-500">|</span>
            <span class="font-medium text-white">Schedule Your Order</span>
        </span>
    </div>
</section>
@endif

{{-- Features Strip --}}
@if(!empty($site['features']))
<section class="bg-gray-50 py-5 border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-3 text-sm text-gray-600">
            @foreach($site['features'] as $index => $feature)
            @if($index > 0)
            <span class="text-red-500 hidden sm:inline text-xs">&#9646;</span>
            @endif
            <span class="flex items-center af-heading tracking-wide">
                <svg class="w-4 h-4 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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

{{-- Hours Section --}}
@if(!empty($site['hours']))
<section class="py-20 bg-white" id="hours">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <div class="af-red-line mx-auto mb-6"></div>
            <h2 class="af-heading text-4xl md:text-5xl font-bold text-gray-900 mb-4 tracking-tight">Hours</h2>
            <div class="af-divider max-w-md mx-auto">
                <span class="text-lg">+</span>
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
                <h3 class="af-heading text-xl font-semibold text-gray-900 mb-6 flex items-center tracking-wide">
                    <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Opening Hours
                </h3>
                <div class="border border-gray-200">
                    <table class="w-full af-heading">
                        <tbody>
                            @foreach($sortedHours as $day => $time)
                            <tr class="border-b border-gray-100 last:border-0 {{ $day === $today ? 'bg-gray-50' : '' }}">
                                <td class="px-5 py-3 text-sm {{ $day === $today ? 'font-bold text-gray-900' : 'text-gray-600' }} tracking-wide">
                                    {{ $day }}
                                    @if($day === $today)
                                    <span class="ml-2 text-[10px] uppercase tracking-widest text-red-500 font-bold">Today</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right text-sm {{ strtolower($time) === 'closed' ? 'text-red-600 font-medium' : ($day === $today ? 'text-gray-900 font-semibold' : 'text-gray-500') }}">
                                    {{ $time }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(!empty($site['holiday_hours']))
                <div class="mt-4 border border-red-200 bg-red-50/50 p-4">
                    <h4 class="af-heading font-bold text-gray-900 text-sm mb-2 tracking-wide">Special Hours</h4>
                    @foreach($site['holiday_hours'] as $holiday)
                    <div class="flex justify-between text-sm py-1 af-heading">
                        <span class="text-gray-600">
                            {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                            @if($holiday['label']) &mdash; {{ $holiday['label'] }} @endif
                        </span>
                        <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                            {{ $holiday['hours'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Contact --}}
            <div>
                <h3 class="af-heading text-xl font-semibold text-gray-900 mb-6 flex items-center tracking-wide">
                    <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Contact
                </h3>
                <div class="border border-gray-200 p-6 space-y-5">
                    @if(!empty($site['address']))
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="af-heading">
                            <span class="text-gray-800 text-sm">{{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}</span>
                            @if(!empty($site['secondary_cta_url']))
                            <a href="{{ $site['secondary_cta_url'] }}" class="block text-red-500 text-sm hover:underline mt-1" target="_blank">Get Directions &rarr;</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!empty($site['phone']))
                    <div class="flex items-center border-t border-gray-100 pt-5">
                        <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="af-heading text-gray-900 hover:text-red-500 font-semibold text-lg transition">{{ $site['phone'] }}</a>
                    </div>
                    @endif

                    @if(!empty($site['email']))
                    <div class="flex items-center border-t border-gray-100 pt-5">
                        <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $site['email'] }}" class="af-heading text-gray-600 hover:text-red-500 transition text-sm">{{ $site['email'] }}</a>
                    </div>
                    @endif
                </div>

                {{-- CTA --}}
                <div class="mt-6">
                    @if($orderingEnabled)
                    <a href="#full-menu" class="block w-full text-center bg-gray-900 text-white py-4 af-heading font-semibold hover:bg-red-600 transition-colors tracking-wide">
                        Order Online Now
                    </a>
                    @else
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="block w-full text-center bg-gray-900 text-white py-4 af-heading font-semibold hover:bg-red-600 transition-colors tracking-wide">
                        Call to Order: {{ $site['phone'] ?? '' }}
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
            <div class="af-red-line mx-auto mb-6"></div>
            <h2 class="af-heading text-4xl md:text-5xl font-bold text-gray-900 mb-4 tracking-tight">Gallery</h2>
            <div class="af-divider max-w-md mx-auto">
                <span class="text-lg">+</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-1">
            @foreach($site['gallery'] as $index => $photo)
            @php $photoCaption = is_array($photo) ? ($photo['caption'] ?? null) : null; @endphp
            <figure class="relative overflow-hidden group">
                <div class="aspect-square overflow-hidden">
                    <img src="{{ is_array($photo) ? ($photo['url'] ?? $photo['src'] ?? '') : $photo }}"
                         alt="{{ $photoCaption ?: (is_array($photo) ? ($photo['alt'] ?? 'Gallery photo') : 'Gallery photo') }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                         loading="lazy">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-500"></div>
                </div>
                @if($photoCaption)
                <figcaption class="px-4 py-3 text-sm text-gray-800 text-center af-heading bg-white">{{ $photoCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Location/Contact --}}
<section class="py-20 bg-white" id="contact">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="af-divider mb-6">
            <span class="text-lg">+</span>
        </div>
        <p class="af-heading text-gray-500 text-lg leading-relaxed max-w-2xl mx-auto">
            We look forward to serving you. Visit us or order online for a modern dining experience.
        </p>
        <p class="af-heading text-gray-900 text-sm uppercase tracking-[0.3em] mt-6 font-semibold">
            &mdash; {{ $site['name'] }} &mdash;
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
