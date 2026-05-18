@extends('samples.layout')

@section('content')
{{-- Hero Section --}}
<section class="relative text-white py-16 md:py-20 overflow-hidden">
    @if(!empty($site['hero_image']))
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $site['hero_image'] }}');"></div>
    <div class="absolute inset-0 bg-black bg-opacity-60"></div>
    @else
    <div class="absolute inset-0 brand-gradient"></div>
    @endif

    <div class="relative max-w-6xl mx-auto px-4 text-center z-10">
        @if(!empty($site['logo']))
        <img src="{{ $site['logo'] }}" alt="{{ $site['name'] }}" class="h-20 mx-auto mb-4 rounded-lg shadow-xl bg-white/10 backdrop-blur p-2">
        @endif
        <h1 class="text-3xl md:text-5xl font-bold mb-3 drop-shadow-lg">{{ $site['name'] }}</h1>
        <p class="text-lg md:text-xl opacity-90">Reputation Management Integration</p>
    </div>
</section>

{{-- Reviews Section --}}
<section class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 bg-amber-100 text-amber-800 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span>Live Google Reviews</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">What Our Customers Say</h2>
            <p class="text-xl text-gray-600">Real reviews from real customers</p>
        </div>

        {{-- Reviews Grid --}}
        <div class="grid md:grid-cols-3 gap-8 mb-12">
            {{-- Review 1 --}}
            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
                {{-- Stars --}}
                <div class="flex gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>

                {{-- Review Text --}}
                <p class="text-gray-700 mb-6 leading-relaxed">
                    "The new online ordering system is so much faster than the old app we used to use. I had my food in 15 minutes. Best burger in the city!"
                </p>

                {{-- Reviewer --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                        A
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">Alex T.</span>
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-500">Local Guide</span>
                    </div>
                </div>
            </div>

            {{-- Review 2 --}}
            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
                {{-- Stars --}}
                <div class="flex gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>

                {{-- Review Text --}}
                <p class="text-gray-700 mb-6 leading-relaxed">
                    "I love that I can see the full menu with photos before I even arrive. The interface is clean and it actually works on my phone!"
                </p>

                {{-- Reviewer --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                        S
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">Sarah M.</span>
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-500">Verified Customer</span>
                    </div>
                </div>
            </div>

            {{-- Review 3 --}}
            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
                {{-- Stars --}}
                <div class="flex gap-1 mb-4">
                    @for($i = 0; $i < 5; $i++)
                    <svg class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>

                {{-- Review Text --}}
                <p class="text-gray-700 mb-6 leading-relaxed">
                    "Finally, a local spot that doesn't use those high-fee delivery apps. Ordering direct was a breeze and the food was hot. 5 stars!"
                </p>

                {{-- Reviewer --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
                        D
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900">David L.</span>
                            <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <span class="text-sm text-gray-500">Verified Customer</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Developer Note --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 md:p-8 border border-blue-200">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-lg mb-2">Developer Note: Live Google Sync</h3>
                    <p class="text-gray-700 leading-relaxed">
                        <strong>This demo page simulates our Live Google Sync.</strong> Once your site is live, this section automatically pulls your real-time 5-star reviews directly from Google Maps, boosting your local SEO and building instant trust with new customers.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-12 bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Ready to Showcase Your Reviews?</h2>
        <p class="text-gray-600 mb-8">Get a professional website with automated Google review integration.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="https://menudirect.ca#demo-form" class="inline-flex items-center justify-center bg-brand text-white px-8 py-4 rounded-full font-bold text-lg hover:opacity-90 transition shadow-lg">
                Apply This to My Restaurant
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
            <a href="/" class="inline-flex items-center justify-center border-2 border-gray-300 text-gray-700 px-8 py-4 rounded-full font-semibold text-lg hover:bg-gray-200 transition">
                Back to Menu
            </a>
        </div>
    </div>
</section>
@endsection
