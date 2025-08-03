<template>
    <div class="alumni-connection-card bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow duration-200">
        <!-- Alumni Avatar and Basic Info -->
        <div class="text-center mb-3">
            <div class="w-16 h-16 bg-gray-300 dark:bg-gray-600 rounded-full mx-auto mb-2 flex items-center justify-center">
                <UserIcon class="w-8 h-8 text-gray-600 dark:text-gray-300" />
            </div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ alumni.name }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ alumni.current_position }}</p>
        </div>

        <!-- Alumni Details -->
        <div class="space-y-2 mb-4">
            <!-- Graduation Info -->
            <div class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400">
                <AcademicCapIcon class="w-3 h-3" />
                <span>Class of {{ alumni.graduation_year }}</span>
            </div>

            <!-- Current Company -->
            <div v-if="alumni.current_company" class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400">
                <BuildingOfficeIcon class="w-3 h-3" />
                <span>{{ alumni.current_company }}</span>
            </div>

            <!-- Location -->
            <div v-if="alumni.location" class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400">
                <MapPinIcon class="w-3 h-3" />
                <span>{{ alumni.location }}</span>
            </div>

            <!-- Industry -->
            <div v-if="alumni.industry" class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400">
                <BriefcaseIcon class="w-3 h-3" />
                <span>{{ alumni.industry }}</span>
            </div>
        </div>

        <!-- Connection Reason -->
        <div v-if="alumni.connection_reason" class="mb-3">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-2">
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    <LightBulbIcon class="w-3 h-3 inline mr-1" />
                    {{ alumni.connection_reason }}
                </p>
            </div>
        </div>

        <!-- Skills/Expertise -->
        <div v-if="alumni.expertise && alumni.expertise.length > 0" class="mb-3">
            <div class="flex flex-wrap gap-1">
                <span
                    v-for="skill in alumni.expertise.slice(0, 3)"
                    :key="skill"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="alumni.expertise.length > 3"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-50 text-gray-500 dark:bg-gray-600 dark:text-gray-400"
                >
                    +{{ alumni.expertise.length - 3 }}
                </span>
            </div>
        </div>

        <!-- Mutual Connections -->
        <div v-if="alumni.mutual_connections_count > 0" class="mb-3">
            <div class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400">
                <UsersIcon class="w-3 h-3" />
                <span>{{ alumni.mutual_connections_count }} mutual connection{{ alumni.mutual_connections_count !== 1 ? 's' : '' }}</span>
            </div>
        </div>

        <!-- Response Rate -->
        <div v-if="alumni.response_rate" class="mb-3">
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600 dark:text-gray-400">Response Rate:</span>
                <div class="flex items-center space-x-1">
                    <div class="w-12 bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                        <div 
                            class="bg-green-500 h-1 rounded-full"
                            :style="{ width: alumni.response_rate + '%' }"
                        ></div>
                    </div>
                    <span class="text-green-600 dark:text-green-400 font-medium">{{ alumni.response_rate }}%</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2">
            <button
                @click="sendConnection"
                :disabled="alumni.connection_sent"
                :class="[
                    'w-full px-3 py-2 rounded-md text-sm font-medium transition-colors',
                    alumni.connection_sent
                        ? 'bg-gray-100 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-400'
                        : 'bg-blue-600 text-white hover:bg-blue-700'
                ]"
            >
                {{ alumni.connection_sent ? 'Request Sent' : 'Connect' }}
            </button>

            <div class="flex space-x-2">
                <Link
                    :href="route('alumni.profile', alumni.id)"
                    class="flex-1 text-center px-3 py-1 text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    View Profile
                </Link>
                
                <button
                    v-if="alumni.stories_count > 0"
                    @click="viewStories"
                    class="flex-1 text-center px-3 py-1 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 border border-blue-300 dark:border-blue-600 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                >
                    {{ alumni.stories_count }} Stories
                </button>
            </div>
        </div>

        <!-- Availability Status -->
        <div v-if="alumni.mentorship_available" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-xs text-green-600 dark:text-green-400 font-medium">Available for mentorship</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import {
    UserIcon,
    AcademicCapIcon,
    BuildingOfficeIcon,
    MapPinIcon,
    BriefcaseIcon,
    LightBulbIcon,
    UsersIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    alumni: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['send-connection'])

const sendConnection = () => {
    if (!props.alumni.connection_sent) {
        emit('send-connection', props.alumni.id)
    }
}

const viewStories = () => {
    router.visit(route('stories.index', { author: props.alumni.id }))
}
</script>

<style scoped>
.alumni-connection-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.alumni-connection-card:hover {
    transform: translateY(-1px);
}
</style>
