<!-- ABOUTME: Touch-optimized form field component for mobile-friendly user input -->
<!-- ABOUTME: Provides enhanced touch interactions, validation, and accessibility features -->
<template>
  <div 
    :class="fieldClasses"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
  >
    <!-- Field Label -->
    <label 
      v-if="field.label"
      :for="fieldId"
      :class="labelClasses"
    >
      {{ field.label }}
      <span v-if="field.required" class="required-indicator">*</span>
    </label>

    <!-- Text Input Fields -->
    <input
      v-if="isTextInput"
      :id="fieldId"
      :type="field.type"
      :name="field.name"
      :value="value"
      :placeholder="field.placeholder"
      :required="field.required"
      :disabled="disabled || field.disabled"
      :readonly="field.readonly"
      :min="field.min"
      :max="field.max"
      :step="field.step"
      :pattern="field.pattern"
      :maxlength="field.maxlength"
      :class="inputClasses"
      :aria-describedby="helpTextId"
      :aria-invalid="hasErrors"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      @touchstart="handleInputTouchStart"
      ref="inputRef"
    />

    <!-- Textarea -->
    <textarea
      v-else-if="field.type === 'textarea'"
      :id="fieldId"
      :name="field.name"
      :value="value"
      :placeholder="field.placeholder"
      :required="field.required"
      :disabled="disabled || field.disabled"
      :readonly="field.readonly"
      :maxlength="field.maxlength"
      :class="textareaClasses"
      :aria-describedby="helpTextId"
      :aria-invalid="hasErrors"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      rows="4"
      ref="inputRef"
    ></textarea>

    <!-- Select Dropdown -->
    <select
      v-else-if="field.type === 'select'"
      :id="fieldId"
      :name="field.name"
      :value="value"
      :required="field.required"
      :disabled="disabled || field.disabled"
      :class="selectClasses"
      :aria-describedby="helpTextId"
      :aria-invalid="hasErrors"
      @change="handleChange"
      @focus="handleFocus"
      @blur="handleBlur"
      ref="inputRef"
    >
      <option value="" v-if="field.placeholder">{{ field.placeholder }}</option>
      <option 
        v-for="option in field.options" 
        :key="option.value"
        :value="option.value"
        :disabled="option.disabled"
      >
        {{ option.label }}
      </option>
    </select>

    <!-- Checkbox -->
    <div v-else-if="field.type === 'checkbox'" class="checkbox-wrapper">
      <input
        :id="fieldId"
        type="checkbox"
        :name="field.name"
        :checked="value"
        :required="field.required"
        :disabled="disabled || field.disabled"
        :class="checkboxClasses"
        :aria-describedby="helpTextId"
        :aria-invalid="hasErrors"
        @change="handleCheckboxChange"
        @focus="handleFocus"
        @blur="handleBlur"
        ref="inputRef"
      />
      <label :for="fieldId" class="checkbox-label">
        {{ field.label }}
        <span v-if="field.required" class="required-indicator">*</span>
      </label>
    </div>

    <!-- Radio Group -->
    <div v-else-if="field.type === 'radio'" class="radio-group">
      <div 
        v-for="option in field.options" 
        :key="option.value"
        class="radio-option"
      >
        <input
          :id="`${fieldId}-${option.value}`"
          type="radio"
          :name="field.name"
          :value="option.value"
          :checked="value === option.value"
          :required="field.required"
          :disabled="disabled || field.disabled || option.disabled"
          :class="radioClasses"
          :aria-describedby="helpTextId"
          @change="handleRadioChange"
          @focus="handleFocus"
          @blur="handleBlur"
        />
        <label :for="`${fieldId}-${option.value}`" class="radio-label">
          {{ option.label }}
        </label>
      </div>
    </div>

    <!-- Help Text -->
    <div 
      v-if="field.helpText"
      :id="helpTextId"
      class="help-text"
    >
      {{ field.helpText }}
    </div>

    <!-- Error Messages -->
    <div 
      v-if="hasErrors"
      class="error-messages"
      role="alert"
    >
      <div 
        v-for="error in errors" 
        :key="error"
        class="error-message"
      >
        {{ error }}
      </div>
    </div>

    <!-- Touch Feedback -->
    <div 
      v-if="showTouchFeedback"
      class="touch-feedback"
      :class="touchFeedbackClass"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue'

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

interface Props {
  field: FormField
  value?: any
  errors?: string[]
  disabled?: boolean
  focusOnMount?: boolean
}

interface Emits {
  'update:value': [value: any]
  'touch-interaction': [data: { field: string; type: string; timestamp: number }]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Refs
const inputRef = ref<HTMLElement>()
const showTouchFeedback = ref(false)
const touchFeedbackClass = ref('')
const isFocused = ref(false)
const isTouched = ref(false)

// Computed
const fieldId = computed(() => `field-${props.field.name}`)
const helpTextId = computed(() => `${fieldId.value}-help`)
const hasErrors = computed(() => props.errors && props.errors.length > 0)

const isTextInput = computed(() => {
  return ['text', 'email', 'password', 'tel', 'url', 'number'].includes(props.field.type)
})

const fieldClasses = computed(() => [
  'touch-optimized-field',
  `field-${props.field.type}`,
  `field-size-${props.field.size || 'md'}`,
  {
    'field-focused': isFocused.value,
    'field-touched': isTouched.value,
    'field-disabled': props.disabled || props.field.disabled,
    'field-readonly': props.field.readonly,
    'field-required': props.field.required,
    'field-error': hasErrors.value,
  }
])

const labelClasses = computed(() => [
  'field-label',
  {
    'label-focused': isFocused.value,
    'label-error': hasErrors.value,
  }
])

const inputClasses = computed(() => [
  'field-input',
  'touch-input',
  {
    'input-error': hasErrors.value,
    'input-focused': isFocused.value,
  }
])

const textareaClasses = computed(() => [
  'field-textarea',
  'touch-input',
  {
    'textarea-error': hasErrors.value,
    'textarea-focused': isFocused.value,
  }
])

const selectClasses = computed(() => [
  'field-select',
  'touch-input',
  {
    'select-error': hasErrors.value,
    'select-focused': isFocused.value,
  }
])

const checkboxClasses = computed(() => [
  'field-checkbox',
  'touch-checkbox',
  {
    'checkbox-error': hasErrors.value,
  }
])

const radioClasses = computed(() => [
  'field-radio',
  'touch-radio',
  {
    'radio-error': hasErrors.value,
  }
])

// Methods
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement | HTMLTextAreaElement
  emit('update:value', target.value)
  emitTouchInteraction('input')
}

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:value', target.value)
  emitTouchInteraction('change')
}

const handleCheckboxChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:value', target.checked)
  emitTouchInteraction('checkbox-change')
}

const handleRadioChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:value', target.value)
  emitTouchInteraction('radio-change')
}

const handleFocus = () => {
  isFocused.value = true
  emitTouchInteraction('focus')
}

const handleBlur = () => {
  isFocused.value = false
  isTouched.value = true
  emitTouchInteraction('blur')
}

const handleTouchStart = () => {
  showTouchFeedback.value = true
  touchFeedbackClass.value = 'touch-start'
  emitTouchInteraction('touch-start')
}

const handleTouchEnd = () => {
  setTimeout(() => {
    showTouchFeedback.value = false
    touchFeedbackClass.value = ''
  }, 150)
  emitTouchInteraction('touch-end')
}

const handleInputTouchStart = (event: TouchEvent) => {
  // Prevent double-tap zoom on mobile
  event.preventDefault()
  if (inputRef.value) {
    inputRef.value.focus()
  }
}

const emitTouchInteraction = (type: string) => {
  emit('touch-interaction', {
    field: props.field.name,
    type,
    timestamp: Date.now()
  })
}

// Lifecycle
onMounted(() => {
  if (props.focusOnMount && inputRef.value) {
    nextTick(() => {
      inputRef.value?.focus()
    })
  }
})
</script>

<style scoped>
.touch-optimized-field {
  @apply relative mb-6;
}

.field-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
  transition: color 0.2s ease;
}

.label-focused {
  @apply text-blue-600 dark:text-blue-400;
}

.label-error {
  @apply text-red-600 dark:text-red-400;
}

.required-indicator {
  @apply text-red-500 ml-1;
}

.touch-input {
  @apply w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg;
  @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  @apply transition-all duration-200 ease-in-out;
  min-height: 48px; /* Touch-friendly minimum height */
  font-size: 16px; /* Prevent zoom on iOS */
}

.field-input {
  @apply touch-input;
}

.field-textarea {
  @apply touch-input;
  min-height: 120px;
  resize: vertical;
}

.field-select {
  @apply touch-input;
  @apply appearance-none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 12px center;
  background-repeat: no-repeat;
  background-size: 16px 12px;
  padding-right: 40px;
}

.input-error,
.textarea-error,
.select-error {
  @apply border-red-500 ring-red-500;
}

.input-focused,
.textarea-focused,
.select-focused {
  @apply ring-2 ring-blue-500 border-blue-500;
}

.checkbox-wrapper {
  @apply flex items-start gap-3;
}

.field-checkbox {
  @apply w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded;
  @apply focus:ring-2 focus:ring-blue-500;
  @apply bg-white dark:bg-gray-800;
  margin-top: 2px; /* Align with first line of label */
}

.checkbox-label {
  @apply text-sm text-gray-700 dark:text-gray-300 cursor-pointer;
  @apply select-none;
}

.radio-group {
  @apply space-y-3;
}

.radio-option {
  @apply flex items-start gap-3;
}

.field-radio {
  @apply w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600;
  @apply focus:ring-2 focus:ring-blue-500;
  @apply bg-white dark:bg-gray-800;
  margin-top: 2px;
}

.radio-label {
  @apply text-sm text-gray-700 dark:text-gray-300 cursor-pointer;
  @apply select-none;
}

.checkbox-error,
.radio-error {
  @apply border-red-500 ring-red-500;
}

.help-text {
  @apply mt-2 text-sm text-gray-600 dark:text-gray-400;
}

.error-messages {
  @apply mt-2 space-y-1;
}

.error-message {
  @apply text-sm text-red-600 dark:text-red-400;
  @apply flex items-center gap-1;
}

.error-message::before {
  content: '⚠';
  @apply text-red-500;
}

.touch-feedback {
  @apply absolute inset-0 pointer-events-none rounded-lg;
  @apply transition-all duration-150 ease-out;
}

.touch-start {
  @apply bg-blue-100 dark:bg-blue-900 opacity-20;
}

.field-disabled {
  @apply opacity-60 cursor-not-allowed;
}

.field-disabled .touch-input,
.field-disabled .field-checkbox,
.field-disabled .field-radio {
  @apply bg-gray-100 dark:bg-gray-700 cursor-not-allowed;
}

.field-readonly .touch-input {
  @apply bg-gray-50 dark:bg-gray-700;
}

/* Size variants */
.field-size-sm .touch-input {
  @apply px-3 py-2 text-sm;
  min-height: 40px;
}

.field-size-lg .touch-input {
  @apply px-5 py-4 text-lg;
  min-height: 56px;
}

/* Touch-specific enhancements */
@media (hover: none) and (pointer: coarse) {
  .touch-input {
    min-height: 52px; /* Larger touch targets on mobile */
  }
  
  .field-checkbox,
  .field-radio {
    @apply w-6 h-6; /* Larger checkboxes/radios on touch devices */
  }
}

/* Focus visible for keyboard navigation */
.touch-input:focus-visible,
.field-checkbox:focus-visible,
.field-radio:focus-visible {
  @apply outline-2 outline-offset-2 outline-blue-500;
}
</style>