<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MenuDirect — Commission-Free Online Ordering for Canadian Restaurants</title>
    <meta name="description" content="Take orders directly from your customers. No commissions, no middlemen. Online ordering, reservations, and delivery management built for independent restaurants.">
    <meta name="robots" content="index, follow">

    <meta property="og:title" content="MenuDirect — Own Your Orders">
    <meta property="og:description" content="Commission-free online ordering for Canadian restaurants. No app fees. No middlemen.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://menudirect.ca">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        surface: { DEFAULT: '#1a1a1a', light: '#242424', lighter: '#2a2a2a' },
                        gold: { DEFAULT: '#d4a053', light: '#e0b97a', dark: '#b8863a' },
                    },
                    fontFamily: {
                        serif: ['"DM Serif Display"', 'Georgia', 'serif'],
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'MenuDirect',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'offers' => [
            ['@type' => 'Offer', 'name' => 'Basic', 'price' => '15.00', 'priceCurrency' => 'CAD'],
            ['@type' => 'Offer', 'name' => 'SiteFresh', 'price' => '35.00', 'priceCurrency' => 'CAD'],
            ['@type' => 'Offer', 'name' => 'SiteFresh Pro', 'price' => '59.00', 'priceCurrency' => 'CAD'],
            ['@type' => 'Offer', 'name' => 'MenuDirect Max', 'price' => '99.00', 'priceCurrency' => 'CAD'],
        ],
        'description' => 'Commission-free online ordering platform for Canadian restaurants.',
        'provider' => [
            '@type' => 'LocalBusiness',
            'name' => 'SOS Technical Services',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '11 Barbara Street',
                'addressLocality' => 'Hampton',
                'addressRegion' => 'NB',
                'postalCode' => 'E5N 5P3',
                'addressCountry' => 'CA',
            ],
            'telephone' => '(506) 910-5547',
        ],
    ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-surface font-sans text-gray-300 antialiased">

    <!-- Sticky Nav -->
    <nav class="fixed top-0 w-full z-50 bg-surface/90 backdrop-blur-md border-b border-white/5">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                @if(file_exists(public_path('images/menudirect/logo.png')))
                    <img src="/images/menudirect/logo.png" alt="MenuDirect" class="h-8">
                @endif
                <span class="text-lg font-bold text-white tracking-tight">MenuDirect</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="#features" class="text-gray-400 hover:text-white transition">Features</a>
                <a href="#pricing" class="text-gray-400 hover:text-white transition">Pricing</a>
                <a href="#demo" class="text-gray-400 hover:text-white transition">Live Demo</a>
                <a href="#contact" class="bg-gold hover:bg-gold-light text-surface font-semibold px-5 py-2 rounded-lg transition">Get Started</a>
            </div>
            <!-- Mobile menu button -->
            <button class="md:hidden text-gray-400" onclick="document.getElementById('mobile-nav').classList.toggle('hidden')">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <div id="mobile-nav" class="hidden md:hidden border-t border-white/5 bg-surface px-6 py-4 space-y-3">
            <a href="#features" class="block text-gray-400 hover:text-white">Features</a>
            <a href="#pricing" class="block text-gray-400 hover:text-white">Pricing</a>
            <a href="#demo" class="block text-gray-400 hover:text-white">Live Demo</a>
            <a href="#contact" class="block bg-gold text-surface font-semibold px-5 py-2 rounded-lg text-center">Get Started</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="relative min-h-screen flex items-center pt-16">
        <div class="absolute inset-0">
            <img src="/images/menudirect/hero-restaurant.jpg" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/40"></div>
        </div>
        <div class="relative max-w-6xl mx-auto px-6 py-24 md:py-32">
            <div class="max-w-2xl">
                <h1 class="font-serif text-4xl sm:text-5xl md:text-6xl text-white leading-tight mb-6">
                    Your restaurant deserves better than a 30% commission.
                </h1>
                <p class="text-lg md:text-xl text-gray-300 mb-10 leading-relaxed max-w-xl">
                    Take orders directly from your customers. Your website, your menu, your profits. No middlemen taking a cut of every order.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#pricing" class="bg-gold hover:bg-gold-light text-surface font-semibold px-8 py-3.5 rounded-lg transition text-lg">See Pricing</a>
                    <a href="#demo" class="border border-white/30 hover:border-white/60 text-white font-medium px-8 py-3.5 rounded-lg transition text-lg">View Live Demo</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Problem / Solution Strip -->
    <section class="bg-[#111111] border-y border-white/5">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <div class="grid md:grid-cols-3 gap-8 md:gap-12 text-center">
                <div>
                    <div class="font-serif text-5xl text-red-400 mb-2">30%</div>
                    <p class="text-gray-500 text-sm uppercase tracking-widest">What delivery apps take</p>
                </div>
                <div>
                    <div class="font-serif text-5xl text-gold mb-2">$0</div>
                    <p class="text-gray-500 text-sm uppercase tracking-widest">Commission with MenuDirect</p>
                </div>
                <div>
                    <div class="font-serif text-5xl text-emerald-400 mb-2">100%</div>
                    <p class="text-gray-500 text-sm uppercase tracking-widest">Of orders go directly to you</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-surface py-24 md:py-32">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="font-serif text-3xl md:text-4xl text-white mb-4">How it works</h2>
                <p class="text-gray-500 max-w-lg mx-auto">From sign-up to your first order in under 48 hours. We handle the tech so you can handle the food.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <div class="relative">
                    <div class="text-gold/30 font-serif text-7xl absolute -top-4 -left-2">01</div>
                    <div class="relative pt-12">
                        <h3 class="text-white font-semibold text-lg mb-3">We build your site</h3>
                        <p class="text-gray-400 leading-relaxed">A fast, mobile-first website on your own subdomain — or your custom domain. Your branding, your menu, ready to take orders.</p>
                    </div>
                </div>
                <div class="relative">
                    <div class="text-gold/30 font-serif text-7xl absolute -top-4 -left-2">02</div>
                    <div class="relative pt-12">
                        <h3 class="text-white font-semibold text-lg mb-3">Customers order direct</h3>
                        <p class="text-gray-400 leading-relaxed">Pickup and delivery ordering built in. Customers find you on Google, land on your site, and place orders without a third-party app.</p>
                    </div>
                </div>
                <div class="relative">
                    <div class="text-gold/30 font-serif text-7xl absolute -top-4 -left-2">03</div>
                    <div class="relative pt-12">
                        <h3 class="text-white font-semibold text-lg mb-3">You manage everything</h3>
                        <p class="text-gray-400 leading-relaxed">Orders appear on your dashboard in real time. Confirm, prep, and mark ready — from a tablet in the kitchen or your phone on the go.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="bg-[#111111] py-24 md:py-32">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="font-serif text-3xl md:text-4xl text-white mb-4">Everything you need to run orders in-house</h2>
                <p class="text-gray-500 max-w-xl mx-auto">No piecemeal tools. One platform that handles your website, menu, orders, and customer communication.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature cards -->
                <div class="bg-surface-light border border-white/5 rounded-lg p-6 hover:border-gold/20 transition">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Online Ordering</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Pickup and delivery with real-time order tracking. Customers order from their phone, you receive it instantly.</p>
                </div>

                <div class="bg-surface-light border border-white/5 rounded-lg p-6 hover:border-gold/20 transition">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Menu Management</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Update your menu, prices, and photos from your dashboard. Changes go live immediately — no waiting on a developer.</p>
                </div>

                <div class="bg-surface-light border border-white/5 rounded-lg p-6 hover:border-gold/20 transition">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Kitchen Display</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">A live order screen for tablets. New orders pop up with audio alerts. Tap to confirm, prep, and mark ready.</p>
                </div>

                <div class="bg-surface-light border border-white/5 rounded-lg p-6 hover:border-gold/20 transition">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Delivery Zones</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Set distance-based delivery areas with custom fees and minimums for each zone. Customers see their fee before checkout.</p>
                </div>

                <div class="bg-surface-light border border-white/5 rounded-lg p-6 hover:border-gold/20 transition">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Reservations</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Built-in table reservation system. Customers book online, you manage availability from the same dashboard.</p>
                </div>

                <div class="bg-surface-light border border-white/5 rounded-lg p-6 hover:border-gold/20 transition">
                    <div class="w-10 h-10 rounded-lg bg-gold/10 flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Online Payments</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Accept cards directly on your site via Stripe. Funds go to your bank account. No holding, no delays.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="bg-surface py-24 md:py-32">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="font-serif text-3xl md:text-4xl text-white mb-4">Straightforward pricing</h2>
                <p class="text-gray-500 max-w-lg mx-auto">Monthly plans with no commission on orders. Every plan includes SSL, hosting, and Canadian support.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- Basic -->
                <div class="bg-surface-light border border-white/5 rounded-lg p-6 flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-white font-semibold text-lg mb-1">Basic</h3>
                        <p class="text-gray-500 text-sm mb-4">Your menu online</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">$15</span>
                            <span class="text-gray-500">/mo</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">$99 one-time setup</p>
                    </div>
                    <ul class="space-y-3 mb-8 flex-1 text-sm">
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Mobile-first website</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Your subdomain + SSL</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Menu + contact info</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Managed by us</li>
                    </ul>
                    <a href="#contact" class="block text-center border border-white/10 hover:border-white/30 text-gray-400 hover:text-white px-5 py-3 rounded-lg transition text-sm font-medium">Get Started</a>
                </div>

                <!-- SiteFresh -->
                <div class="bg-surface-light border border-white/5 rounded-lg p-6 flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-white font-semibold text-lg mb-1">SiteFresh</h3>
                        <p class="text-gray-500 text-sm mb-4">Self-service dashboard</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">$35</span>
                            <span class="text-gray-500">/mo</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">$199 one-time setup</p>
                    </div>
                    <ul class="space-y-3 mb-8 flex-1 text-sm">
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Everything in Basic</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Client dashboard</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Edit menu + photos</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Custom domain option</li>
                    </ul>
                    <a href="#contact" class="block text-center border border-white/10 hover:border-white/30 text-gray-400 hover:text-white px-5 py-3 rounded-lg transition text-sm font-medium">Get Started</a>
                </div>

                <!-- SiteFresh Pro -->
                <div class="bg-surface-light border border-white/5 rounded-lg p-6 flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-white font-semibold text-lg mb-1">SiteFresh Pro</h3>
                        <p class="text-gray-500 text-sm mb-4">Online ordering</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-white">$59</span>
                            <span class="text-gray-500">/mo</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">$299 one-time setup</p>
                    </div>
                    <ul class="space-y-3 mb-8 flex-1 text-sm">
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Everything in SiteFresh</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-white font-medium">Commission-free ordering</span></li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Order dashboard + kitchen display</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Email notifications</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Card payments (+$10/mo, 1% fee)</li>
                    </ul>
                    <a href="#contact" class="block text-center border border-white/10 hover:border-white/30 text-gray-400 hover:text-white px-5 py-3 rounded-lg transition text-sm font-medium">Get Started</a>
                </div>

                <!-- MenuDirect Max -->
                <div class="bg-surface-light border-2 border-gold/40 rounded-lg p-6 flex flex-col relative">
                    <div class="absolute -top-3 left-6">
                        <span class="bg-gold text-surface text-xs font-bold px-3 py-1 rounded">Best Value</span>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-white font-semibold text-lg mb-1">MenuDirect Max</h3>
                        <p class="text-gray-500 text-sm mb-4">The full platform</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-bold text-gold">$99</span>
                            <span class="text-gray-500">/mo</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">$399 one-time setup</p>
                    </div>
                    <ul class="space-y-3 mb-8 flex-1 text-sm">
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Everything in Pro</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-white font-medium">Delivery zone management</span></li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><span class="text-white font-medium">Built-in reservations</span></li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Email + SMS confirmations</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Card payments included (1% fee)</li>
                        <li class="flex gap-2 text-gray-400"><svg class="w-4 h-4 text-gold flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Dedicated onboarding</li>
                    </ul>
                    <a href="#contact" class="block text-center bg-gold hover:bg-gold-light text-surface font-semibold px-5 py-3 rounded-lg transition text-sm">Get Started</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Demo -->
    <section id="demo" class="relative py-24 md:py-32">
        <div class="absolute inset-0">
            <img src="/images/menudirect/food-plated.jpg" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-black/70"></div>
        </div>
        <div class="relative max-w-6xl mx-auto px-6 text-center">
            <h2 class="font-serif text-3xl md:text-4xl text-white mb-4">See it for yourself</h2>
            <p class="text-gray-400 max-w-lg mx-auto mb-10">Browse a working restaurant site built on MenuDirect. Place a test order, explore the menu, check hours — everything works.</p>
            <a href="https://demo-bistro.menudirect.ca" target="_blank" class="inline-flex items-center gap-3 bg-gold hover:bg-gold-light text-surface font-semibold px-8 py-4 rounded-lg transition text-lg">
                Open Demo Site
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
    </section>

    <!-- Trust / About -->
    <section class="bg-[#111111] py-20">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="font-serif text-2xl md:text-3xl text-white mb-6">Built and supported locally</h2>
            <p class="text-gray-400 leading-relaxed mb-8">
                I'm Frank Kahle. I've been building technology infrastructure for 45 years — ISPs, managed hosting, AI systems. MenuDirect is how I think restaurant tech should work: simple, honest, and owned by the people who use it. When you call, I answer.
            </p>
            <div class="flex flex-wrap justify-center gap-8 text-sm text-gray-500">
                <a href="tel:+15069105547" class="hover:text-gold transition">(506) 910-5547</a>
                <span class="hidden md:inline text-gray-700">&middot;</span>
                <a href="tel:+18663497518" class="hover:text-gold transition">866.349.7518 (toll-free)</a>
                <span class="hidden md:inline text-gray-700">&middot;</span>
                <span>Hampton, NB</span>
            </div>
        </div>
    </section>

    <!-- Social Proof Placeholder -->
    <section class="bg-surface border-y border-white/5 py-16">
        <div class="max-w-6xl mx-auto px-6 text-center">
            <p class="text-gray-600 text-sm uppercase tracking-widest">Trusted by restaurants across Canada</p>
        </div>
    </section>

    <!-- Contact / Lead Form -->
    <section id="contact" class="bg-[#111111] py-24 md:py-32">
        <div class="max-w-2xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="font-serif text-3xl md:text-4xl text-white mb-4">Get your free demo</h2>
                <p class="text-gray-500">Tell me about your restaurant and I'll build a working demo site within 48 hours. No payment, no obligation.</p>
            </div>

            @if(session('success'))
            <div class="bg-surface-light border border-gold/30 rounded-lg p-8 text-center mb-8">
                <h3 class="text-xl font-semibold text-white mb-2">Request received</h3>
                <p class="text-gray-400">{{ session('success') }}</p>
                <p class="text-sm text-gray-600 mt-3">I'll be in touch within 48 hours.</p>
            </div>
            @endif

            <form action="{{ route('menudirect.lead') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Honeypot — humans skip, bots fill --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website (leave blank)</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                {{-- Time-based bot detection --}}
                <input type="hidden" name="_form_token" value="{{ base64_encode(time()) }}">

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label for="restaurant_name" class="block text-sm text-gray-500 mb-1.5">Restaurant Name</label>
                        <input type="text" name="restaurant_name" id="restaurant_name" required
                               class="w-full px-4 py-3 bg-surface border border-white/10 rounded-lg text-white placeholder-gray-600 focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition"
                               placeholder="Your restaurant">
                    </div>
                    <div>
                        <label for="contact_name" class="block text-sm text-gray-500 mb-1.5">Your Name</label>
                        <input type="text" name="contact_name" id="contact_name" required
                               class="w-full px-4 py-3 bg-surface border border-white/10 rounded-lg text-white placeholder-gray-600 focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition"
                               placeholder="Full name">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label for="email" class="block text-sm text-gray-500 mb-1.5">Email</label>
                        <input type="email" name="email" id="email" required
                               class="w-full px-4 py-3 bg-surface border border-white/10 rounded-lg text-white placeholder-gray-600 focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition"
                               placeholder="you@email.com">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm text-gray-500 mb-1.5">Phone <span class="text-gray-700">(optional)</span></label>
                        <input type="tel" name="phone" id="phone"
                               class="w-full px-4 py-3 bg-surface border border-white/10 rounded-lg text-white placeholder-gray-600 focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition"
                               placeholder="(506) 555-1234">
                    </div>
                </div>
                <div>
                    <label for="message" class="block text-sm text-gray-500 mb-1.5">Tell me about your restaurant <span class="text-gray-700">(optional)</span></label>
                    <textarea name="message" id="message" rows="3"
                              class="w-full px-4 py-3 bg-surface border border-white/10 rounded-lg text-white placeholder-gray-600 focus:border-gold focus:ring-1 focus:ring-gold focus:outline-none transition resize-none"
                              placeholder="Type of cuisine, current ordering setup, what you're looking for..."></textarea>
                </div>
                @if(\App\Services\TurnstileVerifier::isConfigured())
                    <div class="cf-turnstile" data-sitekey="{{ \App\Services\TurnstileVerifier::siteKey() }}" data-theme="dark"></div>
                @endif

                <button type="submit" class="w-full sm:w-auto bg-gold hover:bg-gold-light text-surface font-semibold px-10 py-3.5 rounded-lg transition text-lg">
                    Request My Demo
                </button>
                <p class="text-xs text-gray-600">No credit card required. I'll personally review your request and build your demo.</p>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-surface border-t border-white/5 py-12">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-2">
                    <span class="text-lg font-bold text-white tracking-tight">MenuDirect</span>
                </div>
                <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-600">
                    <a href="#features" class="hover:text-gray-400 transition">Features</a>
                    <a href="#pricing" class="hover:text-gray-400 transition">Pricing</a>
                    <a href="#demo" class="hover:text-gray-400 transition">Demo</a>
                    <a href="tel:+15069105547" class="hover:text-gray-400 transition">(506) 910-5547</a>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-white/5 text-center text-xs text-gray-700">
                &copy; {{ date('Y') }} SOS Technical Services &middot; Hampton, NB, Canada
            </div>
        </div>
    </footer>

    @if(\App\Services\TurnstileVerifier::isConfigured())
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
</body>
</html>
