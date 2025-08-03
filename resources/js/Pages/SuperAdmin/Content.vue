<template>
    <AdminLayout
        app-name="Alumate"
        user-role="Super Admin"
        page-title="Content Management"
        :navigation-items="navigationItems"
    >
        <Head title="Content Management" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">Content Management</h2>
            <p class="text-gray-400">Manage posts, stories, events, and announcements across the platform.</p>
        </div>

        <!-- Content Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <DarkStatCard
                title="Total Posts"
                :value="contentStats.total_posts"
                icon="DocumentTextIcon"
                color="blue"
            />
            <DarkStatCard
                title="Success Stories"
                :value="contentStats.total_success_stories"
                icon="StarIcon"
                color="yellow"
            />
            <DarkStatCard
                title="Events"
                :value="contentStats.total_events"
                icon="CalendarIcon"
                color="green"
            />
            <DarkStatCard
                title="Announcements"
                :value="contentStats.total_announcements"
                icon="SpeakerphoneIcon"
                color="purple"
            />
        </div>

        <!-- Content Moderation -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Content Moderation</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Pending Approval</span>
                            <span class="text-yellow-400 font-semibold">{{ contentStats.content_moderation.pending_approval }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Flagged Content</span>
                            <span class="text-red-400 font-semibold">{{ contentStats.content_moderation.flagged_content }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Approved Today</span>
                            <span class="text-green-400 font-semibold">{{ contentStats.content_moderation.approved_today }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Review Pending Content
                        </button>
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Create Announcement
                        </button>
                        <button class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Manage Featured Content
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Content -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Recent Content</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Posts -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-300 mb-3">Recent Posts</h4>
                        <div class="space-y-3">
                            <div v-for="post in contentStats.recent_content.posts" :key="post.id" class="bg-gray-700 p-3 rounded-md">
                                <p class="text-sm text-white truncate">{{ post.content || 'No content' }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(post.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Stories -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-300 mb-3">Recent Stories</h4>
                        <div class="space-y-3">
                            <div v-for="story in contentStats.recent_content.stories" :key="story.id" class="bg-gray-700 p-3 rounded-md">
                                <p class="text-sm text-white truncate">{{ story.title || 'Untitled Story' }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(story.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Events -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-300 mb-3">Recent Events</h4>
                        <div class="space-y-3">
                            <div v-for="event in contentStats.recent_content.events" :key="event.id" class="bg-gray-700 p-3 rounded-md">
                                <p class="text-sm text-white truncate">{{ event.title || 'Untitled Event' }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ formatDate(event.created_at) }}</p>
                            </div>
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
import AdminLayout from '@/Components/AdminLayout.vue'
import DarkStatCard from '@/Components/DarkStatCard.vue'
import { format } from 'date-fns'

const props = defineProps({
    contentStats: Object,
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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return format(new Date(dateString), 'MMM dd, yyyy')
}
</script>
