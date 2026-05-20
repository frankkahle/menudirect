@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    // These variables are needed by the template and partials
    // They're also defined in schema partial for JSON-LD, but @include scope doesn't carry into @section
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp
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

{{-- Announcements Banner --}}
@include('samples.partials.announcements-banner', ['announcements' => $site['announcements'] ?? []])

{{-- Hero Section with optional background image/video --}}
<section class="relative text-white py-24 md:py-32 overflow-hidden">
    {{-- Background: Video, Image, or Gradient --}}
    @if(!empty($site['hero_video']))
    <video autoplay muted loop playsinline @if(!empty($site['hero_poster'])) poster="{{ $site['hero_poster'] }}" @endif class="absolute inset-0 w-full h-full object-cover">
        <source src="{{ $site['hero_video'] }}" type="video/mp4">
    </video>
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    @elseif(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $site['hero_image'] ?? $site['cover_photo'] }}');"></div>
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    @else
    <div class="absolute inset-0 brand-gradient"></div>
    @endif

    <div class="relative max-w-6xl mx-auto px-4 text-center z-10">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-28 md:h-36 mx-auto mb-6 rounded-lg shadow-2xl bg-white/10 backdrop-blur p-2">
        @endif
        <h1 class="text-4xl md:text-6xl font-bold mb-4 drop-shadow-lg">{{ $site['name'] }}</h1>
        <p class="text-xl md:text-2xl mb-8 opacity-95 drop-shadow">{{ $site['tagline'] ?? '' }}</p>

        <div class="flex flex-col sm:flex-row justify-center gap-4">
            @if($orderingEnabled)
            <a href="#full-menu" class="bg-white text-gray-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition shadow-lg">
                🍽️ Order Online
            </a>
            @else
            <a href="{{ $site['cta_url'] ?? '#' }}" class="bg-white text-gray-900 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition shadow-lg">
                {{ $site['cta_text'] ?? 'Order Now' }}
            </a>
            @endif
            @if(!empty($site['secondary_cta_url']))
            <a href="{{ $site['secondary_cta_url'] }}" class="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-gray-900 transition backdrop-blur-sm bg-white/10">
                {{ $site['secondary_cta_text'] ?? 'Get Directions' }}
            </a>
            @else
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-gray-900 transition backdrop-blur-sm bg-white/10">
                📞 Call {{ $site['phone'] ?? '' }}
            </a>
            @endif
        </div>
    </div>
</section>

{{-- Online Ordering Banner --}}
@if($orderingEnabled)
{{-- Open Banner --}}
<section x-show="isRestaurantOpen" class="bg-green-600 text-white py-3">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Online Ordering Available
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-2">|</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-2">|</span> Delivery
            @endif
            <span class="mx-2">|</span> Pay at {{ ($orderingConfig['accepts_pickup'] ?? true) ? 'Pickup' : 'Delivery' }}
        </span>
    </div>
</section>
{{-- Closed Banner --}}
<section x-show="!isRestaurantOpen" x-cloak class="bg-amber-500 text-white py-3">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <strong>Currently Closed</strong>
            <span x-show="todayHours" class="mx-2">|</span>
            <span x-show="todayHours">Today: <span x-text="todayHours"></span></span>
            <span x-show="nextOpenLabel" class="mx-2">|</span>
            <span x-show="nextOpenLabel" x-text="nextOpenLabel"></span>
            <span class="mx-2">|</span>
            <span class="font-medium">Schedule Your Order</span>
        </span>
    </div>
</section>
@endif

{{-- Google Reviews Bar --}}
@if(!empty($site['google_place_id']) || !empty($site['social_proof']))
<section class="bg-gray-900 text-white py-4">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center items-center gap-6 text-sm">
            {{-- Google Rating --}}
            @if(!empty($site['google_rating']))
            <a href="/reviews" class="flex items-center gap-2 hover:opacity-80 transition">
                <img src="https://www.google.com/favicon.ico" alt="Google" class="w-5 h-5">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= round($site['google_rating']) ? 'text-yellow-400' : 'text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <span><strong>{{ $site['google_rating'] }}</strong> ({{ $site['google_review_count'] ?? '50+' }} reviews)</span>
            </a>
            @elseif(!empty($site['social_proof']['rating']))
            <span class="flex items-center">
                <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                </svg>
                <strong>{{ $site['social_proof']['rating'] }}</strong>&nbsp;recommend
            </span>
            @endif
            @if(!empty($site['social_proof']['reviews']))
            <span class="flex items-center">
                <svg class="w-5 h-5 text-brand-accent mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                {{ $site['social_proof']['reviews'] }} Reviews
            </span>
            @endif
            @if(!empty($site['social_proof']['followers']))
            <span class="flex items-center">
                <svg class="w-5 h-5 text-brand-accent mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                {{ $site['social_proof']['followers'] }} Followers
            </span>
            @endif
        </div>
    </div>
</section>
@endif

{{-- Features Bar --}}
@if(!empty($site['features']))
<section class="bg-white py-6 border-b">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-600">
            @foreach($site['features'] as $feature)
            <span class="flex items-center">
                <svg class="w-5 h-5 text-brand mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Location Selector (Multi-Location Support) --}}
@if(!empty($site['locations']) && count($site['locations']) > 1)
<section class="bg-brand/5 py-4 border-b">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <span class="text-sm font-medium text-gray-700 flex items-center">
                <svg class="w-5 h-5 mr-2 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ count($site['locations']) }} Locations:
            </span>
            <div class="flex flex-wrap justify-center gap-2">
                @foreach($site['locations'] as $location)
                <a href="{{ $location['url'] }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-full text-sm {{ $location['id'] === $site['id'] ? 'bg-brand text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }} transition">
                    @if($location['is_primary'])
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    @endif
                    {{ $location['name'] }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- Menu Highlights --}}
@if(!empty($site['menu_highlights']))
<section class="py-16">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Popular Dishes</h2>
        <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">Customer favorites you don't want to miss</p>
        <div class="grid md:grid-cols-3 gap-8">
            @foreach($site['menu_highlights'] as $item)
            <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition group">
                @if(!empty($item['image']))
                <div class="h-48 overflow-hidden">
                    <img src="{{ $item['image'] }}"
                         alt="{{ $item['alt_text'] ?? $item['name'] }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                         loading="lazy">
                </div>
                @endif
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $item['name'] }}</h3>
                        <span class="text-brand font-bold whitespace-nowrap ml-2">{{ $item['price'] }}</span>
                    </div>
                    <p class="text-gray-600">{{ $item['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @if(!empty($site['menu_categories']))
        <div class="text-center mt-8">
            <a href="#full-menu" class="text-brand font-semibold hover:underline">View Full Menu &rarr;</a>
        </div>
        @endif
    </div>
</section>
@endif

{{-- Testimonials --}}
@if(!empty($site['testimonials']))
<section class="py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12 text-gray-900">What Our Customers Say</h2>
        <div class="grid md:grid-cols-3 gap-8">
            @foreach($site['testimonials'] as $testimonial)
            <div class="bg-white rounded-lg shadow-md p-6">
                <svg class="w-8 h-8 text-brand-accent mb-4 opacity-50" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                </svg>
                <p class="text-gray-700 mb-4 italic">"{{ $testimonial['text'] }}"</p>
                <p class="text-brand font-semibold">— {{ $testimonial['author'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@include('samples.partials.menu-section')

@include('samples.partials.gallery-section')

@include('samples.partials.reservations-section')

@include('samples.partials.catering-section')

@include('samples.partials.google-reviews-section')

@include('samples.partials.sister-sites-section')

@include('samples.partials.hours-contact-section')

{{-- Social Links --}}
@if(!empty($site['social_links']))
<section class="bg-gray-900 text-white py-10">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <p class="text-xs tracking-widest uppercase text-gray-400 mb-4">Follow Us</p>
        <div class="flex justify-center items-center gap-6">
            @foreach($site['social_links'] as $platform => $url)
                @if($url)
                <a href="{{ $url }}" target="_blank" rel="noopener" class="text-gray-300 hover:text-white transition" aria-label="{{ ucfirst($platform) }}">
                    @if($platform === 'facebook')
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    @elseif($platform === 'instagram')
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    @elseif($platform === 'twitter' || $platform === 'x')
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    @elseif($platform === 'youtube')
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    @elseif($platform === 'tiktok')
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005.8 20.1a6.34 6.34 0 0010.86-4.43V8.81a8.16 8.16 0 004.77 1.52V6.89a4.85 4.85 0 01-1.84-.2z"/></svg>
                    @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif

@include('samples.partials.cart-ui')

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
