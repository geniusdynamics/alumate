<template>
    <div
        class="rounded-lg border border-gray-200 bg-white p-6 shadow-md transition-shadow duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <!-- Session Header -->
        <div class="mb-4 flex items-start justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <img
                        :src="session.mentor.avatar || '/default-avatar.png'"
                        :alt="session.mentor.name"
                        class="h-12 w-12 rounded-full object-cover"
                    />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ session.title || 'Mentorship Session' }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">with {{ session.mentor.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">{{ session.mentor.title }} at {{ session.mentor.company }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span :class="getStatusBadgeClass(session.status)" class="rounded-full px-2 py-1 text-xs font-medium">
                    {{ getStatusText(session.status) }}
                </span>
            </div>
        </div>

        <!-- Session Details -->
        <div class="mb-4 space-y-3">
            <!-- Date and Time -->
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <CalendarIcon class="mr-2 h-4 w-4 text-gray-400" />
                <span>{{ formatDateTime(session.scheduled_at) }}</span>
            </div>

            <!-- Duration -->
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <ClockIcon class="mr-2 h-4 w-4 text-gray-400" />
                <span>{{ session.duration || 60 }} minutes</span>
            </div>

            <!-- Meeting Type -->
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <VideoCameraIcon v-if="session.meeting_type === 'virtual'" class="mr-2 h-4 w-4 text-gray-400" />
                <MapPinIcon v-else class="mr-2 h-4 w-4 text-gray-400" />
                <span>{{ session.meeting_type === 'virtual' ? 'Virtual Meeting' : session.location || 'In Person' }}</span>
            </div>

            <!-- Session Topic -->
            <div v-if="session.topic" class="flex items-start text-sm text-gray-600 dark:text-gray-400">
                <ChatBubbleLeftRightIcon class="mr-2 mt-0.5 h-4 w-4 text-gray-400" />
                <span>{{ session.topic }}</span>
            </div>
        </div>

        <!-- Session Goals -->
        <div v-if="session.goals && session.goals.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Session Goals:</h4>
            <ul class="space-y-1">
                <li v-for="goal in session.goals.slice(0, 3)" :key="goal" class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <CheckCircleIcon class="mr-2 h-3 w-3 text-green-500" />
                    {{ goal }}
                </li>
                <li v-if="session.goals.length > 3" class="ml-5 text-xs text-gray-500 dark:text-gray-500">
                    +{{ session.goals.length - 3 }} more goals
                </li>
            </ul>
        </div>

        <!-- Preparation Notes -->
        <div v-if="session.preparation_notes" class="mb-4 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
            <div class="flex items-start">
                <InformationCircleIcon class="mr-2 mt-0.5 h-4 w-4 text-blue-500" />
                <div>
                    <h4 class="mb-1 text-sm font-medium text-blue-900 dark:text-blue-300">Preparation Notes:</h4>
                    <p class="text-sm text-blue-800 dark:text-blue-400">{{ session.preparation_notes }}</p>
                </div>
            </div>
        </div>

        <!-- Time Until Session -->
        <div class="mb-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Time until session:</span>
                <span :class="getTimeUntilClass(session.scheduled_at)" class="font-medium">
                    {{ getTimeUntil(session.scheduled_at) }}
                </span>
            </div>
            <div class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    :class="getProgressBarClass(session.scheduled_at)"
                    class="h-2 rounded-full transition-all duration-300"
                    :style="`width: ${getProgressPercentage(session.scheduled_at)}%`"
                ></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="flex space-x-2">
                <!-- Join/View Meeting Button -->
                <button
                    v-if="canJoinSession(session)"
                    @click="$emit('join-session', session)"
                    class="inline-flex items-center rounded-md border border-transparent bg-green-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                >
                    <VideoCameraIcon class="mr-1 h-4 w-4" />
                    Join Session
                </button>

                <!-- Reschedule Button -->
                <button
                    v-if="canReschedule(session)"
                    @click="$emit('reschedule-session', session)"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <CalendarIcon class="mr-1 h-4 w-4" />
                    Reschedule
                </button>
            </div>

            <!-- More Options -->
            <div class="flex space-x-2">
                <button
                    @click="$emit('view-details', session)"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <EyeIcon class="mr-1 h-4 w-4" />
                    Details
                </button>

                <button
                    @click="$emit('send-message', session.mentor)"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <ChatBubbleLeftRightIcon class="mr-1 h-4 w-4" />
                    Message
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    CalendarIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    ClockIcon,
    EyeIcon,
    InformationCircleIcon,
    MapPinIcon,
    VideoCameraIcon,
} from '@heroicons/vue/24/outline';
import { addMinutes, format, formatDistanceToNow, isAfter, isBefore } from 'date-fns';

const props = defineProps({
    session: {
        type: Object,
        required: true,
        validator: (session) => {
            return session && session.mentor && session.scheduled_at && typeof session.mentor.name === 'string';
        },
    },
});

const emit = defineEmits(['join-session', 'reschedule-session', 'view-details', 'send-message']);

const getStatusBadgeClass = (status) => {
    const classes = {
        scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
        confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
        completed: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300',
    };
    return classes[status] || classes.scheduled;
};

const getStatusText = (status) => {
    const texts = {
        scheduled: 'Scheduled',
        confirmed: 'Confirmed',
        pending: 'Pending Confirmation',
        cancelled: 'Cancelled',
        completed: 'Completed',
    };
    return texts[status] || 'Scheduled';
};

const formatDateTime = (dateTime) => {
    const date = new Date(dateTime);
    return format(date, "MMM dd, yyyy 'at' h:mm a");
};

const getTimeUntil = (scheduledAt) => {
    const now = new Date();
    const sessionTime = new Date(scheduledAt);

    if (isBefore(sessionTime, now)) {
        return 'Session time has passed';
    }

    return formatDistanceToNow(sessionTime, { addSuffix: true });
};

const getTimeUntilClass = (scheduledAt) => {
    const now = new Date();
    const sessionTime = new Date(scheduledAt);
    const hoursUntil = (sessionTime - now) / (1000 * 60 * 60);

    if (hoursUntil < 0) return 'text-red-600 dark:text-red-400';
    if (hoursUntil < 1) return 'text-red-600 dark:text-red-400';
    if (hoursUntil < 24) return 'text-orange-600 dark:text-orange-400';
    return 'text-green-600 dark:text-green-400';
};

const getProgressPercentage = (scheduledAt) => {
    const now = new Date();
    const sessionTime = new Date(scheduledAt);
    const totalTime = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds
    const timeRemaining = sessionTime - now;

    if (timeRemaining <= 0) return 100;
    if (timeRemaining >= totalTime) return 0;

    return Math.max(0, Math.min(100, ((totalTime - timeRemaining) / totalTime) * 100));
};

const getProgressBarClass = (scheduledAt) => {
    const now = new Date();
    const sessionTime = new Date(scheduledAt);
    const hoursUntil = (sessionTime - now) / (1000 * 60 * 60);

    if (hoursUntil < 0) return 'bg-red-500';
    if (hoursUntil < 1) return 'bg-red-500';
    if (hoursUntil < 24) return 'bg-orange-500';
    return 'bg-green-500';
};

const canJoinSession = (session) => {
    const now = new Date();
    const sessionTime = new Date(session.scheduled_at);
    const sessionEnd = addMinutes(sessionTime, session.duration || 60);

    // Can join 15 minutes before session starts and during the session
    const joinWindow = addMinutes(sessionTime, -15);

    return isAfter(now, joinWindow) && isBefore(now, sessionEnd) && session.status === 'confirmed' && session.meeting_type === 'virtual';
};

const canReschedule = (session) => {
    const now = new Date();
    const sessionTime = new Date(session.scheduled_at);

    // Can reschedule if session is more than 24 hours away and not cancelled/completed
    return isAfter(sessionTime, addMinutes(now, 24 * 60)) && !['cancelled', 'completed'].includes(session.status);
};
</script>

<style scoped>
/* Additional custom styles if needed */
.session-card-hover {
    @apply transform transition-transform duration-200 hover:scale-105;
}
</style>
