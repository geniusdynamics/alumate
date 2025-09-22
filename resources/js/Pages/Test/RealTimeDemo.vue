<template>
    <div class="real-time-demo mx-auto max-w-4xl p-6">
        <div class="mb-8">
            <h1 class="mb-4 text-3xl font-bold text-gray-900">Real-time Updates Demo</h1>
            <p class="text-gray-600">This page demonstrates the real-time features of the Alumni Platform.</p>
        </div>

        <!-- Connection Status -->
        <div class="mb-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">Connection Status</h2>
                <RealTimeStatus :show-details="true" />
            </div>
        </div>

        <!-- Demo Post -->
        <div class="mb-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">Demo Post with Live Engagement</h2>

                <!-- Sample Post -->
                <div class="mb-4 rounded-lg border p-4">
                    <div class="mb-4 flex items-start space-x-3">
                        <img src="https://via.placeholder.com/40x40" alt="User Avatar" class="h-10 w-10 rounded-full" />
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-semibold text-gray-900">John Doe</h3>
                                <span class="text-sm text-gray-500">@johndoe</span>
                                <span class="text-sm text-gray-400">•</span>
                                <span class="text-sm text-gray-500">2 hours ago</span>
                            </div>
                            <p class="mt-1 text-gray-700">
                                Just landed my dream job at TechCorp! Thanks to all the amazing connections I made through this platform. The journey
                                from graduation to here has been incredible! 🎉
                            </p>
                        </div>
                    </div>

                    <!-- Live Engagement Counters -->
                    <LiveEngagementCounters
                        :post-id="demoPostId"
                        :initial-counts="{ likes: 15, comments: 3, shares: 2, reactions: 8 }"
                        :show-reactions="true"
                        :show-recent-activity="true"
                        :show-live-users="false"
                        @like="handleLike"
                        @comment="handleComment"
                        @share="handleShare"
                        @reaction="handleReaction"
                    />
                </div>
            </div>
        </div>

        <!-- Timeline Updates -->
        <div class="mb-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">Live Timeline Updates</h2>

                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div :class="['h-3 w-3 rounded-full', hasNewPosts ? 'animate-pulse bg-green-500' : 'bg-gray-300']" />
                        <span class="text-sm text-gray-600">
                            {{ hasNewPosts ? `${newPosts.length} new posts available` : 'No new posts' }}
                        </span>
                    </div>

                    <button
                        v-if="hasNewPosts"
                        @click="loadNewPosts"
                        class="rounded-lg bg-blue-500 px-4 py-2 text-white transition-colors hover:bg-blue-600"
                    >
                        Load New Posts ({{ newPosts.length }})
                    </button>
                </div>

                <!-- New Posts Display -->
                <div v-if="loadedPosts.length > 0" class="space-y-4">
                    <div v-for="post in loadedPosts" :key="post.id" class="animate-fade-in rounded-lg border bg-blue-50 p-4">
                        <div class="mb-2 flex items-center space-x-2">
                            <img
                                :src="post.user.avatar_url || 'https://via.placeholder.com/32x32'"
                                :alt="post.user.name"
                                class="h-8 w-8 rounded-full"
                            />
                            <span class="font-medium text-gray-900">{{ post.user.name }}</span>
                            <span class="text-sm text-gray-500">just posted</span>
                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs text-green-800"> New </span>
                        </div>
                        <p class="text-gray-700">{{ post.content }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Requests -->
        <div class="mb-8">
            <div class="rounded-lg bg-white p-6 shadow">
                <h2 class="mb-4 text-xl font-semibold">Live Connection Requests</h2>

                <div class="mb-4 flex items-center space-x-2">
                    <div :class="['h-3 w-3 rounded-full', hasNewConnectionRequests ? 'animate-pulse bg-orange-500' : 'bg-gray-300']" />
                    <span class="text-sm text-gray-600">
                        {{ hasNewConnectionRequests ? `${connectionRequests.length} new requests` : 'No new requests' }}
                    </span>
                </div>

                <!-- Connection Requests Display -->
                <div v-if="connectionRequests.length > 0" class="space-y-3">
                    <div
                        v-for="request in connectionRequests"
                        :key="request.connection_id"
                        class="animate-fade-in rounded-lg border bg-orange-50 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="request.from_user.avatar_url || 'https://via.placeholder.com/40x40'"
                                    :alt="request.from_user.name"
                                    class="h-10 w-10 rounded-full"
                                />
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ request.from_user.name }}</h4>
                                    <p class="text-sm text-gray-600">{{ request.from_user.current_position }}</p>
                                    <p class="text-sm text-gray-500">{{ request.from_user.current_company }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    @click="acceptConnection(request.connection_id)"
                                    class="rounded bg-green-500 px-3 py-1 text-sm text-white hover:bg-green-600"
                                >
                                    Accept
                                </button>
                                <button
                                    @click="rejectConnection(request.connection_id)"
                                    class="rounded bg-gray-500 px-3 py-1 text-sm text-white hover:bg-gray-600"
                                >
                                    Decline
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">{{ request.message }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug Information -->
        <div class="rounded-lg bg-gray-50 p-6">
            <h2 class="mb-4 text-xl font-semibold">Debug Information</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <h3 class="mb-2 font-medium text-gray-900">Connection Stats</h3>
                    <pre class="overflow-auto rounded border bg-white p-3 text-xs">{{ JSON.stringify(connectionStats, null, 2) }}</pre>
                </div>

                <div>
                    <h3 class="mb-2 font-medium text-gray-900">Recent Activity</h3>
                    <div class="max-h-40 overflow-auto rounded border bg-white p-3">
                        <div v-for="(activity, index) in recentActivity" :key="index" class="mb-1 text-xs text-gray-600">
                            {{ activity.timestamp }}: {{ activity.message }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import LiveEngagementCounters from '@/Components/LiveEngagementCounters.vue';
import RealTimeStatus from '@/Components/RealTimeStatus.vue';
import { useConnectionRealTime, useRealTimeUpdates, useTimelineRealTime } from '@/Composables/useRealTimeUpdates';
import { onMounted, onUnmounted, ref } from 'vue';

// Demo data
const demoPostId = ref(1);
const loadedPosts = ref([]);
const recentActivity = ref([]);

// Real-time composables
const { getConnectionStats } = useRealTimeUpdates();
const { newPosts, hasNewPosts, startListening: startTimelineListening, getNewPosts } = useTimelineRealTime();
const { connectionRequests, hasNewConnectionRequests, startListening: startConnectionListening, clearConnectionRequests } = useConnectionRealTime();

// State
const connectionStats = ref({});

// Methods
const handleLike = (postId: number) => {
    addActivity(`Liked post ${postId}`);
};

const handleComment = (postId: number) => {
    addActivity(`Opened comments for post ${postId}`);
};

const handleShare = (postId: number) => {
    addActivity(`Shared post ${postId}`);
};

const handleReaction = (postId: number, reactionType: string) => {
    addActivity(`Added ${reactionType} reaction to post ${postId}`);
};

const loadNewPosts = () => {
    const posts = getNewPosts();
    loadedPosts.value.unshift(...posts);
    addActivity(`Loaded ${posts.length} new posts`);
};

const acceptConnection = (connectionId: number) => {
    addActivity(`Accepted connection request ${connectionId}`);
    // Remove from list
    const index = connectionRequests.value.findIndex((req) => req.connection_id === connectionId);
    if (index > -1) {
        connectionRequests.value.splice(index, 1);
    }
};

const rejectConnection = (connectionId: number) => {
    addActivity(`Rejected connection request ${connectionId}`);
    // Remove from list
    const index = connectionRequests.value.findIndex((req) => req.connection_id === connectionId);
    if (index > -1) {
        connectionRequests.value.splice(index, 1);
    }
};

const addActivity = (message: string) => {
    recentActivity.value.unshift({
        timestamp: new Date().toLocaleTimeString(),
        message,
    });

    // Keep only last 20 activities
    if (recentActivity.value.length > 20) {
        recentActivity.value = recentActivity.value.slice(0, 20);
    }
};

const updateConnectionStats = () => {
    connectionStats.value = getConnectionStats();
};

// Lifecycle
let timelineUnsubscribe: (() => void) | null = null;
let connectionUnsubscribe: (() => void) | null = null;
let statsInterval: NodeJS.Timeout;

onMounted(() => {
    // Start listening for real-time updates
    timelineUnsubscribe = startTimelineListening(true);
    connectionUnsubscribe = startConnectionListening();

    // Update connection stats periodically
    updateConnectionStats();
    statsInterval = setInterval(updateConnectionStats, 5000);

    addActivity('Demo page loaded');

    // Simulate some demo data after a delay
    setTimeout(() => {
        // Simulate a new post
        newPosts.value.push({
            id: Date.now(),
            content: 'This is a simulated real-time post update!',
            user: {
                id: 2,
                name: 'Jane Smith',
                username: 'janesmith',
                avatar_url: 'https://via.placeholder.com/40x40',
            },
            created_at: new Date(),
        });

        addActivity('Simulated new post received');
    }, 3000);

    // Simulate a connection request
    setTimeout(() => {
        connectionRequests.value.push({
            connection_id: Date.now(),
            status: 'pending',
            from_user: {
                id: 3,
                name: 'Mike Johnson',
                username: 'mikejohnson',
                avatar_url: 'https://via.placeholder.com/40x40',
                current_position: 'Software Engineer',
                current_company: 'TechCorp',
            },
            message: 'Hi! I saw your profile and would love to connect. We have similar backgrounds in software development.',
            timestamp: new Date(),
            notification_type: 'connection_request_received',
        });

        addActivity('Simulated connection request received');
    }, 5000);
});

onUnmounted(() => {
    if (timelineUnsubscribe) timelineUnsubscribe();
    if (connectionUnsubscribe) connectionUnsubscribe();
    if (statsInterval) clearInterval(statsInterval);
});
</script>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>



