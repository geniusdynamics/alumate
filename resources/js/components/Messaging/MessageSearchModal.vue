<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
      <div class="flex items-center justify-between p-6 border-b">
        <h3 class="text-lg font-semibold text-gray-900">Search Messages</h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="p-6">
        <!-- Search Input -->
        <div class="mb-6">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search messages..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="performSearch"
            >
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Search Filters -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="dateRange" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
            <select
              id="dateRange"
              v-model="filters.dateRange"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @change="performSearch"
            >
              <option value="">All time</option>
              <option value="today">Today</option>
              <option value="week">This week</option>
              <option value="month">This month</option>
              <option value="year">This year</option>
            </select>
          </div>
          <div>
            <label for="messageType" class="block text-sm font-medium text-gray-700 mb-1">Message Type</label>
            <select
              id="messageType"
              v-model="filters.messageType"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @change="performSearch"
            >
              <option value="">All types</option>
              <option value="text">Text</option>
              <option value="file">Files</option>
              <option value="image">Images</option>
              <option value="link">Links</option>
            </select>
          </div>
          <div>
            <label for="sender" class="block text-sm font-medium text-gray-700 mb-1">Sender</label>
            <select
              id="sender"
              v-model="filters.sender"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @change="performSearch"
            >
              <option value="">All senders</option>
              <option v-for="participant in participants" :key="participant.id" :value="participant.id">
                {{ participant.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Search Results -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="isSearching" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>

          <div v-else-if="searchResults.length === 0 && searchQuery" class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.47-.881-6.08-2.33"></path>
            </svg>
            <p>No messages found matching your search.</p>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="result in searchResults"
              :key="result.id"
              class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
              @click="selectMessage(result)"
            >
              <div class="flex items-start justify-between mb-2">
                <div class="flex items-center">
                  <img
                    :src="result.sender.avatar || '/default-avatar.png'"
                    :alt="result.sender.name"
                    class="w-8 h-8 rounded-full mr-3"
                  >
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ result.sender.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(result.created_at) }}</p>
                  </div>
                </div>
                <div v-if="result.type !== 'text'" class="flex items-center text-xs text-gray-500">
                  <svg v-if="result.type === 'file'" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <svg v-else-if="result.type === 'image'" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  <span>{{ result.type }}</span>
                </div>
              </div>
              <div class="text-sm text-gray-700">
                <p v-html="highlightSearchTerm(result.content)"></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end space-x-3 p-6 border-t bg-gray-50">
        <button
          @click="close"
          class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

interface Message {
  id: number
  content: string
  type: 'text' | 'file' | 'image' | 'link'
  sender: {
    id: number
    name: string
    avatar?: string
  }
  created_at: string
}

interface Participant {
  id: number
  name: string
  avatar?: string
}

interface Props {
  isOpen: boolean
  conversationId: number
  participants: Participant[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  selectMessage: [message: Message]
}>()

const searchQuery = ref('')
const searchResults = ref<Message[]>([])
const isSearching = ref(false)

const filters = reactive({
  dateRange: '',
  messageType: '',
  sender: ''
})

const close = () => {
  emit('close')
  resetSearch()
}

const resetSearch = () => {
  searchQuery.value = ''
  searchResults.value = []
  filters.dateRange = ''
  filters.messageType = ''
  filters.sender = ''
}

const performSearch = async () => {
  if (!searchQuery.value.trim() && !filters.dateRange && !filters.messageType && !filters.sender) {
    searchResults.value = []
    return
  }

  isSearching.value = true

  try {
    const response = await fetch('/api/messages/search', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        conversation_id: props.conversationId,
        query: searchQuery.value,
        filters: filters
      })
    })

    const data = await response.json()
    
    if (data.success) {
      searchResults.value = data.data
    }
  } catch (error) {
    console.error('Error searching messages:', error)
  } finally {
    isSearching.value = false
  }
}

const selectMessage = (message: Message) => {
  emit('selectMessage', message)
  close()
}

const highlightSearchTerm = (content: string): string => {
  if (!searchQuery.value.trim()) return content

  const regex = new RegExp(`(${searchQuery.value})`, 'gi')
  return content.replace(regex, '<mark class="bg-yellow-200">$1</mark>')
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)

  if (diffInHours < 24) {
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    })
  } else if (diffInHours < 24 * 7) {
    return date.toLocaleDateString('en-US', {
      weekday: 'short',
      hour: '2-digit',
      minute: '2-digit'
    })
  } else {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}
</script>