<template>
    <div class="student-mentor-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Mentor Header -->
        <div class="flex items-start space-x-4 mb-4">
            <div class="w-16 h-16 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                <UserIcon class="w-8 h-8 text-gray-600 dark:text-gray-300" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ mentor.name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ mentor.current_position }}</p>
                <p class="text-sm text-blue-600 dark:text-blue-400">{{ mentor.current_company }}</p>
            </div>
            <div class="flex items-center space-x-1">
                <StarIcon class="w-4 h-4 text-yellow-400 fill-current" />
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ mentor.rating || 4.8 }}</span>
            </div>
        </div>

        <!-- Mentor Details -->
        <div class="space-y-3 mb-4">
            <!-- Education -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <AcademicCapIcon class="w-4 h-4" />
                <span>{{ mentor.degree }} • Class of {{ mentor.graduation_year }}</span>
            </div>

            <!-- Experience -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <BriefcaseIcon class="w-4 h-4" />
                <span>{{ mentor.years_experience }} years experience</span>
            </div>

            <!-- Location -->
            <div v-if="mentor.location" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                <MapPinIcon class="w-4 h-4" />
                <span>{{ mentor.location }}</span>
            </div>

            <!-- Mentorship Stats -->
            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center space-x-1">
                    <UsersIcon class="w-4 h-4" />
                    <span>{{ mentor.mentees_count || 0 }} mentees</span>
                </div>
                <div class="flex items-center space-x-1">
                    <ClockIcon class="w-4 h-4" />
                    <span>{{ mentor.sessions_count || 0 }} sessions</span>
                </div>
            </div>
        </div>

        <!-- Expertise Areas -->
        <div class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Expertise</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="expertise in mentor.expertise_areas.slice(0, 3)"
                    :key="expertise"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ expertise }}
                </span>
                <span
                    v-if="mentor.expertise_areas.length > 3"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ mentor.expertise_areas.length - 3 }} more
                </span>
            </div>
        </div>

        <!-- Mentorship Focus -->
        <div v-if="mentor.mentorship_focus" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Mentorship Focus</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ mentor.mentorship_focus }}</p>
        </div>

        <!-- Why This Mentor -->
        <div v-if="mentor.match_reason" class="mb-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md p-3">
                <div class="flex items-start space-x-2">
                    <LightBulbIcon class="w-4 h-4 text-green-600 mt-0.5" />
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
                    <div 
                        :class="mentor.is_available ? 'bg-green-500' : 'bg-red-500'"
                        class="w-2 h-2 rounded-full"
                    ></div>
                    <span class="text-sm font-medium" :class="mentor.is_available ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        {{ mentor.is_available ? 'Available' : 'Busy' }}
                    </span>
                </div>
            </div>
            <p v-if="mentor.availability_note" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
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
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Session Types</h4>
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="type in mentor.session_types"
                    :key="type"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
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
                        'flex-1 px-4 py-2 rounded-md text-sm font-medium transition-colors',
                        mentor.request_sent
                            ? 'bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-400'
                            : 'bg-blue-600 text-white hover:bg-blue-700'
                    ]"
                >
                    {{ mentor.request_sent ? 'Request Sent' : 'Request Mentorship' }}
                </button>
                
                <button
                    @click="scheduleIntro"
                    class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-400 rounded-md text-sm font-medium transition-colors dark:text-blue-400 dark:hover:text-blue-200 dark:border-blue-600"
                >
                    Intro Call
                </button>
            </div>

            <!-- Quick Actions -->
            <div class="flex space-x-2 text-sm">
                <Link
                    :href="route('mentors.profile', mentor.id)"
                    class="flex-1 text-center px-3 py-1 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    View Profile
                </Link>
                
                <button
                    v-if="mentor.success_stories_count > 0"
                    @click="viewStories"
                    class="flex-1 text-center px-3 py-1 text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200 border border-purple-300 dark:border-purple-600 rounded-md hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors"
                >
                    {{ mentor.success_stories_count }} Stories
                </button>
            </div>
        </div>

        <!-- Student Testimonial -->
        <div v-if="mentor.latest_testimonial" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                <p class="text-sm text-gray-700 dark:text-gray-300 italic">
                    "{{ mentor.latest_testimonial.content }}"
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    - {{ mentor.latest_testimonial.student_name }}, {{ mentor.latest_testimonial.student_year }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import {
    UserIcon,
    StarIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    MapPinIcon,
    UsersIcon,
    ClockIcon,
    LightBulbIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    mentor: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['request-mentorship', 'schedule-intro'])

const formatSessionType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const requestMentorship = () => {
    if (!props.mentor.request_sent) {
        emit('request-mentorship', props.mentor.id)
    }
}

const scheduleIntro = () => {
    emit('schedule-intro', props.mentor.id)
}

const viewStories = () => {
    router.visit(route('stories.index', { author: props.mentor.id }))
}
</script>

<style scoped>
.student-mentor-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.student-mentor-card:hover {
    transform: translateY(-2px);
}
</style>
