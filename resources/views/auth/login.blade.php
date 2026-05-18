@extends('layouts.app')
@section('content')
<div class="max-w-md mx-auto bg-white border rounded p-6">
  <h1 class="text-xl font-semibold mb-4">Sign in</h1>

  @if (session('captcha_required'))
    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
      Too many failed login attempts. Please complete the CAPTCHA verification.
    </div>
  @endif

  <form method="post" action="{{ route('login.post') }}" class="space-y-3" id="login-form">
    @csrf
    <label class="block text-sm">Email<input type="email" name="email" value="{{ old('email') }}" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <label class="block text-sm">Password<input type="password" name="password" class="mt-1 border rounded px-3 py-2 w-full" required></label>
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="rounded"> Remember me</label>

    @if (config('captcha.sitekey'))
      <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
    @endif

    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded">Sign in</button>
    <div class="text-sm text-right">
      <a href="{{ route('password.request') }}" class="text-indigo-600">Forgot password?</a>
    </div>
  </form>
  <div class="text-sm mt-4">No account? <a href="{{ route('register') }}" class="text-indigo-600">Register</a></div>
</div>

<!-- SOSMail Download -->
<div class="max-w-md mx-auto mt-6 bg-white border rounded p-6">
    <div class="flex items-center mb-4">
        <div class="p-2 bg-indigo-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-semibold text-gray-900">SOSMail — Free Email Client</h3>
            @php
                $sosmailYml = '/var/www/sos-tech/public/updates/sosmail/latest.yml';
                $sosmailVer = 'latest';
                if (file_exists($sosmailYml)) {
                    foreach (file($sosmailYml, FILE_IGNORE_NEW_LINES) as $l) {
                        if (preg_match('/^version:\s*(.+)$/', $l, $m)) { $sosmailVer = trim($m[1]); break; }
                    }
                }
            @endphp
            <p class="text-xs text-gray-500">v{{ $sosmailVer }} — Works with any mail service</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('software.download', 'sosmail') }}" class="flex-1 px-3 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 flex items-center justify-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Windows
        </a>
        <a href="{{ route('software.download', 'sosmail-linux') }}" class="flex-1 px-3 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700 flex items-center justify-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Linux
        </a>
    </div>
</div>

@if (config('captcha.sitekey'))
<script src="https://www.google.com/recaptcha/api.js?render={{ config('captcha.sitekey') }}"></script>
<script>
  document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;

    grecaptcha.ready(function() {
      grecaptcha.execute('{{ config('captcha.sitekey') }}', {action: 'login'}).then(function(token) {
        document.getElementById('g-recaptcha-response').value = token;
        form.submit();
      });
    });
  });
</script>
@endif
@endsection
