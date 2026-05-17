@extends('layouts.app')

@section('title', 'Orders - ' . $site->business_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.show', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $site->business_name }}
        </a>

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
                <p class="text-gray-600 mt-1">Manage incoming orders</p>
            </div>
            <div class="flex items-center space-x-3">
                @if(!$site->ordering_enabled)
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800">
                    Ordering Disabled
                </span>
                @else
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">
                    Accepting Orders
                </span>
                @endif
            </div>
        </div>
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

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('client.restaurant.orders.index', [$site, 'status' => 'pending']) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition {{ request('status') === 'pending' ? 'ring-2 ring-yellow-500' : '' }}">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-bold {{ $counts['pending'] > 0 ? 'text-yellow-600' : 'text-gray-900' }}">{{ $counts['pending'] }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('client.restaurant.orders.index', [$site, 'status' => 'confirmed']) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition {{ request('status') === 'confirmed' ? 'ring-2 ring-blue-500' : '' }}">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">In Progress</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $counts['confirmed'] }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('client.restaurant.orders.index', [$site, 'status' => 'ready']) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition {{ request('status') === 'ready' ? 'ring-2 ring-green-500' : '' }}">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Ready</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $counts['ready'] }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('client.restaurant.orders.index', $site) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition {{ !request('status') && !request('all') ? 'ring-2 ring-indigo-500' : '' }}">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $counts['today'] }}</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form class="flex flex-wrap items-center gap-4">
            <div>
                <label class="text-sm text-gray-600 block mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\FoodOrder::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Date</label>
                <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}"
                       class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
            </div>
            <div class="flex items-end">
                <a href="{{ route('client.restaurant.orders.index', [$site, 'all' => 1]) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800">View All Orders</a>
            </div>
        </form>
    </div>

    <!-- Orders List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($orders->isEmpty())
        <div class="p-8 text-center">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500">No orders found for the selected filters.</p>
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($orders as $order)
                <tr class="{{ $order->isPending() ? 'bg-yellow-50' : '' }} hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('client.restaurant.orders.show', [$site, $order]) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                            #{{ $order->order_number }}
                        </a>
                        <p class="text-xs text-gray-500">{{ $order->item_count }} items</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm font-medium text-gray-900">{{ $order->customer_name }}</p>
                        <p class="text-xs text-gray-500">{{ $order->customer_phone }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $order->isDelivery() ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            {{ $order->order_type_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $order->status_badge_class }}">
                            {{ $order->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $order->formatted_total }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $order->created_at->format('g:i A') }}
                        @if($order->estimated_ready_at && $order->isActive())
                        <p class="text-xs {{ $order->estimated_ready_at->isPast() ? 'text-red-600' : 'text-gray-400' }}">
                            Est: {{ $order->estimated_ready_at->format('g:i A') }}
                        </p>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        @if($order->isPending())
                        <form action="{{ route('client.restaurant.orders.confirm', [$site, $order]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900 mr-2">Confirm</button>
                        </form>
                        @elseif($order->isConfirmed() || $order->isPreparing())
                        <form action="{{ route('client.restaurant.orders.ready', [$site, $order]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900 mr-2">Ready</button>
                        </form>
                        @elseif($order->isReady())
                        <form action="{{ route('client.restaurant.orders.complete', [$site, $order]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900 mr-2">Complete</button>
                        </form>
                        @endif
                        <a href="{{ route('client.restaurant.orders.show', [$site, $order]) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
    <div class="mt-6">
        {{ $orders->withQueryString()->links() }}
    </div>
    @endif
</div>

@if($counts['pending'] > 0)
<script>
// Auto-refresh page every 30 seconds if there are pending orders
setTimeout(() => window.location.reload(), 30000);
</script>
@endif
@endsection
