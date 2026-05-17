@extends('layouts.app')

@section('title', 'Order #' . $order->order_number . ' - ' . $site->business_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.orders.index', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Orders
        </a>

        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Order #{{ $order->order_number }}</h1>
                <p class="text-gray-600 mt-1">{{ $order->created_at->format('F j, Y g:i A') }}</p>
            </div>
            <span class="px-4 py-2 text-sm font-medium rounded-full {{ $order->status_badge_class }}">
                {{ $order->status_label }}
            </span>
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

    <!-- Quick Actions -->
    @if($order->isActive())
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            @if($order->isPending())
            <form action="{{ route('client.restaurant.orders.confirm', [$site, $order]) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                    Confirm Order
                </button>
            </form>
            @endif

            @if($order->isPending() || $order->isConfirmed())
            <form action="{{ route('client.restaurant.orders.preparing', [$site, $order]) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm font-medium hover:bg-purple-700">
                    Start Preparing
                </button>
            </form>
            @endif

            @if($order->isConfirmed() || $order->isPreparing())
            <form action="{{ route('client.restaurant.orders.ready', [$site, $order]) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                    Mark Ready
                </button>
            </form>
            @endif

            @if($order->isReady())
            <form action="{{ route('client.restaurant.orders.complete', [$site, $order]) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium hover:bg-gray-700">
                    Complete Order
                </button>
            </form>
            @endif

            @if(!$order->isCompleted() && !$order->isCancelled())
            <button type="button" onclick="document.getElementById('cancelModal').classList.remove('hidden')"
                    class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                Cancel Order
            </button>
            @endif

            <button type="button" onclick="window.print()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-50">
                Print Order
            </button>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Items -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h2>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                    <div class="flex justify-between items-start pb-4 border-b last:border-0 last:pb-0">
                        <div>
                            <p class="font-medium text-gray-900">{{ $item->quantity }}x {{ $item->name }}</p>
                            @if($item->special_requests)
                            <p class="text-sm text-amber-600 mt-1">Note: {{ $item->special_requests }}</p>
                            @endif
                        </div>
                        <p class="font-medium text-gray-900">{{ $item->formatted_total }}</p>
                    </div>
                    @endforeach
                </div>

                <div class="border-t mt-4 pt-4 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span>{{ $order->formatted_subtotal }}</span>
                    </div>
                    @if($order->tax_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Tax ({{ number_format($order->tax_rate * 100, 1) }}%)</span>
                        <span>{{ $order->formatted_tax }}</span>
                    </div>
                    @endif
                    @if($order->delivery_fee > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Delivery Fee</span>
                        <span>{{ $order->formatted_delivery_fee }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold pt-2 border-t">
                        <span>Total</span>
                        <span class="text-indigo-600">{{ $order->formatted_total }}</span>
                    </div>
                    <p class="text-sm text-gray-500 text-right">Pay at {{ $order->isPickup() ? 'Pickup' : 'Delivery' }}</p>
                </div>
            </div>

            <!-- Special Instructions -->
            @if($order->special_instructions)
            <div class="bg-amber-50 rounded-lg shadow p-6 border border-amber-200">
                <h2 class="text-lg font-semibold text-amber-900 mb-2">Special Instructions</h2>
                <p class="text-amber-800">{{ $order->special_instructions }}</p>
            </div>
            @endif

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Timeline</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">Order Placed</p>
                            <p class="text-sm text-gray-500">{{ $order->created_at->format('M j, g:i A') }}</p>
                        </div>
                    </div>

                    @if($order->confirmed_at)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">Confirmed</p>
                            <p class="text-sm text-gray-500">{{ $order->confirmed_at->format('M j, g:i A') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($order->ready_at)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">Ready for {{ $order->isPickup() ? 'Pickup' : 'Delivery' }}</p>
                            <p class="text-sm text-gray-500">{{ $order->ready_at->format('M j, g:i A') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($order->completed_at)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">Completed</p>
                            <p class="text-sm text-gray-500">{{ $order->completed_at->format('M j, g:i A') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($order->cancelled_at)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-red-900">Cancelled</p>
                            <p class="text-sm text-gray-500">{{ $order->cancelled_at->format('M j, g:i A') }}</p>
                            @if($order->cancellation_reason)
                            <p class="text-sm text-red-600 mt-1">{{ $order->cancellation_reason }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="font-medium">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <a href="tel:{{ $order->customer_phone }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                            {{ $order->customer_phone }}
                        </a>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <a href="mailto:{{ $order->customer_email }}" class="font-medium text-indigo-600 hover:text-indigo-800 break-all">
                            {{ $order->customer_email }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Order Type -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ $order->order_type_label }}</h2>
                @if($order->isDelivery() && $order->delivery_address)
                <div>
                    <p class="text-sm text-gray-500">Delivery Address</p>
                    <p class="font-medium">{{ $order->delivery_address }}</p>
                </div>
                @else
                <p class="text-gray-600">Customer will pick up at your location.</p>
                @endif

                @if($order->estimated_ready_at && $order->isActive())
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500">Estimated Ready</p>
                    <p class="font-medium {{ $order->estimated_ready_at->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $order->estimated_ready_at->format('g:i A') }}
                        @if($order->estimated_ready_at->isPast())
                        <span class="text-sm">(overdue)</span>
                        @endif
                    </p>
                </div>
                @endif
            </div>

            <!-- Notifications Log -->
            @if($order->notifications->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Notifications</h2>
                <div class="space-y-3 text-sm">
                    @foreach($order->notifications->take(5) as $notification)
                    <div class="flex items-start">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center {{ $notification->isSent() ? 'bg-green-100' : 'bg-red-100' }}">
                            @if($notification->isSent())
                            <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            @else
                            <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            @endif
                        </span>
                        <div class="ml-2">
                            <p class="text-gray-900">{{ ucfirst($notification->channel) }} to {{ Str::limit($notification->recipient, 25) }}</p>
                            <p class="text-xs text-gray-500">{{ $notification->created_at->format('M j, g:i A') }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancel Order</h3>
        <form action="{{ route('client.restaurant.orders.cancel', [$site, $order]) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for cancellation</label>
                <textarea name="reason" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="Optional - explain why the order is being cancelled"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Keep Order
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                    Cancel Order
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@media print {
    header, nav, .no-print, button, a { display: none !important; }
    body { background: white !important; }
    .shadow { box-shadow: none !important; }
}
</style>
@endsection
