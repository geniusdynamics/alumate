<template>
    <div class="base-input-wrapper" :class="{ 'base-input-wrapper--error': hasError }">
        <!-- Label -->
        <label
            v-if="label"
            :for="inputId"
            class="base-input__label"
            :class="{ 'base-input__label--required': required }"
        >
            {{ label }}
            <span v-if="required" class="base-input__required" aria-label="required">*</span>
        </label>

        <!-- Input container -->
        <div class="base-input__container" :class="containerClasses">
            <!-- Left icon -->
            <component
                v-if="leftIcon"
                :is="leftIcon"
                class="base-input__icon base-input__icon--left"
                aria-hidden="true"
            />

            <!-- Input element -->
            <component
                :is="inputComponent"
                :id="inputId"
                ref="inputRef"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :autocomplete="autocomplete"
                :autocapitalize="autocapitalize"
                :autocorrect="autocorrect"
                :spellcheck="spellcheck"
                :min="min"
                :max="max"
                :step="step"
                :minlength="minlength"
                :maxlength="maxlength"
                :pattern="pattern"
                :rows="rows"
                :cols="cols"
                :aria-label="ariaLabel"
                :aria-describedby="ariaDescribedby"
                :aria-invalid="hasError"
                :aria-required="required"
                class="base-input__field"
                :class="[
                    sizeClasses,
                    {
                        'base-input__field--error': hasError,
                        'base-input__field--disabled': disabled,
                        'base-input__field--readonly': readonly,
                        'base-input__field--with-left-icon': leftIcon,
                        'base-input__field--with-right-icon': rightIcon || clearable || hasError
                    }
                ]"
                @input="handleInput"
                @change="handleChange"
                @focus="handleFocus"
                @blur="handleBlur"
                @keydown="handleKeydown"
            />

            <!-- Right icon -->
            <component
                v-if="rightIcon && !clearable && !hasError"
                :is="rightIcon"
                class="base-input__icon base-input__icon--right"
                aria-hidden="true"
            />

            <!-- Clear button -->
            <button
                v-if="clearable && modelValue && !disabled && !readonly"
                type="button"
                class="base-input__clear"
                :aria-label="`Clear ${label || 'input'}`"
                @click="handleClear"
            >
                <XMarkIcon class="h-4 w-4" />
            </button>

            <!-- Error icon -->
            <ExclamationCircleIcon
                v-if="hasError"
                class="base-input__icon base-input__icon--right base-input__icon--error"
                aria-hidden="true"
            />

            <!-- Loading spinner -->
            <div
                v-if="loading"
                class="base-input__spinner"
                aria-hidden="true"
            >
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Help text -->
        <p
            v-if="helpText && !hasError"
            :id="`${inputId}-help`"
            class="base-input__help"
        >
            {{ helpText }}
        </p>

        <!-- Error message -->
        <p
            v-if="hasError"
            :id="`${inputId}-error`"
            class="base-input__error"
            role="alert"
            aria-live="polite"
        >
            {{ errorMessage }}
        </p>

        <!-- Character count -->
        <p
            v-if="showCharacterCount && maxlength"
            class="base-input__count"
            :class="{ 'base-input__count--warning': isNearLimit }"
        >
            {{ characterCount }}/{{ maxlength }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import { XMarkIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

interface Props {
    // Input type and behavior
    type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'textarea'
    modelValue?: string | number
    placeholder?: string
    
    // Validation
    required?: boolean
    disabled?: boolean
    readonly?: boolean
    error?: string | boolean
    
    // Attributes
    autocomplete?: string
    autocapitalize?: string
    autocorrect?: string
    spellcheck?: boolean
    min?: number | string
    max?: number | string
    step?: number | string
    minlength?: number
    maxlength?: number
    pattern?: string
    
    // Textarea specific
    rows?: number
    cols?: number
    
    // Appearance
    size?: 'sm' | 'md' | 'lg'
    leftIcon?: any
    rightIcon?: any
    clearable?: boolean
    loading?: boolean
    
    // Labels and help
    label?: string
    helpText?: string
    showCharacterCount?: boolean
    
    // Accessibility
    ariaLabel?: string
    ariaDescribedby?: string
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    size: 'md',
    spellcheck: true,
    clearable: false,
    loading: false,
    showCharacterCount: false
})

const emit = defineEmits<{
    'update:modelValue': [value: string | number]
    input: [event: Event]
    change: [event: Event]
    focus: [event: FocusEvent]
    blur: [event: FocusEvent]
    clear: []
}>()

const inputRef = ref<HTMLInputElement | HTMLTextAreaElement>()

// Generate unique ID for accessibility
const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`)

// Determine input component type
const inputComponent = computed(() => props.type === 'textarea' ? 'textarea' : 'input')

// Error state
const hasError = computed(() => Boolean(props.error))
const errorMessage = computed(() => typeof props.error === 'string' ? props.error : '')

// ARIA describedby
const ariaDescribedby = computed(() => {
    const ids = []
    if (props.ariaDescribedby) ids.push(props.ariaDescribedby)
    if (props.helpText && !hasError.value) ids.push(`${inputId.value}-help`)
    if (hasError.value) ids.push(`${inputId.value}-error`)
    return ids.length > 0 ? ids.join(' ') : undefined
})

// Size classes
const sizeClasses = computed(() => {
    const sizes = {
        sm: 'base-input__field--sm',
        md: 'base-input__field--md',
        lg: 'base-input__field--lg'
    }
    return sizes[props.size]
})

// Container classes
const containerClasses = computed(() => ({
    'base-input__container--error': hasError.value,
    'base-input__container--disabled': props.disabled,
    'base-input__container--readonly': props.readonly,
    'base-input__container--loading': props.loading
}))

// Character count
const characterCount = computed(() => {
    const value = String(props.modelValue || '')
    return value.length
})

const isNearLimit = computed(() => {
    if (!props.maxlength) return false
    return characterCount.value > props.maxlength * 0.8
})

// Event handlers
const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement | HTMLTextAreaElement
    let value: string | number = target.value
    
    if (props.type === 'number') {
        value = target.valueAsNumber || 0
    }
    
    emit('update:modelValue', value)
    emit('input', event)
}

const handleChange = (event: Event) => {
    emit('change', event)
}

const handleFocus = (event: FocusEvent) => {
    emit('focus', event)
}

const handleBlur = (event: FocusEvent) => {
    emit('blur', event)
}

const handleKeydown = (event: KeyboardEvent) => {
    // Handle Escape key to clear input
    if (event.key === 'Escape' && props.clearable && props.modelValue) {
        handleClear()
    }
}

const handleClear = () => {
    emit('update:modelValue', '')
    emit('clear')
    
    // Focus the input after clearing
    nextTick(() => {
        inputRef.value?.focus()
    })
}

// Expose methods for parent components
defineExpose({
    focus: () => inputRef.value?.focus(),
    blur: () => inputRef.value?.blur(),
    select: () => inputRef.value?.select()
})
</script>

<style scoped>
/* Base input wrapper */
.base-input-wrapper {
    @apply w-full;
}

.base-input-wrapper--error {
    /* Error state handled by individual elements */
}

/* Label */
.base-input__label {
    @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1;
}

.base-input__label--required {
    /* Required indicator handled by span */
}

.base-input__required {
    @apply text-red-500 ml-1;
}

/* Input container */
.base-input__container {
    @apply relative;
}

/* Input field */
.base-input__field {
    @apply w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors;
    
    /* Ensure minimum touch target size */
    min-height: 44px;
}

/* Size variants */
.base-input__field--sm {
    @apply px-3 py-2 text-sm;
    min-height: 36px;
}

.base-input__field--md {
    @apply px-4 py-2.5 text-sm;
}

.base-input__field--lg {
    @apply px-4 py-3 text-base;
    min-height: 48px;
}

/* State variants */
.base-input__field--error {
    @apply border-red-300 dark:border-red-600 focus:ring-red-500;
}

.base-input__field--disabled {
    @apply bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed;
}

.base-input__field--readonly {
    @apply bg-gray-50 dark:bg-gray-750 cursor-default;
}

/* Icon spacing */
.base-input__field--with-left-icon {
    @apply pl-10;
}

.base-input__field--with-right-icon {
    @apply pr-10;
}

/* Icons */
.base-input__icon {
    @apply absolute top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400;
}

.base-input__icon--left {
    @apply left-3;
}

.base-input__icon--right {
    @apply right-3;
}

.base-input__icon--error {
    @apply text-red-500;
}

/* Clear button */
.base-input__clear {
    @apply absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors;
}

/* Loading spinner */
.base-input__spinner {
    @apply absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400;
}

/* Help text */
.base-input__help {
    @apply mt-1 text-sm text-gray-600 dark:text-gray-400;
}

/* Error message */
.base-input__error {
    @apply mt-1 text-sm text-red-600 dark:text-red-400;
}

/* Character count */
.base-input__count {
    @apply mt-1 text-xs text-gray-500 dark:text-gray-400 text-right;
}

.base-input__count--warning {
    @apply text-yellow-600 dark:text-yellow-400;
}

/* Textarea specific styles */
textarea.base-input__field {
    @apply resize-y min-h-[100px];
}

/* Focus styles for better accessibility */
.base-input__field:focus-visible {
    @apply ring-2 ring-blue-500 ring-offset-2;
}

.base-input__clear:focus-visible {
    @apply ring-2 ring-blue-500 ring-offset-2;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .base-input__field {
        @apply border-2 border-gray-900 dark:border-gray-100;
    }
    
    .base-input__field--error {
        @apply border-red-600;
    }
    
    .base-input__field:focus {
        @apply border-blue-600;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .base-input__field,
    .base-input__clear {
        @apply transition-none;
    }
    
    .base-input__spinner svg {
        animation: none;
    }
}

/* Print styles */
@media print {
    .base-input__field {
        @apply bg-transparent border-gray-400;
    }
    
    .base-input__clear,
    .base-input__spinner {
        @apply hidden;
    }
}
</style>