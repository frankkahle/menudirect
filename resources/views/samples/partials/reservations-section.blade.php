{{-- Reservations Section --}}
@if(!empty($site['reservations']['enabled']))
<section class="py-16 bg-gray-50" id="reservations">
    <div class="max-w-2xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Make a Reservation</h2>
        <p class="text-gray-600 text-center mb-8">Book your table in advance</p>

        @if(($site['reservations']['type'] ?? 'email') === 'built_in')
        {{-- Built-in Reservation Widget --}}
        <div x-data="reservationWidget({
            apiBaseUrl: '',
            restaurantSlug: '{{ $site['slug'] }}',
            maxPartySize: {{ intval($site['reservations']['max_party_size'] ?? 10) }},
            primaryColor: '{{ $site['colors']['primary'] ?? '#1f2937' }}'
        })">
            {{-- Step 1: Party size & date --}}
            <div x-show="step === 1" class="bg-white rounded-lg shadow-md p-6">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Party Size</label>
                            <select x-model="partySize" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                                <template x-for="i in maxPartySize" :key="i">
                                    <option :value="i" x-text="i + (i === 1 ? ' Guest' : ' Guests')"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" x-model="selectedDate" :min="todayDate"
                                   @change="loadSlots()"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>

                    <template x-if="slotsLoading">
                        <div class="text-center py-4 text-gray-500">
                            <svg class="animate-spin w-6 h-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Loading available times...
                        </div>
                    </template>

                    <template x-if="!slotsLoading && slots.length > 0">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Available Times</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                <template x-for="slot in slots" :key="slot.time">
                                    <button type="button"
                                            @click="selectSlot(slot)"
                                            :disabled="!slot.available"
                                            class="py-2 px-3 rounded-md text-sm font-medium transition"
                                            :class="selectedTime === slot.time ? 'text-white' : (slot.available ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-gray-50 text-gray-300 cursor-not-allowed')"
                                            :style="selectedTime === slot.time ? 'background:' + primaryColor : ''"
                                            x-text="slot.time">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="!slotsLoading && selectedDate && slots.length === 0">
                        <p class="text-center py-4 text-gray-500">No available times for this date. Please try another date.</p>
                    </template>

                    <button @click="step = 2" :disabled="!selectedTime"
                            class="w-full py-3 rounded-lg font-semibold text-white disabled:opacity-50 transition"
                            :style="'background:' + primaryColor">
                        Continue
                    </button>
                </div>
            </div>

            {{-- Step 2: Contact info --}}
            <div x-show="step === 2" class="bg-white rounded-lg shadow-md p-6">
                <div class="space-y-4">
                    <button @click="step = 1" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back
                    </button>

                    <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
                        <span x-text="partySize"></span> <span x-text="partySize == 1 ? 'guest' : 'guests'"></span> &middot;
                        <span x-text="formatDate(selectedDate)"></span> at <span x-text="selectedTime"></span>
                    </div>

                    <template x-if="error">
                        <div class="bg-red-50 border-l-4 border-red-500 p-3 text-sm text-red-700" x-text="error"></div>
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" x-model="customerName" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" x-model="customerEmail" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                            <input type="tel" x-model="customerPhone" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
                        <textarea x-model="specialRequests" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand" placeholder="Allergies, high chair, birthday..."></textarea>
                    </div>

                    <button @click="submitReservation()" :disabled="isSubmitting"
                            class="w-full py-3 rounded-lg font-semibold text-white disabled:opacity-50 transition"
                            :style="'background:' + primaryColor">
                        <span x-show="!isSubmitting">Book Table</span>
                        <span x-show="isSubmitting">Booking...</span>
                    </button>
                </div>
            </div>

            {{-- Step 3: Confirmation --}}
            <div x-show="step === 3" class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2" x-text="confirmation?.auto_confirmed ? 'Reservation Confirmed!' : 'Reservation Requested!'"></h3>
                <p class="text-gray-600 mb-4" x-text="confirmation?.auto_confirmed ? 'Your table is booked.' : 'We\'ll confirm your reservation shortly.'"></p>
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-500">Confirmation Number</p>
                    <p class="text-lg font-bold" :style="'color:' + primaryColor" x-text="confirmation?.confirmation_number"></p>
                    <p class="text-sm text-gray-600 mt-2">
                        <span x-text="partySize"></span> guests &middot; <span x-text="formatDate(selectedDate)"></span> at <span x-text="selectedTime"></span>
                    </p>
                </div>
                <p class="text-sm text-gray-500 mb-4">A confirmation email has been sent to <strong x-text="customerEmail"></strong>.</p>
                <template x-if="confirmation?.status_url">
                    <a :href="confirmation.status_url" class="text-sm hover:underline" :style="'color:' + primaryColor">View or manage your reservation</a>
                </template>
                <button @click="resetWidget()" class="mt-4 w-full py-3 rounded-lg font-semibold border border-gray-300 hover:bg-gray-100 transition">
                    Make Another Reservation
                </button>
            </div>
        </div>
        @elseif(($site['reservations']['type'] ?? 'email') === 'opentable' && !empty($site['reservations']['opentable_id']))
        <div class="text-center">
            <script type="text/javascript" src="//www.opentable.com/widget/reservation/loader?rid={{ $site['reservations']['opentable_id'] }}&type=standard&theme=standard&color=1&dark=false&iframe=true&domain=com&lang=en-US&newtab=false&ot_source=Restaurant%20website"></script>
        </div>
        @elseif(($site['reservations']['type'] ?? 'email') === 'custom' && !empty($site['reservations']['custom_embed']))
        <div class="reservation-embed">
            {!! $site['reservations']['custom_embed'] !!}
        </div>
        @else
        {{-- Simple email reservation form --}}
        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="mailto:{{ $site['reservations']['email'] ?? $site['email'] }}" method="post" enctype="text/plain" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" required min="{{ date('Y-m-d') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" name="time" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Party Size</label>
                        <select name="party_size" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand">
                            @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'Guest' : 'Guests' }}</option>
                            @endfor
                            <option value="10+">10+ Guests</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
                    <textarea name="notes" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand"></textarea>
                </div>
                <button type="submit" class="w-full py-3 rounded-lg font-semibold text-white transition" style="background: {{ $site['colors']['primary'] ?? '#1f2937' }};">
                    Request Reservation
                </button>
                <p class="text-sm text-gray-500 text-center">We'll confirm your reservation by phone or email.</p>
            </form>
        </div>
        @endif
    </div>
</section>
@endif