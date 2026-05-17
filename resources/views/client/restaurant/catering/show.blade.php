@extends('layouts.app')

@section('title', $inquiry->inquiry_number . ' - Catering')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.catering.index', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Catering Inquiries
        </a>

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $inquiry->inquiry_number }}</h1>
                <p class="text-gray-600 mt-1">Received {{ $inquiry->created_at->format('F j, Y \a\t g:i A') }}</p>
            </div>
            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $inquiry->status_badge_class }}">
                {{ $inquiry->status_label }}
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Name</dt>
                        <dd class="font-medium text-gray-900">{{ $inquiry->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Phone</dt>
                        <dd><a href="tel:{{ $inquiry->customer_phone }}" class="text-indigo-600 hover:underline">{{ $inquiry->customer_phone }}</a></dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-sm text-gray-500">Email</dt>
                        <dd><a href="mailto:{{ $inquiry->customer_email }}" class="text-indigo-600 hover:underline">{{ $inquiry->customer_email }}</a></dd>
                    </div>
                </dl>
            </div>

            <!-- Event Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Event Details</h2>
                <dl class="grid grid-cols-2 gap-4">
                    @if($inquiry->event_date)
                    <div>
                        <dt class="text-sm text-gray-500">Date</dt>
                        <dd class="font-medium text-gray-900">{{ $inquiry->formatted_date }}</dd>
                    </div>
                    @endif
                    @if($inquiry->event_time)
                    <div>
                        <dt class="text-sm text-gray-500">Time</dt>
                        <dd class="font-medium text-gray-900">{{ $inquiry->event_time }}</dd>
                    </div>
                    @endif
                    @if($inquiry->guest_count)
                    <div>
                        <dt class="text-sm text-gray-500">Guest Count</dt>
                        <dd class="font-medium text-gray-900">{{ $inquiry->guest_count }}</dd>
                    </div>
                    @endif
                    @if($inquiry->event_type)
                    <div>
                        <dt class="text-sm text-gray-500">Event Type</dt>
                        <dd class="font-medium text-gray-900">{{ $inquiry->event_type_label }}</dd>
                    </div>
                    @endif
                    @if($inquiry->cateringPackage)
                    <div class="col-span-2">
                        <dt class="text-sm text-gray-500">Selected Package</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $inquiry->cateringPackage->name }}
                            <span class="text-gray-500">({{ $inquiry->cateringPackage->formatted_price }})</span>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>

            @if($inquiry->message)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Message</h2>
                <p class="text-gray-700 whitespace-pre-line">{{ $inquiry->message }}</p>
            </div>
            @endif

            <!-- Admin Notes -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Internal Notes</h2>
                <form action="{{ route('client.restaurant.catering.note', [$site, $inquiry]) }}" method="POST">
                    @csrf
                    <textarea name="admin_notes" rows="4"
                        class="w-full rounded-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Add notes about this inquiry...">{{ old('admin_notes', $inquiry->admin_notes) }}</textarea>
                    <div class="mt-2 flex justify-end">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                            Save Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar - Status Actions -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h2>
                <div class="space-y-2">
                    @if($inquiry->status === 'new')
                    <form action="{{ route('client.restaurant.catering.status', [$site, $inquiry]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="contacted">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                            Mark as Contacted
                        </button>
                    </form>
                    @endif

                    @if(in_array($inquiry->status, ['new', 'contacted']))
                    <form action="{{ route('client.restaurant.catering.status', [$site, $inquiry]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="quoted">
                        <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm">
                            Mark as Quoted
                        </button>
                    </form>
                    @endif

                    @if(in_array($inquiry->status, ['contacted', 'quoted']))
                    <form action="{{ route('client.restaurant.catering.status', [$site, $inquiry]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="booked">
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                            Mark as Booked
                        </button>
                    </form>
                    @endif

                    @if(!in_array($inquiry->status, ['booked', 'declined', 'cancelled']))
                    <form action="{{ route('client.restaurant.catering.status', [$site, $inquiry]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="declined">
                        <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm">
                            Decline
                        </button>
                    </form>
                    @endif

                    @if(!in_array($inquiry->status, ['cancelled', 'declined']))
                    <form action="{{ route('client.restaurant.catering.status', [$site, $inquiry]) }}" method="POST"
                          onsubmit="return confirm('Cancel this inquiry?')">
                        @csrf
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm">
                            Cancel
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center text-gray-600">
                        <div class="w-2 h-2 bg-gray-400 rounded-full mr-3"></div>
                        <span>Created {{ $inquiry->created_at->format('M j, g:i A') }}</span>
                    </div>
                    @if($inquiry->contacted_at)
                    <div class="flex items-center text-blue-600">
                        <div class="w-2 h-2 bg-blue-400 rounded-full mr-3"></div>
                        <span>Contacted {{ $inquiry->contacted_at->format('M j, g:i A') }}</span>
                    </div>
                    @endif
                    @if($inquiry->quoted_at)
                    <div class="flex items-center text-purple-600">
                        <div class="w-2 h-2 bg-purple-400 rounded-full mr-3"></div>
                        <span>Quoted {{ $inquiry->quoted_at->format('M j, g:i A') }}</span>
                    </div>
                    @endif
                    @if($inquiry->booked_at)
                    <div class="flex items-center text-green-600">
                        <div class="w-2 h-2 bg-green-400 rounded-full mr-3"></div>
                        <span>Booked {{ $inquiry->booked_at->format('M j, g:i A') }}</span>
                    </div>
                    @endif
                    @if($inquiry->declined_at)
                    <div class="flex items-center text-gray-600">
                        <div class="w-2 h-2 bg-gray-400 rounded-full mr-3"></div>
                        <span>Declined {{ $inquiry->declined_at->format('M j, g:i A') }}</span>
                    </div>
                    @endif
                    @if($inquiry->cancelled_at)
                    <div class="flex items-center text-red-600">
                        <div class="w-2 h-2 bg-red-400 rounded-full mr-3"></div>
                        <span>Cancelled {{ $inquiry->cancelled_at->format('M j, g:i A') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
