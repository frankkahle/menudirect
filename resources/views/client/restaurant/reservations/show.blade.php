@extends('layouts.app')

@section('title', 'Reservation ' . $reservation->confirmation_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.reservations.index', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Reservations
        </a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $reservation->confirmation_number }}</h1>
                <p class="text-gray-500 mt-1">Reservation details</p>
            </div>
            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $reservation->status_badge_class }}">
                {{ $reservation->status_label }}
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Reservation Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Reservation Details</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Date</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation->formatted_date }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Time</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation->formatted_time }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Party Size</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation->party_size }} {{ Str::plural('guest', $reservation->party_size) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Duration</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation->duration_minutes }} min</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Created</dt>
                    <dd class="text-sm text-gray-900">{{ $reservation->created_at->format('M j, Y g:i A') }}</dd>
                </div>
                @if($reservation->confirmed_at)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Confirmed</dt>
                    <dd class="text-sm text-gray-900">{{ $reservation->confirmed_at->format('M j, Y g:i A') }}</dd>
                </div>
                @endif
                @if($reservation->seated_at)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Seated</dt>
                    <dd class="text-sm text-gray-900">{{ $reservation->seated_at->format('M j, Y g:i A') }}</dd>
                </div>
                @endif
                @if($reservation->cancelled_at)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Cancelled</dt>
                    <dd class="text-sm text-gray-900">{{ $reservation->cancelled_at->format('M j, Y g:i A') }}</dd>
                </div>
                @if($reservation->cancellation_reason)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Reason</dt>
                    <dd class="text-sm text-gray-900">{{ $reservation->cancellation_reason }}</dd>
                </div>
                @endif
                @if($reservation->cancelled_by)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Cancelled By</dt>
                    <dd class="text-sm text-gray-900">{{ ucfirst($reservation->cancelled_by) }}</dd>
                </div>
                @endif
                @endif
            </dl>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Name</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $reservation->customer_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Phone</dt>
                    <dd class="text-sm text-gray-900">
                        <a href="tel:{{ $reservation->customer_phone }}" class="text-indigo-600 hover:text-indigo-900">{{ $reservation->customer_phone }}</a>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">
                        <a href="mailto:{{ $reservation->customer_email }}" class="text-indigo-600 hover:text-indigo-900">{{ $reservation->customer_email }}</a>
                    </dd>
                </div>
            </dl>

            @if($reservation->special_requests)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Special Requests</h3>
                <p class="text-sm text-gray-600 bg-yellow-50 p-3 rounded-md">{{ $reservation->special_requests }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
        <div class="flex flex-wrap gap-3">
            @if($reservation->status === 'pending')
            <form action="{{ route('client.restaurant.reservations.confirm', [$site, $reservation]) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">Confirm Reservation</button>
            </form>
            @endif

            @if(in_array($reservation->status, ['pending', 'confirmed']))
            <form action="{{ route('client.restaurant.reservations.seat', [$site, $reservation]) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">Mark as Seated</button>
            </form>
            @endif

            @if($reservation->status === 'seated')
            <form action="{{ route('client.restaurant.reservations.complete', [$site, $reservation]) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-medium hover:bg-gray-700">Complete</button>
            </form>
            @endif

            @if(in_array($reservation->status, ['pending', 'confirmed']))
            <form action="{{ route('client.restaurant.reservations.no-show', [$site, $reservation]) }}" method="POST" onsubmit="return confirm('Mark as no-show?')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-400 text-white rounded-md text-sm font-medium hover:bg-gray-500">No Show</button>
            </form>
            @endif

            @if(!in_array($reservation->status, ['completed', 'cancelled', 'no_show']))
            <form action="{{ route('client.restaurant.reservations.cancel', [$site, $reservation]) }}" method="POST" onsubmit="return confirm('Cancel this reservation?')">
                @csrf
                <input type="hidden" name="reason" value="Cancelled by restaurant">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">Cancel Reservation</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
