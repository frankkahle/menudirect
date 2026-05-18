@props([
    'url',
    'aspect' => 1,
    'postUrl',
    'fieldName' => 'image',
    'method' => 'POST',
    'class' => '',
    'label' => 'Edit crop',
])

@php
    $config = json_encode([
        'url' => $url,
        'aspect' => (float) $aspect,
        'postUrl' => $postUrl,
        'fieldName' => $fieldName,
        'method' => strtoupper($method),
    ]);
@endphp

<button type="button"
        onclick='window.recropImageFromUrl({!! $config !!})'
        class="{{ $class ?: 'inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50' }}"
        title="{{ $label }}">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
    </svg>
    <span>{{ $label }}</span>
</button>
