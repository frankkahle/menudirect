@extends('layouts.app')

@section('title', 'Restaurant Sites')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Restaurant Sites</h1>
        <a href="{{ route('admin.restaurant.create') }}" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
            + Create Demo Site
        </a>
    </div>

    @if(session('status'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
            {{ session('status') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500">Total Sites</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
            <div class="text-sm text-gray-500">Active</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['demo'] }}</div>
            <div class="text-sm text-gray-500">Demo</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-red-600">{{ $stats['suspended'] }}</div>
            <div class="text-sm text-gray-500">Suspended</div>
        </div>
    </div>

    <!-- Online Payments / Platform Fee Analytics -->
    <div class="bg-gradient-to-br from-emerald-50 to-indigo-50 border border-emerald-200 rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <h2 class="text-lg font-semibold text-gray-900">Online Payments — Platform Fees</h2>
            </div>
            <div class="text-sm text-gray-500">
                {{ $payments['connected_sites'] }} connected
                @if($payments['onboarding_sites'] > 0)
                    &middot; {{ $payments['onboarding_sites'] }} onboarding
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg p-4 border border-emerald-100">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Platform Fees (MTD)</div>
                <div class="text-2xl font-bold text-emerald-700">
                    ${{ number_format($payments['platform_fees_mtd'] / 100, 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $payments['paid_orders_mtd'] }} paid {{ Str::plural('order', $payments['paid_orders_mtd']) }}
                </div>
            </div>

            <div class="bg-white rounded-lg p-4 border border-emerald-100">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Gross Volume (MTD)</div>
                <div class="text-2xl font-bold text-indigo-700">
                    ${{ number_format($payments['gross_volume_mtd'], 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">processed by restaurants</div>
            </div>

            <div class="bg-white rounded-lg p-4 border border-emerald-100">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Platform Fees (All-time)</div>
                <div class="text-2xl font-bold text-emerald-700">
                    ${{ number_format($payments['platform_fees_all_time'] / 100, 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $payments['paid_orders_all_time'] }} paid {{ Str::plural('order', $payments['paid_orders_all_time']) }}
                </div>
            </div>

            <div class="bg-white rounded-lg p-4 border border-emerald-100">
                <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Gross Volume (All-time)</div>
                <div class="text-2xl font-bold text-indigo-700">
                    ${{ number_format($payments['gross_volume_all_time'], 2) }}
                </div>
                <div class="text-xs text-gray-500 mt-1">processed by restaurants</div>
            </div>
        </div>

        @if($topEarners->count() > 0)
        <div class="mt-5">
            <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Top Earners (Last 90 Days)</div>
            <div class="bg-white rounded-lg border border-emerald-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-2">Restaurant</th>
                            <th class="text-right px-4 py-2">Orders</th>
                            <th class="text-right px-4 py-2">Gross Volume</th>
                            <th class="text-right px-4 py-2">Platform Fees</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($topEarners as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                @if($row->restaurantSite)
                                <a href="{{ route('admin.restaurant.show', $row->restaurantSite) }}" class="text-indigo-600 hover:underline font-medium">
                                    {{ $row->restaurantSite->business_name }}
                                </a>
                                @else
                                <span class="text-gray-400">Unknown site #{{ $row->restaurant_site_id }}</span>
                                @endif
                            </td>
                            <td class="text-right px-4 py-2 text-gray-700">{{ $row->order_count }}</td>
                            <td class="text-right px-4 py-2 text-gray-700">${{ number_format($row->gross_volume, 2) }}</td>
                            <td class="text-right px-4 py-2 font-semibold text-emerald-700">${{ number_format($row->total_fees_cents / 100, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="mt-5 text-sm text-gray-500 text-center py-3 border-t border-emerald-100">
            No paid orders in the last 90 days yet.
        </div>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Business name, slug, or domain...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="demo" {{ request('status') === 'demo' ? 'selected' : '' }}>Demo</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'client_id']))
                    <a href="{{ route('admin.restaurant.index') }}" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Sites Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($sites as $site)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-8 h-8 bg-gray-100 text-gray-600 text-xs font-mono rounded">#{{ $site->id }}</span>
                            <div class="font-medium text-gray-900">{{ $site->business_name }}</div>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            <a href="{{ $site->getPublicUrl() }}" target="_blank" class="text-indigo-600 hover:underline">
                                {{ $site->slug }}
                            </a>
                            @if($site->custom_domain)
                                <span class="mx-1">|</span>
                                <span class="text-green-600">{{ $site->custom_domain }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($site->client)
                            <a href="https://portal.sos-tech.ca/admin/clients/{{ $site->client_id }}" class="text-indigo-600 hover:underline">
                                {{ $site->client->name }}
                            </a>
                            <div class="text-sm text-gray-500">{{ $site->client->email }}</div>
                        @else
                            <span class="text-gray-400">No client</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                            {{ $site->plan_label }}
                        </span>
                        @if($site->restaurantPlan)
                            <div class="text-xs text-gray-500 mt-1">{{ $site->restaurantPlan->name }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $site->status_badge_class }}">
                            {{ $site->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $site->created_at->format('M j, Y') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                        <a href="{{ $site->getPublicUrl() }}" target="_blank" class="text-gray-600 hover:text-gray-900" title="View Site">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                        <a href="{{ route('admin.restaurant.show', $site) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                        <a href="{{ route('admin.restaurant.edit', $site) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        @if($site->status === 'demo')
                            <a href="https://portal.sos-tech.ca/admin/restaurant/{{ $site->id }}/convert" class="text-orange-600 hover:text-orange-900 font-semibold">Convert</a>
                        @endif
                        @if($site->status !== 'active')
                            <form action="{{ route('admin.restaurant.toggle-status', $site) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                            </form>
                        @endif
                        @if($site->status !== 'suspended')
                            <form action="{{ route('admin.restaurant.toggle-status', $site) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="text-red-600 hover:text-red-900">Suspend</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        No restaurant sites found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sites->hasPages())
    <div class="mt-4">
        {{ $sites->links() }}
    </div>
    @endif
</div>
@endsection
