<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="System Health" />
        
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">System Health</h1>
                        <p class="mt-1 text-sm text-gray-600">Monitor system performance and health metrics</p>
                    </div>
                    <div class="flex space-x-3">
                        <button
                            @click="refreshHealth"
                            :disabled="isRefreshing"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                        >
                            <ArrowPathIcon class="-ml-1 mr-2 h-5 w-5" :class="{ 'animate-spin': isRefreshing }" />
                            {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
                        </button>
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
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- System Status Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CircleStackIcon class="h-8 w-8" :class="getStatusColor(health.database?.status)" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Database</h3>
                            <p class="text-sm" :class="getStatusColor(health.database?.status)">
                                {{ health.database?.status || 'Unknown' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Response: {{ health.database?.response_time || 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <BoltIcon class="h-8 w-8" :class="getStatusColor(health.cache?.status)" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Cache</h3>
                            <p class="text-sm" :class="getStatusColor(health.cache?.status)">
                                {{ health.cache?.status || 'Unknown' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Hit Rate: {{ health.cache?.hit_rate || 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <QueueListIcon class="h-8 w-8" :class="getStatusColor(health.queue?.status)" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Queue</h3>
                            <p class="text-sm" :class="getStatusColor(health.queue?.status)">
                                {{ health.queue?.status || 'Unknown' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Jobs: {{ health.queue?.pending_jobs || 0 }} pending
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ServerIcon class="h-8 w-8" :class="getStatusColor(health.storage?.status)" />
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Storage</h3>
                            <p class="text-sm" :class="getStatusColor(health.storage?.status)">
                                {{ health.storage?.status || 'Unknown' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Usage: {{ health.storage?.usage || 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Performance Metrics -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Performance Metrics</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Response Time -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Average Response Time</span>
                                    <span class="text-sm text-gray-900">{{ health.performance?.response_time || 'N/A' }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="h-2 rounded-full transition-all duration-300"
                                        :class="getPerformanceBarColor(health.performance?.response_time_score || 0)"
                                        :style="{ width: `${health.performance?.response_time_score || 0}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Memory Usage -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Memory Usage</span>
                                    <span class="text-sm text-gray-900">{{ health.performance?.memory_usage || 'N/A' }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="h-2 rounded-full transition-all duration-300"
                                        :class="getUsageBarColor(health.performance?.memory_usage_percent || 0)"
                                        :style="{ width: `${health.performance?.memory_usage_percent || 0}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- CPU Usage -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">CPU Usage</span>
                                    <span class="text-sm text-gray-900">{{ health.performance?.cpu_usage || 'N/A' }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="h-2 rounded-full transition-all duration-300"
                                        :class="getUsageBarColor(health.performance?.cpu_usage_percent || 0)"
                                        :style="{ width: `${health.performance?.cpu_usage_percent || 0}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Uptime -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">System Uptime</span>
                                <span class="text-sm text-gray-900">{{ health.performance?.uptime || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Status -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Security Status</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <ShieldCheckIcon class="h-5 w-5 mr-2" :class="getStatusColor(health.security?.ssl_status)" />
                                    <span class="text-sm text-gray-700">SSL Certificate</span>
                                </div>
                                <span class="text-sm" :class="getStatusColor(health.security?.ssl_status)">
                                    {{ health.security?.ssl_status || 'Unknown' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <LockClosedIcon class="h-5 w-5 mr-2" :class="getStatusColor(health.security?.firewall_status)" />
                                    <span class="text-sm text-gray-700">Firewall</span>
                                </div>
                                <span class="text-sm" :class="getStatusColor(health.security?.firewall_status)">
                                    {{ health.security?.firewall_status || 'Unknown' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <ExclamationTriangleIcon class="h-5 w-5 mr-2 text-yellow-500" />
                                    <span class="text-sm text-gray-700">Security Alerts</span>
                                </div>
                                <span class="text-sm text-gray-900">
                                    {{ health.security?.alerts_count || 0 }} active
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <ClockIcon class="h-5 w-5 mr-2 text-gray-400" />
                                    <span class="text-sm text-gray-700">Last Security Scan</span>
                                </div>
                                <span class="text-sm text-gray-900">
                                    {{ formatDate(health.security?.last_scan) || 'Never' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Backup Status -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Backup Status</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <CloudArrowUpIcon class="h-5 w-5 mr-2" :class="getStatusColor(health.backups?.status)" />
                                    <span class="text-sm text-gray-700">Backup System</span>
                                </div>
                                <span class="text-sm" :class="getStatusColor(health.backups?.status)">
                                    {{ health.backups?.status || 'Unknown' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Last Backup</span>
                                <span class="text-sm text-gray-900">
                                    {{ formatDate(health.backups?.last_backup) || 'Never' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Backup Size</span>
                                <span class="text-sm text-gray-900">
                                    {{ health.backups?.backup_size || 'N/A' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Next Scheduled</span>
                                <span class="text-sm text-gray-900">
                                    {{ formatDate(health.backups?.next_backup) || 'Not scheduled' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Retention Period</span>
                                <span class="text-sm text-gray-900">
                                    {{ health.backups?.retention_days || 'N/A' }} days
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Alerts -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">System Alerts</h2>
                    </div>
                    <div class="p-6">
                        <div v-if="health.alerts?.length" class="space-y-3">
                            <div v-for="alert in health.alerts" :key="alert.id" class="flex items-start space-x-3 p-3 rounded-lg" :class="getAlertBgClass(alert.severity)">
                                <component :is="getAlertIcon(alert.severity)" class="h-5 w-5 mt-0.5" :class="getAlertIconClass(alert.severity)" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium" :class="getAlertTextClass(alert.severity)">
                                        {{ alert.title }}
                                    </p>
                                    <p class="text-sm" :class="getAlertDescriptionClass(alert.severity)">
                                        {{ alert.description }}
                                    </p>
                                    <p class="text-xs mt-1" :class="getAlertDescriptionClass(alert.severity)">
                                        {{ formatDate(alert.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <CheckCircleIcon class="mx-auto h-12 w-12 text-green-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Active Alerts</h3>
                            <p class="mt-1 text-sm text-gray-500">All systems are operating normally.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeftIcon,
    ArrowPathIcon,
    CircleStackIcon,
    BoltIcon,
    QueueListIcon,
    ServerIcon,
    ShieldCheckIcon,
    LockClosedIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    CloudArrowUpIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';

const props = defineProps({
    health: Object,
});

const isRefreshing = ref(false);

const getStatusColor = (status) => {
    const colors = {
        'healthy': 'text-green-600',
        'warning': 'text-yellow-600',
        'critical': 'text-red-600',
        'unknown': 'text-gray-600',
    };
    return colors[status?.toLowerCase()] || 'text-gray-600';
};

const getPerformanceBarColor = (score) => {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getUsageBarColor = (usage) => {
    if (usage >= 90) return 'bg-red-500';
    if (usage >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
};

const getAlertBgClass = (severity) => {
    const classes = {
        'critical': 'bg-red-50',
        'warning': 'bg-yellow-50',
        'info': 'bg-blue-50',
    };
    return classes[severity] || 'bg-gray-50';
};

const getAlertIconClass = (severity) => {
    const classes = {
        'critical': 'text-red-400',
        'warning': 'text-yellow-400',
        'info': 'text-blue-400',
    };
    return classes[severity] || 'text-gray-400';
};

const getAlertTextClass = (severity) => {
    const classes = {
        'critical': 'text-red-800',
        'warning': 'text-yellow-800',
        'info': 'text-blue-800',
    };
    return classes[severity] || 'text-gray-800';
};

const getAlertDescriptionClass = (severity) => {
    const classes = {
        'critical': 'text-red-700',
        'warning': 'text-yellow-700',
        'info': 'text-blue-700',
    };
    return classes[severity] || 'text-gray-700';
};

const getAlertIcon = (severity) => {
    const icons = {
        'critical': XCircleIcon,
        'warning': ExclamationTriangleIcon,
        'info': CheckCircleIcon,
    };
    return icons[severity] || CheckCircleIcon;
};

const formatDate = (dateString) => {
    if (!dateString) return null;
    return format(new Date(dateString), 'MMM dd, yyyy HH:mm');
};

const refreshHealth = () => {
    isRefreshing.value = true;
    router.reload({
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};
</script>