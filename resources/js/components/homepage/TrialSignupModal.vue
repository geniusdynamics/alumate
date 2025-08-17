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
      <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-gray-900">
            Start Your Free Trial
          </h3>
          <button
            @click="closeModal"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <!-- Content -->
        <div class="p-6">
          <div class="mb-6">
            <p class="text-gray-600 mb-4">
              Get full access to all professional features for 14 days. No credit card required.
            </p>
            
            <!-- Trial Benefits -->
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
              <h4 class="font-medium text-blue-900 mb-3">What's included:</h4>
              <ul class="space-y-2 text-sm text-blue-800">
                <li class="flex items-center">
                  <CheckIcon class="h-4 w-4 text-blue-600 mr-2 flex-shrink-0" />
                  Full alumni directory access
                </li>
                <li class="flex items-center">
                  <CheckIcon class="h-4 w-4 text-blue-600 mr-2 flex-shrink-0" />
                  Unlimited messaging
                </li>
                <li class="flex items-center">
                  <CheckIcon class="h-4 w-4 text-blue-600 mr-2 flex-shrink-0" />
                  Event creation & management
                </li>
                <li class="flex items-center">
                  <CheckIcon class="h-4 w-4 text-blue-600 mr-2 flex-shrink-0" />
                  Mentorship matching
                </li>
                <li class="flex items-center">
                  <CheckIcon class="h-4 w-4 text-blue-600 mr-2 flex-shrink-0" />
                  Priority support
                </li>
              </ul>
            </div>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                Full Name *
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.name }"
                placeholder="Enter your full name"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
            </div>

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
                placeholder="Enter your email address"
              />
              <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
            </div>

            <div>
              <label for="graduationYear" class="block text-sm font-medium text-gray-700 mb-1">
                Graduation Year
              </label>
              <input
                id="graduationYear"
                v-model="form.graduationYear"
                type="number"
                min="1950"
                :max="new Date().getFullYear() + 10"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. 2020"
              />
            </div>

            <div>
              <label for="institution" class="block text-sm font-medium text-gray-700 mb-1">
                Institution
              </label>
              <input
                id="institution"
                v-model="form.institution"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Your university or college"
              />
            </div>

            <div>
              <label for="currentRole" class="block text-sm font-medium text-gray-700 mb-1">
                Current Role
              </label>
              <input
                id="currentRole"
                v-model="form.currentRole"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Your current job title"
              />
            </div>

            <div>
              <label for="industry" class="block text-sm font-medium text-gray-700 mb-1">
                Industry
              </label>
              <select
                id="industry"
                v-model="form.industry"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select your industry</option>
                <option value="technology">Technology</option>
                <option value="finance">Finance</option>
                <option value="healthcare">Healthcare</option>
                <option value="education">Education</option>
                <option value="consulting">Consulting</option>
                <option value="marketing">Marketing</option>
                <option value="engineering">Engineering</option>
                <option value="legal">Legal</option>
                <option value="nonprofit">Non-profit</option>
                <option value="government">Government</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label for="referralSource" class="block text-sm font-medium text-gray-700 mb-1">
                How did you hear about us?
              </label>
              <select
                id="referralSource"
                v-model="form.referralSource"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select source</option>
                <option value="search_engine">Search Engine</option>
                <option value="social_media">Social Media</option>
                <option value="friend_referral">Friend/Colleague</option>
                <option value="university">University</option>
                <option value="advertisement">Advertisement</option>
                <option value="blog_article">Blog/Article</option>
                <option value="other">Other</option>
              </select>
            </div>

            <!-- Terms and Privacy -->
            <div class="flex items-start">
              <input
                id="terms"
                v-model="form.acceptTerms"
                type="checkbox"
                required
                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="terms" class="ml-2 text-sm text-gray-600">
                I agree to the 
                <a href="/terms" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                  Terms of Service
                </a> 
                and 
                <a href="/privacy" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                  Privacy Policy
                </a>
              </label>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="isSubmitting"
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-md font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="isSubmitting" class="flex items-center justify-center">
                <LoadingSpinner class="h-4 w-4 mr-2" />
                Starting Trial...
              </span>
              <span v-else>Start Free Trial</span>
            </button>
          </form>

          <!-- Success Message -->
          <div v-if="showSuccess" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <div class="flex">
              <CheckCircleIcon class="h-5 w-5 text-green-400 mr-2 flex-shrink-0 mt-0.5" />
              <div>
                <h4 class="text-sm font-medium text-green-800">Trial Started Successfully!</h4>
                <p class="text-sm text-green-700 mt-1">
                  Check your email for login instructions and next steps.
                </p>
              </div>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="generalError" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
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
            Your trial will automatically expire after 14 days. No charges will be made.
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
import type { TrialSignupData } from '@/types/homepage'

interface Props {
  isOpen: boolean
  planId?: string
}

const props = withDefaults(defineProps<Props>(), {
  planId: 'professional'
})

const emit = defineEmits<{
  close: []
  success: [data: TrialSignupData]
}>()

const isSubmitting = ref(false)
const showSuccess = ref(false)
const generalError = ref('')

const form = reactive<TrialSignupData & { acceptTerms: boolean }>({
  name: '',
  email: '',
  graduationYear: undefined,
  institution: '',
  currentRole: '',
  industry: '',
  referralSource: '',
  acceptTerms: false
})

const errors = reactive<Record<string, string>>({})

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

  if (!form.name.trim()) {
    errors.name = 'Name is required'
    isValid = false
  }

  if (!form.email.trim()) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }

  if (!form.acceptTerms) {
    generalError.value = 'Please accept the Terms of Service and Privacy Policy'
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
    const response = await fetch('/api/homepage/trial-signup', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        ...form,
        planId: props.planId,
        source: 'pricing_modal'
      })
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Failed to start trial')
    }

    // Track successful trial signup
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'trial_signup', {
        plan_id: props.planId,
        source: 'pricing_modal',
        value: 29 // Professional plan value
      })
    }

    showSuccess.value = true
    emit('success', form)

    // Auto-close after success
    setTimeout(() => {
      closeModal()
    }, 3000)

  } catch (error) {
    console.error('Trial signup error:', error)
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
      name: '',
      email: '',
      graduationYear: undefined,
      institution: '',
      currentRole: '',
      industry: '',
      referralSource: '',
      acceptTerms: false
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
select:focus {
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
</style>