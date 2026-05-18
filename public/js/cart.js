/**
 * Restaurant Cart - Alpine.js Component
 * Handles shopping cart functionality for online ordering
 */

function registerCartComponent() {
    Alpine.data('cart', (config = {}) => ({
        // Configuration
        apiBaseUrl: config.apiBaseUrl || '',
        restaurantSlug: config.restaurantSlug || '',
        taxRate: config.taxRate || 0.15,
        minimumOrder: config.minimumOrder || 0,
        deliveryFee: config.deliveryFee || 0,
        acceptsDelivery: config.acceptsDelivery || false,
        acceptsPickup: config.acceptsPickup || true,
        estimatedPrepTime: config.estimatedPrepTime || 30,
        restaurantPhone: config.restaurantPhone || '',
        hasDeliveryZones: config.hasDeliveryZones || false,

        // Restaurant hours (basic info from config, slots loaded via AJAX)
        allHours: config.allHours || {},  // {Monday: "12:00 PM - 8:00 PM", ...}
        restaurantTimezone: config.timezone || 'America/Halifax',
        forceClosed: config.forceClosed === true,
        closureMessage: config.closureMessage || '',
        isRestaurantOpen: config.forceClosed ? false : (config.isOpen !== false),
        todayHours: config.todayHours || '',
        nextOpenLabel: config.nextOpenLabel || '',
        schedulingSlots: [],
        schedulingLoaded: false,
        _openStatusInterval: null,

        // Cart state
        items: [],
        isOpen: false,
        checkoutOpen: false,
        orderType: 'pickup',
        isSubmitting: false,
        error: null,
        successOrder: null,

        // Scheduling
        scheduleMode: 'asap',  // 'asap' or 'schedule'
        schedulePickerOpen: false,
        selectedDate: '',
        selectedTime: '',

        // Delivery zone validation
        deliveryLat: null,
        deliveryLng: null,
        deliveryZoneName: '',
        deliveryZoneFee: 0,
        deliveryZoneMinimum: 0,
        deliveryEstimatedMinutes: null,
        deliveryValidating: false,
        deliveryValidated: false,
        deliveryOutOfRange: false,

        // Customer info
        customerName: '',
        customerEmail: '',
        customerPhone: '',
        deliveryAddress: '',
        specialInstructions: '',

        // Customer lookup state
        lookupPhone: '',
        isLookingUp: false,
        lookupMessage: '',
        lookupFound: false,
        customerFound: false,

        // Initialize
        init() {
            this.loadFromStorage();

            // Watch for changes and save
            this.$watch('items', () => this.saveToStorage());

            // Initialize address autocomplete when checkout opens
            this.$watch('checkoutOpen', (open) => {
                if (open) {
                    this.$nextTick(() => this.initAddressAutocomplete());
                }
            });

            // Recompute open/closed status from actual hours + current time
            // This keeps the banner in sync with the hours list, independent of server cache
            this.recomputeOpenStatus();
            this._openStatusInterval = setInterval(() => this.recomputeOpenStatus(), 60000);

            // If restaurant is closed, default to schedule mode
            if (!this.isRestaurantOpen) {
                this.scheduleMode = 'schedule';
            }

            // Pre-load scheduling data (for time slots)
            this.loadScheduling();
        },

        // Compute open/closed state from the hours list + current time in the
        // restaurant's timezone. Runs once on load and every minute — keeps the
        // banner in sync with hours regardless of the viewer's location or server cache.
        recomputeOpenStatus() {
            // Owner manually closed (seasonal / emergency / vacation)
            if (this.forceClosed) {
                this.isRestaurantOpen = false;
                return;
            }

            const hours = this.allHours || {};
            if (!Object.keys(hours).length) return; // no hours data → trust server default

            const tz = this.restaurantTimezone || 'America/Halifax';
            const now = new Date();

            // Get the day + time in the restaurant's local timezone
            // Uses Intl.DateTimeFormat to convert browser time → restaurant time
            let dayName, hh, mm;
            try {
                const parts = new Intl.DateTimeFormat('en-US', {
                    timeZone: tz,
                    weekday: 'long', hour: '2-digit', minute: '2-digit', hour12: false,
                }).formatToParts(now).reduce((acc, p) => { acc[p.type] = p.value; return acc; }, {});
                dayName = parts.weekday;
                hh = parseInt(parts.hour, 10);
                mm = parseInt(parts.minute, 10);
                if (hh === 24) hh = 0; // some browsers return "24" for midnight
            } catch (e) {
                // Fallback to browser's local time if timezone is invalid
                dayName = now.toLocaleDateString('en-US', { weekday: 'long' });
                hh = now.getHours();
                mm = now.getMinutes();
            }

            const todayStr = hours[dayName];
            if (todayStr) this.todayHours = todayStr;

            const parsed = this._parseHours(todayStr);
            if (!parsed || parsed.closed) {
                this.isRestaurantOpen = false;
                return;
            }

            const nowMinutes = hh * 60 + mm;
            this.isRestaurantOpen = nowMinutes >= parsed.open && nowMinutes <= parsed.close;
        },

        _parseHours(str) {
            if (!str || typeof str !== 'string') return null;
            if (/closed/i.test(str)) return { closed: true };
            const m = str.match(/(\d{1,2}):(\d{2})\s*(AM|PM)?\s*-\s*(\d{1,2}):(\d{2})\s*(AM|PM)?/i);
            if (!m) return null;
            const to24 = (h, min, ap) => {
                h = parseInt(h, 10); min = parseInt(min, 10);
                if (ap) {
                    const up = ap.toUpperCase();
                    if (up === 'PM' && h !== 12) h += 12;
                    if (up === 'AM' && h === 12) h = 0;
                }
                return h * 60 + min;
            };
            let open = to24(m[1], m[2], m[3]);
            let close = to24(m[4], m[5], m[6]);
            if (close <= open) close += 24 * 60; // crosses midnight
            return { open, close };
        },

        // Load scheduling data from API
        async loadScheduling() {
            if (this.schedulingLoaded) return;

            try {
                const response = await fetch(`${this.apiBaseUrl}/api/restaurant/${this.restaurantSlug}/scheduling`);
                if (response.ok) {
                    const data = await response.json();
                    // Don't trust server's is_open (it's cached) — recompute from hours instead
                    this.todayHours = data.today_hours || '';
                    this.nextOpenLabel = data.next_open_label || '';
                    this.schedulingSlots = data.slots || [];
                    this.schedulingLoaded = true;
                    // Recompute open status using fresh client-side check against current hours
                    this.recomputeOpenStatus();

                    // If closed, auto-select schedule mode
                    if (!this.isRestaurantOpen) {
                        this.scheduleMode = 'schedule';
                    }
                }
            } catch (e) {
                console.warn('Failed to load scheduling:', e);
                // Keep defaults (open, no scheduling slots)
            }
        },

        // Address autocomplete state
        addressSuggestions: [],
        showSuggestions: false,
        mapboxEnabled: window.mapboxEnabled || false,

        // Initialize Mapbox Address Autocomplete
        initAddressAutocomplete() {
            const input = document.getElementById('delivery-address-input');
            if (!input || !window.mapboxAccessToken) {
                this.mapboxEnabled = false;
                return;
            }

            this.mapboxEnabled = true;
            let debounceTimer = null;

            // Debounced search on input
            input.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                const query = e.target.value;

                if (query.length < 3) {
                    this.addressSuggestions = [];
                    this.showSuggestions = false;
                    return;
                }

                debounceTimer = setTimeout(() => this.searchAddresses(query), 300);
            });

            // Hide suggestions on blur (with delay for click)
            input.addEventListener('blur', () => {
                setTimeout(() => { this.showSuggestions = false; }, 200);
            });

            // Show suggestions on focus if we have them
            input.addEventListener('focus', () => {
                if (this.addressSuggestions.length > 0) {
                    this.showSuggestions = true;
                }
            });
        },

        async searchAddresses(query) {
            if (!window.mapboxAccessToken) return;

            try {
                const params = new URLSearchParams({
                    access_token: window.mapboxAccessToken,
                    country: 'CA',
                    types: 'address',
                    limit: 5,
                    language: 'en'
                });

                const response = await fetch(
                    `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?${params}`
                );

                if (response.ok) {
                    const data = await response.json();
                    this.addressSuggestions = data.features.map(f => ({
                        id: f.id,
                        text: f.place_name,
                        lng: f.center?.[0] ?? null,
                        lat: f.center?.[1] ?? null
                    }));
                    this.showSuggestions = this.addressSuggestions.length > 0;
                }
            } catch (e) {
                console.warn('Address search failed:', e);
            }
        },

        selectAddress(suggestion) {
            this.deliveryAddress = suggestion.text;
            this.deliveryLat = suggestion.lat;
            this.deliveryLng = suggestion.lng;
            this.addressSuggestions = [];
            this.showSuggestions = false;

            // Validate delivery zone if zones are configured
            if (this.hasDeliveryZones && this.deliveryLat && this.deliveryLng) {
                this.validateDeliveryAddress();
            }
        },

        async validateDeliveryAddress() {
            if (!this.deliveryLat || !this.deliveryLng) return;

            this.deliveryValidating = true;
            this.deliveryValidated = false;
            this.deliveryOutOfRange = false;

            try {
                const response = await fetch(`${this.apiBaseUrl}/api/restaurant/${this.restaurantSlug}/validate-delivery`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ latitude: this.deliveryLat, longitude: this.deliveryLng })
                });

                const result = await response.json();

                if (result.in_range) {
                    this.deliveryZoneName = result.zone_name || '';
                    this.deliveryZoneFee = parseFloat(result.delivery_fee) || 0;
                    this.deliveryZoneMinimum = parseFloat(result.minimum_order) || 0;
                    this.deliveryEstimatedMinutes = result.estimated_delivery_minutes;
                    this.deliveryValidated = true;
                    this.deliveryOutOfRange = false;
                } else {
                    this.deliveryOutOfRange = true;
                    this.deliveryValidated = false;
                }
            } catch (e) {
                console.warn('Delivery validation failed:', e);
                // Fall back to flat fee
                this.deliveryValidated = false;
                this.deliveryOutOfRange = false;
            } finally {
                this.deliveryValidating = false;
            }
        },

        // Cart operations
        addItem(item) {
            const existing = this.items.find(i => i.id === item.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.items.push({
                    id: item.id,
                    name: item.name,
                    price: parseFloat(item.price),
                    quantity: 1,
                    specialRequests: ''
                });
            }
            this.showAddedFeedback();
        },

        removeItem(id) {
            const index = this.items.findIndex(i => i.id === id);
            if (index > -1) {
                this.items.splice(index, 1);
            }
        },

        updateQuantity(id, delta) {
            const item = this.items.find(i => i.id === id);
            if (item) {
                item.quantity = Math.max(1, item.quantity + delta);
            }
        },

        setQuantity(id, qty) {
            const item = this.items.find(i => i.id === id);
            if (item) {
                if (qty <= 0) {
                    this.removeItem(id);
                } else {
                    item.quantity = Math.min(99, qty);
                }
            }
        },

        updateSpecialRequests(id, requests) {
            const item = this.items.find(i => i.id === id);
            if (item) {
                item.specialRequests = requests;
            }
        },

        clearCart() {
            this.items = [];
            this.saveToStorage();
        },

        // Calculations
        getItemCount() {
            return this.items.reduce((sum, item) => sum + item.quantity, 0);
        },

        getSubtotal() {
            return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },

        getTax() {
            return this.getSubtotal() * this.taxRate;
        },

        getDeliveryFeeAmount() {
            if (this.orderType !== 'delivery') return 0;
            // Use zone-based fee if validated, otherwise flat fee
            if (this.hasDeliveryZones && this.deliveryValidated) {
                return this.deliveryZoneFee;
            }
            return this.deliveryFee;
        },

        getEffectiveMinimum() {
            if (this.orderType === 'delivery' && this.hasDeliveryZones && this.deliveryValidated && this.deliveryZoneMinimum > 0) {
                return Math.max(this.minimumOrder, this.deliveryZoneMinimum);
            }
            return this.minimumOrder;
        },

        getTotal() {
            return this.getSubtotal() + this.getTax() + this.getDeliveryFeeAmount();
        },

        formatPrice(amount) {
            return '$' + amount.toFixed(2);
        },

        meetsMinimum() {
            return this.getSubtotal() >= this.getEffectiveMinimum();
        },

        // Persistence
        getStorageKey() {
            return `cart_${this.restaurantSlug}`;
        },

        saveToStorage() {
            try {
                localStorage.setItem(this.getStorageKey(), JSON.stringify(this.items));
            } catch (e) {
                console.warn('Failed to save cart to localStorage', e);
            }
        },

        loadFromStorage() {
            try {
                const saved = localStorage.getItem(this.getStorageKey());
                if (saved) {
                    this.items = JSON.parse(saved);
                }
            } catch (e) {
                console.warn('Failed to load cart from localStorage', e);
                this.items = [];
            }
        },

        // UI helpers
        showAddedFeedback() {
            // Bounce the cart button if visible
            const btn = document.querySelector('.cart-button');
            if (btn) {
                btn.classList.add('animate-bounce');
                setTimeout(() => btn.classList.remove('animate-bounce'), 300);
            }

            // Show a brief toast notification so the user knows it worked
            const toast = document.createElement('div');
            toast.textContent = `${this.getItemCount()} item${this.getItemCount() !== 1 ? 's' : ''} in your order`;
            toast.className = 'fixed bottom-20 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm font-medium px-5 py-2.5 rounded-full shadow-lg transition-opacity duration-300';
            toast.style.opacity = '0';
            document.body.appendChild(toast);
            requestAnimationFrame(() => { toast.style.opacity = '1'; });
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 1500);
        },

        openCart() {
            this.isOpen = true;
        },

        closeCart() {
            this.isOpen = false;
        },

        toggleCart() {
            this.isOpen = !this.isOpen;
        },

        openCheckout() {
            if (this.items.length === 0) return;
            if (!this.meetsMinimum()) {
                this.error = `Minimum order is ${this.formatPrice(this.getEffectiveMinimum())}`;
                return;
            }
            this.isOpen = false;
            this.checkoutOpen = true;
            this.error = null;
        },

        closeCheckout() {
            this.checkoutOpen = false;
        },

        // Scheduling helpers
        getTimesForDate(dateStr) {
            const slot = this.schedulingSlots.find(s => s.date === dateStr);
            return slot ? slot.times : [];
        },

        hasSchedulingSlots() {
            return this.schedulingSlots && this.schedulingSlots.length > 0;
        },

        isAsapMode() {
            return this.scheduleMode === 'asap';
        },

        getScheduleLabel() {
            if (!this.selectedDate || !this.selectedTime) return 'Pick a time';
            const slot = this.schedulingSlots.find(s => s.date === this.selectedDate);
            const dateLabel = slot ? slot.label : this.selectedDate;
            return dateLabel + ' @ ' + this.selectedTime;
        },

        getScheduledDateTime() {
            if (this.isAsapMode() || !this.selectedDate || !this.selectedTime) {
                return null;
            }
            return this.selectedDate + ' ' + this.selectedTime;
        },

        // Form validation
        validateForm() {
            if (!this.customerName.trim()) {
                this.error = 'Please enter your name';
                return false;
            }
            if (!this.customerEmail.trim() || !this.customerEmail.includes('@')) {
                this.error = 'Please enter a valid email';
                return false;
            }
            if (!this.customerPhone.trim()) {
                this.error = 'Please enter your phone number';
                return false;
            }
            if (this.orderType === 'delivery' && !this.deliveryAddress.trim()) {
                this.error = 'Please enter your delivery address';
                return false;
            }
            if (this.orderType === 'delivery' && this.hasDeliveryZones && this.deliveryOutOfRange) {
                this.error = 'Sorry, your address is outside our delivery area';
                return false;
            }
            if (!this.isAsapMode() && !this.selectedDate) {
                this.error = 'Please select a date';
                return false;
            }
            if (!this.isAsapMode() && !this.selectedTime) {
                this.error = 'Please select a time';
                return false;
            }
            return true;
        },

        // Submit order
        async submitOrder() {
            this.error = null;

            if (!this.validateForm()) return;
            if (!this.meetsMinimum()) {
                this.error = `Minimum order is ${this.formatPrice(this.getEffectiveMinimum())}`;
                return;
            }

            this.isSubmitting = true;

            try {
                const orderData = {
                    customer_name: this.customerName.trim(),
                    customer_email: this.customerEmail.trim(),
                    customer_phone: this.customerPhone.trim(),
                    order_type: this.orderType,
                    is_asap: this.isAsapMode(),
                    scheduled_for: this.getScheduledDateTime(),
                    delivery_address: this.orderType === 'delivery' ? this.deliveryAddress.trim() : null,
                    delivery_latitude: this.orderType === 'delivery' ? this.deliveryLat : null,
                    delivery_longitude: this.orderType === 'delivery' ? this.deliveryLng : null,
                    special_instructions: this.specialInstructions.trim() || null,
                    items: this.items.map(item => ({
                        menu_item_id: item.id,
                        quantity: item.quantity,
                        special_requests: item.specialRequests || null
                    }))
                };

                const response = await fetch(`${this.apiBaseUrl}/api/restaurant/${this.restaurantSlug}/orders`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });

                const result = await response.json();

                if (!response.ok) {
                    let errorMsg = result.error || 'Failed to place order';
                    if (result.next_open_label) {
                        errorMsg += ' ' + result.next_open_label + '.';
                    }
                    throw new Error(errorMsg);
                }

                // Check if online payment is required (Stripe Connect)
                if (result.payment && result.payment.required && result.payment.client_secret) {
                    const paymentOk = await this.confirmStripePayment(result.payment, result.order);
                    if (!paymentOk) {
                        // Payment failed — order still exists as "pending payment"
                        // Error message already set by confirmStripePayment
                        return;
                    }
                }

                // Success!
                this.successOrder = result;
                this.clearCart();
                this.resetForm();
                this.checkoutOpen = false;

            } catch (e) {
                this.error = e.message || 'Failed to place order. Please try again.';
                console.error('Order submission failed:', e);
            } finally {
                this.isSubmitting = false;
            }
        },

        // Confirms a Stripe PaymentIntent (card entry via Stripe Elements modal).
        // Returns true on success, false on failure.
        async confirmStripePayment(payment, order) {
            // Load Stripe.js if not already loaded
            if (!window.Stripe) {
                await this.loadStripeJs();
            }
            if (!window.Stripe) {
                this.error = 'Could not load Stripe. Check your network/ad-blocker.';
                return false;
            }

            const stripe = Stripe(payment.publishable_key);

            // Build a Stripe Elements payment modal
            const modalHtml = `
              <div id="stripe-pay-modal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <h3 class="text-xl font-bold text-gray-900">Complete Your Payment</h3>
                      <p class="text-sm text-gray-500 mt-1">Order #${order.order_number} &mdash; ${order.total}</p>
                    </div>
                    <button type="button" id="stripe-pay-cancel" class="text-gray-400 hover:text-gray-600">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                  <div id="stripe-pay-card" class="border border-gray-300 rounded-lg p-3 mb-3 bg-white"></div>
                  <div id="stripe-pay-error" class="text-sm text-red-600 mb-3 min-h-[1.25rem]"></div>
                  <button type="button" id="stripe-pay-submit"
                    class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Pay ${order.total}
                  </button>
                  <p class="text-xs text-gray-500 text-center mt-3">
                    Secured by Stripe. Your card information is never stored on this website.
                  </p>
                </div>
              </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = document.getElementById('stripe-pay-modal');
            const errorEl = document.getElementById('stripe-pay-error');
            const submitBtn = document.getElementById('stripe-pay-submit');
            const cancelBtn = document.getElementById('stripe-pay-cancel');

            const elements = stripe.elements();
            const cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#1f2937',
                        '::placeholder': { color: '#9ca3af' },
                    },
                    invalid: { color: '#dc2626' },
                },
            });
            cardElement.mount('#stripe-pay-card');

            cardElement.on('change', (event) => {
                errorEl.textContent = event.error?.message || '';
            });

            // Return a promise that resolves when the user pays or cancels
            return new Promise((resolve) => {
                const cleanup = () => {
                    cardElement.unmount();
                    modal.remove();
                };

                cancelBtn.addEventListener('click', () => {
                    cleanup();
                    this.error = 'Payment cancelled. Your order is held until payment is completed.';
                    resolve(false);
                });

                submitBtn.addEventListener('click', async () => {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Processing...';
                    errorEl.textContent = '';

                    const { paymentIntent, error } = await stripe.confirmCardPayment(
                        payment.client_secret,
                        {
                            payment_method: {
                                card: cardElement,
                                billing_details: {
                                    name: this.customerName,
                                    email: this.customerEmail,
                                    phone: this.customerPhone,
                                },
                            },
                        }
                    );

                    if (error) {
                        errorEl.textContent = error.message || 'Payment failed. Please try again.';
                        submitBtn.disabled = false;
                        submitBtn.textContent = `Pay ${order.total}`;
                        return;
                    }

                    if (paymentIntent && paymentIntent.status === 'succeeded') {
                        cleanup();
                        resolve(true);
                    } else {
                        errorEl.textContent = 'Payment did not complete. Please try again.';
                        submitBtn.disabled = false;
                        submitBtn.textContent = `Pay ${order.total}`;
                    }
                });
            });
        },

        loadStripeJs() {
            return new Promise((resolve, reject) => {
                if (window.Stripe) return resolve();
                const script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3/';
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Failed to load Stripe.js'));
                document.head.appendChild(script);
            });
        },

        resetForm() {
            this.customerName = '';
            this.customerEmail = '';
            this.customerPhone = '';
            this.deliveryAddress = '';
            this.deliveryLat = null;
            this.deliveryLng = null;
            this.deliveryZoneName = '';
            this.deliveryZoneFee = 0;
            this.deliveryZoneMinimum = 0;
            this.deliveryEstimatedMinutes = null;
            this.deliveryValidated = false;
            this.deliveryOutOfRange = false;
            this.specialInstructions = '';
            this.orderType = 'pickup';
            this.customerFound = false;
            this.lookupPhone = '';
            this.lookupMessage = '';
            this.lookupFound = false;
            this.scheduleMode = 'asap';
            this.selectedDate = '';
            this.selectedTime = '';
        },

        closeSuccess() {
            this.successOrder = null;
        },

        getEstimatedReadyTime() {
            // Use the server-provided estimated_ready_at if available
            if (this.successOrder?.order?.estimated_ready_at) {
                const readyAt = new Date(this.successOrder.order.estimated_ready_at);
                const now = new Date();

                // Check if it's today or another day
                if (readyAt.toDateString() === now.toDateString()) {
                    return readyAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                } else {
                    return readyAt.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' }) +
                           ' at ' + readyAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                }
            }

            // Fallback to client-side calculation
            const now = new Date();
            now.setMinutes(now.getMinutes() + this.estimatedPrepTime);
            return now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        },

        // Customer lookup
        async lookupCustomer() {
            if (!this.lookupPhone.trim()) {
                this.lookupMessage = 'Please enter your phone number';
                this.lookupFound = false;
                return;
            }

            this.isLookingUp = true;
            this.lookupMessage = '';

            try {
                const response = await fetch(`${this.apiBaseUrl}/api/restaurant/${this.restaurantSlug}/customer-lookup`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ phone: this.lookupPhone.trim() })
                });

                const result = await response.json();

                if (result.found) {
                    // Fill in customer info
                    this.customerName = result.customer.name || '';
                    this.customerEmail = result.customer.email || '';
                    this.customerPhone = result.customer.phone || '';
                    this.deliveryAddress = result.customer.delivery_address || '';
                    this.customerFound = true;
                    this.lookupFound = true;
                    this.lookupMessage = `Found! Last order ${result.last_order.date}`;
                } else {
                    this.lookupFound = false;
                    this.lookupMessage = result.message || 'No previous orders found';
                }
            } catch (e) {
                this.lookupFound = false;
                this.lookupMessage = 'Could not look up your info. Please enter manually.';
                console.error('Customer lookup failed:', e);
            } finally {
                this.isLookingUp = false;
            }
        },

        clearCustomer() {
            this.customerName = '';
            this.customerEmail = '';
            this.customerPhone = '';
            this.deliveryAddress = '';
            this.customerFound = false;
            this.lookupPhone = '';
            this.lookupMessage = '';
            this.lookupFound = false;
        }
    }));
}

// Register component — handle both cases: Alpine already started, or not yet
if (window.Alpine) {
    registerCartComponent();
} else {
    document.addEventListener('alpine:init', registerCartComponent);
}
