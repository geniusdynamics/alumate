<template>
  <form
    :id="formId"
    :class="formClasses"
    @submit.prevent="handleSubmit"
    :autocomplete="autocomplete"
    novalidate
  >
    <!-- Form Header -->
    <header v-if="title || subtitle" class="form-header">
      <h2 v-if="title" class="form-title">{{ title }}</h2>
      <p v-if="subtitle" class="form-subtitle">{{ subtitle }}</p>
    </header>

    <!-- Form Fields -->
    <div class="form-fields" :class="{ 'grid-layout': useGrid }">
      <touch-optimized-field
        v-for="field in fields"
        :key="field.name"
        :field="field"
        :value="formData[field.name]"
        :errors="fieldErrors[field.name]"
        @update:value="updateField(field.name, $event)"
        @touch-interaction="handleFieldInteraction"
        :disabled="isSubmitting"
        :focus-on-mount="field.autofocus"
      />
    </div>

    <!-- Form Actions -->
    <div class="form-actions" :class="actionsLayout">
      <touch-optimized-button
        v-if="showReset"
        type="reset"
        variant="secondary"
        size="md"
        :disabled="isSubmitting"
        @click="handleReset"
        :aria-label="resetLabel"
      >
        <slot name="reset-icon">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </slot>
        {{ resetLabel }}
      </touch-optimized-button>

      <touch-optimized-button
        type="submit"
        :variant="submitVariant"
        size="md"
        :loading="isSubmitting"
        :disabled="!canSubmit"
        :aria-label="submitLabel"
        class="submit-button"
      >
        <slot name="submit-icon">
          <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <loading-spinner v-else class="w-4 h-4" />
        </slot>
        {{ submitLabel }}
      </touch-optimized-button>
    </div>

    <!-- Form Messages -->
    <div class="form-messages">
      <transition-group name="form-message" tag="div">
        <div
          v-for="(message, index) in formMessages"
          :key="`message-${index}`"
          :class="['form-message', message.type]"
          role="alert"
          :aria-live="message.type === 'error' ? 'assertive' : 'polite'"
        >
          <component :is="getMessageIcon(message.type)" class="message-icon" />
          <span class="message-content">{{ message.text }}</span>
          <button
            v-if="message.dismissible !== false"
            @click="dismissMessage(index)"
            class="message-dismiss"
            :aria-label="`Dismiss ${message.type} message`"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </transition-group>
    </div>

    <!-- Progress Indicator (for multi-step forms) -->
    <touch-progress
      v-if="isMultiStep"
      :current-step="currentStep"
      :total-steps="totalSteps"
      :progress="progress"
      :show-labels="showProgressLabels"
    />

    <!-- Terms and Conditions (optional) -->
    <div v-if="showTerms" class="form-terms">
      <touch-checkbox
        v-model="acceptTerms"
        :label="termsLabel"
        :required="termsRequired"
        :errors="fieldErrors.acceptTerms"
        @update:model-value="handleTermsUpdate"
      />
      <div v-if="termsLink" class="terms-link">
        <a :href="termsLink" target="_blank" rel="noopener noreferrer">{{ termsLinkText }}</a>
      </div>
    </div>

    <!-- Form Footer Content -->
    <footer v-if="$slots.footer" class="form-footer">
      <slot name="footer" />
    </footer>
  </form>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue' // You'll need to create or import this
import TouchOptimizedField from './TouchOptimizedField.vue' // You'll need to create this component
import TouchOptimizedButton from './TouchOptimizedButton.vue' // You'll need to create this component
import TouchProgress from './TouchProgress.vue' // For multi-step forms
import TouchCheckbox from './TouchCheckbox.vue' // For terms checkbox

// Types
interface FormField {
  name: string
  type: 'text' | 'email' | 'password' | 'tel' | 'url' | 'number' | 'textarea' | 'select' | 'checkbox' | 'radio'
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  min?: number
  max?: number
  step?: number
  pattern?: string
  maxlength?: number
  options?: Array<{ label: string; value: any; disabled?: boolean }>
  helpText?: string
  validation?: Array<{
    type: 'required' | 'email' | 'min' | 'max' | 'pattern' | 'custom'
    message: string
    value?: any
    validator?: (value: any) => boolean
  }>
  autofocus?: boolean
  size?: 'sm' | 'md' | 'lg'
}

interface FormMessage {
  text: string
  type: 'success' | 'error' | 'warning' | 'info'
  dismissible?: boolean
  duration?: number
}

interface TouchInteraction {
  type: 'tap' | 'long-press' | 'swipe' | 'focus' | 'blur'
  field: string
  value?: any
  timestamp: number
}

// Props
interface Props {
  modelValue?: Record<string, any>
  fields: FormField[]
  title?: string
  subtitle?: string
  submitLabel?: string
  resetLabel?: string
  submitVariant?: 'primary' | 'secondary' | 'success' | 'danger'
  loading?: boolean
  disabled?: boolean
  readonly?: boolean

  // Layout options
  useGrid?: boolean
  actionsLayout?: 'inline' | 'stacked' | 'centered'

  // Features
  showReset?: boolean
  showProgress?: boolean
  isMultiStep?: boolean
  currentStep?: number
  totalSteps?: number
  showProgressLabels?: boolean

  // Terms and conditions
  showTerms?: boolean
  termsLabel?: string
  termsRequired?: boolean
  termsLink?: string
  termsLinkText?: string

  // Accessibility
  ariaLabel?: string
  ariaDescribedBy?: string

  // Interactive options
  touchEnabled?: boolean
  hapticFeedback?: boolean
  keyboardShortcuts?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  submitLabel: 'Submit',
  resetLabel: 'Reset',
  submitVariant: 'primary',
  actionsLayout: 'inline',
  useGrid: false,
  showReset: false,
  touchEnabled: true,
  hapticFeedback: true,
  keyboardShortcuts: true,
  termsLabel: 'I accept the terms and conditions',
  termsRequired: false,
  termsLinkText: 'View terms',
  currentStep: 1,
  totalSteps: 1
})

// Emits
const emit = defineEmits<{
  submit: [data: Record<string, any>]
  reset: []
  update: [data: Record<string, any>]
  'update:modelValue': [data: Record<string, any>]
  error: [errors: Record<string, string[]>]
  focus: [fieldName: string]
  blur: [fieldName: string]
  validate: [isValid: boolean, errors: Record<string, string[]>]
}>()

// Reactive State
const formData = ref<Record<string, any>>({ ...props.modelValue })
const fieldErrors = ref<Record<string, string[]>>({})
const formMessages = ref<FormMessage[]>([])
const isSubmitting = ref(false)
const canSubmit = ref(true)
const acceptTerms = ref(false)
const currentFocus = ref<string>('')
const formId = ref(`touch-form-${Date.now()}`)

// Computed Properties
const formClasses = computed(() => ({
  'touch-optimized-form': true,
  'form--grid': props.useGrid,
  'form--multistep': props.isMultiStep,
  'form--touch-enabled': props.touchEnabled,
  'form--loading': isSubmitting.value,
  'form--disabled': props.disabled,
  'form--readonly': props.readonly,
  'form--has-footer': !!slots.footer,
  'form--has-terms': props.showTerms
}))

const progress = computed(() => {
  if (!props.isMultiStep) return 0
  return ((props.currentStep - 1) / (props.totalSteps - 1)) * 100
})

const hasErrors = computed(() => Object.keys(fieldErrors.value).length > 0)

const isValid = computed(() => !hasErrors.value && isFormComplete.value)

const isFormComplete = computed(() => {
  return props.fields.every(field => {
    if (field.required && !formData.value[field.name]) {
      return false
    }
    return true
  }) && (!props.termsRequired || acceptTerms.value)
})

// Methods
const updateField = (name: string, value: any) => {
  formData.value[name] = value

  // Clear field-specific errors when user starts typing
  if (fieldErrors.value[name]) {
    fieldErrors.value[name] = []
  }

  // Auto-validate if field has validation rules
  const field = props.fields.find(f => f.name === name)
  if (field?.validation?.some(v => v.type === 'required' && value)) {
    // Real-time validation for important fields
  }

  emit('update:modelValue', formData.value)
}

const validateField = (fieldName: string) => {
  const field = props.fields.find(f => f.name === fieldName)
  const value = formData.value[fieldName]
  const errors: string[] = []

  if (!field) return errors

  // Run validations
  field.validation?.forEach(rule => {
    switch (rule.type) {
      case 'required':
        if (!value || (typeof value === 'string' && value.trim() === '')) {
          errors.push(rule.message)
        }
        break
      case 'email':
        if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          errors.push(rule.message)
        }
        break
      case 'min':
        if (value && (typeof value === 'string' ? value.length : value) < rule.value) {
          errors.push(rule.message)
        }
        break
      case 'max':
        if (value && (typeof value === 'string' ? value.length : value) > rule.value) {
          errors.push(rule.message)
        }
        break
      case 'pattern':
        if (value && !new RegExp(rule.value).test(value)) {
          errors.push(rule.message)
        }
        break
      case 'custom':
        if (rule.validator && !rule.validator(value)) {
          errors.push(rule.message)
        }
        break
    }
  })

  fieldErrors.value[fieldName] = errors
  return errors
}

const validateForm = () => {
  const allErrors: Record<string, string[]> = {}

  props.fields.forEach(field => {
    const errors = validateField(field.name)
    if (errors.length > 0) {
      allErrors[field.name] = errors
    }
  })

  fieldErrors.value = allErrors
  emit('validate', Object.keys(allErrors).length === 0, allErrors)

  return Object.keys(allErrors).length === 0
}

const handleSubmit = async (event: Event) => {
  if (isSubmitting.value) return

  // Validate form
  if (!validateForm()) {
    addMessage('Please fix the errors and try again', 'error')
    // Focus first error field
    const firstErrorField = props.fields.find(f => fieldErrors.value[f.name]?.length)
    if (firstErrorField) {
      focusField(firstErrorField.name)
    }
    return
  }

  // Check terms if required
  if (props.termsRequired && !acceptTerms.value) {
    addMessage('Please accept the terms and conditions', 'error')
    return
  }

  isSubmitting.value = true

  try {
    await emit('submit', formData.value)
    addMessage('Form submitted successfully!', 'success')

    // Reset form on success
    if (props.showReset) {
      resetForm()
    }
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Submission failed'
    addMessage(message, 'error')
    emit('error', fieldErrors.value)
  } finally {
    isSubmitting.value = false
  }
}

const handleReset = () => {
  resetForm()
  emit('reset')
}

const resetForm = () => {
  formData.value = {}
  fieldErrors.value = {}
  formMessages.value = []
  acceptTerms.value = false
  emit('update:modelValue', formData.value)
}

const handleFieldInteraction = (interaction: TouchInteraction) => {
  // Handle touch interactions like haptic feedback
  if (props.hapticFeedback && window.navigator?.vibrate) {
    switch (interaction.type) {
      case 'tap':
        navigator.vibrate(10)
        break
      case 'long-press':
        navigator.vibrate(50)
        break
    }
  }

  // Emit interaction for parent components
  emit('touch-interaction', interaction)
}

const handleTermsUpdate = (value: boolean) => {
  acceptTerms.value = value
  // Re-validate terms field
  if (props.termsRequired && !value) {
    fieldErrors.value.acceptTerms = ['Please accept the terms and conditions']
  } else {
    delete fieldErrors.value.acceptTerms
  }
}

const addMessage = (text: string, type: FormMessage['type'] = 'info', dismissible = true, duration = 5000) => {
  const message: FormMessage = {
    text,
    type,
    dismissible
  }

  formMessages.value.push(message)

  // Auto-dismiss after duration
  if (dismissible && duration > 0) {
    setTimeout(() => {
      const index = formMessages.value.indexOf(message)
      if (index > -1) {
        formMessages.value.splice(index, 1)
      }
    }, duration)
  }
}

const dismissMessage = (index: number) => {
  formMessages.value.splice(index, 1)
}

const getMessageIcon = (type: FormMessage['type']) => {
  const icons = {
    success: 'CheckCircleIcon',
    error: 'ExclamationCircleIcon',
    warning: 'ExclamationTriangleIcon',
    info: 'InformationCircleIcon'
  }
  return icons[type] || 'InformationCircleIcon'
}

const focusField = (fieldName: string) => {
  nextTick(() => {
    const field = document.querySelector(`#${formId.value} [name="${fieldName}"]`) as HTMLElement
    field?.focus()
  })
}

// Keyboard shortcuts
let keyboardShortcutHandler: ((event: KeyboardEvent) => void) | null = null

const setupKeyboardShortcuts = () => {
  if (!props.keyboardShortcuts) return

  keyboardShortcutHandler = (event: KeyboardEvent) => {
    // Submit form with Ctrl+Enter
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
      event.preventDefault()
      handleSubmit(new Event('submit'))
    }

    // Reset form with Ctrl+R
    if ((event.ctrlKey || event.metaKey) && event.key === 'r' && props.showReset) {
      event.preventDefault()
      handleReset()
    }
  }

  document.addEventListener('keydown', keyboardShortcutHandler)
}

const cleanupKeyboardShortcuts = () => {
  if (keyboardShortcutHandler) {
    document.removeEventListener('keydown', keyboardShortcutHandler)
  }
}

// Lifecycle
onMounted(() => {
  setupKeyboardShortcuts()
})

onBeforeUnmount(() => {
  cleanupKeyboardShortcuts()
})

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    formData.value = { ...newValue }
  }
}, { deep: true })

watch(canSubmit, (newValue) => {
  canSubmit.value = isValid.value && !props.disabled && !props.readonly
})

watch(() => [isValid.value, props.disabled, props.readonly], () => {
  canSubmit.value = isValid.value && !props.disabled && !props.readonly
})
</script>

<style scoped>
.touch-optimized-form {
  @apply max-w-md mx-auto p-4 space-y-6;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

/* Form Header */
.form-header {
  @apply text-center mb-8;
}

.form-title {
  @apply text-2xl font-semibold text-gray-900 dark:text-white mb-2;
  font-size: clamp(1.25rem, 4vw, 1.5rem);
}

.form-subtitle {
  @apply text-gray-600 dark:text-gray-400;
  font-size: clamp(0.875rem, 3vw, 1rem);
}

/* Form Fields */
.form-fields {
  @apply space-y-6;
}

.form-fields.grid-layout {
  @apply grid grid-cols-1 gap-6;
}

/* Form Actions */
.form-actions {
  @apply flex gap-4 mt-8;
}

.form-actions.inline {
  @apply justify-end;
}

.form-actions.stacked {
  @apply flex-col space-y-4;
}

.form-actions.centered {
  @apply justify-center;
}

.submit-button {
  @apply flex-shrink-0;
}

/* Form Messages */
.form-messages {
  @apply mt-4;
}

.form-message {
  @apply flex items-start gap-3 p-4 rounded-lg transition-all duration-300;
  min-height: 48px; /* Touch-friendly minimum */
}

.form-message.success {
  @apply bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300;
}

.form-message.error {
  @apply bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300;
}

.form-message.warning {
  @apply bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300;
}

.form-message.info {
  @apply bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300;
}

.message-icon {
  @apply flex-shrink-0 w-5 h-5 mt-0.5;
}

.message-dismiss {
  @apply flex-shrink-0 ml-2 p-1 hover:bg-black/10 dark:hover:bg-white/10 rounded;
  min-width: 32px;
  min-height: 32px;
}

/* Form Terms */
.form-terms {
  @apply mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg;
}

.terms-link {
  @apply mt-2;
}

.terms-link a {
  @apply text-blue-600 dark:text-blue-400 underline hover:no-underline;
}

/* Form Footer */
.form-footer {
  @apply mt-8 pt-6 border-t border-gray-200 dark:border-gray-700;
}

/* Message Transitions */
.form-message-enter-active,
.form-message-leave-active {
  @apply transition-all duration-300;
}

.form-message-enter-from {
  @apply opacity-0 transform translate-y-2;
}

.form-message-leave-to {
  @apply opacity-0 transform -translate-y-2;
}

/* Responsive Design */
@media (max-width: 480px) {
  .touch-optimized-form {
    @apply p-3;
  }

  .form-actions {
    @apply gap-3;
  }

  .form-actions.stacked {
    @apply space-y-3;
  }

  .form-message {
    @apply p-3;
  }
}

/* Touch Device Optimizations */
@media (pointer: coarse) {
  .form-fields {
    @apply space-y-8; /* More spacing on touch devices */
  }

  /* Ensure all interactive elements meet minimum touch target */
  .form-actions button,
  .message-dismiss {
    min-height: 48px !important;
    min-width: 48px !important;
  }
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
  .form-message {
    border-width: 2px;
  }
}

/* Reduced Motion Support */
@media (prefers-reduced-motion: reduce) {
  .form-message-enter-active,
  .form-message-leave-active {
    @apply transition-none;
  }

  .form-message-enter-from,
  .form-message-leave-to {
    @apply transform-none;
  }
}

/* Focus Styles */
.form-message:focus-within {
  @apply ring-2 ring-blue-500 ring-offset-2;
}

/* Print Styles */
@media print {
  .form-actions,
  .form-messages {
    display: none;
  }
}
</style>