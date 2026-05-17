<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- SEO Meta Tags -->
    <title>@yield('title', 'SOS Tech Portal — Domains, Hosting, Email & Restaurant Sites')</title>
    <meta name="description" content="@yield('description', 'Manage your domains, web hosting, email accounts, and restaurant websites. Canadian-owned with 45+ years IT experience. Based in Hampton, NB.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'SOS Tech Portal — Domains, Hosting, Email & More')">
    <meta property="og:description" content="@yield('og_description', 'Manage your domains, web hosting, email, and restaurant websites. Canadian-owned, 45+ years IT experience.')">
    <meta property="og:image" content="{{ asset('images/sos-logo.png') }}">
    <meta property="og:site_name" content="SOS Technical Services">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('twitter_title', 'SOS Tech Portal — Canadian Web Services')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Domains, hosting, email, and restaurant websites. Canadian-owned.')">

    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style> .container{max-width:1100px} </style>

    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "Organization",
      "name": "SOS Technical Services",
      "url": "https://sos-tech.ca",
      "logo": "https://portal.sos-tech.ca/images/sos-logo.png",
      "telephone": "+1-506-910-5547",
      "address": {
        "@@type": "PostalAddress",
        "addressLocality": "Hampton",
        "addressRegion": "NB",
        "addressCountry": "CA"
      },
      "contactPoint": {
        "@@type": "ContactPoint",
        "email": "support@sos-tech.ca",
        "telephone": "+1-506-910-5547",
        "contactType": "Customer Service"
      },
      "sameAs": [
        "https://sos-tech.ca",
        "https://menudirect.ca"
      ]
    }
    </script>

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebApplication",
      "name": "SOS Tech Portal",
      "url": "https://portal.sos-tech.ca",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web",
      "description": "Client management portal for domains, web hosting, email, and restaurant website services.",
      "offers": {
        "@@type": "AggregateOffer",
        "priceCurrency": "CAD",
        "lowPrice": "4.99",
        "offerCount": "5"
      },
      "potentialAction": {
        "@@type": "SearchAction",
        "target": "https://portal.sos-tech.ca/domains/search?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    @yield('head')

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 text-gray-900">
    @if(auth()->check() && auth()->user()->isDemoAccount())
        @include('partials.demo-banner')
    @endif
    @if(session('impersonating_admin_id'))
    <div class="bg-yellow-500 text-yellow-900 px-4 py-2 text-center text-sm font-medium">
        You are impersonating <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }})
        <a href="{{ route('admin.clients.stop-impersonate') }}" class="ml-4 inline-flex items-center px-3 py-1 bg-yellow-700 text-white rounded hover:bg-yellow-800 text-xs font-semibold">
            Exit Impersonation
        </a>
    </div>
    @endif
    <nav class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <img src="/images/sos-logo.png" alt="SOS Tech" class="h-10">
                @if(auth()->check() && auth()->user()->isDemoAccount())
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">DEMO</span>
                @endif
                <span class="font-semibold text-indigo-600">
                    @php
                        $section = 'Client Portal'; // default

                        if (request()->routeIs('client.hosting.*')) {
                            $section = 'Web Hosting';
                        } elseif (request()->routeIs('client.email.*')) {
                            $section = 'Email Management';
                        } elseif (request()->routeIs('client.dns.*') || request()->routeIs('dns.*')) {
                            $section = 'DNS Manager';
                        } elseif (request()->routeIs('client.domains.*') || request()->routeIs('domains.*') || request()->routeIs('client.transfers*') || request()->routeIs('client.contacts*') || request()->routeIs('client.nameservers*')) {
                            $section = 'Domain Management';
                        } elseif (request()->routeIs('client.ssl.*') || request()->routeIs('ssl.*')) {
                            $section = 'SSL Certificates';
                        } elseif (request()->routeIs('client.account*') || request()->routeIs('client.payment*') || request()->routeIs('client.billing.*') || request()->routeIs('client.login-history*') || request()->routeIs('client.security*')) {
                            $section = 'Account Management';
                        } elseif (request()->routeIs('admin.*')) {
                            $section = 'Admin Panel';
                        } elseif (request()->routeIs('cart.*')) {
                            $section = 'Shopping Cart';
                        }
                    @endphp
                    {{ $section }}
                </span>
            </a>
            <div class="flex items-center gap-6">
                @auth
                    @php
                        // Determine which menu section is active
                        $isDashboard = request()->routeIs('client.dashboard');
                        $isHosting = request()->routeIs('client.hosting.*');
                        $isEmail = request()->routeIs('client.email.*');
                        $isDomains = request()->routeIs('client.domains.*') || request()->routeIs('domains.*') || request()->routeIs('client.transfers*') || request()->routeIs('client.contacts*') || request()->routeIs('client.nameservers*') || request()->routeIs('ssl.*') || request()->routeIs('client.ssl.*');
                        $isAccount = request()->routeIs('client.account*') || request()->routeIs('client.payment*') || request()->routeIs('client.billing.*') || request()->routeIs('client.login-history*') || request()->routeIs('client.security*') || request()->routeIs('client.audit-logs*');
                        $isSupport = request()->routeIs('client.support') || request()->routeIs('client.support.*');
                        $isAdmin = request()->routeIs('admin.*');
                    @endphp
                    <span class="text-sm text-gray-500">{{ app()->getLocale() === 'fr' ? 'Bonjour' : 'Hello' }}, {{ auth()->user()->name }}</span>
                    @if(auth()->user()->isDemoAccount())
                        @php
                            $demoSiteId = auth()->user()->demoSession?->restaurant_site_id;
                        @endphp
                        @if($demoSiteId)
                            <a href="{{ route('client.restaurant.show', $demoSiteId) }}" class="text-sm font-medium text-indigo-600">My Restaurant</a>
                            <a href="{{ route('client.restaurant.menu', $demoSiteId) }}" class="text-sm {{ request()->routeIs('client.restaurant.menu') ? 'font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}">Menu Editor</a>
                            <a href="{{ route('client.restaurant.orders.index', $demoSiteId) }}" class="text-sm {{ request()->routeIs('client.restaurant.orders.*') ? 'font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}">Orders</a>
                        @endif
                    @else
                    <a href="{{ route('client.dashboard') }}" class="text-sm font-medium {{ $isDashboard ? 'text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}">Dashboard</a>
                    @endif
                    @if(!auth()->user()->isDemoAccount())
                    <a href="{{ route('client.hosting.index') }}" class="text-sm {{ $isHosting ? 'font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}">My Websites</a>
                    <a href="{{ route('client.email.index') }}" class="text-sm {{ $isEmail ? 'font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}">Email</a>
                    @endif

                    @if(!auth()->user()->isDemoAccount())
                    <!-- My Domains Dropdown -->
                    <div class="relative group">
                        <button class="text-sm flex items-center gap-1 {{ $isDomains ? 'font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}">
                            {{ __('common.my_domains') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-2 w-48 bg-white border rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                            <a href="/client/domains" class="block px-4 py-2 text-sm hover:bg-gray-100">My Domains</a>
                            <a href="/domains/search" class="block px-4 py-2 text-sm hover:bg-gray-100">Register New Domain</a>
                            <a href="{{ route('client.transfers') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Transfer Domain</a>
                            <a href="{{ route('ssl.certificates.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">SSL Certificates</a>
                            <a href="/client/contacts" class="block px-4 py-2 text-sm hover:bg-gray-100">Contact Manager</a>
                            <a href="{{ route('client.nameservers') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Nameserver Manager</a>
                        </div>
                    </div>

                    <!-- My Account Dropdown -->
                    <div class="relative group">
                        <button class="text-sm flex items-center gap-1 {{ $isAccount ? 'font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}">
                            {{ __('common.my_account') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-2 w-48 bg-white border rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                            <a href="/client/account" class="block px-4 py-2 text-sm hover:bg-gray-100">Account Settings</a>
                            <a href="/client/payment" class="block px-4 py-2 text-sm hover:bg-gray-100">Payment Settings</a>
                            <a href="{{ route('client.billing.services') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">My Services</a>
                            <a href="{{ route('client.billing.invoices') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Invoices</a>
                            <a href="/client/login-history" class="block px-4 py-2 text-sm hover:bg-gray-100">Login History</a>
                            <a href="/client/security" class="block px-4 py-2 text-sm hover:bg-gray-100">Security Settings</a>
                        </div>
                    </div>

                    @endif {{-- end !isDemoAccount nav items --}}

                    <!-- Support Link -->
                    @if(!auth()->user()->isDemoAccount())
                    <a href="{{ route('client.support') }}" target="_blank" rel="noopener" class="text-sm flex items-center gap-1 {{ $isSupport ? 'font-medium text-indigo-600 border-b-2 border-indigo-600 pb-1' : 'text-gray-600 hover:text-indigo-600' }}" title="Open a support ticket (opens in new tab)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Support
                    </a>
                    @endif

                    @if(auth()->user()->isAdmin() && !session('impersonating_admin_id'))
                        <!-- Admin Panel Dropdown -->
                        <div class="relative group">
                            <button class="text-sm flex items-center gap-1 {{ $isAdmin ? 'text-purple-700 font-bold border-b-2 border-purple-600 pb-1' : 'text-purple-600 hover:text-purple-700 font-semibold' }}">
                                Admin Panel
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="absolute left-0 mt-2 w-56 bg-white border rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 font-semibold text-gray-700">{{ app()->getLocale() === 'fr' ? 'Tableau de bord' : 'Dashboard' }}</a>
                                <div class="border-t border-gray-200"></div>
                                <a href="{{ route('admin.clients.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Client Management</a>
                                <a href="{{ route('admin.domains.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Domain Management</a>
                                <a href="{{ route('admin.email.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Email Management</a>
                                <a href="{{ route('admin.sosdesk.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 text-emerald-600">SOSDesk</a>
                                <a href="{{ route('admin.restaurant.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 text-orange-600">Restaurant Sites</a>
                                <a href="{{ route('admin.leads.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 text-orange-600">Restaurant Leads</a>
                                <a href="{{ route('admin.invoices.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Invoices</a>
                                <div class="border-t border-gray-200"></div>
                                <a href="{{ route('admin.products.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Products</a>
                                <a href="{{ route('admin.pricing.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Pricing Rules</a>
                                <a href="{{ route('admin.email-templates.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Email Templates</a>
                                <a href="{{ route('admin.settings.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">System Settings</a>
                                @if(config('cloudflare.enabled'))
                                <a href="{{ route('admin.settings.cloudflare') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Cloudflare Tokens</a>
                                @endif
                                <div class="border-t border-gray-200"></div>
                                <a href="{{ route('admin.reports.revenue') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Revenue Report</a>
                                <a href="{{ route('admin.reports.reconciliation') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Reconciliation Report</a>
                                <a href="{{ route('admin.audit-logs.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Audit Logs</a>
                                <a href="{{ route('admin.auth-logs.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Auth Logs</a>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('logout') }}" method="post" class="inline">
                        @csrf
                        <button class="text-sm text-red-600 hover:text-red-700">{{ __('common.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('common.login') }}</a>
                    <a href="{{ route('register') }}" class="text-sm font-medium bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">{{ __('common.register') }}</a>
                @endauth

                <!-- Language Switcher -->
                <div class="relative group ml-2">
                    <button class="flex items-center gap-1 text-sm hover:text-indigo-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                        {{ app()->getLocale() === 'fr' ? 'FR' : 'EN' }}
                    </button>
                    <div class="absolute right-0 mt-2 w-32 bg-white border rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                        <a href="{{ route('language.switch', 'en') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 {{ app()->getLocale() === 'en' ? 'font-semibold text-indigo-600' : '' }}">
                            English
                        </a>
                        <a href="{{ route('language.switch', 'fr') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 {{ app()->getLocale() === 'fr' ? 'font-semibold text-indigo-600' : '' }}">
                            Français
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main class="container mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 p-3 bg-green-100 border border-green-200 text-green-800 rounded">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-100 border border-red-200 text-red-800 rounded">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 mt-16">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <img src="/images/sos-logo.png" alt="SOS Tech" class="h-10 mb-4 brightness-200">
                    <p class="text-sm mb-4">
                        Professional web hosting, domain registration, and email services from New Brunswick, Canada.
                    </p>
                    <p class="text-sm">
                        <a href="mailto:support@sos-tech.ca" class="hover:text-white">support@sos-tech.ca</a>
                    </p>
                </div>

                <!-- Domain TLDs -->
                <div>
                    <h3 class="text-white font-bold mb-4">Popular Domains</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('tld.ca') }}" class="hover:text-white">.CA Domains</a></li>
                        <li><a href="{{ route('tld.com') }}" class="hover:text-white">.COM Domains</a></li>
                        <li><a href="{{ route('tld.net') }}" class="hover:text-white">.NET Domains</a></li>
                        <li><a href="{{ route('tld.org') }}" class="hover:text-white">.ORG Domains</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h3 class="text-white font-bold mb-4">Services</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('domains.search') }}" class="hover:text-white">Domain Search</a></li>
                        @auth
                            @if(!auth()->user()->isDemoAccount())
                            <li><a href="{{ route('ssl.orders.create') }}" class="hover:text-white">SSL Certificates</a></li>
                            <li><a href="{{ route('client.transfers.create') }}" class="hover:text-white">Transfer Domains</a></li>
                            <li><a href="{{ route('client.domains') }}" class="hover:text-white">Manage Domains</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('register') }}" class="hover:text-white">SSL Certificates</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-white">Transfer Domains</a></li>
                        @endauth
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="text-white font-bold mb-4">Company</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('about') }}" class="hover:text-white">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white">Contact</a></li>
                        @auth
                            @if(!auth()->user()->isDemoAccount())
                            <li><a href="{{ route('client.support') }}" target="_blank" rel="noopener" class="hover:text-white">Support Center</a></li>
                            @endif
                        @endauth
                        <li><a href="{{ route('terms') }}" class="hover:text-white">Terms of Service</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-sm">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-center md:text-left">
                        <p>&copy; {{ date('Y') }} SOS Technical Services. All rights reserved.</p>
                        <p class="mt-2 text-gray-500">Hampton, New Brunswick, Canada</p>
                    </div>
                    <!-- Language Switcher in Footer -->
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500">{{ __('common.language') }}:</span>
                        <a href="{{ route('language.switch', 'en') }}" class="hover:text-white {{ app()->getLocale() === 'en' ? 'text-white font-semibold' : '' }}">
                            English
                        </a>
                        <span class="text-gray-600">|</span>
                        <a href="{{ route('language.switch', 'fr') }}" class="hover:text-white {{ app()->getLocale() === 'fr' ? 'text-white font-semibold' : '' }}">
                            Français
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
