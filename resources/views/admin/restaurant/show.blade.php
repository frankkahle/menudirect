@extends('layouts.app')

@section('title', $site->business_name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.restaurant.index') }}" class="text-indigo-600 hover:text-indigo-800">
            &larr; Back to Restaurant Sites
        </a>
    </div>

    @if(session('status'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="col-span-2 space-y-6">
            <!-- Site Overview -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">{{ $site->business_name }}</h2>
                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{ $site->status_badge_class }}">
                        {{ $site->status_label }}
                    </span>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Slug</dt>
                            <dd class="mt-1">
                                <a href="{{ $site->getPublicUrl() }}" target="_blank" class="text-indigo-600 hover:underline">
                                    {{ $site->slug }}
                                </a>
                            </dd>
                        </div>
                        @if($site->custom_domain)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Custom Domain</dt>
                            <dd class="mt-1 text-green-600">{{ $site->custom_domain }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Plan Type</dt>
                            <dd class="mt-1">{{ $site->plan_label }}</dd>
                        </div>
                        @if($site->restaurantPlan)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Subscription Plan</dt>
                            <dd class="mt-1">{{ $site->restaurantPlan->name }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone</dt>
                            <dd class="mt-1">{{ $site->phone ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1">{{ $site->email ?? 'Not set' }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Address</dt>
                            <dd class="mt-1">{{ $site->address ?? 'Not set' }}</dd>
                        </div>
                        @if($site->tagline)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Tagline</dt>
                            <dd class="mt-1">{{ $site->tagline }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- SEO & AEO -->
            @if($site->seo_title || $site->seo_description || $site->cuisine_type || $site->google_place_id)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">SEO & AI Discovery</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4">
                        @if($site->seo_title)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">SEO Title</dt>
                            <dd class="mt-1">{{ $site->seo_title }}</dd>
                        </div>
                        @endif
                        @if($site->seo_description)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">SEO Description</dt>
                            <dd class="mt-1 text-sm text-gray-700">{{ $site->seo_description }}</dd>
                        </div>
                        @endif
                        @if($site->cuisine_type)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Cuisine</dt>
                            <dd class="mt-1">{{ $site->cuisine_type }}</dd>
                        </div>
                        @endif
                        @if($site->price_range)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Price Range</dt>
                            <dd class="mt-1">{{ $site->price_range }}</dd>
                        </div>
                        @endif
                        @if($site->google_place_id)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Google Place ID</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-600">{{ $site->google_place_id }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
            @endif

            <!-- Menu Categories -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">Menu ({{ $site->categories->count() }} categories)</h2>
                </div>
                <div class="p-6">
                    @forelse($site->categories as $category)
                    <div class="mb-4 last:mb-0">
                        <h3 class="font-medium text-gray-900">{{ $category->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $category->items->count() }} items</p>
                        @if($category->items->count() > 0)
                        <div class="mt-2 pl-4 border-l-2 border-gray-200">
                            @foreach($category->items->take(5) as $item)
                            <div class="text-sm text-gray-600">
                                {{ $item->name }} - ${{ number_format($item->price, 2) }}
                                @if($item->featured)
                                    <span class="text-yellow-600 text-xs">(Featured)</span>
                                @endif
                            </div>
                            @endforeach
                            @if($category->items->count() > 5)
                            <div class="text-sm text-gray-400">+{{ $category->items->count() - 5 }} more items</div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @empty
                    <p class="text-gray-500">No menu categories yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Announcements -->
            @if($site->announcements->count() > 0)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">Announcements</h2>
                </div>
                <div class="p-6">
                    @foreach($site->announcements as $announcement)
                    <div class="mb-3 last:mb-0 p-3 rounded-lg bg-{{ $announcement->type === 'alert' ? 'red' : ($announcement->type === 'special' ? 'green' : 'blue') }}-50">
                        <span class="text-xs uppercase font-medium text-gray-500">{{ $announcement->type }}</span>
                        <p class="text-gray-800">{{ $announcement->message }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Actions</h3>
                <div class="space-y-2">
                    <a href="{{ $site->getPublicUrl() }}" target="_blank"
                        class="block w-full px-4 py-2 bg-indigo-600 text-white text-center rounded-lg hover:bg-indigo-700">
                        View Live Site
                    </a>
                    <a href="{{ route('admin.restaurant.edit', $site) }}"
                        class="block w-full px-4 py-2 bg-gray-200 text-gray-700 text-center rounded-lg hover:bg-gray-300">
                        Edit Site
                    </a>
                    @if($site->status === 'demo')
                    <a href="https://portal.sos-tech.ca/admin/restaurant/{{ $site->id }}/convert"
                        class="block w-full px-4 py-2 bg-orange-600 text-white text-center rounded-lg hover:bg-orange-700">
                        Convert to Paying
                    </a>
                    @endif
                    @if($site->status !== 'active')
                    <form action="{{ route('admin.restaurant.toggle-status', $site) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Activate Site
                        </button>
                    </form>
                    @endif
                    @if($site->status !== 'suspended')
                    <form action="{{ route('admin.restaurant.toggle-status', $site) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="suspended">
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Suspend Site
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Online Payments / Platform Fees -->
            @if($site->stripe_account_id || $sitePayments['paid_orders'] > 0)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-emerald-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Online Payments</h3>
                    @if($site->stripe_charges_enabled && $site->online_payments_enabled)
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full">Active</span>
                    @elseif($site->stripe_account_id)
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Onboarding</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Disabled</span>
                    @endif
                </div>

                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Platform Fees (MTD)</dt>
                        <dd class="font-semibold text-emerald-700">
                            ${{ number_format($sitePayments['platform_fees_mtd_cents'] / 100, 2) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Platform Fees (Total)</dt>
                        <dd class="font-semibold text-emerald-700">
                            ${{ number_format($sitePayments['platform_fees_cents'] / 100, 2) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Gross Volume (Total)</dt>
                        <dd class="font-medium text-gray-900">
                            ${{ number_format($sitePayments['gross_volume'], 2) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Paid Orders</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $sitePayments['paid_orders'] }}
                            <span class="text-xs text-gray-500">({{ $sitePayments['paid_orders_mtd'] }} MTD)</span>
                        </dd>
                    </div>
                    @if($sitePayments['last_paid_at'])
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Last Payment</dt>
                        <dd class="text-sm text-gray-700">
                            {{ \Carbon\Carbon::parse($sitePayments['last_paid_at'])->diffForHumans() }}
                        </dd>
                    </div>
                    @endif
                    @if($site->stripe_account_id)
                    <div class="pt-2 border-t">
                        <dt class="text-xs text-gray-500 mb-1">Stripe Account</dt>
                        <dd class="text-xs font-mono text-gray-700 break-all">{{ $site->stripe_account_id }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif

            <!-- Client Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Client</h3>
                @if($site->client)
                <div>
                    <a href="https://portal.sos-tech.ca/admin/clients/{{ $site->client_id }}" class="text-indigo-600 hover:underline font-medium">
                        {{ $site->client->name }}
                    </a>
                    <p class="text-sm text-gray-500">{{ $site->client->email }}</p>
                </div>
                @else
                <p class="text-gray-500">No client assigned</p>
                @endif
            </div>

            <!-- Service Info -->
            @if($site->service)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Service</h3>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd class="font-medium">{{ ucfirst($site->service->status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Billing</dt>
                        <dd class="font-medium">${{ number_format($site->service->amount, 2) }}/{{ $site->service->billing_cycle }}</dd>
                    </div>
                    @if($site->service->next_bill_date)
                    <div>
                        <dt class="text-sm text-gray-500">Next Bill</dt>
                        <dd class="font-medium">{{ $site->service->next_bill_date->format('M j, Y') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
            @endif

            <!-- Timestamps -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Timeline</h3>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd>{{ $site->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Updated</dt>
                        <dd>{{ $site->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    @if($site->published_at)
                    <div>
                        <dt class="text-gray-500">Published</dt>
                        <dd>{{ $site->published_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
