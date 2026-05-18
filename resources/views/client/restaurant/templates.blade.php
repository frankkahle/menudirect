@extends('layouts.client')

@section('title', 'Choose Template — ' . $site->business_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('client.restaurant.edit', $site) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">&larr; Back to site settings</a>
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Site Template</h1>
            <p class="text-gray-500 mt-1">Choose a design for {{ $site->business_name }}. Changes take effect immediately.</p>
        </div>
        <a href="{{ $site->getPublicUrl() }}" target="_blank" class="text-sm text-indigo-600 hover:underline">View live site &rarr;</a>
    </div>

    @if(session('status'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
        {{ session('status') }}
    </div>
    @endif

    @php
        $categories = ['Cuisine' => [], 'Vibe' => [], 'General' => []];
        foreach ($templates as $slug => $tpl) {
            $categories[$tpl['category'] ?? 'General'][] = array_merge($tpl, ['slug' => $slug]);
        }
    @endphp

    @foreach(['Cuisine' => 'Cuisine-Specific', 'Vibe' => 'Vibe & Atmosphere', 'General' => 'General Purpose'] as $catKey => $catLabel)
    @if(!empty($categories[$catKey]))
    <div class="mb-10">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">{{ $catLabel }}</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($categories[$catKey] as $tpl)
            <div class="group relative bg-white rounded-xl border-2 transition overflow-hidden
                {{ $currentTemplate === $tpl['slug'] ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-gray-200 hover:border-gray-300' }}">

                {{-- Preview Image --}}
                <div class="relative aspect-[16/10] bg-gray-100 overflow-hidden">
                    @if(file_exists(public_path("images/template-previews/{$tpl['slug']}-hero.jpg")))
                    <img src="/images/template-previews/{{ $tpl['slug'] }}-hero.jpg"
                         alt="{{ $tpl['name'] }} template preview"
                         class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300"
                         loading="lazy">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    @endif

                    {{-- Current badge --}}
                    @if($currentTemplate === $tpl['slug'])
                    <div class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                        Current
                    </div>
                    @endif

                    {{-- Preview link overlay --}}
                    <a href="https://menudirect.ca/template-preview/{{ $tpl['slug'] }}" target="_blank"
                       class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <span class="bg-white text-gray-900 px-3 py-1.5 rounded-lg text-sm font-medium shadow">Preview</span>
                    </a>
                </div>

                {{-- Info --}}
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900">{{ $tpl['name'] }}</h3>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $tpl['description'] }}</p>

                    @if($currentTemplate !== $tpl['slug'])
                    <form action="{{ route('client.restaurant.templates.update', $site) }}" method="POST" class="mt-3">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="template" value="{{ $tpl['slug'] }}">
                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Use This Template
                        </button>
                    </form>
                    @else
                    <div class="mt-3 py-2 text-center text-sm text-indigo-600 font-medium">
                        Active
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach
</div>
@endsection
