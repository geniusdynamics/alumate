<template>
  <div class="reunion-memory-wall">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Memory Wall</h3>
      
      <div class="flex items-center space-x-3">
        <!-- Type filter -->
        <select
          v-model="selectedType"
          class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Types</option>
          <option value="story">Stories</option>
          <option value="achievement">Achievements</option>
          <option value="memory">Memories</option>
          <option value="tribute">Tributes</option>
          <option value="update">Updates</option>
        </select>
        
        <!-- Add memory button -->
        <button
          v-if="canAddMemory"
          @click="showMemoryModal = true"
          class="px-4 py-2 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500"
        >
          Share Memory
        </button>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
      <p class="mt-2 text-gray-600">Loading memories...</p>
    </div>

    <!-- Memories list -->
    <div v-else-if="memories.length > 0" class="space-y-6">
      <div
        v-for="memory in memories"
        :key="memory.id"
        class="bg-white rounded-lg shadow-md overflow-hidden"
      >
        <!-- Memory header -->
        <div class="p-6 pb-4">
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center">
              <img
                :src="memory.submitter.avatar_url || '/default-avatar.png'"
                :alt="memory.submitter.name"
                class="w-10 h-10 rounded-full mr-3"
              />
              <div>
                <h4 class="font-semibold text-gray-900">{{ memory.submitter.name }}</h4>
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                  <span>{{ formatDate(memory.created_at) }}</span>
                  <span>•</span>
                  <span class="capitalize">{{ memory.type }}</span>
                </div>
              </div>
            </div>
            
            <!-- Featured badge -->
            <div v-if="memory.is_featured" class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
              Featured
            </div>
          </div>

          <!-- Memory title -->
          <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ memory.title }}</h3>

          <!-- Memory content -->
          <div class="prose prose-sm max-w-none text-gray-700 mb-4">
            <p>{{ memory.content }}</p>
          </div>

          <!-- Media attachments -->
          <div v-if="memory.media_urls && memory.media_urls.length > 0" class="mb-4">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              <img
                v-for="(url, index) in memory.media_urls.slice(0, 6)"
                :key="index"
                :src="url"
                :alt="`Memory media ${index + 1}`"
                class="w-full h-24 object-cover rounded-md cursor-pointer hover:opacity-90"
                @click="openMediaLightbox(url, memory.media_urls)"
              />
            </div>
            <p v-if="memory.media_urls.length > 6" class="text-sm text-gray-500 mt-2">
              +{{ memory.media_urls.length - 6 }} more photos
            </p>
          </div>

          <!-- Tagged users -->
          <div v-if="memory.tagged_users && memory.tagged_users.length > 0" class="mb-4">
            <div class="flex items-center flex-wrap gap-2">
              <span class="text-sm text-gray-600">Tagged:</span>
              <span
                v-for="user in getTaggedUsers(memory.tagged_users)"
                :key="user.id"
                class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"
              >
                {{ user.name }}
              </span>
            </div>
          </div>

          <!-- Memory date -->
          <div v-if="memory.memory_date" class="text-sm text-gray-500 mb-4">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10a2 2 0 002 2h4a2 2 0 002-2V11m-6 0h8m-8 0V7a2 2 0 012-2h4a2 2 0 012 2v4" />
            </svg>
            Memory from {{ formatDate(memory.memory_date) }}
          </div>
        </div>

        <!-- Memory actions -->
        <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-between">
          <div class="flex items-center space-x-6">
            <!-- Like button -->
            <button
              @click="handleLike(memory)"
              :class="[
                'flex items-center space-x-1 text-sm',
                memory.is_liked_by_user ? 'text-red-600' : 'text-gray-600 hover:text-red-600'
              ]"
            >
              <svg class="w-4 h-4" :fill="memory.is_liked_by_user ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
              <span>{{ memory.likes_count }}</span>
            </button>

            <!-- Comment button -->
            <button
              @click="toggleComments(memory)"
              class="flex items-center space-x-1 text-sm text-gray-600 hover:text-blue-600"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <span>{{ memory.comments_count }}</span>
            </button>
          </div>

          <!-- Memory type icon -->
          <div class="flex items-center text-sm text-gray-500">
            <component :is="getTypeIcon(memory.type)" class="w-4 h-4 mr-1" />
            <span class="capitalize">{{ memory.type }}</span>
          </div>
        </div>

        <!-- Comments section -->
        <div v-if="showComments[memory.id]" class="border-t bg-gray-50">
          <div class="p-4">
            <!-- Comment form -->
            <div class="mb-4">
              <div class="flex space-x-3">
                <img
                  :src="currentUser?.avatar_url || '/default-avatar.png'"
                  :alt="currentUser?.name || 'You'"
                  class="w-8 h-8 rounded-full"
                />
                <div class="flex-1">
                  <textarea
                    v-model="commentText[memory.id]"
                    placeholder="Share your thoughts..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                    rows="2"
                  ></textarea>
                  <div class="mt-2 flex justify-end">
                    <button
                      @click="handleComment(memory)"
                      :disabled="!commentText[memory.id]?.trim()"
                      class="px-3 py-1 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Comment
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Comments list would go here -->
            <div class="text-sm text-gray-500">
              Comments will be loaded here...
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <div class="text-gray-400 mb-4">
        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No memories shared yet</h3>
      <p class="text-gray-600 mb-4">Be the first to share a memory from your time together!</p>
      <button
        v-if="canAddMemory"
        @click="showMemoryModal = true"
        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
      >
        Share First Memory
      </button>
    </div>

    <!-- Memory creation modal -->
    <MemoryModal
      v-if="showMemoryModal"
      :event-id="eventId"
      @close="showMemoryModal = false"
      @created="handleMemoryCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, h } from 'vue'
import MemoryModal from './MemoryModal.vue'

interface Memory {
  id: number
  title: string
  content: string
  type: string
  media_urls: string[]
  tagged_users: number[]
  submitter: {
    id: number
    name: string
    avatar_url: string
  }
  likes_count: number
  comments_count: number
  is_featured: boolean
  is_liked_by_user: boolean
  memory_date: string
  created_at: string
}

interface Props {
  eventId: number
  canAddMemory?: boolean
  currentUser?: {
    id: number
    name: string
    avatar_url: string
  }
}

const props = withDefaults(defineProps<Props>(), {
  canAddMemory: false
})

const memories = ref<Memory[]>([])
const loading = ref(false)
const selectedType = ref('')
const showMemoryModal = ref(false)
const showComments = reactive<Record<number, boolean>>({})
const commentText = reactive<Record<number, string>>({})

const loadMemories = async () => {
  loading.value = true
  
  try {
    const params = new URLSearchParams()
    if (selectedType.value) {
      params.append('type', selectedType.value)
    }
    
    const response = await fetch(`/api/reunions/${props.eventId}/memories?${params}`)
    const data = await response.json()
    memories.value = data
  } catch (error) {
    console.error('Error loading memories:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getTaggedUsers = (userIds: number[]) => {
  // In a real implementation, you would fetch user data
  return userIds.map(id => ({ id, name: `User ${id}` }))
}

const getTypeIcon = (type: string) => {
  const icons = {
    story: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' })
    ]),
    achievement: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z' })
    ]),
    memory: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' })
    ]),
    tribute: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' })
    ]),
    update: () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ])
  }
  
  return icons[type as keyof typeof icons] || icons.memory
}

const toggleComments = (memory: Memory) => {
  showComments[memory.id] = !showComments[memory.id]
}

const handleLike = async (memory: Memory) => {
  try {
    const method = memory.is_liked_by_user ? 'DELETE' : 'POST'
    const response = await fetch(`/api/reunion-memories/${memory.id}/like`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      
      // Update the memory in the list
      const memoryIndex = memories.value.findIndex(m => m.id === memory.id)
      if (memoryIndex !== -1) {
        memories.value[memoryIndex].likes_count = data.likes_count
        memories.value[memoryIndex].is_liked_by_user = !memory.is_liked_by_user
      }
    }
  } catch (error) {
    console.error('Error liking memory:', error)
  }
}

const handleComment = async (memory: Memory) => {
  const comment = commentText[memory.id]?.trim()
  if (!comment) return
  
  try {
    const response = await fetch(`/api/reunion-memories/${memory.id}/comments`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ comment })
    })
    
    if (response.ok) {
      // Clear comment text and increment count
      commentText[memory.id] = ''
      const memoryIndex = memories.value.findIndex(m => m.id === memory.id)
      if (memoryIndex !== -1) {
        memories.value[memoryIndex].comments_count++
      }
    }
  } catch (error) {
    console.error('Error commenting on memory:', error)
  }
}

const handleMemoryCreated = (newMemory: Memory) => {
  memories.value.unshift(newMemory)
  showMemoryModal.value = false
}

const openMediaLightbox = (url: string, allUrls: string[]) => {
  // Implementation for media lightbox
  console.log('Open media lightbox:', url, allUrls)
}

watch(selectedType, () => {
  loadMemories()
})

onMounted(() => {
  loadMemories()
})
</script>