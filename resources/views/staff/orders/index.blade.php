@extends('staff.layout')
@section('title', 'Orders')

@section('content')
<div class="flex items-center justify-between mb-6" x-data="{ seconds: 10 }" x-init="setInterval(() => { seconds--; if (seconds <= 0) { window.location.reload(); } }, 1000)">
    <h1 class="text-3xl font-bold text-gray-900">Orders</h1>
    <div class="flex items-center gap-4">
        <span class="text-xs text-gray-400">Refreshing in <span x-text="seconds" class="font-mono"></span>s</span>
        <a href="{{ route('staff.orders.live') }}" class="text-sm bg-gray-900 text-amber-400 px-3 py-1.5 rounded-lg hover:bg-gray-800 transition">Kitchen Display</a>
        <a href="{{ route('staff.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">← Dashboard</a>
    </div>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    <a href="{{ route('staff.orders.index') }}"
       class="px-3 py-1.5 rounded-full text-sm font-medium {{ !request('status') ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700' }}">All</a>
    @foreach(['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'] as $status)
    <a href="{{ route('staff.orders.index', ['status' => $status]) }}"
       class="px-3 py-1.5 rounded-full text-sm font-medium {{ request('status') === $status ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700' }}">{{ ucfirst($status) }}</a>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow overflow-hidden">
    @if($orders->isEmpty())
    <div class="p-8 text-center text-gray-500">No orders found.</div>
    @else
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                <th class="px-6 py-3">Order</th>
                <th class="px-6 py-3">Customer</th>
                <th class="px-6 py-3">Type</th>
                <th class="px-6 py-3">Total</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Time</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($orders as $order)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <a href="{{ route('staff.orders.show', $order) }}" class="text-indigo-600 font-medium hover:underline">{{ $order->order_number }}</a>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">{{ $order->customer_name }}</div>
                    <div class="text-xs text-gray-500">{{ $order->customer_phone }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($order->order_type) }}</td>
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
    <div class="px-6 py-3 border-t border-gray-200">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
