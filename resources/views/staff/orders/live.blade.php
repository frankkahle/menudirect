<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Orders — {{ $site->business_name }}</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        surface: { DEFAULT: '#111111', card: '#1e1e1e', hover: '#252525' },
                        gold: '#d4a053',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        html, body { overscroll-behavior: none; -webkit-user-select: none; user-select: none; }
        @keyframes pulse-amber { 0%, 100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.4); } 50% { box-shadow: 0 0 0 8px rgba(245,158,11,0); } }
        .new-order-pulse { animation: pulse-amber 1.5s ease-in-out 3; }
        .order-enter { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-surface font-sans text-gray-300 antialiased h-screen overflow-hidden" x-data="orderScreen()" x-init="init()">

    <!-- Header -->
    <header class="h-14 bg-surface border-b border-white/5 flex items-center justify-between px-4 flex-shrink-0">
        <div class="flex items-center gap-3">
            @if(!empty($demoMode))
            <a href="{{ $site->getPublicUrl() }}" target="_blank" class="text-gray-600 hover:text-gray-400 transition" title="Back to restaurant site">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
            @else
            <a href="{{ route('staff.dashboard') }}" class="text-gray-600 hover:text-gray-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            @endif
            <h1 class="text-white font-semibold text-sm truncate">{{ $site->business_name }}</h1>
            @if(!empty($demoMode))
            <span class="text-xs bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded font-semibold">DEMO</span>
            @endif
            <!-- Connection indicator -->
            <div class="flex items-center gap-1.5" :class="connectionClass">
                <div class="w-2 h-2 rounded-full" :class="connectionDotClass"></div>
                <span class="text-xs" x-text="connectionLabel"></span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            @if(empty($demoMode))
            <!-- Server Tablet link -->
            <a href="{{ route('staff.server') }}" class="flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-black font-semibold px-3 py-1.5 rounded-lg transition text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Server Tablet
            </a>
            @endif
            <!-- Active order count -->
            <div class="text-xs text-gray-500">
                <span class="text-white font-semibold text-sm" x-text="activeOrders.length"></span> active
            </div>
            <!-- Filter tabs -->
            <div class="flex bg-surface-card rounded-lg p-0.5 gap-0.5">
                <button @click="filter = 'active'" class="px-3 py-1 text-xs rounded-md transition" :class="filter === 'active' ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-gray-400'">Active</button>
                <button @click="filter = 'all'" class="px-3 py-1 text-xs rounded-md transition" :class="filter === 'all' ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-gray-400'">All</button>
                <button @click="filter = 'completed'" class="px-3 py-1 text-xs rounded-md transition" :class="filter === 'completed' ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-gray-400'">Done</button>
            </div>
            @if(empty($demoMode))
            <!-- Close Shift -->
            <button @click="openCloseShift()" class="text-gray-500 hover:text-gray-300 transition text-xs border border-white/10 px-2 py-1 rounded">
                Close Shift
            </button>
            @endif
            <!-- Sound toggle -->
            <button @click="soundEnabled = !soundEnabled" class="text-gray-600 hover:text-gray-400 transition">
                <svg x-show="soundEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                <svg x-show="!soundEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
            </button>
        </div>
    </header>

    <div class="flex h-[calc(100vh-3.5rem)]">
        <!-- Order List -->
        <div class="flex-1 overflow-y-auto p-3 space-y-2" :class="selectedOrder ? 'hidden md:block md:w-2/3 lg:w-[70%]' : 'w-full'">
            <template x-if="filteredOrders.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-gray-600">
                    <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="text-sm">No orders</p>
                </div>
            </template>

            <template x-for="order in filteredOrders" :key="order.id">
                <div @click="selectOrder(order)"
                     class="bg-surface-card rounded-lg p-4 cursor-pointer transition border-l-4 hover:bg-surface-hover order-enter"
                     :class="[
                         statusBorderColor(order.status),
                         selectedOrder?.id === order.id ? 'ring-1 ring-white/20' : '',
                         order._isNew ? 'new-order-pulse' : ''
                     ]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-white font-semibold text-sm" x-text="order.order_number"></span>
                                <span class="text-xs px-1.5 py-0.5 rounded font-medium"
                                      :class="statusBadgeClass(order.status)"
                                      x-text="order.status_label"></span>
                                <span class="text-xs px-1.5 py-0.5 rounded font-medium"
                                      :class="order.order_type === 'delivery' ? 'bg-blue-500/20 text-blue-400' : order.order_type === 'dine_in' ? 'bg-amber-500/20 text-amber-400' : 'bg-gray-500/20 text-gray-400'"
                                      x-text="order.order_type_label"></span>
                                <span x-show="order.table_number" class="text-xs px-1.5 py-0.5 rounded font-bold bg-white/10 text-white" x-text="'T' + order.table_number"></span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-500">
                                <span x-text="order.customer_name"></span>
                                <span>&middot;</span>
                                <span x-text="order.item_count + ' item' + (order.item_count !== 1 ? 's' : '')"></span>
                                <span>&middot;</span>
                                <span>$<span x-text="order.total"></span></span>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-xs font-medium" :class="getElapsedMinutes(order.created_at) > 10 && order.status === 'pending' ? 'text-red-400' : 'text-gray-500'"
                                 x-text="formatElapsed(order.created_at)"></div>
                            <div class="text-[10px] text-gray-600" x-text="formatTime(order.created_at)"></div>
                            <div x-show="order.payment_status === 'paid'" class="text-xs text-emerald-500 mt-0.5">Paid</div>
                        </div>
                    </div>
                    <div x-show="order.special_instructions" class="mt-2 text-xs text-amber-400/80 truncate" x-text="order.special_instructions"></div>
                </div>
            </template>
        </div>

        <!-- Detail Panel (side on landscape, bottom sheet on portrait) -->
        <template x-if="selectedOrder">
            <!-- Landscape: side panel -->
            <div class="hidden md:flex md:w-1/3 lg:w-[30%] bg-surface-card border-l border-white/5 flex-col">
                <div x-html="detailPanelContent()"></div>
            </div>
        </template>

        <!-- Portrait: bottom sheet -->
        <div x-show="selectedOrder && isMobileView" x-cloak
             class="md:hidden fixed inset-0 z-50"
             @click.self="selectedOrder = null">
            <div class="absolute inset-0 bg-black/50" @click="selectedOrder = null"></div>
            <div class="absolute bottom-0 left-0 right-0 bg-surface-card rounded-t-2xl max-h-[85vh] flex flex-col"
                 @click.stop>
                <div class="w-10 h-1 bg-gray-700 rounded-full mx-auto mt-3 mb-1"></div>
                <div class="overflow-y-auto flex-1" x-html="detailPanelContent()"></div>
            </div>
        </div>
    </div>

    <!-- Forced Alert Modal -->
    <div x-show="alertOrder" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4">
        <div class="bg-surface-card rounded-xl shadow-2xl max-w-md w-full p-6 border border-amber-500/30">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-amber-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <h3 class="text-xl font-bold text-white">New Order</h3>
                <p class="text-gray-400 text-sm mt-1" x-text="alertOrder ? '#' + alertOrder.order_number + ' — ' + alertOrder.customer_name : ''"></p>
                <p class="text-lg font-semibold text-white mt-2" x-text="alertOrder ? alertOrder.item_count + ' items — $' + alertOrder.total : ''"></p>
                <p class="text-xs mt-1" :class="alertOrder?.order_type === 'delivery' ? 'text-blue-400' : 'text-gray-500'" x-text="alertOrder?.order_type_label"></p>
            </div>
            <button @click="dismissAlert()"
                    class="w-full py-3.5 bg-amber-500 hover:bg-amber-400 text-black font-bold rounded-lg transition text-lg">
                Got it
            </button>
        </div>
    </div>

    <!-- Toast notifications -->
    <div class="fixed top-16 right-4 z-50 space-y-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="bg-surface-card border rounded-lg px-4 py-3 text-sm shadow-lg max-w-xs"
                 :class="toast.type === 'error' ? 'border-red-500/30 text-red-400' : 'border-emerald-500/30 text-emerald-400'"
                 x-text="toast.message"
                 x-init="setTimeout(() => toasts = toasts.filter(t => t.id !== toast.id), 3000)">
            </div>
        </template>
    </div>

    <!-- New Dine-in Order Overlay -->
    <!-- Close Shift Overlay -->
    @if(empty($demoMode))
    <div x-show="shiftOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center bg-black/70 p-4">
        <div class="bg-surface-card rounded-xl shadow-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-white font-bold text-lg">Close Shift</h2>
                <button @click="shiftOpen = false" class="text-gray-500 hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="shiftData">
                <div>
                    <div class="text-sm text-gray-500 mb-4" x-text="'Shift for ' + shiftData.date"></div>

                    <div x-show="shiftData.already_closed" class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-3 mb-4 text-amber-400 text-sm">
                        This shift has already been closed.
                    </div>

                    <div x-show="shiftData.has_active_orders" class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 mb-4 text-red-400 text-sm">
                        <span x-text="shiftData.active_count"></span> orders are still active. Ready orders will be auto-completed on close.
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-surface rounded-lg p-3">
                                <div class="text-xs text-gray-500">Total Orders</div>
                                <div class="text-xl font-bold text-white" x-text="shiftData.summary.total_orders"></div>
                            </div>
                            <div class="bg-surface rounded-lg p-3">
                                <div class="text-xs text-gray-500">Cancelled</div>
                                <div class="text-xl font-bold text-red-400" x-text="shiftData.summary.cancelled"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="bg-surface rounded-lg p-3">
                                <div class="text-xs text-gray-500">Dine-in</div>
                                <div class="text-white font-semibold" x-text="shiftData.summary.dine_in"></div>
                            </div>
                            <div class="bg-surface rounded-lg p-3">
                                <div class="text-xs text-gray-500">Pickup</div>
                                <div class="text-white font-semibold" x-text="shiftData.summary.pickup"></div>
                            </div>
                            <div class="bg-surface rounded-lg p-3">
                                <div class="text-xs text-gray-500">Delivery</div>
                                <div class="text-white font-semibold" x-text="shiftData.summary.delivery"></div>
                            </div>
                        </div>

                        <div class="bg-surface rounded-lg p-4 space-y-2">
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Gross Sales</span><span class="text-white" x-text="'$' + shiftData.summary.gross_sales"></span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Tax Collected</span><span class="text-white" x-text="'$' + shiftData.summary.tax_collected"></span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-500">Delivery Fees</span><span class="text-white" x-text="'$' + shiftData.summary.delivery_fees"></span></div>
                            <div class="flex justify-between text-sm font-semibold border-t border-white/10 pt-2"><span class="text-gray-300">Total Revenue</span><span class="text-emerald-400" x-text="'$' + shiftData.summary.total_revenue"></span></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-surface rounded-lg p-3 border border-white/5">
                                <div class="text-xs text-gray-500">Cash / Pay-at-counter</div>
                                <div class="text-lg font-bold text-amber-400" x-text="'$' + shiftData.summary.cash_total"></div>
                            </div>
                            <div class="bg-surface rounded-lg p-3 border border-white/5">
                                <div class="text-xs text-gray-500">Card (Online)</div>
                                <div class="text-lg font-bold text-emerald-400" x-text="'$' + shiftData.summary.card_total"></div>
                            </div>
                        </div>
                    </div>

                    <div x-show="!shiftData.already_closed" class="space-y-3">
                        <textarea x-model="shiftNotes" placeholder="Shift notes (optional)..." rows="2"
                                  class="w-full bg-surface border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-600 text-sm focus:border-amber-500 focus:outline-none resize-none"></textarea>
                        <button @click="submitCloseShift()" :disabled="shiftClosing"
                                class="w-full py-3 bg-red-600 hover:bg-red-500 disabled:opacity-40 text-white font-bold rounded-lg transition">
                            <span x-show="!shiftClosing">Close Shift &amp; Save Report</span>
                            <span x-show="shiftClosing">Closing...</span>
                        </button>
                    </div>
                </div>
            </template>

            <div x-show="!shiftData" class="text-gray-500 text-center py-8">Loading shift data...</div>
        </div>
    </div>
    @endif

    @if(empty($demoMode))
    <div x-show="newOrderOpen" x-cloak class="fixed inset-0 z-[80] flex">
        <!-- Left: Menu Browser -->
        <div class="flex-1 bg-surface overflow-y-auto p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white font-bold text-lg">New Dine-in Order</h2>
                <button @click="closeNewOrder()" class="text-gray-500 hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Table Number -->
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase tracking-wide mb-1 block">Table</label>
                <input type="text" x-model="newOrderTable" placeholder="Table number..." maxlength="20"
                       class="w-full bg-surface-card border border-white/10 rounded-lg px-3 py-2 text-white placeholder-gray-600 focus:border-amber-500 focus:outline-none text-sm">
            </div>

            <!-- Menu Categories + Items -->
            <template x-for="cat in menuCategories" :key="cat.id">
                <div class="mb-4">
                    <h3 class="text-amber-400 font-semibold text-sm uppercase tracking-wide mb-2" x-text="cat.name"></h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <template x-for="item in cat.items" :key="item.id">
                            <button @click="addToNewOrder(item)"
                                    class="bg-surface-card hover:bg-surface-hover border border-white/5 rounded-lg p-3 text-left transition">
                                <div class="text-white text-sm font-medium truncate" x-text="item.name"></div>
                                <div class="text-amber-400 text-xs mt-0.5" x-text="item.formatted_price"></div>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <div x-show="menuCategories.length === 0" class="text-gray-600 text-center py-8">Loading menu...</div>
        </div>

        <!-- Right: Order Summary -->
        <div class="w-80 bg-surface-card border-l border-white/5 flex flex-col">
            <div class="p-4 border-b border-white/5">
                <div class="flex items-center justify-between">
                    <h3 class="text-white font-semibold">Order</h3>
                    <span class="text-xs text-gray-500" x-show="newOrderTable" x-text="'Table ' + newOrderTable"></span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <template x-if="newOrderItems.length === 0">
                    <div class="text-gray-600 text-sm text-center py-8">Tap menu items to add</div>
                </template>
                <template x-for="(item, idx) in newOrderItems" :key="idx">
                    <div class="flex items-center justify-between bg-surface rounded-lg p-2">
                        <div class="flex-1 min-w-0">
                            <div class="text-white text-sm truncate" x-text="item.name"></div>
                            <div class="text-xs text-gray-500" x-text="'$' + (item.price * item.quantity).toFixed(2)"></div>
                        </div>
                        <div class="flex items-center gap-2 ml-2">
                            <button @click="item.quantity > 1 ? item.quantity-- : newOrderItems.splice(idx, 1)" class="w-7 h-7 bg-white/5 hover:bg-white/10 rounded text-gray-400 text-sm flex items-center justify-center">-</button>
                            <span class="text-white text-sm w-4 text-center" x-text="item.quantity"></span>
                            <button @click="item.quantity++" class="w-7 h-7 bg-white/5 hover:bg-white/10 rounded text-gray-400 text-sm flex items-center justify-center">+</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-4 border-t border-white/5 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="text-white" x-text="'$' + newOrderSubtotal.toFixed(2)"></span>
                </div>
                <input type="text" x-model="newOrderNotes" placeholder="Special instructions..." maxlength="1000"
                       class="w-full bg-surface border border-white/10 rounded px-3 py-2 text-white placeholder-gray-600 text-xs focus:border-amber-500 focus:outline-none">
                <button @click="submitNewOrder()" :disabled="newOrderItems.length === 0 || !newOrderTable || newOrderSubmitting"
                        class="w-full py-3 bg-amber-500 hover:bg-amber-400 disabled:opacity-40 disabled:cursor-not-allowed text-black font-bold rounded-lg transition">
                    <span x-show="!newOrderSubmitting">Send to Kitchen</span>
                    <span x-show="newOrderSubmitting">Sending...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <script>
    function orderScreen() {
        return {
            orders: [],
            selectedOrder: null,
            alertOrder: null,
            filter: 'active',
            soundEnabled: true,
            connected: true,
            connecting: false,
            toasts: [],
            pollInterval: null,
            alertInterval: null,
            lastPoll: null,
            alertMode: '{{ $alertMode }}',
            demoMode: {{ !empty($demoMode) ? 'true' : 'false' }},
            demoSlug: '{{ $site->slug }}',
            audioCtx: null,
            isMobileView: window.innerWidth < 768,
            knownOrderIds: new Set(),
            // New dine-in order state
            newOrderOpen: false,
            newOrderTable: '',
            newOrderItems: [],
            newOrderNotes: '',
            newOrderSubmitting: false,
            menuCategories: [],
            menuLoaded: false,
            // Shift closeout state
            shiftOpen: false,
            shiftData: null,
            shiftNotes: '',
            shiftClosing: false,

            init() {
                this.fetchOrders();
                this.pollInterval = setInterval(() => this.fetchOrders(), 5000);
                window.addEventListener('resize', () => {
                    this.isMobileView = window.innerWidth < 768;
                });
                // Enable audio on first user interaction (browser policy)
                document.addEventListener('click', () => {
                    if (!this.audioCtx) {
                        this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    }
                }, { once: true });
            },

            async fetchOrders() {
                try {
                    const params = new URLSearchParams({ status: this.filter });
                    if (this.lastPoll) params.set('since', this.lastPoll);

                    const apiUrl = this.demoMode
                        ? `/api/demo-kitchen/${this.demoSlug}/orders?${params}`
                        : `/api/staff/orders?${params}`;

                    const resp = await fetch(apiUrl, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        credentials: 'same-origin',
                    });

                    if (!resp.ok) throw new Error('HTTP ' + resp.status);

                    const data = await resp.json();
                    this.connected = true;
                    this.connecting = false;

                    if (this.lastPoll && data.orders.length > 0) {
                        // Incremental update — merge
                        data.orders.forEach(incoming => {
                            const idx = this.orders.findIndex(o => o.id === incoming.id);
                            if (idx >= 0) {
                                this.orders[idx] = incoming;
                            } else {
                                incoming._isNew = true;
                                this.orders.unshift(incoming);
                                this.onNewOrder(incoming);
                            }
                        });
                    } else if (!this.lastPoll) {
                        // Initial full load
                        this.orders = data.orders;
                        data.orders.forEach(o => this.knownOrderIds.add(o.id));
                    }

                    this.lastPoll = data.server_time;

                    // Update selected order if it was refreshed
                    if (this.selectedOrder) {
                        const updated = this.orders.find(o => o.id === this.selectedOrder.id);
                        if (updated) this.selectedOrder = updated;
                    }

                } catch (e) {
                    this.connected = false;
                    this.connecting = true;
                    console.error('Poll failed:', e);
                }
            },

            onNewOrder(order) {
                if (!this.knownOrderIds.has(order.id)) {
                    this.knownOrderIds.add(order.id);
                    this.playChime();

                    if (this.alertMode === 'forced') {
                        this.alertOrder = order;
                        this.startAlertRepeat();
                    }
                }
            },

            dismissAlert() {
                this.alertOrder = null;
                this.stopAlertRepeat();
            },

            startAlertRepeat() {
                this.stopAlertRepeat();
                this.alertInterval = setInterval(() => {
                    if (this.alertOrder) this.playChime();
                }, 15000);
            },

            stopAlertRepeat() {
                if (this.alertInterval) {
                    clearInterval(this.alertInterval);
                    this.alertInterval = null;
                }
            },

            playChime() {
                if (!this.soundEnabled || !this.audioCtx) return;
                // Simple two-tone chime via Web Audio API
                const ctx = this.audioCtx;
                const now = ctx.currentTime;

                [[880, now, 0.3], [1100, now + 0.15, 0.3]].forEach(([freq, start, dur]) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.3, start);
                    gain.gain.exponentialRampToValueAtTime(0.001, start + dur);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(start);
                    osc.stop(start + dur);
                });
            },

            selectOrder(order) {
                this.selectedOrder = this.selectedOrder?.id === order.id ? null : order;
            },

            processingAction: false,

            async performAction(action, reason) {
                if (!this.selectedOrder || this.processingAction) return;
                this.processingAction = true;
                const orderId = this.selectedOrder.id;
                const prevStatus = this.selectedOrder.status;

                // Optimistic update
                const actionMap = { confirm: 'confirmed', preparing: 'preparing', ready: 'ready', complete: 'completed', cancel: 'cancelled' };
                const labelMap = { confirm: 'Confirmed', preparing: 'Preparing', ready: 'Ready', complete: 'Completed', cancel: 'Cancelled' };
                this.selectedOrder.status = actionMap[action];
                this.selectedOrder.status_label = labelMap[action];

                const orderInList = this.orders.find(o => o.id === orderId);
                if (orderInList) {
                    orderInList.status = actionMap[action];
                    orderInList.status_label = labelMap[action];
                }

                try {
                    const body = { action };
                    if (reason) body.reason = reason;

                    const statusUrl = this.demoMode
                        ? `/api/demo-kitchen/${this.demoSlug}/orders/${orderId}/status`
                        : `/api/staff/orders/${orderId}/status`;

                    const resp = await fetch(statusUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(body),
                    });

                    if (!resp.ok) {
                        throw new Error((await resp.json()).error || 'Action failed');
                    }

                    this.addToast('Order updated', 'success');

                    // If completed/cancelled, close detail panel and refresh
                    if (['completed', 'cancelled'].includes(actionMap[action])) {
                        this.selectedOrder = null;
                        this.lastPoll = null;
                        this.fetchOrders();
                    }

                } catch (e) {
                    // Revert optimistic update
                    if (this.selectedOrder) this.selectedOrder.status = prevStatus;
                    if (orderInList) orderInList.status = prevStatus;
                    this.addToast(e.message, 'error');
                } finally {
                    this.processingAction = false;
                }
            },

            addToast(message, type) {
                const id = Date.now();
                this.toasts.push({ id, message, type });
            },

            get filteredOrders() {
                return this.orders.sort((a, b) => {
                    // Pending first, then by creation time (oldest first within pending)
                    const statusPriority = { pending: 0, confirmed: 1, preparing: 2, ready: 3, completed: 4, cancelled: 5 };
                    const pa = statusPriority[a.status] ?? 9;
                    const pb = statusPriority[b.status] ?? 9;
                    if (pa !== pb) return pa - pb;
                    // Within same status: oldest first (most urgent)
                    return new Date(a.created_at) - new Date(b.created_at);
                });
            },

            get activeOrders() {
                return this.orders.filter(o => ['pending', 'confirmed', 'preparing', 'ready'].includes(o.status));
            },

            get connectionClass() {
                if (this.connected) return 'text-emerald-500';
                if (this.connecting) return 'text-amber-500';
                return 'text-red-500';
            },
            get connectionDotClass() {
                if (this.connected) return 'bg-emerald-500';
                if (this.connecting) return 'bg-amber-500 animate-pulse';
                return 'bg-red-500';
            },
            get connectionLabel() {
                if (this.connected) return 'Live';
                if (this.connecting) return 'Reconnecting...';
                return 'Offline';
            },

            statusBorderColor(status) {
                return {
                    pending: 'border-l-amber-500',
                    confirmed: 'border-l-blue-500',
                    preparing: 'border-l-purple-500',
                    ready: 'border-l-emerald-500',
                    completed: 'border-l-gray-600',
                    cancelled: 'border-l-red-500',
                }[status] || 'border-l-gray-600';
            },

            statusBadgeClass(status) {
                return {
                    pending: 'bg-amber-500/20 text-amber-400',
                    confirmed: 'bg-blue-500/20 text-blue-400',
                    preparing: 'bg-purple-500/20 text-purple-400',
                    ready: 'bg-emerald-500/20 text-emerald-400',
                    completed: 'bg-gray-500/20 text-gray-500',
                    cancelled: 'bg-red-500/20 text-red-400',
                }[status] || 'bg-gray-500/20 text-gray-500';
            },

            getElapsedMinutes(isoDate) {
                return Math.floor((Date.now() - new Date(isoDate).getTime()) / 60000);
            },

            formatElapsed(isoDate) {
                const mins = this.getElapsedMinutes(isoDate);
                if (mins < 1) return 'Just now';
                if (mins < 60) return mins + 'm';
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                if (h >= 24) return Math.floor(h / 24) + 'd ' + (h % 24) + 'h';
                return m > 0 ? h + 'h ' + m + 'm' : h + 'h';
            },

            formatTime(isoDate) {
                const d = new Date(isoDate);
                return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            },

            detailPanelContent() {
                const o = this.selectedOrder;
                if (!o) return '';

                const items = (o.items || []).map(i => `
                    <div class="flex justify-between py-2 border-b border-white/5">
                        <div class="flex-1">
                            <div class="text-white text-sm"><span class="text-gray-500 mr-1">${i.quantity}x</span> ${this.escHtml(i.name)}</div>
                            ${i.special_requests ? `<div class="text-xs text-amber-400/70 mt-0.5">${this.escHtml(i.special_requests)}</div>` : ''}
                        </div>
                        <div class="text-sm text-gray-400 ml-3">$${i.total}</div>
                    </div>
                `).join('');

                const actions = this.getActions(o.status);
                const actionBtns = actions.map(a => {
                    if (a.action === 'cancel') {
                        return `<button @click="showCancelPrompt = true" :disabled="processingAction" class="w-full py-2.5 border border-red-500/30 text-red-400 hover:bg-red-500/10 disabled:opacity-40 rounded-lg transition text-sm font-medium">${a.label}</button>`;
                    }
                    return `<button @click="performAction('${a.action}')" :disabled="processingAction" class="w-full py-3 ${a.class} disabled:opacity-40 disabled:cursor-not-allowed rounded-lg transition text-sm font-bold"><span x-show="!processingAction">${a.label}</span><span x-show="processingAction">Processing...</span></button>`;
                }).join('');

                return `
                    <div class="p-4 overflow-y-auto flex-1" x-data="{ showCancelPrompt: false }">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-white font-bold text-lg">${this.escHtml(o.order_number)}</h2>
                                <span class="text-xs px-2 py-0.5 rounded font-medium ${this.statusBadgeClass(o.status)}">${this.escHtml(o.status_label)}</span>
                            </div>
                            <button @click="selectedOrder = null" class="text-gray-600 hover:text-gray-400 md:hidden">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Customer -->
                        <div class="bg-surface rounded-lg p-3 mb-4">
                            <div class="text-white text-sm font-medium">${this.escHtml(o.customer_name)}</div>
                            <a href="tel:${this.escHtml(o.customer_phone)}" class="text-gold text-sm hover:underline">${this.escHtml(o.customer_phone)}</a>
                            ${o.order_type === 'delivery' && o.delivery_address ? `<div class="text-xs text-gray-500 mt-1">${this.escHtml(o.delivery_address)}</div>` : ''}
                            ${o.payment_status === 'paid' ? '<div class="text-xs text-emerald-500 mt-1 font-medium">Paid online</div>' : '<div class="text-xs text-gray-600 mt-1">Pay at pickup</div>'}
                        </div>

                        <!-- Items -->
                        <div class="mb-4">${items}</div>

                        <!-- Totals -->
                        <div class="space-y-1 text-sm mb-4 border-t border-white/5 pt-3">
                            <div class="flex justify-between text-gray-500"><span>Subtotal</span><span>$${o.subtotal}</span></div>
                            <div class="flex justify-between text-gray-500"><span>Tax</span><span>$${o.tax_amount}</span></div>
                            ${o.delivery_fee ? `<div class="flex justify-between text-gray-500"><span>Delivery</span><span>$${o.delivery_fee}</span></div>` : ''}
                            <div class="flex justify-between text-white font-semibold pt-1 border-t border-white/5"><span>Total</span><span>$${o.total}</span></div>
                        </div>

                        ${o.special_instructions ? `<div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-3 mb-4"><div class="text-xs text-amber-400/70 uppercase tracking-wide mb-1">Special Instructions</div><div class="text-sm text-amber-200">${this.escHtml(o.special_instructions)}</div></div>` : ''}

                        <!-- Actions -->
                        <div class="space-y-2 mt-4">
                            ${actionBtns}
                            <!-- Cancel prompt -->
                            <div x-show="showCancelPrompt" x-cloak class="bg-surface rounded-lg p-3 border border-red-500/20">
                                <input type="text" x-ref="cancelReason" placeholder="Reason for cancellation..." class="w-full bg-transparent border border-white/10 rounded px-3 py-2 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-red-500/50 mb-2">
                                <div class="flex gap-2">
                                    <button @click="performAction('cancel', $refs.cancelReason.value); showCancelPrompt = false" class="flex-1 py-2 bg-red-600 hover:bg-red-500 text-white rounded text-sm font-medium">Cancel Order</button>
                                    <button @click="showCancelPrompt = false" class="flex-1 py-2 border border-white/10 text-gray-400 hover:text-white rounded text-sm">Nevermind</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            },

            getActions(status) {
                switch (status) {
                    case 'pending':
                        return [
                            { action: 'confirm', label: 'Confirm Order', class: 'bg-blue-600 hover:bg-blue-500 text-white' },
                            { action: 'cancel', label: 'Cancel', class: '' },
                        ];
                    case 'confirmed':
                        return [
                            { action: 'preparing', label: 'Start Preparing', class: 'bg-purple-600 hover:bg-purple-500 text-white' },
                            { action: 'cancel', label: 'Cancel', class: '' },
                        ];
                    case 'preparing':
                        return [
                            { action: 'ready', label: 'Mark Ready', class: 'bg-emerald-600 hover:bg-emerald-500 text-white' },
                            { action: 'cancel', label: 'Cancel', class: '' },
                        ];
                    case 'ready':
                        return [
                            { action: 'complete', label: 'Complete', class: 'bg-white/10 hover:bg-white/20 text-white' },
                        ];
                    default:
                        return [];
                }
            },

            // === Shift Closeout Methods ===

            async openCloseShift() {
                this.shiftOpen = true;
                this.shiftData = null;
                this.shiftNotes = '';
                try {
                    const resp = await fetch('/api/staff/shift-summary', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        credentials: 'same-origin',
                    });
                    if (resp.ok) this.shiftData = await resp.json();
                } catch (e) { console.error('Shift summary error:', e); }
            },

            async submitCloseShift() {
                if (this.shiftClosing) return;
                this.shiftClosing = true;
                try {
                    const resp = await fetch('/api/staff/close-shift', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ notes: this.shiftNotes || null }),
                    });
                    if (!resp.ok) {
                        const err = await resp.json();
                        throw new Error(err.error || 'Failed to close shift');
                    }
                    const data = await resp.json();
                    this.addToast(`Shift closed — ${data.closeout.total_orders} orders, $${data.closeout.total_revenue} revenue`, 'success');
                    this.shiftOpen = false;
                    this.lastPoll = null;
                    this.fetchOrders();
                } catch (e) {
                    this.addToast(e.message, 'error');
                } finally {
                    this.shiftClosing = false;
                }
            },

            // === New Dine-in Order Methods ===

            async openNewOrder() {
                this.newOrderOpen = true;
                this.newOrderTable = '';
                this.newOrderItems = [];
                this.newOrderNotes = '';
                if (!this.menuLoaded) {
                    await this.loadMenu();
                }
            },

            closeNewOrder() {
                this.newOrderOpen = false;
            },

            async loadMenu() {
                try {
                    const resp = await fetch('/api/staff/menu', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        credentials: 'same-origin',
                    });
                    if (resp.ok) {
                        const data = await resp.json();
                        this.menuCategories = data.categories;
                        this.menuLoaded = true;
                    }
                } catch (e) {
                    console.error('Failed to load menu:', e);
                }
            },

            addToNewOrder(item) {
                const existing = this.newOrderItems.find(i => i.id === item.id);
                if (existing) {
                    existing.quantity++;
                } else {
                    this.newOrderItems.push({ id: item.id, name: item.name, price: item.price, quantity: 1 });
                }
            },

            get newOrderSubtotal() {
                return this.newOrderItems.reduce((sum, i) => sum + (i.price * i.quantity), 0);
            },

            async submitNewOrder() {
                if (!this.newOrderTable || this.newOrderItems.length === 0) return;
                this.newOrderSubmitting = true;

                try {
                    const resp = await fetch('/api/staff/orders', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            table_number: this.newOrderTable,
                            items: this.newOrderItems.map(i => ({ menu_item_id: i.id, quantity: i.quantity })),
                            special_instructions: this.newOrderNotes || null,
                        }),
                    });

                    if (!resp.ok) {
                        const err = await resp.json();
                        throw new Error(err.error || 'Failed to create order');
                    }

                    const data = await resp.json();
                    this.addToast(`Table ${data.order.table_number} — ${data.order.order_number} sent to kitchen`, 'success');
                    this.closeNewOrder();

                    // Trigger a full refresh to pick up the new order
                    this.lastPoll = null;
                    this.fetchOrders();

                } catch (e) {
                    this.addToast(e.message, 'error');
                } finally {
                    this.newOrderSubmitting = false;
                }
            },

            escHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            },
        };
    }
    </script>
</body>
</html>
