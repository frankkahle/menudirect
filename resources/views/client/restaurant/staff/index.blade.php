@extends('layouts.app')

@section('title', 'Staff Management - ' . $site->business_name)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('client.restaurant.show', $site) }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $site->business_name }}
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Staff Management</h1>
        <p class="text-gray-600 mt-1">Invite staff to help manage orders and reservations</p>
    </div>

    @if(session('status'))
    <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-400 rounded text-sm text-green-800">
        {{ session('status') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-400 rounded text-sm text-red-800">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    {{-- Invite form --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Invite a Staff Member</h2>
        <form method="POST" action="{{ route('client.restaurant.staff.store', $site) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <input type="text" name="name" placeholder="Full name" required
                   class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="email" name="email" placeholder="Email address" required
                   class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select name="role" required class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="staff">Staff — Orders &amp; reservations</option>
                <option value="content">Content — Menu &amp; photos only</option>
                <option value="manager">Manager — Full access</option>
            </select>
            <button type="submit" class="bg-indigo-600 text-white rounded-md px-4 py-2 font-medium hover:bg-indigo-700">
                Send Invite
            </button>
        </form>
        <p class="text-xs text-gray-500 mt-3">
            <strong>Staff</strong> can view and update orders, and manage reservations.
            <strong>Content</strong> can edit menu items, prices, photos, and announcements (no order access).
            <strong>Managers</strong> have full access to everything.
        </p>
    </div>

    {{-- Current staff list --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Team Members ({{ $staff->count() }})</h2>
        </div>

        @if($staff->isEmpty())
        <div class="p-8 text-center text-gray-500">
            <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p>No staff yet. Invite someone above to get started.</p>
        </div>
        @else
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Role</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Last Login</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($staff as $member)
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $member->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $member->email }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $member->isManager() ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ \App\Models\RestaurantStaff::ROLES[$member->role] }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if(!$member->is_active)
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Deactivated</span>
                        @elseif(!$member->invite_accepted_at)
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Pending invite</span>
                        @else
                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Active</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $member->last_login_at ? $member->last_login_at->diffForHumans() : 'Never' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm space-x-2">
                        @if(!$member->invite_accepted_at)
                        <form method="POST" action="{{ route('client.restaurant.staff.resend', [$site, $member]) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-indigo-600 hover:underline">Resend invite</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('client.restaurant.staff.destroy', [$site, $member]) }}" class="inline"
                              onsubmit="return confirm('Remove {{ $member->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
