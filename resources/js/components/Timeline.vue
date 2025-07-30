<template>
  <div class="timeline-container">
    <!-- Pull to refresh indicator -->
    <div 
      v-if="isPullingToRefresh" 
      class="pull-to-refresh-indicator"
      :class="{ 'active': pullDistance > 50 }"
    >
      <div class="refresh-icon">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      <span class="text-sm text-gray-600">
        {{ pullDistance > 50 ? 'Release to refresh' : 'Pull to refresh' }}
      </span>
    </div>

    <!-- Timeline posts -->
    <div 
      ref="timelineContainer"
      class="timeline-posts"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
      @scroll="handleScroll"
    >
      <!-- Loading skeleton for initial load -->
      <div v-if="isInitialLoading" class="space-y-4">
        <PostSkeleton v-for="i in 5" :key="i" />
      </div>

      <!-- Posts -->
      <div v-else-if="posts.length > 0" class="space-y-6">
        <PostCard
          v-for="post in posts"
          :key="post.id"
          :post="post"
          @engagement-updated="handleEngagementUpdate"
          @post-deleted="handlePostDeleted"
        />

        <!-- Load more indicator -->
        <div v-if="isLoadingMore" class="flex justify-center py-4">
          <div class="flex items-center space-x-2">
            <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600">Loading more posts...</span>
          </div>
        </div>

        <!-- End of timeline indicator -->
        <div v-else-if="!hasMore" class="text-center py-8">
          <div class="text-gray-500">
            <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <p class="text-sm">You're all caught up!</p>
            <p class="text-xs mt-1">Check back later for new posts</p>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-12">
        <div class="text-gray-500">
          <svg class="mx-auto h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <h3 class="text-lg font-medium mb-2">No posts yet</h3>
          <p class="text-sm mb-4">Be the first to share something with your community!</p>
          <button 
            @click="$emit('create-post')"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Create Post
          </button>
        </div>
      </div>
    </div>

    <!-- Real-time update notification -->
    <div 
      v-if="hasNewPosts" 
      class="new-posts-notification"
      @click="loadNewPosts"
    >
      <div class="flex items-center space-x-2">
        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
        <span class="text-sm font-medium">{{ newPostsCount }} new post{{ newPostsCount > 1 ? 's' : '' }}</span>
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import PostCard from './PostCard.vue'
import PostSkeleton from './PostSkeleton.vue'

// Props
const props = defineProps({
  initialPosts: {
    type: Array,
    default: () => []
  },
  initialCursor: {
    type: String,
    default: null
  },
  initialHasMore: {
    type: Boolean,
    default: true
  },
  autoRefresh: {
    type: Boolean,
    default: true
  },
  refreshInterval: {
    type: Number,
    default: 30000 // 30 seconds
  }
})

// Emits
const emit = defineEmits(['create-post', 'post-updated', 'timeline-refreshed'])

// Reactive data
const posts = ref([...props.initialPosts])
const nextCursor = ref(props.initialCursor)
const hasMore = ref(props.initialHasMore)
const isInitialLoading = ref(false)
const isLoadingMore = ref(false)
const isRefreshing = ref(false)
const hasNewPosts = ref(false)
const newPostsCount = ref(0)

// Pull to refresh
const isPullingToRefresh = ref(false)
const pullDistance = ref(0)
const touchStartY = ref(0)
const isAtTop = ref(true)

// Refs
const timelineContainer = ref(null)

// WebSocket connection
let websocketConnection = null
let refreshTimer = null

// Lifecycle
onMounted(() => {
  setupInfiniteScroll()
  if (props.autoRefresh) {
    setupWebSocket()
    setupAutoRefresh()
  }
})

onUnmounted(() => {
  if (websocketConnection) {
    websocketConnection.close()
  }
  if (refreshTimer) {
    clearInterval(refreshTimer)
  }
})

// Methods
const loadTimeline = async (refresh = false) => {
  try {
    if (refresh) {
      isRefreshing.value = true
      posts.value = []
      nextCursor.value = null
      hasMore.value = true
    } else if (!hasMore.value || isLoadingMore.value) {
      return
    }

    if (posts.value.length === 0) {
      isInitialLoading.value = true
    } else {
      isLoadingMore.value = true
    }

    const endpoint = refresh ? '/api/timeline/refresh' : '/api/timeline'
    const params = new URLSearchParams({
      limit: '20'
    })

    if (nextCursor.value && !refresh) {
      params.append('cursor', nextCursor.value)
    }

    const response = await fetch(`${endpoint}?${params}`, {
      headers: {
        'Authorization': `Bearer ${usePage().props.auth.token}`,
        'Accept': 'application/json',
      }
    })

    if (!response.ok) {
      throw new Error('Failed to load timeline')
    }

    const data = await response.json()

    if (refresh) {
      posts.value = data.data.posts
      hasNewPosts.value = false
      newPostsCount.value = 0
    } else {
      posts.value.push(...data.data.posts)
    }

    nextCursor.value = data.data.next_cursor
    hasMore.value = data.data.has_more

    emit('timeline-refreshed', { refresh, postsCount: data.data.posts.length })

  } catch (error) {
    console.error('Error loading timeline:', error)
    // Show error notification
  } finally {
    isInitialLoading.value = false
    isLoadingMore.value = false
    isRefreshing.value = false
    isPullingToRefresh.value = false
  }
}

const loadNewPosts = async () => {
  await loadTimeline(true)
  scrollToTop()
}

const scrollToTop = () => {
  if (timelineContainer.value) {
    timelineContainer.value.scrollTo({
      top: 0,
      behavior: 'smooth'
    })
  }
}

const setupInfiniteScroll = () => {
  // Load initial posts if empty
  if (posts.value.length === 0) {
    loadTimeline()
  }
}

const setupWebSocket = () => {
  // WebSocket setup for real-time updates
  try {
    const wsUrl = `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws`
    websocketConnection = new WebSocket(wsUrl)

    websocketConnection.onmessage = (event) => {
      const data = JSON.parse(event.data)
      
      if (data.type === 'new_post') {
        handleNewPostNotification(data.post)
      } else if (data.type === 'post_updated') {
        handlePostUpdate(data.post)
      } else if (data.type === 'post_deleted') {
        handlePostDeleted(data.postId)
      }
    }

    websocketConnection.onerror = (error) => {
      console.error('WebSocket error:', error)
    }

    websocketConnection.onclose = () => {
      // Attempt to reconnect after 5 seconds
      setTimeout(() => {
        if (props.autoRefresh) {
          setupWebSocket()
        }
      }, 5000)
    }

  } catch (error) {
    console.error('Failed to setup WebSocket:', error)
  }
}

const setupAutoRefresh = () => {
  refreshTimer = setInterval(() => {
    // Only check for new posts, don't auto-refresh
    checkForNewPosts()
  }, props.refreshInterval)
}

const checkForNewPosts = async () => {
  try {
    const response = await fetch('/api/timeline?limit=1', {
      headers: {
        'Authorization': `Bearer ${usePage().props.auth.token}`,
        'Accept': 'application/json',
      }
    })

    if (response.ok) {
      const data = await response.json()
      if (data.data.posts.length > 0) {
        const latestPost = data.data.posts[0]
        const currentLatest = posts.value[0]
        
        if (!currentLatest || latestPost.id !== currentLatest.id) {
          newPostsCount.value++
          hasNewPosts.value = true
        }
      }
    }
  } catch (error) {
    console.error('Error checking for new posts:', error)
  }
}

const handleNewPostNotification = (post) => {
  // Check if this post should appear in user's timeline
  const currentLatest = posts.value[0]
  if (!currentLatest || post.id !== currentLatest.id) {
    newPostsCount.value++
    hasNewPosts.value = true
  }
}

const handlePostUpdate = (updatedPost) => {
  const index = posts.value.findIndex(p => p.id === updatedPost.id)
  if (index !== -1) {
    posts.value[index] = updatedPost
    emit('post-updated', updatedPost)
  }
}

const handlePostDeleted = (postId) => {
  const index = posts.value.findIndex(p => p.id === postId)
  if (index !== -1) {
    posts.value.splice(index, 1)
  }
}

const handleEngagementUpdate = (postId, engagement) => {
  const post = posts.value.find(p => p.id === postId)
  if (post) {
    // Update engagement counts
    if (!post.engagement_counts) {
      post.engagement_counts = {}
    }
    post.engagement_counts[engagement.type] = (post.engagement_counts[engagement.type] || 0) + 1
    
    emit('post-updated', post)
  }
}

const handleScroll = (event) => {
  const container = event.target
  const scrollTop = container.scrollTop
  const scrollHeight = container.scrollHeight
  const clientHeight = container.clientHeight

  // Update isAtTop for pull-to-refresh
  isAtTop.value = scrollTop === 0

  // Infinite scroll - load more when near bottom
  if (scrollHeight - scrollTop - clientHeight < 200 && hasMore.value && !isLoadingMore.value) {
    loadTimeline()
  }
}

// Touch handlers for pull-to-refresh
const handleTouchStart = (event) => {
  if (isAtTop.value) {
    touchStartY.value = event.touches[0].clientY
  }
}

const handleTouchMove = (event) => {
  if (!isAtTop.value || isRefreshing.value) return

  const currentY = event.touches[0].clientY
  const diff = currentY - touchStartY.value

  if (diff > 0) {
    event.preventDefault()
    pullDistance.value = Math.min(diff, 100)
    isPullingToRefresh.value = true
  }
}

const handleTouchEnd = () => {
  if (isPullingToRefresh.value && pullDistance.value > 50) {
    loadTimeline(true)
  }
  
  pullDistance.value = 0
  isPullingToRefresh.value = false
}

// Expose methods for parent components
defineExpose({
  refresh: () => loadTimeline(true),
  loadMore: () => loadTimeline(false),
  scrollToTop
})
</script>

<style scoped>
.timeline-container {
  @apply relative h-full overflow-hidden;
}

.timeline-posts {
  @apply h-full overflow-y-auto px-4 py-6;
}

.pull-to-refresh-indicator {
  @apply absolute top-0 left-0 right-0 z-10 bg-white border-b border-gray-200 p-4 text-center transform transition-transform duration-200;
  transform: translateY(-100%);
}

.pull-to-refresh-indicator.active {
  transform: translateY(0);
}

.refresh-icon {
  @apply inline-block mr-2;
}

.new-posts-notification {
  @apply fixed top-4 left-1/2 transform -translate-x-1/2 z-20 bg-blue-600 text-white px-4 py-2 rounded-full shadow-lg cursor-pointer hover:bg-blue-700 transition-colors duration-200;
}

/* Smooth scrolling */
.timeline-posts {
  scroll-behavior: smooth;
}

/* Custom scrollbar */
.timeline-posts::-webkit-scrollbar {
  width: 6px;
}

.timeline-posts::-webkit-scrollbar-track {
  @apply bg-gray-100;
}

.timeline-posts::-webkit-scrollbar-thumb {
  @apply bg-gray-300 rounded-full;
}

.timeline-posts::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400;
}
</style>