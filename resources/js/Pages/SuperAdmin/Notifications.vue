<template>
    <AdminLayout
        app-name="Alumate"
        user-role="Super Admin"
        page-title="Notification Management"
        :navigation-items="navigationItems"
    >
        <Head title="Notification Management" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">Notification Management</h2>
            <p class="text-gray-400">Monitor notification delivery, manage templates, and view user preferences.</p>
        </div>

        <!-- Notification Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <DarkStatCard
                title="Total Notifications"
                :value="notificationStats.total_notifications"
                icon="BellIcon"
                color="blue"
            />
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Delivery Rates -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Delivery Performance</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Email Delivery</span>
                            <span class="text-green-400 font-semibold">{{ notificationStats.delivery_rates.email_delivery_rate }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Push Notifications</span>
                            <span class="text-purple-400 font-semibold">{{ notificationStats.delivery_rates.push_delivery_rate }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">SMS Delivery</span>
                            <span class="text-yellow-400 font-semibold">{{ notificationStats.delivery_rates.sms_delivery_rate }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Types -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Notification Types</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Job Alerts</span>
                            <span class="text-blue-400 font-semibold">{{ notificationStats.notification_types.job_alerts }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Connection Requests</span>
                            <span class="text-green-400 font-semibold">{{ notificationStats.notification_types.connection_requests }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Event Reminders</span>
                            <span class="text-purple-400 font-semibold">{{ notificationStats.notification_types.event_reminders }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Preferences and Recent Notifications -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- User Preferences -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">User Preferences</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Email Enabled</span>
                            <span class="text-green-400 font-semibold">{{ notificationStats.notification_preferences.email_enabled }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Push Enabled</span>
                            <span class="text-purple-400 font-semibold">{{ notificationStats.notification_preferences.push_enabled }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">SMS Enabled</span>
                            <span class="text-yellow-400 font-semibold">{{ notificationStats.notification_preferences.sms_enabled }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Send System Announcement
                        </button>
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Manage Templates
                        </button>
                        <button class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            View Failed Deliveries
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Recent Notifications</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div v-for="notification in notificationStats.recent_notifications" :key="notification.id" class="flex items-start space-x-3 p-3 bg-gray-700 rounded-md">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <BellIcon class="w-4 h-4 text-white" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white">
                                {{ notification.title || 'Notification sent' }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ notification.type || 'system' }} • {{ formatTimeAgo(notification.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import DarkStatCard from '@/components/DarkStatCard.vue'
import { formatDistanceToNow } from 'date-fns'
import { BellIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    notificationStats: Object,
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

const formatTimeAgo = (timestamp) => {
    if (!timestamp) return 'Unknown time'
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}
</script>
