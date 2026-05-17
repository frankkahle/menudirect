<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Staff Dashboard') - {{ $staff->site->business_name ?? 'SOS Tech' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.15.11/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-indigo-700 text-white shadow-md">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <div class="text-lg font-bold">{{ $staff->site->business_name }}</div>
                <div class="text-xs text-indigo-200">Staff Dashboard — {{ ucfirst($staff->role) }}: {{ $staff->name }}</div>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('staff.dashboard') }}" class="text-sm hover:text-indigo-200">Dashboard</a>
                <a href="{{ route('staff.orders.index') }}" class="text-sm hover:text-indigo-200">Orders</a>
                <form method="POST" action="{{ route('staff.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm bg-indigo-800 hover:bg-indigo-900 px-3 py-1 rounded">Sign out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if(session('status'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('status') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            {{ $errors->first() }}
        </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
