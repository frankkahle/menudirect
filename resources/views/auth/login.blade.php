<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — MenuDirect</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">MenuDirect</h1>
            <p class="text-sm text-gray-500 mt-2">Restaurant owner portal</p>
        </div>

        <div class="bg-white border rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900">Sign in</h2>

            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="remember" class="rounded">
                    Remember me
                </label>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white font-medium rounded-md hover:bg-indigo-700">
                    Sign in
                </button>
                <div class="text-sm text-center">
                    <a href="{{ route('password.request') }}" class="text-indigo-600 hover:text-indigo-800">Forgot password?</a>
                </div>
            </form>
        </div>

        <p class="text-xs text-gray-500 text-center mt-6">
            Not a MenuDirect customer yet? <a href="https://menudirect.ca" class="text-indigo-600">Get a demo</a>
        </p>
    </div>
</body>
</html>
