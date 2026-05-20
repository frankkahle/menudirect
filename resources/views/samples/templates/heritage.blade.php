@extends('samples.layout')

@include('samples.partials.schema')

@section('content')
@php
    // These variables are needed by the template and partials
    // They're also defined in schema partial for JSON-LD, but @include scope doesn't carry into @section
    $orderingEnabled = !empty($site['ordering']['enabled']);
    $orderingConfig = $site['ordering'] ?? [];
@endphp

{{-- Global warm styling for Heritage template --}}
<style>
    body { background-color: #fffbeb; }
    .heritage-serif { font-family: Georgia, 'Times New Roman', 'Iowan Old Style', Palatino, serif; }
    .heritage-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        color: #b45309;
    }
    .heritage-divider::before,
    .heritage-divider::after {
        content: '';
        height: 1px;
        flex: 1;
        max-width: 120px;
        background: linear-gradient(to right, transparent, rgba(180, 83, 9, 0.5), transparent);
    }
    .heritage-ornament {
        display: inline-block;
        color: #b45309;
        font-size: 1.25rem;
        letter-spacing: 0.5rem;
    }
    .heritage-menu-row {
        display: flex;
        align-items: baseline;
        gap: 0.75rem;
    }
    .heritage-menu-row .heritage-menu-dots {
        flex: 1;
        border-bottom: 1px dotted rgba(120, 53, 15, 0.35);
        margin: 0 0.5rem;
        transform: translateY(-0.35rem);
    }
    .heritage-scrapbook {
        background-color: #fffdf7;
        padding: 0.75rem 0.75rem 2.5rem;
        box-shadow: 0 10px 25px -10px rgba(120, 53, 15, 0.25), 0 4px 10px -5px rgba(120, 53, 15, 0.12);
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

{{-- Announcements Banner --}}
@include('samples.partials.announcements-banner', ['announcements' => $site['announcements'] ?? []])

{{-- Split Hero Section (Heritage style — newspaper/menu cover feel) --}}
<section class="bg-amber-50 border-b-2 border-amber-900/20">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-5 gap-0 items-stretch">
            {{-- LEFT: Text block on warm background (60%) --}}
            <div class="md:col-span-3 px-6 py-12 md:px-12 md:py-20 flex flex-col justify-center bg-amber-50 relative">
                {{-- Decorative inner border --}}
                <div class="absolute inset-4 md:inset-6 border border-amber-900/15 pointer-events-none"></div>
                <div class="relative">
                    @if(!empty($site['logo']))
                    <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-20 md:h-24 mb-6 rounded bg-white/50 p-2 shadow-sm">
                    @endif

                    <p class="heritage-ornament mb-3">&bull; &bull; &bull;</p>

                    <p class="text-xs uppercase tracking-[0.3em] text-amber-800 font-semibold mb-3">
                        @if(!empty($site['address']['city']))
                            Est. in {{ $site['address']['city'] }}
                        @else
                            Since Day One
                        @endif
                    </p>

                    <h1 class="heritage-serif font-serif text-5xl md:text-6xl lg:text-7xl font-bold text-stone-900 leading-tight mb-4">
                        {{ $site['name'] }}
                    </h1>

                    <div class="w-24 h-1 bg-amber-700 mb-6"></div>

                    <p class="heritage-serif font-serif text-xl md:text-2xl text-stone-700 italic mb-8 leading-relaxed">
                        {{ $site['tagline'] ?? 'A warm welcome awaits you.' }}
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        @if($orderingEnabled)
                        <a href="#full-menu" class="inline-flex items-center justify-center bg-stone-900 text-amber-50 px-8 py-4 rounded-sm font-semibold hover:bg-stone-800 transition shadow-md border-2 border-stone-900">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Order Online
                        </a>
                        @else
                        <a href="{{ $site['cta_url'] ?? '#' }}" class="inline-flex items-center justify-center bg-stone-900 text-amber-50 px-8 py-4 rounded-sm font-semibold hover:bg-stone-800 transition shadow-md border-2 border-stone-900">
                            {{ $site['cta_text'] ?? 'Order Now' }}
                        </a>
                        @endif

                        @if(!empty($site['phone']))
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="inline-flex items-center justify-center bg-transparent text-stone-900 px-8 py-4 rounded-sm font-semibold hover:bg-stone-900 hover:text-amber-50 transition border-2 border-stone-900">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $site['phone'] }}
                        </a>
                        @endif
                    </div>

                    @if(!empty($site['address']['street']))
                    <p class="mt-6 text-sm text-stone-600 heritage-serif font-serif italic">
                        {{ $site['address']['street'] }}@if(!empty($site['address']['city'])), {{ $site['address']['city'] }}@endif
                    </p>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Cover photo (40%) --}}
            <div class="md:col-span-2 relative min-h-[300px] md:min-h-0 bg-stone-100">
                @if(!empty($site['hero_image'] ?? $site['cover_photo'] ?? null))
                @php $heroImg = $site['hero_image'] ?? $site['cover_photo']; @endphp
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $heroImg }}');">
                    <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-amber-50/20"></div>
                </div>
                @else
                <div class="absolute inset-0 flex items-center justify-center brand-gradient">
                    <svg class="w-32 h-32 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                @endif

                {{-- Vintage corner stamp --}}
                <div class="absolute top-6 right-6 hidden md:flex w-24 h-24 rounded-full border-2 border-amber-50 items-center justify-center bg-stone-900/40 backdrop-blur-sm">
                    <div class="text-center text-amber-50">
                        <p class="heritage-serif font-serif text-xs uppercase tracking-widest">Welcome</p>
                        <div class="w-8 h-px bg-amber-50/60 mx-auto my-1"></div>
                        <p class="heritage-serif font-serif text-[10px] italic">family owned</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Online Ordering Banner --}}
@if($orderingEnabled)
{{-- Open Banner --}}
<section x-show="isRestaurantOpen" class="bg-amber-800 text-amber-50 py-3 border-b border-amber-900">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center heritage-serif font-serif">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="italic">Now Serving</span>
            @if($orderingConfig['accepts_pickup'] ?? true)
            <span class="mx-2">&bull;</span> Pickup
            @endif
            @if($orderingConfig['accepts_delivery'] ?? false)
            <span class="mx-2">&bull;</span> Delivery
            @endif
            <span class="mx-2">&bull;</span> Pay at {{ ($orderingConfig['accepts_pickup'] ?? true) ? 'Pickup' : 'Delivery' }}
        </span>
    </div>
</section>
{{-- Closed Banner --}}
<section x-show="!isRestaurantOpen" x-cloak class="bg-stone-700 text-amber-50 py-3 border-b border-stone-800">
    <div class="max-w-6xl mx-auto px-4 text-center text-sm">
        <span class="inline-flex items-center flex-wrap justify-center heritage-serif font-serif">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <strong class="italic">Currently Closed</strong>
            <span x-show="todayHours" class="mx-2">&bull;</span>
            <span x-show="todayHours">Today: <span x-text="todayHours"></span></span>
            <span x-show="nextOpenLabel" class="mx-2">&bull;</span>
            <span x-show="nextOpenLabel" x-text="nextOpenLabel"></span>
            <span class="mx-2">&bull;</span>
            <span class="font-medium">Schedule Your Order</span>
        </span>
    </div>
</section>
@endif

{{-- Features Bar (warm styled) --}}
@if(!empty($site['features']))
<section class="bg-stone-100 py-6 border-b border-amber-900/10">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center items-center gap-x-8 gap-y-3 text-sm text-stone-700">
            @foreach($site['features'] as $index => $feature)
            @if($index > 0)
            <span class="text-amber-700 hidden sm:inline">&#x2766;</span>
            @endif
            <span class="flex items-center heritage-serif font-serif italic">
                <svg class="w-4 h-4 text-amber-700 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
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
<section class="bg-amber-50 py-4 border-b border-amber-900/10">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <span class="text-sm font-medium text-stone-700 flex items-center heritage-serif font-serif">
                <svg class="w-5 h-5 mr-2 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ count($site['locations']) }} Locations:
            </span>
            <div class="flex flex-wrap justify-center gap-2">
                @foreach($site['locations'] as $location)
                <a href="{{ $location['url'] }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-sm text-sm border {{ $location['id'] === $site['id'] ? 'bg-stone-900 text-amber-50 border-stone-900' : 'bg-white text-stone-700 hover:bg-amber-50 border-amber-900/20' }} transition">
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

{{-- Menu Highlights — warm bordered cards --}}
@if(!empty($site['menu_highlights']))
<section class="py-20 bg-orange-50/60">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-800 font-semibold mb-3">House Favorites</p>
            <h2 class="heritage-serif font-serif text-4xl md:text-5xl font-bold text-stone-900 mb-4">Guest Favorites</h2>
            <div class="heritage-divider max-w-md mx-auto">
                <span class="heritage-ornament text-lg">&#x2766;</span>
            </div>
            <p class="heritage-serif font-serif italic text-stone-600 mt-4 max-w-2xl mx-auto">
                Time-tested dishes our regulars come back for, week after week.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            @foreach($site['menu_highlights'] as $item)
            <div class="bg-white border-2 border-amber-900/15 overflow-hidden hover:border-amber-700/40 hover:shadow-xl transition-all duration-300 group">
                @if(!empty($item['image']))
                <div class="h-56 overflow-hidden border-b-2 border-amber-900/10">
                    <img src="{{ $item['image'] }}"
                         alt="{{ $item['alt_text'] ?? $item['name'] }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         loading="lazy">
                </div>
                @endif
                <div class="p-6">
                    <div class="flex justify-between items-baseline mb-3 gap-3">
                        <h3 class="heritage-serif font-serif text-2xl font-bold text-stone-900 leading-tight">{{ $item['name'] }}</h3>
                        <span class="heritage-serif font-serif text-amber-800 font-bold text-xl whitespace-nowrap">{{ $item['price'] }}</span>
                    </div>
                    <div class="w-12 h-px bg-amber-700/60 mb-3"></div>
                    <p class="text-stone-600 text-sm leading-relaxed">{{ $item['desc'] ?? ($item['description'] ?? '') }}</p>
                </div>
            </div>
            @endforeach
        </div>

        @if(!empty($site['menu_categories']))
        <div class="text-center mt-12">
            <a href="#full-menu" class="inline-flex items-center heritage-serif font-serif italic text-amber-800 hover:text-amber-900 font-semibold text-lg border-b-2 border-amber-700/40 hover:border-amber-800 pb-1 transition">
                View Our Complete Menu
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
        @endif
    </div>
</section>
@endif

<hr class="border-0 h-px bg-gradient-to-r from-transparent via-amber-900/20 to-transparent max-w-6xl mx-auto">

{{-- Testimonial Carousel (Alpine.js) --}}
@if(!empty($site['testimonials']))
@php $testimonialCount = count($site['testimonials']); @endphp
<section class="py-20 bg-amber-50">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-amber-800 font-semibold mb-3">Kind Words</p>
        <h2 class="heritage-serif font-serif text-4xl md:text-5xl font-bold text-stone-900 mb-10">From Our Guests</h2>

        <div x-data="{ current: 0, total: {{ $testimonialCount }}, autoplay: true }"
             x-init="if (total > 1) { setInterval(() => { if (autoplay) current = (current + 1) % total }, 7000) }"
             class="relative">

            <div class="relative bg-white border-2 border-amber-900/15 px-8 py-14 md:px-16 md:py-16 shadow-lg min-h-[280px]">
                {{-- Decorative quotation marks --}}
                <div class="absolute top-4 left-6 heritage-serif font-serif text-[8rem] md:text-[10rem] leading-none text-amber-700/15 select-none pointer-events-none">
                    &ldquo;
                </div>
                <div class="absolute bottom-0 right-6 heritage-serif font-serif text-[8rem] md:text-[10rem] leading-none text-amber-700/15 select-none pointer-events-none">
                    &rdquo;
                </div>

                @foreach($site['testimonials'] as $index => $testimonial)
                <div x-show="current === {{ $index }}"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     @if($index > 0) style="display: none;" @endif
                     class="relative z-10">

                    {{-- Star rating --}}
                    <div class="flex justify-center mb-6">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        @endfor
                    </div>

                    <p class="heritage-serif font-serif text-xl md:text-2xl text-stone-700 italic leading-relaxed mb-6">
                        &ldquo;{{ $testimonial['text'] }}&rdquo;
                    </p>

                    <div class="heritage-divider max-w-[120px] mx-auto mb-4">
                        <span class="heritage-ornament text-base">&bull;</span>
                    </div>

                    <p class="heritage-serif font-serif text-amber-800 font-bold uppercase tracking-widest text-sm">
                        &mdash; {{ $testimonial['author'] }} &mdash;
                    </p>
                </div>
                @endforeach
            </div>

            @if($testimonialCount > 1)
            {{-- Navigation buttons --}}
            <div class="flex justify-between items-center mt-8">
                <button @click="autoplay = false; current = (current - 1 + total) % total"
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white border-2 border-amber-900/20 text-stone-700 hover:bg-stone-900 hover:text-amber-50 hover:border-stone-900 transition shadow-md"
                        aria-label="Previous testimonial">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Dot indicators --}}
                <div class="flex items-center gap-2">
                    @foreach($site['testimonials'] as $index => $_)
                    <button @click="autoplay = false; current = {{ $index }}"
                            :class="current === {{ $index }} ? 'w-8 bg-amber-700' : 'w-2 bg-amber-900/25'"
                            class="h-2 rounded-full transition-all duration-300"
                            aria-label="Go to testimonial {{ $index + 1 }}"></button>
                    @endforeach
                </div>

                <button @click="autoplay = false; current = (current + 1) % total"
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white border-2 border-amber-900/20 text-stone-700 hover:bg-stone-900 hover:text-amber-50 hover:border-stone-900 transition shadow-md"
                        aria-label="Next testimonial">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

<hr class="border-0 h-px bg-gradient-to-r from-transparent via-amber-900/20 to-transparent max-w-6xl mx-auto">

{{-- Table-style Full Menu --}}
@if(!empty($site['menu_categories']))
<section class="py-20 bg-stone-100" id="full-menu"
         @if(count($site['menu_categories']) > 1) x-data="{ activeCategory: '{{ array_key_first($site['menu_categories']) }}' }" @endif>
    <div class="max-w-5xl mx-auto px-4">
        <div class="text-center mb-12">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-800 font-semibold mb-3">The Full Bill of Fare</p>
            <h2 class="heritage-serif font-serif text-4xl md:text-5xl font-bold text-stone-900 mb-4">Our Menu</h2>
            <div class="heritage-divider max-w-md mx-auto">
                <span class="heritage-ornament text-lg">&#x2766;</span>
            </div>
            <p class="heritage-serif font-serif italic text-stone-600 mt-4 max-w-2xl mx-auto">
                @if($orderingEnabled)
                    Select an item to add it to your order.
                @else
                    Something comforting for every appetite.
                @endif
            </p>
        </div>

        {{-- Dietary Legend — only show if the menu actually uses dietary tags --}}
        @php
            $hasDietaryItems = false;
            foreach ($site['menu_categories'] ?? [] as $cat) {
                foreach ($cat['items'] ?? [] as $item) {
                    if (!empty($item['dietary'])) { $hasDietaryItems = true; break 2; }
                }
            }
        @endphp
        @if($hasDietaryItems)
        <div class="flex flex-wrap justify-center gap-4 mb-8 text-xs text-stone-600 heritage-serif font-serif">
            <span class="inline-flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">&#127793;</span> Vegetarian</span>
            <span class="inline-flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">&#127807;</span> Vegan</span>
            <span class="inline-flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center text-[10px] font-bold">GF</span> Gluten Free</span>
            <span class="inline-flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center">&#127798;</span> Spicy</span>
        </div>
        @endif

        {{-- Category Tabs --}}
        @if(count($site['menu_categories']) > 1)
        <div class="flex flex-wrap justify-center gap-2 mb-12 border-y border-amber-900/15 py-4">
            @foreach($site['menu_categories'] as $categoryKey => $category)
            <button @click="activeCategory = '{{ $categoryKey }}'"
                    :class="activeCategory === '{{ $categoryKey }}' ? 'bg-stone-900 text-amber-50 border-stone-900' : 'bg-transparent text-stone-700 border-amber-900/20 hover:bg-amber-50 hover:border-amber-800/50'"
                    class="heritage-serif font-serif italic text-base px-5 py-2 border-2 transition">
                {{ $category['name'] }}
            </button>
            @endforeach
        </div>
        @endif

        {{-- Menu Categories — each as a bordered table/list --}}
        @foreach($site['menu_categories'] as $categoryKey => $category)
        <div class="mb-12"
             @if(count($site['menu_categories']) > 1) x-show="activeCategory === '{{ $categoryKey }}'" x-transition.opacity @endif>

            {{-- Category heading (only shown when no tabs, or as menu cover style) --}}
            @if(count($site['menu_categories']) === 1)
            <div class="text-center mb-10">
                <h3 class="heritage-serif font-serif text-3xl md:text-4xl font-bold text-stone-900 mb-2">{{ $category['name'] }}</h3>
                <div class="heritage-divider max-w-xs mx-auto">
                    <span class="heritage-ornament text-sm">&bull;</span>
                </div>
            </div>
            @else
            <div class="text-center mb-8">
                <h3 class="heritage-serif font-serif text-3xl font-bold text-stone-900 inline-block border-b-2 border-amber-700 pb-2 px-4">{{ $category['name'] }}</h3>
            </div>
            @endif

            @if(!empty($category['description']))
            <p class="heritage-serif font-serif italic text-center text-stone-600 mb-6">{{ $category['description'] }}</p>
            @endif

            {{-- Menu list — like a physical menu --}}
            <div class="bg-white border-2 border-amber-900/15 shadow-md divide-y divide-amber-900/10">
                @foreach($category['items'] as $itemIndex => $item)
                @php
                    $itemId = $item['id'] ?? ($categoryKey . '_' . $itemIndex);
                    $itemPrice = floatval(preg_replace('/[^0-9.]/', '', $item['price']));
                    $hasBadges = !empty($item['badges']);
                    $hasDietary = !empty($item['dietary']);
                @endphp
                <div class="px-6 py-5 md:px-8 md:py-6 hover:bg-amber-50/50 transition {{ $orderingEnabled ? 'cursor-pointer group' : '' }}"
                     @if($orderingEnabled)
                     @click="addItem({ id: '{{ $itemId }}', name: '{{ addslashes($item['name']) }}', price: {{ $itemPrice }} })"
                     @endif>
                    <div class="flex items-start gap-4">
                        {{-- Optional thumbnail image (small, polaroid style) --}}
                        @if(!empty($item['image']))
                        <div class="hidden sm:block flex-shrink-0 w-20 h-20 md:w-24 md:h-24 overflow-hidden border-2 border-amber-900/15 p-1 bg-white shadow-sm">
                            <img src="{{ $item['image'] }}"
                                 alt="{{ $item['alt_text'] ?? $item['name'] }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="heritage-menu-row">
                                <h4 class="heritage-serif font-serif text-xl md:text-2xl font-bold text-stone-900 group-hover:text-amber-900 transition">{{ $item['name'] }}</h4>
                                <span class="heritage-menu-dots"></span>
                                <span class="heritage-serif font-serif text-amber-800 font-bold text-xl whitespace-nowrap">{{ $item['price'] }}</span>
                            </div>

                            @if(!empty($item['description']))
                            <p class="text-stone-600 text-sm mt-2 leading-relaxed">{{ $item['description'] }}</p>
                            @endif

                            @if(!empty($item['note']))
                            <p class="text-xs text-stone-500 italic mt-1 heritage-serif font-serif">{{ $item['note'] }}</p>
                            @endif

                            {{-- Badges & Dietary Tags --}}
                            @if($hasBadges || $hasDietary)
                            <div class="flex flex-wrap items-center gap-1.5 mt-3">
                                @if($hasBadges)
                                @foreach($item['badges'] as $badge)
                                @php
                                    $colorClasses = [
                                        'red' => 'bg-red-700 text-white',
                                        'amber' => 'bg-amber-700 text-white',
                                        'green' => 'bg-green-700 text-white',
                                        'purple' => 'bg-purple-700 text-white',
                                        'blue' => 'bg-blue-700 text-white',
                                    ];
                                    $badgeColor = $colorClasses[$badge['color'] ?? 'red'] ?? 'bg-red-700 text-white';
                                    $badgeLabel = $badge['label'] ?? 'Special';
                                @endphp
                                <span class="px-2 py-0.5 text-[10px] uppercase tracking-wider font-bold {{ $badgeColor }}">{{ $badgeLabel }}</span>
                                @endforeach
                                @endif

                                @if($hasDietary)
                                @foreach($item['dietary'] as $diet)
                                @php
                                    $dietaryIcons = [
                                        'vegetarian' => ['icon' => '&#127793;', 'bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Vegetarian'],
                                        'vegan' => ['icon' => '&#127807;', 'bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Vegan'],
                                        'gluten_free' => ['icon' => 'GF', 'bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'label' => 'Gluten Free'],
                                        'dairy_free' => ['icon' => 'DF', 'bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Dairy Free'],
                                        'nut_free' => ['icon' => 'NF', 'bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'label' => 'Nut Free'],
                                        'spicy' => ['icon' => '&#127798;', 'bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Spicy'],
                                        'keto' => ['icon' => 'K', 'bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => 'Keto'],
                                        'low_carb' => ['icon' => 'LC', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'label' => 'Low Carb'],
                                        'halal' => ['icon' => 'H', 'bg' => 'bg-teal-100', 'text' => 'text-teal-800', 'label' => 'Halal'],
                                    ];
                                    $info = $dietaryIcons[$diet] ?? ['icon' => '?', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'label' => ucfirst(str_replace('_', ' ', $diet))];
                                @endphp
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $info['bg'] }} {{ $info['text'] }}" title="{{ $info['label'] }}">
                                    {!! $info['icon'] !!}
                                </span>
                                @endforeach
                                @endif
                            </div>
                            @endif
                        </div>

                        {{-- Add to cart indicator --}}
                        @if($orderingEnabled)
                        <div class="hidden md:flex items-center justify-center w-10 h-10 rounded-full border-2 border-amber-900/20 text-amber-800 group-hover:bg-stone-900 group-hover:text-amber-50 group-hover:border-stone-900 transition flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

<hr class="border-0 h-px bg-gradient-to-r from-transparent via-amber-900/20 to-transparent max-w-6xl mx-auto">

{{-- Scrapbook-style Gallery --}}
@if(!empty($site['gallery']) && count($site['gallery']) > 0)
<section class="py-20 bg-amber-50" id="gallery">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-800 font-semibold mb-3">Memories</p>
            <h2 class="heritage-serif font-serif text-4xl md:text-5xl font-bold text-stone-900 mb-4">A Glimpse Inside</h2>
            <div class="heritage-divider max-w-md mx-auto">
                <span class="heritage-ornament text-lg">&#x2766;</span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 md:gap-8">
            @foreach($site['gallery'] as $index => $photo)
            @php
                // Alternating rotations for scrapbook feel
                $rotations = ['rotate-1', '-rotate-1', 'rotate-2', '-rotate-2', 'rotate-1', '-rotate-1'];
                $rotation = $rotations[$index % count($rotations)];
            @endphp
            <div class="heritage-scrapbook rounded-sm transform {{ $rotation }} hover:rotate-0 hover:scale-105 transition-transform duration-500">
                <div class="overflow-hidden rounded-sm">
                    <img src="{{ is_array($photo) ? ($photo['url'] ?? $photo['src'] ?? '') : $photo }}"
                         alt="{{ is_array($photo) ? ($photo['alt'] ?? 'Gallery photo') : 'Gallery photo' }}"
                         class="w-full h-56 md:h-64 object-cover"
                         loading="lazy">
                </div>
                @if(is_array($photo) && !empty($photo['caption']))
                <p class="heritage-serif font-serif italic text-center text-stone-600 text-sm mt-3">{{ $photo['caption'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@include('samples.partials.reservations-section')

@include('samples.partials.catering-section')

@include('samples.partials.google-reviews-section')

@include('samples.partials.sister-sites-section')

<hr class="border-0 h-px bg-gradient-to-r from-transparent via-amber-900/20 to-transparent max-w-6xl mx-auto">

{{-- CUSTOM warm hours & contact section --}}
<section class="py-20 bg-orange-50/60" id="contact">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-800 font-semibold mb-3">Come See Us</p>
            <h2 class="heritage-serif font-serif text-4xl md:text-5xl font-bold text-stone-900 mb-4">Visit &amp; Hours</h2>
            <div class="heritage-divider max-w-md mx-auto">
                <span class="heritage-ornament text-lg">&#x2766;</span>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
            {{-- Hours Table --}}
            @if(!empty($site['hours']))
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
                <h3 class="heritage-serif font-serif text-2xl font-bold text-stone-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Kitchen Hours
                </h3>
                <div class="bg-amber-50 border-2 border-amber-900/15 shadow-md overflow-hidden">
                    <table class="w-full heritage-serif font-serif">
                        <tbody>
                            @foreach($sortedHours as $day => $time)
                            <tr class="border-b border-amber-900/10 last:border-0 {{ $day === $today ? 'bg-amber-100/60' : '' }}">
                                <td class="px-5 py-3 {{ $day === $today ? 'font-bold text-stone-900' : 'text-stone-700' }}">
                                    {{ $day }}
                                    @if($day === $today)
                                    <span class="ml-2 text-xs uppercase tracking-wider text-amber-800 not-italic">Today</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right italic {{ strtolower($time) === 'closed' ? 'text-red-700' : ($day === $today ? 'text-stone-900 font-semibold' : 'text-stone-600') }}">
                                    {{ $time }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Holiday Hours Notice --}}
                @if(!empty($site['holiday_hours']))
                <div class="mt-4 bg-white border-2 border-amber-700/30 p-4">
                    <h4 class="heritage-serif font-serif font-bold text-stone-900 mb-2">Special Hours</h4>
                    @foreach($site['holiday_hours'] as $holiday)
                    <div class="flex justify-between text-sm py-1 heritage-serif font-serif">
                        <span class="text-stone-700">
                            {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                            @if($holiday['label']) &mdash; {{ $holiday['label'] }} @endif
                        </span>
                        <span class="italic {{ strtolower($holiday['hours']) === 'closed' ? 'text-red-700 font-medium' : 'text-stone-700' }}">
                            {{ $holiday['hours'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- Contact card --}}
            <div>
                <h3 class="heritage-serif font-serif text-2xl font-bold text-stone-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Find Us
                </h3>
                <div class="bg-amber-50 border-2 border-amber-900/15 shadow-md p-6 space-y-5">
                    @if(!empty($site['address']))
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-amber-700 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div class="heritage-serif font-serif">
                            <span class="text-stone-800 text-base">{{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}</span>
                            @if(!empty($site['secondary_cta_url']))
                            <a href="{{ $site['secondary_cta_url'] }}" class="block text-amber-800 text-sm italic hover:underline mt-1" target="_blank">Get Directions &rarr;</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!empty($site['phone']))
                    <div class="flex items-center border-t border-amber-900/10 pt-5">
                        <svg class="w-6 h-6 text-amber-700 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="heritage-serif font-serif text-amber-800 hover:text-amber-900 font-bold text-lg">{{ $site['phone'] }}</a>
                    </div>
                    @endif

                    @if(!empty($site['email']))
                    <div class="flex items-center border-t border-amber-900/10 pt-5">
                        <svg class="w-6 h-6 text-amber-700 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $site['email'] }}" class="heritage-serif font-serif text-amber-800 hover:text-amber-900 italic">{{ $site['email'] }}</a>
                    </div>
                    @endif
                </div>

                {{-- Social Links --}}
                @if(!empty($site['social_links']))
                <div class="mt-6">
                    <p class="heritage-serif font-serif italic text-stone-600 text-sm mb-3 text-center">Stay in touch</p>
                    <div class="flex justify-center gap-3">
                        @foreach($site['social_links'] as $platform => $url)
                        @if($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-white border-2 border-amber-900/20 text-stone-700 hover:bg-stone-900 hover:text-amber-50 hover:border-stone-900 transition shadow-sm"
                           aria-label="{{ ucfirst($platform) }}">
                            @if($platform === 'facebook')
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            @elseif($platform === 'instagram')
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                            @elseif($platform === 'twitter' || $platform === 'x')
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            @elseif($platform === 'tiktok')
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                            @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            @endif
                        </a>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Primary CTA --}}
                <div class="mt-6">
                    @if($orderingEnabled)
                    <a href="#full-menu" class="block w-full text-center bg-stone-900 text-amber-50 py-4 font-semibold hover:bg-stone-800 transition text-lg heritage-serif font-serif border-2 border-stone-900">
                        Order Online Now
                    </a>
                    @else
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="block w-full text-center bg-stone-900 text-amber-50 py-4 font-semibold hover:bg-stone-800 transition text-lg heritage-serif font-serif border-2 border-stone-900">
                        Call to Order: {{ $site['phone'] ?? '' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Closing message --}}
        <div class="text-center mt-16 max-w-2xl mx-auto">
            <div class="heritage-divider mb-6">
                <span class="heritage-ornament text-lg">&#x2766;</span>
            </div>
            <p class="heritage-serif font-serif italic text-stone-600 text-lg leading-relaxed">
                &ldquo;Thank you for being part of our story. We look forward to welcoming you to our table.&rdquo;
            </p>
            <p class="heritage-serif font-serif text-amber-800 text-sm uppercase tracking-[0.3em] mt-4">
                &mdash; The {{ $site['name'] }} Family &mdash;
            </p>
        </div>
    </div>
</section>

@include('samples.partials.cart-ui')

@if($orderingEnabled)
</div>
@endif
@endsection

@include('samples.partials.cart-scripts')
