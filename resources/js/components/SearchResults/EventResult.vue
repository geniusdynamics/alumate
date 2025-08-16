<template>
  <div class="event-result">
    <div class="event-header">
      <div class="event-info">
        <h3 class="event-title">
          <span v-html="highlightText(event.title, highlight.title)"></span>
        </h3>
        <div class="event-meta">
          <div class="event-date">
            <CalendarIcon class="w-4 h-4" />
            <span>{{ formatEventDate(event.event_date) }}</span>
          </div>
          <div v-if="event.location" class="event-location">
            <MapPinIcon class="w-4 h-4" />
            <span>{{ event.location }}</span>
          </div>
          <div v-if="event.event_type" class="event-type">
            {{ formatEventType(event.event_type) }}
          </div>
        </div>
      </div>
      
      <div class="event-actions-header">
        <div class="relevance-score" :title="`Relevance score: ${score.toFixed(2)}`">
          <div class="score-bar">
            <div 
              class="score-fill" 
              :style="{ width: `${Math.min(score * 10, 100)}%` }"
            ></div>
          </div>
          <span class="score-text">{{ (score * 10).toFixed(0) }}%</span>
        </div>
        
        <div class="event-status">
          <span 
            class="status-badge"
            :class="getStatusClass(event.status)"
          >
            {{ formatEventStatus(event.status) }}
          </span>
        </div>
      </div>
    </div>
    
    <div class="event-description">
      <p v-html="highlightText(truncateDescription(event.description), highlight.description)"></p>
      <button
        v-if="event.description && event.description.length > 200"
        @click="showFullDescription = !showFullDescription"
        class="show-more-btn"
      >
        {{ showFullDescription ? 'Show Less' : 'Show More' }}
      </button>
    </div>
    
    <div class="event-details">
      <div v-if="event.organizer" class="organizer-info">
        <UserIcon class="w-4 h-4" />
        <span>Organized by {{ event.organizer }}</span>
      </div>
      
      <div v-if="event.capacity" class="capacity-info">
        <UsersIcon class="w-4 h-4" />
        <span>{{ event.attendees_count || 0 }} / {{ event.capacity }} attendees</span>
      </div>
      
      <div v-if="event.is_virtual" class="virtual-info">
        <ComputerDesktopIcon class="w-4 h-4" />
        <span>Virtual Event</span>
      </div>
      
      <div v-if="event.price" class="price-info">
        <CurrencyDollarIcon class="w-4 h-4" />
        <span>{{ formatPrice(event.price) }}</span>
      </div>
    </div>
    
    <div v-if="event.tags && event.tags.length > 0" class="event-tags">
      <TagIcon class="w-4 h-4" />
      <div class="tags-list">
        <span
          v-for="tag in event.tags"
          :key="tag"
          class="tag"
        >
          {{ tag }}
        </span>
      </div>
    </div>
    
    <div v-if="attendingAlumni.length > 0" class="attending-alumni">
      <div class="alumni-header">
        <UsersIcon class="w-4 h-4" />
        <span class="alumni-title">Alumni Attending</span>
      </div>
      <div class="alumni-list">
        <div
          v-for="alumnus in attendingAlumni.slice(0, 4)"
          :key="alumnus.id"
          class="alumnus-item"
        >
          <img
            v-if="alumnus.avatar_url"
            :src="alumnus.avatar_url"
            :alt="`${alumnus.name}'s avatar`"
            class="alumnus-avatar"
          />
          <div v-else class="alumnus-avatar-placeholder">
            {{ getInitials(alumnus.name) }}
          </div>
          <span class="alumnus-name">{{ alumnus.name }}</span>
        </div>
        <div v-if="attendingAlumni.length > 4" class="more-alumni">
          +{{ attendingAlumni.length - 4 }} more
        </div>
      </div>
    </div>
    
    <div class="event-actions">
      <button
        @click="viewEvent"
        class="action-btn primary"
      >
        <EyeIcon class="w-4 h-4" />
        View Details
      </button>
      
      <button
        v-if="canRSVP"
        @click="rsvpToEvent"
        class="action-btn"
        :class="rsvpStatus === 'attending' ? 'attending' : 'primary'"
        :disabled="isRSVPing"
      >
        <CheckIcon v-if="rsvpStatus === 'attending' && !isRSVPing" class="w-4 h-4" />
        <CalendarIcon v-else-if="!isRSVPing" class="w-4 h-4" />
        <LoadingSpinner v-else class="w-4 h-4" />
        {{ getRSVPButtonText() }}
      </button>
      
      <button
        @click="shareEvent"
        class="action-btn secondary"
      >
        <ShareIcon class="w-4 h-4" />
        Share
      </button>
      
      <button
        @click="saveEvent"
        class="action-btn secondary"
        :class="{ saved: isSaved }"
        :disabled="isSaving"
      >
        <BookmarkIcon v-if="!isSaving" class="w-4 h-4" />
        <LoadingSpinner v-else class="w-4 h-4" />
        {{ isSaved ? 'Saved' : 'Save' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/composables/useToast'
import {
  CalendarIcon,
  MapPinIcon,
  UserIcon,
  UsersIcon,
  ComputerDesktopIcon,
  CurrencyDollarIcon,
  TagIcon,
  EyeIcon,
  CheckIcon,
  ShareIcon,
  BookmarkIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '../LoadingSpinner.vue'

interface Event {
  id: number
  title: string
  description: string
  event_date: string
  location?: string
  event_type?: string
  status: 'upcoming' | 'ongoing' | 'completed' | 'cancelled'
  organizer?: string
  capacity?: number
  attendees_count?: number
  is_virtual: boolean
  price?: number
  tags?: string[]
}

interface AttendingAlumnus {
  id: number
  name: string
  avatar_url?: string
}

interface Highlight {
  title?: string[]
  description?: string[]
  [key: string]: string[] | undefined
}

const props = defineProps<{
  event: Event
  highlight: Highlight
  score: number
}>()

const emit = defineEmits<{
  'event-viewed': [eventId: number]
  'event-rsvp': [eventId: number, status: string]
  'event-shared': [eventId: number]
  'event-saved': [eventId: number]
}>()

// Reactive state
const showFullDescription = ref(false)
const isRSVPing = ref(false)
const isSaving = ref(false)
const isSaved = ref(false) // This would be determined from user's saved events
const rsvpStatus = ref<'none' | 'attending' | 'maybe' | 'not_attending'>('none')

// Mock attending alumni (this would come from props or API)
const attendingAlumni = ref<AttendingAlumnus[]>([
  {
    id: 1,
    name: 'Alice Johnson',
    avatar_url: undefined
  },
  {
    id: 2,
    name: 'Bob Smith'
  },
  {
    id: 3,
    name: 'Carol Davis'
  }
])

// Toast composable
const { showToast } = useToast()

// Computed properties
const truncateDescription = computed(() => {
  if (showFullDescription.value || !props.event.description) {
    return props.event.description
  }
  
  return props.event.description.length > 200 
    ? props.event.description.substring(0, 200) + '...'
    : props.event.description
})

const canRSVP = computed(() => {
  return props.event.status === 'upcoming' || props.event.status === 'ongoing'
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

const formatEventDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()
  const isTomorrow = date.toDateString() === new Date(now.getTime() + 24 * 60 * 60 * 1000).toDateString()
  
  if (isToday) {
    return `Today at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
  } else if (isTomorrow) {
    return `Tomorrow at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
  } else {
    return date.toLocaleDateString([], { 
      weekday: 'short', 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

const formatEventType = (type: string): string => {
  const typeMap: Record<string, string> = {
    'networking': 'Networking',
    'reunion': 'Reunion',
    'workshop': 'Workshop',
    'webinar': 'Webinar',
    'social': 'Social',
    'career': 'Career',
    'fundraising': 'Fundraising'
  }
  
  return typeMap[type] || type
}

const formatEventStatus = (status: string): string => {
  const statusMap: Record<string, string> = {
    'upcoming': 'Upcoming',
    'ongoing': 'Live Now',
    'completed': 'Completed',
    'cancelled': 'Cancelled'
  }
  
  return statusMap[status] || status
}

const getStatusClass = (status: string): string => {
  const classMap: Record<string, string> = {
    'upcoming': 'status-upcoming',
    'ongoing': 'status-ongoing',
    'completed': 'status-completed',
    'cancelled': 'status-cancelled'
  }
  
  return classMap[status] || ''
}

const formatPrice = (price: number): string => {
  if (price === 0) {
    return 'Free'
  }
  
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(price)
}

const getRSVPButtonText = (): string => {
  switch (rsvpStatus.value) {
    case 'attending':
      return 'Attending'
    case 'maybe':
      return 'Maybe'
    case 'not_attending':
      return 'Not Attending'
    default:
      return 'RSVP'
  }
}

const viewEvent = () => {
  router.visit(`/events/${props.event.id}`)
  emit('event-viewed', props.event.id)
}

const rsvpToEvent = async () => {
  isRSVPing.value = true
  
  try {
    const newStatus = rsvpStatus.value === 'attending' ? 'none' : 'attending'
    
    const response = await fetch(`/api/events/${props.event.id}/rsvp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        status: newStatus
      })
    })

    if (response.ok) {
      const data = await response.json()
      rsvpStatus.value = data.status
      
      const message = data.status === 'attending' 
        ? 'RSVP confirmed! See you at the event.'
        : 'RSVP cancelled.'
      
      showToast(message, 'success')
      emit('event-rsvp', props.event.id, data.status)
    } else {
      throw new Error('Failed to RSVP to event')
    }
  } catch (error) {
    console.error('Failed to RSVP to event:', error)
    showToast('Failed to RSVP. Please try again.', 'error')
  } finally {
    isRSVPing.value = false
  }
}

const shareEvent = () => {
  // This would open a share modal or copy link to clipboard
  navigator.clipboard.writeText(`${window.location.origin}/events/${props.event.id}`)
  showToast('Event link copied to clipboard!', 'success')
  emit('event-shared', props.event.id)
}

const saveEvent = async () => {
  isSaving.value = true
  
  try {
    const response = await fetch(`/api/events/${props.event.id}/save`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })

    if (response.ok) {
      const data = await response.json()
      isSaved.value = data.saved
      
      const action = data.saved ? 'saved' : 'unsaved'
      showToast(`Event ${action} successfully!`, 'success')
      emit('event-saved', props.event.id)
    } else {
      throw new Error('Failed to save event')
    }
  } catch (error) {
    console.error('Failed to save event:', error)
    showToast('Failed to save event. Please try again.', 'error')
  } finally {
    isSaving.value = false
  }
}
</script>

<style scoped>
.event-result {
  @apply space-y-4;
}

.event-header {
  @apply flex items-start justify-between;
}

.event-info {
  @apply flex-1 space-y-2;
}

.event-title {
  @apply text-lg font-semibold text-gray-900;
}

.event-meta {
  @apply flex items-center space-x-4 text-sm text-gray-600;
}

.event-date,
.event-location {
  @apply flex items-center space-x-1;
}

.event-type {
  @apply px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs;
}

.event-actions-header {
  @apply space-y-2 text-right;
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

.event-status {
  @apply text-xs;
}

.status-badge {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.status-upcoming {
  @apply bg-blue-100 text-blue-800;
}

.status-ongoing {
  @apply bg-green-100 text-green-800;
}

.status-completed {
  @apply bg-gray-100 text-gray-800;
}

.status-cancelled {
  @apply bg-red-100 text-red-800;
}

.event-description {
  @apply space-y-2;
}

.event-description p {
  @apply text-gray-700 leading-relaxed;
}

.show-more-btn {
  @apply text-sm text-blue-600 hover:text-blue-800;
}

.event-details {
  @apply flex items-center space-x-4 text-sm text-gray-600;
}

.organizer-info,
.capacity-info,
.virtual-info,
.price-info {
  @apply flex items-center space-x-1;
}

.event-tags {
  @apply flex items-center space-x-2;
}

.tags-list {
  @apply flex flex-wrap gap-1;
}

.tag {
  @apply inline-block px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded;
}

.attending-alumni {
  @apply bg-purple-50 border border-purple-200 rounded-lg p-4 space-y-3;
}

.alumni-header {
  @apply flex items-center space-x-2 text-purple-700 font-medium;
}

.alumni-list {
  @apply flex items-center space-x-3;
}

.alumnus-item {
  @apply flex items-center space-x-2;
}

.alumnus-avatar {
  @apply w-6 h-6 rounded-full object-cover;
}

.alumnus-avatar-placeholder {
  @apply w-6 h-6 rounded-full bg-purple-200 flex items-center justify-center;
  @apply text-purple-700 font-medium text-xs;
}

.alumnus-name {
  @apply text-sm text-gray-700;
}

.more-alumni {
  @apply text-sm text-purple-600 font-medium;
}

.event-actions {
  @apply flex items-center space-x-2 pt-3 border-t border-gray-200;
}

.action-btn {
  @apply flex items-center space-x-1 px-3 py-2 rounded-md text-sm;
  @apply transition-colors;
}

.action-btn.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-btn.attending {
  @apply bg-green-600 text-white hover:bg-green-700;
}

.action-btn.secondary {
  @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.action-btn.secondary.saved {
  @apply border-green-300 text-green-600 bg-green-50;
}

/* Highlight styles */
:deep(mark) {
  @apply bg-yellow-200 text-yellow-900 px-1 rounded;
}
</style>