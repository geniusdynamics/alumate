<template>
    <div class="forums-index">
        <!-- Header -->
        <div class="border-b bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Discussion Forums</h1>
                            <p class="mt-2 text-gray-600">Connect with fellow alumni through meaningful discussions</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button
                                @click="showSearch = !showSearch"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                            >
                                <MagnifyingGlassIcon class="mr-2 h-4 w-4" />
                                Search
                            </button>
                            <button
                                v-if="canCreateForum"
                                @click="showCreateForum = true"
                                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
                            >
                                <PlusIcon class="mr-2 h-4 w-4" />
                                Create Forum
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div v-if="showSearch" class="border-b bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <ForumSearch @search="handleSearch" />
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <!-- Forums List -->
                <div class="lg:col-span-3">
                    <div v-if="loading" class="space-y-4">
                        <ForumSkeleton v-for="i in 3" :key="i" />
                    </div>

                    <div v-else-if="forums.length === 0" class="py-12 text-center">
                        <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No forums available</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating the first forum.</p>
                    </div>

                    <div v-else class="space-y-6">
                        <ForumCard v-for="forum in forums" :key="forum.id" :forum="forum" @click="navigateToForum(forum)" />
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="space-y-6">
                        <!-- Popular Tags -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Popular Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="tag in popularTags"
                                    :key="tag.id"
                                    @click="searchByTag(tag.slug)"
                                    :style="{ backgroundColor: tag.color }"
                                    class="inline-flex cursor-pointer items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white hover:opacity-80"
                                >
                                    {{ tag.name }}
                                    <span class="ml-1 text-xs opacity-75">({{ tag.usage_count }})</span>
                                </span>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Recent Activity</h3>
                            <div class="space-y-3">
                                <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start space-x-3">
                                    <img :src="activity.user.avatar_url" :alt="activity.user.name" class="h-8 w-8 rounded-full" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium">{{ activity.user.name }}</span>
                                            posted in
                                            <span class="font-medium text-blue-600">{{ activity.topic.title }}</span>
                                        </p>
                                        <p class="text-xs text-gray-500">{{ formatTimeAgo(activity.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Forum Statistics -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Community Stats</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Forums</span>
                                    <span class="text-sm font-medium text-gray-900">{{ stats.total_forums }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Topics</span>
                                    <span class="text-sm font-medium text-gray-900">{{ stats.total_topics }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Total Posts</span>
                                    <span class="text-sm font-medium text-gray-900">{{ stats.total_posts }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Active Today</span>
                                    <span class="text-sm font-medium text-gray-900">{{ stats.active_users_today }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Forum Modal -->
        <CreateForumModal v-if="showCreateForum" @close="showCreateForum = false" @created="handleForumCreated" />
    </div>
</template>

<script setup>
import { ChatBubbleLeftRightIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import CreateForumModal from '@/Components/Forums/CreateForumModal.vue';
import ForumCard from '@/Components/Forums/ForumCard.vue';
import ForumSearch from '@/Components/Forums/ForumSearch.vue';
import ForumSkeleton from '@/Components/Forums/ForumSkeleton.vue';

// Props
const props = defineProps({
    user: Object,
});

// Reactive data
const forums = ref([]);
const popularTags = ref([]);
const recentActivity = ref([]);
const stats = ref({});
const loading = ref(true);
const showSearch = ref(false);
const showCreateForum = ref(false);

// Computed
const canCreateForum = computed(() => {
    return props.user?.roles?.some((role) => ['admin', 'moderator'].includes(role.name));
});

// Methods
const loadForums = async () => {
    try {
        loading.value = true;
        const response = await fetch('/api/forums');
        const data = await response.json();

        if (data.success) {
            forums.value = data.data;
        }
    } catch (error) {
        console.error('Error loading forums:', error);
    } finally {
        loading.value = false;
    }
};

const loadPopularTags = async () => {
    try {
        const response = await fetch('/api/forums/tags?limit=10');
        const data = await response.json();

        if (data.success) {
            popularTags.value = data.data;
        }
    } catch (error) {
        console.error('Error loading tags:', error);
    }
};

const loadStats = async () => {
    try {
        const response = await fetch('/api/forums/analytics');
        const data = await response.json();

        if (data.success) {
            stats.value = data.data;
            recentActivity.value = data.data.recent_activity || [];
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
};

const navigateToForum = (forum) => {
    router.visit(`/forums/${forum.slug}`);
};

const handleSearch = (searchData) => {
    router.visit('/forums/search', {
        data: searchData,
        preserveState: true,
    });
};

const searchByTag = (tagSlug) => {
    router.visit('/forums/search', {
        data: { tag: tagSlug },
        preserveState: true,
    });
};

const handleForumCreated = (forum) => {
    forums.value.unshift(forum);
    showCreateForum.value = false;
};

const formatTimeAgo = (date) => {
    const now = new Date();
    const past = new Date(date);
    const diffInSeconds = Math.floor((now - past) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
};

// Lifecycle
onMounted(() => {
    loadForums();
    loadPopularTags();
    loadStats();
});
</script>

<style scoped>
.forums-index {
    min-height: 100vh;
    background-color: #f9fafb;
}
</style>











