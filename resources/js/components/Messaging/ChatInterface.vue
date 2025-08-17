<template>
  <div class="chat-interface">
    <div v-if="!conversation" class="no-conversation">
      <div class="text-center py-12">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.959 8.959 0 01-4.906-1.456L3 21l2.544-5.094A8.959 8.959 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Select a conversation</h3>
        <p class="text-gray-500 dark:text-gray-400">Choose a conversation from the sidebar to start messaging</p>
      </div>
    </div>

    <div v-else class="conversation-view">
      <!-- Chat Header -->
      <div class="chat-header">
        <div class="flex items-center space-x-3">
          <div class="flex-shrink-0">
            <div v-if="conversation.type === 'direct'" class="relative">
              <img
                :src="otherParticipant?.avatar_url || '/default-avatar.png'"
                :alt="otherParticipant?.name"
                class="w-10 h-10 rounded-full"
              />
              <div
                v-if="isUserOnline(otherParticipant?.id)"
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
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
              {{ conversationTitle }}
            </h2>
            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
              <span v-if="conversation.type === 'direct' && isUserOnline(otherParticipant?.id)">
                Online
              </span>
              <span v-else-if="conversation.type === 'direct' && otherParticipant?.last_seen">
                Last seen {{ formatTime(otherParticipant.last_seen) }}
              </span>
              <span v-else-if="conversation.type !== 'direct'">
                {{ conversation.participants?.length || 0 }} members
              </span>
              <span v-if="typingUsers.length > 0" class="text-blue-500">
                {{ getTypingText() }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Search Messages -->
          <button
            @click="showSearchModal = true"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            title="Search messages"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>

          <!-- Conversation Menu -->
          <div class="relative" ref="menuRef">
            <button
              @click="showMenu = !showMenu"
              class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
              </svg>
            </button>

            <div v-if="showMenu" class="conversation-menu">
              <button @click="togglePin" class="menu-item">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                </svg>
                {{ conversation.is_pinned ? 'Unpin' : 'Pin' }} conversation
              </button>
              <button @click="toggleMute" class="menu-item">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.793L4.617 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.617l3.766-3.793a1 1 0 011.617.793z" clip-rule="evenodd" />
                </svg>
                {{ conversation.is_muted ? 'Unmute' : 'Mute' }} conversation
              </button>
              <button v-if="conversation.type !== 'direct'" @click="showParticipantsModal = true" class="menu-item">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                </svg>
                Manage participants
              </button>
              <button @click="archiveConversation" class="menu-item text-red-600 dark:text-red-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l4 4 4-4" />
                </svg>
                Archive conversation
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Messages Area -->
      <div class="messages-area" ref="messagesContainer">
        <div v-if="loadingMessages" class="loading-messages">
          <div class="text-center py-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            <p class="text-sm text-gray-500 mt-2">Loading messages...</p>
          </div>
        </div>

        <div v-else-if="messages.length === 0" class="empty-messages">
          <div class="text-center py-8">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.959 8.959 0 01-4.906-1.456L3 21l2.544-5.094A8.959 8.959 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No messages yet</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Start the conversation!</p>
          </div>
        </div>

        <div v-else class="messages-list">
          <!-- Load more messages button -->
          <div v-if="hasMoreMessages" class="text-center py-4">
            <button
              @click="loadMoreMessages"
              :disabled="loadingMoreMessages"
              class="btn btn-secondary btn-sm"
            >
              <span v-if="loadingMoreMessages">Loading...</span>
              <span v-else>Load older messages</span>
            </button>
          </div>

          <!-- Messages -->
          <div
            v-for="(message, index) in messages"
            :key="message.id"
            :class="[
              'message-item',
              message.user.id === currentUser.id ? 'message-own' : 'message-other',
              shouldShowAvatar(message, index) ? 'message-with-avatar' : 'message-no-avatar'
            ]"
          >
            <MessageBubble
              :message="message"
              :show-avatar="shouldShowAvatar(message, index)"
              :show-timestamp="shouldShowTimestamp(message, index)"
              :is-own="message.user.id === currentUser.id"
              @reply="replyToMessage"
              @edit="editMessage"
              @delete="deleteMessage"
            />
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <div class="message-input-area">
        <div v-if="replyingTo" class="reply-preview">
          <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 border-l-4 border-blue-500">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                Replying to {{ replyingTo.user.name }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                {{ replyingTo.content }}
              </p>
            </div>
            <button
              @click="cancelReply"
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div class="message-input">
          <div class="flex items-end space-x-3 p-4">
            <!-- Attachment button -->
            <button
              @click="showAttachmentMenu = !showAttachmentMenu"
              class="flex-shrink-0 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              title="Attach file"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
              </svg>
            </button>

            <!-- Message input -->
            <div class="flex-1">
              <textarea
                v-model="messageText"
                @keydown="handleKeyDown"
                @input="handleTyping"
                ref="messageInput"
                placeholder="Type a message..."
                rows="1"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                :disabled="sending"
              ></textarea>
            </div>

            <!-- Send button -->
            <button
              @click="sendMessage"
              :disabled="!canSend"
              :class="[
                'flex-shrink-0 p-2 rounded-lg transition-colors',
                canSend
                  ? 'text-white bg-blue-500 hover:bg-blue-600'
                  : 'text-gray-400 bg-gray-200 dark:bg-gray-700 cursor-not-allowed'
              ]"
            >
              <svg v-if="sending" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <MessageSearchModal
      v-if="showSearchModal"
      :conversation="conversation"
      @close="showSearchModal = false"
    />

    <ParticipantsModal
      v-if="showParticipantsModal"
      :conversation="conversation"
      @close="showParticipantsModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useMessagingStore } from '@/stores/messaging'
import { useAuthStore } from '@/stores/auth'
import { formatDistanceToNow } from 'date-fns'
import MessageBubble from './MessageBubble.vue'
import MessageSearchModal from './MessageSearchModal.vue'
import ParticipantsModal from './ParticipantsModal.vue'

const props = defineProps({
  conversation: {
    type: Object,
    default: null
  }
})

const messagingStore = useMessagingStore()
const authStore = useAuthStore()

const messageText = ref('')
const replyingTo = ref(null)
const sending = ref(false)
const loadingMessages = ref(false)
const loadingMoreMessages = ref(false)
const showMenu = ref(false)
const showSearchModal = ref(false)
const showParticipantsModal = ref(false)
const showAttachmentMenu = ref(false)
const typingTimeout = ref(null)
const messagesContainer = ref(null)
const messageInput = ref(null)

const currentUser = computed(() => authStore.user)
const messages = computed(() => messagingStore.getConversationMessages(props.conversation?.id))
const hasMoreMessages = computed(() => messagingStore.hasMoreMessages(props.conversation?.id))
const typingUsers = computed(() => messagingStore.getTypingUsers(props.conversation?.id))

const conversationTitle = computed(() => {
  if (!props.conversation) return ''
  
  if (props.conversation.title) {
    return props.conversation.title
  }

  if (props.conversation.type === 'direct') {
    return otherParticipant.value?.name || 'Unknown User'
  }

  if (props.conversation.circle) {
    return props.conversation.circle.name
  }

  if (props.conversation.group) {
    return props.conversation.group.name
  }

  return `${props.conversation.type} conversation`
})

const otherParticipant = computed(() => {
  if (props.conversation?.type !== 'direct') return null
  return props.conversation.participants?.find(p => p.id !== currentUser.value?.id)
})

const canSend = computed(() => {
  return messageText.value.trim().length > 0 && !sending.value
})

const shouldShowAvatar = (message, index) => {
  if (index === 0) return true
  const prevMessage = messages.value[index - 1]
  return prevMessage.user.id !== message.user.id || 
         (new Date(message.created_at) - new Date(prevMessage.created_at)) > 300000 // 5 minutes
}

const shouldShowTimestamp = (message, index) => {
  if (index === 0) return true
  const prevMessage = messages.value[index - 1]
  return (new Date(message.created_at) - new Date(prevMessage.created_at)) > 300000 // 5 minutes
}

const isUserOnline = (userId) => {
  return messagingStore.onlineUsers.includes(userId)
}

const formatTime = (timestamp) => {
  if (!timestamp) return ''
  return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const getTypingText = () => {
  const users = typingUsers.value.filter(u => u.id !== currentUser.value.id)
  if (users.length === 0) return ''
  if (users.length === 1) return `${users[0].name} is typing...`
  if (users.length === 2) return `${users[0].name} and ${users[1].name} are typing...`
  return `${users[0].name} and ${users.length - 1} others are typing...`
}

const handleKeyDown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

const handleTyping = () => {
  // Send typing indicator
  messagingStore.sendTypingIndicator(props.conversation.id, true)
  
  // Clear previous timeout
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }
  
  // Set timeout to stop typing indicator
  typingTimeout.value = setTimeout(() => {
    messagingStore.sendTypingIndicator(props.conversation.id, false)
  }, 2000)
}

const sendMessage = async () => {
  if (!canSend.value) return

  const content = messageText.value.trim()
  const replyToId = replyingTo.value?.id

  // Clear input immediately for better UX
  messageText.value = ''
  replyingTo.value = null
  sending.value = true

  try {
    await messagingStore.sendMessage({
      conversation_id: props.conversation.id,
      content,
      reply_to_id: replyToId
    })

    // Scroll to bottom
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to send message:', error)
    // Restore message text on error
    messageText.value = content
    if (replyToId) {
      replyingTo.value = messages.value.find(m => m.id === replyToId)
    }
  } finally {
    sending.value = false
  }
}

const replyToMessage = (message) => {
  replyingTo.value = message
  messageInput.value?.focus()
}

const cancelReply = () => {
  replyingTo.value = null
}

const editMessage = (message) => {
  // Implement message editing
  console.log('Edit message:', message)
}

const deleteMessage = async (message) => {
  if (confirm('Are you sure you want to delete this message?')) {
    try {
      await messagingStore.deleteMessage(message.id)
    } catch (error) {
      console.error('Failed to delete message:', error)
    }
  }
}

const loadMoreMessages = async () => {
  if (loadingMoreMessages.value) return
  
  loadingMoreMessages.value = true
  try {
    await messagingStore.loadMoreMessages(props.conversation.id)
  } finally {
    loadingMoreMessages.value = false
  }
}

const togglePin = async () => {
  try {
    await messagingStore.togglePinConversation(props.conversation.id)
    showMenu.value = false
  } catch (error) {
    console.error('Failed to toggle pin:', error)
  }
}

const toggleMute = async () => {
  try {
    await messagingStore.toggleMuteConversation(props.conversation.id)
    showMenu.value = false
  } catch (error) {
    console.error('Failed to toggle mute:', error)
  }
}

const archiveConversation = async () => {
  if (confirm('Are you sure you want to archive this conversation?')) {
    try {
      await messagingStore.archiveConversation(props.conversation.id)
      showMenu.value = false
    } catch (error) {
      console.error('Failed to archive conversation:', error)
    }
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// Load messages when conversation changes
watch(() => props.conversation?.id, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    loadingMessages.value = true
    try {
      await messagingStore.loadMessages(newId)
      await messagingStore.markConversationAsRead(newId)
      await nextTick()
      scrollToBottom()
    } finally {
      loadingMessages.value = false
    }
  }
}, { immediate: true })

// Auto-resize textarea
watch(messageText, () => {
  if (messageInput.value) {
    messageInput.value.style.height = 'auto'
    messageInput.value.style.height = messageInput.value.scrollHeight + 'px'
  }
})

onMounted(() => {
  // Set up real-time message listeners
  if (props.conversation) {
    messagingStore.joinConversation(props.conversation.id)
  }
})

onUnmounted(() => {
  // Clean up typing timeout
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }
  
  // Leave conversation
  if (props.conversation) {
    messagingStore.leaveConversation(props.conversation.id)
  }
})
</script>

<style scoped>
.chat-interface {
  @apply h-full flex flex-col;
}

.no-conversation {
  @apply flex-1 flex items-center justify-center bg-gray-50 dark:bg-gray-900;
}

.conversation-view {
  @apply h-full flex flex-col;
}

.chat-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800;
}

.conversation-menu {
  @apply absolute right-0 top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10;
}

.menu-item {
  @apply w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-2;
}

.messages-area {
  @apply flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900;
}

.messages-list {
  @apply p-4 space-y-2;
}

.message-item {
  @apply flex;
}

.message-own {
  @apply justify-end;
}

.message-other {
  @apply justify-start;
}

.message-input-area {
  @apply border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800;
}

.reply-preview {
  @apply border-b border-gray-200 dark:border-gray-700;
}

.message-input textarea {
  @apply max-h-32 min-h-[2.5rem];
}

.btn {
  @apply inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
}

.btn-secondary {
  @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700;
}

.btn-sm {
  @apply px-3 py-1.5 text-xs;
}
</style>