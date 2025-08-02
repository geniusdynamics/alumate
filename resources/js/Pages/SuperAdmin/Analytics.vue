<template>
    <AdminLayout>
        <Head title="System Analytics" />

        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-white">System Analytics</h1>
                    <p class="mt-1 text-sm text-gray-300">Advanced analytics and insights across the platform</p>
                </div>
                <div class="flex space-x-3">
                    <Link
                        :href="route('super-admin.dashboard')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <ArrowLeftIcon class="-ml-1 mr-2 h-5 w-5" />
                        Back to Dashboard
                    </Link>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Analytics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <UsersIcon class="h-8 w-8 text-blue-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.total_users || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <BriefcaseIcon class="h-8 w-8 text-green-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Jobs</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.active_jobs || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <DocumentTextIcon class="h-8 w-8 text-purple-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Applications</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.total_applications || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ChartBarIcon class="h-8 w-8 text-yellow-600" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Employment Rate</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ analytics?.overview?.employment_rate || 0 }}%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- User Growth Chart -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">User Growth</h3>
                    </div>
                    <div class="p-6">
                        <div class="h-64 flex items-center justify-center text-gray-500">
                            Chart placeholder - User growth over time
                        </div>
                    </div>
                </div>

                <!-- Employment Trends -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Employment Trends</h3>
                    </div>
                    <div class="p-6">
                        <div class="h-64 flex items-center justify-center text-gray-500">
                            Chart placeholder - Employment trends
                        </div>
                    </div>
                </div>
            </div>

            <!-- Institution Performance -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Institution Performance</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Institution</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Graduates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Jobs</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="institution in analytics?.institutions || []" :key="institution.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ institution.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ institution.total_graduates || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ institution.employed_graduates || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span :class="getEmploymentRateColor(institution.employment_rate)">
                                            {{ Math.round(institution.employment_rate || 0) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ institution.active_jobs || 0 }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent System Activity</h3>
                </div>
                <div class="p-6">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <li v-for="(activity, index) in analytics?.recent_activities || []" :key="activity.id">
                                <div class="relative pb-8" :class="{ 'pb-0': index === (analytics?.recent_activities?.length - 1) }">
                                    <span v-if="index !== (analytics?.recent_activities?.length - 1)" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                <component :is="getActivityIcon(activity.type)" class="h-5 w-5 text-white" aria-hidden="true" />
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-500">{{ activity.description }}</p>
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
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

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Components/AdminLayout.vue';
import {
    ArrowLeftIcon,
    UsersIcon,
    BriefcaseIcon,
    DocumentTextIcon,
    ChartBarIcon,
    UserPlusIcon,
    BuildingOfficeIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline';
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
        'user': UserPlusIcon,
        'institution': BuildingOfficeIcon,
        'graduate': AcademicCapIcon,
        'job': BriefcaseIcon,
        'application': DocumentTextIcon,
    };
    return icons[type] || DocumentTextIcon;
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy HH:mm');
};
</script>
</template>