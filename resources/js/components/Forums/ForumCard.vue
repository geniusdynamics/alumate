<template>
    <div class="cursor-pointer rounded-lg bg-white shadow transition-shadow duration-200 hover:shadow-md" @click="$emit('click')">
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex flex-1 items-start space-x-4">
                    <!-- Forum Icon -->
                    <div
                        class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg text-xl text-white"
                        :style="{ backgroundColor: forum.color }"
                    >
                        <span v-if="forum.icon">{{ forum.icon }}</span>
                        <ChatBubbleLeftRightIcon v-else class="h-6 w-6" />
                    </div>

                    <!-- Forum Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center space-x-2">
                            <h3 class="truncate text-lg font-semibold text-gray-900">
                                {{ forum.name }}
                            </h3>
                            <span
                                v-if="forum.visibility === 'private'"
                                class="inline-flex items-center rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800"
                            >
                                <LockClosedIcon class="mr-1 h-3 w-3" />
                                Private
                            </span>
                            <span
                                v-else-if="forum.visibility === 'group_only'"
                                class="inline-flex items-center rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800"
                            >
                                <UserGroupIcon class="mr-1 h-3 w-3" />
                                Group Only
                            </span>
                        </div>

                        <p v-if="forum.description" class="mt-1 line-clamp-2 text-sm text-gray-600">
                            {{ forum.description }}
                        </p>

                        <div v-if="forum.group" class="mt-2">
                            <span class="inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                                <BuildingOfficeIcon class="mr-1 h-3 w-3" />
                                {{ forum.group.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Forum Stats -->
                <div class="flex-shrink-0 text-right">
                    <div class="text-sm text-gray-500">
                        <div class="flex items-center space-x-4">
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900">{{ forum.topics_count }}</div>
                                <div class="text-xs">Topics</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-gray-900">{{ forum.posts_count }}</div>
                                <div class="text-xs">Posts</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Topics Preview -->
            <div v-if="forum.latest_topics && forum.latest_topics.length > 0" class="mt-4 border-t border-gray-200 pt-4">
                <h4 class="mb-2 text-sm font-medium text-gray-900">Recent Topics</h4>
                <div class="space-y-2">
                    <div v-for="topic in forum.latest_topics.slice(0, 3)" :key="topic.id" class="flex items-center justify-between text-sm">
                        <div class="flex min-w-0 flex-1 items-center space-x-2">
                            <span v-if="topic.is_sticky" class="h-4 w-4 flex-shrink-0 text-yellow-500" title="Sticky"> 📌 </span>
                            <span v-if="topic.is_announcement" class="h-4 w-4 flex-shrink-0 text-blue-500" title="Announcement"> 📢 </span>
                            <span class="truncate font-medium text-gray-900">{{ topic.title }}</span>
                        </div>
                        <div class="flex flex-shrink-0 items-center space-x-2 text-xs text-gray-500">
                            <span>{{ topic.posts_count }} posts</span>
                            <span>•</span>
                            <span>{{ formatTimeAgo(topic.last_post_at || topic.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Last Activity -->
            <div v-if="forum.last_activity_at" class="mt-4 border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>Last activity</span>
                    <span>{{ formatTimeAgo(forum.last_activity_at) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { BuildingOfficeIcon, ChatBubbleLeftRightIcon, LockClosedIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

// Props
defineProps({
    forum: {
        type: Object,
        required: true,
    },
});

// Emits
defineEmits(['click']);

// Methods
const formatTimeAgo = (date) => {
    const now = new Date();
    const past = new Date(date);
    const diffInSeconds = Math.floor((now - past) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
