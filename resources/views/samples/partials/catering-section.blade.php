{{-- Catering Section --}}
@if(!empty($site['catering']['enabled']))
<section class="py-16 bg-white" id="catering">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-4 text-gray-900">Catering</h2>
        <p class="text-center text-gray-600 mb-10 max-w-2xl mx-auto">
            @if(!empty($site['catering']['custom_message']))
                {{ $site['catering']['custom_message'] }}
            @else
                Let us cater your next event! Browse our packages below or send us an inquiry.
            @endif
        </p>

        @if(!empty($site['catering']['packages']))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            @foreach($site['catering']['packages'] as $package)
            <div class="bg-gray-50 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                @if(!empty($package['image']))
                <img src="{{ $package['image'] }}" alt="{{ $package['name'] }}" class="w-full h-48 object-cover">
                @endif
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-bold text-gray-900">{{ $package['name'] }}</h3>
                        <span class="text-lg font-bold" style="color: {{ $site['colors']['primary'] ?? '#2563eb' }}">{{ $package['price'] }}</span>
                    </div>
                    @if(!empty($package['description']))
                    <p class="text-gray-600 text-sm mb-3">{{ $package['description'] }}</p>
                    @endif
                    <div class="flex gap-3 text-xs text-gray-500 mb-3">
                        @if(!empty($package['min_guests']))
                        <span>Min {{ $package['min_guests'] }} guests</span>
                        @endif
                        @if(!empty($package['max_guests']))
                        <span>Max {{ $package['max_guests'] }} guests</span>
                        @endif
                    </div>
                    @if(!empty($package['includes']))
                    <ul class="text-sm text-gray-600 space-y-1">
                        @foreach($package['includes'] as $item)
                        @if($item)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" style="color: {{ $site['colors']['primary'] ?? '#2563eb' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $item }}
                        </li>
                        @endif
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Catering Inquiry Form --}}
        <div class="max-w-2xl mx-auto">
            <div class="bg-gray-50 rounded-xl p-8" x-data="cateringInquiry()">
                <h3 class="text-xl font-bold text-gray-900 mb-6 text-center">Request Catering</h3>

                <template x-if="!submitted">
                    <form @submit.prevent="submitInquiry()" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                                <input type="text" x-model="form.customer_name" required class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent" style="--tw-ring-color: {{ $site['colors']['primary'] ?? '#2563eb' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                                <input type="tel" x-model="form.customer_phone" required class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" x-model="form.customer_email" required class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Event Date</label>
                                <input type="date" x-model="form.event_date" :min="minDate" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Event Time</label>
                                <input type="time" x-model="form.event_time" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Guest Count</label>
                                <input type="number" x-model="form.guest_count" min="1" placeholder="{{ $site['catering']['min_guests'] ?? 10 }}+" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                                <select x-model="form.event_type" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent">
                                    <option value="">Select type...</option>
                                    <option value="wedding">Wedding</option>
                                    <option value="corporate">Corporate Event</option>
                                    <option value="birthday">Birthday Party</option>
                                    <option value="anniversary">Anniversary</option>
                                    <option value="graduation">Graduation</option>
                                    <option value="holiday">Holiday Party</option>
                                    <option value="funeral">Memorial/Funeral</option>
                                    <option value="fundraiser">Fundraiser</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            @if(!empty($site['catering']['packages']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Package</label>
                                <select x-model="form.catering_package_id" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent">
                                    <option value="">No preference</option>
                                    @foreach($site['catering']['packages'] as $package)
                                    <option value="{{ $package['id'] }}">{{ $package['name'] }} ({{ $package['price'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tell us about your event</label>
                            <textarea x-model="form.message" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-2 focus:border-transparent" placeholder="Dietary needs, theme, special requests..."></textarea>
                        </div>

                        <p x-show="error" x-text="error" class="text-red-600 text-sm"></p>

                        <button type="submit" :disabled="isSubmitting"
                                class="w-full py-3 rounded-lg text-white font-semibold transition disabled:opacity-50"
                                :style="{ backgroundColor: '{{ $site['colors']['primary'] ?? '#2563eb' }}' }">
                            <span x-show="!isSubmitting">Send Inquiry</span>
                            <span x-show="isSubmitting">Sending...</span>
                        </button>
                    </form>
                </template>

                <template x-if="submitted">
                    <div class="text-center py-8">
                        <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: {{ $site['colors']['primary'] ?? '#2563eb' }}20">
                            <svg class="w-8 h-8" style="color: {{ $site['colors']['primary'] ?? '#2563eb' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Inquiry Submitted!</h3>
                        <p class="text-gray-600 mb-2">Reference: <strong x-text="inquiryNumber"></strong></p>
                        <p class="text-gray-600">We'll be in touch shortly to discuss your event.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</section>

<script>
function cateringInquiry() {
    const leadTimeHours = {{ intval($site['catering']['lead_time_hours'] ?? 48) }};
    const minDateObj = new Date();
    minDateObj.setHours(minDateObj.getHours() + leadTimeHours);

    return {
        form: {
            customer_name: '',
            customer_email: '',
            customer_phone: '',
            event_date: '',
            event_time: '',
            guest_count: '',
            event_type: '',
            catering_package_id: '',
            message: '',
        },
        minDate: minDateObj.toISOString().split('T')[0],
        isSubmitting: false,
        submitted: false,
        inquiryNumber: '',
        error: '',

        async submitInquiry() {
            this.isSubmitting = true;
            this.error = '';

            const payload = { ...this.form };
            if (!payload.catering_package_id) delete payload.catering_package_id;
            if (!payload.event_date) delete payload.event_date;
            if (!payload.event_time) delete payload.event_time;
            if (!payload.guest_count) delete payload.guest_count;
            if (!payload.event_type) delete payload.event_type;
            if (payload.guest_count) payload.guest_count = parseInt(payload.guest_count);

            try {
                const response = await fetch('{{ config("services.sostech.portal_api_url", "https://portal.sos-tech.ca") }}/api/restaurant/{{ $site["slug"] }}/catering/inquiries', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.submitted = true;
                    this.inquiryNumber = data.inquiry_number;
                } else {
                    this.error = data.error || data.message || 'Something went wrong. Please try again.';
                }
            } catch (e) {
                this.error = 'Unable to submit. Please call us directly.';
            }

            this.isSubmitting = false;
        }
    };
}
</script>
@endif