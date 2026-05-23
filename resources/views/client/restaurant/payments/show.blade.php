@extends('layouts.app')

@section('title', 'Online Payments — ' . $site->business_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('client.restaurant.show', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $site->business_name }}
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Online Payments</h1>
        <p class="text-gray-600 mt-1">Accept credit card payments directly on your restaurant website</p>
    </div>

    @if(session('status'))
    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 rounded text-sm text-green-800">
        {{ session('status') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 rounded text-sm text-red-800">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    {{-- ========================================================= --}}
    {{-- STATE 1: Plan doesn't support online payments (upgrade CTA) --}}
    {{-- ========================================================= --}}
    @if(!$canEnable)
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl p-8 mb-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Upgrade to accept online payments</h2>
                <p class="text-gray-700 mb-4">
                    Online payments aren't available on the <strong>{{ $plan?->name ?? 'current' }}</strong> plan.
                    Upgrade to <strong>SiteFresh Pro</strong> to enable online ordering with credit card checkout.
                </p>

                <div class="bg-white rounded-lg p-5 mb-4 border border-gray-200">
                    <h3 class="font-semibold text-gray-900 mb-3">What you get with Online Payments</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Customers pay with card when they order — no more missed pickups
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Money goes directly to your bank — powered by Stripe
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            No monthly commitment fees or hidden charges
                        </li>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Setup takes minutes — just enter your business info and bank details
                        </li>
                    </ul>
                </div>

                <div class="flex gap-3">
                    <a href="https://portal.sos-tech.ca/client/billing/services" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700">
                        View Upgrade Options
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- =============================================================== --}}
    {{-- STATE 2: Eligible plan, not yet enabled — show enable CTA       --}}
    {{-- =============================================================== --}}
    @if($canEnable && !$site->stripe_account_id)
    <div class="bg-white border border-gray-200 rounded-xl p-8 mb-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Enable Online Payments</h2>
                <p class="text-gray-600">Connect your Stripe account to start accepting credit card payments on {{ $site->business_name }}.</p>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-5 mb-5">
            <h3 class="font-semibold text-gray-900 mb-3">How it works</h3>
            <ol class="space-y-2 text-sm text-gray-700 list-decimal list-inside">
                <li>Click the button below — we'll create a Stripe account for your restaurant</li>
                <li>Stripe will walk you through a secure setup form (~5 minutes): business info, bank account, identity verification</li>
                <li>Once approved, customers can pay by card on your website</li>
                <li>Payouts go directly to your bank on a daily schedule</li>
            </ol>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-5">
            <h3 class="font-semibold text-gray-900 mb-3">Fees</h3>
            <div class="space-y-2 text-sm text-gray-700">
                <div class="flex justify-between">
                    <span>Stripe processing fee:</span>
                    <span class="font-medium">2.49% + $0.10 per transaction</span>
                </div>
                <div class="flex justify-between">
                    <span>MenuDirect platform fee:</span>
                    <span class="font-medium">{{ number_format($platformFeePercent, 2) }}%</span>
                </div>
                @if($requiresAddon)
                <div class="flex justify-between pt-2 border-t border-blue-200">
                    <span>Online Payments add-on:</span>
                    <span class="font-semibold text-blue-900">$10.00 / month</span>
                </div>
                <div class="text-xs text-blue-700 italic">
                    Upgrade to MenuDirect Max to get online payments included in your subscription.
                </div>
                @else
                <div class="flex justify-between pt-2 border-t border-blue-200">
                    <span>Monthly add-on fee:</span>
                    <span class="font-semibold text-emerald-700">Included in your plan</span>
                </div>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('client.restaurant.payments.enable', $site) }}">
            @csrf
            @if($requiresAddon)
            <label class="flex items-start gap-3 mb-5 p-4 border border-amber-300 bg-amber-50 rounded-lg cursor-pointer">
                <input type="checkbox" name="acknowledge_addon" value="1" required
                       class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-amber-900">
                    I understand that enabling Online Payments will add <strong>$10.00/month</strong> to my subscription, charged on my next invoice. The add-on can be disabled at any time from this page.
                </span>
            </label>
            @endif

            <button type="submit" class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white rounded-lg font-semibold hover:bg-emerald-700">
                Connect with Stripe
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- STATE 3: Onboarding started but not yet complete              --}}
    {{-- =========================================================== --}}
    @if($canEnable && $site->stripe_account_id && !$site->stripe_charges_enabled)
    <div class="bg-white border border-amber-200 rounded-xl p-8 mb-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Finish setting up your Stripe account</h2>
                <p class="text-gray-600 mb-1">Your Stripe account is created but onboarding isn't complete yet.</p>
                <p class="text-sm text-gray-500">Stripe needs a few more details before you can start accepting payments.</p>
            </div>
        </div>

        @if($accountStatus && !empty($accountStatus['requirements']['currently_due']))
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-5">
            <p class="text-sm font-semibold text-amber-900 mb-2">Stripe needs:</p>
            <ul class="text-sm text-amber-800 list-disc list-inside">
                @foreach(array_slice($accountStatus['requirements']['currently_due'], 0, 8) as $req)
                <li>{{ str_replace(['_', '.'], ' ', $req) }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="flex gap-3">
            <a href="{{ route('client.restaurant.payments.onboarding', $site) }}"
               class="inline-flex items-center px-5 py-2.5 bg-amber-600 text-white rounded-lg font-semibold hover:bg-amber-700">
                Continue Setup
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>

            <form method="POST" action="{{ route('client.restaurant.payments.disable', $site) }}" onsubmit="return confirm('Cancel setup? You can re-enable later.')">
                @csrf
                <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                    Cancel setup
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- STATE 4: Fully active — online payments working              --}}
    {{-- =========================================================== --}}
    @if($site->stripe_charges_enabled && $site->online_payments_enabled)
    <div class="bg-white border border-emerald-200 rounded-xl p-8 mb-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 mb-1">Online Payments Active</h2>
                <p class="text-gray-600">Customers can now pay by credit card on your website.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800">
                ✓ Active
            </span>
        </div>

        <dl class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <dt class="text-gray-500">Charges</dt>
                <dd class="font-medium {{ $site->stripe_charges_enabled ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $site->stripe_charges_enabled ? 'Enabled' : 'Disabled' }}
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Payouts</dt>
                <dd class="font-medium {{ $site->stripe_payouts_enabled ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $site->stripe_payouts_enabled ? 'Enabled' : 'Pending' }}
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Platform fee</dt>
                <dd class="font-medium text-gray-900">{{ number_format($platformFeePercent, 2) }}%</dd>
            </div>
            <div>
                <dt class="text-gray-500">Monthly add-on</dt>
                <dd class="font-medium text-gray-900">
                    @if($requiresAddon)
                        $10.00/mo
                    @else
                        Included
                    @endif
                </dd>
            </div>
        </dl>

        <div class="flex gap-3 flex-wrap">
            <a href="{{ route('client.restaurant.payments.dashboard', $site) }}" target="_blank"
               class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700">
                Open Stripe Dashboard
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>

            <form method="POST" action="{{ route('client.restaurant.payments.disable', $site) }}" onsubmit="return confirm('Disable online payments? Orders will go back to pay-at-pickup.')">
                @csrf
                <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                    Disable Online Payments
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
