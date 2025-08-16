<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Request a Demo</h3>
        <button 
          @click="$emit('close')"
          class="close-button"
          aria-label="Close modal"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div class="modal-body">
        <p class="modal-description">
          Schedule a personalized demo to see how {{ institutionName }} can benefit 
          from our institutional alumni platform.
        </p>

        <form @submit.prevent="handleSubmit" class="demo-form">
          <div class="form-group">
            <label for="institutionName" class="form-label">Institution Name *</label>
            <input
              id="institutionName"
              v-model="formData.institutionName"
              type="text"
              class="form-input"
              :class="{ 'error': errors.institutionName }"
              required
              placeholder="e.g., Harvard University"
            />
            <span v-if="errors.institutionName" class="error-message">
              {{ errors.institutionName }}
            </span>
          </div>

          <div class="form-group">
            <label for="contactName" class="form-label">Your Name *</label>
            <input
              id="contactName"
              v-model="formData.contactName"
              type="text"
              class="form-input"
              :class="{ 'error': errors.contactName }"
              required
              placeholder="John Smith"
            />
            <span v-if="errors.contactName" class="error-message">
              {{ errors.contactName }}
            </span>
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Email Address *</label>
            <input
              id="email"
              v-model="formData.email"
              type="email"
              class="form-input"
              :class="{ 'error': errors.email }"
              required
              placeholder="john.smith@university.edu"
            />
            <span v-if="errors.email" class="error-message">
              {{ errors.email }}
            </span>
          </div>

          <div class="form-group">
            <label for="title" class="form-label">Job Title</label>
            <input
              id="title"
              v-model="formData.title"
              type="text"
              class="form-input"
              placeholder="e.g., Alumni Relations Director"
            />
          </div>

          <div class="form-group">
            <label for="phone" class="form-label">Phone Number</label>
            <input
              id="phone"
              v-model="formData.phone"
              type="tel"
              class="form-input"
              placeholder="+1 (555) 123-4567"
            />
          </div>

          <div class="form-group">
            <label for="alumniCount" class="form-label">Approximate Alumni Count</label>
            <select
              id="alumniCount"
              v-model="formData.alumniCount"
              class="form-select"
            >
              <option value="">Select range</option>
              <option value="under-1000">Under 1,000</option>
              <option value="1000-5000">1,000 - 5,000</option>
              <option value="5000-10000">5,000 - 10,000</option>
              <option value="10000-25000">10,000 - 25,000</option>
              <option value="25000-50000">25,000 - 50,000</option>
              <option value="over-50000">Over 50,000</option>
            </select>
          </div>

          <div class="form-group">
            <label for="currentSolution" class="form-label">Current Alumni Platform</label>
            <select
              id="currentSolution"
              v-model="formData.currentSolution"
              class="form-select"
            >
              <option value="">Select current solution</option>
              <option value="none">No current platform</option>
              <option value="custom">Custom built solution</option>
              <option value="blackbaud">Blackbaud</option>
              <option value="salesforce">Salesforce</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label for="interests" class="form-label">Primary Interests</label>
            <div class="checkbox-group">
              <label 
                v-for="interest in availableInterests" 
                :key="interest.value"
                class="checkbox-label"
              >
                <input
                  type="checkbox"
                  :value="interest.value"
                  v-model="formData.interests"
                  class="checkbox-input"
                />
                <span class="checkbox-text">{{ interest.label }}</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label for="preferredTime" class="form-label">Preferred Demo Time</label>
            <select
              id="preferredTime"
              v-model="formData.preferredTime"
              class="form-select"
            >
              <option value="">Select preferred time</option>
              <option value="morning">Morning (9 AM - 12 PM)</option>
              <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
              <option value="evening">Evening (5 PM - 8 PM)</option>
              <option value="flexible">Flexible</option>
            </select>
          </div>

          <div class="form-group">
            <label for="message" class="form-label">Additional Information</label>
            <textarea
              id="message"
              v-model="formData.message"
              class="form-textarea"
              rows="4"
              placeholder="Tell us about your specific needs or questions..."
            ></textarea>
          </div>

          <div class="form-actions">
            <button
              type="button"
              @click="$emit('close')"
              class="cancel-button"
              :disabled="isSubmitting"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="submit-button"
              :disabled="isSubmitting || !isFormValid"
            >
              <span v-if="!isSubmitting">Schedule Demo</span>
              <span v-else class="flex items-center">
                <LoadingSpinner class="w-4 h-4 mr-2" />
                Scheduling...
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import LoadingSpinner from '../ui/LoadingSpinner.vue'
import type { DemoRequestData } from '../../types/homepage'

interface Props {
  institutionName?: string
}

const props = withDefaults(defineProps<Props>(), {
  institutionName: 'Your Institution'
})

const emit = defineEmits<{
  'close': []
  'submit': [data: DemoRequestData]
}>()

// Form data
const formData = reactive({
  institutionName: '',
  contactName: '',
  email: '',
  title: '',
  phone: '',
  alumniCount: '',
  currentSolution: '',
  interests: [] as string[],
  preferredTime: '',
  message: ''
})

// Form validation
const errors = reactive({
  institutionName: '',
  contactName: '',
  email: ''
})

const isSubmitting = ref(false)

// Available interests
const availableInterests = [
  { value: 'networking', label: 'Alumni Networking' },
  { value: 'events', label: 'Event Management' },
  { value: 'fundraising', label: 'Fundraising Tools' },
  { value: 'analytics', label: 'Analytics & Reporting' },
  { value: 'mobile-app', label: 'Branded Mobile App' },
  { value: 'integrations', label: 'System Integrations' },
  { value: 'mentorship', label: 'Mentorship Programs' },
  { value: 'job-board', label: 'Career Services' }
]

// Computed properties
const isFormValid = computed(() => {
  return formData.institutionName.trim() !== '' &&
         formData.contactName.trim() !== '' &&
         formData.email.trim() !== '' &&
         isValidEmail(formData.email)
})

// Methods
const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

const validateForm = (): boolean => {
  // Reset errors
  errors.institutionName = ''
  errors.contactName = ''
  errors.email = ''

  let isValid = true

  if (!formData.institutionName.trim()) {
    errors.institutionName = 'Institution name is required'
    isValid = false
  }

  if (!formData.contactName.trim()) {
    errors.contactName = 'Your name is required'
    isValid = false
  }

  if (!formData.email.trim()) {
    errors.email = 'Email address is required'
    isValid = false
  } else if (!isValidEmail(formData.email)) {
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

  try {
    const demoRequest: DemoRequestData = {
      institutionName: formData.institutionName,
      contactName: formData.contactName,
      email: formData.email,
      title: formData.title,
      phone: formData.phone,
      alumniCount: formData.alumniCount,
      currentSolution: formData.currentSolution,
      interests: formData.interests,
      preferredTime: formData.preferredTime,
      message: formData.message,
      timestamp: new Date().toISOString()
    }

    emit('submit', demoRequest)
  } catch (error) {
    console.error('Demo request submission failed:', error)
  } finally {
    isSubmitting.value = false
  }
}

const handleOverlayClick = () => {
  emit('close')
}
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto;
}

.modal-header {
  @apply flex justify-between items-center p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-2xl font-bold text-gray-900;
}

.close-button {
  @apply text-gray-400 hover:text-gray-600 transition-colors;
}

.modal-body {
  @apply p-6;
}

.modal-description {
  @apply text-gray-600 mb-6;
}

.demo-form {
  @apply space-y-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.form-input,
.form-select,
.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors;
}

.form-input.error,
.form-select.error,
.form-textarea.error {
  @apply border-red-500 focus:ring-red-500 focus:border-red-500;
}

.error-message {
  @apply text-sm text-red-600;
}

.checkbox-group {
  @apply grid grid-cols-2 gap-2;
}

.checkbox-label {
  @apply flex items-center space-x-2 cursor-pointer;
}

.checkbox-input {
  @apply rounded border-gray-300 text-blue-600 focus:ring-blue-500;
}

.checkbox-text {
  @apply text-sm text-gray-700;
}

.form-actions {
  @apply flex justify-end space-x-3 pt-4 border-t border-gray-200;
}

.cancel-button {
  @apply px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors disabled:opacity-50;
}

.submit-button {
  @apply px-6 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

@media (max-width: 640px) {
  .modal-content {
    @apply mx-2 max-h-[95vh];
  }
  
  .checkbox-group {
    @apply grid-cols-1;
  }
  
  .form-actions {
    @apply flex-col space-x-0 space-y-2;
  }
}
</style>
         