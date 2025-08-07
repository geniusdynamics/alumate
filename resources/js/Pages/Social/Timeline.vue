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
                                <ChatBubbleLeftIcon class="mx-auto h-12 w-12 mb-4" />
                                <h3 class="text-lg font-medium mb-2">No posts yet</h3>
                                <p>Be the first to share something with your network!</p>
                            </div>
                        </div>

                        <div v-for="post in timelinePosts" :key="post.id" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <!-- Post Header -->
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
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
                                            <EllipsisHorizontalIcon class="w-5 h-5" />
                                        </button>
                                        
                                        <div 
                                            v-if="showPostOptions[post.id]"
                                            class="absolute right-0 top-full mt-1 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg border border-gray-200 dark:border-gray-600 z-10"
                                        >
                                            <button 
                                                @click="startEditingPost(post)"
                                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                                            >
                                                <PencilIcon class="w-4 h-4 mr-2" />
                                                Edit Post
                                            </button>
                                            <button 
                                                @click="handlePostDeleted(post.id)"
                                                class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600"
                                            >
                                                <TrashIcon class="w-4 h-4 mr-2" />
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

        <!-- User Flow Integration -->
        <UserFlowIntegration />
        
        <!-- Real-time Updates -->
        <RealTimeUpdates 
            :show-post-updates="true"
            :show-engagement-counters="true"
        />
        
        <!-- Cross-feature Connections -->
        <CrossFeatureConnections 
            context="social-timeline"
            :context-data="{ posts: timelinePosts }"
        />
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PostCreator from '@/components/PostCreator.vue'
import PostReactions from '@/components/PostReactions.vue'
import PostComments from '@/components/PostComments.vue'
import PeopleYouMayKnow from '@/components/PeopleYouMayKnow.vue'
import UserFlowIntegration from '@/components/UserFlowIntegration.vue'
import RealTimeUpdates from '@/components/RealTimeUpdates.vue'
import CrossFeatureConnections from '@/components/CrossFeatureConnections.vue'
import { formatDistanceToNow } from 'date-fns'
import { useRealTimeUpdates } from '@/composables/useRealTimeUpdates'
import userFlowIntegration from '@/services/UserFlowIntegration'
import {
    ChatBubbleLeftIcon,
    UserIcon,
    UsersIcon,
    UserGroupIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    EllipsisHorizontalIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    posts: Object,
    userCircles: Array,
    userGroups: Array,
    suggestedConnections: Array,
})

const loadingMore = ref(false)
const timelinePosts = reactive([...props.posts.data])
const editingPost = ref(null)
const showPostOptions = ref({})

// Real-time updates
const realTimeUpdates = useRealTimeUpdates()

onMounted(() => {
    // Set up real-time event listeners
    realTimeUpdates.onPostCreated((post) => {
        // Add new post to the beginning of the timeline
        timelinePosts.unshift(post)
        userFlowIntegration.showNotification('New post from your network!', 'info')
    })
    
    realTimeUpdates.onPostUpdated((post) => {
        // Update existing post
        const index = timelinePosts.findIndex(p => p.id === post.id)
        if (index > -1) {
            timelinePosts[index] = post
        }
    })
    
    realTimeUpdates.onPostEngagement((postId, engagement) => {
        // Update post engagement
        const post = timelinePosts.find(p => p.id === postId)
        if (post) {
            post.engagements = engagement
        }
    })
    
    realTimeUpdates.onCommentAdded((postId, comment) => {
        // Add new comment
        const post = timelinePosts.find(p => p.id === postId)
        if (post) {
            post.comments.push(comment)
        }
    })
    
    // Set up user flow integration callbacks
    userFlowIntegration.on('postCreated', (post) => {
        timelinePosts.unshift(post)
    })
    
    userFlowIntegration.on('postUpdated', (post) => {
        const index = timelinePosts.findIndex(p => p.id === post.id)
        if (index > -1) {
            timelinePosts[index] = post
            editingPost.value = null
        }
    })
    
    userFlowIntegration.on('postDeleted', (postId) => {
        const index = timelinePosts.findIndex(p => p.id === postId)
        if (index > -1) {
            timelinePosts.splice(index, 1)
        }
    })
})

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const handlePostCreated = async (postData) => {
    try {
        await userFlowIntegration.createPostAndRefreshTimeline(postData)
    } catch (error) {
        console.error('Failed to create post:', error)
    }
}

const handlePostUpdated = async (postId, updateData) => {
    try {
        await userFlowIntegration.updatePostAndRefresh(postId, updateData)
    } catch (error) {
        console.error('Failed to update post:', error)
    }
}

const handlePostDeleted = async (postId) => {
    if (confirm('Are you sure you want to delete this post?')) {
        try {
            await userFlowIntegration.deletePostAndRefresh(postId)
        } catch (error) {
            console.error('Failed to delete post:', error)
        }
    }
}

const handleReactionUpdated = (postId, reactions) => {
    const post = timelinePosts.find(p => p.id === postId)
    if (post) {
        post.engagements = reactions
    }
}

const handleCommentAdded = (postId, comment) => {
    const post = timelinePosts.find(p => p.id === postId)
    if (post) {
        post.comments.push(comment)
    }
}

const handleConnectionRequested = async (userId) => {
    try {
        await userFlowIntegration.sendConnectionRequestAndUpdate(userId)
        // Remove from suggested connections
        const index = props.suggestedConnections.findIndex(user => user.id === userId)
        if (index > -1) {
            props.suggestedConnections.splice(index, 1)
        }
    } catch (error) {
        console.error('Failed to send connection request:', error)
    }
}

const togglePostOptions = (postId) => {
    showPostOptions.value[postId] = !showPostOptions.value[postId]
}

const startEditingPost = (post) => {
    editingPost.value = post
    showPostOptions.value[post.id] = false
}

const cancelEditing = () => {
    editingPost.value = null
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

const canEditPost = (post) => {
    return post.user.id === props.auth?.user?.id
}
</script>
