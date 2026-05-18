@extends('layouts.app')
@section('content')
<div class="max-w-md mx-auto bg-white border rounded p-6">
  <h1 class="text-xl font-semibold mb-4">Create account</h1>
  <form method="post" action="{{ route('register.post') }}" class="space-y-3" id="registerForm">
    @csrf
    {{-- Honeypot field - hidden from users, bots will fill it --}}
    <div style="position: absolute; left: -9999px;" aria-hidden="true">
      <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>
    {{-- Timing token - form submitted too fast = bot --}}
    <input type="hidden" name="_form_token" value="{{ encrypt(now()->timestamp) }}">

    <label class="block text-sm">Name<input type="text" name="name" value="{{ old('name') }}" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <label class="block text-sm">Email<input type="email" name="email" value="{{ old('email') }}" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <label class="block text-sm">Password<input type="password" name="password" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <label class="block text-sm">Confirm Password<input type="password" name="password_confirmation" class="mt-1 border rounded px-3 py-2 w-full" required></label>

    @if(config('services.recaptcha.site_key'))
    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
    <p class="text-xs text-gray-500">
      This site is protected by reCAPTCHA and the Google
      <a href="https://policies.google.com/privacy" class="text-indigo-600" target="_blank">Privacy Policy</a> and
      <a href="https://policies.google.com/terms" class="text-indigo-600" target="_blank">Terms of Service</a> apply.
    </p>
    @endif

    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded">Register</button>
  </form>
  <div class="text-sm mt-4">Already have an account? <a href="{{ route('login') }}" class="text-indigo-600">Sign in</a></div>
</div>

@if(config('services.recaptcha.site_key'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'register'}).then(function(token) {
            document.getElementById('g-recaptcha-response').value = token;
            document.getElementById('registerForm').submit();
        });
    });
});
</script>
@endif
@endsection
