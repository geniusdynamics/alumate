<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
              <!-- Header -->
              <div class="bg-white px-6 py-4 border-b">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                      <img
                        v-if="event.organizer.avatar_url"
                        :src="event.organizer.avatar_url"
                        :alt="event.organizer.name"
                        class="h-10 w-10 rounded-full"
                      />
                      <div v-else class="h-10 w-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">
                          {{ event.organizer.name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                    </div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ event.organizer.name }}</p>
                      <p class="text-sm text-gray-500">Event Organizer</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <button
                      v-if="event.user_data?.can_edit"
                      @click="$emit('edit', event)"
                      class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                      <PencilIcon class="h-4 w-4 mr-2" />
                      Edit
                    </button>
                    <button
                      @click="$emit('close')"
                      class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                      <XMarkIcon class="h-6 w-6" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Content -->
              <div class="bg-white px-6 py-6 max-h-96 overflow-y-auto">
                <!-- Event Image -->
                <div v-if="event.media_urls && event.media_urls.length > 0" class="mb-6">
                  <img
                    :src="event.media_urls[0]"
                    :alt="event.title"
                    class="w-full h-64 object-cover rounded-lg"
                  />
                </div>

                <!-- Title and Badges -->
                <div class="mb-6">
                  <div class="flex items-start justify-between mb-3">
                    <h1 class="text-2xl font-bold text-gray-900">{{ event.title }}</h1>
                    <div class="flex space-x-2">
                      <span :class="formatBadgeClass" class="px-2 py-1 text-xs font-medium rounded-full">
                        {{ formatLabel }}
                      </span>
                      <span :class="typeBadgeClass" class="px-2 py-1 text-xs font-medium rounded-full">
                        {{ typeLabel }}
                      </span>
                    </div>
                  </div>
                  
                  <!-- Registration Status -->
                  <div v-if="event.user_data?.is_registered" class="mb-4">
                    <div class="flex items-center text-sm">
                      <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2" />
                      <span class="text-green-700 font-medium">
                        <template v-if="event.user_data.is_checked_in">
                          You're checked in to this event
                        </template>
                        <template v-else>
                          You're registered for this event
                        </template>
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Event Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                  <!-- Date and Time -->
                  <div class="flex items-start space-x-3">
                    <CalendarIcon class="h-5 w-5 text-gray-400 mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">Date & Time</p>
                      <p class="text-sm text-gray-600">{{ formatDate(event.start_date) }}</p>
                      <p class="text-sm text-gray-600">{{ formatTime(event.start_date, event.end_date) }}</p>
                      <p class="text-xs text-gray-500">{{ getDuration() }}</p>
                    </div>
                  </div>

                  <!-- Location -->
                  <div class="flex items-start space-x-3">
                    <MapPinIcon class="h-5 w-5 text-gray-400 mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">Location</p>
                      <template v-if="event.format === 'virtual'">
                        <p class="text-sm text-gray-600">Virtual Event</p>
                        <p v-if="event.virtual_link" class="text-sm text-blue-600">
                          <a :href="event.virtual_link" target="_blank" class="hover:underline">
                            Join Virtual Event
                          </a>
                        </p>
                      </template>
                      <template v-else>
                        <p class="text-sm text-gray-600">{{ event.venue_name }}</p>
                        <p v-if="event.venue_address" class="text-sm text-gray-500">{{ event.venue_address }}</p>
                      </template>
                    </div>
                  </div>

                  <!-- Capacity -->
                  <div v-if="event.max_capacity" class="flex items-start space-x-3">
                    <UsersIcon class="h-5 w-5 text-gray-400 mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">Capacity</p>
                      <p class="text-sm text-gray-600">
                        {{ event.current_attendees }} / {{ event.max_capacity }} attendees
                      </p>
                      <div class="w-32 bg-gray-200 rounded-full h-2 mt-1">
                        <div
                          class="bg-blue-600 h-2 rounded-full"
                          :style="{ width: `${Math.min((event.current_attendees / event.max_capacity) * 100, 100)}%` }"
                        ></div>
                      </div>
                    </div>
                  </div>

                  <!-- Institution -->
                  <div v-if="event.institution" class="flex items-start space-x-3">
                    <BuildingOfficeIcon class="h-5 w-5 text-gray-400 mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">Institution</p>
                      <p class="text-sm text-gray-600">{{ event.institution.name }}</p>
                    </div>
                  </div>
                </div>

                <!-- Description -->
                <div class="mb-6">
                  <h3 class="text-lg font-medium text-gray-900 mb-3">About This Event</h3>
                  <div class="prose prose-sm max-w-none text-gray-600">
                    <p class="whitespace-pre-wrap">{{ event.description }}</p>
                  </div>
                </div>

                <!-- Virtual Event Viewer -->
                <div v-if="event.format !== 'in_person' && event.user_data?.is_registered" class="mb-6">
                  <h3 class="text-lg font-medium text-gray-900 mb-3">Join Virtual Event</h3>
                  
                  <!-- Meeting Credentials -->
                  <div class="mb-4">
                    <MeetingCredentials
                      :credentials="meetingCredentials"
                      :event-title="event.title"
                      :event-date="event.start_date"
                      @test-connection="handleTestConnection"
                      @add-to-calendar="handleAddToCalendar"
                    />
                  </div>

                  <!-- Virtual Event Viewer (for Jitsi or embeddable meetings) -->
                  <div v-if="canShowEventViewer" class="mt-4">
                    <VirtualEventViewer
                      :event="event"
                      :meeting-credentials="meetingCredentials"
                      :can-manage-event="event.user_data?.can_edit || false"
                      :can-record="event.user_data?.can_edit || false"
                      height="400px"
                      @settings-updated="handleVirtualSettingsUpdate"
                      @recording-toggled="handleRecordingToggle"
                    />
                  </div>

                  <!-- Hybrid Event Interface (for hybrid events) -->
                  <div v-if="event.format === 'hybrid' && canShowEventViewer" class="mt-4">
                    <HybridEventInterface
                      :event="event"
                      :participants="eventParticipants"
                      :meeting-credentials="meetingCredentials"
                      :can-manage-event="event.user_data?.can_edit || false"
                      :can-record="event.user_data?.can_edit || false"
                      @settings-updated="handleVirtualSettingsUpdate"
                      @participant-action="handleParticipantAction"
                      @meeting-action="handleMeetingAction"
                      @chat-message="handleChatMessage"
                      @question-submitted="handleQuestionSubmitted"
                      @poll-created="handlePollCreated"
                      @poll-voted="handlePollVoted"
                    />
                  </div>
                </div>

                <!-- Virtual Instructions (for non-registered users) -->
                <div v-else-if="event.format !== 'in_person' && event.virtual_instructions" class="mb-6">
                  <h3 class="text-lg font-medium text-gray-900 mb-3">Virtual Event Instructions</h3>
                  <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <p class="text-sm text-blue-800 whitespace-pre-wrap">{{ event.virtual_instructions }}</p>
                    <div class="mt-3">
                      <p class="text-sm text-blue-700 font-medium">
                        Register for this event to access the virtual meeting room.
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Tags -->
                <div v-if="event.tags && event.tags.length > 0" class="mb-6">
                  <h3 class="text-lg font-medium text-gray-900 mb-3">Tags</h3>
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="tag in event.tags"
                      :key="tag"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                    >
                      {{ tag }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Footer Actions -->
              <div class="bg-gray-50 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <!-- Share Button -->
                  <button
                    @click="shareEvent"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <ShareIcon class="h-4 w-4 mr-2" />
                    Share
                  </button>

                  <!-- Add to Calendar -->
                  <button
                    @click="addToCalendar"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <CalendarIcon class="h-4 w-4 mr-2" />
                    Add to Calendar
                  </button>
                </div>

                <div class="flex items-center space-x-3">
                  <!-- Check-in Button -->
                  <button
                    v-if="event.user_data?.is_registered && !event.user_data.is_checked_in && canCheckIn"
                    @click="$emit('checkin', event)"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                  >
                    <CheckIcon class="h-4 w-4 mr-2" />
                    Check In
                  </button>

                  <!-- Cancel Registration -->
                  <button
                    v-else-if="event.user_data?.is_registered && canCancelRegistration"
                    @click="cancelRegistration"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    Cancel Registration
                  </button>

                  <!-- Register Button -->
                  <button
                    v-else-if="!event.user_data?.is_registered"
                    @click="showRegistrationModal = true"
                    :disabled="!canRegister"
                    :class="[
                      'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                      canRegister
                        ? 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
                        : 'text-gray-500 bg-gray-200 cursor-not-allowed'
                    ]"
                  >
                    <template v-if="!canRegister">
                      {{ registrationStatusText }}
                    </template>
                    <template v-else>
                      Register for Event
                    </template>
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

  <!-- Registration Modal -->
  <EventRegistrationModal
    :show="showRegistrationModal"
    :event="event"
    @close="showRegistrationModal = false"
    @registered="handleRegistered"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  XMarkIcon,
  CalendarIcon,
  MapPinIcon,
  UsersIcon,
  BuildingOfficeIcon,
  CheckCircleIcon,
  PencilIcon,
  ShareIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'
import { format, parseISO, isAfter, isBefore, addHours, differenceInMinutes } from 'date-fns'
import EventRegistrationModal from './EventRegistrationModal.vue'
import MeetingCredentials from './MeetingCredentials.vue'
import VirtualEventViewer from './VirtualEventViewer.vue'
import HybridEventInterface from './HybridEventInterface.vue'
import { useEventsStore } from '@/Stores/eventsStore'

interface Event {
  id: number
  title: string
  description: string
  short_description?: string
  type: string
  format: string
  start_date: string
  end_date: string
  venue_name?: string
  venue_address?: string
  virtual_link?: string
  virtual_instructions?: string
  max_capacity?: number
  current_attendees: number
  registration_status: string
  registration_deadline?: string
  organizer: {
    id: number
    name: string
    avatar_url?: string
  }
  institution?: {
    id: number
    name: string
  }
  user_data?: {
    is_registered: boolean
    registration?: any
    is_checked_in: boolean
    can_edit: boolean
  }
  media_urls?: string[]
  tags?: string[]
}

interface Props {
  event: Event
  show: boolean
}

interface Emits {
  (e: 'close'): void
  (e: 'register', event: Event, registrationData?: any): void
  (e: 'edit', event: Event): void
  (e: 'checkin', event: Event): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const showRegistrationModal = ref(false)

// Computed properties
const formatLabel = computed(() => {
  const formats = {
    'in_person': 'In Person',
    'virtual': 'Virtual',
    'hybrid': 'Hybrid'
  }
  return formats[props.event.format as keyof typeof formats] || props.event.format
})

const formatBadgeClass = computed(() => {
  const classes = {
    'in_person': 'bg-green-100 text-green-800',
    'virtual': 'bg-blue-100 text-blue-800',
    'hybrid': 'bg-purple-100 text-purple-800'
  }
  return classes[props.event.format as keyof typeof classes] || 'bg-gray-100 text-gray-800'
})

const typeLabel = computed(() => {
  const types = {
    'networking': 'Networking',
    'reunion': 'Reunion',
    'webinar': 'Webinar',
    'workshop': 'Workshop',
    'social': 'Social',
    'professional': 'Professional',
    'fundraising': 'Fundraising',
    'other': 'Other'
  }
  return types[props.event.type as keyof typeof types] || props.event.type
})

const typeBadgeClass = computed(() => {
  const classes = {
    'networking': 'bg-orange-100 text-orange-800',
    'reunion': 'bg-pink-100 text-pink-800',
    'webinar': 'bg-indigo-100 text-indigo-800',
    'workshop': 'bg-yellow-100 text-yellow-800',
    'social': 'bg-green-100 text-green-800',
    'professional': 'bg-blue-100 text-blue-800',
    'fundraising': 'bg-red-100 text-red-800',
    'other': 'bg-gray-100 text-gray-800'
  }
  return classes[props.event.type as keyof typeof classes] || 'bg-gray-100 text-gray-800'
})

const canRegister = computed(() => {
  if (props.event.registration_status !== 'open') {
    return false
  }

  if (props.event.registration_deadline) {
    const deadline = parseISO(props.event.registration_deadline)
    if (isAfter(new Date(), deadline)) {
      return false
    }
  }

  if (props.event.max_capacity && props.event.current_attendees >= props.event.max_capacity) {
    return false
  }

  return true
})

const canCheckIn = computed(() => {
  const now = new Date()
  const startDate = parseISO(props.event.start_date)
  const endDate = parseISO(props.event.end_date)
  
  // Allow check-in 2 hours before event starts and until event ends
  const checkInStart = addHours(startDate, -2)
  
  return isAfter(now, checkInStart) && isBefore(now, endDate)
})

const canCancelRegistration = computed(() => {
  const now = new Date()
  const startDate = parseISO(props.event.start_date)
  
  // Allow cancellation until event starts
  return isBefore(now, startDate)
})

const registrationStatusText = computed(() => {
  if (props.event.registration_status === 'closed') {
    return 'Registration Closed'
  }
  
  if (props.event.registration_status === 'waitlist') {
    return 'Join Waitlist'
  }
  
  if (props.event.max_capacity && props.event.current_attendees >= props.event.max_capacity) {
    return 'Event Full'
  }
  
  if (props.event.registration_deadline) {
    const deadline = parseISO(props.event.registration_deadline)
    if (isAfter(new Date(), deadline)) {
      return 'Registration Deadline Passed'
    }
  }
  
  return 'Register'
})

// Methods
const formatDate = (dateString: string) => {
  const date = parseISO(dateString)
  return format(date, 'EEEE, MMMM d, yyyy')
}

const formatTime = (startString: string, endString: string) => {
  const start = parseISO(startString)
  const end = parseISO(endString)
  return `${format(start, 'h:mm a')} - ${format(end, 'h:mm a')}`
}

const getDuration = () => {
  const start = parseISO(props.event.start_date)
  const end = parseISO(props.event.end_date)
  const minutes = differenceInMinutes(end, start)
  
  if (minutes < 60) {
    return `${minutes} minutes`
  }
  
  const hours = Math.floor(minutes / 60)
  const remainingMinutes = minutes % 60
  
  if (remainingMinutes === 0) {
    return `${hours} hour${hours > 1 ? 's' : ''}`
  }
  
  return `${hours}h ${remainingMinutes}m`
}

const handleRegistered = (registrationData: any) => {
  showRegistrationModal.value = false
  emit('register', props.event, registrationData)
}

const cancelRegistration = async () => {
  if (confirm('Are you sure you want to cancel your registration for this event?')) {
    // This would call the API to cancel registration
    console.log('Cancelling registration for event:', props.event.id)
  }
}

const shareEvent = () => {
  if (navigator.share) {
    navigator.share({
      title: props.event.title,
      text: props.event.short_description || props.event.description,
      url: window.location.href
    })
  } else {
    // Fallback to copying URL to clipboard
    navigator.clipboard.writeText(window.location.href)
    alert('Event URL copied to clipboard!')
  }
}

const addToCalendar = () => {
  const start = parseISO(props.event.start_date)
  const end = parseISO(props.event.end_date)
  
  const startDate = format(start, "yyyyMMdd'T'HHmmss")
  const endDate = format(end, "yyyyMMdd'T'HHmmss")
  
  const calendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(props.event.title)}&dates=${startDate}/${endDate}&details=${encodeURIComponent(props.event.description)}&location=${encodeURIComponent(props.event.venue_address || 'Virtual Event')}`
  
  window.open(calendarUrl, '_blank')
}

// Virtual event functionality
const eventsStore = useEventsStore()

const meetingCredentials = computed(() => {
  if (!props.event.user_data?.is_registered || props.event.format === 'in_person') {
    return {}
  }
  
  // This would typically come from the API
  return {
    platform: 'jitsi', // or props.event.meeting_platform
    url: props.event.virtual_link || '',
    password: '', // props.event.meeting_password
    instructions: props.event.virtual_instructions || '',
    embed_allowed: true,
    features: {
      chat: true,
      screen_sharing: true,
      recording: false,
      waiting_room: false
    }
  }
})

const canShowEventViewer = computed(() => {
  return props.event.user_data?.is_registered && 
         props.event.format !== 'in_person' &&
         meetingCredentials.value.platform === 'jitsi'
})

const eventParticipants = computed(() => {
  // Mock participants - would come from API
  return [
    {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      attendance_type: 'virtual',
      is_host: false,
      is_moderator: false,
      status: 'active'
    },
    {
      id: 2,
      name: 'Jane Smith',
      email: 'jane@example.com',
      attendance_type: 'in_person',
      is_host: false,
      is_moderator: false,
      status: 'active'
    }
  ]
})

// Virtual event handlers
const handleVirtualSettingsUpdate = (settings: any) => {
  console.log('Virtual settings updated:', settings)
  // This would call the API to update event settings
}

const handleRecordingToggle = (recording: boolean) => {
  console.log('Recording toggled:', recording)
  // This would call the API to start/stop recording
}

const handleParticipantAction = (action: string, participantId: number, data?: any) => {
  console.log('Participant action:', action, participantId, data)
  // This would call the API to handle participant actions
}

const handleMeetingAction = (action: string, data?: any) => {
  console.log('Meeting action:', action, data)
  // This would call the API to handle meeting actions
}

const handleChatMessage = (message: string) => {
  console.log('Chat message:', message)
  // This would send the message via WebSocket or API
}

const handleQuestionSubmitted = (question: string) => {
  console.log('Question submitted:', question)
  // This would submit the question via API
}

const handlePollCreated = (poll: any) => {
  console.log('Poll created:', poll)
  // This would create the poll via API
}

const handlePollVoted = (optionId: number) => {
  console.log('Poll voted:', optionId)
  // This would submit the vote via API
}

const handleTestConnection = () => {
  console.log('Testing connection...')
  // This would test the meeting connection
}

const handleAddToCalendar = (credentials: any) => {
  console.log('Adding to calendar:', credentials)
  // This would add the meeting to calendar
}
</script>