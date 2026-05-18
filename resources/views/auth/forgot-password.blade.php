@extends('layouts.app')
@section('content')
<div class="max-w-md mx-auto bg-white border rounded p-6">
  <h1 class="text-xl font-semibold mb-4">Forgot password</h1>
  <form method="post" action="{{ route('password.email') }}" class="space-y-3">
    @csrf
    <label class="block text-sm">Email<input type="email" name="email" value="{{ old('email') }}" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <button class="w-full px-4 py-2 bg-indigo-600 text-white rounded">Send reset link</button>
  </form>
  <div class="text-sm mt-4"><a href="{{ route('login') }}" class="text-indigo-600">Back to sign in</a></div>
</div>
@endsection
