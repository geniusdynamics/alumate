<template>
  <div class="conversation-list">
    <div class="conversation-list-header">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Messages</h2>
      <button
        @click="showNewConversationModal = true"
        class="btn btn-primary btn-sm"
        title="Start new conversation"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <div class="conversation-search mb-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search conversations..."
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      />
    </div>

    <div class="conversation-filters mb-4">
      <div class="flex space-x-2">
        <button
          v-for="filter in filters"
          :key="filter.key"
          @click="activeFilter = filter.key"
          :class="[
            'px-3 py-1 text-sm rounded-full transition-colors',
            activeFilter === filter.key
              ? 'bg-blue-500 text-white'
              : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
          ]"
        >
          {{ filter.label }}
          <span v-if="filter.count > 0" class="ml-1 text-xs">{{ filter.count }}</span>
        </button>
      </div>
    </div>

    <div class="conversation-items">
      <div
        v-for="conversation in filteredConversations"
        :key="conversation.id"
        @click="selectConversation(conversation)"
        :class="[
          'conversation-item p-3 border-b border-gray-200 cursor-pointer transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800',
          selectedConversationId === conversation.id ? 'bg-blue-50 dark:bg-blue-900/20' : ''
        ]"
      >
        <div class="flex items-start space-x-3">
          <div class="flex-shrink-0">
            <div v-if="conversation.type === 'direct'" class="relative">
              <img
                :src="getOtherParticipant(conversation)?.avatar_url || '/default-avatar.png'"
                :alt="getOtherParticipant(conversation)?.name"
                class="w-10 h-10 rounded-full"
              />
              <div
                v-if="isUserOnline(getOtherParticipant(conversation)?.id)"
                class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"
              ></div>
            </div>
            <div v-else class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center dark:bg-gray-600">
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
              </svg>
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ getConversationTitle(conversation) }}
              </h3>
              <div class="flex items-center space-x-2">
                <span v-if="conversation.is_pinned" class="text-yellow-500" title="Pinned">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                  </svg>
                </span>
                <span v-if="conversation.is_muted" class="text-gray-400" title="Muted">
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.793L4.617 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.617l3.766-3.793a1 1 0 011.617.793zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.983 5.983 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.984 3.984 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd" />
                  </svg>
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatTime(conversation.last_message_at) }}
                </span>
              </div>
            </div>

            <div class="flex items-center justify-between mt-1">
              <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                <span v-if="conversation.latest_message?.user?.id === currentUser.id" class="text-gray-500">
                  You: 
                </span>
                {{ conversation.latest_message?.content || 'No messages yet' }}
              </p>
              <div v-if="conversation.unread_count > 0" class="flex-shrink-0">
                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">
                  {{ conversation.unread_count > 99 ? '99+' : conversation.unread_count }}
                </span>
              </div>
            </div>

            <div v-if="conversation.type !== 'direct'" class="mt-1">
              <div class="flex -space-x-1">
                <img
                  v-for="participant in conversation.participants.slice(0, 3)"
                  :key="participant.id"
                  :src="participant.avatar_url || '/default-avatar.png'"
                  :alt="participant.name"
                  class="w-5 h-5 rounded-full border border-white dark:border-gray-800"
                  :title="participant.name"
                />
                <span v-if="conversation.participants.length > 3" class="text-xs text-gray-500 ml-2">
                  +{{ conversation.participants.length - 3 }} more
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredConversations.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.959 8.959 0 01-4.906-1.456L3 21l2.544-5.094A8.959 8.959 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
        </svg>
        <p>No conversations found</p>
        <button
          @click="showNewConversationModal = true"
          class="mt-2 text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Start your first conversation
        </button>
      </div>
    </div>

    <!-- Load more button -->
    <div v-if="hasMoreConversations" class="p-4 text-center">
      <button
        @click="loadMoreConversations"
        :disabled="loadingMore"
        class="btn btn-secondary btn-sm"
      >
        <span v-if="loadingMore">Loading...</span>
        <span v-else>Load More</span>
      </button>
    </div>

    <!-- New Conversation Modal -->
    <NewConversationModal
      v-if="showNewConversationModal"
      @close="showNewConversationModal = false"
      @conversation-created="onConversationCreated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useMessagingStore } from '@/stores/messaging'
import { useAuthStore } from '@/stores/auth'
import { formatDistanceToNow } from 'date-fns'
import NewConversationModal from './NewConversationModal.vue'

const props = defineProps({
  selectedConversationId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['conversation-selected'])

const messagingStore = useMessagingStore()
const authStore = useAuthStore()

const searchQuery = ref('')
const activeFilter = ref('all')
const showNewConversationModal = ref(false)
const loadingMore = ref(false)

const currentUser = computed(() => authStore.user)

const filters = computed(() => [
  { key: 'all', label: 'All', count: messagingStore.conversations.length },
  { key: 'unread', label: 'Unread', count: messagingStore.unreadConversations.length },
  { key: 'pinned', label: 'Pinned', count: messagingStore.pinnedConversations.length },
  { key: 'direct', label: 'Direct', count: messagingStore.directConversations.length },
  { key: 'groups', label: 'Groups', count: messagingStore.groupConversations.length }
])

const filteredConversations = computed(() => {
  let conversations = messagingStore.conversations

  // Apply filter
  switch (activeFilter.value) {
    case 'unread':
      conversations = conversations.filter(c => c.unread_count > 0)
      break
    case 'pinned':
      conversations = conversations.filter(c => c.is_pinned)
      break
    case 'direct':
      conversations = conversations.filter(c => c.type === 'direct')
      break
    case 'groups':
      conversations = conversations.filter(c => c.type === 'group' || c.type === 'circle')
      break
  }

  // Apply search
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase()
    conversations = conversations.filter(conversation => {
      const title = getConversationTitle(conversation).toLowerCase()
      const lastMessage = conversation.latest_message?.content?.toLowerCase() || ''
      return title.includes(query) || lastMessage.includes(query)
    })
  }

  return conversations
})

const hasMoreConversations = computed(() => messagingStore.hasMoreConversations)

const getConversationTitle = (conversation) => {
  if (conversation.title) {
    return conversation.title
  }

  if (conversation.type === 'direct') {
    const otherParticipant = getOtherParticipant(conversation)
    return otherParticipant?.name || 'Unknown User'
  }

  if (conversation.circle) {
    return conversation.circle.name
  }

  if (conversation.group) {
    return conversation.group.name
  }

  return `${conversation.type} conversation`
}

const getOtherParticipant = (conversation) => {
  if (conversation.type !== 'direct') return null
  return conversation.participants?.find(p => p.id !== currentUser.value?.id)
}

const isUserOnline = (userId) => {
  // This would be connected to your presence system
  return messagingStore.onlineUsers.includes(userId)
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const selectConversation = (conversation) => {
  emit('conversation-selected', conversation)
}

const loadMoreConversations = async () => {
  loadingMore.value = true
  try {
    await messagingStore.loadMoreConversations()
  } finally {
    loadingMore.value = false
  }
}

const onConversationCreated = (conversation) => {
  showNewConversationModal.value = false
  emit('conversation-selected', conversation)
}

onMounted(() => {
  messagingStore.loadConversations()
})

// Watch for real-time updates
watch(() => messagingStore.conversations, (newConversations) => {
  // Handle real-time conversation updates
}, { deep: true })
</script>

<style scoped>
.conversation-list {
  @apply h-full flex flex-col;
}

.conversation-list-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.conversation-search {
  @apply px-4;
}

.conversation-filters {
  @apply px-4;
}

.conversation-items {
  @apply flex-1 overflow-y-auto;
}

.conversation-item {
  @apply relative;
}

.conversation-item:hover {
  @apply bg-gray-50 dark:bg-gray-800;
}

.conversation-item.selected {
  @apply bg-blue-50 border-r-2 border-blue-500 dark:bg-blue-900/20;
}

.btn {
  @apply inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700;
}

.btn-sm {
  @apply px-3 py-1.5 text-xs;
}
</style>