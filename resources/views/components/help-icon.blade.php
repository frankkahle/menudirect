@props([
    'article' => null,        // slug of the related help article
    'tooltip' => null,        // short text shown on hover (preview)
    'position' => 'top',      // top, bottom, left, right
    'size' => 'sm',           // sm, md
])

@php
    $sizeClass = $size === 'md' ? 'w-5 h-5' : 'w-4 h-4';
@endphp

<button type="button"
        class="inline-flex items-center justify-center text-gray-400 hover:text-indigo-600 align-middle ml-1 transition"
        @if($article) data-help-article="{{ $article }}" @endif
        @if($tooltip) title="{{ $tooltip }}" @endif
        onclick="event.preventDefault(); event.stopPropagation(); window.openHelpDrawer && window.openHelpDrawer({{ $article ? json_encode($article) : 'null' }});"
        aria-label="Help">
    <svg class="{{ $sizeClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
</button>
