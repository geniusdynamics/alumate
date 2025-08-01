<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

      <div class="inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-medium text-gray-900">Create New Scholarship</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Scholarship Name *
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter scholarship name"
              />
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Description *
              </label>
              <textarea
                v-model="form.description"
                required
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Describe the scholarship purpose and goals"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Award Amount ($) *
              </label>
              <input
                v-model.number="form.amount"
                type="number"
                min="0"
                step="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0.00"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Scholarship Type *
              </label>
              <select
                v-model="form.type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select type</option>
                <option value="one_time">One-time Award</option>
                <option value="recurring">Recurring Award</option>
                <option value="endowment">Endowment Fund</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Application Deadline *
              </label>
              <input
                v-model="form.application_deadline"
                type="date"
                required
                :min="tomorrow"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Award Date
              </label>
              <input
                v-model="form.award_date"
                type="date"
                :min="form.application_deadline"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Maximum Recipients *
              </label>
              <input
                v-model.number="form.max_recipients"
                type="number"
                min="1"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="1"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Total Fund Amount ($) *
              </label>
              <input
                v-model.number="form.total_fund_amount"
                type="number"
                min="0"
                step="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="0.00"
              />
            </div>
          </div>

          <div class="border-t pt-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Eligibility Criteria</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Minimum GPA
                </label>
                <input
                  v-model.number="form.eligibility_criteria.min_gpa"
                  type="number"
                  min="0"
                  max="4"
                  step="0.1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="3.0"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Academic Year
                </label>
                <select
                  v-model="form.eligibility_criteria.academic_year"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Any year</option>
                  <option value="freshman">Freshman</option>
                  <option value="sophomore">Sophomore</option>
                  <option value="junior">Junior</option>
                  <option value="senior">Senior</option>
                  <option value="graduate">Graduate</option>
                </select>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3 pt-6 border-t">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {{ loading ? 'Creating...' : 'Create Scholarship' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from 'axios'

interface Props {
  show: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  close: []
  created: [scholarship: any]
}>()

const loading = ref(false)

const form = ref({
  name: '',
  description: '',
  amount: 0,
  type: '',
  application_deadline: '',
  award_date: '',
  max_recipients: 1,
  total_fund_amount: 0,
  eligibility_criteria: {
    min_gpa: null,
    academic_year: '',
    field_of_study: '',
    financial_need: false
  },
  application_requirements: {
    personal_statement: true,
    transcripts: true,
    letters_of_recommendation: 2,
    financial_documents: false
  }
})

const tomorrow = computed(() => {
  const date = new Date()
  date.setDate(date.getDate() + 1)
  return date.toISOString().split('T')[0]
})

const handleSubmit = async () => {
  loading.value = true
  
  try {
    const response = await axios.post('/api/scholarships', form.value)
    
    if (response.data.success) {
      emit('created', response.data.data)
      emit('close')
      resetForm()
    }
  } catch (error) {
    console.error('Error creating scholarship:', error)
    // Handle error (show notification, etc.)
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    description: '',
    amount: 0,
    type: '',
    application_deadline: '',
    award_date: '',
    max_recipients: 1,
    total_fund_amount: 0,
    eligibility_criteria: {
      min_gpa: null,
      academic_year: '',
      field_of_study: '',
      financial_need: false
    },
    application_requirements: {
      personal_statement: true,
      transcripts: true,
      letters_of_recommendation: 2,
      financial_documents: false
    }
  }
}
</script>