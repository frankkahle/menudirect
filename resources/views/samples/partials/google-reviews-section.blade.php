{{-- Google Reviews Section --}}
@if(!empty($site['google_reviews']) && count($site['google_reviews']) > 0)
<section class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-10">
            <div class="flex items-center justify-center gap-2 mb-3">
                <img src="https://www.google.com/favicon.ico" alt="Google" class="w-6 h-6">
                <h2 class="text-2xl font-bold text-gray-900">What Our Customers Say</h2>
            </div>
            @if(!empty($site['google_rating']))
            <div class="flex items-center justify-center gap-2">
                <div class="flex">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-5 h-5 {{ $i <= round($site['google_rating']) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <span class="text-gray-600">{{ $site['google_rating'] }} out of 5 based on {{ $site['google_review_count'] ?? '50+' }} reviews</span>
            </div>
            @endif
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach(array_slice($site['google_reviews'], 0, 3) as $review)
            <div class="bg-gray-50 rounded-lg p-6 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-brand text-white rounded-full flex items-center justify-center font-bold">
                        {{ strtoupper(substr($review['author'] ?? 'A', 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $review['author'] ?? 'Customer' }}</p>
                        <div class="flex">
                            @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= ($review['rating'] ?? 5) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            @endfor
                        </div>
                    </div>
                </div>
                <p class="text-gray-600 text-sm leading-relaxed">"{{ Str::limit($review['text'] ?? '', 150) }}"</p>
                @if(!empty($review['date']))
                <p class="text-xs text-gray-400 mt-3">{{ $review['date'] }}</p>
                @endif
            </div>
            @endforeach
        </div>

        @if(!empty($site['google_reviews_url']))
        <div class="text-center mt-8">
            <a href="{{ $site['google_reviews_url'] }}" target="_blank" class="inline-flex items-center gap-2 text-brand hover:underline font-medium">
                <img src="https://www.google.com/favicon.ico" alt="Google" class="w-4 h-4">
                See all reviews on Google
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
        @endif
    </div>
</section>
@endif