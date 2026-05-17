@extends('layouts.app')
@use('Illuminate\Support\Facades\Storage')

@section('title', $site->business_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Restaurant Sites
        </a>

        <div class="flex justify-between items-start">
            <div class="flex items-center">
                @if($site->logo_url)
                <img src="{{ $site->logo_url }}" alt="{{ $site->business_name }}" class="w-16 h-16 rounded-lg shadow mr-4 object-cover">
                @endif
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $site->business_name }}</h1>
                    <p class="text-gray-500 mt-1">
                        <a href="{{ $site->getPublicUrl() }}" target="_blank" class="hover:text-indigo-600">
                            {{ $site->getPublicUrl() }}
                            <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                @if($site->force_closed)
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-800 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Temporarily Closed
                </span>
                @endif
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $site->status_badge_class }}">
                    {{ $site->status_label }}
                </span>
                @if(!$site->isPublished())
                <form action="{{ route('client.restaurant.publish', $site) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                        Publish Site
                    </button>
                </form>
                @endif
            </div>
        </div>
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Categories</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['categories'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Menu Items</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['items'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Featured</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['featured'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Announcements</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['announcements'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Quick Actions -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Management Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Manage Your Site</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('client.restaurant.menu', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-green-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Menu Editor</p>
                            <p class="text-sm text-gray-500">Add and edit menu items</p>
                        </div>
                    </a>

                    <a href="{{ route('client.restaurant.announcements', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-purple-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Announcements</p>
                            <p class="text-sm text-gray-500">Specials and closures</p>
                        </div>
                    </a>

                    <a href="{{ route('client.restaurant.edit', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-blue-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Site Settings</p>
                            <p class="text-sm text-gray-500">Contact info and branding</p>
                        </div>
                    </a>

                    <a href="{{ $site->getPublicUrl() }}" target="_blank" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-indigo-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">View Live Site</p>
                            <p class="text-sm text-gray-500">See how it looks</p>
                        </div>
                    </a>

                    <a href="{{ route('client.restaurant.locations', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-orange-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Locations</p>
                            <p class="text-sm text-gray-500">Multi-location support</p>
                        </div>
                    </a>

                    @if($site->ordering_enabled)
                    <a href="{{ route('client.restaurant.orders.index', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-red-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Orders</p>
                            <p class="text-sm text-gray-500">Manage online orders</p>
                        </div>
                    </a>
                    @endif

                    <a href="{{ route('client.restaurant.staff.index', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-indigo-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Staff</p>
                            <p class="text-sm text-gray-500">Invite and manage team</p>
                        </div>
                    </a>

                    <a href="{{ route('client.restaurant.payments.show', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-emerald-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Online Payments</p>
                            @if($site->stripe_charges_enabled && $site->online_payments_enabled)
                            <p class="text-sm text-emerald-600">✓ Active</p>
                            @elseif($site->stripe_account_id)
                            <p class="text-sm text-amber-600">Setup in progress</p>
                            @else
                            <p class="text-sm text-gray-500">Accept card payments</p>
                            @endif
                        </div>
                    </a>

                    @if($site->canAcceptReservations() && !empty($site->reservation_settings['enabled']) && ($site->reservation_settings['type'] ?? '') === 'built_in')
                    <a href="{{ route('client.restaurant.reservations.index', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-violet-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Reservations</p>
                            <p class="text-sm text-gray-500">Manage table bookings</p>
                        </div>
                    </a>
                    @endif

                    @if($site->catering_enabled && $site->canUseCatering())
                    <a href="{{ route('client.restaurant.catering.index', $site) }}" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="p-2 bg-amber-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Catering</p>
                            <p class="text-sm text-gray-500">Manage catering packages & inquiries</p>
                        </div>
                    </a>
                    @endif
                </div>
            </div>

            <!-- Template Picker -->
            <a href="{{ route('client.restaurant.templates', $site) }}" class="block bg-white rounded-lg shadow p-6 hover:shadow-md transition group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        @if(file_exists(public_path('images/template-previews/' . ($site->settings['template'] ?? 'restaurant') . '-hero.jpg')))
                        <img src="/images/template-previews/{{ $site->settings['template'] ?? 'restaurant' }}-hero.jpg"
                             alt="Current template"
                             class="w-20 h-14 object-cover object-top rounded-lg border">
                        @endif
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Site Template</h2>
                            <p class="text-sm text-gray-500">
                                Currently using <span class="font-medium text-gray-700">{{ config('restaurant_templates.' . ($site->settings['template'] ?? 'restaurant') . '.name', 'Classic') }}</span>
                                — {{ config('restaurant_templates.' . ($site->settings['template'] ?? 'restaurant') . '.description', '') }}
                            </p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>

            <!-- Upload Media -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Media</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Logo Upload -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Logo</h3>
                        <form action="{{ route('client.restaurant.logo', $site) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <x-image-cropper
                                name="logo"
                                :aspect-ratio="config('images.aspect_ratios.logo')"
                                :current="$site->logo_url"
                                preview-class="w-24 h-24 object-cover rounded-lg border"
                                help-text="Max 5MB. JPG, PNG, GIF, or WebP. Cropped to a square." />
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">Upload Logo</button>
                                @if($site->logo_url)
                                    <x-recrop-button
                                        :url="$site->logo_url"
                                        :aspect="config('images.aspect_ratios.logo')"
                                        :post-url="route('client.restaurant.logo', $site)"
                                        field-name="logo"
                                        label="Re-crop existing" />
                                @endif
                            </div>
                        </form>
                        @if($site->logo_url)
                        <form action="{{ route('client.restaurant.logo.remove', $site) }}" method="POST" onsubmit="return confirm('Remove the logo? This can\'t be undone.')" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 hover:underline">Remove logo</button>
                        </form>
                        @endif
                        @error('logo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cover Photo Upload -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Cover Photo</h3>
                        <form action="{{ route('client.restaurant.cover', $site) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <x-image-cropper
                                name="cover_photo"
                                :aspect-ratio="config('images.aspect_ratios.cover')"
                                :current="$site->cover_photo_url"
                                preview-class="w-full h-24 object-cover rounded-lg border"
                                help-text="Max 5MB. JPG, PNG, GIF, or WebP. Cropped to 16:9." />
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white rounded text-sm hover:bg-indigo-700">Upload Cover</button>
                                @if($site->cover_photo_url)
                                    <x-recrop-button
                                        :url="$site->cover_photo_url"
                                        :aspect="config('images.aspect_ratios.cover')"
                                        :post-url="route('client.restaurant.cover', $site)"
                                        field-name="cover_photo"
                                        label="Re-crop existing" />
                                @endif
                            </div>
                        </form>
                        @if($site->cover_photo_url)
                        <form action="{{ route('client.restaurant.cover.remove', $site) }}" method="POST" onsubmit="return confirm('Remove the cover photo? This can\'t be undone.')" class="mt-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 hover:underline">Remove cover photo</button>
                        </form>
                        @endif
                        <p class="mt-2 text-xs text-gray-500">Used as the background image on your site's hero section (top banner). 16:9 recommended.</p>
                        @error('cover_photo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Gallery Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center gap-2 mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Photo Gallery</h2>
                    <x-help-icon article="gallery-captions" tooltip="Adding captions to photos" />
                </div>
                <p class="text-sm text-gray-500 mb-4">Add up to 10 photos to showcase your restaurant. These will appear in the Gallery section of your site. Add an optional caption beneath each photo.</p>

                @php $gallery = $site->settings['gallery'] ?? []; @endphp

                <!-- Current Gallery Photos -->
                @if(count($gallery) > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    @foreach($gallery as $index => $photoEntry)
                    @php
                        $photoPath = is_array($photoEntry) ? ($photoEntry['path'] ?? '') : $photoEntry;
                        $photoCaption = is_array($photoEntry) ? ($photoEntry['caption'] ?? '') : '';
                        $photoUrl = $photoPath ? Storage::disk('public')->url($photoPath) : '';
                    @endphp
                    <div class="space-y-2">
                        <div class="relative group">
                            <img src="{{ $photoUrl }}" alt="Gallery photo {{ $index + 1 }}" class="w-full h-32 object-cover rounded-lg border">
                            <div class="absolute top-1 left-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <x-recrop-button
                                    :url="$photoUrl"
                                    :aspect="config('images.aspect_ratios.gallery')"
                                    :post-url="route('client.restaurant.gallery.replace', [$site, $index])"
                                    field-name="image"
                                    label="Re-crop"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 shadow" />
                            </div>
                            <form action="{{ route('client.restaurant.gallery.destroy', [$site, $index]) }}" method="POST" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 bg-red-600 text-white rounded-full hover:bg-red-700" onclick="return confirm('Remove this photo?')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        <div x-data="{ saved: false, error: false, original: @js($photoCaption) }">
                            <input
                                type="text"
                                maxlength="200"
                                placeholder="Add a caption (optional)"
                                value="{{ $photoCaption }}"
                                @blur="
                                    if ($event.target.value === original) return;
                                    saved = false; error = false;
                                    fetch('{{ route('client.restaurant.gallery.caption', [$site, $index]) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        },
                                        body: JSON.stringify({ caption: $event.target.value }),
                                    }).then(r => r.json()).then(data => {
                                        if (data.success) { saved = true; original = $event.target.value; setTimeout(() => saved = false, 1500); }
                                        else { error = true; }
                                    }).catch(() => { error = true; });
                                "
                                @keydown.enter.prevent="$event.target.blur()"
                                class="w-full px-2 py-1 text-xs border rounded focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                :class="{ 'border-green-400 bg-green-50': saved, 'border-red-400 bg-red-50': error, 'border-gray-300': !saved && !error }"
                            >
                            <p x-show="saved" x-cloak class="mt-1 text-[11px] text-green-700">Caption saved</p>
                            <p x-show="error" x-cloak class="mt-1 text-[11px] text-red-700">Save failed</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Upload Form -->
                @if(count($gallery) < 10)
                <form action="{{ route('client.restaurant.gallery.store', $site) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <x-image-cropper
                        name="photo"
                        :aspect-ratio="config('images.aspect_ratios.gallery')"
                        preview-class="w-48 h-36 object-cover rounded-lg border"
                        help-text="Max 5MB per photo. JPG, PNG, GIF, or WebP. Cropped to 4:3. {{ 10 - count($gallery) }} slots remaining." />
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Add Photo</button>
                </form>
                @else
                <p class="text-sm text-gray-500">Gallery is full (10/10 photos). Remove a photo to add more.</p>
                @endif

                @error('photo')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Site Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Site Information</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Plan</dt>
                        <dd class="font-medium text-gray-900">{{ $site->plan_label }}</dd>
                    </div>
                    @if($site->phone)
                    <div>
                        <dt class="text-gray-500">Phone</dt>
                        <dd class="font-medium text-gray-900">{{ $site->phone }}</dd>
                    </div>
                    @endif
                    @if($site->email)
                    <div>
                        <dt class="text-gray-500">Email</dt>
                        <dd class="font-medium text-gray-900">{{ $site->email }}</dd>
                    </div>
                    @endif
                    @if($site->address)
                    <div>
                        <dt class="text-gray-500">Address</dt>
                        <dd class="font-medium text-gray-900">{{ $site->address }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd class="font-medium text-gray-900">{{ $site->created_at->format('M j, Y') }}</dd>
                    </div>
                    @if($site->published_at)
                    <div>
                        <dt class="text-gray-500">Published</dt>
                        <dd class="font-medium text-gray-900">{{ $site->published_at->format('M j, Y') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Hours Quick Edit -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Hours</h2>
                    <a href="{{ route('client.restaurant.edit', $site) }}#hours" class="text-sm text-indigo-600 hover:text-indigo-800">Edit</a>
                </div>
                <dl class="space-y-1 text-sm">
                    @foreach($site->getHours() as $day => $hours)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ $day }}</dt>
                        <dd class="text-gray-900">{{ $hours }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>

            <!-- Colors -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Brand Colors</h2>
                    <a href="{{ route('client.restaurant.edit', $site) }}#colors" class="text-sm text-indigo-600 hover:text-indigo-800">Edit</a>
                </div>
                <div class="flex space-x-3">
                    @php $colors = $site->getColors(); @endphp
                    <div class="text-center">
                        <div class="w-10 h-10 rounded-lg border shadow-sm" style="background-color: {{ $colors['primary'] }}"></div>
                        <p class="text-xs text-gray-500 mt-1">Primary</p>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 rounded-lg border shadow-sm" style="background-color: {{ $colors['secondary'] }}"></div>
                        <p class="text-xs text-gray-500 mt-1">Secondary</p>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 rounded-lg border shadow-sm" style="background-color: {{ $colors['accent'] }}"></div>
                        <p class="text-xs text-gray-500 mt-1">Accent</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
