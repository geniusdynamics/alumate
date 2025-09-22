<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="$emit('close')">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                        >
                            <!-- Header -->
                            <div class="border-b bg-white px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">Register for Event</h3>
                                    <button
                                        @click="$emit('close')"
                                        class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        <XMarkIcon class="h-6 w-6" />
                                    </button>
                                </div>
                            </div>

                            <!-- Event Summary -->
                            <div class="border-b bg-gray-50 px-6 py-4">
                                <h4 class="mb-2 font-medium text-gray-900">{{ event.title }}</h4>
                                <div class="space-y-1 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <CalendarIcon class="mr-2 h-4 w-4" />
                                        {{ formatDate(event.start_date) }}
                                    </div>
                                    <div class="flex items-center">
                                        <ClockIcon class="mr-2 h-4 w-4" />
                                        {{ formatTime(event.start_date, event.end_date) }}
                                    </div>
                                    <div class="flex items-center">
                                        <MapPinIcon class="mr-2 h-4 w-4" />
                                        <span v-if="event.format === 'virtual'">Virtual Event</span>
                                        <span v-else>{{ event.venue_name || event.venue_address || 'TBD' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Registration Form -->
                            <form @submit.prevent="handleSubmit" class="bg-white px-6 py-6">
                                <div class="space-y-6">
                                    <!-- Guests -->
                                    <div v-if="event.allow_guests">
                                        <label class="mb-2 block text-sm font-medium text-gray-700"> Number of Guests </label>
                                        <select
                                            v-model="form.guests_count"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option v-for="i in event.max_guests_per_attendee + 1" :key="i - 1" :value="i - 1">
                                                {{ i - 1 }} {{ i - 1 === 1 ? 'guest' : 'guests' }}
                                            </option>
                                        </select>
                                        <p class="mt-1 text-sm text-gray-500">You can bring up to {{ event.max_guests_per_attendee }} guests</p>
                                    </div>

                                    <!-- Guest Details -->
                                    <div v-if="event.allow_guests && form.guests_count > 0">
                                        <label class="mb-2 block text-sm font-medium text-gray-700"> Guest Details </label>
                                        <div class="space-y-3">
                                            <div v-for="i in form.guests_count" :key="i" class="rounded-md border border-gray-200 p-3">
                                                <h5 class="mb-2 text-sm font-medium text-gray-900">Guest {{ i }}</h5>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <input
                                                        v-model="form.guest_details[i - 1].name"
                                                        type="text"
                                                        placeholder="Full Name"
                                                        required
                                                        class="rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                                    />
                                                    <input
                                                        v-model="form.guest_details[i - 1].email"
                                                        type="email"
                                                        placeholder="Email (optional)"
                                                        class="rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Special Requirements -->
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">
                                            Special Requirements or Dietary Restrictions
                                        </label>
                                        <textarea
                                            v-model="form.special_requirements"
                                            rows="3"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Any accessibility needs, dietary restrictions, or other requirements..."
                                        ></textarea>
                                    </div>

                                    <!-- Additional Questions -->
                                    <div v-if="hasAdditionalQuestions">
                                        <h4 class="mb-3 text-sm font-medium text-gray-700">Additional Information</h4>
                                        <div class="space-y-4">
                                            <!-- Example additional questions -->
                                            <div>
                                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                                    How did you hear about this event?
                                                </label>
                                                <select
                                                    v-model="form.additional_data.how_heard"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                                >
                                                    <option value="">Select an option</option>
                                                    <option value="email">Email notification</option>
                                                    <option value="social_media">Social media</option>
                                                    <option value="friend">Friend or colleague</option>
                                                    <option value="website">Alumni website</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>

                                            <div v-if="event.type === 'networking'">
                                                <label class="mb-2 block text-sm font-medium text-gray-700">
                                                    What are you hoping to get out of this event?
                                                </label>
                                                <textarea
                                                    v-model="form.additional_data.expectations"
                                                    rows="2"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Your networking goals or expectations..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Information -->
                                    <div
                                        v-if="event.ticket_price && event.ticket_price > 0"
                                        class="rounded-md border border-yellow-200 bg-yellow-50 p-4"
                                    >
                                        <div class="flex items-center">
                                            <CurrencyDollarIcon class="mr-2 h-5 w-5 text-yellow-600" />
                                            <div>
                                                <h4 class="text-sm font-medium text-yellow-800">Payment Required</h4>
                                                <p class="text-sm text-yellow-700">
                                                    This event requires a ticket fee of ${{ event.ticket_price.toFixed(2) }}
                                                    {{
                                                        form.guests_count > 0
                                                            ? ` per person (total: $${(event.ticket_price * (form.guests_count + 1)).toFixed(2)})`
                                                            : ''
                                                    }}
                                                </p>
                                                <p class="mt-1 text-xs text-yellow-600">Payment will be processed after registration confirmation.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Terms and Conditions -->
                                    <div class="flex items-start">
                                        <input
                                            v-model="form.agree_to_terms"
                                            type="checkbox"
                                            required
                                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <label class="ml-2 text-sm text-gray-700">
                                            I agree to the event terms and conditions and understand that my registration may be subject to approval.
                                            <span v-if="event.requires_approval" class="font-medium text-yellow-600">
                                                This event requires organizer approval.
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="mt-8 flex items-center justify-between">
                                    <div class="text-sm text-gray-500">
                                        <template v-if="event.requires_approval"> Your registration will be reviewed by the organizer </template>
                                        <template v-else> You'll receive a confirmation email after registration </template>
                                    </div>

                                    <div class="flex items-center space-x-3">
                                        <button
                                            type="button"
                                            @click="$emit('close')"
                                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="loading || !form.agree_to_terms"
                                            class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                        >
                                            <template v-if="loading">
                                                <svg
                                                    class="-ml-1 mr-3 inline h-4 w-4 animate-spin text-white"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path
                                                        class="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                    ></path>
                                                </svg>
                                                Registering...
                                            </template>
                                            <template v-else>
                                                {{ event.requires_approval ? 'Submit for Approval' : 'Register Now' }}
                                            </template>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup lang="ts">
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { CalendarIcon, ClockIcon, CurrencyDollarIcon, MapPinIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { format, parseISO } from 'date-fns';
import { computed, reactive, ref, watch } from 'vue';

interface Event {
    id: number;
    title: string;
    type: string;
    format: string;
    start_date: string;
    end_date: string;
    venue_name?: string;
    venue_address?: string;
    allow_guests: boolean;
    max_guests_per_attendee: number;
    ticket_price?: number;
    requires_approval: boolean;
}

interface Props {
    show: boolean;
    event: Event;
}

interface Emits {
    (e: 'close'): void;
    (e: 'registered', registrationData: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const loading = ref(false);

const form = reactive({
    guests_count: 0,
    guest_details: [] as Array<{ name: string; email: string }>,
    special_requirements: '',
    additional_data: {
        how_heard: '',
        expectations: '',
    },
    agree_to_terms: false,
});

const hasAdditionalQuestions = computed(() => {
    return ['networking', 'workshop', 'professional'].includes(props.event.type);
});

// Watch guests count to manage guest details array
watch(
    () => form.guests_count,
    (newCount) => {
        if (newCount > form.guest_details.length) {
            // Add new guest detail objects
            for (let i = form.guest_details.length; i < newCount; i++) {
                form.guest_details.push({ name: '', email: '' });
            }
        } else if (newCount < form.guest_details.length) {
            // Remove excess guest detail objects
            form.guest_details.splice(newCount);
        }
    },
);

// Methods
const formatDate = (dateString: string) => {
    const date = parseISO(dateString);
    return format(date, 'EEEE, MMMM d, yyyy');
};

const formatTime = (startString: string, endString: string) => {
    const start = parseISO(startString);
    const end = parseISO(endString);
    return `${format(start, 'h:mm a')} - ${format(end, 'h:mm a')}`;
};

const handleSubmit = async () => {
    loading.value = true;

    try {
        const registrationData = {
            guests_count: form.guests_count,
            guest_details: form.guest_details.slice(0, form.guests_count),
            special_requirements: form.special_requirements || null,
            additional_data: form.additional_data,
        };

        emit('registered', registrationData);
    } catch (error) {
        console.error('Registration failed:', error);
        alert('Registration failed. Please try again.');
    } finally {
        loading.value = false;
    }
};

// Reset form when modal opens
watch(
    () => props.show,
    (show) => {
        if (show) {
            form.guests_count = 0;
            form.guest_details = [];
            form.special_requirements = '';
            form.additional_data = {
                how_heard: '',
                expectations: '',
            };
            form.agree_to_terms = false;
        }
    },
);
</script>
