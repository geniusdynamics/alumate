<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75" @click="$emit('close')"></div>
      </div>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="createInteraction">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                  Log Donor Interaction
                </h3>
                
                <div class="space-y-4">
                  <div>
                    <label for="donor_profile_id" class="block text-sm font-medium text-gray-700">
                      Select Donor
                    </label>
                    <select
                      id="donor_profile_id"
                      v-model="form.donor_profile_id"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      required
                    >
                      <option value="">Select a donor...</option>
                      <option v-for="donor in donors" :key="donor.id" :value="donor.id">
                        {{ donor.user?.name }} ({{ donor.donor_status }})
                      </option>
                    </select>
                  </div>

                  <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">
                      Interaction Type
                    </label>
                    <select
                      id="type"
                      v-model="form.type"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      required
                    >
                      <option value="call">Phone Call</option>
                      <option value="email">Email</option>
                      <option value="meeting">Meeting</option>
                      <option value="event">Event</option>
                      <option value="letter">Letter</option>
                      <option value="other">Other</option>
                    </select>
                  </div>

                  <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">
                      Subject
                    </label>
                    <input
                      id="subject"
                      v-model="form.subject"
                      type="text"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Brief subject of the interaction"
                      required
                    />
                  </div>

                  <div>
                    <label for="interaction_date" class="block text-sm font-medium text-gray-700">
                      Interaction Date
                    </label>
                    <input
                      id="interaction_date"
                      v-model="form.interaction_date"
                      type="datetime-local"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      required
                    />
                  </div>

                  <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">
                      Notes
                    </label>
                    <textarea
                      id="notes"
                      v-model="form.notes"
                      rows="4"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Detailed notes about the interaction..."
                      required
                    ></textarea>
                  </div>

                  <div>
                    <label for="outcome" class="block text-sm font-medium text-gray-700">
                      Outcome
                    </label>
                    <select
                      id="outcome"
                      v-model="form.outcome"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="">Select outcome...</option>
                      <option value="positive">Positive</option>
                      <option value="neutral">Neutral</option>
                      <option value="negative">Negative</option>
                      <option value="follow_up_needed">Follow-up Needed</option>
                    </select>
                  </div>

                  <div>
                    <label for="follow_up_date" class="block text-sm font-medium text-gray-700">
                      Follow-up Date (Optional)
                    </label>
                    <input
                      id="follow_up_date"
                      v-model="form.follow_up_date"
                      type="date"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              type="submit"
              :disabled="loading"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              {{ loading ? 'Logging...' : 'Log Interaction' }}
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

// Emits
const emit = defineEmits<{
  close: []
  created: [interaction: any]
}>()

// Types
interface DonorProfile {
  id: number
  user?: {
    name: string
  }
  donor_status: string
}

// Reactive state
const loading = ref(false)
const donors = ref<DonorProfile[]>([])

const form = ref({
  donor_profile_id: '',
  type: 'call',
  subject: '',
  interaction_date: new Date().toISOString().slice(0, 16),
  notes: '',
  outcome: '',
  follow_up_date: ''
})

// Methods
const loadDonors = async () => {
  try {
    const response = await axios.get('/api/donor-profiles')
    donors.value = response.data.data
  } catch (error) {
    console.error('Error loading donors:', error)
  }
}

const createInteraction = async () => {
  loading.value = true
  try {
    const response = await axios.post('/api/donor-interactions', form.value)
    emit('created', response.data)
  } catch (error) {
    console.error('Error creating interaction:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadDonors()
})
</script>