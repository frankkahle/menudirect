@extends('layouts.app')

@section('title', 'Configure Your Restaurant Website')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4">
    <div class="mb-8">
        <a href="{{ route('client.restaurant.order.plans') }}" class="text-indigo-600 hover:text-indigo-800">
            &larr; Back to Plans
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Plan Summary Header -->
        <div class="bg-gradient-to-r from-orange-500 to-red-500 text-white p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">{{ $plan->name }} Plan</h1>
                    <p class="text-orange-100">{{ $plan->description }}</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">${{ number_format($plan->price_monthly, 0) }}<span class="text-lg">/mo</span></div>
                    <div class="text-orange-100 text-sm">+ ${{ number_format($plan->setup_fee, 0) }} setup</div>
                </div>
            </div>
        </div>

        <!-- Configuration Form -->
        <form action="{{ route('client.restaurant.order.checkout') }}" method="POST" class="p-6 space-y-6">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $plan->id }}">

            <!-- Business Name -->
            <div>
                <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="business_name" id="business_name" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                    placeholder="e.g. Joe's Pizza"
                    value="{{ old('business_name') }}">
                @error('business_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- URL Slug -->
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                    Website URL <span class="text-red-500">*</span>
                </label>
                <div class="flex rounded-md shadow-sm">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                        sos-tech.ca/s/
                    </span>
                    <input type="text" name="slug" id="slug" required
                        class="flex-1 min-w-0 block w-full rounded-none rounded-r-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                        placeholder="joes-pizza"
                        pattern="[a-z0-9\-]+"
                        title="Lowercase letters, numbers, and hyphens only"
                        value="{{ old('slug') }}">
                </div>
                <p class="mt-1 text-sm text-gray-500">Lowercase letters, numbers, and hyphens only</p>
                @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contact Info -->
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="phone"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                        placeholder="(506) 555-1234"
                        value="{{ old('phone') }}">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                        placeholder="info@restaurant.ca"
                        value="{{ old('email') }}">
                </div>
            </div>

            <!-- Address -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <input type="text" name="address" id="address"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                    placeholder="123 Main Street, Moncton, NB"
                    value="{{ old('address') }}">
            </div>

            <!-- Custom Domain -->
            <div class="border-t pt-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" name="want_domain" id="want_domain" value="1"
                        class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded"
                        {{ old('want_domain') ? 'checked' : '' }}>
                    <label for="want_domain" class="ml-2 block text-sm font-medium text-gray-700">
                        I want to register a custom domain name for my restaurant
                    </label>
                </div>

                <div id="domain-section" class="{{ old('want_domain') ? '' : 'hidden' }}">
                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-800">
                            <strong>Why get a custom domain?</strong> Instead of <code>sos-tech.ca/s/your-restaurant</code>,
                            your customers can visit <code>yourrestaurant.ca</code> directly. Professional and easy to remember!
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <input type="text" id="domain_search"
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            placeholder="e.g. joes-pizza.ca"
                            value="{{ old('domain_name') }}">
                        <button type="button" id="check_domain_btn"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                            Check
                        </button>
                    </div>

                    <div id="domain_results" class="mt-4 hidden">
                        <!-- Domain search results will appear here -->
                    </div>

                    <input type="hidden" name="domain_name" id="domain_name_input" value="{{ old('domain_name') }}">
                    <input type="hidden" name="domain_price" id="domain_price_input" value="{{ old('domain_price') }}">
                    <input type="hidden" name="domain_years" id="domain_years_input" value="{{ old('domain_years', 1) }}">
                </div>
            </div>

            <!-- Billing Cycle -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Billing Cycle</label>
                <div class="grid md:grid-cols-2 gap-4">
                    <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none billing-option" id="monthly-option">
                        <input type="radio" name="billing_cycle" value="monthly" class="sr-only" {{ old('billing_cycle', request('billing', 'monthly')) === 'monthly' ? 'checked' : '' }}>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">Monthly</span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">${{ number_format($plan->price_monthly, 2) }}/month</span>
                            </span>
                        </span>
                        <svg class="h-5 w-5 text-orange-600 check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </label>

                    <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none billing-option" id="annual-option">
                        <input type="radio" name="billing_cycle" value="annual" class="sr-only" {{ old('billing_cycle', request('billing')) === 'annual' ? 'checked' : '' }}>
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">
                                    Annual
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Save {{ $plan->annual_savings_percent }}%
                                    </span>
                                </span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">${{ number_format($plan->price_annual, 2) }}/year</span>
                            </span>
                        </span>
                        <svg class="h-5 w-5 text-orange-600 check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-4">
                <button type="submit" class="w-full bg-orange-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-orange-600 transition">
                    Continue to Checkout
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.billing-option').forEach(option => {
    const radio = option.querySelector('input[type="radio"]');
    const checkIcon = option.querySelector('.check-icon');

    function updateStyle() {
        document.querySelectorAll('.billing-option').forEach(opt => {
            const r = opt.querySelector('input[type="radio"]');
            const icon = opt.querySelector('.check-icon');
            if (r.checked) {
                opt.classList.add('border-orange-500', 'ring-2', 'ring-orange-500');
                opt.classList.remove('border-gray-300');
                icon.classList.remove('hidden');
            } else {
                opt.classList.remove('border-orange-500', 'ring-2', 'ring-orange-500');
                opt.classList.add('border-gray-300');
                icon.classList.add('hidden');
            }
        });
    }

    option.addEventListener('click', () => {
        radio.checked = true;
        updateStyle();
    });

    updateStyle();
});

// Auto-generate slug from business name
document.getElementById('business_name').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.dataset.userEdited) {
        slugField.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 50);
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.userEdited = 'true';
});

// Domain toggle
document.getElementById('want_domain').addEventListener('change', function() {
    document.getElementById('domain-section').classList.toggle('hidden', !this.checked);
    if (!this.checked) {
        document.getElementById('domain_name_input').value = '';
        document.getElementById('domain_price_input').value = '';
        document.getElementById('domain_results').classList.add('hidden');
    }
});

// Domain search
document.getElementById('check_domain_btn').addEventListener('click', async function() {
    const searchInput = document.getElementById('domain_search');
    const resultsDiv = document.getElementById('domain_results');
    const domain = searchInput.value.trim().toLowerCase();

    if (!domain) {
        alert('Please enter a domain name');
        return;
    }

    // Add .ca if no TLD provided
    let searchDomain = domain;
    if (!domain.includes('.')) {
        searchDomain = domain + '.ca';
    }

    this.disabled = true;
    this.textContent = 'Checking...';

    try {
        const response = await fetch(`/api/domain/check?domain=${encodeURIComponent(searchDomain)}`);
        const data = await response.json();

        resultsDiv.innerHTML = '';
        resultsDiv.classList.remove('hidden');

        if (data.results && data.results.length > 0) {
            data.results.forEach(result => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 border rounded-lg mb-2 ' +
                    (result.available ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200');

                const statusIcon = result.available
                    ? '<svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
                    : '<svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';

                let content = `
                    <div class="flex items-center gap-2">
                        ${statusIcon}
                        <span class="font-medium">${result.domain}</span>
                        ${result.premium ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">Premium</span>' : ''}
                    </div>
                `;

                if (result.available && result.pricing) {
                    content += `
                        <div class="flex items-center gap-3">
                            <span class="text-gray-600">$${result.pricing.retail.toFixed(2)} CAD/year</span>
                            <button type="button" class="select-domain-btn px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700"
                                data-domain="${result.domain}"
                                data-price="${result.pricing.retail}">
                                Select
                            </button>
                        </div>
                    `;
                } else if (!result.available) {
                    content += '<span class="text-red-600 text-sm">Not available</span>';
                }

                div.innerHTML = content;
                resultsDiv.appendChild(div);
            });

            // Add click handlers for select buttons
            resultsDiv.querySelectorAll('.select-domain-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const selectedDomain = this.dataset.domain;
                    const selectedPrice = this.dataset.price;

                    document.getElementById('domain_name_input').value = selectedDomain;
                    document.getElementById('domain_price_input').value = selectedPrice;
                    document.getElementById('domain_search').value = selectedDomain;

                    // Update UI to show selection
                    resultsDiv.querySelectorAll('.select-domain-btn').forEach(b => {
                        b.textContent = 'Select';
                        b.classList.remove('bg-orange-600');
                        b.classList.add('bg-green-600');
                    });
                    this.textContent = 'Selected';
                    this.classList.remove('bg-green-600');
                    this.classList.add('bg-orange-600');
                });
            });
        } else {
            resultsDiv.innerHTML = '<p class="text-gray-600">No results found. Try a different domain name.</p>';
        }
    } catch (error) {
        resultsDiv.innerHTML = '<p class="text-red-600">Error checking domain. Please try again.</p>';
        resultsDiv.classList.remove('hidden');
    }

    this.disabled = false;
    this.textContent = 'Check';
});
</script>
@endsection
