@extends('layouts.app')

@section('title', 'Checkout - Restaurant Website')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="mb-8">
        <a href="{{ route('client.restaurant.order.plans') }}" class="text-indigo-600 hover:text-indigo-800">
            &larr; Back to Plans
        </a>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
        <!-- Order Summary -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b">
                    <h1 class="text-xl font-bold text-gray-900">Order Summary</h1>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Site Details -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Your Restaurant Website</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-semibold text-gray-900">{{ $orderDetails['business_name'] }}</span>
                                <span class="text-sm text-gray-500">{{ $plan->name }} Plan</span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <p>URL: <a href="https://sos-tech.ca/s/{{ $orderDetails['slug'] }}" target="_blank" class="text-indigo-600 hover:underline">sos-tech.ca/s/{{ $orderDetails['slug'] }}</a></p>
                                @if(!empty($orderDetails['domain_name']))
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        + Custom Domain
                                    </span>
                                    <span class="ml-1 font-medium">{{ $orderDetails['domain_name'] }}</span>
                                </p>
                                @endif
                                @if($orderDetails['phone'])
                                <p>Phone: {{ $orderDetails['phone'] }}</p>
                                @endif
                                @if($orderDetails['address'])
                                <p>Address: {{ $orderDetails['address'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Plan Features -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Plan Features</h3>
                        <ul class="grid grid-cols-2 gap-2">
                            @foreach($plan->features ?? [] as $feature)
                            <li class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Billing -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Billing Details</h3>
                        <div class="border rounded-lg divide-y">
                            @if($orderDetails['setup_fee'] > 0)
                            <div class="flex justify-between p-3">
                                <span class="text-gray-600">{{ $plan->name }} - Setup Fee</span>
                                <span class="font-medium">${{ number_format($orderDetails['setup_fee'], 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between p-3">
                                <span class="text-gray-600">
                                    {{ $plan->name }} - {{ $orderDetails['billing_cycle'] === 'annual' ? 'Annual' : 'Monthly' }} Subscription
                                </span>
                                <span class="font-medium">${{ number_format($orderDetails['recurring_price'], 2) }}</span>
                            </div>
                            @if(!empty($orderDetails['domain_name']) && $orderDetails['domain_price'] > 0)
                            <div class="flex justify-between p-3">
                                <span class="text-gray-600">
                                    Domain: {{ $orderDetails['domain_name'] }} ({{ $orderDetails['domain_years'] }} {{ $orderDetails['domain_years'] == 1 ? 'year' : 'years' }})
                                </span>
                                <span class="font-medium">${{ number_format($orderDetails['domain_price'] * $orderDetails['domain_years'], 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between p-3 bg-gray-50 font-semibold">
                                <span>Total Due Today</span>
                                <span class="text-lg">${{ number_format($orderDetails['total'], 2) }} CAD</span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            @if($orderDetails['billing_cycle'] === 'annual')
                                Your subscription will renew annually at ${{ number_format($orderDetails['recurring_price'], 2) }}/year.
                            @else
                                Your subscription will renew monthly at ${{ number_format($orderDetails['recurring_price'], 2) }}/month.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden sticky top-4">
                <div class="bg-orange-500 text-white px-6 py-4">
                    <h2 class="text-lg font-bold">Complete Order</h2>
                </div>

                <div class="p-6">
                    <div class="mb-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-900">${{ number_format($orderDetails['total'], 2) }}</div>
                            <div class="text-sm text-gray-500">CAD, due today</div>
                        </div>
                    </div>

                    <form action="{{ route('client.restaurant.order.process') }}" method="POST">
                        @csrf

                        <!-- Account Info -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <div class="text-sm text-gray-500 mb-1">Billing to:</div>
                            <div class="font-medium">{{ $client->name }}</div>
                            <div class="text-sm text-gray-600">{{ $client->email }}</div>
                        </div>

                        @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                            {{ session('error') }}
                        </div>
                        @endif

                        <button type="submit" class="w-full bg-orange-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-orange-600 transition">
                            Place Order & Pay
                        </button>

                        <p class="mt-4 text-xs text-gray-500 text-center">
                            By placing this order, you agree to our
                            <a href="/terms" class="text-indigo-600 hover:underline">Terms of Service</a>.
                        </p>
                    </form>

                    <div class="mt-6 pt-6 border-t">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">What happens next?</h4>
                        <ol class="text-sm text-gray-600 space-y-2">
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-medium mr-2">1</span>
                                <span>Complete payment</span>
                            </li>
                            @if(!empty($orderDetails['domain_name']))
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-medium mr-2">2</span>
                                <span>We'll register your domain</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-medium mr-2">3</span>
                                <span>We'll set up your site (1-3 days)</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-medium mr-2">4</span>
                                <span>Add your menu & go live!</span>
                            </li>
                            @else
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-medium mr-2">2</span>
                                <span>We'll set up your site (1-3 days)</span>
                            </li>
                            <li class="flex items-start">
                                <span class="flex-shrink-0 w-5 h-5 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xs font-medium mr-2">3</span>
                                <span>Add your menu & go live!</span>
                            </li>
                            @endif
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
