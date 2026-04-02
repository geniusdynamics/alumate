<template>
    <div
        class="student-mentor-card rounded-lg border border-gray-200 bg-white p-6 shadow-md transition-shadow duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <!-- Mentor Header -->
        <div class="mb-4 flex items-start space-x-4">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                <UserIcon class="h-8 w-8 text-gray-600 dark:text-gray-300" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ mentor.name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ mentor.current_position }}</p>
                <p class="text-sm text-blue-600 dark:text-blue-400">{{ mentor.current_company }}</p>
            </div>
            <div class="flex items-center space-x-1">
                <StarIcon class="h-4 w-4 fill-current text-yellow-400" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ mentor.rating || 4.8 }}</span>
            </div>
        </div>

        <!-- Mentor Details -->
        <div class="mb-4 space-y-3">
            <!-- Education -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <AcademicCapIcon class="h-4 w-4" />
                <span>{{ mentor.degree }} â€¢ Class of {{ mentor.graduation_year }}</span>
            </div>

            <!-- Experience -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <BriefcaseIcon class="h-4 w-4" />
                <span>{{ mentor.years_experience }} years experience</span>
            </div>

            <!-- Location -->
            <div v-if="mentor.location" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <MapPinIcon class="h-4 w-4" />
                <span>{{ mentor.location }}</span>
            </div>

            <!-- Mentorship Stats -->
            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center space-x-1">
                    <UsersIcon class="h-4 w-4" />
                    <span>{{ mentor.mentees_count || 0 }} mentees</span>
                </div>
                <div class="flex items-center space-x-1">
                    <ClockIcon class="h-4 w-4" />
                    <span>{{ mentor.sessions_count || 0 }} sessions</span>
                </div>
            </div>
        </div>

        <!-- Expertise Areas -->
        <div class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Expertise</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="expertise in mentor.expertise_areas.slice(0, 3)"
                    :key="expertise"
                    class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ expertise }}
                </span>
                <span
                    v-if="mentor.expertise_areas.length > 3"
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ mentor.expertise_areas.length - 3 }} more
                </span>
            </div>
        </div>

        <!-- Mentorship Focus -->
        <div v-if="mentor.mentorship_focus" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Mentorship Focus</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ mentor.mentorship_focus }}</p>
        </div>

        <!-- Why This Mentor -->
        <div v-if="mentor.match_reason" class="mb-4">
            <div class="rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                <div class="flex items-start space-x-2">
                    <LightBulbIcon class="mt-0.5 h-4 w-4 text-green-600" />
                    <div>
                        <h5 class="text-sm font-medium text-green-800 dark:text-green-300">Why this mentor?</h5>
                        <p class="text-sm text-green-700 dark:text-green-400">{{ mentor.match_reason }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Availability -->
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Availability:</span>
                <div class="flex items-center space-x-2">
                    <div :class="mentor.is_available ? 'bg-green-500' : 'bg-red-500'" class="h-2 w-2 rounded-full"></div>
                    <span
                        class="text-sm font-medium"
                        :class="mentor.is_available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                    >
                        {{ mentor.is_available ? 'Available' : 'Busy' }}
                    </span>
                </div>
            </div>
            <p v-if="mentor.availability_note" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ mentor.availability_note }}
            </p>
        </div>

        <!-- Response Time -->
        <div v-if="mentor.avg_response_time" class="mb-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Avg. Response Time:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ mentor.avg_response_time }}</span>
            </div>
        </div>

        <!-- Session Types -->
        <div v-if="mentor.session_types && mentor.session_types.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Session Types</h4>
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="type in mentor.session_types"
                    :key="type"
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ formatSessionType(type) }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3">
            <div class="flex space-x-2">
                <button
                    @click="requestMentorship"
                    :disabled="mentor.request_sent"
                    :class="[
                        'flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                        mentor.request_sent
                            ? 'cursor-not-allowed bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                            : 'bg-blue-600 text-white hover:bg-blue-700',
                    ]"
                >
                    {{ mentor.request_sent ? 'Request Sent' : 'Request Mentorship' }}
                </button>

                <button
                    @click="scheduleIntro"
                    class="rounded-md border border-blue-300 px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:border-blue-400 hover:text-blue-800 dark:border-blue-600 dark:text-blue-400 dark:hover:text-blue-200"
                >
                    Intro Call
                </button>
            </div>

            <!-- Quick Actions -->
            <div class="flex space-x-2 text-sm">
                <Link
                    :href="route('mentors.profile', mentor.id)"
                    class="flex-1 rounded-md border border-gray-300 px-3 py-1 text-center text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                >
                    View Profile
                </Link>

                <button
                    v-if="mentor.success_stories_count > 0"
                    @click="viewStories"
                    class="flex-1 rounded-md border border-purple-300 px-3 py-1 text-center text-purple-600 transition-colors hover:bg-purple-50 hover:text-purple-800 dark:border-purple-600 dark:text-purple-400 dark:hover:bg-purple-900/20 dark:hover:text-purple-200"
                >
                    {{ mentor.success_stories_count }} Stories
                </button>
            </div>
        </div>

        <!-- Student Testimonial -->
        <div v-if="mentor.latest_testimonial" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-700">
                <p class="text-sm italic text-gray-700 dark:text-gray-300">"{{ mentor.latest_testimonial.content }}"</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    - {{ mentor.latest_testimonial.student_name }}, {{ mentor.latest_testimonial.student_year }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { AcademicCapIcon, BriefcaseIcon, ClockIcon, LightBulbIcon, MapPinIcon, StarIcon, UserIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    mentor: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['request-mentorship', 'schedule-intro']);

const formatSessionType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const requestMentorship = () => {
    if (!props.mentor.request_sent) {
        emit('request-mentorship', props.mentor.id);
    }
};

const scheduleIntro = () => {
    emit('schedule-intro', props.mentor.id);
};

const viewStories = () => {
    router.visit(route('stories.index', { author: props.mentor.id }));
};
</script>

<style scoped>
.student-mentor-card {
    transition:
        transform 0.2s ease-in-out,
        box-shadow 0.2s ease-in-out;
}

.student-mentor-card:hover {
    transform: translateY(-2px);
}
</style>

