<template>
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md">
        <!-- Header with Avatar and Basic Info -->
        <div class="p-6">
            <div class="flex items-start space-x-4">
                <img :src="mentor.user.avatar_url || '/default-avatar.png'" :alt="mentor.user.name" class="h-16 w-16 rounded-full object-cover" />
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-lg font-semibold text-gray-900">
                        {{ mentor.user.name }}
                    </h3>
                    <p class="truncate text-sm text-gray-600">
                        {{ mentor.user.current_title || 'Professional' }}
                    </p>
                    <p class="truncate text-sm text-gray-500">
                        {{ mentor.user.current_company || 'Alumni Network' }}
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <span :class="availabilityClass" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                        {{ availabilityText }}
                    </span>
                </div>
            </div>

            <!-- Bio -->
            <div class="mt-4">
                <p class="line-clamp-3 text-sm text-gray-700">
                    {{ mentor.bio }}
                </p>
            </div>

            <!-- Expertise Areas -->
            <div class="mt-4">
                <h4 class="mb-2 text-sm font-medium text-gray-900">Expertise</h4>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="area in mentor.expertise_areas.slice(0, 3)"
                        :key="area"
                        class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800"
                    >
                        {{ area }}
                    </span>
                    <span
                        v-if="mentor.expertise_areas.length > 3"
                        class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600"
                    >
                        +{{ mentor.expertise_areas.length - 3 }} more
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ mentor.completed_mentorships || 0 }}
                    </div>
                    <div class="text-xs text-gray-500">Mentorships</div>
                </div>
                <div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ mentor.average_rating || 'N/A' }}
                    </div>
                    <div class="text-xs text-gray-500">Rating</div>
                </div>
            </div>

            <!-- Match Score (if available) -->
            <div v-if="mentor.match_score" class="mt-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Match Score</span>
                    <span class="text-sm font-semibold text-green-600"> {{ Math.round(mentor.match_score * 100) }}% </span>
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-gray-200">
                    <div class="h-2 rounded-full bg-green-500 transition-all duration-300" :style="{ width: `${mentor.match_score * 100}%` }"></div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="rounded-b-lg border-t border-gray-200 bg-gray-50 px-6 py-4">
            <div class="flex space-x-3">
                <button
                    @click="viewProfile"
                    class="flex-1 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    View Profile
                </button>
                <button
                    @click="requestMentorship"
                    :disabled="!canRequestMentorship"
                    class="flex-1 rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ requestButtonText }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

// Props
const props = defineProps({
    mentor: {
        type: Object,
        required: true,
    },
});

// Emits
const emit = defineEmits(['request-mentorship', 'view-profile']);

// Computed properties
const availabilityClass = computed(() => {
    switch (props.mentor.availability) {
        case 'high':
            return 'bg-green-100 text-green-800';
        case 'medium':
            return 'bg-yellow-100 text-yellow-800';
        case 'low':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
});

const availabilityText = computed(() => {
    switch (props.mentor.availability) {
        case 'high':
            return 'High Availability';
        case 'medium':
            return 'Medium Availability';
        case 'low':
            return 'Low Availability';
        default:
            return 'Unknown';
    }
});

const canRequestMentorship = computed(() => {
    // Check if mentor has available slots
    const currentMentees = props.mentor.current_mentees || 0;
    const maxMentees = props.mentor.max_mentees || 3;
    return currentMentees < maxMentees && props.mentor.is_active;
});

const requestButtonText = computed(() => {
    if (!props.mentor.is_active) {
        return 'Unavailable';
    }

    const currentMentees = props.mentor.current_mentees || 0;
    const maxMentees = props.mentor.max_mentees || 3;

    if (currentMentees >= maxMentees) {
        return 'Fully Booked';
    }

    return 'Request Mentorship';
});

// Methods
const viewProfile = () => {
    emit('view-profile', props.mentor);
};

const requestMentorship = () => {
    if (canRequestMentorship.value) {
        emit('request-mentorship', props.mentor);
    }
};
</script>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
