{{-- Photo Gallery --}}
@if(!empty($site['gallery']) && count($site['gallery']) > 0)
<section class="py-16 bg-white" id="gallery">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-10 text-gray-900">Gallery</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($site['gallery'] as $photo)
            @php
                $photoUrl = is_array($photo) ? ($photo['url'] ?? $photo['src'] ?? '') : $photo;
                $photoCaption = is_array($photo) ? ($photo['caption'] ?? null) : null;
            @endphp
            <figure class="overflow-hidden rounded-lg shadow-lg bg-white">
                <img src="{{ $photoUrl }}" alt="{{ $photoCaption ?: 'Gallery photo' }}" class="w-full h-64 object-cover hover:scale-105 transition-transform duration-300" loading="lazy">
                @if($photoCaption)
                <figcaption class="px-4 py-3 text-sm text-gray-700 text-center">{{ $photoCaption }}</figcaption>
                @endif
            </figure>
            @endforeach
        </div>
    </div>
</section>
@endif
