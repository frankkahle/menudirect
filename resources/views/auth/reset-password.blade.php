@extends('layouts.app')
@section('content')
<div class="max-w-md mx-auto bg-white border rounded p-6">
  <h1 class="text-xl font-semibold mb-4">Reset password</h1>
  <form method="post" action="{{ route('password.update') }}" class="space-y-3">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label class="block text-sm">Email<input type="email" name="email" value="{{ old('email', $email) }}" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <label class="block text-sm">New password<input type="password" name="password" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <label class="block text-sm">Confirm password<input type="password" name="password_confirmation" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded">Update password</button>
  </form>
  <div class="text-sm mt-4"><a href="{{ route('login') }}" class="text-indigo-600">Back to sign in</a></div>
</div>
@endsection
