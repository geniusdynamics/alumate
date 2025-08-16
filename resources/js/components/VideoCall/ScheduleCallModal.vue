<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between p-6 border-b">
        <h3 class="text-lg font-semibold text-gray-900">Schedule Call</h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitSchedule" class="p-6">
        <div class="mb-4">
          <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
            Call Title
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="e.g., Career Discussion, Project Review"
            required
          >
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
              Date
            </label>
            <input
              id="date"
              v-model="form.date"
              type="date"
              :min="minDate"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            >
          </div>
          <div>
            <label for="time" class="block text-sm font-medium text-gray-700 mb-2">
              Time
            </label>
            <input
              id="time"
              v-model="form.time"
              type="time"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            >
          </div>
        </div>

        <div class="mb-4">
          <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
            Duration
          </label>
          <select
            id="duration"
            v-model="form.duration"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="">Select duration</option>
            <option value="15">15 minutes</option>
            <option value="30">30 minutes</option>
            <option value="45">45 minutes</option>
            <option value="60">60 minutes</option>
            <option value="90">90 minutes</option>
          </select>
        </div>

        <div class="mb-4">
          <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
            Call Type
          </label>
          <select
            id="type"
            v-model="form.type"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="">Select type</option>
            <option value="mentorship">Mentorship</option>
            <option value="career-advice">Career Advice</option>
            <option value="networking">Networking</option>
            <option value="project-discussion">Project Discussion</option>
            <option value="interview-prep">Interview Preparation</option>
            <option value="coffee-chat">Coffee Chat</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div class="mb-4">
          <label for="agenda" class="block text-sm font-medium text-gray-700 mb-2">
            Agenda/Topics to Discuss
          </label>
          <textarea
            id="agenda"
            v-model="form.agenda"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="What would you like to discuss during this call?"
            required
          ></textarea>
        </div>

        <div class="mb-4">
          <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
            Timezone
          </label>
          <select
            id="timezone"
            v-model="form.timezone"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="">Select timezone</option>
            <option value="America/New_York">Eastern Time (ET)</option>
            <option value="America/Chicago">Central Time (CT)</option>
            <option value="America/Denver">Mountain Time (MT)</option>
            <option value="America/Los_Angeles">Pacific Time (PT)</option>
            <option value="UTC">UTC</option>
          </select>
        </div>

        <div class="mb-6">
          <label class="flex items-center">
            <input
              v-model="form.sendReminder"
              type="checkbox"
              class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
            >
            <span class="ml-2 text-sm text-gray-700">Send reminder 24 hours before the call</span>
          </label>
        </div>

        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isSubmitting ? 'Scheduling...' : 'Schedule Call' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'

interface Props {
  isOpen: boolean
  participantId?: number
  requestId?: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  submit: [data: any]
}>()

const isSubmitting = ref(false)

const form = reactive({
  title: '',
  date: '',
  time: '',
  duration: '',
  type: '',
  agenda: '',
  timezone: 'America/New_York',
  sendReminder: true
})

const minDate = computed(() => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  return tomorrow.toISOString().split('T')[0]
})

const close = () => {
  emit('close')
  resetForm()
}

const resetForm = () => {
  form.title = ''
  form.date = ''
  form.time = ''
  form.duration = ''
  form.type = ''
  form.agenda = ''
  form.timezone = 'America/New_York'
  form.sendReminder = true
}

const submitSchedule = async () => {
  isSubmitting.value = true
  
  try {
    const scheduleData = {
      participantId: props.participantId,
      requestId: props.requestId,
      title: form.title,
      scheduledAt: `${form.date}T${form.time}:00`,
      duration: parseInt(form.duration),
      type: form.type,
      agenda: form.agenda,
      timezone: form.timezone,
      sendReminder: form.sendReminder
    }
    
    emit('submit', scheduleData)
    close()
  } catch (error) {
    console.error('Error scheduling call:', error)
  } finally {
    isSubmitting.value = false
  }
}
</script>