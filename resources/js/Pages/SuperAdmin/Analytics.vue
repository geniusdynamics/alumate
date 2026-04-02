<template>
    <AdminLayout>
        <Head title="System Analytics" />

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">System Analytics</h1>
                    <p class="mt-1 text-sm text-gray-300">Advanced analytics and insights across the platform</p>
                </div>
                <div class="flex space-x-3">
                    <Link
                        :href="route('super-admin.dashboard')"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <ArrowLeftIcon class="-ml-1 mr-2 h-5 w-5" />
                        Back to Dashboard
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Analytics Overview -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <UsersIcon class="h-8 w-8 text-blue-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.total_users || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <BriefcaseIcon class="h-8 w-8 text-green-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Active Jobs</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.active_jobs || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <DocumentTextIcon class="h-8 w-8 text-purple-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Applications</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.total_applications || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ChartBarIcon class="h-8 w-8 text-yellow-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500">Employment Rate</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.employment_rate || 0 }}%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- User Growth Chart -->
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">User Growth</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex h-64 items-center justify-center text-gray-500">Chart placeholder - User growth over time</div>
                    </div>
                </div>

                <!-- Employment Trends -->
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Employment Trends</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex h-64 items-center justify-center text-gray-500">Chart placeholder - Employment trends</div>
                    </div>
                </div>
            </div>

            <!-- Market Trends -->
            <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Top In-Demand Skills</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-2">
                            <li
                                v-for="skill in analytics?.market_trends?.top_skills || []"
                                :key="skill.skill"
                                class="flex items-center justify-between text-sm"
                            >
                                <span>{{ skill.skill }}</span>
                                <span class="font-semibold">{{ skill.count }} mentions</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Top Hiring Industries</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-2">
                            <li
                                v-for="industry in analytics?.market_trends?.top_industries || []"
                                :key="industry.industry"
                                class="flex items-center justify-between text-sm"
                            >
                                <span>{{ industry.industry }}</span>
                                <span class="font-semibold">{{ industry.jobs_count }} jobs</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Platform Benchmarking -->
            <div class="mb-8 rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Platform Benchmarking</h3>
                    <p class="text-sm text-gray-500">Anonymized comparison of key metrics across all institutions.</p>
                </div>
                <div class="p-6">
                    <div class="flex h-80 items-center justify-center text-gray-500">
                        Chart placeholder - Employment Rate vs. Average Salary by Institution
                    </div>
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Institution (Anonymized)
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employment Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Average Salary (Year 1)
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="(benchmark, index) in analytics?.platform_benchmarks || []" :key="benchmark.institution_id">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">Institution {{ index + 1 }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <span :class="getEmploymentRateColor(benchmark.employment_rate)">
                                            {{ Math.round(benchmark.employment_rate || 0) }}%
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        ${{ (benchmark.average_salary || 0).toLocaleString() }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-lg bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">Recent System Activity</h3>
                </div>
                <div class="p-6">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <li v-for="(activity, index) in analytics?.recent_activities || []" :key="activity.id">
                                <div class="relative pb-8" :class="{ 'pb-0': index === analytics?.recent_activities?.length - 1 }">
                                    <span
                                        v-if="index !== analytics?.recent_activities?.length - 1"
                                        class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"
                                        aria-hidden="true"
                                    ></span>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 ring-8 ring-white">
                                                <component :is="getActivityIcon(activity.type)" class="h-5 w-5 text-white" aria-hidden="true" />
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-gray-500">{{ activity.description }}</p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                {{ formatDate(activity.created_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/Components/AdminLayout.vue';
import {
    AcademicCapIcon,
    ArrowLeftIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    DocumentTextIcon,
    UserPlusIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';

const props = defineProps({
    analytics: Object,
});

const getEmploymentRateColor = (rate) => {
    if (rate >= 80) return 'text-green-600';
    if (rate >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getActivityIcon = (type) => {
    const icons = {
        user: UserPlusIcon,
        institution: BuildingOfficeIcon,
        graduate: AcademicCapIcon,
        job: BriefcaseIcon,
        application: DocumentTextIcon,
    };
    return icons[type] || DocumentTextIcon;
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy HH:mm');
};
</script>












