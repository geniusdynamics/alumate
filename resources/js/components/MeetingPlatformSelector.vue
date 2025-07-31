<template>
  <div class="meeting-platform-selector">
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-3">
        Virtual Meeting Platform
      </label>
      
      <!-- Platform Selection -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="platform in platforms"
          :key="platform.value"
          @click="selectPlatform(platform.value)"
          :class="[
            'relative p-4 border-2 rounded-lg cursor-pointer transition-all',
            selectedPlatform === platform.value
              ? 'border-blue-500 bg-blue-50'
              : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
          ]"
        >
          <div class="flex items-center space-x-3">
            <Icon :name="platform.icon" class="w-6 h-6 text-gray-600" />
            <div>
              <h3 class="font-medium text-gray-900">{{ platform.name }}</h3>
              <p class="text-sm text-gray-500">{{ platform.description }}</p>
            </div>
          </div>
          
          <!-- Selected indicator -->
          <div
            v-if="selectedPlatform === platform.value"
            class="absolute top-2 right-2"
          >
            <Icon name="check-circle" class="w-5 h-5 text-blue-500" />
          </div>
        </div>
      </div>
    </div>

    <!-- Jitsi Meet Configuration -->
    <div v-if="selectedPlatform === 'jitsi'" class="space-y-4">
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start space-x-3">
          <Icon name="info" class="w-5 h-5 text-blue-500 mt-0.5" />
          <div>
            <h4 class="font-medium text-blue-900">Automatic Jitsi Meet Setup</h4>
            <p class="text-sm text-blue-700 mt-1">
              A Jitsi Meet room will be automatically created for your event. 
              Attendees can join directly from the event page without additional software.
            </p>
          </div>
        </div>
      </div>

      <!-- Jitsi Settings -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div>
            <label class="text-sm font-medium text-gray-700">Waiting Room</label>
            <p class="text-xs text-gray-500">Require host approval to join</p>
          </div>
          <input
            v-model="jitsiSettings.waiting_room_enabled"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
        </div>

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div>
            <label class="text-sm font-medium text-gray-700">Enable Chat</label>
            <p class="text-xs text-gray-500">Allow text messaging</p>
          </div>
          <input
            v-model="jitsiSettings.chat_enabled"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
        </div>

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div>
            <label class="text-sm font-medium text-gray-700">Screen Sharing</label>
            <p class="text-xs text-gray-500">Allow screen sharing</p>
          </div>
          <input
            v-model="jitsiSettings.screen_sharing_enabled"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
        </div>

        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div>
            <label class="text-sm font-medium text-gray-700">Recording</label>
            <p class="text-xs text-gray-500">Enable meeting recording</p>
          </div>
          <input
            v-model="jitsiSettings.recording_enabled"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- Meeting Password -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Meeting Password (Optional)
        </label>
        <input
          v-model="jitsiSettings.meeting_password"
          type="text"
          placeholder="Enter password for additional security"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
        <p class="text-xs text-gray-500 mt-1">
          Leave empty for password-free access
        </p>
      </div>
    </div>

    <!-- Manual Meeting URL -->
    <div v-else class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Meeting URL
        </label>
        <input
          v-model="manualSettings.meeting_url"
          type="url"
          placeholder="https://zoom.us/j/123456789 or https://meet.google.com/abc-defg-hij"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @blur="validateMeetingUrl"
        />
        
        <!-- URL Validation -->
        <div v-if="urlValidation.checked" class="mt-2">
          <div
            v-if="urlValidation.valid"
            class="flex items-center space-x-2 text-sm text-green-600"
          >
            <Icon name="check-circle" class="w-4 h-4" />
            <span>Valid {{ urlValidation.platform }} meeting URL</span>
          </div>
          <div
            v-else
            class="flex items-center space-x-2 text-sm text-red-600"
          >
            <Icon name="alert-circle" class="w-4 h-4" />
            <span>{{ urlValidation.error || 'Invalid meeting URL format' }}</span>
          </div>
        </div>
      </div>

      <!-- Meeting Password -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Meeting Password/PIN (Optional)
        </label>
        <input
          v-model="manualSettings.meeting_password"
          type="text"
          placeholder="Enter meeting password or PIN"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <!-- Meeting Instructions -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Join Instructions (Optional)
        </label>
        <textarea
          v-model="manualSettings.meeting_instructions"
          rows="3"
          placeholder="Additional instructions for joining the meeting..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <!-- Platform-specific help -->
      <div v-if="urlValidation.valid && urlValidation.platform" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="font-medium text-gray-900 mb-2">
          {{ getPlatformName(urlValidation.platform) }} Instructions
        </h4>
        <div class="text-sm text-gray-600 space-y-1">
          <p v-for="instruction in getPlatformInstructions(urlValidation.platform)" :key="instruction">
            {{ instruction }}
          </p>
        </div>
      </div>
    </div>

    <!-- Preview -->
    <div v-if="hasValidConfiguration" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
      <h4 class="font-medium text-green-900 mb-2">Configuration Preview</h4>
      <div class="text-sm text-green-700 space-y-1">
        <p><strong>Platform:</strong> {{ getPlatformName(selectedPlatform) }}</p>
        <p v-if="selectedPlatform === 'jitsi'">
          <strong>Room:</strong> Will be auto-generated based on event title
        </p>
        <p v-else-if="manualSettings.meeting_url">
          <strong>URL:</strong> {{ manualSettings.meeting_url }}
        </p>
        <p v-if="getCurrentPassword()">
          <strong>Password:</strong> {{ getCurrentPassword() }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import Icon from '@/Components/Icon.vue'

interface JitsiSettings {
  waiting_room_enabled: boolean
  chat_enabled: boolean
  screen_sharing_enabled: boolean
  recording_enabled: boolean
  meeting_password: string
}

interface ManualSettings {
  meeting_url: string
  meeting_password: string
  meeting_instructions: string
}

interface UrlValidation {
  checked: boolean
  valid: boolean
  platform?: string
  error?: string
}

interface Props {
  modelValue?: {
    platform: string
    settings: any
  }
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: { platform: string; settings: any }]
}>()

const { showToast } = useToast()

// Platform options
const platforms = [
  {
    value: 'jitsi',
    name: 'Jitsi Meet (Recommended)',
    description: 'Free, secure, and embedded directly in your event',
    icon: 'video'
  },
  {
    value: 'zoom',
    name: 'Zoom',
    description: 'Popular video conferencing platform',
    icon: 'video'
  },
  {
    value: 'teams',
    name: 'Microsoft Teams',
    description: 'Microsoft\'s collaboration platform',
    icon: 'users'
  },
  {
    value: 'google_meet',
    name: 'Google Meet',
    description: 'Google\'s video conferencing service',
    icon: 'video'
  },
  {
    value: 'webex',
    name: 'WebEx',
    description: 'Cisco\'s video conferencing solution',
    icon: 'video'
  },
  {
    value: 'other',
    name: 'Other Platform',
    description: 'Use any other meeting platform',
    icon: 'external-link'
  }
]

// Reactive state
const selectedPlatform = ref<string>('jitsi')
const jitsiSettings = ref<JitsiSettings>({
  waiting_room_enabled: false,
  chat_enabled: true,
  screen_sharing_enabled: true,
  recording_enabled: false,
  meeting_password: ''
})
const manualSettings = ref<ManualSettings>({
  meeting_url: '',
  meeting_password: '',
  meeting_instructions: ''
})
const urlValidation = ref<UrlValidation>({
  checked: false,
  valid: false
})

// Computed
const hasValidConfiguration = computed(() => {
  if (selectedPlatform.value === 'jitsi') {
    return true // Jitsi is always valid as it's auto-generated
  }
  return urlValidation.value.valid && manualSettings.value.meeting_url
})

// Methods
const selectPlatform = (platform: string) => {
  selectedPlatform.value = platform
  updateModelValue()
}

const validateMeetingUrl = async () => {
  if (!manualSettings.value.meeting_url) {
    urlValidation.value = { checked: false, valid: false }
    return
  }

  try {
    // This would typically call an API endpoint to validate the URL
    const validation = await validateUrl(manualSettings.value.meeting_url)
    urlValidation.value = {
      checked: true,
      valid: validation.valid,
      platform: validation.platform,
      error: validation.error
    }
  } catch (error) {
    urlValidation.value = {
      checked: true,
      valid: false,
      error: 'Failed to validate URL'
    }
  }
  
  updateModelValue()
}

const validateUrl = async (url: string) => {
  // Mock validation - in real app, this would call the JitsiMeetService
  const patterns = {
    zoom: /zoom\.us\/j\/\d+/,
    teams: /(teams\.microsoft\.com|teams\.live\.com)/,
    google_meet: /meet\.google\.com\/[a-z-]+/,
    webex: /webex\.com/,
    jitsi: /meet\.jit\.si\/.+/
  }

  for (const [platform, pattern] of Object.entries(patterns)) {
    if (pattern.test(url)) {
      return { valid: true, platform }
    }
  }

  // Check if it's a valid URL
  try {
    new URL(url)
    return { valid: true, platform: 'other' }
  } catch {
    return { valid: false, error: 'Invalid URL format' }
  }
}

const getPlatformName = (platform: string): string => {
  const names = {
    jitsi: 'Jitsi Meet',
    zoom: 'Zoom',
    teams: 'Microsoft Teams',
    google_meet: 'Google Meet',
    webex: 'WebEx',
    other: 'Other Platform'
  }
  return names[platform] || 'Unknown Platform'
}

const getPlatformInstructions = (platform: string): string[] => {
  const instructions = {
    zoom: [
      '1. Click the meeting link',
      '2. Download Zoom client if prompted',
      '3. Enter meeting ID and password if required',
      '4. Join with audio and video'
    ],
    teams: [
      '1. Click the meeting link',
      '2. Choose to join via web browser or Teams app',
      '3. Enter your name',
      '4. Join the meeting'
    ],
    google_meet: [
      '1. Click the meeting link',
      '2. Sign in with Google account if required',
      '3. Allow camera and microphone access',
      '4. Join the meeting'
    ],
    webex: [
      '1. Click the meeting link',
      '2. Enter your name and email',
      '3. Join via browser or download WebEx app',
      '4. Connect audio and video'
    ]
  }
  return instructions[platform] || ['Click the meeting link to join']
}

const getCurrentPassword = (): string => {
  if (selectedPlatform.value === 'jitsi') {
    return jitsiSettings.value.meeting_password
  }
  return manualSettings.value.meeting_password
}

const updateModelValue = () => {
  const value = {
    platform: selectedPlatform.value,
    settings: selectedPlatform.value === 'jitsi' ? jitsiSettings.value : manualSettings.value
  }
  emit('update:modelValue', value)
}

// Initialize from props
if (props.modelValue) {
  selectedPlatform.value = props.modelValue.platform
  if (props.modelValue.platform === 'jitsi') {
    jitsiSettings.value = { ...jitsiSettings.value, ...props.modelValue.settings }
  } else {
    manualSettings.value = { ...manualSettings.value, ...props.modelValue.settings }
  }
}

// Watch for changes
watch([selectedPlatform, jitsiSettings, manualSettings], updateModelValue, { deep: true })
</script>

<style scoped>
.meeting-platform-selector {
  @apply space-y-6;
}

/* Platform selection cards */
.platform-card {
  transition: all 0.2s ease-in-out;
}

.platform-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Settings grid */
.settings-grid {
  display: grid;
  gap: 1rem;
}

@media (min-width: 768px) {
  .settings-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>