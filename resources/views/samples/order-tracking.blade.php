<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ $order['order_number'] }} | {{ $order['restaurant']['name'] }}</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="min-h-screen py-8 md:py-12">
    <div class="max-w-2xl mx-auto px-4">
        {{-- Order Header --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Order {{ $order['order_number'] }}</h1>
                <p class="text-gray-600 mt-1">{{ $order['restaurant']['name'] }}</p>
            </div>

            {{-- Status Badge --}}
            <div class="flex justify-center mb-6">
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-blue-100 text-blue-800',
                        'preparing' => 'bg-purple-100 text-purple-800',
                        'ready' => 'bg-green-100 text-green-800',
                        'completed' => 'bg-gray-100 text-gray-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                    $statusColor = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-semibold {{ $statusColor }}">
                    @if($order['status'] === 'ready')
                        <svg class="w-5 h-5 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    {{ $order['status_label'] }}
                </span>
            </div>

            {{-- Status Timeline --}}
            <div class="relative">
                @php
                    $steps = [
                        ['key' => 'pending', 'label' => 'Order Placed', 'time' => $order['created_at']],
                        ['key' => 'confirmed', 'label' => 'Confirmed', 'time' => $order['confirmed_at'] ?? null],
                        ['key' => 'ready', 'label' => 'Ready', 'time' => $order['ready_at'] ?? null],
                        ['key' => 'completed', 'label' => 'Completed', 'time' => $order['completed_at'] ?? null],
                    ];
                    $statusOrder = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
                    $currentIndex = array_search($order['status'], $statusOrder);
                    if ($currentIndex === false) $currentIndex = -1;
                @endphp

                @if($order['status'] !== 'cancelled')
                <div class="flex justify-between mb-2">
                    @foreach($steps as $index => $step)
                        @php
                            $isActive = $currentIndex >= $index || ($step['key'] === 'confirmed' && $currentIndex >= 1);
                            $isCurrent = ($order['status'] === $step['key']) || ($order['status'] === 'preparing' && $step['key'] === 'confirmed');
                        @endphp
                        <div class="flex-1 text-center {{ $index < count($steps) - 1 ? 'relative' : '' }}">
                            <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center {{ $isActive ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                                @if($isActive)
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <p class="text-xs mt-1 {{ $isCurrent ? 'font-semibold text-gray-900' : 'text-gray-500' }}">{{ $step['label'] }}</p>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4">
                    <div class="w-12 h-12 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <p class="text-red-600 font-medium">Order Cancelled</p>
                    @if($order['cancellation_reason'])
                        <p class="text-sm text-gray-500 mt-1">{{ $order['cancellation_reason'] }}</p>
                    @endif
                </div>
                @endif
            </div>

            {{-- Estimated Ready Time --}}
            @if(!empty($order['estimated_ready_at']) && !in_array($order['status'], ['completed', 'cancelled', 'ready']))
                @php
                    $readyAt = \Carbon\Carbon::parse($order['estimated_ready_at']);
                    $isToday = $readyAt->isToday();
                    $isTomorrow = $readyAt->isTomorrow();
                @endphp
                <div class="mt-6 p-4 bg-blue-50 rounded-lg text-center">
                    <p class="text-sm text-blue-600">Estimated Ready Time</p>
                    <p class="text-xl font-bold text-blue-800">
                        @if($isToday)
                            {{ $readyAt->format('g:i A') }}
                        @elseif($isTomorrow)
                            Tomorrow at {{ $readyAt->format('g:i A') }}
                        @else
                            {{ $readyAt->format('l, M j') }} at {{ $readyAt->format('g:i A') }}
                        @endif
                    </p>
                </div>
            @endif

            @if($order['status'] === 'ready')
                <div class="mt-6 p-4 bg-green-50 rounded-lg text-center">
                    <p class="text-lg font-bold text-green-800">
                        @if($order['order_type'] === 'pickup')
                            Your order is ready for pickup!
                        @else
                            Your order is ready and out for delivery!
                        @endif
                    </p>
                </div>
            @endif
        </div>

        {{-- Order Details --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Order Details</h2>

            <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Order Type</span>
                    <span class="font-medium">{{ $order['order_type_label'] }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Customer</span>
                    <span class="font-medium">{{ $order['customer_name'] }}</span>
                </div>
                @if(!empty($order['delivery_address']))
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Delivery Address</span>
                    <span class="font-medium text-right max-w-[60%]">{{ $order['delivery_address'] }}</span>
                </div>
                @endif
            </div>

            <hr class="my-4">

            {{-- Items --}}
            <div class="space-y-3">
                @foreach($order['items'] as $item)
                <div class="flex justify-between">
                    <div>
                        <span class="font-medium">{{ $item['quantity'] }}x</span>
                        <span>{{ $item['name'] }}</span>
                        @if(!empty($item['special_requests']))
                            <p class="text-xs text-gray-500 ml-6">{{ $item['special_requests'] }}</p>
                        @endif
                    </div>
                    <span class="text-gray-700">{{ $item['total'] }}</span>
                </div>
                @endforeach
            </div>

            <hr class="my-4">

            {{-- Totals --}}
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>{{ $order['subtotal'] }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Tax</span>
                    <span>{{ $order['tax_amount'] }}</span>
                </div>
                @if(!empty($order['delivery_fee']))
                <div class="flex justify-between text-sm">
                    <span>Delivery Fee</span>
                    <span>{{ $order['delivery_fee'] }}</span>
                </div>
                @endif
                <div class="flex justify-between text-lg font-bold pt-2 border-t">
                    <span>Total</span>
                    <span>{{ $order['total'] }}</span>
                </div>
            </div>

            @if(!empty($order['special_instructions']))
            <div class="mt-4 p-3 bg-gray-50 rounded">
                <p class="text-sm text-gray-600"><strong>Special Instructions:</strong> {{ $order['special_instructions'] }}</p>
            </div>
            @endif
        </div>

        {{-- Restaurant Contact --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Restaurant Contact</h2>
            <p class="font-medium">{{ $order['restaurant']['name'] }}</p>
            @if(!empty($order['restaurant']['address']))
                <p class="text-gray-600 text-sm">{{ $order['restaurant']['address'] }}</p>
            @endif
            @if(!empty($order['restaurant']['phone']))
                <a href="tel:{{ $order['restaurant']['phone'] }}" class="inline-flex items-center mt-3 text-blue-600 hover:text-blue-800">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    {{ $order['restaurant']['phone'] }}
                </a>
            @endif
        </div>

        {{-- Footer --}}
        <div class="text-center text-sm text-gray-500">
            <p>Powered by <a href="https://sos-tech.ca" class="text-blue-600 hover:underline">SOS Tech</a></p>
        </div>

        {{-- Auto-refresh for active orders --}}
        @if(!in_array($order['status'], ['completed', 'cancelled']))
        <p class="text-center text-xs text-gray-400 mt-4">
            This page refreshes automatically every 30 seconds.
        </p>
        <script>
            setTimeout(function() {
                window.location.reload();
            }, 30000);
        </script>
        @endif
    </div>
</div>
</body>
</html>
