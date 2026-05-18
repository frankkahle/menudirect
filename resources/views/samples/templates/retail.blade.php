@extends('samples.layout')

@section('content')
{{-- Hero Section --}}
<section class="brand-gradient text-white py-20">
    <div class="max-w-6xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $site['name'] }}</h1>
        <p class="text-xl mb-8 opacity-90 max-w-2xl mx-auto">{{ $site['tagline'] ?? '' }}</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ $site['cta_url'] ?? '#contact' }}" class="bg-brand-accent text-gray-900 px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition">
                {{ $site['cta_text'] ?? 'Shop Now' }}
            </a>
            @if(!empty($site['phone']))
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-brand transition">
                Call {{ $site['phone'] }}
            </a>
            @endif
        </div>
    </div>
</section>

{{-- Features Bar --}}
@if(!empty($site['features']))
<section class="bg-white py-6 border-b">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center gap-8 text-sm text-gray-600">
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

{{-- Product Categories --}}
@if(!empty($site['categories']))
<section class="py-16">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Shop by Category</h2>
        <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">Browse our selection of quality products.</p>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($site['categories'] as $category)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition group">
                <div class="h-40 flex items-center justify-center" style="background: {{ $site['colors']['primary'] }}10;">
                    <svg class="w-16 h-16 text-brand opacity-50 group-hover:opacity-70 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $category['name'] }}</h3>
                    <p class="text-gray-600 text-sm">{{ $category['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Featured Products --}}
@if(!empty($site['products']))
<section class="py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Featured Products</h2>
        <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">Discover our most popular items.</p>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($site['products'] as $product)
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="h-48 flex items-center justify-center bg-gray-100">
                    <svg class="w-20 h-20 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-1">{{ $product['name'] }}</h3>
                    @if(!empty($product['price']))
                    <p class="text-brand font-bold">{{ $product['price'] }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About / Why Shop With Us --}}
@if(!empty($site['about']))
<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6 text-gray-900">About Us</h2>
        <p class="text-gray-600 text-lg leading-relaxed">{{ $site['about'] }}</p>
    </div>
</section>
@endif

{{-- Store Info / Contact --}}
<section class="py-16 bg-gray-100" id="contact">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12">
            {{-- Hours --}}
            @if(!empty($site['hours']))
            <div>
                <h2 class="text-2xl font-bold mb-6 text-gray-900">Store Hours</h2>
                <div class="bg-white rounded-lg shadow-md p-6">
                    @foreach($site['hours'] as $day => $time)
                    <div class="flex justify-between py-2 border-b last:border-0">
                        <span class="font-medium text-gray-700">{{ $day }}</span>
                        <span class="text-gray-600">{{ $time }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Contact --}}
            <div>
                <h2 class="text-2xl font-bold mb-6 text-gray-900">Visit Us</h2>
                <div class="bg-white rounded-lg shadow-md p-6 space-y-4">
                    @if(!empty($site['address']))
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-brand mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="text-gray-700">{{ $site['address'] }}</span>
                    </div>
                    @endif
                    @if(!empty($site['phone']))
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-brand mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-brand hover:underline">{{ $site['phone'] }}</a>
                    </div>
                    @endif
                    @if(!empty($site['email']))
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-brand mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <a href="mailto:{{ $site['email'] }}" class="text-brand hover:underline">{{ $site['email'] }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
