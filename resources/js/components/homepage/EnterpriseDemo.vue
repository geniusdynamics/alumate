<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto"
    @click="handleBackdropClick"
  >
    <div class="flex min-h-screen items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
      
      <!-- Modal -->
      <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <h3 class="text-xl font-semibold text-gray-900">
              Request Enterprise Demo
            </h3>
            <p class="text-sm text-gray-600 mt-1">
              See how our platform can transform your alumni engagement
            </p>
          </div>
          <button
            @click="closeModal"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Demo Benefits -->
          <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mb-6">
            <h4 class="font-semibold text-gray-900 mb-4">What you'll see in your demo:</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex items-start">
                <CheckIcon class="h-5 w-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="font-medium text-gray-900">Custom Branded App</p>
                  <p class="text-sm text-gray-600">See your institution's mobile app</p>
                </div>
              </div>
              <div class="flex items-start">
                <CheckIcon class="h-5 w-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="font-medium text-gray-900">Admin Dashboard</p>
                  <p class="text-sm text-gray-600">Comprehensive management tools</p>
                </div>
              </div>
              <div class="flex items-start">
                <CheckIcon class="h-5 w-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="font-medium text-gray-900">Analytics & Insights</p>
                  <p class="text-sm text-gray-600">Track engagement and ROI</p>
                </div>
              </div>
              <div class="flex items-start">
                <CheckIcon class="h-5 w-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="font-medium text-gray-900">Integration Options</p>
                  <p class="text-sm text-gray-600">Connect with existing systems</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Institution Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="institutionName" class="block text-sm font-medium text-gray-700 mb-1">
                  Institution Name *
                </label>
                <input
                  id="institutionName"
                  v-model="form.institutionName"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.institutionName }"
                  placeholder="Your university or organization"
                />
                <p v-if="errors.institutionName" class="mt-1 text-sm text-red-600">{{ errors.institutionName }}</p>
              </div>

              <div>
                <label for="alumniCount" class="block text-sm font-medium text-gray-700 mb-1">
                  Alumni Count
                </label>
                <select
                  id="alumniCount"
                  v-model="form.alumniCount"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Select range</option>
                  <option value="under_1000">Under 1,000</option>
                  <option value="1000_5000">1,000 - 5,000</option>
                  <option value="5000_10000">5,000 - 10,000</option>
                  <option value="10000_25000">10,000 - 25,000</option>
                  <option value="25000_50000">25,000 - 50,000</option>
                  <option value="over_50000">Over 50,000</option>
                </select>
              </div>
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="contactName" class="block text-sm font-medium text-gray-700 mb-1">
                  Your Name *
                </label>
                <input
                  id="contactName"
                  v-model="form.contactName"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.contactName }"
                  placeholder="Enter your full name"
                />
                <p v-if="errors.contactName" class="mt-1 text-sm text-red-600">{{ errors.contactName }}</p>
              </div>

              <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                  Job Title
                </label>
                <input
                  id="title"
                  v-model="form.title"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="e.g. Alumni Relations Director"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                  Email Address *
                </label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="{ 'border-red-500': errors.email }"
                  placeholder="your.email@institution.edu"
                />
                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
              </div>

              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                  Phone Number
                </label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="(555) 123-4567"
                />
              </div>
            </div>

            <!-- Current Solution -->
            <div>
              <label for="currentSolution" class="block text-sm font-medium text-gray-700 mb-1">
                Current Alumni Management Solution
              </label>
              <select
                id="currentSolution"
                v-model="form.currentSolution"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select current solution</option>
                <option value="none">No current solution</option>
                <option value="spreadsheets">Spreadsheets/Manual tracking</option>
                <option value="crm">CRM system</option>
                <option value="alumni_platform">Other alumni platform</option>
                <option value="custom_built">Custom-built solution</option>
                <option value="other">Other</option>
              </select>
            </div>

            <!-- Interests -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3">
                Areas of Interest (select all that apply)
              </label>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <label
                  v-for="interest in availableInterests"
                  :key="interest.value"
                  class="flex items-center"
                >
                  <input
                    v-model="form.interests"
                    :value="interest.value"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">{{ interest.label }}</span>
                </label>
              </div>
            </div>

            <!-- Preferred Demo Time -->
            <div>
              <label for="preferredTime" class="block text-sm font-medium text-gray-700 mb-1">
                Preferred Demo Time
              </label>
              <select
                id="preferredTime"
                v-model="form.preferredTime"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select preferred time</option>
                <option value="morning">Morning (9 AM - 12 PM)</option>
                <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                <option value="evening">Evening (5 PM - 8 PM)</option>
                <option value="flexible">I'm flexible</option>
              </select>
            </div>

            <!-- Additional Message -->
            <div>
              <label for="message" class="block text-sm font-medium text-gray-700 mb-1">
                Additional Information
              </label>
              <textarea
                id="message"
                v-model="form.message"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Tell us about your specific needs, challenges, or questions..."
              ></textarea>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-md font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="isSubmitting" class="flex items-center justify-center">
                <LoadingSpinner class="h-4 w-4 mr-2" />
                Scheduling Demo...
              </span>
              <span v-else>Request Demo</span>
            </button>
          </form>

          <!-- Success Message -->
          <div v-if="showSuccess" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-md">
            <div class="flex">
              <CheckCircleIcon class="h-5 w-5 text-green-400 mr-2 flex-shrink-0 mt-0.5" />
              <div>
                <h4 class="text-sm font-medium text-green-800">Demo Request Submitted!</h4>
                <p class="text-sm text-green-700 mt-1">
                  Our team will contact you within 24 hours to schedule your personalized demo.
                </p>
              </div>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="generalError" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-md">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
              <div>
                <h4 class="text-sm font-medium text-red-800">Error</h4>
                <p class="text-sm text-red-700 mt-1">{{ generalError }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 rounded-b-xl">
          <p class="text-xs text-gray-500 text-center">
            We'll never share your information with third parties. 
            <a href="/privacy" class="text-blue-600 hover:text-blue-800 underline">Privacy Policy</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { 
  XMarkIcon, 
  CheckIcon, 
  CheckCircleIcon, 
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import type { DemoRequestData } from '@/types/homepage'

interface Props {
  isOpen: boolean
  planId?: string
}

const props = withDefaults(defineProps<Props>(), {
  planId: 'enterprise'
})

const emit = defineEmits<{
  close: []
  success: [data: DemoRequestData]
}>()

const isSubmitting = ref(false)
const showSuccess = ref(false)
const generalError = ref('')

const form = reactive<DemoRequestData>({
  institutionName: '',
  contactName: '',
  email: '',
  title: '',
  phone: '',
  alumniCount: '',
  currentSolution: '',
  interests: [],
  preferredTime: '',
  message: ''
})

const errors = reactive<Record<string, string>>({})

const availableInterests = [
  { value: 'mobile_app', label: 'Mobile App' },
  { value: 'alumni_directory', label: 'Alumni Directory' },
  { value: 'event_management', label: 'Event Management' },
  { value: 'fundraising', label: 'Fundraising' },
  { value: 'mentorship', label: 'Mentorship Programs' },
  { value: 'job_board', label: 'Job Board' },
  { value: 'analytics', label: 'Analytics & Reporting' },
  { value: 'integrations', label: 'System Integrations' },
  { value: 'branding', label: 'Custom Branding' }
]

const closeModal = () => {
  if (!isSubmitting.value) {
    emit('close')
  }
}

const handleBackdropClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    closeModal()
  }
}

const validateForm = (): boolean => {
  // Clear previous errors
  Object.keys(errors).forEach(key => delete errors[key])
  
  let isValid = true

  if (!form.institutionName.trim()) {
    errors.institutionName = 'Institution name is required'
    isValid = false
  }

  if (!form.contactName.trim()) {
    errors.contactName = 'Your name is required'
    isValid = false
  }

  if (!form.email.trim()) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true
  generalError.value = ''

  try {
    const response = await fetch('/api/homepage/demo-request', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        ...form,
        planId: props.planId,
        source: 'pricing_modal',
        timestamp: new Date().toISOString()
      })
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Failed to submit demo request')
    }

    // Track successful demo request
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'demo_request', {
        plan_id: props.planId,
        source: 'pricing_modal',
        institution_name: form.institutionName,
        alumni_count: form.alumniCount
      })
    }

    showSuccess.value = true
    emit('success', form)

    // Auto-close after success
    setTimeout(() => {
      closeModal()
    }, 4000)

  } catch (error) {
    console.error('Demo request error:', error)
    generalError.value = error instanceof Error ? error.message : 'An unexpected error occurred'
  } finally {
    isSubmitting.value = false
  }
}

// Reset form when modal opens/closes
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    // Reset form
    Object.assign(form, {
      institutionName: '',
      contactName: '',
      email: '',
      title: '',
      phone: '',
      alumniCount: '',
      currentSolution: '',
      interests: [],
      preferredTime: '',
      message: ''
    })
    
    // Clear states
    Object.keys(errors).forEach(key => delete errors[key])
    generalError.value = ''
    showSuccess.value = false
    isSubmitting.value = false
  }
})
</script>

<style scoped>
/* Custom scrollbar for modal content */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Focus styles for better accessibility */
input:focus,
select:focus,
textarea:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Animation for success message */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.bg-green-50 {
  animation: slideIn 0.3s ease-out;
}

.bg-red-50 {
  animation: slideIn 0.3s ease-out;
}

/* Checkbox styling */
input[type="checkbox"]:checked {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

/* Grid responsive adjustments */
@media (max-width: 768px) {
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
  
  .md\\:grid-cols-3 {
    grid-template-columns: 1fr;
  }
}
</style>