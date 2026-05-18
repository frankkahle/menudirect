{{-- Cart UI (if ordering enabled) --}}
@if($orderingEnabled)
{{-- Sticky Mobile Order Bar --}}
<div x-show="getItemCount() > 0"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-y-full opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="translate-y-full opacity-0"
     class="fixed bottom-0 left-0 right-0 z-40 md:hidden bg-white border-t shadow-lg safe-area-bottom">
    <div class="flex items-center justify-between p-3">
        <div class="flex items-center gap-3">
            <span class="bg-red-500 text-white text-sm font-bold rounded-full w-8 h-8 flex items-center justify-center" x-text="getItemCount()"></span>
            <div>
                <p class="font-semibold text-gray-900">Your Order</p>
                <p class="text-sm text-gray-600" x-text="formatPrice(getTotal())"></p>
            </div>
        </div>
        <button @click="openCart()"
                class="px-6 py-2.5 rounded-lg font-semibold text-white shadow"
                style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
            View Cart
        </button>
    </div>
</div>

{{-- Floating Cart Button (desktop & when no items on mobile) --}}
<button @click="toggleCart()" class="cart-button fixed bottom-6 right-6 z-40 p-4 rounded-full shadow-lg text-white transition-transform hover:scale-105 hidden md:flex"
        style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};"
        x-show="getItemCount() > 0">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center"
          x-text="getItemCount()"></span>
</button>

{{-- Cart Sidebar --}}
<div x-show="isOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 bg-black bg-opacity-50" @click="closeCart()">
</div>

<div x-show="isOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
     class="fixed right-0 top-0 h-full w-full max-w-md z-50 bg-white shadow-xl flex flex-col" @click.stop>

    {{-- Cart Header --}}
    <div class="p-4 border-b flex justify-between items-center" style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
        <h2 class="text-xl font-bold text-white">Your Order</h2>
        <button @click="closeCart()" class="text-white hover:opacity-80">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Cart Items --}}
    <div class="flex-1 overflow-y-auto p-4">
        <template x-if="items.length === 0">
            <div class="text-center py-8 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p>Your cart is empty</p>
                <p class="text-sm mt-2">Add items from the menu to get started</p>
            </div>
        </template>

        <template x-for="item in items" :key="item.id">
            <div class="flex items-start py-4 border-b">
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900" x-text="item.name"></h3>
                    <p class="text-sm text-brand" x-text="formatPrice(item.price)"></p>
                    <input type="text" x-model="item.specialRequests"
                           class="mt-2 text-sm w-full border-gray-200 rounded p-1"
                           placeholder="Special requests...">
                </div>
                <div class="flex items-center ml-4">
                    <button @click="updateQuantity(item.id, -1)" class="p-1 rounded bg-gray-100 hover:bg-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </button>
                    <span class="mx-3 font-medium" x-text="item.quantity"></span>
                    <button @click="updateQuantity(item.id, 1)" class="p-1 rounded bg-gray-100 hover:bg-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                    <button @click="removeItem(item.id)" class="ml-3 text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Cart Footer --}}
    <div class="border-t p-4 bg-gray-50">
        <div class="space-y-2 mb-4">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span x-text="formatPrice(getSubtotal())"></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Tax</span>
                <span x-text="formatPrice(getTax())"></span>
            </div>
            <div class="flex justify-between font-bold text-lg">
                <span>Total</span>
                <span class="text-brand" x-text="formatPrice(getTotal())"></span>
            </div>
        </div>

        <template x-if="minimumOrder > 0 && !meetsMinimum()">
            <p class="text-sm text-amber-600 mb-3">
                Minimum order: <span x-text="formatPrice(minimumOrder)"></span>
            </p>
        </template>

        <button @click="openCheckout()" :disabled="items.length === 0 || !meetsMinimum()"
                class="w-full py-3 rounded-lg font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed transition"
                style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
            Proceed to Checkout
        </button>
    </div>
</div>

{{-- Checkout Modal --}}
<div x-show="checkoutOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50" @click="closeCheckout()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="p-6 border-b">
            <h2 class="text-2xl font-bold text-gray-900">Checkout</h2>
        </div>

        <div class="p-6 space-y-4">
            <template x-if="error">
                <div class="bg-red-50 border-l-4 border-red-500 p-4 text-red-700" x-text="error"></div>
            </template>

            {{-- Returning Customer Lookup --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4" x-show="!customerFound">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-sm font-medium text-blue-900">Ordered before?</span>
                </div>
                <div class="flex gap-2">
                    <input type="tel" x-model="lookupPhone" placeholder="Enter your phone number"
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand text-sm"
                           @keyup.enter="lookupCustomer()">
                    <button @click="lookupCustomer()" :disabled="isLookingUp"
                            class="px-4 py-2 text-sm font-medium text-white rounded-md transition"
                            style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
                        <span x-show="!isLookingUp">Find Me</span>
                        <span x-show="isLookingUp">...</span>
                    </button>
                </div>
                <p x-show="lookupMessage" class="text-xs mt-2" :class="lookupFound ? 'text-green-600' : 'text-gray-500'" x-text="lookupMessage"></p>
            </div>

            {{-- Customer Found Badge --}}
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between" x-show="customerFound">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm text-green-800">Welcome back, <strong x-text="customerName.split(' ')[0]"></strong>!</span>
                </div>
                <button @click="clearCustomer()" class="text-sm text-green-600 hover:text-green-800">Not you?</button>
            </div>

            {{-- Order Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
                <div class="flex gap-4">
                    @if($orderingConfig['accepts_pickup'] ?? true)
                    <label class="flex-1">
                        <input type="radio" value="pickup" x-model="orderType" class="sr-only peer">
                        <div class="p-4 border rounded-lg cursor-pointer peer-checked:border-brand peer-checked:bg-brand peer-checked:bg-opacity-5 text-center">
                            <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span class="font-medium">Pickup</span>
                        </div>
                    </label>
                    @endif
                    @if($orderingConfig['accepts_delivery'] ?? false)
                    <label class="flex-1">
                        <input type="radio" value="delivery" x-model="orderType" class="sr-only peer">
                        <div class="p-4 border rounded-lg cursor-pointer peer-checked:border-brand peer-checked:bg-brand peer-checked:bg-opacity-5 text-center">
                            <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                            <span class="font-medium">Delivery</span>
                            <span class="text-sm text-gray-500 block">+ <span x-text="formatPrice(getDeliveryFeeAmount())"></span></span>
                        </div>
                    </label>
                    @endif
                </div>
            </div>

            {{-- When? (ASAP vs Schedule) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">When do you want it?</label>

                {{-- Closed notice --}}
                <div x-show="!isRestaurantOpen" class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm">
                    <strong class="text-amber-800">Currently closed</strong>
                    <span x-show="todayHours" class="text-amber-700"> - Today: <span x-text="todayHours"></span></span>
                    <p x-show="nextOpenLabel" class="text-amber-700 mt-1" x-text="nextOpenLabel"></p>
                </div>

                <div class="flex gap-4">
                    {{-- ASAP --}}
                    <div @click="if(isRestaurantOpen) { scheduleMode = 'asap'; selectedDate = ''; selectedTime = ''; }"
                         class="flex-1 p-4 border rounded-lg text-center cursor-pointer transition-colors"
                         :class="[scheduleMode === 'asap' ? 'border-blue-500 bg-blue-50' : 'border-gray-300', !isRestaurantOpen ? 'opacity-50' : '']">
                        <span class="font-medium">ASAP</span>
                        <span class="text-sm text-gray-500 block" x-show="isRestaurantOpen">~<span x-text="estimatedPrepTime"></span> min</span>
                        <span class="text-sm text-red-500 block" x-show="!isRestaurantOpen">Closed</span>
                    </div>
                    {{-- Schedule --}}
                    <div @click="scheduleMode = 'schedule'; schedulePickerOpen = true;"
                         class="flex-1 p-4 border rounded-lg text-center cursor-pointer transition-colors"
                         :class="scheduleMode === 'schedule' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'">
                        <span class="font-medium">Schedule</span>
                        <span x-show="!selectedDate || !selectedTime" class="text-sm text-gray-500 block">Pick a time</span>
                        <span x-show="selectedDate && selectedTime" class="text-sm text-blue-600 block" x-text="getScheduleLabel()"></span>
                    </div>
                </div>
            </div>

            {{-- Contact Info --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" x-model="customerName" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" x-model="customerEmail" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                    <input type="tel" x-model="customerPhone" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                </div>
            </div>

            {{-- Delivery Address with Autocomplete --}}
            <div x-show="orderType === 'delivery'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address *</label>
                <div class="relative">
                    <input type="text" id="delivery-address-input" x-model="deliveryAddress"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand"
                           placeholder="Start typing your address..."
                           autocomplete="off">
                    {{-- Address suggestions dropdown --}}
                    <div x-show="showSuggestions && addressSuggestions.length > 0"
                         x-transition
                         class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                        <template x-for="suggestion in addressSuggestions" :key="suggestion.id">
                            <button type="button"
                                    @click="selectAddress(suggestion)"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 focus:bg-gray-100 focus:outline-none border-b border-gray-100 last:border-0"
                                    x-text="suggestion.text">
                            </button>
                        </template>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1" x-show="mapboxEnabled && !deliveryValidating && !deliveryValidated && !deliveryOutOfRange">Address suggestions powered by Mapbox</p>

                {{-- Delivery zone validation feedback --}}
                <div x-show="deliveryValidating" class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Checking delivery area...
                </div>
                <div x-show="deliveryValidated && !deliveryValidating" class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Delivery available</span>
                        <span x-show="deliveryZoneName" x-text="'(' + deliveryZoneName + ')'"></span>
                    </div>
                    <div class="flex gap-4 mt-1 text-xs text-green-600">
                        <span>Fee: <strong x-text="'$' + deliveryZoneFee.toFixed(2)"></strong></span>
                        <span x-show="deliveryEstimatedMinutes">Est. <strong x-text="deliveryEstimatedMinutes"></strong> min</span>
                    </div>
                </div>
                <div x-show="deliveryOutOfRange && !deliveryValidating" class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Sorry, this address is outside our delivery area.
                </div>
            </div>

            {{-- Special Instructions --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Special Instructions</label>
                <textarea x-model="specialInstructions" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand" placeholder="Any allergies or special requests?"></textarea>
            </div>

            {{-- Order Summary --}}
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold mb-3">Order Summary</h3>
                <template x-for="item in items" :key="item.id">
                    <div class="flex justify-between text-sm py-1">
                        <span><span x-text="item.quantity"></span>x <span x-text="item.name"></span></span>
                        <span x-text="formatPrice(item.price * item.quantity)"></span>
                    </div>
                </template>
                <div class="border-t mt-2 pt-2 space-y-1">
                    <div class="flex justify-between text-sm">
                        <span>Subtotal</span>
                        <span x-text="formatPrice(getSubtotal())"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Tax</span>
                        <span x-text="formatPrice(getTax())"></span>
                    </div>
                    <div x-show="orderType === 'delivery'" class="flex justify-between text-sm">
                        <span>Delivery</span>
                        <span x-text="formatPrice(getDeliveryFeeAmount())"></span>
                    </div>
                    <div class="flex justify-between font-bold pt-1 border-t">
                        <span>Total</span>
                        <span x-text="formatPrice(getTotal())"></span>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2 text-center">
                    Pay at <span x-text="orderType === 'pickup' ? 'Pickup' : 'Delivery'"></span>
                </p>
            </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex gap-3">
            <button @click="closeCheckout()" class="flex-1 py-3 rounded-lg font-semibold border border-gray-300 hover:bg-gray-100 transition">
                Back
            </button>
            <button @click="submitOrder()" :disabled="isSubmitting"
                    class="flex-1 py-3 rounded-lg font-semibold text-white disabled:opacity-50 transition"
                    style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
                <span x-show="!isSubmitting">Place Order</span>
                <span x-show="isSubmitting">Placing Order...</span>
            </button>
        </div>
    </div>
</div>

{{-- Success Modal --}}
<div x-show="successOrder" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md text-center p-8">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Order Placed!</h2>
        <p class="text-gray-600 mb-4">Thank you for your order.</p>

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <p class="text-sm text-gray-600">Order Number</p>
            <p class="text-xl font-bold text-brand" x-text="successOrder?.order?.order_number"></p>
            <p class="text-sm text-gray-500 mt-2">
                Estimated ready: <span x-text="getEstimatedReadyTime()"></span>
            </p>
        </div>

        <p class="text-sm text-gray-500 mb-4">
            We've sent a confirmation to your email. You can track your order status using the link in the email.
        </p>

        <button @click="closeSuccess()"
                class="w-full py-3 rounded-lg font-semibold text-white transition"
                style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
            Done
        </button>
    </div>
</div>

{{-- Schedule Picker Modal --}}
<div x-show="schedulePickerOpen" x-transition class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black bg-opacity-50" @click="schedulePickerOpen = false">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm" @click.stop>
        <div class="p-4 border-b">
            <h3 class="text-lg font-bold text-gray-900">Schedule Your Order</h3>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
                <select x-model="selectedDate" @change="selectedTime = ''" class="w-full rounded-md border-gray-300 shadow-sm text-sm p-2 border">
                    <option value="">Choose a date...</option>
                    <template x-for="slot in schedulingSlots" :key="slot.date">
                        <option :value="slot.date" x-text="slot.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Time</label>
                <select x-model="selectedTime" class="w-full rounded-md border-gray-300 shadow-sm text-sm p-2 border" :disabled="!selectedDate">
                    <option value="">Choose a time...</option>
                    <template x-for="time in getTimesForDate(selectedDate)" :key="time">
                        <option :value="time" x-text="time"></option>
                    </template>
                </select>
            </div>
        </div>
        <div class="p-4 border-t flex gap-3">
            <button @click="schedulePickerOpen = false; if(!selectedDate || !selectedTime) { scheduleMode = 'asap'; }"
                    class="flex-1 py-2 px-4 border border-gray-300 rounded-lg text-gray-700 font-medium">
                Cancel
            </button>
            <button @click="schedulePickerOpen = false"
                    :disabled="!selectedDate || !selectedTime"
                    class="flex-1 py-2 px-4 rounded-lg text-white font-medium disabled:opacity-50"
                    style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
                Confirm
            </button>
        </div>
    </div>
</div>

@endif