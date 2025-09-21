<template>
    <div class="rounded-lg border-l-4 border-orange-400 bg-white p-6 shadow-md">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center">
                <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-orange-100">
                    <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        ></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Coffee Chat Request</h3>
                    <p class="text-sm text-gray-600">From {{ request.sender.name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span :class="statusClasses" class="rounded-full px-2 py-1 text-xs font-medium">
                    {{ request.status }}
                </span>
            </div>
        </div>

        <div class="mb-4">
            <div class="mb-2 flex items-center">
                <img :src="request.sender.avatar || '/default-avatar.png'" :alt="request.sender.name" class="mr-3 h-8 w-8 rounded-full" />
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ request.sender.name }}</p>
                    <p class="text-xs text-gray-600">{{ request.sender.title }}</p>
                </div>
            </div>
        </div>

        <div class="mb-4 space-y-2">
            <div class="flex items-center text-sm text-gray-600">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a2 2 0 012-2z"
                    ></path>
                </svg>
                <strong>Topic:</strong> {{ request.topic }}
            </div>
            <div class="flex items-center text-sm text-gray-600">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <strong>Duration:</strong> {{ request.duration }} minutes
            </div>
            <div class="flex items-center text-sm text-gray-600">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    ></path>
                </svg>
                <strong>Time Preference:</strong> {{ formatTimePreference(request.timePreference) }}
            </div>
        </div>

        <div v-if="request.message" class="mb-4 rounded-md bg-gray-50 p-3">
            <p class="text-sm text-gray-700">{{ request.message }}</p>
        </div>

        <div class="mb-4 flex items-center justify-between text-xs text-gray-500">
            <span>Requested {{ formatDate(request.createdAt) }}</span>
            <span v-if="request.respondedAt">Responded {{ formatDate(request.respondedAt) }}</span>
        </div>

        <div v-if="request.status === 'pending'" class="flex justify-end space-x-3">
            <button @click="declineRequest" class="rounded-md bg-gray-200 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-300">
                Decline
            </button>
            <button @click="acceptRequest" class="rounded-md bg-green-600 px-4 py-2 text-sm text-white transition-colors hover:bg-green-700">
                Accept & Schedule
            </button>
        </div>

        <div v-else-if="request.status === 'accepted'" class="flex justify-end">
            <button @click="viewScheduledCall" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white transition-colors hover:bg-blue-700">
                View Scheduled Call
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface CoffeeChatRequest {
    id: number;
    sender: {
        name: string;
        title: string;
        avatar?: string;
    };
    topic: string;
    duration: number;
    timePreference: string;
    message?: string;
    status: 'pending' | 'accepted' | 'declined' | 'scheduled';
    createdAt: string;
    respondedAt?: string;
}

interface Props {
    request: CoffeeChatRequest;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    accept: [id: number];
    decline: [id: number];
    viewCall: [id: number];
}>();

const statusClasses = computed(() => {
    switch (props.request.status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'accepted':
            return 'bg-green-100 text-green-800';
        case 'declined':
            return 'bg-red-100 text-red-800';
        case 'scheduled':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
});

const formatTimePreference = (preference: string) => {
    const preferences = {
        morning: 'Morning (9 AM - 12 PM)',
        afternoon: 'Afternoon (12 PM - 5 PM)',
        evening: 'Evening (5 PM - 8 PM)',
        flexible: 'Flexible',
    };
    return preferences[preference as keyof typeof preferences] || preference;
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const acceptRequest = () => emit('accept', props.request.id);
const declineRequest = () => emit('decline', props.request.id);
const viewScheduledCall = () => emit('viewCall', props.request.id);
</script>
