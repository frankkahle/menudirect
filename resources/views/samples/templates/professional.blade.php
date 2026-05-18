@extends('samples.layout')

@section('content')
{{-- Hero Section --}}
<section class="brand-gradient text-white py-20">
    <div class="max-w-6xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $site['name'] }}</h1>
        <p class="text-xl mb-8 opacity-90 max-w-2xl mx-auto">{{ $site['tagline'] ?? '' }}</p>
        <a href="{{ $site['cta_url'] ?? '#contact' }}" class="inline-block bg-white text-brand px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
            {{ $site['cta_text'] ?? 'Schedule Consultation' }}
        </a>
    </div>
</section>

{{-- Credentials Bar --}}
@if(!empty($site['credentials']))
<section class="bg-gray-900 text-white py-4">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-wrap justify-center gap-6 text-sm">
            @foreach($site['credentials'] as $cred)
            <span class="flex items-center">
                <svg class="w-5 h-5 text-brand-accent mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ $cred }}
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
        <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">Comprehensive solutions tailored to your needs.</p>
        <div class="grid md:grid-cols-2 gap-8">
            @foreach($site['services'] as $service)
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100 flex">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 mr-4" style="background: {{ $site['colors']['primary'] }}15;">
                    <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $service['name'] }}</h3>
                    <p class="text-gray-600">{{ $service['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Why Choose Us --}}
@if(!empty($site['features']))
<section class="py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12 text-gray-900">Why Choose Us</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($site['features'] as $feature)
            <div class="text-center">
                <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center" style="background: {{ $site['colors']['primary'] }}15;">
                    <svg class="w-8 h-8 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p class="font-semibold text-gray-900">{{ $feature }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Contact Section --}}
<section class="py-16" id="contact">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="brand-gradient text-white p-8 text-center">
                <h2 class="text-2xl font-bold mb-2">Schedule Your Free Consultation</h2>
                <p class="opacity-90">Let's discuss how we can help your business succeed.</p>
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
