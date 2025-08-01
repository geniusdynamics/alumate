<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity" aria-hidden="true">
        <div class="absolute inset-0 bg-gray-500 opacity-75" @click="$emit('close')"></div>
      </div>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <form @submit.prevent="createStewardshipPlan">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                  Create Stewardship Plan
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
                    <label for="plan_name" class="block text-sm font-medium text-gray-700">
                      Plan Name
                    </label>
                    <input
                      id="plan_name"
                      v-model="form.plan_name"
                      type="text"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="e.g., Annual Giving Plan 2024"
                      required
                    />
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label for="start_date" class="block text-sm font-medium text-gray-700">
                        Start Date
                      </label>
                      <input
                        id="start_date"
                        v-model="form.start_date"
                        type="date"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required
                      />
                    </div>

                    <div>
                      <label for="end_date" class="block text-sm font-medium text-gray-700">
                        End Date
                      </label>
                      <input
                        id="end_date"
                        v-model="form.end_date"
                        type="date"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        required
                      />
                    </div>
                  </div>

                  <div>
                    <label for="giving_goal" class="block text-sm font-medium text-gray-700">
                      Giving Goal ($)
                    </label>
                    <input
                      id="giving_goal"
                      v-model.number="form.giving_goal"
                      type="number"
                      step="0.01"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label for="contact_frequency" class="block text-sm font-medium text-gray-700">
                      Contact Frequency
                    </label>
                    <select
                      id="contact_frequency"
                      v-model="form.contact_frequency"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      required
                    >
                      <option value="weekly">Weekly</option>
                      <option value="monthly">Monthly</option>
                      <option value="quarterly">Quarterly</option>
                      <option value="semi_annually">Semi-Annually</option>
                      <option value="annually">Annually</option>
                    </select>
                  </div>

                  <div>
                    <label for="preferred_contact_methods" class="block text-sm font-medium text-gray-700">
                      Preferred Contact Methods
                    </label>
                    <div class="mt-2 space-y-2">
                      <label v-for="method in contactMethods" :key="method.value" class="inline-flex items-center mr-4">
                        <input
                          type="checkbox"
                          :value="method.value"
                          v-model="form.preferred_contact_methods"
                          class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                        <span class="ml-2 text-sm text-gray-700">{{ method.label }}</span>
                      </label>
                    </div>
                  </div>

                  <div>
                    <label for="objectives" class="block text-sm font-medium text-gray-700">
                      Objectives
                    </label>
                    <textarea
                      id="objectives"
                      v-model="form.objectives"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Key objectives for this stewardship plan..."
                      required
                    ></textarea>
                  </div>

                  <div>
                    <label for="strategies" class="block text-sm font-medium text-gray-700">
                      Strategies
                    </label>
                    <textarea
                      id="strategies"
                      v-model="form.strategies"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Strategies to achieve the objectives..."
                      required
                    ></textarea>
                  </div>

                  <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">
                      Additional Notes
                    </label>
                    <textarea
                      id="notes"
                      v-model="form.notes"
                      rows="2"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Any additional notes or considerations..."
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
              {{ loading ? 'Creating...' : 'Create Plan' }}
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
  created: [plan: any]
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

const contactMethods = [
  { value: 'email', label: 'Email' },
  { value: 'phone', label: 'Phone' },
  { value: 'mail', label: 'Mail' },
  { value: 'in_person', label: 'In Person' },
  { value: 'event', label: 'Events' }
]

const form = ref({
  donor_profile_id: '',
  plan_name: '',
  start_date: new Date().toISOString().split('T')[0],
  end_date: '',
  giving_goal: null as number | null,
  contact_frequency: 'quarterly',
  preferred_contact_methods: [] as string[],
  objectives: '',
  strategies: '',
  notes: ''
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

const createStewardshipPlan = async () => {
  loading.value = true
  try {
    const response = await axios.post('/api/donor-stewardship-plans', form.value)
    emit('created', response.data)
  } catch (error) {
    console.error('Error creating stewardship plan:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadDonors()
  
  // Set default end date to one year from start date
  const nextYear = new Date()
  nextYear.setFullYear(nextYear.getFullYear() + 1)
  form.value.end_date = nextYear.toISOString().split('T')[0]
})
</script>