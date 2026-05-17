@extends('layouts.app')

@section('title', 'Locations - ' . $site->business_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.show', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $site->business_name }}
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Manage Locations</h1>
        <p class="text-gray-600 mt-1">Link multiple restaurant locations to share the same menu across all sites.</p>
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

    @if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                @foreach($errors->all() as $error)
                <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($parentSite)
    <!-- This site is a child location -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-medium text-yellow-800">This is a child location</h3>
                <p class="mt-1 text-sm text-yellow-700">
                    This site inherits its menu from <strong>{{ $parentSite->business_name }}</strong>.
                    To manage linked locations, go to the parent site.
                </p>
                <a href="{{ route('client.restaurant.locations', $parentSite) }}" class="mt-2 inline-block text-sm font-medium text-yellow-800 hover:text-yellow-900">
                    Go to parent site &rarr;
                </a>
            </div>
        </div>
    </div>
    @else
    <!-- This site can be a parent -->

    <!-- How It Works -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <h3 class="text-sm font-medium text-blue-900 mb-2">How Multi-Location Works</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li>&#8226; Link other restaurant sites to this one to share the same menu</li>
            <li>&#8226; Child locations inherit menu categories and items from the parent</li>
            <li>&#8226; Each location keeps its own hours, address, phone, and ordering settings</li>
            <li>&#8226; Visitors can switch between locations on your live site</li>
        </ul>
    </div>

    <!-- Current Locations -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Linked Locations</h2>

        @if($childSites->count() > 0)
        <div class="space-y-4">
            <!-- Parent (this site) -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border-2 {{ $site->is_primary_location ? 'border-indigo-500' : 'border-transparent' }}">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span class="font-medium text-gray-900">{{ $site->business_name }}</span>
                        <span class="text-xs px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded-full">Parent</span>
                        @if($site->is_primary_location)
                        <span class="text-xs px-2 py-0.5 bg-green-100 text-green-800 rounded-full">Primary</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $site->address }}</p>
                </div>
                @if(!$site->is_primary_location)
                <form action="{{ route('client.restaurant.locations.primary', [$site, $site]) }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800">Set as Primary</button>
                </form>
                @endif
            </div>

            <!-- Child locations -->
            @foreach($childSites as $childSite)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border-2 {{ $childSite->is_primary_location ? 'border-indigo-500' : 'border-transparent' }}">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span class="font-medium text-gray-900">{{ $childSite->business_name }}</span>
                        <span class="text-xs px-2 py-0.5 bg-gray-200 text-gray-700 rounded-full">Child</span>
                        @if($childSite->is_primary_location)
                        <span class="text-xs px-2 py-0.5 bg-green-100 text-green-800 rounded-full">Primary</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $childSite->address }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    @if(!$childSite->is_primary_location)
                    <form action="{{ route('client.restaurant.locations.primary', [$site, $childSite]) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800">Set as Primary</button>
                    </form>
                    @endif
                    <form action="{{ route('client.restaurant.locations.unlink', [$site, $childSite]) }}" method="POST"
                          onsubmit="return confirm('Unlink {{ $childSite->business_name }}? It will become an independent site with its own menu.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Unlink</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-500 text-sm">No locations linked yet. Link other restaurant sites below to share your menu with them.</p>
        @endif
    </div>

    <!-- Add Location -->
    @if($availableSites->count() > 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Link a Location</h2>
        <p class="text-sm text-gray-500 mb-4">Select a restaurant site to link as a child location. It will inherit the menu from this site.</p>

        <form action="{{ route('client.restaurant.locations.link', $site) }}" method="POST" class="flex items-end space-x-4">
            @csrf
            <div class="flex-1">
                <label for="child_site_id" class="block text-sm font-medium text-gray-700 mb-1">Select Site</label>
                <select name="child_site_id" id="child_site_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($availableSites as $availableSite)
                    <option value="{{ $availableSite->id }}">{{ $availableSite->business_name }} ({{ $availableSite->slug }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                Link Location
            </button>
        </form>
    </div>
    @elseif($childSites->count() === 0)
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Link a Location</h2>
        <p class="text-gray-500 text-sm">
            No other restaurant sites available to link.
            <a href="{{ route('client.restaurant.create') }}" class="text-indigo-600 hover:text-indigo-800">Create a new site</a>
            to add another location.
        </p>
    </div>
    @endif
    @endif
</div>
@endsection
