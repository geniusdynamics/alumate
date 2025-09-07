<template>
  <form
    :class="formClasses"
    :aria-label="config.ariaLabel || config.title"
    :aria-describedby="config.description ? `${formId}-description` : undefined"
    @submit.prevent="handleSubmit"
    novalidate
  >
    <!-- Form Header -->
    <div v-if="config.title || config.description" class="mb-6 sm:mb-8">
      <h2
        v-if="config.title"
        :id="`${formId}-title`"
        class="text-2xl font-bold text-gray-900 dark:text-white mb-2"
      >
        {{ config.title }}
      </h2>
      <p
        v-if="config.description"
        :id="`${formId}-description`"
        class="text-gray-600 dark:text-gray-300"
      >
        {{ config.description }}
      </p>
    </div>

    <!-- Screen Reader Instructions -->
    <div
      v-if="config.screenReaderInstructions"
      class="sr-only"
      :id="`${formId}-instructions`"
      aria-live="polite"
    >
      {{ config.screenReaderInstructions }}
    </div>

    <!-- Progress Indicator -->
    <div
      v-if="config.showProgress && totalSteps > 1"
      class="mb-6"
      role="progressbar"
      :aria-valuenow="currentStep"
      :aria-valuemin="1"
      :aria-valuemax="totalSteps"
      :aria-label="`Step ${currentStep} of ${totalSteps}`"
    >
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
          Step {{ currentStep }} of {{ totalSteps }}
        </span>
        <span class="text-sm text-gray-500 dark:text-gray-400">
          {{ Math.round((currentStep / totalSteps) * 100) }}% Complete
        </span>
      </div>
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div
          class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out"
          :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
        />
      </div>
    </div>

    <!-- Validation Summary -->
    <div
      v-if="config.showValidationSummary && hasErrors && showValidationSummary"
      class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
      role="alert"
      aria-live="polite"
    >
      <h3 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">
        Please correct the following errors:
      </h3>
      <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
        <li v-for="error in validationErrors" :key="error.field">
          <a
            :href="`#${error.field}`"
            class="hover:underline focus:underline focus:outline-none"
            @click.prevent="focusField(error.field)"
          >
            {{ error.message }}
          </a>
        </li>
      </ul>
    </div>

    <!-- Form Fields Container -->
    <div :class="fieldsContainerClasses">
      <FormFieldRenderer
        v-for="field in visibleFields"
        :key="field.id"
        :field="field"
        :value="formData[field.name]"
        :error="fieldErrors[field.name]"
        :disabled="isSubmitting || field.disabled"
        :form-id="formId"
        @update:value="updateFieldValue"
        @blur="handleFieldBlur"
        @focus="handleFieldFocus"
      />
    </div>

    <!-- Honeypot Field (hidden) -->
    <input
      v-if="config.honeypot"
      type="text"
      name="website"
      :id="`${formId}-honeypot`"
      class="absolute -left-9999px opacity-0 pointer-events-none"
      tabindex="-1"
      autocomplete="off"
      v-model="honeypotValue"
      aria-hidden="true"
    />

    <!-- reCAPTCHA -->
    <div
      v-if="config.recaptcha?.enabled"
      class="mb-6"
    >
      <div
        :id="`${formId}-recaptcha`"
        class="g-recaptcha"
        :data-sitekey="config.recaptcha.siteKey"
        :data-theme="config.recaptcha.theme || 'light'"
      />
    </div>

    <!-- Form Actions -->
    <div class="flex flex-col sm:flex-row gap-4 justify-end">
      <button
        v-if="currentStep > 1"
        type="button"
        class="px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        @click="previousStep"
        :disabled="isSubmitting"
      >
        Previous
      </button>
      
      <button
        v-if="config.allowSaveProgress && !isLastStep"
        type="button"
        class="px-6 py-3 text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
        @click="saveProgress"
        :disabled="isSubmitting"
      >
        Save Progress
      </button>
      
      <button
        type="submit"
        :class="submitButtonClasses"
        :disabled="isSubmitting || !isFormValid"
        :aria-describedby="isSubmitting ? `${formId}-submitting` : undefined"
      >
        <svg
          v-if="isSubmitting"
          class="animate-spin -ml-1 mr-3 h-5 w-5"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>
        {{ isSubmitting ? 'Submitting...' : (isLastStep ? 'Submit' : 'Next') }}
      </button>
    </div>

    <!-- Auto-save Status -->
    <div
      v-if="config.enableAutoSave && autoSaveStatus"
      class="mt-4 text-sm text-gray-500 dark:text-gray-400 flex items-center"
      :id="`${formId}-autosave`"
      aria-live="polite"
    >
      <svg
        v-if="autoSaveStatus === 'saving'"
        class="animate-spin h-4 w-4 mr-2"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
      <svg
        v-else-if="autoSaveStatus === 'saved'"
        class="h-4 w-4 mr-2 text-green-500"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M5 13l4 4L19 7"
        />
      </svg>
      <svg
        v-else-if="autoSaveStatus === 'error'"
        class="h-4 w-4 mr-2 text-red-500"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      {{ autoSaveStatusText }}
    </div>

    <!-- Submitting Status (for screen readers) -->
    <div
      v-if="isSubmitting"
      :id="`${formId}-submitting`"
      class="sr-only"
      aria-live="assertive"
    >
      Form is being submitted. Please wait.
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, ref, reactive, watch, onMounted, onUnmounted, nextTick } from 'vue'
import type { FormComponentConfig, FormField } from '@/types/components'
import FormFieldRenderer from './FormFieldRenderer.vue'
import { useFormValidation } from '@/composables/useFormValidation'
import { useFormSubmission } from '@/composables/useFormSubmission'
import { useFormAutoSave } from '@/composables/useFormAutoSave'

interface Props {
  config: FormComponentConfig
  initialData?: Record<string, any>
  readonly?: boolean
}

interface Emits {
  (e: 'submit', data: Record<string, any>): void
  (e: 'step-change', step: number): void
  (e: 'field-change', field: string, value: any): void
  (e: 'validation-change', isValid: boolean, errors: Record<string, string>): void
}

const props = withDefaults(defineProps<Props>(), {
  initialData: () => ({}),
  readonly: false
})

const emit = defineEmits<Emits>()

// Refs
const formId = ref(`form-${Math.random().toString(36).substring(2, 9)}`)
const currentStep = ref(1)
const formData = reactive<Record<string, any>>({})
const honeypotValue = ref('')
const showValidationSummary = ref(false)
const isSubmitting = ref(false)

// Initialize form data
const initializeFormData = () => {
  props.config.fields.forEach(field => {
    if (props.initialData[field.name] !== undefined) {
      formData[field.name] = props.initialData[field.name]
    } else if (field.defaultValue !== undefined) {
      formData[field.name] = field.defaultValue
    } else {
      formData[field.name] = field.type === 'checkbox' ? false : ''
    }
  })
}

// Composables
const {
  fieldErrors,
  validationErrors,
  hasErrors,
  isFormValid,
  validateField,
  validateForm,
  clearFieldError
} = useFormValidation(props.config, formData)

const {
  submitForm,
  isSubmitting: submissionLoading
} = useFormSubmission(props.config)

const {
  autoSaveStatus,
  autoSaveStatusText,
  startAutoSave,
  stopAutoSave,
  saveProgress
} = useFormAutoSave(props.config, formData)

// Computed properties
const totalSteps = computed(() => {
  // For now, treat all forms as single step
  // This can be enhanced later for multi-step forms
  return 1
})

const isLastStep = computed(() => currentStep.value === totalSteps.value)

const visibleFields = computed(() => {
  return props.config.fields.filter(field => {
    if (!field.showWhen) return true
    
    const conditionField = formData[field.showWhen.field]
    const conditionValue = field.showWhen.value
    
    switch (field.showWhen.operator) {
      case 'equals':
        return conditionField === conditionValue
      case 'not_equals':
        return conditionField !== conditionValue
      case 'contains':
        return String(conditionField).includes(String(conditionValue))
      case 'not_contains':
        return !String(conditionField).includes(String(conditionValue))
      default:
        return true
    }
  })
})

const formClasses = computed(() => [
  'max-w-2xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg',
  {
    'opacity-50 pointer-events-none': props.readonly,
  },
  // Theme classes
  {
    'border border-gray-200 dark:border-gray-700': props.config.theme === 'default',
    'shadow-none border-0': props.config.theme === 'minimal',
    'shadow-xl border-2 border-gray-100 dark:border-gray-600': props.config.theme === 'modern',
    'border-2 border-gray-300 dark:border-gray-600 rounded-none': props.config.theme === 'classic',
  }
])

const fieldsContainerClasses = computed(() => [
  'space-y-6',
  {
    'space-y-4': props.config.spacing === 'compact',
    'space-y-6': props.config.spacing === 'default',
    'space-y-8': props.config.spacing === 'spacious',
  },
  {
    'grid grid-cols-1': props.config.layout === 'single-column',
    'grid grid-cols-1 md:grid-cols-2 gap-6': props.config.layout === 'two-column',
    'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6': props.config.layout === 'grid',
  }
])

const submitButtonClasses = computed(() => [
  'px-6 py-3 rounded-lg font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed',
  {
    'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500': props.config.colorScheme === 'primary',
    'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500': props.config.colorScheme === 'secondary',
    'bg-purple-600 hover:bg-purple-700 text-white focus:ring-purple-500': props.config.colorScheme === 'accent',
    'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500': props.config.colorScheme === 'default' || !props.config.colorScheme,
  }
])

// Methods
const updateFieldValue = (fieldName: string, value: any) => {
  formData[fieldName] = value
  
  // Clear field error when user starts typing
  if (fieldErrors.value[fieldName]) {
    clearFieldError(fieldName)
  }
  
  // Validate on change if enabled
  if (props.config.validateOnChange) {
    const field = props.config.fields.find(f => f.name === fieldName)
    if (field) {
      validateField(field, value)
    }
  }
  
  emit('field-change', fieldName, value)
}

const handleFieldBlur = (fieldName: string) => {
  if (props.config.validateOnBlur) {
    const field = props.config.fields.find(f => f.name === fieldName)
    if (field) {
      validateField(field, formData[fieldName])
    }
  }
}

const handleFieldFocus = (fieldName: string) => {
  // Clear validation summary when user focuses on a field
  if (showValidationSummary.value) {
    showValidationSummary.value = false
  }
}

const focusField = (fieldName: string) => {
  nextTick(() => {
    const element = document.getElementById(fieldName)
    if (element) {
      element.focus()
      element.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
  })
}

const handleSubmit = async () => {
  // Check honeypot
  if (honeypotValue.value) {
    console.warn('Honeypot triggered - potential spam submission')
    return
  }
  
  // Validate form
  const isValid = validateForm()
  
  if (!isValid) {
    showValidationSummary.value = true
    // Focus on first error field
    const firstError = validationErrors.value[0]
    if (firstError) {
      focusField(firstError.field)
    }
    return
  }
  
  isSubmitting.value = true
  
  try {
    // Submit form
    await submitForm(formData)
    
    // Emit success
    emit('submit', { ...formData })
    
    // Track form submission
    if (props.config.trackingEnabled && typeof window !== 'undefined' && (window as any).gtag) {
      (window as any).gtag('event', 'form_submit', {
        form_name: props.config.title || 'Form',
        form_fields: props.config.fields.length,
        form_layout: props.config.layout,
        ...(props.config.trackingEvents || []).reduce((acc, event) => {
          acc[event] = true
          return acc
        }, {} as Record<string, boolean>)
      })
    }
    
  } catch (error) {
    console.error('Form submission error:', error)
    
    // Track form error
    if (props.config.trackingEnabled && typeof window !== 'undefined' && (window as any).gtag) {
      (window as any).gtag('event', 'form_error', {
        form_name: props.config.title || 'Form',
        error_message: error instanceof Error ? error.message : 'Unknown error'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    emit('step-change', currentStep.value)
  }
}

const nextStep = () => {
  if (currentStep.value < totalSteps.value) {
    currentStep.value++
    emit('step-change', currentStep.value)
  }
}

// Watch for validation changes
watch(
  [isFormValid, fieldErrors],
  ([valid, errors]) => {
    emit('validation-change', valid, errors)
  },
  { deep: true }
)

// Watch for form data changes for auto-save
watch(
  formData,
  () => {
    if (props.config.enableAutoSave) {
      startAutoSave()
    }
  },
  { deep: true }
)

// Lifecycle
onMounted(() => {
  initializeFormData()
  
  // Start auto-save if enabled
  if (props.config.enableAutoSave) {
    startAutoSave()
  }
  
  // Load reCAPTCHA if enabled
  if (props.config.recaptcha?.enabled && props.config.recaptcha.siteKey) {
    loadRecaptcha()
  }
})

onUnmounted(() => {
  if (props.config.enableAutoSave) {
    stopAutoSave()
  }
})

const loadRecaptcha = () => {
  if (typeof window !== 'undefined' && !(window as any).grecaptcha) {
    const script = document.createElement('script')
    script.src = 'https://www.google.com/recaptcha/api.js'
    script.async = true
    script.defer = true
    document.head.appendChild(script)
  }
}
</script>

<style scoped>
/* Custom focus styles for better accessibility */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px var(--tw-ring-color);
}

/* Smooth transitions for form elements */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .bg-blue-600 {
    background-color: #0000ff;
  }
  
  .text-gray-600 {
    color: #000000;
  }
  
  .border-gray-200 {
    border-color: #000000;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .transition-all,
  .animate-spin {
    animation: none;
    transition: none;
  }
}
</style>