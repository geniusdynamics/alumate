<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75" @click="$emit('close')"></div>
      </div>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <form @submit.prevent="createMajorGiftProspect">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                  Add Major Gift Prospect
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

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label for="prospect_rating" class="block text-sm font-medium text-gray-700">
                        Prospect Rating
                      </label>
                      <select
                        id="prospect_rating"
                        v-model="form.prospect_rating"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required
                      >
                        <option value="A">A - Highest Priority</option>
                        <option value="B">B - High Priority</option>
                        <option value="C">C - Medium Priority</option>
                        <option value="D">D - Low Priority</option>
                      </select>
                    </div>

                    <div>
                      <label for="stage" class="block text-sm font-medium text-gray-700">
                        Current Stage
                      </label>
                      <select
                        id="stage"
                        v-model="form.stage"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required
                      >
                        <option value="identification">Identification</option>
                        <option value="qualification">Qualification</option>
                        <option value="cultivation">Cultivation</option>
                        <option value="solicitation">Solicitation</option>
                        <option value="stewardship">Stewardship</option>
                      </select>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label for="estimated_capacity" class="block text-sm font-medium text-gray-700">
                        Estimated Capacity ($)
                      </label>
                      <input
                        id="estimated_capacity"
                        v-model.number="form.estimated_capacity"
                        type="number"
                        step="0.01"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0.00"
                        required
                      />
                    </div>

                    <div>
                      <label for="ask_amount" class="block text-sm font-medium text-gray-700">
                        Proposed Ask Amount ($)
                      </label>
                      <input
                        id="ask_amount"
                        v-model.number="form.ask_amount"
                        type="number"
                        step="0.01"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0.00"
                      />
                    </div>
                  </div>

                  <div>
                    <label for="assigned_officer" class="block text-sm font-medium text-gray-700">
                      Assigned Development Officer
                    </label>
                    <select
                      id="assigned_officer"
                      v-model="form.assigned_officer"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="">Select officer...</option>
                      <option v-for="officer in officers" :key="officer.id" :value="officer.id">
                        {{ officer.name }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label for="interests" class="block text-sm font-medium text-gray-700">
                      Interests & Motivations
                    </label>
                    <textarea
                      id="interests"
                      v-model="form.interests"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="What motivates this donor? What are their interests?"
                    ></textarea>
                  </div>

                  <div>
                    <label for="strategy" class="block text-sm font-medium text-gray-700">
                      Cultivation Strategy
                    </label>
                    <textarea
                      id="strategy"
                      v-model="form.strategy"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Strategy for cultivating this major gift prospect..."
                      required
                    ></textarea>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label for="next_action" class="block text-sm font-medium text-gray-700">
                        Next Action
                      </label>
                      <input
                        id="next_action"
                        v-model="form.next_action"
                        type="text"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="e.g., Schedule lunch meeting"
                      />
                    </div>

                    <div>
                      <label for="next_action_date" class="block text-sm font-medium text-gray-700">
                        Next Action Date
                      </label>
                      <input
                        id="next_action_date"
                        v-model="form.next_action_date"
                        type="date"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      />
                    </div>
                  </div>

                  <div>
                    <label for="research_notes" class="block text-sm font-medium text-gray-700">
                      Research Notes
                    </label>
                    <textarea
                      id="research_notes"
                      v-model="form.research_notes"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Background research, connections, wealth indicators, etc."
                    ></textarea>
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
              {{ loading ? 'Creating...' : 'Add Prospect' }}
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
  created: [prospect: any]
}>()

// Types
interface DonorProfile {
  id: number
  user?: {
    name: string
  }
  donor_status: string
}

interface Officer {
  id: number
  name: string
}

// Reactive state
const loading = ref(false)
const donors = ref<DonorProfile[]>([])
const officers = ref<Officer[]>([])

const form = ref({
  donor_profile_id: '',
  prospect_rating: 'B',
  stage: 'identification',
  estimated_capacity: null as number | null,
  ask_amount: null as number | null,
  assigned_officer: '',
  interests: '',
  strategy: '',
  next_action: '',
  next_action_date: '',
  research_notes: ''
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

const loadOfficers = async () => {
  try {
    const response = await axios.get('/api/users', {
      params: { role: 'development_officer' }
    })
    officers.value = response.data.data
  } catch (error) {
    console.error('Error loading officers:', error)
  }
}

const createMajorGiftProspect = async () => {
  loading.value = true
  try {
    const response = await axios.post('/api/major-gift-prospects', form.value)
    emit('created', response.data)
  } catch (error) {
    console.error('Error creating major gift prospect:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadDonors()
  loadOfficers()
})
</script>