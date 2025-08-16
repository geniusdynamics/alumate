<template>
  <div class="real-time-demo p-6 max-w-4xl mx-auto">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">Real-time Updates Demo</h1>
      <p class="text-gray-600">
        This page demonstrates the real-time features of the Alumni Platform.
      </p>
    </div>

    <!-- Connection Status -->
    <div class="mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Connection Status</h2>
        <RealTimeStatus :show-details="true" />
      </div>
    </div>

    <!-- Demo Post -->
    <div class="mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Demo Post with Live Engagement</h2>
        
        <!-- Sample Post -->
        <div class="border rounded-lg p-4 mb-4">
          <div class="flex items-start space-x-3 mb-4">
            <img
              src="https://via.placeholder.com/40x40"
              alt="User Avatar"
              class="w-10 h-10 rounded-full"
            />
            <div class="flex-1">
              <div class="flex items-center space-x-2">
                <h3 class="font-semibold text-gray-900">John Doe</h3>
                <span class="text-sm text-gray-500">@johndoe</span>
                <span class="text-sm text-gray-400">•</span>
                <span class="text-sm text-gray-500">2 hours ago</span>
              </div>
              <p class="text-gray-700 mt-1">
                Just landed my dream job at TechCorp! Thanks to all the amazing connections I made through this platform. 
                The journey from graduation to here has been incredible! 🎉
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
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Live Timeline Updates</h2>
        
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-2">
            <div
              :class="[
                'w-3 h-3 rounded-full',
                hasNewPosts ? 'bg-green-500 animate-pulse' : 'bg-gray-300'
              ]"
            />
            <span class="text-sm text-gray-600">
              {{ hasNewPosts ? `${newPosts.length} new posts available` : 'No new posts' }}
            </span>
          </div>
          
          <button
            v-if="hasNewPosts"
            @click="loadNewPosts"
            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
          >
            Load New Posts ({{ newPosts.length }})
          </button>
        </div>
        
        <!-- New Posts Display -->
        <div v-if="loadedPosts.length > 0" class="space-y-4">
          <div
            v-for="post in loadedPosts"
            :key="post.id"
            class="border rounded-lg p-4 bg-blue-50 animate-fade-in"
          >
            <div class="flex items-center space-x-2 mb-2">
              <img
                :src="post.user.avatar_url || 'https://via.placeholder.com/32x32'"
                :alt="post.user.name"
                class="w-8 h-8 rounded-full"
              />
              <span class="font-medium text-gray-900">{{ post.user.name }}</span>
              <span class="text-sm text-gray-500">just posted</span>
              <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                New
              </span>
            </div>
            <p class="text-gray-700">{{ post.content }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Connection Requests -->
    <div class="mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Live Connection Requests</h2>
        
        <div class="flex items-center space-x-2 mb-4">
          <div
            :class="[
              'w-3 h-3 rounded-full',
              hasNewConnectionRequests ? 'bg-orange-500 animate-pulse' : 'bg-gray-300'
            ]"
          />
          <span class="text-sm text-gray-600">
            {{ hasNewConnectionRequests ? `${connectionRequests.length} new requests` : 'No new requests' }}
          </span>
        </div>
        
        <!-- Connection Requests Display -->
        <div v-if="connectionRequests.length > 0" class="space-y-3">
          <div
            v-for="request in connectionRequests"
            :key="request.connection_id"
            class="border rounded-lg p-4 bg-orange-50 animate-fade-in"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <img
                  :src="request.from_user.avatar_url || 'https://via.placeholder.com/40x40'"
                  :alt="request.from_user.name"
                  class="w-10 h-10 rounded-full"
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
                  class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600"
                >
                  Accept
                </button>
                <button
                  @click="rejectConnection(request.connection_id)"
                  class="px-3 py-1 bg-gray-500 text-white text-sm rounded hover:bg-gray-600"
                >
                  Decline
                </button>
              </div>
            </div>
            <p class="text-sm text-gray-600 mt-2">{{ request.message }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Debug Information -->
    <div class="bg-gray-50 rounded-lg p-6">
      <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <h3 class="font-medium text-gray-900 mb-2">Connection Stats</h3>
          <pre class="text-xs bg-white p-3 rounded border overflow-auto">{{ JSON.stringify(connectionStats, null, 2) }}</pre>
        </div>
        
        <div>
          <h3 class="font-medium text-gray-900 mb-2">Recent Activity</h3>
          <div class="bg-white p-3 rounded border max-h-40 overflow-auto">
            <div
              v-for="(activity, index) in recentActivity"
              :key="index"
              class="text-xs text-gray-600 mb-1"
            >
              {{ activity.timestamp }}: {{ activity.message }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import RealTimeStatus from '@/Components/RealTimeStatus.vue';
import LiveEngagementCounters from '@/Components/LiveEngagementCounters.vue';
import { 
  useRealTimeUpdates, 
  useTimelineRealTime, 
  useConnectionRealTime 
} from '@/Composables/useRealTimeUpdates';

// Demo data
const demoPostId = ref(1);
const loadedPosts = ref([]);
const recentActivity = ref([]);

// Real-time composables
const { getConnectionStats } = useRealTimeUpdates();
const { newPosts, hasNewPosts, startListening: startTimelineListening, getNewPosts } = useTimelineRealTime();
const { 
  connectionRequests, 
  hasNewConnectionRequests, 
  startListening: startConnectionListening,
  clearConnectionRequests 
} = useConnectionRealTime();

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
  const index = connectionRequests.value.findIndex(req => req.connection_id === connectionId);
  if (index > -1) {
    connectionRequests.value.splice(index, 1);
  }
};

const rejectConnection = (connectionId: number) => {
  addActivity(`Rejected connection request ${connectionId}`);
  // Remove from list
  const index = connectionRequests.value.findIndex(req => req.connection_id === connectionId);
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