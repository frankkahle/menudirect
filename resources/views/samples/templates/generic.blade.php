@extends('samples.layout')

@section('content')
{{-- Hero Section --}}
<section class="brand-gradient text-white py-20">
    <div class="max-w-6xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $site['name'] }}</h1>
        @if(!empty($site['tagline']))
        <p class="text-xl mb-8 opacity-90 max-w-2xl mx-auto">{{ $site['tagline'] }}</p>
        @endif
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ $site['cta_url'] ?? '#contact' }}" class="bg-white text-brand px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                {{ $site['cta_text'] ?? 'Contact Us' }}
            </a>
            @if(!empty($site['phone']))
            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-brand transition">
                {{ $site['phone'] }}
            </a>
            @endif
        </div>
    </div>
</section>

{{-- Features/Highlights --}}
@if(!empty($site['features']))
<section class="py-6 bg-gray-900 text-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center gap-6 text-sm">
            @foreach($site['features'] as $feature)
            <span class="flex items-center">
                <svg class="w-5 h-5 text-brand-accent mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ $feature }}
            </span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- About Section --}}
@if(!empty($site['about']))
<section class="py-16">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900">About Us</h2>
        <p class="text-gray-600 text-lg leading-relaxed text-center">{{ $site['about'] }}</p>
    </div>
</section>
@endif

{{-- Services --}}
@if(!empty($site['services']))
<section class="py-16 {{ !empty($site['about']) ? 'bg-gray-50' : '' }}">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Our Services</h2>
        <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">What we offer.</p>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($site['services'] as $service)
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background: {{ $site['colors']['primary'] }}15;">
                    <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $service['name'] }}</h3>
                <p class="text-gray-600">{{ $service['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Products (if defined) --}}
@if(!empty($site['products']))
<section class="py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12 text-gray-900">Our Products</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($site['products'] as $product)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="h-40 flex items-center justify-center bg-gray-100">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900">{{ $product['name'] }}</h3>
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

{{-- Hours (if defined) --}}
@if(!empty($site['hours']))
<section class="py-16">
    <div class="max-w-2xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900">Hours of Operation</h2>
        <div class="bg-white rounded-lg shadow-md p-6">
            @foreach($site['hours'] as $day => $time)
            <div class="flex justify-between py-2 border-b last:border-0">
                <span class="font-medium text-gray-700">{{ $day }}</span>
                <span class="text-gray-600">{{ $time }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact Section --}}
<section class="py-16 bg-gray-100" id="contact">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="brand-gradient text-white p-8 text-center">
                <h2 class="text-2xl font-bold mb-2">Get In Touch</h2>
                <p class="opacity-90">We'd love to hear from you.</p>
            </div>
            <div class="p-8">
                <div class="grid md:grid-cols-3 gap-6 text-center">
                    @if(!empty($site['phone']))
                    <div>
                        <svg class="w-8 h-8 text-brand mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <p class="font-semibold text-gray-900">Phone</p>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-brand hover:underline">{{ $site['phone'] }}</a>
                    </div>
                    @endif
                    @if(!empty($site['email']))
                    <div>
                        <svg class="w-8 h-8 text-brand mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <p class="font-semibold text-gray-900">Email</p>
                        <a href="mailto:{{ $site['email'] }}" class="text-brand hover:underline">{{ $site['email'] }}</a>
                    </div>
                    @endif
                    @if(!empty($site['address']))
                    <div>
                        <svg class="w-8 h-8 text-brand mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                        <p class="font-semibold text-gray-900">Location</p>
                        <p class="text-gray-600 text-sm">{{ $site['address'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
