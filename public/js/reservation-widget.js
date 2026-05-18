/**
 * Reservation Widget - Alpine.js Component
 * Multi-step reservation booking for MenuDirect restaurants
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('reservationWidget', (config = {}) => ({
        apiBaseUrl: config.apiBaseUrl || '',
        restaurantSlug: config.restaurantSlug || '',
        maxPartySize: config.maxPartySize || 10,
        primaryColor: config.primaryColor || '#1f2937',

        // State
        step: 1,
        error: null,
        isSubmitting: false,

        // Step 1: Party size, date & time
        partySize: 2,
        selectedDate: '',
        selectedTime: '',
        todayDate: new Date().toISOString().split('T')[0],
        slots: [],
        slotsLoading: false,

        // Step 2: Contact info
        customerName: '',
        customerEmail: '',
        customerPhone: '',
        specialRequests: '',

        // Step 3: Confirmation
        confirmation: null,

        async loadSlots() {
            if (!this.selectedDate) return;

            this.slotsLoading = true;
            this.slots = [];
            this.selectedTime = '';

            try {
                const params = new URLSearchParams({
                    date: this.selectedDate,
                    party_size: this.partySize
                });

                const response = await fetch(
                    `${this.apiBaseUrl}/api/restaurant/${this.restaurantSlug}/reservations/availability?${params}`
                );

                if (response.ok) {
                    const data = await response.json();
                    this.slots = data.slots || [];
                }
            } catch (e) {
                console.warn('Failed to load reservation slots:', e);
            } finally {
                this.slotsLoading = false;
            }
        },

        selectSlot(slot) {
            if (!slot.available) return;
            this.selectedTime = slot.time;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr + 'T12:00:00');
            return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        },

        async submitReservation() {
            this.error = null;

            if (!this.customerName.trim()) {
                this.error = 'Please enter your name';
                return;
            }
            if (!this.customerEmail.trim() || !this.customerEmail.includes('@')) {
                this.error = 'Please enter a valid email';
                return;
            }
            if (!this.customerPhone.trim()) {
                this.error = 'Please enter your phone number';
                return;
            }

            this.isSubmitting = true;

            try {
                const response = await fetch(
                    `${this.apiBaseUrl}/api/restaurant/${this.restaurantSlug}/reservations`,
                    {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            reservation_date: this.selectedDate,
                            reservation_time: this.selectedTime,
                            party_size: this.partySize,
                            customer_name: this.customerName.trim(),
                            customer_email: this.customerEmail.trim(),
                            customer_phone: this.customerPhone.trim(),
                            special_requests: this.specialRequests.trim() || null
                        })
                    }
                );

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Failed to book reservation');
                }

                this.confirmation = result.reservation;
                this.step = 3;
            } catch (e) {
                this.error = e.message || 'Failed to book reservation. Please try again.';
            } finally {
                this.isSubmitting = false;
            }
        },

        resetWidget() {
            this.step = 1;
            this.error = null;
            this.selectedDate = '';
            this.selectedTime = '';
            this.partySize = 2;
            this.slots = [];
            this.customerName = '';
            this.customerEmail = '';
            this.customerPhone = '';
            this.specialRequests = '';
            this.confirmation = null;
        }
    }));
});
