<template>
    <AppLayout title="Social Timeline">
        <Head title="Social Timeline" />

        <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Social Timeline</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with your alumni network and share your journey</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                <!-- Main Timeline -->
                <div class="lg:col-span-3">
                    <!-- Post Creator -->
                    <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="p-6">
                            <PostCreator :user-circles="userCircles" :user-groups="userGroups" @post-created="handlePostCreated" />
                        </div>
                    </div>

                    <!-- Posts Feed -->
                    <div class="space-y-6">
                        <div v-if="posts.data.length === 0" class="rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
                            <div class="text-gray-500 dark:text-gray-400">
                                <ChatBubbleLeftIcon class="mx-auto mb-4 h-12 w-12" />
                                <h3 class="mb-2 text-lg font-medium">No posts yet</h3>
                                <p>Be the first to share something with your network!</p>
                            </div>
                        </div>

                        <div v-for="post in timelinePosts" :key="post.id" class="rounded-lg bg-white shadow dark:bg-gray-800">
                            <!-- Post Header -->
                            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                                                <UserIcon class="h-6 w-6 text-gray-600 dark:text-gray-300" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ post.user.name }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatTimeAgo(post.created_at) }}
                                                <span v-if="post.updated_at !== post.created_at" class="ml-1">(edited)</span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Post Options -->
                                    <div v-if="canEditPost(post)" class="relative">
                                        <button
                                            @click="togglePostOptions(post.id)"
                                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        >
                                            <EllipsisHorizontalIcon class="h-5 w-5" />
                                        </button>

                                        <div
                                            v-if="showPostOptions[post.id]"
                                            class="absolute right-0 top-full z-10 mt-1 w-48 rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
                                        >
                                            <button
                                                @click="startEditingPost(post)"
                                                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600"
                                            >
                                                <PencilIcon class="mr-2 h-4 w-4" />
                                                Edit Post
                                            </button>
                                            <button
                                                @click="handlePostDeleted(post.id)"
                                                class="flex w-full items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600"
                                            >
                                                <TrashIcon class="mr-2 h-4 w-4" />
                                                Delete Post
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Post Content or Edit Form -->
                            <div class="p-6">
                                <div v-if="editingPost && editingPost.id === post.id">
                                    <!-- Edit Form -->
                                    <PostCreator
                                        :edit-post="post"
                                        :user-circles="userCircles"
                                        :user-groups="userGroups"
                                        @post-updated="handlePostUpdated"
                                        @cancel="cancelEditing"
                                    />
                                </div>
                                <div v-else>
                                    <p class="whitespace-pre-wrap text-gray-900 dark:text-white">{{ post.content }}</p>

                                    <!-- Post Images -->
                                    <div v-if="post.images && post.images.length > 0" class="mt-4 grid grid-cols-2 gap-2">
                                        <img
                                            v-for="image in post.images"
                                            :key="image.id"
                                            :src="image.url"
                                            :alt="image.alt_text"
                                            class="h-48 w-full rounded-lg object-cover"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Post Actions -->
                            <div class="border-t border-gray-200 px-6 py-3 dark:border-gray-700">
                                <PostReactions :post="post" @reaction-updated="handleReactionUpdated" />
                            </div>

                            <!-- Comments Section -->
                            <div class="border-t border-gray-200 dark:border-gray-700">
                                <PostComments :post-id="post.id" :comments="post.comments" @comment-added="handleCommentAdded" />
                            </div>
                        </div>
                    </div>

                    <!-- Load More -->
                    <div v-if="posts.next_page_url" class="mt-6 text-center">
                        <button
                            @click="loadMorePosts"
                            :disabled="loadingMore"
                            class="rounded-md bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span v-if="loadingMore">Loading...</span>
                            <span v-else>Load More Posts</span>
                        </button>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Suggested Connections -->
                    <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">People You May Know</h3>
                        </div>
                        <div class="p-4">
                            <PeopleYouMayKnow :suggestions="suggestedConnections" @connection-requested="handleConnectionRequested" />
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="space-y-3 p-4">
                            <Link
                                :href="route('social.circles')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <UsersIcon class="h-5 w-5" />
                                <span>Manage Circles</span>
                            </Link>
                            <Link
                                :href="route('social.groups')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <UserGroupIcon class="h-5 w-5" />
                                <span>Join Groups</span>
                            </Link>
                            <Link
                                :href="route('alumni.directory')"
                                class="flex items-center space-x-3 text-sm text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="h-5 w-5" />
                                <span>Find Alumni</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Flow Integration -->
        <UserFlowIntegration />

        <!-- Real-time Updates -->
        <RealTimeUpdates :show-post-updates="true" :show-engagement-counters="true" />

        <!-- Cross-feature Connections -->
        <CrossFeatureConnections context="social-timeline" :context-data="{ posts: timelinePosts }" />
    </AppLayout>
</template>

<script setup>
import CrossFeatureConnections from '@/components/CrossFeatureConnections.vue';
import PeopleYouMayKnow from '@/components/PeopleYouMayKnow.vue';
import PostComments from '@/components/PostComments.vue';
import PostCreator from '@/components/PostCreator.vue';
import PostReactions from '@/components/PostReactions.vue';
import RealTimeUpdates from '@/components/RealTimeUpdates.vue';
import UserFlowIntegration from '@/components/UserFlowIntegration.vue';
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates';
import AppLayout from '@/layouts/AppLayout.vue';
import userFlowIntegration from '@/services/UserFlowIntegration';
import {
    ChatBubbleLeftIcon,
    EllipsisHorizontalIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    UserGroupIcon,
    UserIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { onMounted, reactive, ref } from 'vue';

const props = defineProps({
    posts: Object,
    userCircles: Array,
    userGroups: Array,
    suggestedConnections: Array,
});

const loadingMore = ref(false);
const timelinePosts = reactive([...props.posts.data]);
const editingPost = ref(null);
const showPostOptions = ref({});

// Real-time updates
const realTimeUpdates = useRealTimeUpdates();

onMounted(() => {
    // Set up real-time event listeners
    realTimeUpdates.onPostCreated((post) => {
        // Add new post to the beginning of the timeline
        timelinePosts.unshift(post);
        userFlowIntegration.showNotification('New post from your network!', 'info');
    });

    realTimeUpdates.onPostUpdated((post) => {
        // Update existing post
        const index = timelinePosts.findIndex((p) => p.id === post.id);
        if (index > -1) {
            timelinePosts[index] = post;
        }
    });

    realTimeUpdates.onPostEngagement((postId, engagement) => {
        // Update post engagement
        const post = timelinePosts.find((p) => p.id === postId);
        if (post) {
            post.engagements = engagement;
        }
    });

    realTimeUpdates.onCommentAdded((postId, comment) => {
        // Add new comment
        const post = timelinePosts.find((p) => p.id === postId);
        if (post) {
            post.comments.push(comment);
        }
    });

    // Set up user flow integration callbacks
    userFlowIntegration.on('postCreated', (post) => {
        timelinePosts.unshift(post);
    });

    userFlowIntegration.on('postUpdated', (post) => {
        const index = timelinePosts.findIndex((p) => p.id === post.id);
        if (index > -1) {
            timelinePosts[index] = post;
            editingPost.value = null;
        }
    });

    userFlowIntegration.on('postDeleted', (postId) => {
        const index = timelinePosts.findIndex((p) => p.id === postId);
        if (index > -1) {
            timelinePosts.splice(index, 1);
        }
    });
});

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true });
};

const handlePostCreated = async (postData) => {
    try {
        await userFlowIntegration.createPostAndRefreshTimeline(postData);
    } catch (error) {
        console.error('Failed to create post:', error);
    }
};

const handlePostUpdated = async (postId, updateData) => {
    try {
        await userFlowIntegration.updatePostAndRefresh(postId, updateData);
    } catch (error) {
        console.error('Failed to update post:', error);
    }
};

const handlePostDeleted = async (postId) => {
    if (confirm('Are you sure you want to delete this post?')) {
        try {
            await userFlowIntegration.deletePostAndRefresh(postId);
        } catch (error) {
            console.error('Failed to delete post:', error);
        }
    }
};

const handleReactionUpdated = (postId, reactions) => {
    const post = timelinePosts.find((p) => p.id === postId);
    if (post) {
        post.engagements = reactions;
    }
};

const handleCommentAdded = (postId, comment) => {
    const post = timelinePosts.find((p) => p.id === postId);
    if (post) {
        post.comments.push(comment);
    }
};

const handleConnectionRequested = async (userId) => {
    try {
        await userFlowIntegration.sendConnectionRequestAndUpdate(userId);
        // Remove from suggested connections
        const index = props.suggestedConnections.findIndex((user) => user.id === userId);
        if (index > -1) {
            props.suggestedConnections.splice(index, 1);
        }
    } catch (error) {
        console.error('Failed to send connection request:', error);
    }
};

const togglePostOptions = (postId) => {
    showPostOptions.value[postId] = !showPostOptions.value[postId];
};

const startEditingPost = (post) => {
    editingPost.value = post;
    showPostOptions.value[post.id] = false;
};

const cancelEditing = () => {
    editingPost.value = null;
};

const loadMorePosts = () => {
    if (props.posts.next_page_url && !loadingMore.value) {
        loadingMore.value = true;
        router.visit(props.posts.next_page_url, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                loadingMore.value = false;
            },
        });
    }
};

const canEditPost = (post) => {
    return post.user.id === props.auth?.user?.id;
};
</script>

















