@extends('layouts.app')

@section('title', 'Edit ' . $site->business_name)

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
        <h1 class="text-3xl font-bold text-gray-900">Edit Site Settings</h1>
        <p class="text-gray-600 mt-1">Update your restaurant's information and branding</p>
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

    <!-- Main Settings Form -->
    <form action="{{ route('client.restaurant.update', $site) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="space-y-8">
            <!-- Site Template -->
            @php
                $currentTemplate = $site->settings['template'] ?? 'restaurant';
                $templateInfo = config('restaurant_templates.' . $currentTemplate, ['name' => ucfirst($currentTemplate), 'description' => '']);
                $previewPath = 'images/template-previews/' . $currentTemplate . '-hero.jpg';
            @endphp
            <a href="{{ route('client.restaurant.templates', $site) }}" class="block bg-white rounded-lg shadow p-6 hover:shadow-md transition group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        @if(file_exists(public_path($previewPath)))
                            <img src="/{{ $previewPath }}" alt="Current template" class="w-24 h-16 object-cover object-top rounded-lg border">
                        @endif
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Site Template</h2>
                            <p class="text-sm text-gray-500">
                                Currently: <span class="font-medium text-gray-700">{{ $templateInfo['name'] }}</span>
                                @if(!empty($templateInfo['description']))
                                    — {{ $templateInfo['description'] }}
                                @endif
                            </p>
                            <p class="text-xs text-indigo-600 mt-1">Browse all {{ count(config('restaurant_templates', [])) }} templates →</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>

            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>

                <div class="space-y-6">
                    <!-- Business Name -->
                    <div>
                        <label for="business_name" class="block text-sm font-medium text-gray-700">Business Name *</label>
                        <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $site->business_name) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        @error('business_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Slug -->
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700">URL Slug *</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                sos-tech.ca/s/
                            </span>
                            <input type="text" name="slug" id="slug" value="{{ old('slug', $site->slug) }}"
                                   class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 @if(auth()->user()->isDemoAccount()) bg-gray-100 @endif" required pattern="[a-z0-9\-]+"
                                   @if(auth()->user()->isDemoAccount()) readonly @endif>
                        </div>
                        @error('slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tagline -->
                    <div>
                        <label for="tagline" class="block text-sm font-medium text-gray-700">Tagline</label>
                        <input type="text" name="tagline" id="tagline" value="{{ old('tagline', $site->tagline) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('tagline')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $site->phone) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $site->email) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Address -->
                <div class="mt-6">
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea name="address" id="address" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $site->address) }}</textarea>
                    @error('address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Temporary Closure -->
                <div class="mt-6 p-4 border-2 rounded-lg {{ $site->force_closed ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="force_closed" value="0">
                        <input type="checkbox" name="force_closed" value="1" {{ old('force_closed', $site->force_closed) ? 'checked' : '' }}
                               class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">Temporarily closed / Seasonal closure</div>
                            <p class="text-sm text-gray-600 mt-0.5">Overrides your regular hours. Your site will show as Closed regardless of the time of day until you turn this off.</p>
                        </div>
                    </label>
                    <div class="mt-3 pl-8">
                        <label for="closure_message" class="block text-xs font-medium text-gray-700">Message shown to customers (optional)</label>
                        <input type="text" name="closure_message" id="closure_message" maxlength="255"
                               value="{{ old('closure_message', $site->closure_message) }}"
                               placeholder="e.g. Closed for the season — reopening in May 2027"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                        @error('closure_message')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Timezone -->
                <div class="mt-6">
                    <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone <x-help-icon article="timezone-setting" tooltip="What does the timezone control?" /></label>
                    <select name="timezone" id="timezone"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @php
                            $currentTz = old('timezone', $site->timezone ?: 'America/Halifax');
                            $tzGroups = [
                                'Canada' => [
                                    'America/St_Johns' => 'Newfoundland (NST/NDT)',
                                    'America/Halifax' => 'Atlantic — NB, NS, PEI (AST/ADT)',
                                    'America/Toronto' => 'Eastern — ON, QC (EST/EDT)',
                                    'America/Winnipeg' => 'Central — MB (CST/CDT)',
                                    'America/Regina' => 'Saskatchewan (CST, no DST)',
                                    'America/Edmonton' => 'Mountain — AB (MST/MDT)',
                                    'America/Vancouver' => 'Pacific — BC (PST/PDT)',
                                ],
                                'United States' => [
                                    'America/New_York' => 'Eastern (EST/EDT)',
                                    'America/Chicago' => 'Central (CST/CDT)',
                                    'America/Denver' => 'Mountain (MST/MDT)',
                                    'America/Phoenix' => 'Arizona (MST, no DST)',
                                    'America/Los_Angeles' => 'Pacific (PST/PDT)',
                                    'America/Anchorage' => 'Alaska (AKST/AKDT)',
                                    'Pacific/Honolulu' => 'Hawaii (HST)',
                                ],
                            ];
                        @endphp
                        @foreach($tzGroups as $region => $zones)
                        <optgroup label="{{ $region }}">
                            @foreach($zones as $tz => $label)
                            <option value="{{ $tz }}" {{ $currentTz === $tz ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Used to determine when your restaurant shows as open based on your hours. Default: Atlantic.</p>
                    @error('timezone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Features / Highlights -->
            <div class="bg-white rounded-lg shadow p-6" x-data="featuresEditor({{ json_encode($site->settings['features'] ?? []) }})">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Features &amp; Highlights</h2>
                <p class="text-sm text-gray-500 mb-4">Short phrases shown on your site (e.g. "Homemade Burgers", "Locally Sourced", "Takeout Available"). Keep them snappy — 3-6 words each.</p>

                <div class="space-y-2 mb-3">
                    <template x-for="(feature, idx) in features" :key="idx">
                        <div class="flex items-center gap-2">
                            <input type="text" name="settings[features][]" x-model="features[idx]" maxlength="60"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="e.g. Fresh Cut Fries">
                            <button type="button" @click="removeFeature(idx)"
                                    class="px-2 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded" title="Remove">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <input type="hidden" name="settings_features_placeholder" x-show="features.length === 0" value="">
                </div>

                <button type="button" @click="addFeature()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/></svg>
                    Add feature
                </button>
            </div>
            <script>
            function featuresEditor(initial) {
                return {
                    features: Array.isArray(initial) ? [...initial] : [],
                    addFeature() { this.features.push(''); },
                    removeFeature(idx) { this.features.splice(idx, 1); },
                };
            }
            </script>

            <!-- Social Proof -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Social Proof</h2>
                <p class="text-sm text-gray-500 mb-4">Display trust indicators on your site</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @php $socialProof = $site->social_proof ?? []; @endphp
                    <!-- Rating -->
                    <div>
                        <label for="social_proof_rating" class="block text-sm font-medium text-gray-700">Rating (e.g., 4.8)</label>
                        <input type="number" name="social_proof[rating]" id="social_proof_rating" value="{{ old('social_proof.rating', $socialProof['rating'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               step="0.1" min="0" max="5">
                    </div>

                    <!-- Reviews -->
                    <div>
                        <label for="social_proof_reviews" class="block text-sm font-medium text-gray-700">Reviews Text (e.g., "200+ Reviews")</label>
                        <input type="text" name="social_proof[reviews]" id="social_proof_reviews" value="{{ old('social_proof.reviews', $socialProof['reviews'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Followers -->
                    <div>
                        <label for="social_proof_followers" class="block text-sm font-medium text-gray-700">Followers Text (e.g., "5K Followers")</label>
                        <input type="text" name="social_proof[followers]" id="social_proof_followers" value="{{ old('social_proof.followers', $socialProof['followers'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <!-- Recommendation -->
                    <div>
                        <label for="social_proof_recommendation" class="block text-sm font-medium text-gray-700">Recommendation (e.g., "98% Recommended")</label>
                        <input type="text" name="social_proof[recommendation]" id="social_proof_recommendation" value="{{ old('social_proof.recommendation', $socialProof['recommendation'] ?? '') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Sister Sites -->
            @php
                $currentSisterSites = $site->settings['sister_sites'] ?? [];
                // SECURITY: only show sites owned by the same client (prevents cross-tenant data leak)
                $availableSites = \App\Models\RestaurantSite::where('id', '!=', $site->id)
                    ->where('client_id', $site->client_id)
                    ->whereIn('status', [\App\Models\RestaurantSite::STATUS_ACTIVE, \App\Models\RestaurantSite::STATUS_DEMO])
                    ->orderBy('business_name')
                    ->get();
            @endphp
            @if($availableSites->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Sister Sites</h2>
                <p class="text-sm text-gray-500 mb-4">Link to related restaurants (sister locations, sibling brands). They'll appear as cards on your site.</p>

                <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-md p-3">
                    @foreach($availableSites as $availSite)
                    <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                        <input type="checkbox" name="settings[sister_sites][]" value="{{ $availSite->slug }}"
                               {{ in_array($availSite->slug, $currentSisterSites) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div class="ml-3 flex-1">
                            <div class="text-sm font-medium text-gray-900">{{ $availSite->business_name }}</div>
                            @if($availSite->tagline)
                            <div class="text-xs text-gray-500">{{ $availSite->tagline }}</div>
                            @endif
                        </div>
                        @if($availSite->cuisine_type)
                        <span class="text-xs text-gray-400">{{ $availSite->cuisine_type }}</span>
                        @endif
                    </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-gray-500">Tip: Great for multi-brand owners like a burger joint + ice cream shop</p>

                {{-- Custom heading text --}}
                <div class="mt-6 border-t pt-5 space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Section headings</p>
                    <div>
                        <label for="sister_sites_eyebrow" class="block text-sm font-medium text-gray-700">Small eyebrow text</label>
                        <input type="text" name="settings[sister_sites_eyebrow]" id="sister_sites_eyebrow" maxlength="60"
                               value="{{ old('settings.sister_sites_eyebrow', $site->settings['sister_sites_eyebrow'] ?? '') }}"
                               placeholder="Also Visit Us At"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Small label above the main heading. Leave blank to use "Also Visit Us At".</p>
                    </div>
                    <div>
                        <label for="sister_sites_heading" class="block text-sm font-medium text-gray-700">Main heading</label>
                        <input type="text" name="settings[sister_sites_heading]" id="sister_sites_heading" maxlength="100"
                               value="{{ old('settings.sister_sites_heading', $site->settings['sister_sites_heading'] ?? '') }}"
                               placeholder="Our Other Locations"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <p class="mt-1 text-xs text-gray-500">Examples: "Our Other Locations", "Our Sister Restaurants", "More From Our Kitchen"</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Social Media Links -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Social Media</h2>
                <p class="text-sm text-gray-500 mb-4">Link to your social media pages — icons will appear on your site</p>

                @php $socialLinks = $site->settings['social_links'] ?? []; @endphp
                <div class="space-y-4">
                    <div>
                        <label for="social_facebook" class="block text-sm font-medium text-gray-700">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                Facebook
                            </span>
                        </label>
                        <input type="url" name="settings[social_links][facebook]" id="social_facebook"
                               value="{{ old('settings.social_links.facebook', $socialLinks['facebook'] ?? '') }}"
                               placeholder="https://facebook.com/yourpage"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="social_instagram" class="block text-sm font-medium text-gray-700">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                Instagram
                            </span>
                        </label>
                        <input type="url" name="settings[social_links][instagram]" id="social_instagram"
                               value="{{ old('settings.social_links.instagram', $socialLinks['instagram'] ?? '') }}"
                               placeholder="https://instagram.com/yourpage"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="social_tiktok" class="block text-sm font-medium text-gray-700">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                TikTok
                            </span>
                        </label>
                        <input type="url" name="settings[social_links][tiktok]" id="social_tiktok"
                               value="{{ old('settings.social_links.tiktok', $socialLinks['tiktok'] ?? '') }}"
                               placeholder="https://tiktok.com/@yourpage"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="social_twitter" class="block text-sm font-medium text-gray-700">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                X (Twitter)
                            </span>
                        </label>
                        <input type="url" name="settings[social_links][twitter]" id="social_twitter"
                               value="{{ old('settings.social_links.twitter', $socialLinks['twitter'] ?? '') }}"
                               placeholder="https://x.com/yourpage"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="social_google" class="block text-sm font-medium text-gray-700">
                            <span class="inline-flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                                Google Business Profile
                            </span>
                        </label>
                        <input type="url" name="settings[social_links][google]" id="social_google"
                               value="{{ old('settings.social_links.google', $socialLinks['google'] ?? '') }}"
                               placeholder="https://g.page/yourbusiness"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('client.restaurant.show', $site) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    <!-- Hours Section -->
    <div id="hours" class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Business Hours</h2>

        <form action="{{ route('client.restaurant.hours', $site) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                @php $hours = $site->getHours(); @endphp
                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                <div class="flex items-center">
                    <label class="w-28 text-sm font-medium text-gray-700">{{ $day }}</label>
                    <input type="text" name="hours[{{ $day }}]" value="{{ old('hours.' . $day, $hours[$day] ?? '') }}"
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="11:00 AM - 9:00 PM or Closed">
                </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    Update Hours
                </button>
            </div>
        </form>
    </div>

    <!-- Holiday Hours Section -->
    <div id="holiday-hours" class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Holiday & Special Hours</h2>
        <p class="text-sm text-gray-500 mb-4">Add exceptions for holidays or special events. These override your regular hours for specific dates.</p>

        <!-- Existing Holiday Hours -->
        @php $holidayHours = $site->holidayHours()->upcoming()->get(); @endphp
        @if($holidayHours->count() > 0)
        <div class="mb-6 space-y-3">
            @foreach($holidayHours as $holiday)
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <span class="text-sm font-medium text-gray-900">{{ $holiday->date->format('M j, Y') }}</span>
                        @if($holiday->label)
                        <span class="text-xs px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded-full">{{ $holiday->label }}</span>
                        @endif
                    </div>
                    <span class="text-sm {{ strtolower($holiday->hours) === 'closed' ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                        {{ $holiday->hours }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="editHolidayHour({{ $holiday->id }}, '{{ $holiday->hours }}', '{{ $holiday->label }}')"
                            class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</button>
                    <form action="{{ route('client.restaurant.holiday-hours.destroy', [$site, $holiday]) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Remove this holiday exception?')">Remove</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Add New Holiday Hours -->
        <form action="{{ route('client.restaurant.holiday-hours.store', $site) }}" method="POST" class="border-t pt-4">
            @csrf
            <h3 class="text-sm font-medium text-gray-700 mb-3">Add Holiday Hours</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="holiday_date" class="block text-xs text-gray-500 mb-1">Date</label>
                    <input type="date" name="date" id="holiday_date" min="{{ date('Y-m-d') }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                </div>
                <div>
                    <label for="holiday_hours" class="block text-xs text-gray-500 mb-1">Hours</label>
                    <input type="text" name="hours" id="holiday_hours" placeholder="11:00 AM - 3:00 PM or Closed"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
                </div>
                <div>
                    <label for="holiday_label" class="block text-xs text-gray-500 mb-1">Label (optional)</label>
                    <input type="text" name="label" id="holiday_label" placeholder="e.g., Christmas Day"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 w-full">
                        Add
                    </button>
                </div>
            </div>
            @error('date')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </form>

        <!-- Edit Modal (hidden by default) -->
        <div id="editHolidayModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Holiday Hours</h3>
                <form id="editHolidayForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hours</label>
                            <input type="text" name="hours" id="edit_holiday_hours"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Label (optional)</label>
                            <input type="text" name="label" id="edit_holiday_label"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeHolidayModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Online Ordering Section -->
    <div id="ordering" class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Online Ordering</h2>
        <p class="text-sm text-gray-500 mb-4">Enable customers to place orders directly from your website.</p>

        <form action="{{ route('client.restaurant.ordering.update', $site) }}" method="POST">
            @csrf
            @method('PUT')

            @php $orderingSettings = $site->ordering_settings ?? []; @endphp

            <div class="space-y-4">
                <!-- Enable Toggle -->
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Enable Online Ordering</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ordering_enabled" value="1" {{ $site->ordering_enabled ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>

                <!-- Order Types -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="ordering_settings[accepts_pickup]" value="1" {{ ($orderingSettings['accepts_pickup'] ?? true) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" id="accepts_pickup">
                        <label for="accepts_pickup" class="ml-2 text-sm text-gray-700">Accept Pickup Orders</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="ordering_settings[accepts_delivery]" value="1" {{ ($orderingSettings['accepts_delivery'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" id="accepts_delivery">
                        <label for="accepts_delivery" class="ml-2 text-sm text-gray-700">Accept Delivery Orders</label>
                    </div>
                </div>

                <!-- Settings -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order ($)</label>
                        <input type="number" step="0.01" min="0" name="ordering_settings[minimum_order]"
                               value="{{ $orderingSettings['minimum_order'] ?? '' }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee ($)</label>
                        <input type="number" step="0.01" min="0" name="ordering_settings[delivery_fee]"
                               value="{{ $orderingSettings['delivery_fee'] ?? '' }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="5.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prep Time (minutes)</label>
                        <input type="number" min="5" max="180" name="ordering_settings[estimated_prep_time_minutes]"
                               value="{{ $orderingSettings['estimated_prep_time_minutes'] ?? 30 }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="ordering_settings[tax_rate]"
                               value="{{ isset($orderingSettings['tax_rate']) ? $orderingSettings['tax_rate'] * 100 : 15 }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="15">
                        <p class="text-xs text-gray-500 mt-1">Enter as percentage (e.g., 15 for 15%)</p>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="pt-4 border-t">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Order Notifications</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Notification Email</label>
                            <input type="email" name="ordering_settings[notification_email]"
                                   value="{{ $orderingSettings['notification_email'] ?? $site->email }}"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="orders@restaurant.com">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Notification Phone (SMS)</label>
                            <input type="text" name="ordering_settings[notification_phone]"
                                   value="{{ $orderingSettings['notification_phone'] ?? $site->phone }}"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="+1 506 123 4567">
                        </div>
                    </div>
                </div>

                <!-- Auto-confirm Toggle -->
                <div class="flex items-center pt-2">
                    <input type="checkbox" name="ordering_settings[auto_confirm]" value="1" {{ ($orderingSettings['auto_confirm'] ?? false) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" id="auto_confirm">
                    <label for="auto_confirm" class="ml-2 text-sm text-gray-700">Auto-confirm orders (skip manual confirmation)</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    Save Ordering Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Delivery Zones Section -->
    <div id="delivery-zones" class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Delivery Zones</h2>

        @if($site->canUseDeliveryZones())
        <p class="text-sm text-gray-500 mb-4">Configure distance-based delivery fees. Zones are matched by radius from your restaurant.</p>

        <!-- Restaurant Coordinates -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Restaurant Location</h3>
            <form action="{{ route('client.restaurant.ordering.update', $site) }}" method="POST" class="flex flex-wrap gap-4 items-end">
                @csrf
                @method('PUT')
                <input type="hidden" name="ordering_enabled" value="{{ $site->ordering_enabled ? '1' : '0' }}">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" value="{{ $site->latitude }}"
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm w-40"
                           placeholder="46.0878">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" value="{{ $site->longitude }}"
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm w-40"
                           placeholder="-64.7782">
                </div>
                <button type="submit" class="px-3 py-2 bg-gray-600 text-white rounded-md text-sm font-medium hover:bg-gray-700">Save Coordinates</button>
            </form>
            @if(!$site->latitude || !$site->longitude)
            <p class="text-xs text-amber-600 mt-2">Set your restaurant coordinates to enable delivery zone validation.</p>
            @endif
        </div>

        <!-- Existing Zones -->
        @php $zones = $site->deliveryZones()->ordered()->get(); @endphp
        @if($zones->isNotEmpty())
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Radius (km)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fee</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Min Order</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Est. Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($zones as $zone)
                    <tr>
                        <form action="{{ route('client.restaurant.delivery-zones.update', [$site, $zone]) }}" method="POST" class="contents">
                            @csrf
                            @method('PUT')
                            <td class="px-4 py-2"><input type="text" name="name" value="{{ $zone->name }}" class="text-sm rounded border-gray-300 w-24" required></td>
                            <td class="px-4 py-2"><input type="number" step="0.1" name="radius_km" value="{{ $zone->radius_km }}" class="text-sm rounded border-gray-300 w-20" required></td>
                            <td class="px-4 py-2"><input type="number" step="0.01" name="delivery_fee" value="{{ $zone->delivery_fee }}" class="text-sm rounded border-gray-300 w-20" required></td>
                            <td class="px-4 py-2"><input type="number" step="0.01" name="minimum_order" value="{{ $zone->minimum_order }}" class="text-sm rounded border-gray-300 w-20"></td>
                            <td class="px-4 py-2"><input type="number" name="estimated_delivery_minutes" value="{{ $zone->estimated_delivery_minutes }}" class="text-sm rounded border-gray-300 w-16" required></td>
                            <td class="px-4 py-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $zone->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                            </td>
                            <td class="px-4 py-2 text-right space-x-1">
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-sm">Save</button>
                        </form>
                        <form action="{{ route('client.restaurant.delivery-zones.destroy', [$site, $zone]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this zone?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                        </form>
                            </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Add Zone Form -->
        <form action="{{ route('client.restaurant.delivery-zones.store', $site) }}" method="POST" class="border-t pt-4">
            @csrf
            <h3 class="text-sm font-medium text-gray-700 mb-3">Add Delivery Zone</h3>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Name</label>
                    <input type="text" name="name" required placeholder="Zone 1" class="w-full text-sm rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Radius (km)</label>
                    <input type="number" step="0.1" name="radius_km" required placeholder="3.0" class="w-full text-sm rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Fee ($)</label>
                    <input type="number" step="0.01" name="delivery_fee" required placeholder="3.00" class="w-full text-sm rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Min Order ($)</label>
                    <input type="number" step="0.01" name="minimum_order" placeholder="0" class="w-full text-sm rounded-md border-gray-300">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Est. Minutes</label>
                    <input type="number" name="estimated_delivery_minutes" required placeholder="20" class="w-full text-sm rounded-md border-gray-300">
                </div>
                <div>
                    <button type="submit" class="w-full px-3 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Add Zone</button>
                </div>
            </div>
        </form>

        @else
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-6 text-center">
            <svg class="w-12 h-12 text-purple-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delivery Zones</h3>
            <p class="text-sm text-gray-600 mb-4">Set distance-based delivery fees with automatic address validation. Available on the MenuDirect Max plan.</p>
            <a href="{{ route('client.restaurant.order.plans') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md text-sm font-medium hover:bg-purple-700">
                Upgrade to Max
            </a>
        </div>
        @endif
    </div>

    <!-- Reservations Section -->
    <div id="reservations" class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Reservations</h2>
        <p class="text-sm text-gray-500 mb-4">Allow customers to request table reservations.</p>

        <form action="{{ route('client.restaurant.reservations.update', $site) }}" method="POST">
            @csrf
            @method('PUT')

            @php $reservationSettings = $site->reservation_settings ?? []; @endphp

            <div class="space-y-4">
                <!-- Enable Toggle -->
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Enable Reservations</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="reservation_settings[enabled]" value="1" {{ !empty($reservationSettings['enabled']) ? 'checked' : '' }} class="sr-only peer" onchange="toggleReservationFields()">
                        <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-indigo-600 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>

                <div id="reservationFields" class="{{ empty($reservationSettings['enabled']) ? 'hidden' : '' }}">
                    <!-- Reservation Type -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reservation Type</label>
                        <select name="reservation_settings[type]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="toggleReservationType()">
                            <option value="email" {{ ($reservationSettings['type'] ?? 'email') === 'email' ? 'selected' : '' }}>Email Form (Simple)</option>
                            @if($site->canAcceptReservations())
                            <option value="built_in" {{ ($reservationSettings['type'] ?? '') === 'built_in' ? 'selected' : '' }}>Built-in Booking System</option>
                            @endif
                            <option value="opentable" {{ ($reservationSettings['type'] ?? '') === 'opentable' ? 'selected' : '' }}>OpenTable Widget</option>
                            <option value="resy" {{ ($reservationSettings['type'] ?? '') === 'resy' ? 'selected' : '' }}>Resy Widget</option>
                            <option value="custom" {{ ($reservationSettings['type'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Embed Code</option>
                        </select>
                    </div>

                    <!-- Email Form Settings -->
                    <div id="emailReservation" class="{{ ($reservationSettings['type'] ?? 'email') === 'email' ? '' : 'hidden' }}">
                        <label class="block text-sm text-gray-600 mb-1">Reservation Email</label>
                        <input type="email" name="reservation_settings[email]"
                               value="{{ $reservationSettings['email'] ?? $site->email }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="reservations@restaurant.com">
                        <p class="text-xs text-gray-500 mt-1">Reservation requests will be sent to this email.</p>
                    </div>

                    <!-- Built-in Booking System Settings -->
                    @if($site->canAcceptReservations())
                    <div id="builtInReservation" class="{{ ($reservationSettings['type'] ?? '') === 'built_in' ? '' : 'hidden' }}">
                        @php $resDefaults = $site->getReservationSettings(); @endphp
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Max Covers per Slot</label>
                                    <input type="number" min="1" max="500" name="reservation_settings[max_covers_per_slot]"
                                           value="{{ $resDefaults['max_covers_per_slot'] }}"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Slot Duration</label>
                                    <select name="reservation_settings[slot_duration_minutes]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="15" {{ ($resDefaults['slot_duration_minutes'] ?? 30) == 15 ? 'selected' : '' }}>15 minutes</option>
                                        <option value="30" {{ ($resDefaults['slot_duration_minutes'] ?? 30) == 30 ? 'selected' : '' }}>30 minutes</option>
                                        <option value="60" {{ ($resDefaults['slot_duration_minutes'] ?? 30) == 60 ? 'selected' : '' }}>60 minutes</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Max Party Size</label>
                                    <input type="number" min="1" max="50" name="reservation_settings[max_party_size]"
                                           value="{{ $resDefaults['max_party_size'] }}"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Reservation Duration (min)</label>
                                    <input type="number" min="30" max="300" name="reservation_settings[default_duration_minutes]"
                                           value="{{ $resDefaults['default_duration_minutes'] }}"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Advance Booking (days)</label>
                                    <input type="number" min="1" max="90" name="reservation_settings[advance_booking_days]"
                                           value="{{ $resDefaults['advance_booking_days'] }}"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Min Advance (hours)</label>
                                    <input type="number" min="0" max="72" name="reservation_settings[min_advance_hours]"
                                           value="{{ $resDefaults['min_advance_hours'] }}"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Notification Email</label>
                                    <input type="email" name="reservation_settings[notification_email]"
                                           value="{{ $resDefaults['notification_email'] ?? $site->email }}"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">Notification Phone</label>
                                    <input type="text" name="reservation_settings[notification_phone]"
                                           value="{{ $resDefaults['notification_phone'] ?? $site->phone }}"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="reservation_settings[auto_confirm]" value="1"
                                       {{ !empty($resDefaults['auto_confirm']) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" id="res_auto_confirm">
                                <label for="res_auto_confirm" class="ml-2 text-sm text-gray-700">Auto-confirm reservations</label>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Confirmation Message (optional)</label>
                                <textarea name="reservation_settings[confirmation_message]" rows="2"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                          placeholder="Thank you for your reservation! We look forward to seeing you.">{{ $resDefaults['confirmation_message'] ?? '' }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Cancellation Policy (optional)</label>
                                <textarea name="reservation_settings[cancellation_policy]" rows="2"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                          placeholder="Please cancel at least 2 hours in advance.">{{ $resDefaults['cancellation_policy'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- OpenTable Settings -->
                    <div id="opentableReservation" class="{{ ($reservationSettings['type'] ?? '') === 'opentable' ? '' : 'hidden' }}">
                        <label class="block text-sm text-gray-600 mb-1">OpenTable Restaurant ID</label>
                        <input type="text" name="reservation_settings[opentable_id]"
                               value="{{ $reservationSettings['opentable_id'] ?? '' }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="12345">
                        <p class="text-xs text-gray-500 mt-1">Find your restaurant ID in your OpenTable dashboard.</p>
                    </div>

                    <!-- Resy Settings -->
                    <div id="resyReservation" class="{{ ($reservationSettings['type'] ?? '') === 'resy' ? '' : 'hidden' }}">
                        <label class="block text-sm text-gray-600 mb-1">Resy Venue ID</label>
                        <input type="text" name="reservation_settings[resy_venue_id]"
                               value="{{ $reservationSettings['resy_venue_id'] ?? '' }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="abc123">
                        <p class="text-xs text-gray-500 mt-1">Find your venue ID in your Resy dashboard.</p>
                    </div>

                    <!-- Custom Embed -->
                    <div id="customReservation" class="{{ ($reservationSettings['type'] ?? '') === 'custom' ? '' : 'hidden' }}">
                        <label class="block text-sm text-gray-600 mb-1">Custom Embed Code</label>
                        <textarea name="reservation_settings[custom_embed]" rows="4"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                  placeholder="<script>...</script>">{{ $reservationSettings['custom_embed'] ?? '' }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Paste the embed code from your reservation provider.</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    Save Reservation Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Catering Settings Section -->
    @if($site->canUseCatering())
    <div id="catering" class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Catering Settings</h2>

        <form action="{{ route('client.restaurant.catering.settings.update', $site) }}" method="POST">
            @csrf
            @method('PUT')

            @php $cateringSettings = $site->catering_settings ?? []; @endphp

            <div class="space-y-6">
                <!-- Enable Catering -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Enable Catering</h3>
                        <p class="text-sm text-gray-500">Show catering packages and inquiry form on your site</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="catering_enabled" value="0">
                        <input type="checkbox" name="catering_enabled" value="1" {{ $site->catering_enabled ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notification Email</label>
                        <input type="email" name="catering_settings[notification_email]"
                               value="{{ $cateringSettings['notification_email'] ?? $site->email }}"
                               class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notification Phone (SMS)</label>
                        <input type="text" name="catering_settings[notification_phone]"
                               value="{{ $cateringSettings['notification_phone'] ?? $site->phone }}"
                               class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Lead Time (hours)</label>
                        <input type="number" name="catering_settings[lead_time_hours]" min="1"
                               value="{{ $cateringSettings['lead_time_hours'] ?? 48 }}"
                               class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-500 mt-1">How far in advance must customers book?</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Guests</label>
                        <input type="number" name="catering_settings[min_guests]" min="1"
                               value="{{ $cateringSettings['min_guests'] ?? 10 }}"
                               class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custom Message</label>
                        <textarea name="catering_settings[custom_message]" rows="2"
                                  class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Shown on the catering inquiry form...">{{ $cateringSettings['custom_message'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-between items-center">
                <a href="{{ route('client.restaurant.catering.packages', $site) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                    Manage Catering Packages &rarr;
                </a>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 font-medium">
                    Save Catering Settings
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Colors Section -->
    <div id="colors" class="mt-8 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Brand Colors</h2>

        <form action="{{ route('client.restaurant.colors', $site) }}" method="POST">
            @csrf
            @method('PUT')

            @php $colors = $site->getColors(); @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="colors_primary" class="block text-sm font-medium text-gray-700">Primary Color</label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="color" name="colors[primary]" id="colors_primary" value="{{ old('colors.primary', $colors['primary']) }}"
                               class="h-10 w-16 rounded border border-gray-300">
                        <input type="text" value="{{ $colors['primary'] }}" id="colors_primary_text"
                               class="flex-1 rounded-md border-gray-300 shadow-sm text-sm"
                               onchange="document.getElementById('colors_primary').value = this.value">
                    </div>
                    @error('colors.primary')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="colors_secondary" class="block text-sm font-medium text-gray-700">Secondary Color</label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="color" name="colors[secondary]" id="colors_secondary" value="{{ old('colors.secondary', $colors['secondary']) }}"
                               class="h-10 w-16 rounded border border-gray-300">
                        <input type="text" value="{{ $colors['secondary'] }}" id="colors_secondary_text"
                               class="flex-1 rounded-md border-gray-300 shadow-sm text-sm"
                               onchange="document.getElementById('colors_secondary').value = this.value">
                    </div>
                    @error('colors.secondary')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="colors_accent" class="block text-sm font-medium text-gray-700">Accent Color</label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="color" name="colors[accent]" id="colors_accent" value="{{ old('colors.accent', $colors['accent']) }}"
                               class="h-10 w-16 rounded border border-gray-300">
                        <input type="text" value="{{ $colors['accent'] }}" id="colors_accent_text"
                               class="flex-1 rounded-md border-gray-300 shadow-sm text-sm"
                               onchange="document.getElementById('colors_accent').value = this.value">
                    </div>
                    @error('colors.accent')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
                    Update Colors
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Sync color picker with text input
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = document.getElementById(colorInput.id + '_text');
    if (textInput) {
        colorInput.addEventListener('input', () => {
            textInput.value = colorInput.value;
        });
    }
});

// Holiday Hours Modal
function editHolidayHour(id, hours, label) {
    const form = document.getElementById('editHolidayForm');
    form.action = '{{ url("client/restaurant/{$site->id}/holiday-hours") }}/' + id;
    document.getElementById('edit_holiday_hours').value = hours;
    document.getElementById('edit_holiday_label').value = label || '';
    document.getElementById('editHolidayModal').classList.remove('hidden');
}

function closeHolidayModal() {
    document.getElementById('editHolidayModal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('editHolidayModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeHolidayModal();
});

// Reservation settings toggles
function toggleReservationFields() {
    const checkbox = document.querySelector('input[name="reservation_settings[enabled]"]');
    const fields = document.getElementById('reservationFields');
    if (checkbox.checked) {
        fields.classList.remove('hidden');
    } else {
        fields.classList.add('hidden');
    }
}

function toggleReservationType() {
    const select = document.querySelector('select[name="reservation_settings[type]"]');
    const type = select.value;

    document.getElementById('emailReservation').classList.add('hidden');
    document.getElementById('opentableReservation').classList.add('hidden');
    document.getElementById('resyReservation').classList.add('hidden');
    document.getElementById('customReservation').classList.add('hidden');
    const builtIn = document.getElementById('builtInReservation');
    if (builtIn) builtIn.classList.add('hidden');

    if (type === 'email') document.getElementById('emailReservation').classList.remove('hidden');
    if (type === 'built_in' && builtIn) builtIn.classList.remove('hidden');
    if (type === 'opentable') document.getElementById('opentableReservation').classList.remove('hidden');
    if (type === 'resy') document.getElementById('resyReservation').classList.remove('hidden');
    if (type === 'custom') document.getElementById('customReservation').classList.remove('hidden');
}
</script>
@endsection
