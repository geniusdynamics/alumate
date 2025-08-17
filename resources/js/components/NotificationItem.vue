<template>
  <div
    :class="[
      'notification-item group px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors',
      { 'bg-blue-50': !isRead }
    ]"
    @click="handleClick"
  >
    <div class="flex items-start space-x-3">
      <!-- Avatar -->
      <img
        :src="avatarUrl"
        :alt="actorName"
        class="w-10 h-10 rounded-full flex-shrink-0"
      >
      
      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <!-- Notification Text -->
            <p class="text-sm text-gray-900" v-html="notificationText"></p>
            
            <!-- Timestamp -->
            <p class="text-xs text-gray-500 mt-1">
              {{ formatTime(notification.created_at) }}
            </p>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center space-x-1 ml-2">
            <!-- Unread Indicator -->
            <div
              v-if="!isRead"
              class="w-2 h-2 bg-blue-500 rounded-full"
              title="Unread"
            ></div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                v-if="!isRead"
                @click.stop="markAsRead"
                class="p-1 text-gray-400 hover:text-blue-600 rounded"
                title="Mark as read"
              >
                <i class="fas fa-check text-xs"></i>
              </button>
              
              <button
                @click.stop="deleteNotification"
                class="p-1 text-gray-400 hover:text-red-600 rounded"
                title="Delete"
              >
                <i class="fas fa-times text-xs"></i>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Action Buttons for Connection Requests -->
        <div v-if="showConnectionActions" class="mt-3 flex space-x-2">
          <button
            @click.stop="acceptConnection"
            :disabled="processingConnection"
            class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 disabled:opacity-50"
          >
            <i v-if="processingConnection" class="fas fa-spinner fa-spin mr-1"></i>
            Accept
          </button>
          <button
            @click.stop="declineConnection"
            :disabled="processingConnection"
            class="px-3 py-1 bg-gray-300 text-gray-700 text-xs rounded hover:bg-gray-400 disabled:opacity-50"
          >
            Decline
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  notification: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['mark-read', 'delete'])

const processingConnection = ref(false)

const isRead = computed(() => !!props.notification.read_at)

const notificationData = computed(() => props.notification.data || {})

const notificationType = computed(() => notificationData.value.type || 'unknown')

const actorName = computed(() => {
  switch (notificationType.value) {
    case 'post_reaction':
      return notificationData.value.reactor_name || 'Someone'
    case 'post_comment':
      return notificationData.value.commenter_name || 'Someone'
    case 'post_mention':
      return notificationData.value.mentioner_name || 'Someone'
    case 'connection_request':
      return notificationData.value.requester_name || 'Someone'
    case 'connection_accepted':
      return notificationData.value.accepter_name || 'Someone'
    default:
      return 'Someone'
  }
})

const avatarUrl = computed(() => {
  switch (notificationType.value) {
    case 'post_reaction':
      return notificationData.value.reactor_avatar || '/default-avatar.png'
    case 'post_comment':
      return notificationData.value.commenter_avatar || '/default-avatar.png'
    case 'post_mention':
      return notificationData.value.mentioner_avatar || '/default-avatar.png'
    case 'connection_request':
      return notificationData.value.requester_avatar || '/default-avatar.png'
    case 'connection_accepted':
      return notificationData.value.accepter_avatar || '/default-avatar.png'
    default:
      return '/default-avatar.png'
  }
})

const notificationText = computed(() => {
  const name = `<strong>${actorName.value}</strong>`
  
  switch (notificationType.value) {
    case 'post_reaction':
      const reactionType = notificationData.value.reaction_type || 'liked'
      const reactionEmoji = getReactionEmoji(reactionType)
      return `${name} ${reactionEmoji} ${getReactionText(reactionType)} your post`
      
    case 'post_comment':
      return `${name} commented on your post`
      
    case 'post_mention':
      return `${name} mentioned you in a comment`
      
    case 'connection_request':
      return `${name} wants to connect with you`
      
    case 'connection_accepted':
      return `${name} accepted your connection request`
      
    default:
      return 'You have a new notification'
  }
})

const showConnectionActions = computed(() => {
  return notificationType.value === 'connection_request' && !isRead.value
})

const getReactionEmoji = (type) => {
  const emojis = {
    like: '👍',
    love: '❤️',
    celebrate: '🎉',
    support: '🤝',
    insightful: '💡'
  }
  return emojis[type] || '👍'
}

const getReactionText = (type) => {
  const texts = {
    like: 'liked',
    love: 'loved',
    celebrate: 'celebrated',
    support: 'supported',
    insightful: 'found insightful'
  }
  return texts[type] || 'reacted to'
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)

  if (diffInSeconds < 60) return 'just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
  
  return date.toLocaleDateString()
}

const handleClick = () => {
  // Mark as read if unread
  if (!isRead.value) {
    markAsRead()
  }
  
  // Navigate to relevant page based on notification type
  navigateToRelevantPage()
}

const navigateToRelevantPage = () => {
  switch (notificationType.value) {
    case 'post_reaction':
    case 'post_comment':
    case 'post_mention':
      if (notificationData.value.post_id) {
        router.visit(`/posts/${notificationData.value.post_id}`)
      }
      break
      
    case 'connection_request':
      router.visit('/connections/requests')
      break
      
    case 'connection_accepted':
      if (notificationData.value.accepter_id) {
        router.visit(`/alumni/${notificationData.value.accepter_id}`)
      }
      break
  }
}

const markAsRead = () => {
  if (!isRead.value) {
    emit('mark-read', props.notification.id)
  }
}

const deleteNotification = () => {
  emit('delete', props.notification.id)
}

const acceptConnection = async () => {
  if (processingConnection.value) return
  
  processingConnection.value = true
  
  try {
    const connectionId = notificationData.value.connection_id
    const response = await fetch(`/api/connections/${connectionId}/accept`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      // Mark notification as read
      markAsRead()
      // Show success message
      // You could emit an event or show a toast notification here
    }
  } catch (error) {
    console.error('Error accepting connection:', error)
  } finally {
    processingConnection.value = false
  }
}

const declineConnection = async () => {
  if (processingConnection.value) return
  
  processingConnection.value = true
  
  try {
    const connectionId = notificationData.value.connection_id
    const response = await fetch(`/api/connections/${connectionId}/decline`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      // Mark notification as read
      markAsRead()
    }
  } catch (error) {
    console.error('Error declining connection:', error)
  } finally {
    processingConnection.value = false
  }
}
</script>

<style scoped>
.notification-item {
  /* group class should be applied in template, not with @apply */
}

.notification-item:hover .opacity-0 {
  @apply opacity-100;
}
</style>