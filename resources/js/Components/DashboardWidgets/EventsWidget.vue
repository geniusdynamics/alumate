<template>
    <div class="card-mobile border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">Upcoming Events</h3>
            <CalendarDaysIcon class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
        </div>

        <div class="space-y-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="flex space-x-3">
                        <div class="h-12 w-12 rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-3 w-1/2 rounded bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events -->
            <div v-else-if="events.length > 0" class="space-y-4">
                <div
                    v-for="event in events"
                    :key="event.id"
                    class="flex cursor-pointer space-x-3 rounded-lg p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    @click="viewEvent(event)"
                >
                    <!-- Date Badge -->
                    <div class="flex-shrink-0">
                        <div class="flex h-12 w-12 flex-col items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/50">
                            <span class="text-xs font-medium uppercase text-indigo-600 dark:text-indigo-400">
                                {{ formatMonth(event.start_date) }}
                            </span>
                            <span class="text-sm font-bold text-indigo-800 dark:text-indigo-200">
                                {{ formatDay(event.start_date) }}
                            </span>
                        </div>
                    </div>

                    <!-- Event Details -->
                    <div class="min-w-0 flex-1">
                        <h4 class="line-clamp-1 text-sm font-medium text-gray-900 dark:text-white">
                            {{ event.title }}
                        </h4>
                        <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                            <span class="flex items-center space-x-1">
                                <ClockIcon class="h-3 w-3" />
                                <span>{{ formatTime(event.start_date) }}</span>
                            </span>
                            <span v-if="event.location" class="flex items-center space-x-1">
                                <MapPinIcon class="h-3 w-3" />
                                <span class="truncate">{{ event.location }}</span>
                            </span>
                            <span v-else-if="event.is_virtual" class="flex items-center space-x-1">
                                <VideoCameraIcon class="h-3 w-3" />
                                <span>Virtual</span>
                            </span>
                        </div>

                        <!-- RSVP Status -->
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span v-if="event.attendee_count" class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ event.attendee_count }} attending
                                </span>
                                <span
                                    v-if="event.rsvp_status"
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                        event.rsvp_status === 'attending'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200'
                                            : event.rsvp_status === 'maybe'
                                              ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200'
                                              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    ]"
                                >
                                    {{ event.rsvp_status === 'attending' ? 'Going' : event.rsvp_status === 'maybe' ? 'Maybe' : 'Not Going' }}
                                </span>
                            </div>

                            <button
                                v-if="!event.rsvp_status"
                                @click.stop="rsvpToEvent(event, 'attending')"
                                class="rounded bg-indigo-600 px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-indigo-700"
                            >
                                RSVP
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-6 text-center">
                <CalendarDaysIcon class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming events</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Check back later for new events</p>
            </div>
        </div>

        <!-- View All Link -->
        <div v-if="events.length > 0" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <Link
                :href="route('events.index')"
                class="flex items-center justify-center space-x-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
                <span>View All Events</span>
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ArrowRightIcon, CalendarDaysIcon, ClockIcon, MapPinIcon, VideoCameraIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const loading = ref(true);
const events = ref([]);

const props = defineProps({
    limit: {
        type: Number,
        default: 3,
    },
});

onMounted(async () => {
    await fetchUpcomingEvents();
});

const fetchUpcomingEvents = async () => {
    try {
        loading.value = true;
        const response = await fetch(`/api/dashboard/upcoming-events?limit=${props.limit}`);
        const data = await response.json();
        events.value = data.events || [];
    } catch (error) {
        console.error('Failed to fetch upcoming events:', error);
        events.value = [];
    } finally {
        loading.value = false;
    }
};

const formatMonth = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short' });
};

const formatDay = (dateString) => {
    const date = new Date(dateString);
    return date.getDate();
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const viewEvent = (event) => {
    window.location.href = `/events/${event.id}`;
};

const rsvpToEvent = async (event, status) => {
    try {
        const response = await fetch(`/api/events/${event.id}/rsvp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ status }),
        });

        if (response.ok) {
            event.rsvp_status = status;
            if (status === 'attending') {
                event.attendee_count = (event.attendee_count || 0) + 1;
            }
        }
    } catch (error) {
        console.error('Failed to RSVP to event:', error);
    }
};
</script>

