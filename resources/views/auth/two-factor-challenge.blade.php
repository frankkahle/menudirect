@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white border rounded-lg p-6">
        <h1 class="text-xl font-semibold mb-4">Two-Factor Authentication</h1>

        <p class="text-sm text-gray-600 mb-6">
            Please enter the 6-digit code from your authenticator app, or use a recovery code.
        </p>

        <form action="{{ route('two-factor.verify') }}" method="post">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">
                    Authentication Code
                </label>
                <input
                    type="text"
                    name="code"
                    placeholder="000000"
                    class="w-full border rounded px-4 py-3 text-center text-2xl tracking-widest font-mono @error('code') border-red-500 @enderror"
                    required
                    autofocus
                    autocomplete="off">
                @error('code')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-2">
                    Enter the 6-digit code from your authenticator app
                </p>
            </div>

            <button type="submit" class="w-full px-4 py-3 bg-indigo-600 text-white rounded hover:bg-indigo-700 font-medium">
                Verify
            </button>
        </form>

        <div class="mt-6 pt-6 border-t">
            <details class="text-sm">
                <summary class="cursor-pointer text-gray-600 hover:text-gray-900 font-medium">
                    Lost access to your authenticator app?
                </summary>
                <div class="mt-3 text-gray-600">
                    <p class="mb-2">You can use a recovery code instead:</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs">
                        <li>Enter your recovery code in the field above (format: XXXX-XXXX)</li>
                        <li>Each recovery code can only be used once</li>
                        <li>After logging in, generate new recovery codes</li>
                    </ol>
                </div>
            </details>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:underline">
                ← Back to login
            </a>
        </div>
    </div>
</div>
@endsection
