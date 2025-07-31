<template>
  <div class="virtual-event-viewer">
    <!-- Meeting Platform Header -->
    <div class="meeting-header bg-gray-50 p-4 rounded-t-lg border-b">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="flex items-center space-x-2">
            <Icon :name="getPlatformIcon(event.meeting_platform)" class="w-5 h-5" />
            <span class="font-medium text-gray-900">
              {{ getPlatformName(event.meeting_platform) }}
            </span>
          </div>
          <div class="flex items-center space-x-2 text-sm text-gray-600">
            <Icon name="users" class="w-4 h-4" />
            <span>{{ event.current_attendees }} attending</span>
          </div>
        </div>
        
        <div class="flex items-center space-x-2">
          <button
            v-if="canRecord && event.recording_enabled"
            @click="toggleRecording"
            :class="[
              'px-3 py-1 rounded-full text-sm font-medium transition-colors',
              isRecording 
                ? 'bg-red-100 text-red-700 hover:bg-red-200' 
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            <Icon :name="isRecording ? 'stop-circle' : 'play-circle'" class="w-4 h-4 mr-1" />
            {{ isRecording ? 'Stop Recording' : 'Start Recording' }}
          </button>
          
          <button
            @click="toggleFullscreen"
            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
            title="Toggle Fullscreen"
          >
            <Icon name="maximize" class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Meeting Viewer -->
    <div class="meeting-viewer relative bg-black" :style="{ height: viewerHeight }">
      <!-- Jitsi Meet Embed -->
      <div
        v-if="event.meeting_platform === 'jitsi' && event.meeting_embed_allowed && embedUrl"
        class="w-full h-full"
      >
        <iframe
          ref="jitsiFrame"
          :src="embedUrl"
          class="w-full h-full border-0"
          :allow="iframePermissions"
          allowfullscreen
          @load="onIframeLoad"
        />
      </div>

      <!-- External Platform Link -->
      <div
        v-else
        class="flex flex-col items-center justify-center h-full text-white space-y-6"
      >
        <div class="text-center">
          <Icon :name="getPlatformIcon(event.meeting_platform)" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
          <h3 class="text-xl font-semibold mb-2">Join {{ getPlatformName(event.meeting_platform) }} Meeting</h3>
          <p class="text-gray-300 mb-6 max-w-md">
            This meeting is hosted on {{ getPlatformName(event.meeting_platform) }}. 
            Click the button below to join in a new window.
          </p>
        </div>

        <div class="space-y-4">
          <a
            :href="meetingCredentials.meeting_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
          >
            <Icon name="external-link" class="w-5 h-5 mr-2" />
            Join Meeting
          </a>

          <div v-if="meetingCredentials.password" class="text-center">
            <p class="text-sm text-gray-300 mb-1">Meeting Password:</p>
            <code class="bg-gray-800 px-3 py-1 rounded text-green-400 font-mono">
              {{ meetingCredentials.password }}
            </code>
          </div>
        </div>
      </div>

      <!-- Loading Overlay -->
      <div
        v-if="isLoading"
        class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
      >
        <div class="text-center text-white">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
          <p>Loading meeting...</p>
        </div>
      </div>

      <!-- Error State -->
      <div
        v-if="error"
        class="absolute inset-0 bg-red-50 flex items-center justify-center"
      >
        <div class="text-center text-red-600 max-w-md">
          <Icon name="alert-circle" class="w-12 h-12 mx-auto mb-4" />
          <h3 class="text-lg font-semibold mb-2">Unable to Load Meeting</h3>
          <p class="text-sm mb-4">{{ error }}</p>
          <button
            @click="retryLoad"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    </div>

    <!-- Meeting Controls -->
    <div class="meeting-controls bg-gray-50 p-4 rounded-b-lg border-t">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <!-- Meeting Info -->
          <div class="text-sm text-gray-600">
            <span class="font-medium">Meeting ID:</span>
            <code class="ml-1 bg-gray-200 px-2 py-1 rounded font-mono">
              {{ getMeetingId() }}
            </code>
          </div>
          
          <!-- Copy Link -->
          <button
            @click="copyMeetingLink"
            class="flex items-center space-x-1 text-sm text-blue-600 hover:text-blue-700"
          >
            <Icon name="copy" class="w-4 h-4" />
            <span>Copy Link</span>
          </button>
        </div>

        <!-- Additional Controls -->
        <div class="flex items-center space-x-2">
          <button
            v-if="canManageEvent"
            @click="showMeetingSettings = true"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors"
          >
            <Icon name="settings" class="w-4 h-4 mr-1" />
            Settings
          </button>
          
          <button
            @click="refreshMeeting"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors"
          >
            <Icon name="refresh-cw" class="w-4 h-4 mr-1" />
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Meeting Settings Modal -->
    <div
      v-if="showMeetingSettings"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showMeetingSettings = false"
    >
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Meeting Settings</h3>
        
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700">Enable Chat</label>
            <input
              v-model="localSettings.chat_enabled"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
          </div>
          
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700">Enable Screen Sharing</label>
            <input
              v-model="localSettings.screen_sharing_enabled"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
          </div>
          
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700">Enable Recording</label>
            <input
              v-model="localSettings.recording_enabled"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
          </div>
          
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-gray-700">Waiting Room</label>
            <input
              v-model="localSettings.waiting_room_enabled"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
          </div>
        </div>
        
        <div class="flex justify-end space-x-3 mt-6">
          <button
            @click="showMeetingSettings = false"
            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="saveMeetingSettings"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import Icon from '@/Components/Icon.vue'

interface Event {
  id: number
  title: string
  meeting_platform: string
  meeting_url?: string
  meeting_embed_allowed: boolean
  recording_enabled: boolean
  chat_enabled: boolean
  screen_sharing_enabled: boolean
  waiting_room_enabled: boolean
  current_attendees: number
  jitsi_room_id?: string
}

interface MeetingCredentials {
  platform: string
  meeting_url: string
  password?: string
  room_id?: string
  embed_url?: string
}

interface Props {
  event: Event
  meetingCredentials: MeetingCredentials
  canManageEvent?: boolean
  canRecord?: boolean
  height?: string
}

const props = withDefaults(defineProps<Props>(), {
  canManageEvent: false,
  canRecord: false,
  height: '500px'
})

const emit = defineEmits<{
  settingsUpdated: [settings: any]
  recordingToggled: [recording: boolean]
}>()

const { showToast } = useToast()

// Refs
const jitsiFrame = ref<HTMLIFrameElement>()
const isLoading = ref(true)
const error = ref<string>()
const isRecording = ref(false)
const showMeetingSettings = ref(false)
const localSettings = ref({
  chat_enabled: props.event.chat_enabled,
  screen_sharing_enabled: props.event.screen_sharing_enabled,
  recording_enabled: props.event.recording_enabled,
  waiting_room_enabled: props.event.waiting_room_enabled,
})

// Computed
const viewerHeight = computed(() => props.height)

const embedUrl = computed(() => {
  if (props.event.meeting_platform === 'jitsi' && props.meetingCredentials.embed_url) {
    return props.meetingCredentials.embed_url
  }
  return null
})

const iframePermissions = computed(() => {
  return 'camera; microphone; display-capture; fullscreen; web-share'
})

// Methods
const getPlatformIcon = (platform: string): string => {
  const icons = {
    jitsi: 'video',
    zoom: 'video',
    teams: 'users',
    google_meet: 'video',
    webex: 'video',
    other: 'external-link'
  }
  return icons[platform] || 'video'
}

const getPlatformName = (platform: string): string => {
  const names = {
    jitsi: 'Jitsi Meet',
    zoom: 'Zoom',
    teams: 'Microsoft Teams',
    google_meet: 'Google Meet',
    webex: 'WebEx',
    other: 'External Platform'
  }
  return names[platform] || 'Virtual Meeting'
}

const getMeetingId = (): string => {
  if (props.event.jitsi_room_id) {
    return props.event.jitsi_room_id
  }
  if (props.meetingCredentials.room_id) {
    return props.meetingCredentials.room_id
  }
  return 'N/A'
}

const onIframeLoad = () => {
  isLoading.value = false
  error.value = undefined
}

const toggleFullscreen = () => {
  const viewer = document.querySelector('.meeting-viewer')
  if (viewer) {
    if (document.fullscreenElement) {
      document.exitFullscreen()
    } else {
      viewer.requestFullscreen()
    }
  }
}

const toggleRecording = () => {
  isRecording.value = !isRecording.value
  emit('recordingToggled', isRecording.value)
  
  showToast({
    type: 'success',
    message: isRecording.value ? 'Recording started' : 'Recording stopped'
  })
}

const copyMeetingLink = async () => {
  try {
    await navigator.clipboard.writeText(props.meetingCredentials.meeting_url)
    showToast({
      type: 'success',
      message: 'Meeting link copied to clipboard'
    })
  } catch (err) {
    showToast({
      type: 'error',
      message: 'Failed to copy meeting link'
    })
  }
}

const refreshMeeting = () => {
  isLoading.value = true
  error.value = undefined
  
  if (jitsiFrame.value) {
    jitsiFrame.value.src = jitsiFrame.value.src
  }
  
  setTimeout(() => {
    isLoading.value = false
  }, 2000)
}

const retryLoad = () => {
  error.value = undefined
  refreshMeeting()
}

const saveMeetingSettings = () => {
  emit('settingsUpdated', localSettings.value)
  showMeetingSettings.value = false
  
  showToast({
    type: 'success',
    message: 'Meeting settings updated'
  })
}

// Lifecycle
onMounted(() => {
  // Set loading timeout
  setTimeout(() => {
    if (isLoading.value) {
      error.value = 'Meeting failed to load. Please try refreshing.'
      isLoading.value = false
    }
  }, 10000)
})

// Watch for prop changes
watch(() => props.event, (newEvent) => {
  localSettings.value = {
    chat_enabled: newEvent.chat_enabled,
    screen_sharing_enabled: newEvent.screen_sharing_enabled,
    recording_enabled: newEvent.recording_enabled,
    waiting_room_enabled: newEvent.waiting_room_enabled,
  }
}, { deep: true })
</script>

<style scoped>
.virtual-event-viewer {
  @apply border border-gray-200 rounded-lg overflow-hidden shadow-sm;
}

.meeting-viewer {
  position: relative;
  background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
}

.meeting-header {
  background: linear-gradient(to right, #f8fafc, #f1f5f9);
}

.meeting-controls {
  background: linear-gradient(to right, #f1f5f9, #f8fafc);
}

/* Fullscreen styles */
.meeting-viewer:fullscreen {
  @apply bg-black;
}

.meeting-viewer:fullscreen iframe {
  @apply w-full h-full;
}
</style>