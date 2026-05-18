<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MenuDirect')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
    @stack('head')
</head>
<body class="bg-gray-50 min-h-screen">
    @auth
        <nav class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-8">
                        <a href="/" class="flex items-center gap-2">
                            <span class="text-xl font-bold text-gray-900">MenuDirect</span>
                        </a>

                        <div class="hidden md:flex items-center gap-6 text-sm">
                            <a href="/client/restaurant" class="{{ request()->routeIs('client.restaurant.*') ? 'text-indigo-600 font-medium' : 'text-gray-600 hover:text-indigo-600' }}">My Restaurants</a>

                            @if(auth()->user()->is_admin ?? false)
                                <span class="text-gray-300">|</span>
                                <a href="/admin/restaurant" class="{{ request()->routeIs('admin.restaurant.*') ? 'text-orange-600 font-medium' : 'text-gray-600 hover:text-orange-600' }}">Admin: Sites</a>
                                <a href="/admin/leads" class="{{ request()->routeIs('admin.leads.*') ? 'text-orange-600 font-medium' : 'text-gray-600 hover:text-orange-600' }}">Admin: Leads</a>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600 hidden sm:inline">{{ auth()->user()->email ?? '' }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-red-600">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endauth

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
