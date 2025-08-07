<template>
    <AdminLayout
        app-name="Alumate"
        user-role="Super Admin"
        page-title="Activity Monitoring"
        :navigation-items="navigationItems"
    >
        <Head title="Activity Monitoring" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">Activity Monitoring</h2>
            <p class="text-gray-400">Monitor user activity, engagement, and feature usage across the platform.</p>
        </div>

        <!-- Active Users Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <DarkStatCard
                title="Daily Active Users"
                :value="activityStats.daily_active_users"
                icon="UsersIcon"
                color="blue"
            />
            <DarkStatCard
                title="Weekly Active Users"
                :value="activityStats.weekly_active_users"
                icon="UsersIcon"
                color="green"
            />
            <DarkStatCard
                title="Monthly Active Users"
                :value="activityStats.monthly_active_users"
                icon="UsersIcon"
                color="purple"
            />
        </div>

        <!-- Engagement and Feature Usage -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- User Engagement -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">User Engagement (Today)</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Posts Created</span>
                            <span class="text-blue-400 font-semibold">{{ activityStats.user_engagement.posts_created }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Comments Made</span>
                            <span class="text-green-400 font-semibold">{{ activityStats.user_engagement.comments_made }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Connections Made</span>
                            <span class="text-purple-400 font-semibold">{{ activityStats.user_engagement.connections_made }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Usage -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Feature Usage (Today)</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Job Applications</span>
                            <span class="text-yellow-400 font-semibold">{{ activityStats.feature_usage.job_applications }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Event Registrations</span>
                            <span class="text-red-400 font-semibold">{{ activityStats.feature_usage.event_registrations }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Messages Sent</span>
                            <span class="text-indigo-400 font-semibold">{{ activityStats.feature_usage.messages_sent }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Recent Activity Timeline</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div v-for="activity in activityStats.activity_timeline" :key="activity.id" class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <component :is="getActivityIcon(activity.event)" class="w-4 h-4 text-white" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white">
                                {{ activity.description || 'Activity recorded' }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ formatTimeAgo(activity.created_at) }}
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
import {
    UsersIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    BriefcaseIcon,
    CalendarIcon,
    UserPlusIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    activityStats: Object,
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

const getActivityIcon = (eventType) => {
    const icons = {
        'user_registration': UsersIcon,
        'post_created': DocumentTextIcon,
        'message_sent': ChatBubbleLeftRightIcon,
        'job_application': BriefcaseIcon,
        'event_registration': CalendarIcon,
        'connection_made': UserPlusIcon,
    }
    return icons[eventType] || DocumentTextIcon
}

const formatTimeAgo = (timestamp) => {
    if (!timestamp) return 'Unknown time'
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}
</script>
