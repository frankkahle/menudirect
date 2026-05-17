@extends('layouts.app')

@section('title', 'Restaurant Sites')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Restaurant Sites</h1>
            <p class="text-gray-600 mt-1">Manage your restaurant websites</p>
        </div>
        <a href="{{ route('client.restaurant.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Create New Site
        </a>
    </div>

    @if(session('status'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">{{ session('status') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($sites->count() > 0)
    <!-- Sites Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($sites as $site)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Cover Photo or Placeholder -->
            <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600 relative">
                @if($site->cover_photo_url)
                <img src="{{ $site->cover_photo_url }}" alt="{{ $site->business_name }}" class="w-full h-full object-cover">
                @endif
                <!-- Status Badge -->
                <div class="absolute top-3 right-3">
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $site->status_badge_class }}">
                        {{ $site->status_label }}
                    </span>
                </div>
                <!-- Logo -->
                @if($site->logo_url)
                <div class="absolute -bottom-8 left-4">
                    <img src="{{ $site->logo_url }}" alt="{{ $site->business_name }}" class="w-16 h-16 rounded-lg border-4 border-white shadow object-cover bg-white">
                </div>
                @endif
            </div>

            <!-- Content -->
            <div class="p-4 {{ $site->logo_url ? 'pt-10' : 'pt-4' }}">
                <h3 class="font-semibold text-lg text-gray-900">{{ $site->business_name }}</h3>
                @if($site->tagline)
                <p class="text-sm text-gray-500 mt-1">{{ Str::limit($site->tagline, 50) }}</p>
                @endif

                <div class="mt-3 flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <a href="{{ $site->getPublicUrl() }}" target="_blank" class="hover:text-indigo-600">
                        sos-tech.ca/s/{{ $site->slug }}
                    </a>
                </div>

                <!-- Actions -->
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <a href="{{ route('client.restaurant.show', $site) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        Manage
                    </a>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('client.restaurant.menu', $site) }}" class="text-sm text-gray-500 hover:text-gray-700">
                            Menu
                        </a>
                        <a href="{{ $site->getPublicUrl() }}" target="_blank" class="text-sm text-gray-500 hover:text-gray-700">
                            View
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No restaurant sites yet</h3>
        <p class="mt-2 text-gray-500">Get started by creating your first restaurant website.</p>
        <div class="mt-6">
            <a href="{{ route('client.restaurant.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Your First Site
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
