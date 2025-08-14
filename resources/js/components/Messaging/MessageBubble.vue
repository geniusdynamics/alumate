<template>
  <div :class="['message-bubble', isOwn ? 'message-own' : 'message-other']">
    <!-- Avatar (for other users) -->
    <div v-if="showAvatar && !isOwn" class="message-avatar">
      <img
        :src="message.user.avatar_url || '/default-avatar.png'"
        :alt="message.user.name"
        class="w-8 h-8 rounded-full"
      />
    </div>

    <div class="message-content">
      <!-- Reply indicator -->
      <div v-if="message.reply_to_id" class="reply-indicator">
        <div class="reply-line"></div>
        <div class="reply-content">
          <span class="reply-author">{{ message.reply_to?.user?.name }}</span>
          <span class="reply-text">{{ message.reply_to?.content }}</span>
        </div>
      </div>

      <!-- Message bubble -->
      <div
        :class="[
          'bubble',
          isOwn ? 'bubble-own' : 'bubble-other',
          message.type === 'system' ? 'bubble-system' : ''
        ]"
        @contextmenu="showContextMenu"
      >
        <!-- Message content -->
        <div class="bubble-content">
          <!-- Attachments -->
          <div v-if="message.attachments && message.attachments.length > 0" class="attachments">
            <div
              v-for="(attachment, index) in message.attachments"
              :key="index"
              class="attachment"
            >
              <div v-if="attachment.type === 'image'" class="attachment-image">
                <img
                  :src="attachment.url"
                  :alt="attachment.name"
                  class="max-w-xs rounded-lg cursor-pointer"
                  @click="openImageModal(attachment)"
                />
              </div>
              <div v-else class="attachment-file">
                <div class="flex items-center space-x-2 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                  </svg>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ attachment.name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatFileSize(attachment.size) }}
                    </p>
                  </div>
                  <a
                    :href="attachment.url"
                    download
                    class="text-blue-500 hover:text-blue-600 dark:text-blue-400"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Text content -->
          <div v-if="message.content" class="message-text">
            <p class="whitespace-pre-wrap">{{ message.content }}</p>
          </div>

          <!-- Edited indicator -->
          <span v-if="message.is_edited" class="edited-indicator">
            (edited)
          </span>
        </div>

        <!-- Message actions -->
        <div v-if="showActions" class="message-actions">
          <button
            @click="$emit('reply', message)"
            class="action-button"
            title="Reply"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
          </button>
          <button
            v-if="isOwn && canEdit"
            @click="$emit('edit', message)"
            class="action-button"
            title="Edit"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            v-if="isOwn || canDelete"
            @click="$emit('delete', message)"
            class="action-button text-red-500 hover:text-red-600"
            title="Delete"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Message metadata -->
      <div class="message-metadata">
        <!-- Timestamp -->
        <div v-if="showTimestamp" class="timestamp">
          <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatTime(message.created_at) }}
          </span>
        </div>

        <!-- Read receipts (for own messages) -->
        <div v-if="isOwn && message.reads && message.reads.length > 0" class="read-receipts">
          <div class="flex -space-x-1">
            <img
              v-for="read in message.reads.slice(0, 3)"
              :key="read.user.id"
              :src="read.user.avatar_url || '/default-avatar.png'"
              :alt="read.user.name"
              :title="`Read by ${read.user.name} at ${formatTime(read.read_at)}`"
              class="w-4 h-4 rounded-full border border-white dark:border-gray-800"
            />
            <span v-if="message.reads.length > 3" class="text-xs text-gray-500 ml-1">
              +{{ message.reads.length - 3 }}
            </span>
          </div>
        </div>

        <!-- Delivery status -->
        <div v-if="isOwn" class="delivery-status">
          <svg
            v-if="message.sending"
            class="w-4 h-4 text-gray-400 animate-spin"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg
            v-else-if="message.reads && message.reads.length > 0"
            class="w-4 h-4 text-blue-500"
            fill="currentColor"
            viewBox="0 0 20 20"
            title="Read"
          >
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          <svg
            v-else
            class="w-4 h-4 text-gray-400"
            fill="currentColor"
            viewBox="0 0 20 20"
            title="Delivered"
          >
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Avatar (for own messages) -->
    <div v-if="showAvatar && isOwn" class="message-avatar">
      <img
        :src="message.user.avatar_url || '/default-avatar.png'"
        :alt="message.user.name"
        class="w-8 h-8 rounded-full"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { formatDistanceToNow, format } from 'date-fns'

const props = defineProps({
  message: {
    type: Object,
    required: true
  },
  showAvatar: {
    type: Boolean,
    default: true
  },
  showTimestamp: {
    type: Boolean,
    default: true
  },
  isOwn: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['reply', 'edit', 'delete'])

const showActions = ref(false)

const canEdit = computed(() => {
  // Can edit within 24 hours
  const messageTime = new Date(props.message.created_at)
  const now = new Date()
  const hoursDiff = (now - messageTime) / (1000 * 60 * 60)
  return hoursDiff < 24
})

const canDelete = computed(() => {
  // Add logic for who can delete messages (admins, moderators, etc.)
  return props.isOwn
})

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInHours = (now - date) / (1000 * 60 * 60)
  
  if (diffInHours < 24) {
    return formatDistanceToNow(date, { addSuffix: true })
  } else {
    return format(date, 'MMM d, yyyy h:mm a')
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const showContextMenu = (event) => {
  event.preventDefault()
  showActions.value = !showActions.value
}

const openImageModal = (attachment) => {
  // Implement image modal
  console.log('Open image modal:', attachment)
}
</script>

<style scoped>
.message-bubble {
  @apply flex items-end space-x-2 mb-2;
}

.message-own {
  @apply flex-row-reverse space-x-reverse;
}

.message-other {
  @apply flex-row;
}

.message-avatar {
  @apply flex-shrink-0;
}

.message-content {
  @apply flex-1 max-w-xs sm:max-w-md lg:max-w-lg;
}

.reply-indicator {
  @apply flex items-start space-x-2 mb-1 pl-3;
}

.reply-line {
  @apply w-0.5 h-4 bg-gray-300 dark:bg-gray-600 rounded-full flex-shrink-0 mt-1;
}

.reply-content {
  @apply flex flex-col text-xs text-gray-600 dark:text-gray-400;
}

.reply-author {
  @apply font-medium;
}

.reply-text {
  @apply truncate max-w-48;
}

.bubble {
  @apply relative rounded-2xl px-4 py-2 shadow-sm;
}

.bubble-own {
  @apply bg-blue-500 text-white;
}

.bubble-other {
  @apply bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600;
}

.bubble-system {
  @apply bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-center italic;
}

.bubble-content {
  @apply space-y-2;
}

.attachments {
  @apply space-y-2;
}

.attachment-image img {
  @apply max-w-full h-auto;
}

.message-text {
  @apply text-sm leading-relaxed;
}

.edited-indicator {
  @apply text-xs opacity-70 ml-2;
}

.message-actions {
  @apply absolute -top-8 right-0 flex space-x-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-1 opacity-0 group-hover:opacity-100 transition-opacity;
}

.action-button {
  @apply p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded transition-colors;
}

.message-metadata {
  @apply flex items-center justify-between mt-1 px-1;
}

.message-own .message-metadata {
  @apply flex-row-reverse;
}

.timestamp {
  @apply flex-shrink-0;
}

.read-receipts {
  @apply flex-shrink-0;
}

.delivery-status {
  @apply flex-shrink-0;
}

/* Hover effects */
.message-bubble:hover .message-actions {
  @apply opacity-100;
}

.bubble:hover {
  @apply shadow-md;
}

/* Animation for new messages */
.message-bubble {
  animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .message-content {
    @apply max-w-xs;
  }
  
  .bubble {
    @apply px-3 py-2;
  }
  
  .message-text {
    @apply text-sm;
  }
}
</style>