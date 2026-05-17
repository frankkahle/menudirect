@extends('layouts.app')

@section('title', 'Catering Inquiries - ' . $site->business_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('client.restaurant.show', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $site->business_name }}
        </a>

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Catering Inquiries</h1>
                <p class="text-gray-600 mt-1">Manage catering requests</p>
            </div>
            <a href="{{ route('client.restaurant.catering.packages', $site) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                Manage Packages
            </a>
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

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-8">
        <a href="{{ route('client.restaurant.catering.index', [$site, 'status' => 'new']) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition {{ request('status') === 'new' ? 'ring-2 ring-yellow-500' : '' }}">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">New</p>
                    <p class="text-2xl font-bold {{ $newCount > 0 ? 'text-yellow-600' : 'text-gray-900' }}">{{ $newCount }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('client.restaurant.catering.index', $site) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition {{ !request('status') ? 'ring-2 ring-indigo-500' : '' }}">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Active</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $activeCount }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('client.restaurant.catering.index', [$site, 'status' => 'booked']) }}"
           class="bg-white rounded-lg shadow p-4 hover:shadow-md transition {{ request('status') === 'booked' ? 'ring-2 ring-green-500' : '' }}">
            <div class="flex items-center">
                <div class="p-2 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Booked</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $bookedCount }}</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form class="flex items-center gap-4">
            <div>
                <label class="text-sm text-gray-600 block mb-1">Status</label>
                <select name="status" class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\CateringInquiry::STATUSES as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- Inquiries List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($inquiries->isEmpty())
        <div class="p-8 text-center">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="text-gray-500">No catering inquiries yet.</p>
        </div>
        @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inquiry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($inquiries as $inquiry)
                <tr class="{{ $inquiry->status === 'new' ? 'bg-yellow-50' : '' }} hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('client.restaurant.catering.show', [$site, $inquiry]) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                            {{ $inquiry->inquiry_number }}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm font-medium text-gray-900">{{ $inquiry->customer_name }}</p>
                        <p class="text-xs text-gray-500">{{ $inquiry->customer_phone }}</p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($inquiry->event_date)
                        <p>{{ $inquiry->formatted_date }}</p>
                        @endif
                        @if($inquiry->guest_count)
                        <p class="text-xs">{{ $inquiry->guest_count }} guests</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $inquiry->cateringPackage?->name ?? 'General' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $inquiry->status_badge_class }}">
                            {{ $inquiry->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $inquiry->created_at->diffForHumans() }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        @if($inquiry->status === 'new')
                        <form action="{{ route('client.restaurant.catering.status', [$site, $inquiry]) }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="contacted">
                            <button type="submit" class="text-blue-600 hover:text-blue-900 mr-2">Mark Contacted</button>
                        </form>
                        @endif
                        <a href="{{ route('client.restaurant.catering.show', [$site, $inquiry]) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    @if($inquiries->hasPages())
    <div class="mt-6">
        {{ $inquiries->withQueryString()->links() }}
    </div>
    @endif
</div>

@if($newCount > 0)
<script>
setTimeout(() => window.location.reload(), 60000);
</script>
@endif
@endsection
