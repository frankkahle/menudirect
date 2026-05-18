@props(['announcements' => []])

@if(!empty($announcements))
    @foreach($announcements as $announcement)
        @if(!empty($announcement['image']))
            {{-- Image-bearing announcement — full image, no crop, letterboxed against page background --}}
            <section class="relative w-full flex flex-col items-center justify-center py-4 md:py-6" style="background: transparent;">
                <img src="{{ $announcement['image'] }}" alt="{{ $announcement['title'] ?? $announcement['message'] ?? 'Promotion' }}" class="block w-auto max-w-full" style="max-height: 60vh; object-fit: contain;">
                @if(!empty($announcement['title']) || !empty($announcement['message']) || !empty($announcement['link_url']))
                    <div class="w-full max-w-6xl mx-auto px-4 mt-4 text-center">
                        @if(!empty($announcement['title']))
                            <h3 class="text-xl md:text-2xl font-semibold">{{ $announcement['title'] }}</h3>
                        @endif
                        @if(!empty($announcement['message']))
                            <p class="mt-1 text-sm md:text-base opacity-80 max-w-2xl mx-auto">{{ $announcement['message'] }}</p>
                        @endif
                        @if(!empty($announcement['link_url']))
                            <a href="{{ $announcement['link_url'] }}" target="_blank" rel="noopener" class="inline-block mt-3 px-4 py-2 bg-white/10 text-current text-sm font-semibold rounded-md hover:bg-white/20 transition border border-current/20">Learn More</a>
                        @endif
                    </div>
                @endif
            </section>
        @else
            {{-- Text-only announcement — thin strip, uses site primary color for accent --}}
            @php
                $stripBg = $site['colors']['secondary'] ?? '#1a1a1a';
                $stripAccent = $site['colors']['accent'] ?? ($site['colors']['primary'] ?? '#b8860b');
            @endphp
            <div class="w-full py-2 text-center text-xs md:text-sm tracking-widest uppercase border-b" style="background-color: {{ $stripBg }}; color: #f5f0e1; border-color: {{ $stripAccent }};">
                <span style="color: {{ $stripAccent }};">&#9830;</span>
                @if(!empty($announcement['title']))
                    <span class="mx-3 font-semibold">{{ $announcement['title'] }}</span>
                @endif
                @if(!empty($announcement['message']))
                    <span class="mx-3">{{ $announcement['message'] }}</span>
                @endif
                @if(!empty($announcement['link_url']))
                    <a href="{{ $announcement['link_url'] }}" target="_blank" rel="noopener" class="mx-3 underline" style="color: {{ $stripAccent }};">Details</a>
                @endif
                <span style="color: {{ $stripAccent }};">&#9830;</span>
            </div>
        @endif
    @endforeach
@endif
