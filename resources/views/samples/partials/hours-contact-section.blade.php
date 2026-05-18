{{-- Hours & Contact --}}
<section class="py-16 bg-gray-100" id="contact">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12">
            {{-- Hours --}}
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
                <h2 class="text-2xl font-bold mb-6 text-gray-900">Hours</h2>
                <div class="bg-white rounded-lg shadow-md p-6">
                    @foreach($sortedHours as $day => $time)
                    <div class="flex justify-between py-2 border-b last:border-0 {{ $day === $today ? 'font-semibold' : '' }}">
                        <span class="text-gray-700">{{ $day }} {{ $day === $today ? '(Today)' : '' }}</span>
                        <span class="{{ strtolower($time) === 'closed' ? 'text-red-600' : 'text-gray-600' }}">{{ $time }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Holiday Hours Notice --}}
                @if(!empty($site['holiday_hours']))
                <div class="mt-4 bg-amber-50 rounded-lg p-4 border border-amber-200">
                    <h3 class="font-semibold text-amber-900 mb-2">Special Hours</h3>
                    @foreach($site['holiday_hours'] as $holiday)
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-amber-800">
                            {{ \Carbon\Carbon::parse($holiday['date'])->format('M j') }}
                            @if($holiday['label']) - {{ $holiday['label'] }} @endif
                        </span>
                        <span class="{{ strtolower($holiday['hours']) === 'closed' ? 'text-red-600 font-medium' : 'text-amber-800' }}">
                            {{ $holiday['hours'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
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
                        <div>
                            <span class="text-gray-700">{{ is_array($site['address']) ? ($site['address']['full'] ?? '') : $site['address'] }}</span>
                            @if(!empty($site['secondary_cta_url']))
                            <a href="{{ $site['secondary_cta_url'] }}" class="block text-brand text-sm hover:underline mt-1" target="_blank">Get Directions &rarr;</a>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if(!empty($site['phone']))
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-brand mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone']) }}" class="text-brand hover:underline font-medium">{{ $site['phone'] }}</a>
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

                {{-- Call to Action --}}
                <div class="mt-6">
                    @if($orderingEnabled)
                    <a href="#full-menu" class="block w-full text-center text-white py-4 rounded-lg font-semibold hover:opacity-90 transition text-lg" style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
                        Order Online Now
                    </a>
                    @else
                    <a href="tel:{{ preg_replace('/[^0-9]/', '', $site['phone'] ?? '') }}" class="block w-full text-center text-white py-4 rounded-lg font-semibold hover:opacity-90 transition text-lg" style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
                        Call to Order: {{ $site['phone'] ?? '' }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>