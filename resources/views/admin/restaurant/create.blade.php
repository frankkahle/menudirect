@extends('layouts.app')

@section('title', 'Create Demo Restaurant Site')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.restaurant.index') }}" class="text-indigo-600 hover:text-indigo-800">
            &larr; Back to Restaurant Sites
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Create Demo Restaurant Site</h1>
        <p class="text-gray-600 mt-1">Create a demo site to show potential customers. Convert to paying when they're ready.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.restaurant.store') }}" method="POST" class="bg-white rounded-lg shadow-md p-6 space-y-6">
        @csrf

        <div>
            <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                Business Name <span class="text-red-500">*</span>
            </label>
            <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="e.g. Joe's Pizza" required>
        </div>

        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                URL Slug <span class="text-red-500">*</span>
            </label>
            <div class="flex">
                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                    .menudirect.ca
                </span>
                <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-r-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="joes-pizza" pattern="[a-z0-9\-]+" required>
            </div>
            <p class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, and hyphens only</p>
        </div>

        <div>
            <label for="tagline" class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
            <input type="text" name="tagline" id="tagline" value="{{ old('tagline') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="The best pizza in town!">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="(506) 555-1234">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="info@restaurant.ca">
            </div>
        </div>

        <div>
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <input type="text" name="address" id="address" value="{{ old('address') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="123 Main St, Moncton, NB">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="cuisine_type" class="block text-sm font-medium text-gray-700 mb-1">Cuisine Type</label>
                <input type="text" name="cuisine_type" id="cuisine_type" value="{{ old('cuisine_type') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="e.g. Italian, Thai, Burgers">
            </div>
            <div>
                <label for="price_range" class="block text-sm font-medium text-gray-700 mb-1">Price Range</label>
                <select name="price_range" id="price_range"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- Select --</option>
                    <option value="$" {{ old('price_range') === '$' ? 'selected' : '' }}>$ — Budget</option>
                    <option value="$$" {{ old('price_range') === '$$' ? 'selected' : '' }}>$$ — Moderate</option>
                    <option value="$$$" {{ old('price_range') === '$$$' ? 'selected' : '' }}>$$$ — Upscale</option>
                    <option value="$$$$" {{ old('price_range') === '$$$$' ? 'selected' : '' }}>$$$$ — Fine Dining</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Brand Colors</label>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="color_primary" class="block text-xs text-gray-500 mb-1">Primary</label>
                    <input type="color" name="color_primary" id="color_primary" value="{{ old('color_primary', '#2563eb') }}"
                        class="w-full h-10 rounded border border-gray-300 cursor-pointer">
                </div>
                <div>
                    <label for="color_secondary" class="block text-xs text-gray-500 mb-1">Secondary</label>
                    <input type="color" name="color_secondary" id="color_secondary" value="{{ old('color_secondary', '#7c3aed') }}"
                        class="w-full h-10 rounded border border-gray-300 cursor-pointer">
                </div>
                <div>
                    <label for="color_accent" class="block text-xs text-gray-500 mb-1">Accent</label>
                    <input type="color" name="color_accent" id="color_accent" value="{{ old('color_accent', '#f59e0b') }}"
                        class="w-full h-10 rounded border border-gray-300 cursor-pointer">
                </div>
            </div>
        </div>

        <div class="border-t pt-6 flex justify-end space-x-3">
            <a href="{{ route('admin.restaurant.index') }}"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                Create Demo Site
            </button>
        </div>
    </form>
</div>

<script>
// Auto-generate slug from business name
document.getElementById('business_name').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.dataset.userEdited) {
        slugField.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 50);
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.userEdited = 'true';
});
</script>
@endsection
