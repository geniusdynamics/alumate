<template>
  <div class="messaging-app">
    <Head title="Messages" />
    
    <div class="messaging-layout">
      <!-- Sidebar -->
      <div class="messaging-sidebar">
        <ConversationList
          :selected-conversation-id="selectedConversation?.id"
          @conversation-selected="selectConversation"
        />
      </div>

      <!-- Main Chat Area -->
      <div class="messaging-main">
        <ChatInterface :conversation="selectedConversation" />
      </div>
    </div>

    <!-- Mobile overlay for conversation list -->
    <div
      v-if="showMobileSidebar"
      class="mobile-sidebar-overlay"
      @click="showMobileSidebar = false"
    >
      <div class="mobile-sidebar" @click.stop>
        <div class="mobile-sidebar-header">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Messages</h2>
          <button
            @click="showMobileSidebar = false"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <ConversationList
          :selected-conversation-id="selectedConversation?.id"
          @conversation-selected="selectConversationMobile"
        />
      </div>
    </div>

    <!-- Mobile header -->
    <div class="mobile-header">
      <button
        @click="showMobileSidebar = true"
        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ selectedConversation ? getConversationTitle(selectedConversation) : 'Messages' }}
      </h1>
      <div class="w-10"></div> <!-- Spacer for centering -->
    </div>

    <!-- Unread count badge for mobile -->
    <div
      v-if="unreadCount > 0"
      class="mobile-unread-badge"
      @click="showMobileSidebar = true"
    >
      {{ unreadCount > 99 ? '99+' : unreadCount }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useMessagingStore } from '@/stores/messaging'
import { useAuthStore } from '@/stores/auth'
import ConversationList from '@/Components/Messaging/ConversationList.vue'
import ChatInterface from '@/Components/Messaging/ChatInterface.vue'

const messagingStore = useMessagingStore()
const authStore = useAuthStore()

const selectedConversation = ref(null)
const showMobileSidebar = ref(false)

const unreadCount = computed(() => messagingStore.unreadCount)
const currentUser = computed(() => authStore.user)

const getConversationTitle = (conversation) => {
  if (conversation.title) {
    return conversation.title
  }

  if (conversation.type === 'direct') {
    const otherParticipant = conversation.participants?.find(p => p.id !== currentUser.value?.id)
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

const selectConversation = (conversation) => {
  selectedConversation.value = conversation
}

const selectConversationMobile = (conversation) => {
  selectedConversation.value = conversation
  showMobileSidebar.value = false
}

// Handle browser back/forward navigation
const handlePopState = (event) => {
  if (event.state?.conversationId) {
    const conversation = messagingStore.conversations.find(c => c.id === event.state.conversationId)
    if (conversation) {
      selectedConversation.value = conversation
    }
  } else {
    selectedConversation.value = null
  }
}

// Update URL when conversation changes
const updateUrl = (conversation) => {
  const url = conversation 
    ? `/messages/${conversation.id}`
    : '/messages'
  
  const state = conversation 
    ? { conversationId: conversation.id }
    : null

  window.history.pushState(state, '', url)
}

// Watch for conversation selection changes
const selectConversationWithHistory = (conversation) => {
  selectedConversation.value = conversation
  updateUrl(conversation)
}

onMounted(async () => {
  // Initialize messaging store
  await messagingStore.initialize()

  // Set up browser navigation
  window.addEventListener('popstate', handlePopState)

  // Check if there's a conversation ID in the URL
  const pathParts = window.location.pathname.split('/')
  if (pathParts[1] === 'messages' && pathParts[2]) {
    const conversationId = parseInt(pathParts[2])
    const conversation = messagingStore.conversations.find(c => c.id === conversationId)
    if (conversation) {
      selectedConversation.value = conversation
    }
  }
})

onUnmounted(() => {
  window.removeEventListener('popstate', handlePopState)
})

// Handle keyboard shortcuts
const handleKeyDown = (event) => {
  // Escape key to close mobile sidebar
  if (event.key === 'Escape' && showMobileSidebar.value) {
    showMobileSidebar.value = false
  }
  
  // Ctrl/Cmd + K to open search
  if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
    event.preventDefault()
    // Open search modal
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyDown)
})
</script>

<style scoped>
.messaging-app {
  @apply h-screen bg-white dark:bg-gray-900;
}

.messaging-layout {
  @apply h-full flex;
}

.messaging-sidebar {
  @apply w-80 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hidden lg:block;
}

.messaging-main {
  @apply flex-1 flex flex-col min-w-0;
}

.mobile-sidebar-overlay {
  @apply fixed inset-0 z-50 bg-black bg-opacity-50 lg:hidden;
}

.mobile-sidebar {
  @apply w-80 h-full bg-white dark:bg-gray-800 shadow-xl;
}

.mobile-sidebar-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.mobile-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 lg:hidden;
}

.mobile-unread-badge {
  @apply fixed bottom-4 right-4 w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold shadow-lg cursor-pointer lg:hidden z-40;
}

/* Responsive adjustments */
@media (max-width: 1023px) {
  .messaging-layout {
    @apply flex-col;
  }
  
  .messaging-sidebar {
    @apply hidden;
  }
  
  .messaging-main {
    @apply flex-1;
  }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .messaging-app {
    @apply bg-gray-900;
  }
}

/* Animation for mobile sidebar */
.mobile-sidebar {
  animation: slideInLeft 0.3s ease-out;
}

@keyframes slideInLeft {
  from {
    transform: translateX(-100%);
  }
  to {
    transform: translateX(0);
  }
}

/* Scrollbar styling */
.messaging-sidebar::-webkit-scrollbar,
.messaging-main::-webkit-scrollbar {
  @apply w-2;
}

.messaging-sidebar::-webkit-scrollbar-track,
.messaging-main::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-800;
}

.messaging-sidebar::-webkit-scrollbar-thumb,
.messaging-main::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-600 rounded-full;
}

.messaging-sidebar::-webkit-scrollbar-thumb:hover,
.messaging-main::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400 dark:bg-gray-500;
}
</style>