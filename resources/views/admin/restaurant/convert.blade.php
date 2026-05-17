@extends('layouts.app')

@section('title', 'Convert to Paying: ' . $site->business_name)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.restaurant.show', $site) }}" class="text-indigo-600 hover:text-indigo-800">
            &larr; Back to Site
        </a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Convert to Paying Customer</h1>
        <p class="text-gray-600 mt-1">{{ $site->business_name }}</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('admin.restaurant.convert.store', $site) }}" method="POST" class="space-y-6">
            @csrf

            <!-- Site Preview -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-medium text-gray-900">Site Details</h3>
                <div class="mt-2 text-sm text-gray-600">
                    <p><strong>Business:</strong> {{ $site->business_name }}</p>
                    <p><strong>URL:</strong> <a href="{{ $site->getPublicUrl() }}" target="_blank" class="text-indigo-600 hover:underline">{{ $site->getPublicUrl() }}</a></p>
                    @if($site->phone)
                    <p><strong>Phone:</strong> {{ $site->phone }}</p>
                    @endif
                </div>
            </div>

            <!-- Customer Info -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Customer Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="customer_name" id="customer_name"
                            value="{{ old('customer_name', $site->business_name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                    </div>
                    <div>
                        <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-1">
                            Customer Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="customer_email" id="customer_email"
                            value="{{ old('customer_email', $site->email) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                        <p class="mt-1 text-xs text-gray-500">If this email doesn't have an account, one will be created.</p>
                    </div>
                </div>
            </div>

            <!-- Plan Selection -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Plan</h3>
                <div class="space-y-3">
                    @foreach($plans as $plan)
                    <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:border-indigo-500 transition {{ old('plan_id') == $plan->id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                        <input type="radio" name="plan_id" value="{{ $plan->id }}"
                            class="mt-1 text-indigo-600 focus:ring-indigo-500"
                            {{ old('plan_id', $plans->first()->id) == $plan->id ? 'checked' : '' }}
                            data-monthly="{{ $plan->price_monthly }}"
                            data-annual="{{ $plan->price_annual }}"
                            data-setup="{{ $plan->setup_fee }}">
                        <div class="ml-3 flex-1">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-900">{{ $plan->name }}</span>
                                <span class="text-gray-600">
                                    ${{ number_format($plan->price_monthly, 2) }}/mo
                                    <span class="text-gray-400">|</span>
                                    ${{ number_format($plan->price_annual, 2) }}/yr
                                </span>
                            </div>
                            <p class="text-sm text-gray-500">{{ $plan->description }}</p>
                            <p class="text-sm text-gray-500">Setup fee: ${{ number_format($plan->setup_fee, 2) }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Billing Cycle -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing Cycle</h3>
                <div class="flex gap-4">
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-indigo-500 transition flex-1">
                        <input type="radio" name="billing_cycle" value="monthly"
                            class="text-indigo-600 focus:ring-indigo-500"
                            {{ old('billing_cycle', 'monthly') === 'monthly' ? 'checked' : '' }}>
                        <span class="ml-2 font-medium">Monthly</span>
                    </label>
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-indigo-500 transition flex-1">
                        <input type="radio" name="billing_cycle" value="annual"
                            class="text-indigo-600 focus:ring-indigo-500"
                            {{ old('billing_cycle') === 'annual' ? 'checked' : '' }}>
                        <span class="ml-2 font-medium">Annual</span>
                        <span class="ml-2 text-sm text-green-600">(Save ~15%)</span>
                    </label>
                </div>
            </div>

            <!-- Options -->
            <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Options</h3>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="waive_setup_fee" value="1"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('waive_setup_fee') ? 'checked' : '' }}>
                        <span class="ml-2 text-gray-700">Waive setup fee</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="send_welcome_email" value="1"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            {{ old('send_welcome_email', true) ? 'checked' : '' }} checked>
                        <span class="ml-2 text-gray-700">Send welcome email with login instructions</span>
                    </label>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-orange-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Invoice Summary</h3>
                <div id="invoice-summary" class="text-sm text-gray-700">
                    <!-- Populated by JavaScript -->
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.restaurant.show', $site) }}"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Convert & Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateSummary() {
    const selectedPlan = document.querySelector('input[name="plan_id"]:checked');
    const billingCycle = document.querySelector('input[name="billing_cycle"]:checked').value;
    const waiveSetup = document.querySelector('input[name="waive_setup_fee"]').checked;

    if (!selectedPlan) return;

    const monthly = parseFloat(selectedPlan.dataset.monthly);
    const annual = parseFloat(selectedPlan.dataset.annual);
    const setup = parseFloat(selectedPlan.dataset.setup);

    const recurring = billingCycle === 'annual' ? annual : monthly;
    const setupFee = waiveSetup ? 0 : setup;
    const total = recurring + setupFee;

    const cycleLabel = billingCycle === 'annual' ? 'Annual' : 'Monthly';

    let html = '';
    if (setupFee > 0) {
        html += `<div class="flex justify-between"><span>Setup Fee</span><span>$${setupFee.toFixed(2)}</span></div>`;
    }
    html += `<div class="flex justify-between"><span>${cycleLabel} Subscription</span><span>$${recurring.toFixed(2)}</span></div>`;
    html += `<div class="flex justify-between font-semibold border-t mt-2 pt-2"><span>Total</span><span>$${total.toFixed(2)} CAD</span></div>`;

    document.getElementById('invoice-summary').innerHTML = html;
}

document.querySelectorAll('input[name="plan_id"], input[name="billing_cycle"], input[name="waive_setup_fee"]').forEach(el => {
    el.addEventListener('change', updateSummary);
});

updateSummary();
</script>
@endsection
