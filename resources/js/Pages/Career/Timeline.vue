<template>
    <AppLayout title="Career Timeline">
        <Head title="Career Timeline" />

        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Career Timeline</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Track your professional journey and set career goals</p>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- Main Timeline -->
                <div class="lg:col-span-3">
                    <!-- Add Career Entry Button -->
                    <div class="mb-6">
                        <button
                            @click="showAddEntryModal = true"
                            class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            Add Career Entry
                        </button>
                    </div>

                    <!-- Career Timeline -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Your Career Journey</h2>
                        </div>
                        <div class="p-6">
                            <div v-if="careerEntries.length === 0" class="py-12 text-center">
                                <BriefcaseIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">Start Your Career Timeline</h3>
                                <p class="mb-4 text-gray-500 dark:text-gray-400">
                                    Add your first career entry to begin tracking your professional journey
                                </p>
                                <button
                                    @click="showAddEntryModal = true"
                                    class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                                >
                                    Add First Entry
                                </button>
                            </div>

                            <!-- Timeline Entries -->
                            <div v-else class="space-y-8">
                                <div v-for="(entry, index) in careerEntries" :key="entry.id" class="relative">
                                    <!-- Timeline Line -->
                                    <div
                                        v-if="index < careerEntries.length - 1"
                                        class="absolute left-4 top-12 h-full w-0.5 bg-gray-300 dark:bg-gray-600"
                                    ></div>

                                    <!-- Timeline Entry -->
                                    <div class="flex items-start space-x-4">
                                        <!-- Timeline Dot -->
                                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600">
                                            <BriefcaseIcon class="h-4 w-4 text-white" />
                                        </div>

                                        <!-- Entry Content -->
                                        <div class="flex-1 rounded-lg bg-gray-50 p-6 dark:bg-gray-700">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                                        {{ entry.position_title }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                        {{ entry.company_name }}
                                                    </p>
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">
                                                        {{ formatDate(entry.start_date) }} -
                                                        {{ entry.end_date ? formatDate(entry.end_date) : 'Present' }}
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button @click="editEntry(entry)" class="text-gray-400 hover:text-gray-600">
                                                        <PencilIcon class="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>

                                            <div v-if="entry.description" class="mt-4">
                                                <p class="text-gray-700 dark:text-gray-300">{{ entry.description }}</p>
                                            </div>

                                            <!-- Milestones -->
                                            <div v-if="entry.milestones && entry.milestones.length > 0" class="mt-4">
                                                <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Key Achievements</h4>
                                                <div class="space-y-2">
                                                    <div
                                                        v-for="milestone in entry.milestones"
                                                        :key="milestone.id"
                                                        class="flex items-center space-x-2"
                                                    >
                                                        <CheckCircleIcon class="h-4 w-4 text-green-500" />
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
                    <div class="mt-8 rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Career Milestones</h2>
                        </div>
                        <div class="p-6">
                            <div v-if="milestones.length === 0" class="py-8 text-center">
                                <TrophyIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No milestones yet</h3>
                                <p class="text-gray-500 dark:text-gray-400">Celebrate your achievements by adding career milestones</p>
                            </div>
                            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div
                                    v-for="milestone in milestones"
                                    :key="milestone.id"
                                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div class="flex items-start space-x-3">
                                        <TrophyIcon class="mt-1 h-6 w-6 flex-shrink-0 text-yellow-500" />
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ milestone.title }}
                                            </h3>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ milestone.description }}
                                            </p>
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-500">
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
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Career Goals</h3>
                                <Link :href="route('career.goals')" class="text-sm text-blue-600 hover:text-blue-500"> Manage </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div v-if="goals.length === 0" class="py-4 text-center">
                                <FlagIcon class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">No goals set yet</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="goal in goals" :key="goal.id" class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ goal.title }}
                                    </h4>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Target: {{ formatDate(goal.target_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Career Insights -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Career Insights</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="insights.length === 0" class="py-4 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No insights available</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="insight in insights"
                                    :key="insight.type"
                                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ insight.title }}
                                    </h4>
                                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ insight.message }}
                                    </p>
                                    <button class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                        {{ insight.action }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="space-y-3 p-6">
                            <Link
                                :href="route('jobs.dashboard')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <BriefcaseIcon class="h-5 w-5" />
                                <span>Job Dashboard</span>
                            </Link>
                            <Link
                                :href="route('career.mentorship')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <UserGroupIcon class="h-5 w-5" />
                                <span>Find Mentors</span>
                            </Link>
                            <Link
                                :href="route('career.goals')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <FlagIcon class="h-5 w-5" />
                                <span>Set Goals</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Entry Modal -->
        <CareerEntryModal v-if="showAddEntryModal" @close="showAddEntryModal = false" @saved="handleEntrySaved" />

        <!-- User Flow Integration -->
        <UserFlowIntegration />

        <!-- Real-time Updates -->
        <RealTimeUpdates :show-activity-feed="true" />

        <!-- Cross-feature Connections -->
        <CrossFeatureConnections context="career" :context-data="{ careerEntries, goals, milestones }" />
    </AppLayout>
</template>

<script setup>
import CareerEntryModal from '@/components/CareerEntryModal.vue';
import CrossFeatureConnections from '@/components/CrossFeatureConnections.vue';
import RealTimeUpdates from '@/components/RealTimeUpdates.vue';
import UserFlowIntegration from '@/components/UserFlowIntegration.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BriefcaseIcon, CheckCircleIcon, FlagIcon, PencilIcon, TrophyIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { ref } from 'vue';

const props = defineProps({
    careerEntries: Array,
    milestones: Array,
    goals: Array,
    insights: Array,
});

const showAddEntryModal = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return format(new Date(dateString), 'MMM yyyy');
};

const editEntry = (entry) => {
    // Handle editing entry
    console.log('Edit entry:', entry);
};

const handleEntrySaved = () => {
    showAddEntryModal.value = false;
    // Refresh the page or update the data
    window.location.reload();
};
</script>
