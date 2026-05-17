@extends('layouts.app')

@section('title', 'Catering Packages - ' . $site->business_name)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.catering.index', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Catering Inquiries
        </a>

        <h1 class="text-3xl font-bold text-gray-900">Catering Packages</h1>
        <p class="text-gray-600 mt-1">Create and manage your catering offerings</p>
    </div>

    @if(session('status'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
        <p class="text-sm text-green-700">{{ session('status') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <p class="text-sm text-red-700">{{ session('error') }}</p>
    </div>
    @endif

    <!-- Add New Package -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add New Package</h2>
        <form action="{{ route('client.restaurant.catering.packages.store', $site) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Package Name *</label>
                    <input type="text" name="name" required class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., Corporate Lunch Package">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                        <input type="number" name="price" step="0.01" min="0" required class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="25.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price Type</label>
                        <select name="price_type" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="per_person">Per Person</option>
                            <option value="flat">Flat Rate</option>
                        </select>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Describe what's included..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Guests</label>
                    <input type="number" name="min_guests" min="1" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Guests</label>
                    <input type="number" name="max_guests" min="1" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lead Time (hours)</label>
                    <input type="number" name="lead_time_hours" min="1" class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" placeholder="48">
                </div>
            </div>

            <!-- Included items -->
            <div class="mt-4" x-data="{ items: [''] }">
                <label class="block text-sm font-medium text-gray-700 mb-1">What's Included</label>
                <template x-for="(item, index) in items" :key="index">
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text" :name="'includes[' + index + ']'" x-model="items[index]"
                               class="flex-1 rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                               placeholder="e.g., Choice of 3 entrees">
                        <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1"
                                class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
                <button type="button" @click="items.push('')" class="text-sm text-indigo-600 hover:text-indigo-800">
                    + Add item
                </button>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium">
                    Add Package
                </button>
            </div>
        </form>
    </div>

    <!-- Existing Packages -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Packages ({{ $packages->count() }})</h2>

    @if($packages->isEmpty())
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-500">No catering packages yet. Create your first one above.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($packages as $package)
        <div class="bg-white rounded-lg shadow p-6 {{ !$package->active ? 'opacity-60' : '' }}">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $package->name }}</h3>
                        <span class="text-lg font-bold text-indigo-600">{{ $package->formatted_price }}</span>
                        @if(!$package->active)
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Inactive</span>
                        @endif
                    </div>
                    @if($package->description)
                    <p class="text-gray-600 mt-1">{{ $package->description }}</p>
                    @endif
                    <div class="flex gap-4 mt-2 text-sm text-gray-500">
                        @if($package->min_guests)
                        <span>Min: {{ $package->min_guests }} guests</span>
                        @endif
                        @if($package->max_guests)
                        <span>Max: {{ $package->max_guests }} guests</span>
                        @endif
                        @if($package->lead_time_hours)
                        <span>Lead time: {{ $package->lead_time_hours }}h</span>
                        @endif
                    </div>
                    @if($package->includes && count($package->includes))
                    <div class="mt-2">
                        <ul class="text-sm text-gray-600 list-disc list-inside">
                            @foreach($package->includes as $item)
                            @if($item)
                            <li>{{ $item }}</li>
                            @endif
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 ml-4">
                    <!-- Image upload -->
                    <form action="{{ route('client.restaurant.catering.packages.image', [$site, $package]) }}" method="POST" enctype="multipart/form-data" class="inline">
                        @csrf
                        <label class="cursor-pointer text-gray-400 hover:text-gray-600" title="Upload image">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="file" name="image" accept="image/*" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>

                    <!-- Toggle active -->
                    <form action="{{ route('client.restaurant.catering.packages.update', [$site, $package]) }}" method="POST" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $package->name }}">
                        <input type="hidden" name="price" value="{{ $package->price }}">
                        <input type="hidden" name="price_type" value="{{ $package->price_type }}">
                        <input type="hidden" name="active" value="{{ $package->active ? '0' : '1' }}">
                        <button type="submit" class="text-sm {{ $package->active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $package->active ? 'Deactivate' : 'Activate' }}">
                            {{ $package->active ? 'Disable' : 'Enable' }}
                        </button>
                    </form>

                    <!-- Delete -->
                    <form action="{{ route('client.restaurant.catering.packages.destroy', [$site, $package]) }}" method="POST" class="inline"
                          onsubmit="return confirm('Delete this package?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @if($package->image_url)
            <div class="mt-3">
                <img src="{{ $package->image_url }}" alt="{{ $package->name }}" class="w-32 h-24 object-cover rounded-lg">
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
