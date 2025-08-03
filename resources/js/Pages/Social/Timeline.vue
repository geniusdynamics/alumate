<template>
    <AppLayout title="Social Timeline">
        <Head title="Social Timeline" />

        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Social Timeline</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with your alumni network and share your journey</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Timeline -->
                <div class="lg:col-span-3">
                    <!-- Post Creator -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                        <div class="p-6">
                            <PostCreator 
                                :user-circles="userCircles"
                                :user-groups="userGroups"
                                @post-created="handlePostCreated"
                            />
                        </div>
                    </div>

                    <!-- Posts Feed -->
                    <div class="space-y-6">
                        <div v-if="posts.data.length === 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                            <div class="text-gray-500 dark:text-gray-400">
                                <MessageCircleIcon class="mx-auto h-12 w-12 mb-4" />
                                <h3 class="text-lg font-medium mb-2">No posts yet</h3>
                                <p>Be the first to share something with your network!</p>
                            </div>
                        </div>

                        <div v-for="post in posts.data" :key="post.id" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <!-- Post Header -->
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <UserIcon class="w-6 h-6 text-gray-600 dark:text-gray-300" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ post.user.name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatTimeAgo(post.created_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <div class="p-6">
                                <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ post.content }}</p>
                                
                                <!-- Post Images -->
                                <div v-if="post.images && post.images.length > 0" class="mt-4 grid grid-cols-2 gap-2">
                                    <img 
                                        v-for="image in post.images" 
                                        :key="image.id"
                                        :src="image.url" 
                                        :alt="image.alt_text"
                                        class="rounded-lg object-cover h-48 w-full"
                                    />
                                </div>
                            </div>

                            <!-- Post Actions -->
                            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                                <PostReactions 
                                    :post="post"
                                    @reaction-updated="handleReactionUpdated"
                                />
                            </div>

                            <!-- Comments Section -->
                            <div class="border-t border-gray-200 dark:border-gray-700">
                                <PostComments 
                                    :post-id="post.id"
                                    :comments="post.comments"
                                    @comment-added="handleCommentAdded"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Load More -->
                    <div v-if="posts.next_page_url" class="mt-6 text-center">
                        <button 
                            @click="loadMorePosts"
                            :disabled="loadingMore"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors disabled:opacity-50"
                        >
                            <span v-if="loadingMore">Loading...</span>
                            <span v-else>Load More Posts</span>
                        </button>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Suggested Connections -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">People You May Know</h3>
                        </div>
                        <div class="p-4">
                            <PeopleYouMayKnow 
                                :suggestions="suggestedConnections"
                                @connection-requested="handleConnectionRequested"
                            />
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <Link 
                                :href="route('social.circles')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <UsersIcon class="w-5 h-5" />
                                <span>Manage Circles</span>
                            </Link>
                            <Link 
                                :href="route('social.groups')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <UserGroupIcon class="w-5 h-5" />
                                <span>Join Groups</span>
                            </Link>
                            <Link 
                                :href="route('alumni.directory')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <MagnifyingGlassIcon class="w-5 h-5" />
                                <span>Find Alumni</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PostCreator from '@/Components/PostCreator.vue'
import PostReactions from '@/Components/PostReactions.vue'
import PostComments from '@/Components/PostComments.vue'
import PeopleYouMayKnow from '@/Components/PeopleYouMayKnow.vue'
import { formatDistanceToNow } from 'date-fns'
import {
    MessageCircleIcon,
    UserIcon,
    UsersIcon,
    UserGroupIcon,
    MagnifyingGlassIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    posts: Object,
    userCircles: Array,
    userGroups: Array,
    suggestedConnections: Array,
})

const loadingMore = ref(false)

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const handlePostCreated = (newPost) => {
    // Refresh the page to show the new post
    router.reload()
}

const handleReactionUpdated = (postId, reactions) => {
    // Update the post reactions in the local state
    const post = props.posts.data.find(p => p.id === postId)
    if (post) {
        post.engagements = reactions
    }
}

const handleCommentAdded = (postId, comment) => {
    // Add the new comment to the post
    const post = props.posts.data.find(p => p.id === postId)
    if (post) {
        post.comments.push(comment)
    }
}

const handleConnectionRequested = (userId) => {
    // Remove the user from suggested connections
    const index = props.suggestedConnections.findIndex(user => user.id === userId)
    if (index > -1) {
        props.suggestedConnections.splice(index, 1)
    }
}

const loadMorePosts = () => {
    if (props.posts.next_page_url && !loadingMore.value) {
        loadingMore.value = true
        router.visit(props.posts.next_page_url, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                loadingMore.value = false
            }
        })
    }
}
</script>
