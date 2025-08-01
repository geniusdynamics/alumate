<template>
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Apply for {{ scholarship.name }}</h2>
        <p class="text-gray-600">{{ scholarship.description }}</p>
        <div class="mt-4 flex items-center space-x-6 text-sm text-gray-600">
          <span>Award: ${{ formatCurrency(scholarship.amount) }}</span>
          <span>Deadline: {{ formatDate(scholarship.application_deadline) }}</span>
          <span>Max Recipients: {{ scholarship.max_recipients }}</span>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-8">
        <!-- Personal Information -->
        <div class="border-b pb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Academic Year *
              </label>
              <select
                v-model="form.application_data.academic_year"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select academic year</option>
                <option value="freshman">Freshman</option>
                <option value="sophomore">Sophomore</option>
                <option value="junior">Junior</option>
                <option value="senior">Senior</option>
                <option value="graduate">Graduate</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Field of Study *
              </label>
              <input
                v-model="form.application_data.field_of_study"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., Computer Science"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Current GPA
              </label>
              <input
                v-model.number="form.gpa"
                type="number"
                min="0"
                max="4"
                step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="3.50"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Expected Graduation
              </label>
              <input
                v-model="form.application_data.expected_graduation"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- Personal Statement -->
        <div class="border-b pb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Statement *</h3>
          <p class="text-sm text-gray-600 mb-4">
            Please write a personal statement explaining why you deserve this scholarship and how it will help you achieve your goals.
          </p>
          <textarea
            v-model="form.personal_statement"
            required
            rows="8"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Write your personal statement here..."
          ></textarea>
          <div class="mt-2 text-sm text-gray-500">
            {{ form.personal_statement.length }} characters
          </div>
        </div>

        <!-- Financial Need (if required) -->
        <div v-if="scholarship.eligibility_criteria?.financial_need" class="border-b pb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Need Statement</h3>
          <p class="text-sm text-gray-600 mb-4">
            Please describe your financial situation and explain why you need this scholarship.
          </p>
          <textarea
            v-model="form.financial_need_statement"
            rows="6"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Describe your financial need..."
          ></textarea>
        </div>

        <!-- References -->
        <div class="border-b pb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">References</h3>
          <p class="text-sm text-gray-600 mb-4">
            Please provide contact information for {{ scholarship.application_requirements?.letters_of_recommendation || 2 }} references.
          </p>
          
          <div v-for="(reference, index) in form.references" :key="index" class="mb-6 p-4 border border-gray-200 rounded-lg">
            <div class="flex justify-between items-center mb-4">
              <h4 class="font-medium text-gray-900">Reference {{ index + 1 }}</h4>
              <button
                v-if="form.references.length > 1"
                type="button"
                @click="removeReference(index)"
                class="text-red-600 hover:text-red-800 text-sm"
              >
                Remove
              </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input
                  v-model="reference.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title/Position *</label>
                <input
                  v-model="reference.title"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input
                  v-model="reference.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input
                  v-model="reference.phone"
                  type="tel"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Relationship *</label>
                <input
                  v-model="reference.relationship"
                  type="text"
                  required
                  placeholder="e.g., Professor, Supervisor, Mentor"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>

          <button
            type="button"
            @click="addReference"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
          >
            + Add Another Reference
          </button>
        </div>

        <!-- Submit Section -->
        <div class="flex justify-between items-center pt-6">
          <div class="text-sm text-gray-600">
            <p>By submitting this application, you agree to the terms and conditions.</p>
          </div>
          <div class="flex space-x-4">
            <button
              type="button"
              @click="saveDraft"
              :disabled="loading"
              class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            >
              Save Draft
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {{ loading ? 'Submitting...' : 'Submit Application' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Scholarship {
  id: number
  name: string
  description: string
  amount: number
  application_deadline: string
  max_recipients: number
  eligibility_criteria?: {
    financial_need?: boolean
  }
  application_requirements?: {
    letters_of_recommendation?: number
  }
}

interface Props {
  scholarship: Scholarship
}

const props = defineProps<Props>()

const emit = defineEmits<{
  submitted: [application: any]
  saved: [application: any]
}>()

const loading = ref(false)

const form = ref({
  application_data: {
    academic_year: '',
    field_of_study: '',
    expected_graduation: ''
  },
  personal_statement: '',
  gpa: null as number | null,
  financial_need_statement: '',
  references: [
    {
      name: '',
      title: '',
      email: '',
      phone: '',
      relationship: ''
    }
  ]
})

const addReference = () => {
  form.value.references.push({
    name: '',
    title: '',
    email: '',
    phone: '',
    relationship: ''
  })
}

const removeReference = (index: number) => {
  form.value.references.splice(index, 1)
}

const handleSubmit = async () => {
  loading.value = true
  
  try {
    const response = await axios.post(`/api/scholarships/${props.scholarship.id}/applications`, form.value)
    
    if (response.data.success) {
      emit('submitted', response.data.data)
    }
  } catch (error) {
    console.error('Error submitting application:', error)
  } finally {
    loading.value = false
  }
}

const saveDraft = async () => {
  loading.value = true
  
  try {
    const draftData = { ...form.value, status: 'draft' }
    const response = await axios.post(`/api/scholarships/${props.scholarship.id}/applications`, draftData)
    
    if (response.data.success) {
      emit('saved', response.data.data)
    }
  } catch (error) {
    console.error('Error saving draft:', error)
  } finally {
    loading.value = false
  }
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-US').format(amount)
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(() => {
  // Initialize references based on requirements
  const requiredReferences = props.scholarship.application_requirements?.letters_of_recommendation || 2
  while (form.value.references.length < requiredReferences) {
    addReference()
  }
})
</script>