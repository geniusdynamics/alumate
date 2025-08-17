<template>
    <AdminLayout
        app-name="Alumate"
        user-role="Super Admin"
        page-title="Dashboard"
        :navigation-items="navigationItems"
    >
        <Head title="Super Admin Dashboard" />

        <!-- Welcome Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">Welcome Admin</h2>
            <p class="text-gray-400">Here's what's happening with your platform today.</p>
        </div>
        <!-- System Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <DarkStatCard
                title="Total Users"
                :value="systemStats.total_users"
                icon="UsersIcon"
                color="blue"
                change="+12%"
            />
            <DarkStatCard
                title="Revenue"
                :value="'$45,678'"
                icon="CurrencyDollarIcon"
                color="green"
                change="+8.2%"
            />
            <DarkStatCard
                title="Activity"
                :value="2456"
                icon="ChartBarIcon"
                color="purple"
                change="+15%"
            />
            <DarkStatCard
                title="Views"
                :value="34567"
                icon="EyeIcon"
                color="yellow"
                change="-2.4%"
            />
        </div>

        <!-- Quick Actions -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-lg font-medium text-white">Quick Actions</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link
                        :href="route('super-admin.institutions')"
                        class="flex items-center p-4 border border-gray-600 rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        <BuildingOfficeIcon class="h-8 w-8 text-blue-400 mr-3" />
                        <div>
                            <div class="font-medium text-white">Manage Institutions</div>
                            <div class="text-sm text-gray-400">{{ systemStats.total_institutions }} institutions</div>
                        </div>
                    </Link>

                    <Link
                        :href="route('super-admin.users')"
                        class="flex items-center p-4 border border-gray-600 rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        <UsersIcon class="h-8 w-8 text-green-400 mr-3" />
                        <div>
                            <div class="font-medium text-white">User Management</div>
                            <div class="text-sm text-gray-400">{{ systemStats.total_users }} users</div>
                        </div>
                    </Link>

                    <Link
                        :href="route('super-admin.employer-verification')"
                        class="flex items-center p-4 border border-gray-600 rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        <ShieldCheckIcon class="h-8 w-8 text-orange-400 mr-3" />
                        <div>
                            <div class="font-medium text-white">Employer Verification</div>
                            <div class="text-sm text-gray-400">{{ systemStats.pending_verifications }} pending</div>
                        </div>
                    </Link>

                    <Link
                        :href="route('super-admin.reports')"
                        class="flex items-center p-4 border border-gray-600 rounded-lg hover:bg-gray-700 transition-colors"
                    >
                        <DocumentTextIcon class="h-8 w-8 text-purple-400 mr-3" />
                        <div>
                            <div class="font-medium text-white">System Reports</div>
                            <div class="text-sm text-gray-400">Generate reports</div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Institution Performance -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Institution Performance</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="institution in institutionStats.slice(0, 5)" :key="institution.id" class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">{{ institution.name }}</div>
                                    <div class="text-sm text-gray-500">{{ institution.graduate_count }} graduates</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium" :class="getEmploymentRateColor(institution.employment_rate)">
                                        {{ institution.employment_rate }}% employed
                                    </div>
                                    <div class="text-xs text-gray-500">{{ institution.status }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6">
                            <Link
                                :href="route('super-admin.institutions')"
                                class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
                            >
                                View all institutions →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">System Health</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Database</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ systemHealth.database_status }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Cache</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ systemHealth.cache_status }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Queue</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ systemHealth.queue_status }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Storage Usage</span>
                                <span class="text-sm font-medium text-gray-900">{{ systemHealth.storage_usage }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Response Time</span>
                                <span class="text-sm font-medium text-gray-900">{{ systemHealth.response_time }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Uptime</span>
                                <span class="text-sm font-medium text-gray-900">{{ systemHealth.uptime }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Recent Activity</h2>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                <li v-for="(activity, index) in recentActivity.slice(0, 8)" :key="index" class="relative pb-8">
                                    <div v-if="index !== recentActivity.slice(0, 8).length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></div>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white" :class="getActivityIconClass(activity.type)">
                                                <component :is="getActivityIcon(activity.type)" class="h-5 w-5 text-white" />
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-500">{{ activity.description }}</p>
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                {{ formatTimeAgo(activity.timestamp) }}
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Job Market Overview -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Job Market Overview</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ jobStats.total_jobs }}</div>
                                <div class="text-sm text-gray-500">Total Jobs</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ jobStats.active_jobs }}</div>
                                <div class="text-sm text-gray-500">Active Jobs</div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Filled Jobs</span>
                                <span class="text-sm font-medium text-gray-900">{{ jobStats.filled_jobs }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Pending Approval</span>
                                <span class="text-sm font-medium text-orange-600">{{ jobStats.pending_approval }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Avg Applications/Job</span>
                                <span class="text-sm font-medium text-gray-900">{{ Math.round(jobStats.avg_applications_per_job) }}</span>
                            </div>
                        </div>

                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Top Job Types</h4>
                            <div class="space-y-2">
                                <div v-for="jobType in jobStats.top_job_types" :key="jobType.job_type" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">{{ jobType.job_type || 'Other' }}</span>
                                    <span class="text-sm font-medium text-gray-900">{{ jobType.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    BuildingOfficeIcon,
    UsersIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    ChartBarIcon,
    DocumentTextIcon,
    ShieldCheckIcon,
    UserIcon,
    PlusIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/AdminLayout.vue'
import DarkStatCard from '@/components/DarkStatCard.vue'
import { formatDistanceToNow } from 'date-fns'

const props = defineProps({
    systemStats: Object,
    institutionStats: Array,
    employerStats: Object,
    jobStats: Object,
    recentActivity: Array,
    systemHealth: Object,
})

const navigationItems = computed(() => [
    {
        name: 'Dashboard',
        href: route('super-admin.dashboard'),
        icon: 'HomeIcon',
        active: route().current('super-admin.dashboard')
    },
    {
        name: 'Analytics',
        href: route('super-admin.analytics'),
        icon: 'ChartBarIcon',
        active: route().current('super-admin.analytics')
    },
    {
        name: 'Users',
        href: route('super-admin.users'),
        icon: 'UsersIcon',
        active: route().current('super-admin.users')
    },
    {
        name: 'Content',
        href: route('super-admin.content'),
        icon: 'DocumentTextIcon',
        active: route().current('super-admin.content')
    },
    {
        name: 'Activity',
        href: route('super-admin.activity'),
        icon: 'ChartPieIcon',
        active: route().current('super-admin.activity')
    },
    {
        name: 'Database',
        href: route('super-admin.database'),
        icon: 'CircleStackIcon',
        active: route().current('super-admin.database')
    },
    {
        name: 'Security',
        href: route('security.dashboard'),
        icon: 'ShieldCheckIcon',
        active: route().current('security.dashboard')
    },
    {
        name: 'Performance',
        href: route('super-admin.performance'),
        icon: 'ChartBarIcon',
        active: route().current('super-admin.performance')
    },
    {
        name: 'Notifications',
        href: route('super-admin.notifications'),
        icon: 'BellIcon',
        active: route().current('super-admin.notifications')
    },
    {
        name: 'Settings',
        href: route('super-admin.settings'),
        icon: 'CogIcon',
        active: route().current('super-admin.settings')
    }
])

const getEmploymentRateColor = (rate) => {
    if (rate >= 80) return 'text-green-600';
    if (rate >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getActivityIconClass = (type) => {
    const classes = {
        'user_registration': 'bg-blue-500',
        'job_posted': 'bg-green-500',
        'job_application': 'bg-purple-500',
        'employer_verified': 'bg-yellow-500',
    };
    return classes[type] || 'bg-gray-500';
};

const getActivityIcon = (type) => {
    const icons = {
        'user_registration': UserIcon,
        'job_posted': BriefcaseIcon,
        'job_application': PlusIcon,
        'employer_verified': CheckCircleIcon,
    };
    return icons[type] || UserIcon;
};

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true });
};
</script>