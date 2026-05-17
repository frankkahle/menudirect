@php $staff = auth('staff')->user(); @endphp
@extends('staff.layout')
@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('staff.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to orders</a>
</div>

<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $order->order_number }}</h1>
        <p class="text-gray-600 mt-1">{{ $order->created_at->format('M j, Y g:i A') }}</p>
    </div>
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
    <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-semibold {{ $statusColors[$order->status] ?? '' }}">
        {{ ucfirst($order->status) }}
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Items</h2>
        <table class="w-full">
            <tbody class="divide-y divide-gray-200">
                @foreach($order->items as $item)
                <tr>
                    <td class="py-3">
                        <div class="font-medium text-gray-900">{{ $item->quantity }}× {{ $item->name }}</div>
                        @if($item->special_instructions)
                        <div class="text-xs text-amber-700 italic mt-1">Note: {{ $item->special_instructions }}</div>
                        @endif
                    </td>
                    <td class="py-3 text-right text-sm text-gray-900">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-gray-200">
                <tr><td class="py-2 text-sm text-gray-600">Subtotal</td><td class="py-2 text-right">${{ number_format($order->subtotal, 2) }}</td></tr>
                @if($order->tax > 0)
                <tr><td class="py-2 text-sm text-gray-600">Tax</td><td class="py-2 text-right">${{ number_format($order->tax, 2) }}</td></tr>
                @endif
                @if($order->delivery_fee > 0)
                <tr><td class="py-2 text-sm text-gray-600">Delivery</td><td class="py-2 text-right">${{ number_format($order->delivery_fee, 2) }}</td></tr>
                @endif
                <tr class="border-t border-gray-200"><td class="py-2 font-bold">Total</td><td class="py-2 text-right font-bold">${{ number_format($order->total, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer</h2>
            <div class="space-y-2 text-sm">
                <div><strong>Name:</strong> {{ $order->customer_name }}</div>
                <div><strong>Phone:</strong> <a href="tel:{{ $order->customer_phone }}" class="text-indigo-600">{{ $order->customer_phone }}</a></div>
                @if($order->customer_email)
                <div><strong>Email:</strong> {{ $order->customer_email }}</div>
                @endif
                <div><strong>Type:</strong> {{ ucfirst($order->order_type) }}</div>
                @if($order->delivery_address)
                <div><strong>Delivery:</strong> {{ $order->delivery_address }}</div>
                @endif
                @if($order->scheduled_for)
                <div><strong>Scheduled:</strong> {{ \Carbon\Carbon::parse($order->scheduled_for)->format('M j g:i A') }}</div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
            <div class="space-y-2">
                @if($order->status === 'pending')
                <form method="POST" action="{{ route('staff.orders.confirm', $order) }}">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Confirm Order</button>
                </form>
                @endif
                @if($order->status === 'confirmed')
                <form method="POST" action="{{ route('staff.orders.preparing', $order) }}">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">Start Preparing</button>
                </form>
                @endif
                @if($order->status === 'preparing')
                <form method="POST" action="{{ route('staff.orders.ready', $order) }}">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">Mark Ready</button>
                </form>
                @endif
                @if($order->status === 'ready')
                <form method="POST" action="{{ route('staff.orders.complete', $order) }}">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium">Mark Completed</button>
                </form>
                @endif
                @if(in_array($order->status, ['pending', 'confirmed', 'preparing']))
                <form method="POST" action="{{ route('staff.orders.cancel', $order) }}" x-data="{ showCancel: false }">
                    @csrf
                    <button type="button" @click="showCancel = !showCancel" class="w-full py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 font-medium">Cancel Order</button>
                    <div x-show="showCancel" x-cloak class="mt-2">
                        <input type="text" name="reason" placeholder="Reason (required)" required
                               class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                        <button type="submit" class="w-full mt-2 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Confirm Cancel</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
