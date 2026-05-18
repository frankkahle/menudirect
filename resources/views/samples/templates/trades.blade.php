@extends('samples.layout')

@section('content')
{{-- Hero Section --}}
<section class="brand-gradient text-white py-20">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $site['name'] }}</h1>
                <p class="text-xl mb-6 opacity-90">{{ $site['tagline'] ?? '' }}</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ $site['cta_url'] ?? '#contact' }}" class="bg-brand-accent text-gray-900 px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition text-center">
                        {{ $site['cta_text'] ?? 'Get a Quote' }}
                    </a>
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-brand transition text-center">
                        {{ $site['phone'] ?? 'Call Now' }}
                    </a>
                </div>
            </div>
            <div class="hidden md:block">
                <div class="bg-white/10 backdrop-blur rounded-lg p-8 text-center">
                    <svg class="w-24 h-24 mx-auto mb-4 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                    <p class="text-2xl font-bold">24/7 Emergency Service</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Trust Badges --}}
@if(!empty($site['features']))
<section class="bg-gray-900 text-white py-6">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center gap-8 text-sm">
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

{{-- Services --}}
@if(!empty($site['services']))
<section class="py-16">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Our Services</h2>
        <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">Professional service you can trust. We handle jobs of all sizes.</p>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($site['services'] as $service)
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100 hover:shadow-lg transition">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background: {{ $site['colors']['primary'] }}20;">
                    <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if(($service['icon'] ?? '') === 'wrench')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        @elseif(($service['icon'] ?? '') === 'droplet')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"></path>
                        @elseif(($service['icon'] ?? '') === 'flame')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                        @elseif(($service['icon'] ?? '') === 'home')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        @endif
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $service['name'] }}</h3>
                <p class="text-gray-600 text-sm">{{ $service['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- CTA Section --}}
<section class="py-16 bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-4 text-gray-900">Ready to Get Started?</h2>
        <p class="text-gray-600 mb-8">Contact us today for a free estimate. No job is too big or too small.</p>
        <div class="bg-white rounded-lg shadow-lg p-8" id="contact">
            <div class="grid md:grid-cols-2 gap-6 text-left">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-4">Contact Information</h3>
                    <div class="space-y-3">
                        @if(!empty($site['phone']))
                        <p class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-brand mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-brand font-semibold hover:underline">{{ $site['phone'] }}</a>
                        </p>
                        @endif
                        @if(!empty($site['email']))
                        <p class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-brand mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ $site['email'] }}
                        </p>
                        @endif
                        @if(!empty($site['address']))
                        <p class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-brand mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            {{ $site['address'] }}
                        </p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-center">
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="w-full brand-gradient text-white px-8 py-4 rounded-lg font-semibold text-center hover:opacity-90 transition text-lg">
                        Call Now for Free Estimate
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
