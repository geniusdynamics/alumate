<template>
    <AdminLayout app-name="Alumate" user-role="Super Admin" page-title="Content Management" :navigation-items="navigationItems">
        <Head title="Content Management" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="mb-2 text-2xl font-bold text-white">Content Management</h2>
            <p class="text-gray-400">Manage posts, stories, events, and announcements across the platform.</p>
        </div>

        <!-- Content Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <DarkStatCard title="Total Posts" :value="contentStats.total_posts" icon="DocumentTextIcon" color="blue" />
            <DarkStatCard title="Success Stories" :value="contentStats.total_success_stories" icon="StarIcon" color="yellow" />
            <DarkStatCard title="Events" :value="contentStats.total_events" icon="CalendarIcon" color="green" />
            <DarkStatCard title="Announcements" :value="contentStats.total_announcements" icon="SpeakerphoneIcon" color="purple" />
        </div>

        <!-- Content Moderation -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Content Moderation</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Pending Approval</span>
                            <span class="font-semibold text-yellow-400">{{ contentStats.content_moderation.pending_approval }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Flagged Content</span>
                            <span class="font-semibold text-red-400">{{ contentStats.content_moderation.flagged_content }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300">Approved Today</span>
                            <span class="font-semibold text-green-400">{{ contentStats.content_moderation.approved_today }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-700 bg-gray-800">
                <div class="border-b border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-medium text-white">Quick Actions</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <button class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                            Review Pending Content
                        </button>
                        <button class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700">
                            Create Announcement
                        </button>
                        <button
                            class="w-full rounded-md bg-purple-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-purple-700"
                        >
                            Manage Featured Content
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Content -->
        <div class="rounded-lg border border-gray-700 bg-gray-800">
            <div class="border-b border-gray-700 px-6 py-4">
                <h3 class="text-lg font-medium text-white">Recent Content</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Recent Posts -->
                    <div>
                        <h4 class="mb-3 text-sm font-medium text-gray-300">Recent Posts</h4>
                        <div class="space-y-3">
                            <div v-for="post in contentStats.recent_content.posts" :key="post.id" class="rounded-md bg-gray-700 p-3">
                                <p class="truncate text-sm text-white">{{ post.content || 'No content' }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ formatDate(post.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Stories -->
                    <div>
                        <h4 class="mb-3 text-sm font-medium text-gray-300">Recent Stories</h4>
                        <div class="space-y-3">
                            <div v-for="story in contentStats.recent_content.stories" :key="story.id" class="rounded-md bg-gray-700 p-3">
                                <p class="truncate text-sm text-white">{{ story.title || 'Untitled Story' }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ formatDate(story.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Events -->
                    <div>
                        <h4 class="mb-3 text-sm font-medium text-gray-300">Recent Events</h4>
                        <div class="space-y-3">
                            <div v-for="event in contentStats.recent_content.events" :key="event.id" class="rounded-md bg-gray-700 p-3">
                                <p class="truncate text-sm text-white">{{ event.title || 'Untitled Event' }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ formatDate(event.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Components/AdminLayout.vue';
import DarkStatCard from '@/Components/DarkStatCard.vue';
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { computed } from 'vue';

const props = defineProps({
    contentStats: Object,
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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return format(new Date(dateString), 'MMM dd, yyyy');
};
</script>











