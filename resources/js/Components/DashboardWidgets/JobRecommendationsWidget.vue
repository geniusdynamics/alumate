<template>
    <div class="card-mobile border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">Recommended Jobs</h3>
            <BriefcaseIcon class="h-6 w-6 text-purple-600 dark:text-purple-400" />
        </div>

        <div class="space-y-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="space-y-2">
                        <div class="h-4 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="h-3 w-1/2 rounded bg-gray-200 dark:bg-gray-700"></div>
                        <div class="flex space-x-2">
                            <div class="h-6 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-6 w-20 rounded bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Recommendations -->
            <div v-else-if="jobs.length > 0" class="space-y-4">
                <div
                    v-for="job in jobs"
                    :key="job.id"
                    class="cursor-pointer rounded-lg border border-gray-200 p-4 transition-colors hover:border-purple-300 dark:border-gray-600 dark:hover:border-purple-500"
                    @click="viewJob(job)"
                >
                    <div class="mb-2 flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="line-clamp-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ job.title }}
                            </h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {{ job.company_name }}
                            </p>
                        </div>
                        <div class="ml-2 flex-shrink-0">
                            <span
                                class="inline-flex items-center rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800 dark:bg-purple-900/50 dark:text-purple-200"
                            >
                                {{ job.match_score }}% match
                            </span>
                        </div>
                    </div>

                    <div class="mb-3 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center space-x-1">
                            <MapPinIcon class="h-3 w-3" />
                            <span>{{ job.location }}</span>
                        </span>
                        <span class="flex items-center space-x-1">
                            <ClockIcon class="h-3 w-3" />
                            <span>{{ job.employment_type }}</span>
                        </span>
                        <span v-if="job.salary_range" class="flex items-center space-x-1">
                            <CurrencyDollarIcon class="h-3 w-3" />
                            <span>{{ job.salary_range }}</span>
                        </span>
                    </div>

                    <!-- Connection Insights -->
                    <div v-if="job.connection_insights" class="mb-3">
                        <div class="flex items-center space-x-2 text-xs text-blue-600 dark:text-blue-400">
                            <UsersIcon class="h-3 w-3" />
                            <span>{{ job.connection_insights }}</span>
                        </div>
                    </div>

                    <!-- Skills Match -->
                    <div v-if="job.matching_skills && job.matching_skills.length > 0" class="mb-3 flex flex-wrap gap-1">
                        <span
                            v-for="skill in job.matching_skills.slice(0, 3)"
                            :key="skill"
                            class="inline-flex items-center rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/50 dark:text-green-200"
                        >
                            {{ skill }}
                        </span>
                        <span
                            v-if="job.matching_skills.length > 3"
                            class="inline-flex items-center rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                        >
                            +{{ job.matching_skills.length - 3 }} more
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400"> Posted {{ formatTimeAgo(job.created_at) }} </span>
                        <button
                            @click.stop="saveJob(job)"
                            :class="[
                                'rounded px-3 py-1 text-xs font-medium transition-colors',
                                job.is_saved
                                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
                            ]"
                        >
                            {{ job.is_saved ? 'Saved' : 'Save' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-6 text-center">
                <BriefcaseIcon class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No job recommendations</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Complete your profile to get personalized job recommendations</p>
            </div>
        </div>

        <!-- View All Link -->
        <div v-if="jobs.length > 0" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <Link
                :href="route('jobs.dashboard')"
                class="flex items-center justify-center space-x-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
                <span>View All Jobs</span>
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ArrowRightIcon, BriefcaseIcon, ClockIcon, CurrencyDollarIcon, MapPinIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const loading = ref(true);
const jobs = ref([]);

const props = defineProps({
    limit: {
        type: Number,
        default: 3,
    },
});

onMounted(async () => {
    await fetchJobRecommendations();
});

const fetchJobRecommendations = async () => {
    try {
        loading.value = true;
        const response = await fetch(`/api/dashboard/job-recommendations?limit=${props.limit}`);
        const data = await response.json();
        jobs.value = data.jobs || [];
    } catch (error) {
        console.error('Failed to fetch job recommendations:', error);
        jobs.value = [];
    } finally {
        loading.value = false;
    }
};

const formatTimeAgo = (timestamp) => {
    const now = new Date();
    const time = new Date(timestamp);
    const diffInSeconds = Math.floor((now - time) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
};

const viewJob = (job) => {
    window.location.href = `/jobs/${job.id}`;
};

const saveJob = async (job) => {
    try {
        const response = await fetch(`/api/jobs/${job.id}/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        if (response.ok) {
            job.is_saved = !job.is_saved;
        }
    } catch (error) {
        console.error('Failed to save job:', error);
    }
};
</script>

