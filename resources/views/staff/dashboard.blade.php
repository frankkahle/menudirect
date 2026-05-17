@extends('staff.layout')
@section('title', 'Dashboard')

@section('content')
<h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard</h1>
<p class="text-gray-600 mb-8">Welcome back, {{ $staff->name }}!</p>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <a href="{{ route('staff.orders.index', ['status' => 'pending']) }}"
       class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition border-l-4 border-amber-500">
        <div class="text-sm text-gray-500 font-medium uppercase tracking-wide">Pending Orders</div>
        <div class="text-4xl font-bold text-gray-900 mt-2">{{ $pendingCount }}</div>
        <div class="text-xs text-amber-600 mt-2">⚠ Need attention</div>
    </a>

    <a href="{{ route('staff.orders.index') }}"
       class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition border-l-4 border-blue-500">
        <div class="text-sm text-gray-500 font-medium uppercase tracking-wide">Active Orders</div>
        <div class="text-4xl font-bold text-gray-900 mt-2">{{ $activeCount }}</div>
        <div class="text-xs text-blue-600 mt-2">In progress</div>
    </a>

    <div class="bg-white rounded-xl shadow p-6 border-l-4 border-emerald-500">
        <div class="text-sm text-gray-500 font-medium uppercase tracking-wide">Completed Today</div>
        <div class="text-4xl font-bold text-gray-900 mt-2">{{ $todayCompleted }}</div>
        <div class="text-xs text-emerald-600 mt-2">✓ Done</div>
    </div>
</div>

<a href="{{ route('staff.orders.live') }}" class="block bg-gray-900 text-white rounded-xl shadow p-5 mb-8 hover:bg-gray-800 transition group">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-500/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <div class="font-semibold">Kitchen Display</div>
                <div class="text-sm text-gray-400">Full-screen live order view for tablets</div>
            </div>
        </div>
        <svg class="w-5 h-5 text-gray-500 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
    </div>
</a>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
        <a href="{{ route('staff.orders.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">View all →</a>
    </div>

    @if($recentOrders->isEmpty())
    <div class="p-8 text-center text-gray-500">
        No orders yet. New orders will appear here.
    </div>
    @else
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                <th class="px-6 py-3">Order</th>
                <th class="px-6 py-3">Customer</th>
                <th class="px-6 py-3">Items</th>
                <th class="px-6 py-3">Total</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Time</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($recentOrders as $order)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <a href="{{ route('staff.orders.show', $order) }}" class="text-indigo-600 font-medium hover:underline">
                        {{ $order->order_number }}
                    </a>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ $order->customer_name }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $order->items->count() }} item(s)</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">${{ number_format($order->total, 2) }}</td>
                <td class="px-6 py-4">
                    @php
                        $statusColors = [
                            'pending' => 'bg-amber-100 text-amber-800',
                            'confirmed' => 'bg-blue-100 text-blue-800',
                            'preparing' => 'bg-purple-100 text-purple-800',
                            'ready' => 'bg-emerald-100 text-emerald-800',
                            'completed' => 'bg-gray-100 text-gray-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $order->created_at->diffForHumans() }}</td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ route('staff.orders.show', $order) }}" class="text-sm text-indigo-600 hover:text-indigo-800">View →</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
