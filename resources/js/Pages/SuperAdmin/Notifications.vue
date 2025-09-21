<template>
    <AdminLayout app-name="Alumate" user-role="Super Admin" page-title="Notification Management" :navigation-items="navigationItems">
        <Head title="Notification Management" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="mb-2 text-2xl font-bold text-white">Notification Management</h2>
            <p class="text-gray-400">Monitor notification delivery, manage templates, and view user preferences.</p>
        </div>

        <!-- Notification Stats -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <DarkStatCard title="Total Notifications" :value="notificationStats.total_notifications" icon="BellIcon" color="blue" />
            <DarkStatCard
                title="Email Delivery Rate"
                :value="notificationStats.delivery_rates.email_delivery_rate"
                icon="EnvelopeIcon"
                color="green"
            />
            <DarkStatCard
                title="Push Delivery Rate"
                :value="notificationStats.delivery_rates.push_delivery_rate"
                icon="DevicePhoneMobileIcon"
                color="purple"
            />
            <DarkStatCard
                title="SMS Delivery Rate"
                :value="notificationStats.delivery_rates.sms_delivery_rate"
                icon="ChatBubbleLeftRightIcon"
                color="yellow"
            />
        </div>

        <!-- Delivery Rates and Notification Types -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Delivery Rates -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Delivery Performance</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Email Delivery</span>
                            <span class="font-semibold text-green-400">{{ notificationStats.delivery_rates.email_delivery_rate }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Push Notifications</span>
                            <span class="font-semibold text-purple-400">{{ notificationStats.delivery_rates.push_delivery_rate }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">SMS Delivery</span>
                            <span class="font-semibold text-yellow-400">{{ notificationStats.delivery_rates.sms_delivery_rate }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Types -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Notification Types</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Job Alerts</span>
                            <span class="font-semibold text-blue-400">{{ notificationStats.notification_types.job_alerts }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Connection Requests</span>
                            <span class="font-semibold text-green-400">{{ notificationStats.notification_types.connection_requests }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Event Reminders</span>
                            <span class="font-semibold text-purple-400">{{ notificationStats.notification_types.event_reminders }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Preferences and Recent Notifications -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- User Preferences -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">User Preferences</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Email Enabled</span>
                            <span class="font-semibold text-green-400">{{ notificationStats.notification_preferences.email_enabled }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Push Enabled</span>
                            <span class="font-semibold text-purple-400">{{ notificationStats.notification_preferences.push_enabled }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">SMS Enabled</span>
                            <span class="font-semibold text-yellow-400">{{ notificationStats.notification_preferences.sms_enabled }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <button class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                            Send System Announcement
                        </button>
                        <button class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700">
                            Manage Templates
                        </button>
                        <button
                            class="w-full rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-purple-700"
                        >
                            View Failed Deliveries
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="rounded-lg border border-gray-700 bg-gray-800">
            <div class="border-b border-gray-700 px-6 py-4">
                <h3 class="text-lg font-medium text-white">Recent Notifications</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div
                        v-for="notification in notificationStats.recent_notifications"
                        :key="notification.id"
                        class="flex items-start space-x-3 rounded-md bg-gray-700 p-3"
                    >
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600">
                            <BellIcon class="h-4 w-4 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-white">
                                {{ notification.title || 'Notification sent' }}
                            </p>
                            <p class="text-xs text-gray-400">{{ notification.type || 'system' }} • {{ formatTimeAgo(notification.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/components/AdminLayout.vue';
import DarkStatCard from '@/components/DarkStatCard.vue';
import { BellIcon } from '@heroicons/vue/24/outline';
import { Head } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { computed } from 'vue';

const props = defineProps({
    notificationStats: Object,
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

const formatTimeAgo = (timestamp) => {
    if (!timestamp) return 'Unknown time';
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true });
};
</script>
