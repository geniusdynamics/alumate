<template>
  <div class="feedback-widget">
    <!-- Feedback Button -->
    <button
      v-if="!showWidget"
      @click="showWidget = true"
      class="fixed bottom-4 right-4 z-50 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition-all duration-200 hover:scale-105"
      :class="{ 'animate-pulse': shouldPulse }"
    >
      <ChatBubbleLeftRightIcon class="h-6 w-6" />
    </button>

    <!-- Feedback Widget -->
    <div
      v-if="showWidget"
      class="fixed bottom-4 right-4 z-50 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 w-96 max-w-[calc(100vw-2rem)]"
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ currentStep === 'type' ? 'Share Feedback' : 'Tell us more' }}
        </h3>
        <button
          @click="closeWidget"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <!-- Content -->
      <div class="p-4">
        <!-- Step 1: Feedback Type Selection -->
        <div v-if="currentStep === 'type'" class="space-y-3">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            What type of feedback would you like to share?
          </p>
          
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="type in feedbackTypes"
              :key="type.value"
              @click="selectFeedbackType(type.value)"
              class="p-3 text-left border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <div class="flex items-center space-x-2">
                <component :is="type.icon" class="h-5 w-5 text-gray-500" />
                <div>
                  <div class="font-medium text-sm text-gray-900 dark:text-white">
                    {{ type.label }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ type.description }}
                  </div>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- Step 2: Feedback Form -->
        <div v-else-if="currentStep === 'form'" class="space-y-4">
          <!-- Rating (for general feedback) -->
          <div v-if="form.type === 'general_feedback'" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              How would you rate your experience?
            </label>
            <div class="flex space-x-1">
              <button
                v-for="star in 5"
                :key="star"
                @click="form.rating = star"
                class="text-2xl transition-colors"
                :class="star <= (form.rating || 0) ? 'text-yellow-400' : 'text-gray-300 hover:text-yellow-300'"
              >
                ★
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ getContentLabel() }}
            </label>
            <textarea
              v-model="form.content"
              :placeholder="getContentPlaceholder()"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none"
            ></textarea>
          </div>

          <!-- Current Page Context -->
          <div class="text-xs text-gray-500 dark:text-gray-400">
            <span>Page: {{ currentPage }}</span>
          </div>

          <!-- Actions -->
          <div class="flex space-x-2">
            <button
              @click="currentStep = 'type'"
              class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              Back
            </button>
            <button
              @click="submitFeedback"
              :disabled="!form.content.trim() || submitting"
              class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ submitting ? 'Sending...' : 'Send Feedback' }}
            </button>
          </div>
        </div>

        <!-- Step 3: Success -->
        <div v-else-if="currentStep === 'success'" class="text-center space-y-4">
          <div class="mx-auto w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
            <CheckIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <h4 class="text-lg font-medium text-gray-900 dark:text-white">
              Thank you!
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Your feedback has been submitted and will help us improve the platform.
            </p>
          </div>
          <button
            @click="closeWidget"
            class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Overlay -->
    <div
      v-if="showWidget"
      @click="closeWidget"
      class="fixed inset-0 bg-black bg-opacity-25 z-40"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import {
  ChatBubbleLeftRightIcon,
  XMarkIcon,
  CheckIcon,
  BugAntIcon,
  LightBulbIcon,
  HeartIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const page = usePage()

// State
const showWidget = ref(false)
const currentStep = ref('type')
const submitting = ref(false)
const shouldPulse = ref(false)

// Form data
const form = ref({
  type: '',
  content: '',
  rating: null,
  metadata: {}
})

// Feedback types
const feedbackTypes = [
  {
    value: 'bug_report',
    label: 'Bug Report',
    description: 'Something is broken',
    icon: BugAntIcon
  },
  {
    value: 'feature_request',
    label: 'Feature Request',
    description: 'Suggest an improvement',
    icon: LightBulbIcon
  },
  {
    value: 'general_feedback',
    label: 'General Feedback',
    description: 'Share your thoughts',
    icon: HeartIcon
  },
  {
    value: 'usability_issue',
    label: 'Usability Issue',
    description: 'Something is confusing',
    icon: ExclamationTriangleIcon
  }
]

// Computed
const currentPage = computed(() => {
  return page.url || window.location.pathname
})

// Methods
const selectFeedbackType = (type) => {
  form.value.type = type
  currentStep.value = 'form'
}

const getContentLabel = () => {
  const labels = {
    bug_report: 'Describe the bug',
    feature_request: 'Describe your feature idea',
    general_feedback: 'Share your feedback',
    usability_issue: 'What was confusing?'
  }
  return labels[form.value.type] || 'Your feedback'
}

const getContentPlaceholder = () => {
  const placeholders = {
    bug_report: 'Please describe what happened, what you expected, and steps to reproduce...',
    feature_request: 'Tell us about the feature you\'d like to see and how it would help...',
    general_feedback: 'We\'d love to hear your thoughts about the platform...',
    usability_issue: 'What part of the interface was confusing or difficult to use?...'
  }
  return placeholders[form.value.type] || 'Please share your feedback...'
}

const submitFeedback = async () => {
  if (!form.value.content.trim()) return

  submitting.value = true

  try {
    // Collect additional metadata
    form.value.metadata = {
      page: currentPage.value,
      userAgent: navigator.userAgent,
      timestamp: new Date().toISOString(),
      screenResolution: `${screen.width}x${screen.height}`,
      viewport: `${window.innerWidth}x${window.innerHeight}`
    }

    await router.post('/api/feedback', form.value, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        currentStep.value = 'success'
        // Auto-close after 3 seconds
        setTimeout(() => {
          closeWidget()
        }, 3000)
      },
      onError: (errors) => {
        console.error('Feedback submission error:', errors)
        // Handle error - could show error message
      }
    })
  } catch (error) {
    console.error('Feedback submission error:', error)
  } finally {
    submitting.value = false
  }
}

const closeWidget = () => {
  showWidget.value = false
  currentStep.value = 'type'
  form.value = {
    type: '',
    content: '',
    rating: null,
    metadata: {}
  }
}

// Auto-show widget occasionally for engagement
onMounted(() => {
  // Show pulse animation after 30 seconds if user hasn't interacted
  setTimeout(() => {
    if (!showWidget.value) {
      shouldPulse.value = true
      setTimeout(() => {
        shouldPulse.value = false
      }, 3000)
    }
  }, 30000)
})
</script>

<style scoped>
.feedback-widget {
  /* Ensure widget appears above other elements */
  z-index: 9999;
}

/* Custom scrollbar for textarea */
textarea::-webkit-scrollbar {
  width: 4px;
}

textarea::-webkit-scrollbar-track {
  background: transparent;
}

textarea::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 2px;
}

textarea::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

.dark textarea::-webkit-scrollbar-thumb {
  background: #4a5568;
}

.dark textarea::-webkit-scrollbar-thumb:hover {
  background: #2d3748;
}
</style>