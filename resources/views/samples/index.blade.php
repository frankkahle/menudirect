<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Sites | SOS Tech</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-12 px-4">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Sample Websites</h1>
            <p class="text-gray-600">Click any site to preview. Share with potential clients to demonstrate your services.</p>
        </div>

        @if(empty($sites))
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-500">No sample sites configured yet.</p>
            <p class="text-sm text-gray-400 mt-2">Add sites in <code>config/samples.php</code></p>
        </div>
        @else
        <div class="grid gap-4">
            @foreach($sites as $slug => $site)
            <a href="{{ route('samples.show', $slug) }}" class="block bg-white rounded-lg shadow hover:shadow-md transition p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $site['name'] }}</h2>
                        <p class="text-gray-600 text-sm mt-1">{{ $site['tagline'] ?? '' }}</p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                            <span class="bg-gray-100 px-2 py-1 rounded">{{ $site['template'] ?? 'generic' }}</span>
                            @if(!empty($site['phone']))
                            <span>{{ $site['phone'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!empty($site['colors']['primary']))
                        <div class="w-6 h-6 rounded-full border border-gray-200" style="background: {{ $site['colors']['primary'] }};"></div>
                        @endif
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif

        <div class="mt-12 text-center text-sm text-gray-500">
            <p>URLs: <code class="bg-gray-200 px-2 py-1 rounded">{slug}.menudirect.ca</code></p>
        </div>
    </div>
</body>
</html>
