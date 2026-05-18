@extends('layouts.app')

@section('title', 'Create Restaurant Site')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Restaurant Sites
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Create Restaurant Site</h1>
        <p class="text-gray-600 mt-1">Set up your restaurant's online presence</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('client.restaurant.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Business Name -->
                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700">Business Name *</label>
                    <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Joe's Pizza" required>
                    @error('business_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700">URL Slug *</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            .menudirect.ca
                        </span>
                        <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                               class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="joes-pizza" required pattern="[a-z0-9\-]+">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Only lowercase letters, numbers, and hyphens allowed.</p>
                    @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tagline -->
                <div>
                    <label for="tagline" class="block text-sm font-medium text-gray-700">Tagline</label>
                    <input type="text" name="tagline" id="tagline" value="{{ old('tagline') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Best pizza in town since 1985">
                    @error('tagline')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Info -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="(506) 555-1234">
                            @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="info@joespizza.com">
                            @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mt-6">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" id="address" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="123 Main Street, Moncton, NB E1C 1A1">{{ old('address') }}</textarea>
                        @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit -->
                <div class="border-t border-gray-200 pt-6 flex justify-end space-x-3">
                    <a href="{{ route('client.restaurant.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                        Create Site
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate slug from business name
document.getElementById('business_name').addEventListener('input', function(e) {
    const slugInput = document.getElementById('slug');
    if (!slugInput.dataset.modified) {
        slugInput.value = e.target.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.modified = true;
});
</script>
@endsection
