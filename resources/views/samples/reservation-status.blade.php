<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation {{ $reservation['confirmation_number'] }} | {{ $reservation['restaurant']['name'] }}</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="min-h-screen py-8 md:py-12">
    <div class="max-w-2xl mx-auto px-4">
        {{-- Reservation Header --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Reservation {{ $reservation['confirmation_number'] }}</h1>
                <p class="text-gray-600 mt-1">{{ $reservation['restaurant']['name'] }}</p>
            </div>

            {{-- Status Badge --}}
            <div class="flex justify-center mb-6">
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'confirmed' => 'bg-green-100 text-green-800',
                        'seated' => 'bg-blue-100 text-blue-800',
                        'completed' => 'bg-gray-100 text-gray-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                        'no_show' => 'bg-gray-100 text-gray-800',
                    ];
                    $statusLabels = [
                        'pending' => 'Pending Confirmation',
                        'confirmed' => 'Confirmed',
                        'seated' => 'Seated',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show',
                    ];
                    $statusColor = $statusColors[$reservation['status']] ?? 'bg-gray-100 text-gray-800';
                    $statusLabel = $statusLabels[$reservation['status']] ?? ucfirst($reservation['status']);
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-semibold {{ $statusColor }}">
                    @if($reservation['status'] === 'confirmed')
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    @endif
                    {{ $statusLabel }}
                </span>
            </div>
        </div>

        {{-- Reservation Details --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Reservation Details</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Date</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation['formatted_date'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Time</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation['formatted_time'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Party Size</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation['party_size'] }} {{ $reservation['party_size'] == 1 ? 'guest' : 'guests' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Name</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation['customer_name'] }}</dd>
                </div>
                @if(!empty($reservation['special_requests']))
                <div class="pt-3 border-t">
                    <dt class="text-sm text-gray-500 mb-1">Special Requests</dt>
                    <dd class="text-sm text-gray-700 bg-yellow-50 p-3 rounded-md">{{ $reservation['special_requests'] }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Restaurant Info --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Restaurant</h2>
            <div class="space-y-2">
                <p class="text-sm font-medium text-gray-900">{{ $reservation['restaurant']['name'] }}</p>
                @if(!empty($reservation['restaurant']['address']))
                <p class="text-sm text-gray-600">{{ $reservation['restaurant']['address'] }}</p>
                @endif
                @if(!empty($reservation['restaurant']['phone']))
                <p class="text-sm">
                    <a href="tel:{{ $reservation['restaurant']['phone'] }}" class="text-indigo-600 hover:text-indigo-800">{{ $reservation['restaurant']['phone'] }}</a>
                </p>
                @endif
            </div>
        </div>

        {{-- Cancel Button --}}
        @if(in_array($reservation['status'], ['pending', 'confirmed']))
        <div class="bg-white rounded-lg shadow-md p-6" x-data="{ cancelling: false, cancelled: false }">
            <template x-if="!cancelled">
                <div>
                    <p class="text-sm text-gray-600 mb-4">Need to cancel? Please let us know as soon as possible.</p>
                    <button @click="if(confirm('Are you sure you want to cancel this reservation?')) {
                        cancelling = true;
                        fetch('{{ config('services.portal.url', 'https://portal.sos-tech.ca') }}/api/reservations/{{ $token }}/cancel', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
                        })
                        .then(r => r.json())
                        .then(data => { if(data.success) { cancelled = true; } else { alert(data.error || 'Failed to cancel'); } })
                        .catch(() => alert('Failed to cancel. Please call the restaurant.'))
                        .finally(() => cancelling = false);
                    }"
                    :disabled="cancelling"
                    class="w-full py-3 rounded-lg font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition">
                        <span x-show="!cancelling">Cancel Reservation</span>
                        <span x-show="cancelling">Cancelling...</span>
                    </button>
                </div>
            </template>
            <template x-if="cancelled">
                <div class="text-center py-4">
                    <svg class="w-12 h-12 text-red-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <p class="text-lg font-semibold text-gray-900">Reservation Cancelled</p>
                    <p class="text-sm text-gray-500 mt-1">Your reservation has been cancelled.</p>
                </div>
            </template>
        </div>
        @endif

        @if(!empty($reservation['cancellation_reason']))
        <div class="bg-red-50 rounded-lg p-4 mt-6">
            <p class="text-sm text-red-700"><strong>Cancellation reason:</strong> {{ $reservation['cancellation_reason'] }}</p>
        </div>
        @endif
    </div>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
