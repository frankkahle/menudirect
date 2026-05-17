<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Server — {{ $site->business_name }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { surface: { DEFAULT: '#111111', card: '#1e1e1e', hover: '#252525' }, gold: '#d4a053' },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
            }}
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        html, body { overscroll-behavior: none; }
        .item-flash { animation: flash 0.3s ease-out; }
        @keyframes flash { 0% { background: rgba(245,158,11,0.3); } 100% { background: transparent; } }
    </style>
</head>
<body class="bg-surface font-sans text-gray-300 antialiased h-screen overflow-hidden" x-data="serverTablet()" x-init="init()">

    <!-- Header -->
    <header class="h-12 bg-surface border-b border-white/5 flex items-center justify-between px-4 flex-shrink-0">
        <div class="flex items-center gap-3">
            @if(!empty($demoMode))
            <span class="text-xs bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded font-semibold">DEMO</span>
            @endif
            <h1 class="text-white font-semibold text-sm">{{ $site->business_name }}</h1>
            <span class="text-gray-600 text-xs">Server Tablet</span>
        </div>
        <div class="flex items-center gap-3">
            @if(!empty($demoMode))
            <a href="{{ route('demo-kitchen.show', $site->slug) }}" target="_blank" class="text-xs text-gray-500 hover:text-gray-300 border border-white/10 px-2 py-1 rounded">Kitchen View</a>
            @else
            <span class="text-xs text-gray-500" x-text="staffName"></span>
            <a href="{{ route('staff.orders.live') }}" class="text-xs text-gray-500 hover:text-gray-300 border border-white/10 px-2 py-1 rounded">Kitchen</a>
            @endif
        </div>
    </header>

    <div class="flex h-[calc(100vh-3rem)]">

        <!-- LEFT: Active Tables / Open Orders -->
        <div class="w-48 bg-[#0a0a0a] border-r border-white/5 flex flex-col flex-shrink-0" x-show="!menuOpen">
            <div class="p-3 border-b border-white/5">
                <button @click="startNewOrder()" class="w-full py-2.5 bg-amber-500 hover:bg-amber-400 text-black font-bold rounded-lg transition text-sm">
                    + New Table
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                <template x-for="order in openOrders" :key="order.id">
                    <button @click="selectOpenOrder(order)"
                            class="w-full text-left p-2.5 rounded-lg transition text-sm"
                            :class="selectedTab?.id === order.id ? 'bg-amber-500/20 border border-amber-500/30' : 'bg-surface-card hover:bg-surface-hover border border-transparent'">
                        <div class="flex items-center justify-between">
                            <span class="text-white font-bold" x-text="'T' + (order.table_number || '?')"></span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded font-medium"
                                  :class="order.order_type === 'dine_in' ? 'bg-amber-500/20 text-amber-400' : 'bg-gray-500/20 text-gray-400'"
                                  x-text="order.order_type === 'dine_in' ? 'Dine-in' : order.order_type === 'pickup' ? 'Pickup' : 'Delivery'"></span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1" x-text="order.item_count + ' items — $' + order.total"></div>
                        <div class="text-[10px] text-gray-600 mt-0.5" x-text="order.status_label"></div>
                    </button>
                </template>
                <div x-show="openOrders.length === 0" class="text-center text-gray-700 text-xs py-6">No open orders</div>
            </div>
        </div>

        <!-- CENTER: Menu Browser (shown when adding items) -->
        <div class="flex-1 flex flex-col overflow-hidden" x-show="menuOpen || selectedTab">

            <!-- Category tabs -->
            <div class="bg-[#0a0a0a] border-b border-white/5 flex overflow-x-auto flex-shrink-0 px-2 gap-1 py-2" x-show="menuOpen">
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="activeCategory = cat.id"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition flex-shrink-0"
                            :class="activeCategory === cat.id ? 'bg-amber-500 text-black' : 'bg-surface-card text-gray-400 hover:text-white'">
                        <span x-text="cat.name"></span>
                    </button>
                </template>
            </div>

            <!-- Items grid -->
            <div class="flex-1 overflow-y-auto p-3" x-show="menuOpen">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                    <template x-for="item in activeItems" :key="item.id">
                        <button @click="addItem(item)"
                                class="bg-surface-card hover:bg-surface-hover active:bg-amber-500/10 border border-white/5 rounded-lg p-3 text-left transition">
                            <div class="text-white text-sm font-medium" x-text="item.name"></div>
                            <div class="text-amber-400 text-xs mt-1" x-text="item.formatted_price"></div>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Order detail view when a tab is selected but menu is closed -->
            <div class="flex-1 overflow-y-auto p-4" x-show="!menuOpen && selectedTab">
                <div class="max-w-md mx-auto">
                    <div class="text-center mb-6">
                        <div class="text-2xl font-bold text-white" x-text="'Table ' + (selectedTab?.table_number || '?')"></div>
                        <div class="text-sm text-gray-500" x-text="selectedTab?.order_number"></div>
                        <div class="text-xs mt-1" :class="selectedTab?.status === 'confirmed' ? 'text-blue-400' : selectedTab?.status === 'preparing' ? 'text-purple-400' : selectedTab?.status === 'ready' ? 'text-emerald-400' : 'text-gray-500'" x-text="selectedTab?.status_label"></div>
                    </div>
                    <div class="space-y-2 mb-4">
                        <template x-for="item in (selectedTab?.items || [])" :key="item.name + item.quantity">
                            <div class="flex justify-between py-2 border-b border-white/5">
                                <div><span class="text-gray-500 mr-1" x-text="item.quantity + 'x'"></span><span class="text-white" x-text="item.name"></span></div>
                                <div class="text-gray-400" x-text="'$' + item.total"></div>
                            </div>
                        </template>
                    </div>
                    <div class="flex justify-between font-semibold text-white border-t border-white/10 pt-2">
                        <span>Total</span>
                        <span x-text="'$' + (selectedTab?.total || '0.00')"></span>
                    </div>
                    <button @click="menuOpen = true" class="w-full mt-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-black font-semibold rounded-lg transition text-sm">
                        + Add More Items
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div class="flex-1 flex items-center justify-center" x-show="!menuOpen && !selectedTab">
                <div class="text-center text-gray-700">
                    <svg class="w-16 h-16 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="text-sm">Tap "+ New Table" to start an order</p>
                </div>
            </div>
        </div>

        <!-- RIGHT: Current Order Cart -->
        <div class="w-72 bg-surface-card border-l border-white/5 flex flex-col flex-shrink-0" x-show="menuOpen">

            <!-- Table number -->
            <div class="p-3 border-b border-white/5">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-500">Table</label>
                    <input type="text" x-model="currentTable" placeholder="#" maxlength="20"
                           class="flex-1 bg-surface border border-white/10 rounded px-2 py-1.5 text-white text-sm text-center font-bold focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <!-- Cart items -->
            <div class="flex-1 overflow-y-auto p-3 space-y-1">
                <template x-if="cartItems.length === 0">
                    <div class="text-gray-600 text-xs text-center py-8">Tap items to add</div>
                </template>
                <template x-for="(item, idx) in cartItems" :key="item.id + '-' + idx">
                    <div class="flex items-center justify-between bg-surface rounded-lg p-2 item-flash">
                        <div class="flex-1 min-w-0">
                            <div class="text-white text-xs truncate" x-text="item.name"></div>
                            <div class="text-[10px] text-gray-500" x-text="'$' + (item.price * item.quantity).toFixed(2)"></div>
                        </div>
                        <div class="flex items-center gap-1.5 ml-2">
                            <button @click="item.quantity > 1 ? item.quantity-- : cartItems.splice(idx, 1)" class="w-6 h-6 bg-white/5 hover:bg-white/10 rounded text-gray-400 text-xs flex items-center justify-center">-</button>
                            <span class="text-white text-xs w-3 text-center" x-text="item.quantity"></span>
                            <button @click="item.quantity++" class="w-6 h-6 bg-white/5 hover:bg-white/10 rounded text-gray-400 text-xs flex items-center justify-center">+</button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Cart footer -->
            <div class="p-3 border-t border-white/5 space-y-2">
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-gray-400">Subtotal</span>
                    <span class="text-white" x-text="'$' + cartSubtotal.toFixed(2)"></span>
                </div>
                <button @click="sendToKitchen()" :disabled="cartItems.length === 0 || !currentTable || sending"
                        class="w-full py-3 bg-amber-500 hover:bg-amber-400 disabled:opacity-40 disabled:cursor-not-allowed text-black font-bold rounded-lg transition text-sm">
                    <span x-show="!sending">Send to Kitchen</span>
                    <span x-show="sending">Sending...</span>
                </button>
                <button @click="cancelCurrentOrder()" class="w-full py-2 text-gray-600 hover:text-gray-400 text-xs transition">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="fixed top-14 right-4 z-50 space-y-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="bg-surface-card border rounded-lg px-4 py-3 text-sm shadow-lg max-w-xs"
                 :class="toast.type === 'error' ? 'border-red-500/30 text-red-400' : 'border-emerald-500/30 text-emerald-400'"
                 x-text="toast.message"
                 x-init="setTimeout(() => toasts = toasts.filter(t => t.id !== toast.id), 3000)">
            </div>
        </template>
    </div>

    <script>
    function serverTablet() {
        const demoMode = {{ !empty($demoMode) ? 'true' : 'false' }};
        const demoSlug = '{{ $site->slug }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        return {
            demoMode,
            staffName: '{{ $staff?->name ?? "Demo Server" }}',
            categories: [],
            activeCategory: null,
            openOrders: [],
            selectedTab: null,
            menuOpen: false,
            currentTable: '',
            cartItems: [],
            sending: false,
            toasts: [],
            pollInterval: null,

            init() {
                this.loadMenu();
                this.loadOpenOrders();
                this.pollInterval = setInterval(() => this.loadOpenOrders(), 5000);
            },

            async loadMenu() {
                const url = demoMode ? `/api/demo-kitchen/${demoSlug}/menu` : '/api/staff/menu';
                try {
                    const resp = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, credentials: 'same-origin' });
                    if (resp.ok) {
                        const data = await resp.json();
                        this.categories = data.categories;
                        if (this.categories.length > 0) this.activeCategory = this.categories[0].id;
                    }
                } catch (e) { console.error('Menu load failed:', e); }
            },

            async loadOpenOrders() {
                const url = demoMode
                    ? `/api/demo-kitchen/${demoSlug}/orders?status=active`
                    : '/api/staff/orders?status=active';
                try {
                    const resp = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, credentials: 'same-origin' });
                    if (resp.ok) {
                        const data = await resp.json();
                        // Only show dine-in orders on the server tablet
                        this.openOrders = data.orders.filter(o => o.order_type === 'dine_in');
                        // Update selected tab if it's still open
                        if (this.selectedTab) {
                            const updated = data.orders.find(o => o.id === this.selectedTab.id);
                            if (updated) this.selectedTab = updated;
                        }
                    }
                } catch (e) { console.error('Orders load failed:', e); }
            },

            get activeItems() {
                const cat = this.categories.find(c => c.id === this.activeCategory);
                return cat ? cat.items : [];
            },

            get cartSubtotal() {
                return this.cartItems.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            },

            startNewOrder() {
                this.selectedTab = null;
                this.currentTable = '';
                this.cartItems = [];
                this.menuOpen = true;
                if (this.categories.length > 0 && !this.activeCategory) {
                    this.activeCategory = this.categories[0].id;
                }
            },

            selectOpenOrder(order) {
                this.selectedTab = order;
                this.menuOpen = false;
                this.cartItems = [];
                this.currentTable = order.table_number || '';
            },

            addItem(item) {
                const existing = this.cartItems.find(i => i.id === item.id);
                if (existing) {
                    existing.quantity++;
                } else {
                    this.cartItems.push({ id: item.id, name: item.name, price: item.price, quantity: 1 });
                }
            },

            cancelCurrentOrder() {
                this.menuOpen = false;
                this.cartItems = [];
                this.currentTable = '';
            },

            async sendToKitchen() {
                if (!this.currentTable || this.cartItems.length === 0 || this.sending) return;
                this.sending = true;

                try {
                    // If adding to an existing open order
                    if (this.selectedTab) {
                        const url = demoMode
                            ? `/api/demo-kitchen/${demoSlug}/orders/${this.selectedTab.id}/add-items`
                            : `/api/staff/orders/${this.selectedTab.id}/add-items`;
                        const resp = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            credentials: 'same-origin',
                            body: JSON.stringify({ items: this.cartItems.map(i => ({ menu_item_id: i.id, quantity: i.quantity })) }),
                        });
                        if (!resp.ok) throw new Error((await resp.json()).error || 'Failed');
                        this.addToast(`Added ${this.cartItems.length} items to Table ${this.currentTable}`, 'success');
                    } else {
                        // New order
                        const url = demoMode
                            ? `/api/demo-kitchen/${demoSlug}/orders`
                            : '/api/staff/orders';
                        const resp = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                table_number: this.currentTable,
                                items: this.cartItems.map(i => ({ menu_item_id: i.id, quantity: i.quantity })),
                            }),
                        });
                        if (!resp.ok) throw new Error((await resp.json()).error || 'Failed');
                        const data = await resp.json();
                        this.addToast(`Table ${this.currentTable} — ${data.order.order_number} sent to kitchen`, 'success');
                    }

                    this.menuOpen = false;
                    this.cartItems = [];
                    this.loadOpenOrders();

                } catch (e) {
                    this.addToast(e.message, 'error');
                } finally {
                    this.sending = false;
                }
            },

            addToast(message, type) {
                const id = Date.now();
                this.toasts.push({ id, message, type });
            },
        };
    }
    </script>
</body>
</html>
