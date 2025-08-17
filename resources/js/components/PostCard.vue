<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
    <!-- Post header -->
    <div class="flex items-center space-x-3 mb-4">
      <!-- Avatar -->
      <img
        :src="post.user.avatar_url || '/default-avatar.png'"
        :alt="post.user.name"
        class="w-10 h-10 rounded-full"
      >
      
      <!-- User info -->
      <div class="flex-1">
        <div class="flex items-center space-x-2">
          <h3 class="font-medium text-gray-900">{{ post.user.name }}</h3>
          <span v-if="post.user.verified" class="text-blue-500">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
          </span>
        </div>
        <div class="flex items-center space-x-2 text-sm text-gray-500">
          <span>{{ post.user.title || 'Alumni' }}</span>
          <span>•</span>
          <time :datetime="post.created_at">{{ formatDate(post.created_at) }}</time>
          <span v-if="post.visibility !== 'public'" class="flex items-center space-x-1">
            <span>•</span>
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
            </svg>
            <span class="capitalize">{{ post.visibility }}</span>
          </span>
        </div>
      </div>
      
      <!-- Menu button -->
      <div class="relative">
        <button
          @click="toggleMenu"
          class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
          </svg>
        </button>
        
        <!-- Dropdown menu -->
        <div v-if="showMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
          <div class="py-1">
            <button
              v-if="canEdit"
              @click="editPost"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              Edit Post
            </button>
            <button
              v-if="canDelete"
              @click="deletePost"
              class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
            >
              Delete Post
            </button>
            <button
              @click="reportPost"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              Report Post
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Post content -->
    <div class="mb-4">
      <div class="prose prose-sm max-w-none" v-html="post.content"></div>
    </div>

    <!-- Media attachments -->
    <div v-if="post.media && post.media.length" class="mb-4">
      <div class="grid gap-2" :class="mediaGridClass">
        <div
          v-for="(media, index) in post.media"
          :key="media.id"
          class="relative rounded-lg overflow-hidden bg-gray-100"
          :class="getMediaItemClass(index)"
        >
          <img
            v-if="media.type === 'image'"
            :src="media.urls.medium || media.urls.original"
            :alt="media.original_name"
            class="w-full h-full object-cover cursor-pointer"
            @click="openMediaViewer(index)"
          >
          <video
            v-else-if="media.type === 'video'"
            :src="media.urls.original"
            class="w-full h-full object-cover"
            controls
            preload="metadata"
          >
            Your browser does not support the video tag.
          </video>
          <div
            v-else
            class="w-full h-full flex items-center justify-center bg-gray-200 p-4"
          >
            <div class="text-center">
              <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
              </svg>
              <p class="text-xs text-gray-600 truncate">{{ media.original_name }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Engagement bar -->
    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
      <!-- Reaction buttons -->
      <div class="flex items-center space-x-4">
        <button
          @click="toggleLike"
          class="flex items-center space-x-1 text-sm transition-colors"
          :class="post.user_has_liked ? 'text-red-500' : 'text-gray-500 hover:text-red-500'"
        >
          <svg class="w-5 h-5" :fill="post.user_has_liked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
          <span>{{ post.likes_count || 0 }}</span>
        </button>
        
        <button
          @click="toggleComments"
          class="flex items-center space-x-1 text-sm text-gray-500 hover:text-blue-500 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <span>{{ post.comments_count || 0 }}</span>
        </button>
        
        <button
          @click="sharePost"
          class="flex items-center space-x-1 text-sm text-gray-500 hover:text-green-500 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
          </svg>
          <span>{{ post.shares_count || 0 }}</span>
        </button>
      </div>
      
      <!-- Bookmark button -->
      <button
        @click="toggleBookmark"
        class="text-gray-500 hover:text-yellow-500 transition-colors"
        :class="post.user_has_bookmarked ? 'text-yellow-500' : ''"
      >
        <svg class="w-5 h-5" :fill="post.user_has_bookmarked ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
  post: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['engagement-updated', 'post-deleted'])

// Reactive data
const showMenu = ref(false)
const showComments = ref(false)

// Get current user
const { props: pageProps } = usePage()
const currentUser = computed(() => pageProps.auth?.user)

// Computed properties
const canEdit = computed(() => {
  return currentUser.value && currentUser.value.id === props.post.user.id
})

const canDelete = computed(() => {
  return currentUser.value && (
    currentUser.value.id === props.post.user.id ||
    currentUser.value.role === 'admin'
  )
})

const mediaGridClass = computed(() => {
  const count = props.post.media?.length || 0
  if (count === 1) return 'grid-cols-1'
  if (count === 2) return 'grid-cols-2'
  if (count === 3) return 'grid-cols-3'
  return 'grid-cols-2'
})

// Methods
const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)
  
  if (diffInSeconds < 60) return 'Just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
  
  return date.toLocaleDateString()
}

const getMediaItemClass = (index) => {
  const count = props.post.media?.length || 0
  if (count === 1) return 'aspect-video'
  if (count === 2) return 'aspect-square'
  if (count === 3 && index === 0) return 'aspect-square row-span-2'
  return 'aspect-square'
}

const toggleMenu = () => {
  showMenu.value = !showMenu.value
}

const toggleLike = async () => {
  try {
    const response = await fetch(`/api/posts/${props.post.id}/like`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      emit('engagement-updated', {
        postId: props.post.id,
        type: 'like',
        data: data
      })
    }
  } catch (error) {
    console.error('Error toggling like:', error)
  }
}

const toggleComments = () => {
  showComments.value = !showComments.value
}

const sharePost = async () => {
  try {
    if (navigator.share) {
      await navigator.share({
        title: `Post by ${props.post.user.name}`,
        text: props.post.content.substring(0, 100),
        url: window.location.href
      })
    } else {
      // Fallback: copy to clipboard
      await navigator.clipboard.writeText(window.location.href)
      // You might want to show a toast notification here
    }
    
    // Track share
    await fetch(`/api/posts/${props.post.id}/share`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
  } catch (error) {
    console.error('Error sharing post:', error)
  }
}

const toggleBookmark = async () => {
  try {
    const response = await fetch(`/api/posts/${props.post.id}/bookmark`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      emit('engagement-updated', {
        postId: props.post.id,
        type: 'bookmark',
        data: data
      })
    }
  } catch (error) {
    console.error('Error toggling bookmark:', error)
  }
}

const editPost = () => {
  showMenu.value = false
  // Emit edit event or navigate to edit page
  emit('post-edit', props.post)
}

const deletePost = async () => {
  if (!confirm('Are you sure you want to delete this post?')) {
    return
  }
  
  try {
    const response = await fetch(`/api/posts/${props.post.id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
    
    if (response.ok) {
      emit('post-deleted', props.post.id)
    }
  } catch (error) {
    console.error('Error deleting post:', error)
  }
  
  showMenu.value = false
}

const reportPost = () => {
  showMenu.value = false
  // Implement report functionality
  console.log('Report post:', props.post.id)
}

const openMediaViewer = (index) => {
  // Implement media viewer/lightbox
  console.log('Open media viewer for index:', index)
}

// Close menu when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    showMenu.value = false
  }
}

// Add event listener for clicking outside
if (typeof window !== 'undefined') {
  document.addEventListener('click', handleClickOutside)
}
</script>

<style scoped>
.prose {
  color: inherit;
}

.prose p {
  margin-bottom: 0.75rem;
}

.prose p:last-child {
  margin-bottom: 0;
}

.prose a {
  color: #3b82f6;
  text-decoration: none;
}

.prose a:hover {
  text-decoration: underline;
}

.prose strong {
  font-weight: 600;
}

.prose em {
  font-style: italic;
}
</style>