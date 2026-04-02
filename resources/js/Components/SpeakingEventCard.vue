<template>
    <div
        class="speaking-event-card rounded-lg border border-gray-200 bg-white p-6 shadow-md transition-shadow duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <!-- Event Header -->
        <div class="mb-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">{{ event.title }}</h3>
                    <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">{{ event.description }}</p>
                </div>
                <div class="ml-4">
                    <span :class="getEventStatusClass(event.status)" class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium">
                        {{ formatEventStatus(event.status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Event Details -->
        <div class="mb-4 space-y-3">
            <!-- Date and Time -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <CalendarIcon class="h-4 w-4" />
                <span>{{ formatEventDate(event.event_date) }}</span>
                <span v-if="event.event_time">â€¢ {{ event.event_time }}</span>
            </div>

            <!-- Location -->
            <div v-if="event.location" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <MapPinIcon class="h-4 w-4" />
                <span>{{ event.location }}</span>
                <span v-if="event.is_virtual" class="text-blue-600 dark:text-blue-400">(Virtual)</span>
            </div>

            <!-- Event Type -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <TagIcon class="h-4 w-4" />
                <span>{{ formatEventType(event.event_type) }}</span>
            </div>

            <!-- Audience -->
            <div v-if="event.audience_size" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <UsersIcon class="h-4 w-4" />
                <span>{{ event.audience_size }} attendees</span>
                <span v-if="event.audience_type">â€¢ {{ event.audience_type }}</span>
            </div>

            <!-- Duration -->
            <div v-if="event.duration" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <ClockIcon class="h-4 w-4" />
                <span>{{ event.duration }} minutes</span>
            </div>
        </div>

        <!-- Speaker Info -->
        <div v-if="event.speaker" class="mb-4 rounded-md bg-gray-50 p-3 dark:bg-gray-700">
            <div class="flex items-center space-x-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                    <UserIcon class="h-5 w-5 text-gray-600 dark:text-gray-300" />
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ event.speaker.user.name }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ event.speaker.user.current_position }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ event.speaker.user.current_company }}</p>
                </div>
            </div>
        </div>

        <!-- Event Topics -->
        <div v-if="event.topics && event.topics.length > 0" class="mb-4">
            <h5 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Topics</h5>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="topic in event.topics.slice(0, 3)"
                    :key="topic"
                    class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ topic }}
                </span>
                <span
                    v-if="event.topics.length > 3"
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ event.topics.length - 3 }} more
                </span>
            </div>
        </div>

        <!-- Organizer Info -->
        <div v-if="event.organizer" class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium">Organized by:</span> {{ event.organizer.name }}
            <span v-if="event.organizer.organization"> â€¢ {{ event.organizer.organization }}</span>
        </div>

        <!-- Registration Info -->
        <div v-if="event.registration_required" class="mb-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Registration:</span>
                <div class="flex items-center space-x-2">
                    <span
                        :class="event.registration_open ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                        class="font-medium"
                    >
                        {{ event.registration_open ? 'Open' : 'Closed' }}
                    </span>
                    <span v-if="event.registration_deadline" class="text-gray-500 dark:text-gray-400">
                        (Deadline: {{ formatDate(event.registration_deadline) }})
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                @click="viewDetails"
                class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            >
                View Details
            </button>

            <button
                v-if="event.registration_open && event.registration_required"
                @click="register"
                class="rounded-md border border-blue-300 px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:border-blue-400 hover:text-blue-800 dark:border-blue-600 dark:text-blue-400 dark:hover:text-blue-200"
            >
                Register
            </button>

            <button
                v-if="event.is_virtual && event.meeting_link"
                @click="joinVirtual"
                class="rounded-md border border-green-300 px-4 py-2 text-sm font-medium text-green-600 transition-colors hover:border-green-400 hover:text-green-800 dark:border-green-600 dark:text-green-400 dark:hover:text-green-200"
            >
                Join Virtual
            </button>
        </div>

        <!-- Additional Info -->
        <div v-if="event.special_requirements" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <h5 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Special Requirements</h5>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ event.special_requirements }}</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { CalendarIcon, ClockIcon, MapPinIcon, TagIcon, UserIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { format } from 'date-fns';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['view-details', 'register', 'join-virtual']);

const getEventStatusClass = (status) => {
    const classes = {
        confirmed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
        completed: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatEventStatus = (status) => {
    return status.charAt(0).toUpperCase() + status.slice(1);
};

const formatEventType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const formatEventDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const viewDetails = () => {
    emit('view-details', props.event.id);
};

const register = () => {
    emit('register', props.event.id);
};

const joinVirtual = () => {
    if (props.event.meeting_link) {
        window.open(props.event.meeting_link, '_blank');
    } else {
        emit('join-virtual', props.event.id);
    }
};
</script>

<style scoped>
.speaking-event-card {
    transition:
        transform 0.2s ease-in-out,
        box-shadow 0.2s ease-in-out;
}

.speaking-event-card:hover {
    transform: translateY(-2px);
}
</style>

