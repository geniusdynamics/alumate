<template>
    <AppLayout title="Career Timeline">
        <Head title="Career Timeline" />

        <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Career Timeline</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Track your professional journey and set career goals</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Timeline -->
                <div class="lg:col-span-3">
                    <!-- Add Career Entry Button -->
                    <div class="mb-6">
                        <button 
                            @click="showAddEntryModal = true"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            Add Career Entry
                        </button>
                    </div>

                    <!-- Career Timeline -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Your Career Journey</h2>
                        </div>
                        <div class="p-6">
                            <div v-if="careerEntries.length === 0" class="text-center py-12">
                                <BriefcaseIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Start Your Career Timeline</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Add your first career entry to begin tracking your professional journey</p>
                                <button 
                                    @click="showAddEntryModal = true"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Add First Entry
                                </button>
                            </div>

                            <!-- Timeline Entries -->
                            <div v-else class="space-y-8">
                                <div 
                                    v-for="(entry, index) in careerEntries" 
                                    :key="entry.id"
                                    class="relative"
                                >
                                    <!-- Timeline Line -->
                                    <div 
                                        v-if="index < careerEntries.length - 1"
                                        class="absolute left-4 top-12 w-0.5 h-full bg-gray-300 dark:bg-gray-600"
                                    ></div>

                                    <!-- Timeline Entry -->
                                    <div class="flex items-start space-x-4">
                                        <!-- Timeline Dot -->
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                            <BriefcaseIcon class="w-4 h-4 text-white" />
                                        </div>

                                        <!-- Entry Content -->
                                        <div class="flex-1 bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                                        {{ entry.position_title }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ entry.company_name }}
                                                    </p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                                        {{ formatDate(entry.start_date) }} - 
                                                        {{ entry.end_date ? formatDate(entry.end_date) : 'Present' }}
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button 
                                                        @click="editEntry(entry)"
                                                        class="text-gray-400 hover:text-gray-600"
                                                    >
                                                        <PencilIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>

                                            <div v-if="entry.description" class="mt-4">
                                                <p class="text-gray-700 dark:text-gray-300">{{ entry.description }}</p>
                                            </div>

                                            <!-- Milestones -->
                                            <div v-if="entry.milestones && entry.milestones.length > 0" class="mt-4">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Key Achievements</h4>
                                                <div class="space-y-2">
                                                    <div 
                                                        v-for="milestone in entry.milestones" 
                                                        :key="milestone.id"
                                                        class="flex items-center space-x-2"
                                                    >
                                                        <CheckCircleIcon class="w-4 h-4 text-green-500" />
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ milestone.title }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Career Milestones -->
                    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Career Milestones</h2>
                        </div>
                        <div class="p-6">
                            <div v-if="milestones.length === 0" class="text-center py-8">
                                <TrophyIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No milestones yet</h3>
                                <p class="text-gray-500 dark:text-gray-400">Celebrate your achievements by adding career milestones</p>
                            </div>
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div 
                                    v-for="milestone in milestones" 
                                    :key="milestone.id"
                                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <div class="flex items-start space-x-3">
                                        <TrophyIcon class="w-6 h-6 text-yellow-500 flex-shrink-0 mt-1" />
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ milestone.title }}
                                            </h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                {{ milestone.description }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                                Achieved {{ formatDate(milestone.achieved_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Career Goals -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Career Goals</h3>
                                <Link 
                                    :href="route('career.goals')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    Manage
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="goals.length === 0" class="text-center py-4">
                                <FlagIcon class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">No goals set yet</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="goal in goals" 
                                    :key="goal.id"
                                    class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ goal.title }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Target: {{ formatDate(goal.target_date) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Career Insights -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Career Insights</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="insights.length === 0" class="text-center py-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No insights available</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div 
                                    v-for="insight in insights" 
                                    :key="insight.type"
                                    class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                        {{ insight.title }}
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ insight.message }}
                                    </p>
                                    <button class="text-sm text-blue-600 hover:text-blue-500 font-medium">
                                        {{ insight.action }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <Link 
                                :href="route('jobs.dashboard')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <BriefcaseIcon class="w-5 h-5" />
                                <span>Job Dashboard</span>
                            </Link>
                            <Link 
                                :href="route('career.mentorship')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <UserGroupIcon class="w-5 h-5" />
                                <span>Find Mentors</span>
                            </Link>
                            <Link 
                                :href="route('career.goals')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <FlagIcon class="w-5 h-5" />
                                <span>Set Goals</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Entry Modal -->
        <CareerEntryModal 
            v-if="showAddEntryModal"
            @close="showAddEntryModal = false"
            @saved="handleEntrySaved"
        />

        <!-- User Flow Integration -->
        <UserFlowIntegration />
        
        <!-- Real-time Updates -->
        <RealTimeUpdates 
            :show-activity-feed="true"
        />
        
        <!-- Cross-feature Connections -->
        <CrossFeatureConnections 
            context="career"
            :context-data="{ careerEntries, goals, milestones }"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import CareerEntryModal from '@/components/CareerEntryModal.vue'
import UserFlowIntegration from '@/components/UserFlowIntegration.vue'
import RealTimeUpdates from '@/components/RealTimeUpdates.vue'
import CrossFeatureConnections from '@/components/CrossFeatureConnections.vue'
import { format } from 'date-fns'
import {
    BriefcaseIcon,
    CheckCircleIcon,
    TrophyIcon,
    FlagIcon,
    PencilIcon,
    UserGroupIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    careerEntries: Array,
    milestones: Array,
    goals: Array,
    insights: Array,
})

const showAddEntryModal = ref(false)

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return format(new Date(dateString), 'MMM yyyy')
}

const editEntry = (entry) => {
    // Handle editing entry
    console.log('Edit entry:', entry)
}

const handleEntrySaved = () => {
    showAddEntryModal.value = false
    // Refresh the page or update the data
    window.location.reload()
}
</script>
