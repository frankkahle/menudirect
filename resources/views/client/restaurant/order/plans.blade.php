@extends('layouts.app')

@section('title', 'Restaurant Website Plans')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Choose Your Restaurant Website Plan</h1>
        <p class="text-gray-600">Professional restaurant websites with mobile-friendly menus. All prices in CAD.</p>
    </div>

    <!-- Billing Toggle -->
    <div class="flex justify-center mb-8">
        <div class="bg-gray-100 p-1 rounded-lg inline-flex" id="billingToggle">
            <button type="button" onclick="setBilling('monthly')" id="monthlyBtn" class="px-6 py-2 rounded-md bg-white shadow text-gray-900 font-medium">Monthly</button>
            <button type="button" onclick="setBilling('annual')" id="annualBtn" class="px-6 py-2 rounded-md text-gray-600">Annual <span class="text-green-600 text-sm">(Save 17%)</span></button>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($plans as $plan)
        <div class="bg-white rounded-2xl shadow-lg p-8 border-2 {{ $plan->is_featured ? 'border-indigo-500' : 'border-gray-200' }} relative">
            @if($plan->is_featured)
            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                <span class="bg-indigo-500 text-white px-4 py-1 rounded-full text-sm font-medium">Best Value</span>
            </div>
            @endif

            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>
            <p class="text-gray-500 mb-4">{{ $plan->description }}</p>

            <div class="mb-2">
                <span class="text-4xl font-bold text-gray-900 monthly-price">${{ number_format($plan->price_monthly, 0) }}</span>
                <span class="text-4xl font-bold text-gray-900 annual-price hidden">${{ number_format($plan->price_annual, 0) }}</span>
                <span class="text-gray-500 monthly-label">/month</span>
                <span class="text-gray-500 annual-label hidden">/year</span>
            </div>
            <div class="text-sm text-gray-500 mb-6">+ ${{ number_format($plan->setup_fee, 0) }} one-time setup</div>

            <ul class="space-y-3 mb-8">
                @foreach($plan->features ?? [] as $feature)
                <li class="flex items-start">
                    @if(str_starts_with($feature, 'Everything in'))
                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="text-gray-600">{{ $feature }}</span>
                    @elseif($plan->slug === 'sitefresh-pro' && in_array($feature, ['Online ordering integration', 'Reservation widget', 'Priority support']))
                    <svg class="w-5 h-5 text-purple-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="text-gray-600"><strong>{{ $feature }}</strong></span>
                    @elseif($plan->slug === 'menudirect-max' && in_array($feature, ['Delivery zone management', 'Built-in reservation system', 'Distance-based fees', 'Delivery time estimates']))
                    <svg class="w-5 h-5 text-indigo-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="text-gray-600"><strong>{{ $feature }}</strong></span>
                    @else
                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="text-gray-600">{{ $feature }}</span>
                    @endif
                </li>
                @endforeach
            </ul>

            <form action="{{ route('client.restaurant.order.configure', $plan) }}" method="GET">
                <input type="hidden" name="billing" id="billing_{{ $plan->slug }}" value="monthly">
                <button type="submit" class="block w-full text-center {{ $plan->is_featured ? 'bg-indigo-500 text-white hover:bg-indigo-600' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }} py-3 rounded-lg font-medium transition">
                    Select Plan
                </button>
            </form>
        </div>
        @endforeach
    </div>

    <div class="mt-12 text-center">
        <p class="text-gray-500 text-sm">
            All plans include hosting, SSL, and Canadian support. Domain registration sold separately.
        </p>
    </div>
</div>

<script>
let currentBilling = 'monthly';

function setBilling(type) {
    currentBilling = type;
    const monthlyBtn = document.getElementById('monthlyBtn');
    const annualBtn = document.getElementById('annualBtn');

    document.querySelectorAll('[id^="billing_"]').forEach(el => el.value = type);

    if (type === 'monthly') {
        monthlyBtn.classList.add('bg-white', 'shadow', 'text-gray-900');
        monthlyBtn.classList.remove('text-gray-600');
        annualBtn.classList.remove('bg-white', 'shadow', 'text-gray-900');
        annualBtn.classList.add('text-gray-600');
        document.querySelectorAll('.monthly-price, .monthly-label').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.annual-price, .annual-label').forEach(el => el.classList.add('hidden'));
    } else {
        annualBtn.classList.add('bg-white', 'shadow', 'text-gray-900');
        annualBtn.classList.remove('text-gray-600');
        monthlyBtn.classList.remove('bg-white', 'shadow', 'text-gray-900');
        monthlyBtn.classList.add('text-gray-600');
        document.querySelectorAll('.annual-price, .annual-label').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.monthly-price, .monthly-label').forEach(el => el.classList.add('hidden'));
    }
}

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('billing') === 'annual') {
    setBilling('annual');
}
</script>
@endsection
