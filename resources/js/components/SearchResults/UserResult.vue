<template>
  <div class="user-result">
    <div class="user-avatar">
      <img
        v-if="user.avatar_url"
        :src="user.avatar_url"
        :alt="`${user.name}'s avatar`"
        class="avatar-image"
      />
      <div v-else class="avatar-placeholder">
        {{ getInitials(user.name) }}
      </div>
    </div>
    
    <div class="user-info">
      <div class="user-header">
        <h3 class="user-name">
          <span v-html="highlightText(user.name, highlight.name)"></span>
        </h3>
        <div class="user-meta">
          <span v-if="user.current_position" class="user-position">
            {{ user.current_position }}
          </span>
          <span v-if="user.current_company" class="user-company">
            at {{ user.current_company }}
          </span>
          <span v-if="user.location" class="user-location">
            <MapPinIcon class="w-4 h-4" />
            {{ user.location }}
          </span>
        </div>
      </div>
      
      <div v-if="user.bio" class="user-bio">
        <span v-html="highlightText(user.bio, highlight.bio)"></span>
      </div>
      
      <div class="user-details">
        <div v-if="user.graduation_year || user.school" class="education-info">
          <AcademicCapIcon class="w-4 h-4" />
          <span>
            {{ user.school }}
            <span v-if="user.graduation_year">Class of {{ user.graduation_year }}</span>
          </span>
        </div>
        
        <div v-if="user.skills && user.skills.length > 0" class="skills-info">
          <span class="skills-label">Skills:</span>
          <div class="skills-list">
            <span
              v-for="skill in displaySkills"
              :key="skill"
              class="skill-tag"
              v-html="highlightText(skill, highlight.skills)"
            ></span>
            <span v-if="user.skills.length > maxSkillsDisplay" class="more-skills">
              +{{ user.skills.length - maxSkillsDisplay }} more
            </span>
          </div>
        </div>
      </div>
      
      <div class="user-actions">
        <button
          @click="viewProfile"
          class="action-btn primary"
        >
          <UserIcon class="w-4 h-4" />
          View Profile
        </button>
        
        <button
          v-if="canConnect"
          @click="sendConnectionRequest"
          class="action-btn secondary"
          :disabled="isConnecting"
        >
          <UserPlusIcon v-if="!isConnecting" class="w-4 h-4" />
          <LoadingSpinner v-else class="w-4 h-4" />
          Connect
        </button>
        
        <button
          @click="sendMessage"
          class="action-btn secondary"
        >
          <ChatBubbleLeftIcon class="w-4 h-4" />
          Message
        </button>
      </div>
    </div>
    
    <div class="result-meta">
      <div class="relevance-score" :title="`Relevance score: ${score.toFixed(2)}`">
        <div class="score-bar">
          <div 
            class="score-fill" 
            :style="{ width: `${Math.min(score * 10, 100)}%` }"
          ></div>
        </div>
        <span class="score-text">{{ (score * 10).toFixed(0) }}%</span>
      </div>
      
      <div v-if="mutualConnections > 0" class="mutual-connections">
        <UsersIcon class="w-4 h-4" />
        {{ mutualConnections }} mutual connection{{ mutualConnections !== 1 ? 's' : '' }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import {
  UserIcon,
  UserPlusIcon,
  ChatBubbleLeftIcon,
  MapPinIcon,
  AcademicCapIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '../LoadingSpinner.vue'

interface User {
  id: number
  name: string
  email: string
  bio?: string
  avatar_url?: string
  current_position?: string
  current_company?: string
  location?: string
  graduation_year?: number
  school?: string
  degree?: string
  skills?: string[]
  industries?: string[]
}

interface Highlight {
  name?: string[]
  bio?: string[]
  skills?: string[]
  [key: string]: string[] | undefined
}

const props = defineProps<{
  user: User
  highlight: Highlight
  score: number
}>()

const emit = defineEmits<{
  'profile-viewed': [userId: number]
  'connection-requested': [userId: number]
  'message-sent': [userId: number]
}>()

// Reactive state
const isConnecting = ref(false)
const maxSkillsDisplay = 5

// Toast composable
const { showToast } = useToast()

// Computed properties
const displaySkills = computed(() => 
  props.user.skills?.slice(0, maxSkillsDisplay) || []
)

const canConnect = computed(() => {
  // This would typically check if the user is already connected
  // For now, we'll assume all users can be connected to
  return true
})

const mutualConnections = computed(() => {
  // This would be calculated based on actual mutual connections
  // For now, we'll return a mock value based on score
  return Math.floor(props.score * 5)
})

// Methods
const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const highlightText = (text: string, highlights?: string[]): string => {
  if (!highlights || highlights.length === 0) {
    return text
  }
  
  // Join all highlight fragments and return as HTML
  return highlights.join('...')
}

const viewProfile = () => {
  router.visit(`/alumni/${props.user.id}`)
  emit('profile-viewed', props.user.id)
}

const sendConnectionRequest = async () => {
  isConnecting.value = true
  
  try {
    const response = await fetch('/api/connections', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        user_id: props.user.id
      })
    })

    if (response.ok) {
      showToast(`Connection request sent to ${props.user.name}!`, 'success')
      emit('connection-requested', props.user.id)
    } else {
      throw new Error('Failed to send connection request')
    }
  } catch (error) {
    console.error('Failed to send connection request:', error)
    showToast('Failed to send connection request. Please try again.', 'error')
  } finally {
    isConnecting.value = false
  }
}

const sendMessage = () => {
  router.visit(`/messages/new?to=${props.user.id}`)
  emit('message-sent', props.user.id)
}
</script>

<style scoped>
.user-result {
  @apply flex items-start space-x-4 p-4;
}

.user-avatar {
  @apply flex-shrink-0;
}

.avatar-image {
  @apply w-12 h-12 rounded-full object-cover;
}

.avatar-placeholder {
  @apply w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center;
  @apply text-gray-600 font-medium text-sm;
}

.user-info {
  @apply flex-1 space-y-3;
}

.user-header {
  @apply space-y-1;
}

.user-name {
  @apply text-lg font-semibold text-gray-900;
}

.user-meta {
  @apply flex items-center space-x-2 text-sm text-gray-600;
}

.user-position {
  @apply font-medium;
}

.user-company {
  @apply text-gray-500;
}

.user-location {
  @apply flex items-center space-x-1;
}

.user-bio {
  @apply text-sm text-gray-700 leading-relaxed;
}

.user-details {
  @apply space-y-2;
}

.education-info {
  @apply flex items-center space-x-2 text-sm text-gray-600;
}

.skills-info {
  @apply space-y-1;
}

.skills-label {
  @apply text-sm font-medium text-gray-700;
}

.skills-list {
  @apply flex flex-wrap gap-1;
}

.skill-tag {
  @apply inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded;
}

.more-skills {
  @apply text-xs text-gray-500;
}

.user-actions {
  @apply flex items-center space-x-2;
}

.action-btn {
  @apply flex items-center space-x-1 px-3 py-1 rounded-md text-sm;
  @apply transition-colors;
}

.action-btn.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-btn.secondary {
  @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.result-meta {
  @apply flex-shrink-0 space-y-2 text-right;
}

.relevance-score {
  @apply space-y-1;
}

.score-bar {
  @apply w-16 h-2 bg-gray-200 rounded-full overflow-hidden;
}

.score-fill {
  @apply h-full bg-green-500 transition-all duration-300;
}

.score-text {
  @apply text-xs text-gray-500;
}

.mutual-connections {
  @apply flex items-center space-x-1 text-xs text-gray-500;
}

/* Highlight styles */
:deep(mark) {
  @apply bg-yellow-200 text-yellow-900 px-1 rounded;
}
</style>