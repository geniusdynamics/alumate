<template>
  <div class="meeting-credentials">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <Icon :name="getPlatformIcon(credentials.platform)" class="w-6 h-6 text-blue-600" />
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Meeting Details</h3>
          <p class="text-sm text-gray-600">{{ getPlatformName(credentials.platform) }}</p>
        </div>
      </div>
      
      <div class="flex items-center space-x-2">
        <button
          @click="copyAllCredentials"
          class="flex items-center space-x-2 px-3 py-2 text-sm text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
        >
          <Icon name="copy" class="w-4 h-4" />
          <span>Copy All</span>
        </button>
        
        <button
          @click="shareCredentials"
          class="flex items-center space-x-2 px-3 py-2 text-sm text-green-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors"
        >
          <Icon name="share" class="w-4 h-4" />
          <span>Share</span>
        </button>
      </div>
    </div>

    <!-- Meeting URL -->
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
      <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-medium text-gray-700">Meeting Link</label>
        <button
          @click="copyToClipboard(credentials.url, 'Meeting link copied!')"
          class="text-xs text-blue-600 hover:text-blue-700 flex items-center space-x-1"
        >
          <Icon name="copy" class="w-3 h-3" />
          <span>Copy</span>
        </button>
      </div>
      <div class="flex items-center space-x-3">
        <code class="flex-1 bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-800 break-all">
          {{ credentials.url }}
        </code>
        <a
          :href="credentials.url"
          target="_blank"
          rel="noopener noreferrer"
          class="flex-shrink-0 p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded transition-colors"
          title="Open in new tab"
        >
          <Icon name="external-link" class="w-4 h-4" />
        </a>
      </div>
    </div>

    <!-- Meeting ID/Room ID -->
    <div v-if="credentials.room_id || credentials.meeting_id" class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
      <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-medium text-gray-700">
          {{ credentials.platform === 'jitsi' ? 'Room ID' : 'Meeting ID' }}
        </label>
        <button
          @click="copyToClipboard(credentials.room_id || credentials.meeting_id, 'Meeting ID copied!')"
          class="text-xs text-blue-600 hover:text-blue-700 flex items-center space-x-1"
        >
          <Icon name="copy" class="w-3 h-3" />
          <span>Copy</span>
        </button>
      </div>
      <code class="block bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-800">
        {{ credentials.room_id || credentials.meeting_id }}
      </code>
    </div>

    <!-- Meeting Password -->
    <div v-if="credentials.password" class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
      <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-medium text-gray-700">Password</label>
        <div class="flex items-center space-x-2">
          <button
            @click="togglePasswordVisibility"
            class="text-xs text-gray-600 hover:text-gray-700 flex items-center space-x-1"
          >
            <Icon :name="showPassword ? 'eye-off' : 'eye'" class="w-3 h-3" />
            <span>{{ showPassword ? 'Hide' : 'Show' }}</span>
          </button>
          <button
            @click="copyToClipboard(credentials.password, 'Password copied!')"
            class="text-xs text-blue-600 hover:text-blue-700 flex items-center space-x-1"
          >
            <Icon name="copy" class="w-3 h-3" />
            <span>Copy</span>
          </button>
        </div>
      </div>
      <code class="block bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-800">
        {{ showPassword ? credentials.password : '•'.repeat(credentials.password.length) }}
      </code>
    </div>

    <!-- Dial-in Numbers (for platforms that support it) -->
    <div v-if="credentials.dial_in" class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
      <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-medium text-gray-700">Dial-in Numbers</label>
        <button
          @click="copyDialInNumbers"
          class="text-xs text-blue-600 hover:text-blue-700 flex items-center space-x-1"
        >
          <Icon name="copy" class="w-3 h-3" />
          <span>Copy</span>
        </button>
      </div>
      <div class="space-y-2">
        <div v-for="(number, country) in credentials.dial_in" :key="country" class="flex items-center justify-between">
          <span class="text-sm text-gray-600">{{ country }}:</span>
          <code class="text-sm font-mono text-gray-800">{{ number }}</code>
        </div>
      </div>
    </div>

    <!-- Meeting Features -->
    <div v-if="credentials.features" class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-3">Meeting Features</label>
      <div class="grid grid-cols-2 gap-3">
        <div
          v-for="(enabled, feature) in credentials.features"
          :key="feature"
          class="flex items-center space-x-2"
        >
          <Icon
            :name="enabled ? 'check-circle' : 'x-circle'"
            :class="[
              'w-4 h-4',
              enabled ? 'text-green-500' : 'text-gray-400'
            ]"
          />
          <span class="text-sm text-gray-700 capitalize">
            {{ feature.replace('_', ' ') }}
          </span>
        </div>
      </div>
    </div>

    <!-- Instructions -->
    <div v-if="credentials.instructions" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
      <div class="flex items-start space-x-3">
        <Icon name="info" class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" />
        <div>
          <h4 class="font-medium text-blue-900 mb-2">How to Join</h4>
          <div class="text-sm text-blue-800 whitespace-pre-line">{{ credentials.instructions }}</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-wrap gap-3">
      <a
        :href="credentials.url"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      >
        <Icon name="video" class="w-4 h-4" />
        <span>Join Meeting</span>
      </a>
      
      <button
        @click="addToCalendar"
        class="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
      >
        <Icon name="calendar" class="w-4 h-4" />
        <span>Add to Calendar</span>
      </button>
      
      <button
        v-if="canTest"
        @click="testConnection"
        class="flex items-center space-x-2 px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors"
      >
        <Icon name="wifi" class="w-4 h-4" />
        <span>Test Connection</span>
      </button>
    </div>

    <!-- QR Code Modal -->
    <div
      v-if="showQRCode"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="showQRCode = false"
    >
      <div class="bg-white rounded-lg p-6 w-full max-w-sm">
        <div class="text-center">
          <h3 class="text-lg font-semibold mb-4">Scan to Join</h3>
          <div class="bg-gray-100 p-4 rounded-lg mb-4">
            <!-- QR Code would be generated here -->
            <div class="w-48 h-48 bg-gray-200 rounded flex items-center justify-center mx-auto">
              <Icon name="qr-code" class="w-16 h-16 text-gray-400" />
            </div>
          </div>
          <p class="text-sm text-gray-600 mb-4">
            Scan this QR code with your mobile device to join the meeting
          </p>
          <button
            @click="showQRCode = false"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from '@/composables/useToast'
import Icon from '@/Components/Icon.vue'

interface MeetingCredentials {
  platform: string
  url: string
  password?: string
  room_id?: string
  meeting_id?: string
  instructions?: string
  dial_in?: Record<string, string>
  features?: Record<string, boolean>
  embed_allowed?: boolean
}

interface Props {
  credentials: MeetingCredentials
  eventTitle?: string
  eventDate?: string
  canTest?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canTest: true
})

const emit = defineEmits<{
  testConnection: []
  addToCalendar: [credentials: MeetingCredentials]
}>()

const { showToast } = useToast()

// Reactive state
const showPassword = ref(false)
const showQRCode = ref(false)

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

const copyToClipboard = async (text: string, successMessage: string) => {
  try {
    await navigator.clipboard.writeText(text)
    showToast({
      type: 'success',
      message: successMessage
    })
  } catch (err) {
    showToast({
      type: 'error',
      message: 'Failed to copy to clipboard'
    })
  }
}

const copyAllCredentials = async () => {
  let text = `Meeting Details - ${getPlatformName(props.credentials.platform)}\n\n`
  text += `Meeting Link: ${props.credentials.url}\n`
  
  if (props.credentials.room_id || props.credentials.meeting_id) {
    text += `Meeting ID: ${props.credentials.room_id || props.credentials.meeting_id}\n`
  }
  
  if (props.credentials.password) {
    text += `Password: ${props.credentials.password}\n`
  }
  
  if (props.credentials.dial_in) {
    text += '\nDial-in Numbers:\n'
    Object.entries(props.credentials.dial_in).forEach(([country, number]) => {
      text += `${country}: ${number}\n`
    })
  }
  
  if (props.credentials.instructions) {
    text += `\nInstructions:\n${props.credentials.instructions}`
  }

  await copyToClipboard(text, 'All meeting details copied!')
}

const copyDialInNumbers = async () => {
  if (!props.credentials.dial_in) return
  
  let text = 'Dial-in Numbers:\n'
  Object.entries(props.credentials.dial_in).forEach(([country, number]) => {
    text += `${country}: ${number}\n`
  })
  
  await copyToClipboard(text, 'Dial-in numbers copied!')
}

const shareCredentials = async () => {
  if (navigator.share) {
    try {
      await navigator.share({
        title: `${props.eventTitle || 'Meeting'} - ${getPlatformName(props.credentials.platform)}`,
        text: `Join the meeting: ${props.credentials.url}`,
        url: props.credentials.url
      })
    } catch (err) {
      // Fallback to copy
      await copyAllCredentials()
    }
  } else {
    await copyAllCredentials()
  }
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const addToCalendar = () => {
  emit('addToCalendar', props.credentials)
}

const testConnection = () => {
  emit('testConnection')
  showToast({
    type: 'info',
    message: 'Testing connection...'
  })
}
</script>

<style scoped>
.meeting-credentials {
  @apply space-y-4;
}

/* Credential cards */
.credential-card {
  @apply bg-white border border-gray-200 rounded-lg p-4;
  transition: all 0.2s ease-in-out;
}

.credential-card:hover {
  @apply border-gray-300 shadow-sm;
}

/* Code blocks */
code {
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
}

/* Feature grid */
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .meeting-credentials {
    @apply space-y-3;
  }
  
  .credential-card {
    @apply p-3;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>