<template>
    <div
        :class="[
            'speaker-card overflow-hidden rounded-lg border border-gray-200 bg-white shadow-md transition-shadow duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800',
            featured ? 'border-2 border-yellow-300 dark:border-yellow-600' : '',
        ]"
    >
        <!-- Speaker Header -->
        <div class="relative">
            <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600">
                <img v-if="speaker.banner_image" :src="speaker.banner_image" :alt="speaker.name" class="h-full w-full object-cover" />
            </div>

            <!-- Featured Badge -->
            <div v-if="featured" class="absolute left-3 top-3">
                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                    <StarIcon class="mr-1 h-3 w-3" />
                    Featured
                </span>
            </div>

            <!-- Availability Status -->
            <div class="absolute right-3 top-3">
                <span
                    :class="getAvailabilityClass(speaker.availability_status)"
                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                >
                    {{ formatAvailabilityStatus(speaker.availability_status) }}
                </span>
            </div>

            <!-- Speaker Avatar -->
            <div class="absolute -bottom-8 left-6">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-gray-300 dark:border-gray-800 dark:bg-gray-600"
                >
                    <UserIcon class="h-8 w-8 text-gray-600 dark:text-gray-300" />
                </div>
            </div>
        </div>

        <!-- Speaker Info -->
        <div class="p-6 pt-10">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ speaker.name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ speaker.current_position }}</p>
                <p class="text-sm text-blue-600 dark:text-blue-400">{{ speaker.current_company }}</p>
            </div>

            <!-- Speaker Details -->
            <div class="mb-4 space-y-2">
                <!-- Education -->
                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <AcademicCapIcon class="h-4 w-4" />
                    <span>{{ speaker.degree }} â€¢ Class of {{ speaker.graduation_year }}</span>
                </div>

                <!-- Speaking Experience -->
                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <MicrophoneIcon class="h-4 w-4" />
                    <span>{{ speaker.speaking_events_count || 0 }} speaking events</span>
                </div>

                <!-- Rating -->
                <div v-if="speaker.rating" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center space-x-1">
                        <StarIcon class="h-4 w-4 fill-current text-yellow-400" />
                        <span class="font-medium">{{ speaker.rating }}</span>
                        <span>({{ speaker.reviews_count || 0 }} reviews)</span>
                    </div>
                </div>

                <!-- Location -->
                <div v-if="speaker.location" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <MapPinIcon class="h-4 w-4" />
                    <span>{{ speaker.location }}</span>
                </div>
            </div>

            <!-- Speaking Topics -->
            <div class="mb-4">
                <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Speaking Topics</h4>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="topic in speaker.speaking_topics.slice(0, 3)"
                        :key="topic"
                        class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                    >
                        {{ topic }}
                    </span>
                    <span
                        v-if="speaker.speaking_topics.length > 3"
                        class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                    >
                        +{{ speaker.speaking_topics.length - 3 }} more
                    </span>
                </div>
            </div>

            <!-- Event Types -->
            <div v-if="speaker.preferred_event_types && speaker.preferred_event_types.length > 0" class="mb-4">
                <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Event Types</h4>
                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="type in speaker.preferred_event_types"
                        :key="type"
                        class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                    >
                        {{ formatEventType(type) }}
                    </span>
                </div>
            </div>

            <!-- Speaker Bio -->
            <div v-if="speaker.bio" class="mb-4">
                <p class="line-clamp-3 text-sm text-gray-700 dark:text-gray-300">
                    {{ speaker.bio }}
                </p>
            </div>

            <!-- Speaking Highlights -->
            <div v-if="speaker.speaking_highlights && speaker.speaking_highlights.length > 0" class="mb-4">
                <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Recent Highlights</h4>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li v-for="highlight in speaker.speaking_highlights.slice(0, 2)" :key="highlight" class="flex items-start space-x-2">
                        <div class="mt-2 h-1 w-1 rounded-full bg-blue-500"></div>
                        <span>{{ highlight }}</span>
                    </li>
                </ul>
            </div>

            <!-- Availability Info -->
            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Next Available:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ speaker.next_available_date || 'Contact for availability' }}
                    </span>
                </div>
                <div v-if="speaker.travel_preference" class="mt-1 flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Travel:</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ formatTravelPreference(speaker.travel_preference) }}
                    </span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <button
                    @click="requestSpeaker"
                    class="w-full rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                >
                    Request Speaker
                </button>

                <div class="flex space-x-2">
                    <button
                        @click="viewProfile"
                        class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-center text-sm text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    >
                        View Profile
                    </button>

                    <button
                        v-if="speaker.sample_videos && speaker.sample_videos.length > 0"
                        @click="viewSamples"
                        class="flex-1 rounded-md border border-purple-300 px-3 py-2 text-center text-sm text-purple-600 transition-colors hover:bg-purple-50 hover:text-purple-800 dark:border-purple-600 dark:text-purple-400 dark:hover:bg-purple-900/20 dark:hover:text-purple-200"
                    >
                        View Samples
                    </button>
                </div>
            </div>

            <!-- Recent Testimonial -->
            <div v-if="speaker.latest_testimonial" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-700">
                    <p class="text-sm italic text-gray-700 dark:text-gray-300">"{{ speaker.latest_testimonial.content }}"</p>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        - {{ speaker.latest_testimonial.event_organizer }}, {{ speaker.latest_testimonial.event_name }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { AcademicCapIcon, MapPinIcon, MicrophoneIcon, StarIcon, UserIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    speaker: {
        type: Object,
        required: true,
    },
    featured: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['request-speaker', 'view-profile']);

const getAvailabilityClass = (status) => {
    const classes = {
        available: 'bg-green-100 text-green-800',
        busy: 'bg-red-100 text-red-800',
        limited: 'bg-yellow-100 text-yellow-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatAvailabilityStatus = (status) => {
    const statuses = {
        available: 'Available',
        busy: 'Busy',
        limited: 'Limited',
    };
    return statuses[status] || 'Contact';
};

const formatEventType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const formatTravelPreference = (preference) => {
    const preferences = {
        local_only: 'Local Only',
        regional: 'Regional',
        national: 'National',
        international: 'International',
        virtual_only: 'Virtual Only',
        hybrid: 'Hybrid',
    };
    return preferences[preference] || preference;
};

const requestSpeaker = () => {
    emit('request-speaker', props.speaker.id);
};

const viewProfile = () => {
    emit('view-profile', props.speaker.id);
};

const viewSamples = () => {
    // Open modal or navigate to samples page
    logger.log('View speaker samples');
};
</script>

<style scoped>
.speaker-card {
    transition:
        transform 0.2s ease-in-out,
        box-shadow 0.2s ease-in-out;
}

.speaker-card:hover {
    transform: translateY(-2px);
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

