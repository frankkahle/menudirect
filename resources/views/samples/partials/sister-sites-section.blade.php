@if(!empty($site['sister_sites']) && count($site['sister_sites']) > 0)
<section class="py-16 bg-gray-50" id="sister-sites">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-10">
            <p class="text-sm font-semibold uppercase tracking-widest text-brand mb-2">{{ $site['settings']['sister_sites_eyebrow'] ?? 'Also Visit Us At' }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">{{ $site['settings']['sister_sites_heading'] ?? 'Our Other Locations' }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ min(count($site['sister_sites']), 3) }} gap-6">
            @foreach($site['sister_sites'] as $sister)
            <a href="{{ $sister['url'] }}" target="_blank" rel="noopener"
               class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:-translate-y-1">

                {{-- Cover image with color bar at top --}}
                <div class="h-3" style="background: linear-gradient(90deg, {{ $sister['colors']['primary'] ?? '#2563eb' }}, {{ $sister['colors']['secondary'] ?? '#7c3aed' }});"></div>

                @if(!empty($sister['cover']))
                <div class="h-40 overflow-hidden bg-gray-100">
                    <img src="{{ $sister['cover'] }}" alt="{{ $sister['name'] }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         loading="lazy">
                </div>
                @endif

                <div class="p-6 text-center">
                    @if(!empty($sister['logo']))
                    <div class="flex justify-center mb-4 {{ empty($sister['cover']) ? 'mt-2' : '-mt-16 relative' }}">
                        <div class="bg-white rounded-full p-3 shadow-lg border-4 border-white">
                            <img src="{{ $sister['logo'] }}" alt="{{ $sister['name'] }} logo"
                                 class="h-16 w-16 object-contain">
                        </div>
                    </div>
                    @endif

                    <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-brand transition-colors">
                        {{ $sister['name'] }}
                    </h3>

                    @if(!empty($sister['tagline']))
                    <p class="text-sm text-gray-600 mb-3">{{ $sister['tagline'] }}</p>
                    @endif

                    @if(!empty($sister['cuisine']))
                    <span class="inline-block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">
                        {{ $sister['cuisine'] }}
                    </span>
                    @endif

                    <div class="pt-3 border-t border-gray-100">
                        <span class="inline-flex items-center text-sm font-medium text-brand group-hover:gap-2 gap-1 transition-all">
                            Visit Site
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
