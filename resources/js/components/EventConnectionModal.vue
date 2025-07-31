<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-900">Connect with {{ recommendedUser?.name }}</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="createConnection" class="space-y-4">
        <!-- Connection Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Connection Type</label>
          <select
            v-model="form.connection_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="met_at_event">Met at Event</option>
            <option value="mutual_interest">Mutual Interest</option>
            <option value="follow_up">Follow-up Connection</option>
            <option value="collaboration">Collaboration Opportunity</option>
          </select>
        </div>

        <!-- Connection Note -->
        <div>
          <label for="connection_note" class="block text-sm font-medium text-gray-700 mb-2">
            Connection Note
          </label>
          <textarea
            id="connection_note"
            v-model="form.connection_note"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Add a personal note about your connection..."
          ></textarea>
        </div>

        <!-- Shared Interests -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Shared Interests</label>
          <div class="space-y-2">
            <div v-for="(interest, index) in form.shared_interests" :key="index" class="flex items-center space-x-2">
              <input
                v-model="form.shared_interests[index]"
                type="text"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter a shared interest..."
              >
              <button
                type="button"
                @click="removeInterest(index)"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
            <button
              type="button"
              @click="addInterest"
              class="text-blue-600 hover:text-blue-800 text-sm"
            >
              + Add shared interest
            </button>
          </div>
        </div>

        <!-- Follow-up Request -->
        <div class="flex items-center">
          <input
            id="follow_up_requested"
            v-model="form.follow_up_requested"
            type="checkbox"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          >
          <label for="follow_up_requested" class="ml-2 block text-sm text-gray-900">
            Request follow-up after the event
          </label>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ loading ? 'Connecting...' : 'Create Connection' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import axios from 'axios'

interface Props {
  show: boolean
  event: any
  recommendedUser: any
}

interface Emits {
  (e: 'close'): void
  (e: 'connected', connection: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const loading = ref(false)

const form = reactive({
  connection_type: 'met_at_event',
  connection_note: '',
  shared_interests: [''],
  follow_up_requested: false
})

const addInterest = () => {
  form.shared_interests.push('')
}

const removeInterest = (index: number) => {
  form.shared_interests.splice(index, 1)
}

const createConnection = async () => {
  loading.value = true

  try {
    // Filter out empty interests
    const interests = form.shared_interests.filter(i => i.trim() !== '')
    
    const response = await axios.post(`/api/events/${props.event.id}/connections`, {
      connected_user_id: props.recommendedUser.id,
      connection_type: form.connection_type,
      connection_note: form.connection_note || null,
      shared_interests: interests.length > 0 ? interests : null,
      follow_up_requested: form.follow_up_requested
    })

    emit('connected', response.data.connection)
    emit('close')
    resetForm()
  } catch (error) {
    console.error('Failed to create connection:', error)
    alert('Failed to create connection. Please try again.')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  Object.assign(form, {
    connection_type: 'met_at_event',
    connection_note: '',
    shared_interests: [''],
    follow_up_requested: false
  })
}

// Reset form when modal is closed
watch(() => props.show, (newShow) => {
  if (!newShow) {
    resetForm()
  }
})
</script>