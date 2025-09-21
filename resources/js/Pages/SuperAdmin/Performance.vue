<template>
    <AdminLayout app-name="Alumate" user-role="Super Admin" page-title="Performance Monitoring" :navigation-items="navigationItems">
        <Head title="Performance Monitoring" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="mb-2 text-2xl font-bold text-white">Performance Monitoring</h2>
            <p class="text-gray-400">Monitor system performance, response times, and server metrics.</p>
        </div>

        <!-- Performance Metrics -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <DarkStatCard title="Avg Response Time" :value="performanceStats.response_times.avg_response_time" icon="ClockIcon" color="blue" />
            <DarkStatCard title="CPU Usage" :value="performanceStats.server_metrics.cpu_usage" icon="CpuChipIcon" color="yellow" />
            <DarkStatCard title="Memory Usage" :value="performanceStats.server_metrics.memory_usage" icon="CircleStackIcon" color="red" />
            <DarkStatCard title="Cache Hit Rate" :value="performanceStats.cache_performance.hit_rate" icon="BoltIcon" color="green" />
        </div>

        <!-- Server Metrics and Cache Performance -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Server Metrics -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Server Metrics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">CPU Usage</span>
                            <span class="font-semibold text-yellow-400">{{ performanceStats.server_metrics.cpu_usage }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Memory Usage</span>
                            <span class="font-semibold text-red-400">{{ performanceStats.server_metrics.memory_usage }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Disk Usage</span>
                            <span class="font-semibold text-blue-400">{{ performanceStats.server_metrics.disk_usage }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cache Performance -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Cache Performance</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Hit Rate</span>
                            <span class="font-semibold text-green-400">{{ performanceStats.cache_performance.hit_rate }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Miss Rate</span>
                            <span class="font-semibold text-yellow-400">{{ performanceStats.cache_performance.miss_rate }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Eviction Rate</span>
                            <span class="font-semibold text-red-400">{{ performanceStats.cache_performance.eviction_rate }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Times and Queue Status -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Response Times -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Response Times</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Average</span>
                            <span class="font-semibold text-blue-400">{{ performanceStats.response_times.avg_response_time }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">95th Percentile</span>
                            <span class="font-semibold text-yellow-400">{{ performanceStats.response_times.p95_response_time }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">99th Percentile</span>
                            <span class="font-semibold text-red-400">{{ performanceStats.response_times.p99_response_time }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Status -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Queue Status</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Pending Jobs</span>
                            <span class="font-semibold text-yellow-400">{{ performanceStats.queue_status.pending_jobs }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Failed Jobs</span>
                            <span class="font-semibold text-red-400">{{ performanceStats.queue_status.failed_jobs }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Processed Today</span>
                            <span class="font-semibold text-green-400">{{ performanceStats.queue_status.processed_today }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Rates -->
        <div class="rounded-lg border border-gray-700 bg-gray-800">
            <div class="border-b border-gray-700 px-6 py-4">
                <h3 class="text-lg font-medium text-white">Error Rates</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-400">{{ performanceStats.error_rates['4xx_errors'] }}</div>
                        <div class="text-sm text-gray-400">4xx Errors</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-400">{{ performanceStats.error_rates['5xx_errors'] }}</div>
                        <div class="text-sm text-gray-400">5xx Errors</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400">{{ performanceStats.error_rates.error_rate }}</div>
                        <div class="text-sm text-gray-400">Overall Error Rate</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/AdminLayout.vue';
import DarkStatCard from '@/components/DarkStatCard.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    performanceStats: Object,
});

const navigationItems = computed(() => [
    {
        name: 'Dashboard',
        href: route('super-admin.dashboard'),
        icon: 'HomeIcon',
        active: route().current('super-admin.dashboard'),
    },
    {
        name: 'Analytics',
        href: route('super-admin.analytics'),
        icon: 'ChartBarIcon',
        active: route().current('super-admin.analytics'),
    },
    {
        name: 'Users',
        href: route('super-admin.users'),
        icon: 'UsersIcon',
        active: route().current('super-admin.users'),
    },
    {
        name: 'Content',
        href: route('super-admin.content'),
        icon: 'DocumentTextIcon',
        active: route().current('super-admin.content'),
    },
    {
        name: 'Activity',
        href: route('super-admin.activity'),
        icon: 'ChartPieIcon',
        active: route().current('super-admin.activity'),
    },
    {
        name: 'Database',
        href: route('super-admin.database'),
        icon: 'CircleStackIcon',
        active: route().current('super-admin.database'),
    },
    {
        name: 'Security',
        href: route('security.dashboard'),
        icon: 'ShieldCheckIcon',
        active: route().current('security.dashboard'),
    },
    {
        name: 'Performance',
        href: route('super-admin.performance'),
        icon: 'ChartBarIcon',
        active: route().current('super-admin.performance'),
    },
    {
        name: 'Notifications',
        href: route('super-admin.notifications'),
        icon: 'BellIcon',
        active: route().current('super-admin.notifications'),
    },
    {
        name: 'Settings',
        href: route('super-admin.settings'),
        icon: 'CogIcon',
        active: route().current('super-admin.settings'),
    },
]);
</script>
