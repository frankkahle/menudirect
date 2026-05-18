@extends('layouts.app')

@section('title', 'Edit: ' . $site->business_name)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('admin.restaurant.index') }}" class="text-indigo-600 hover:text-indigo-800">
            &larr; Back to Restaurant Sites
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Edit: {{ $site->business_name }}</h1>
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

    <form action="{{ route('admin.restaurant.update', $site) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Owner -->
        <div>
            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                Owner (Client) <span class="text-red-500">*</span>
            </label>
            <select name="client_id" id="client_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id', $site->client_id) == $client->id ? 'selected' : '' }}>
                        {{ $client->name }} ({{ $client->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $site->business_name) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                    Slug <span class="text-red-500">*</span>
                </label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        .menudirect.ca
                    </span>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $site->slug) }}"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-r-lg focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
            </div>
        </div>

        <div>
            <label for="tagline" class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
            <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $site->tagline) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="A short description of the restaurant">
        </div>

        <div>
            <label for="custom_domain" class="block text-sm font-medium text-gray-700 mb-1">Custom Domain</label>
            <input type="text" name="custom_domain" id="custom_domain" value="{{ old('custom_domain', $site->custom_domain) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="e.g., myrestaurant.ca">
        </div>

        <!-- Contact Info -->
        <div class="border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $site->phone) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $site->email) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
            <div class="mt-4">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input type="text" name="address" id="address" value="{{ old('address', $site->address) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <!-- Plan & Status -->
        <div class="border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Plan & Status</h2>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
                        <option value="demo" {{ old('status', $site->status) === 'demo' ? 'selected' : '' }}>Demo</option>
                        <option value="active" {{ old('status', $site->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ old('status', $site->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div>
                    <label for="plan" class="block text-sm font-medium text-gray-700 mb-1">Plan Type <span class="text-red-500">*</span></label>
                    <select name="plan" id="plan"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" required>
                        <option value="basic" {{ old('plan', $site->plan) === 'basic' ? 'selected' : '' }}>Basic</option>
                        <option value="selfservice" {{ old('plan', $site->plan) === 'selfservice' ? 'selected' : '' }}>Self-Service</option>
                        <option value="premium" {{ old('plan', $site->plan) === 'premium' ? 'selected' : '' }}>Premium</option>
                        <option value="max" {{ old('plan', $site->plan) === 'max' ? 'selected' : '' }}>Max</option>
                    </select>
                </div>
                <div>
                    <label for="restaurant_plan_id" class="block text-sm font-medium text-gray-700 mb-1">Subscription Plan</label>
                    <select name="restaurant_plan_id" id="restaurant_plan_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">None</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('restaurant_plan_id', $site->restaurant_plan_id) == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} (${{ number_format($plan->price_monthly, 2) }}/mo)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Online Ordering -->
        <div class="border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Online Ordering</h2>
            @php $orderingSettings = $site->ordering_settings ?? []; @endphp

            <div class="space-y-4">
                <!-- Enable Toggle -->
                <div class="flex items-center">
                    <input type="checkbox" name="ordering_enabled" id="ordering_enabled" value="1"
                           {{ old('ordering_enabled', $site->ordering_enabled) ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="ordering_enabled" class="ml-2 text-sm font-medium text-gray-700">Enable Online Ordering</label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="ordering_settings[accepts_pickup]" id="accepts_pickup" value="1"
                               {{ old('ordering_settings.accepts_pickup', $orderingSettings['accepts_pickup'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="accepts_pickup" class="ml-2 text-sm text-gray-700">Accept Pickup Orders</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="ordering_settings[accepts_delivery]" id="accepts_delivery" value="1"
                               {{ old('ordering_settings.accepts_delivery', $orderingSettings['accepts_delivery'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="accepts_delivery" class="ml-2 text-sm text-gray-700">Accept Delivery Orders</label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="minimum_order" class="block text-sm font-medium text-gray-700 mb-1">Minimum Order ($)</label>
                        <input type="number" step="0.01" min="0" name="ordering_settings[minimum_order]" id="minimum_order"
                               value="{{ old('ordering_settings.minimum_order', $orderingSettings['minimum_order'] ?? 0) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="delivery_fee" class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee ($)</label>
                        <input type="number" step="0.01" min="0" name="ordering_settings[delivery_fee]" id="delivery_fee"
                               value="{{ old('ordering_settings.delivery_fee', $orderingSettings['delivery_fee'] ?? 0) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="ordering_settings[tax_rate]" id="tax_rate"
                               value="{{ old('ordering_settings.tax_rate', isset($orderingSettings['tax_rate']) ? $orderingSettings['tax_rate'] * 100 : 15) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="estimated_prep_time_minutes" class="block text-sm font-medium text-gray-700 mb-1">Prep Time (minutes)</label>
                        <input type="number" min="5" max="180" name="ordering_settings[estimated_prep_time_minutes]" id="estimated_prep_time_minutes"
                               value="{{ old('ordering_settings.estimated_prep_time_minutes', $orderingSettings['estimated_prep_time_minutes'] ?? 30) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="flex items-end pb-2">
                        <input type="checkbox" name="ordering_settings[auto_confirm]" id="auto_confirm" value="1"
                               {{ old('ordering_settings.auto_confirm', $orderingSettings['auto_confirm'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="auto_confirm" class="ml-2 text-sm text-gray-700">Auto-confirm orders</label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="notification_email" class="block text-sm font-medium text-gray-700 mb-1">Notification Email</label>
                        <input type="email" name="ordering_settings[notification_email]" id="notification_email"
                               value="{{ old('ordering_settings.notification_email', $orderingSettings['notification_email'] ?? $site->email) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="orders@restaurant.com">
                    </div>
                    <div>
                        <label for="notification_phone" class="block text-sm font-medium text-gray-700 mb-1">Notification Phone (SMS)</label>
                        <input type="text" name="ordering_settings[notification_phone]" id="notification_phone"
                               value="{{ old('ordering_settings.notification_phone', $orderingSettings['notification_phone'] ?? $site->phone) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="+1 506 123 4567">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO & AEO -->
        <div class="border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">SEO & AI Discovery</h2>
            <p class="text-sm text-gray-500 mb-4">Search engine optimization and AI engine optimization settings</p>

            <div class="space-y-4">
                <!-- Meta Title -->
                <div>
                    <label for="seo_title" class="block text-sm font-medium text-gray-700 mb-1">
                        SEO Title <span class="text-gray-400 text-xs">(max 70 chars)</span>
                    </label>
                    <input type="text" name="seo_title" id="seo_title" maxlength="70"
                           value="{{ old('seo_title', $site->seo_title) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g., Suwanna Thai - Authentic Thai Cuisine in Moncton, NB">
                    <p class="text-xs text-gray-400 mt-1">
                        Leave blank to auto-generate: <em>{{ $site->business_name }}{{ $site->tagline ? ' - ' . $site->tagline : '' }}</em>
                    </p>
                </div>

                <!-- Meta Description -->
                <div>
                    <label for="seo_description" class="block text-sm font-medium text-gray-700 mb-1">
                        SEO Description <span class="text-gray-400 text-xs">(max 160 chars)</span>
                    </label>
                    <textarea name="seo_description" id="seo_description" maxlength="160" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="A compelling description for search results...">{{ old('seo_description', $site->seo_description) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">
                        Used in Google search results, social shares, and AI answers. Leave blank to auto-generate.
                    </p>
                </div>

                <!-- Keywords -->
                <div>
                    <label for="seo_keywords" class="block text-sm font-medium text-gray-700 mb-1">
                        Keywords <span class="text-gray-400 text-xs">(comma-separated)</span>
                    </label>
                    <input type="text" name="seo_keywords" id="seo_keywords" maxlength="500"
                           value="{{ old('seo_keywords', $site->seo_keywords) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g., thai food, pad thai, moncton restaurant, takeout">
                    <p class="text-xs text-gray-400 mt-1">Helps AI systems and internal search. Not heavily used by Google but valuable for AI discovery.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Cuisine Type -->
                    <div>
                        <label for="cuisine_type" class="block text-sm font-medium text-gray-700 mb-1">Cuisine Type</label>
                        <input type="text" name="cuisine_type" id="cuisine_type"
                               value="{{ old('cuisine_type', $site->cuisine_type) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="e.g., Thai, Italian, Canadian">
                        <p class="text-xs text-gray-400 mt-1">Used in Restaurant schema markup (servesCuisine)</p>
                    </div>

                    <!-- Price Range -->
                    <div>
                        <label for="price_range" class="block text-sm font-medium text-gray-700 mb-1">Price Range</label>
                        <select name="price_range" id="price_range"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Not set</option>
                            <option value="$" {{ old('price_range', $site->price_range) === '$' ? 'selected' : '' }}>$ - Budget</option>
                            <option value="$$" {{ old('price_range', $site->price_range) === '$$' ? 'selected' : '' }}>$$ - Moderate</option>
                            <option value="$$$" {{ old('price_range', $site->price_range) === '$$$' ? 'selected' : '' }}>$$$ - Upscale</option>
                            <option value="$$$$" {{ old('price_range', $site->price_range) === '$$$$' ? 'selected' : '' }}>$$$$ - Fine Dining</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Used in schema.org priceRange</p>
                    </div>
                </div>

                <!-- Google Place ID -->
                <div>
                    <label for="google_place_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Google Place ID
                    </label>
                    <input type="text" name="google_place_id" id="google_place_id"
                           value="{{ old('google_place_id', $site->google_place_id ?? ($site->settings['google_place_id'] ?? '')) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g., ChIJ...">
                    <p class="text-xs text-gray-400 mt-1">
                        Enables Google Reviews integration. Find at
                        <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" class="text-indigo-600 hover:underline">Google Place ID Finder</a>
                    </p>
                </div>

                <!-- OG Image -->
                <div>
                    <label for="og_image" class="block text-sm font-medium text-gray-700 mb-1">
                        Social Share Image (OG Image)
                    </label>
                    @if($site->og_image_path)
                    <div class="mb-2">
                        <img src="{{ Storage::disk('public')->url($site->og_image_path) }}" alt="Current OG image" class="h-24 rounded border">
                    </div>
                    @endif
                    <input type="file" name="og_image" id="og_image" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-400 mt-1">
                        Recommended: 1200x630px. Used when shared on Facebook, Twitter, etc. Falls back to logo/hero image.
                    </p>
                </div>
            </div>

            <!-- SEO Preview -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Search Result Preview</h3>
                <div class="font-medium text-blue-700 text-lg truncate">
                    {{ $site->seo_title ?: $site->business_name . ($site->tagline ? ' - ' . $site->tagline : '') }}
                </div>
                <div class="text-green-700 text-sm">
                    {{ $site->getPublicUrl() }}
                </div>
                <div class="text-sm text-gray-600 line-clamp-2">
                    {{ $site->seo_description ?: $site->business_name . ' - ' . ($site->tagline ?? 'Restaurant') . '. View our menu, hours, and order online.' }}
                </div>
            </div>

            <!-- AEO Status -->
            <div class="mt-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                <h3 class="text-sm font-medium text-indigo-800 mb-2">AI Discovery (AEO) Status</h3>
                <ul class="text-sm text-indigo-700 space-y-1">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Restaurant JSON-LD schema (auto-generated)
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        LocalBusiness schema (auto-generated)
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mr-2 {{ $site->cuisine_type ? 'text-green-500' : 'text-yellow-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Cuisine type: {{ $site->cuisine_type ?: 'Not set (defaults to Canadian)' }}
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mr-2 {{ ($site->google_place_id ?? ($site->settings['google_place_id'] ?? null)) ? 'text-green-500' : 'text-yellow-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Google Reviews: {{ ($site->google_place_id ?? ($site->settings['google_place_id'] ?? null)) ? 'Connected' : 'Not connected (add Place ID above)' }}
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Menu items in schema ({{ $site->categories()->withCount('items')->get()->sum('items_count') }} items indexed)
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        Sitemap + IndexNow integration
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        llms.txt for AI crawlers
                    </li>
                </ul>
            </div>
        </div>

        <!-- Actions -->
        <div class="border-t border-gray-200 pt-6 flex justify-between">
            <div>
                <button type="button" id="delete-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete Site
                </button>
            </div>
            <div class="space-x-3">
                <a href="{{ route('admin.restaurant.index') }}"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    <!-- Delete form (outside main form to avoid nesting) -->
    <form id="delete-form" action="{{ route('admin.restaurant.destroy', $site) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <script>
        document.getElementById('delete-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this restaurant site? This cannot be undone.')) {
                document.getElementById('delete-form').submit();
            }
        });
    </script>
</div>

    
    <!-- Custom Domains Management -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Custom Domains</h2>
        <p class="text-sm text-gray-500 mb-4">Manage custom domains for this restaurant site. DNS records are automatically created in Cloudflare pointing to edge.sos-tech.ca.</p>

        @if(session('domain_status'))
            @php $ds = session('domain_status'); @endphp
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-4">
                <h3 class="font-semibold text-indigo-900 mb-2">Status Check: {{ $ds['domain'] }}</h3>
                <div class="space-y-2 text-sm">
                    @foreach($ds['checks'] ?? [] as $checkName => $check)
                        <div class="flex items-start gap-2">
                            @if(($check['status'] ?? '') === 'active' || ($check['status'] ?? '') === 'ok')
                                <span class="text-green-600 font-bold">&#10003;</span>
                            @elseif(($check['status'] ?? '') === 'error' || ($check['status'] ?? '') === 'failed' || ($check['status'] ?? '') === 'missing' || ($check['status'] ?? '') === 'not_configured')
                                <span class="text-red-600 font-bold">&#10007;</span>
                            @else
                                <span class="text-yellow-600 font-bold">&#9679;</span>
                            @endif
                            <div>
                                <span class="font-medium text-gray-700 capitalize">{{ str_replace('_', ' ', $checkName) }}:</span>
                                <span class="text-gray-600">{{ $check['message'] ?? 'Unknown' }}</span>
                                @if(!empty($check['name_servers']))
                                    <div class="text-xs text-gray-500 mt-1">Nameservers: {{ implode(', ', $check['name_servers']) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($site->customDomains->count())
            <div class="space-y-3 mb-4">
                @foreach($site->customDomains as $cd)
                    <div class="flex items-center justify-between p-3 rounded-lg border {{ $cd->is_primary ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                        <div>
                            <span class="font-medium text-gray-900">{{ $cd->domain }}</span>
                            @if($cd->is_primary)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-200 text-green-800">Primary</span>
                            @endif
                            @if($cd->dns_configured)
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">DNS Active</span>
                            @else
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">DNS Pending</span>
                            @endif
                            @if($cd->status === 'failed')
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Failed</span>
                            @endif
                            @if($cd->notes)
                                <p class="text-xs text-gray-500 mt-1">{{ $cd->notes }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('admin.restaurant.check-custom-domain-status', [$site, $cd]) }}"
                               class="text-xs px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">Check Status</a>
                            <form action="{{ route('admin.restaurant.activate-custom-domain-haproxy', [$site, $cd]) }}" method="POST" class="inline" onsubmit="return confirm('This will obtain an SSL cert and activate {{ $cd->domain }} on HAProxy. Continue?')">
                                @csrf
                                <button type="submit" class="text-xs px-2 py-1 bg-orange-600 text-white rounded hover:bg-orange-700">Activate HAProxy</button>
                            </form>
                            @if(!$cd->is_primary)
                                <form action="{{ route('admin.restaurant.set-primary-custom-domain', [$site, $cd]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700">Set Primary</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.restaurant.remove-custom-domain', [$site, $cd]) }}" method="POST" class="inline" onsubmit="return confirm('Remove {{ $cd->domain }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">Remove</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-sm text-gray-500 mb-4 p-3 bg-gray-50 rounded-lg">No custom domains configured yet.</div>
        @endif

        <!-- Add New Domain Form -->
        <form action="{{ route('admin.restaurant.add-custom-domain', $site) }}" method="POST" class="border-t border-gray-200 pt-4">
            @csrf
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label for="new_custom_domain" class="block text-sm font-medium text-gray-700 mb-1">Add Domain</label>
                    <input type="text" name="domain" id="new_custom_domain"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="e.g. mybusiness.com" required>
                </div>
                <label class="flex items-center gap-2 pb-2">
                    <input type="checkbox" name="is_primary" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Primary</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium whitespace-nowrap">
                    Add & Configure DNS
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2">Creates CNAME records in Cloudflare pointing to edge.sos-tech.ca. Domain will be added to Cloudflare if not already there.</p>
        </form>
    </div>

    @include('admin.partials.restaurant-billing')
@endsection
