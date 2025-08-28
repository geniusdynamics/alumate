<template>
  <div :class="fieldContainerClasses">
    <!-- Field Label -->
    <label
      v-if="field.type !== 'checkbox'"
      :for="fieldId"
      :class="labelClasses"
    >
      {{ field.label }}
      <span
        v-if="field.required"
        class="text-red-500 ml-1"
        aria-label="required"
      >
        *
      </span>
    </label>

    <!-- Field Input -->
    <div class="relative">
      <!-- Text Input -->
      <input
        v-if="field.type === 'text'"
        :id="fieldId"
        type="text"
        :name="field.name"
        :value="value"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="disabled"
        :readonly="field.readonly"
        :pattern="field.pattern"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Email Input -->
      <input
        v-else-if="field.type === 'email'"
        :id="fieldId"
        type="email"
        :name="field.name"
        :value="value"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="disabled"
        :readonly="field.readonly"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="inputClasses"
        autocomplete="email"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Phone Input -->
      <input
        v-else-if="field.type === 'phone'"
        :id="fieldId"
        type="tel"
        :name="field.name"
        :value="value"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="disabled"
        :readonly="field.readonly"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="inputClasses"
        autocomplete="tel"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Number Input -->
      <input
        v-else-if="field.type === 'number'"
        :id="fieldId"
        type="number"
        :name="field.name"
        :value="value"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="disabled"
        :readonly="field.readonly"
        :min="field.min"
        :max="field.max"
        :step="field.step"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- URL Input -->
      <input
        v-else-if="field.type === 'url'"
        :id="fieldId"
        type="url"
        :name="field.name"
        :value="value"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="disabled"
        :readonly="field.readonly"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Date Input -->
      <input
        v-else-if="field.type === 'date'"
        :id="fieldId"
        type="date"
        :name="field.name"
        :value="value"
        :required="field.required"
        :disabled="disabled"
        :readonly="field.readonly"
        :min="field.min"
        :max="field.max"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Textarea -->
      <textarea
        v-else-if="field.type === 'textarea'"
        :id="fieldId"
        :name="field.name"
        :value="value"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="disabled"
        :readonly="field.readonly"
        :rows="field.rows || 4"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="textareaClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Select -->
      <select
        v-else-if="field.type === 'select'"
        :id="fieldId"
        :name="field.name"
        :value="value"
        :required="field.required"
        :disabled="disabled"
        :multiple="field.multiple"
        :aria-label="field.ariaLabel"
        :aria-describedby="getAriaDescribedBy()"
        :class="selectClasses"
        @change="handleSelect"
        @blur="handleBlur"
        @focus="handleFocus"
      >
        <option value="" v-if="!field.required && !field.multiple">
          {{ field.placeholder || 'Select an option' }}
        </option>
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
      <div
        v-else-if="field.type === 'checkbox'"
        class="flex items-start"
      >
        <input
          :id="fieldId"
          type="checkbox"
          :name="field.name"
          :checked="value"
          :required="field.required"
          :disabled="disabled"
          :readonly="field.readonly"
          :aria-label="field.ariaLabel"
          :aria-describedby="getAriaDescribedBy()"
          :class="checkboxClasses"
          @change="handleCheckbox"
          @blur="handleBlur"
          @focus="handleFocus"
        />
        <label
          :for="fieldId"
          :class="checkboxLabelClasses"
        >
          {{ field.label }}
          <span
            v-if="field.required"
            class="text-red-500 ml-1"
            aria-label="required"
          >
            *
          </span>
        </label>
      </div>

      <!-- Radio Group -->
      <fieldset
        v-else-if="field.type === 'radio'"
        :aria-label="field.label"
        :aria-describedby="getAriaDescribedBy()"
        class="space-y-2"
      >
        <legend class="sr-only">{{ field.label }}</legend>
        <div
          v-for="option in field.options"
          :key="option.value"
          class="flex items-center"
        >
          <input
            :id="`${fieldId}-${option.value}`"
            type="radio"
            :name="field.name"
            :value="option.value"
            :checked="value === option.value"
            :required="field.required"
            :disabled="disabled || option.disabled"
            :class="radioClasses"
            @change="handleRadio"
            @blur="handleBlur"
            @focus="handleFocus"
          />
          <label
            :for="`${fieldId}-${option.value}`"
            :class="radioLabelClasses"
          >
            {{ option.label }}
          </label>
        </div>
      </fieldset>

      <!-- Drag Handle for Field Builder -->
      <button
        v-if="draggable"
        type="button"
        class="absolute -left-8 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 cursor-grab active:cursor-grabbing focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
        :aria-label="`Drag to reorder ${field.label} field`"
        @mousedown="startDrag"
        @touchstart="startDrag"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
          <path d="M7 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
        </svg>
      </button>
    </div>

    <!-- Help Text -->
    <p
      v-if="field.helpText"
      :id="`${fieldId}-help`"
      class="mt-1 text-sm text-gray-600 dark:text-gray-400"
    >
      {{ field.helpText }}
    </p>

    <!-- Error Message -->
    <div
      v-if="error"
      :id="`${fieldId}-error`"
      class="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center"
      role="alert"
      aria-live="polite"
    >
      <svg
        class="w-4 h-4 mr-1 flex-shrink-0"
        fill="currentColor"
        viewBox="0 0 20 20"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
          clip-rule="evenodd"
        />
      </svg>
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { FormField } from '@/types/components'

interface Props {
  field: FormField
  value: any
  error?: string
  disabled?: boolean
  formId: string
  draggable?: boolean
}

interface Emits {
  (e: 'update:value', value: any): void
  (e: 'blur'): void
  (e: 'focus'): void
  (e: 'drag-start', event: MouseEvent | TouchEvent): void
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
  draggable: false
})

const emit = defineEmits<Emits>()

// Computed properties
const fieldId = computed(() => `${props.formId}-${props.field.name}`)

const fieldContainerClasses = computed(() => [
  'relative',
  {
    'w-full': props.field.width === 'full',
    'w-1/2': props.field.width === 'half',
    'w-1/3': props.field.width === 'third',
    'w-1/4': props.field.width === 'quarter',
  },
  props.field.className || ''
])

const labelClasses = computed(() => [
  'block text-sm font-medium mb-2',
  {
    'text-gray-700 dark:text-gray-300': !props.error,
    'text-red-700 dark:text-red-400': props.error,
  }
])

const baseInputClasses = [
  'block w-full px-3 py-2 border rounded-lg shadow-sm transition-colors duration-200',
  'focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
  'disabled:opacity-50 disabled:cursor-not-allowed',
  'placeholder-gray-400 dark:placeholder-gray-500'
]

const inputClasses = computed(() => [
  ...baseInputClasses,
  {
    'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500': !props.error,
    'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20 text-red-900 dark:text-red-100 focus:border-red-500 focus:ring-red-500': props.error,
  }
])

const textareaClasses = computed(() => [
  ...baseInputClasses,
  'resize-vertical min-h-[100px]',
  {
    'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500': !props.error,
    'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20 text-red-900 dark:text-red-100 focus:border-red-500 focus:ring-red-500': props.error,
  }
])

const selectClasses = computed(() => [
  ...baseInputClasses,
  'pr-10 bg-no-repeat bg-right bg-[length:16px_16px] bg-[position:right_12px_center]',
  "bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQgNkw4IDEwTDEyIDYiIHN0cm9rZT0iIzZCNzI4MCIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPC9zdmc+')]",
  {
    'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500': !props.error,
    'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20 text-red-900 dark:text-red-100 focus:border-red-500 focus:ring-red-500': props.error,
  }
])

const checkboxClasses = computed(() => [
  'h-4 w-4 rounded border-2 transition-colors duration-200',
  'focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
  'disabled:opacity-50 disabled:cursor-not-allowed',
  {
    'text-blue-600 border-gray-300 dark:border-gray-600 focus:ring-blue-500': !props.error,
    'text-red-600 border-red-300 dark:border-red-600 focus:ring-red-500': props.error,
  }
])

const checkboxLabelClasses = computed(() => [
  'ml-3 text-sm font-medium cursor-pointer',
  {
    'text-gray-700 dark:text-gray-300': !props.error,
    'text-red-700 dark:text-red-400': props.error,
  }
])

const radioClasses = computed(() => [
  'h-4 w-4 border-2 transition-colors duration-200',
  'focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
  'disabled:opacity-50 disabled:cursor-not-allowed',
  {
    'text-blue-600 border-gray-300 dark:border-gray-600 focus:ring-blue-500': !props.error,
    'text-red-600 border-red-300 dark:border-red-600 focus:ring-red-500': props.error,
  }
])

const radioLabelClasses = computed(() => [
  'ml-3 text-sm font-medium cursor-pointer',
  {
    'text-gray-700 dark:text-gray-300': !props.error,
    'text-red-700 dark:text-red-400': props.error,
  }
])

// Methods
const getAriaDescribedBy = () => {
  const describedBy = []
  
  if (props.field.helpText) {
    describedBy.push(`${fieldId.value}-help`)
  }
  
  if (props.error) {
    describedBy.push(`${fieldId.value}-error`)
  }
  
  if (props.field.ariaDescribedBy) {
    describedBy.push(props.field.ariaDescribedBy)
  }
  
  return describedBy.length > 0 ? describedBy.join(' ') : undefined
}

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement | HTMLTextAreaElement
  emit('update:value', target.value)
}

const handleSelect = (event: Event) => {
  const target = event.target as HTMLSelectElement
  
  if (props.field.multiple) {
    const selectedValues = Array.from(target.selectedOptions).map(option => option.value)
    emit('update:value', selectedValues)
  } else {
    emit('update:value', target.value)
  }
}

const handleCheckbox = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:value', target.checked)
}

const handleRadio = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:value', target.value)
}

const handleBlur = () => {
  emit('blur')
}

const handleFocus = () => {
  emit('focus')
}

const startDrag = (event: MouseEvent | TouchEvent) => {
  emit('drag-start', event)
}
</script>

<style scoped>
/* Custom focus styles for better accessibility */
input:focus,
textarea:focus,
select:focus {
  box-shadow: 0 0 0 2px var(--tw-ring-color);
}

/* Improve checkbox and radio button styling */
input[type="checkbox"],
input[type="radio"] {
  appearance: none;
  background-color: white;
  margin: 0;
  font: inherit;
  color: currentColor;
  border: 2px solid currentColor;
  display: grid;
  place-content: center;
}

input[type="checkbox"]:checked,
input[type="radio"]:checked {
  background-color: currentColor;
}

input[type="checkbox"]:checked::before {
  content: "✓";
  color: white;
  font-size: 0.75rem;
  font-weight: bold;
}

input[type="radio"] {
  border-radius: 50%;
}

input[type="radio"]:checked::before {
  content: "";
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background-color: white;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  input,
  textarea,
  select {
    border-width: 2px;
  }
  
  input:focus,
  textarea:focus,
  select:focus {
    outline: 2px solid;
    outline-offset: 2px;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  input,
  textarea,
  select {
    transition: none;
  }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
  input,
  textarea,
  select,
  button {
    min-height: 44px; /* Minimum touch target size */
  }
  
  input[type="checkbox"],
  input[type="radio"] {
    min-width: 44px;
    min-height: 44px;
  }
}
</style>