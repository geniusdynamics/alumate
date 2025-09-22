<!-- ABOUTME: Touch-optimized checkbox component for mobile-friendly form interactions -->
<!-- ABOUTME: Provides enhanced touch targets, visual feedback, and accessibility features -->
<template>
    <div :class="checkboxWrapperClasses" @touchstart="handleTouchStart" @touchend="handleTouchEnd">
        <div class="checkbox-container">
            <input
                :id="checkboxId"
                type="checkbox"
                :name="name"
                :checked="modelValue"
                :required="required"
                :disabled="disabled"
                :class="checkboxClasses"
                :aria-describedby="helpTextId"
                :aria-invalid="hasErrors"
                @change="handleChange"
                @focus="handleFocus"
                @blur="handleBlur"
                ref="checkboxRef"
            />

            <!-- Custom Checkbox Visual -->
            <div class="checkbox-visual">
                <svg v-if="modelValue" class="checkbox-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <!-- Label -->
        <label v-if="label" :for="checkboxId" :class="labelClasses">
            {{ label }}
            <span v-if="required" class="required-indicator">*</span>
        </label>

        <!-- Help Text -->
        <div v-if="helpText" :id="helpTextId" class="help-text">
            {{ helpText }}
        </div>

        <!-- Error Messages -->
        <div v-if="hasErrors" class="error-messages" role="alert">
            <div v-for="error in errors" :key="error" class="error-message">
                {{ error }}
            </div>
        </div>

        <!-- Touch Feedback -->
        <div v-if="showTouchFeedback" class="touch-feedback" :class="touchFeedbackClass"></div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';

interface Props {
    modelValue?: boolean;
    label?: string;
    name?: string;
    required?: boolean;
    disabled?: boolean;
    helpText?: string;
    errors?: string[];
    size?: 'sm' | 'md' | 'lg';
    variant?: 'default' | 'switch';
    focusOnMount?: boolean;
}

interface Emits {
    'update:modelValue': [value: boolean];
    change: [value: boolean, event: Event];
    focus: [event: FocusEvent];
    blur: [event: FocusEvent];
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    size: 'md',
    variant: 'default',
    disabled: false,
    required: false,
    focusOnMount: false,
});

const emit = defineEmits<Emits>();

// Refs
const checkboxRef = ref<HTMLInputElement>();
const showTouchFeedback = ref(false);
const touchFeedbackClass = ref('');
const isFocused = ref(false);
const isTouched = ref(false);

// Computed
const checkboxId = computed(() => {
    return props.name ? `checkbox-${props.name}` : `checkbox-${Math.random().toString(36).substr(2, 9)}`;
});

const helpTextId = computed(() => `${checkboxId.value}-help`);
const hasErrors = computed(() => props.errors && props.errors.length > 0);

const checkboxWrapperClasses = computed(() => [
    'touch-checkbox-wrapper',
    `checkbox-${props.size}`,
    `checkbox-${props.variant}`,
    {
        'checkbox-focused': isFocused.value,
        'checkbox-touched': isTouched.value,
        'checkbox-disabled': props.disabled,
        'checkbox-required': props.required,
        'checkbox-error': hasErrors.value,
        'checkbox-checked': props.modelValue,
    },
]);

const checkboxClasses = computed(() => [
    'touch-checkbox-input',
    {
        'checkbox-input-error': hasErrors.value,
    },
]);

const labelClasses = computed(() => [
    'checkbox-label',
    {
        'label-focused': isFocused.value,
        'label-error': hasErrors.value,
        'label-disabled': props.disabled,
    },
]);

// Methods
const handleChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const newValue = target.checked;

    emit('update:modelValue', newValue);
    emit('change', newValue, event);
};

const handleFocus = (event: FocusEvent) => {
    isFocused.value = true;
    emit('focus', event);
};

const handleBlur = (event: FocusEvent) => {
    isFocused.value = false;
    isTouched.value = true;
    emit('blur', event);
};

const handleTouchStart = () => {
    if (!props.disabled) {
        showTouchFeedback.value = true;
        touchFeedbackClass.value = 'touch-active';
    }
};

const handleTouchEnd = () => {
    setTimeout(() => {
        showTouchFeedback.value = false;
        touchFeedbackClass.value = '';
    }, 150);
};

// Lifecycle
onMounted(() => {
    if (props.focusOnMount && checkboxRef.value) {
        nextTick(() => {
            checkboxRef.value?.focus();
        });
    }
});
</script>

<style scoped>
.touch-checkbox-wrapper {
    @apply relative flex items-start gap-3 rounded-lg p-2;
    @apply transition-all duration-200 ease-in-out;

    /* Touch-friendly minimum dimensions */
    min-height: 48px;

    /* Prevent text selection and zoom on double-tap */
    -webkit-user-select: none;
    -webkit-touch-callout: none;
    -webkit-tap-highlight-color: transparent;
}

.checkbox-container {
    @apply relative flex-shrink-0;
    margin-top: 2px; /* Align with first line of label */
}

.touch-checkbox-input {
    @apply sr-only; /* Hide native checkbox but keep it accessible */
}

.checkbox-visual {
    @apply h-6 w-6 rounded border-2 border-gray-300 dark:border-gray-600;
    @apply bg-white dark:bg-gray-800;
    @apply flex items-center justify-center;
    @apply transition-all duration-200 ease-in-out;
    @apply cursor-pointer;
}

.checkbox-check {
    @apply h-4 w-4 text-white;
    @apply transition-all duration-150 ease-in-out;
}

.checkbox-label {
    @apply cursor-pointer text-sm text-gray-700 dark:text-gray-300;
    @apply flex-grow select-none;
    @apply transition-colors duration-200 ease-in-out;
}

.required-indicator {
    @apply ml-1 text-red-500;
}

.help-text {
    @apply mt-1 text-xs text-gray-600 dark:text-gray-400;
    @apply ml-9; /* Align with label */
}

.error-messages {
    @apply mt-1 space-y-1;
    @apply ml-9; /* Align with label */
}

.error-message {
    @apply text-xs text-red-600 dark:text-red-400;
    @apply flex items-center gap-1;
}

.error-message::before {
    content: '⚠';
    @apply text-red-500;
}

.touch-feedback {
    @apply pointer-events-none absolute inset-0 rounded-lg;
    @apply transition-all duration-150 ease-out;
}

.touch-active {
    @apply bg-blue-100 opacity-20 dark:bg-blue-900;
}

/* Size Variants */
.checkbox-sm {
    min-height: 40px;
}

.checkbox-sm .checkbox-visual {
    @apply h-5 w-5;
}

.checkbox-sm .checkbox-check {
    @apply h-3 w-3;
}

.checkbox-sm .checkbox-label {
    @apply text-xs;
}

.checkbox-lg {
    min-height: 56px;
}

.checkbox-lg .checkbox-visual {
    @apply h-7 w-7;
}

.checkbox-lg .checkbox-check {
    @apply h-5 w-5;
}

.checkbox-lg .checkbox-label {
    @apply text-base;
}

/* Checked State */
.checkbox-checked .checkbox-visual {
    @apply border-blue-600 bg-blue-600;
}

.dark .checkbox-checked .checkbox-visual {
    @apply border-blue-500 bg-blue-500;
}

/* Focus State */
.checkbox-focused .checkbox-visual {
    @apply ring-2 ring-blue-500 ring-offset-2;
    @apply ring-offset-white dark:ring-offset-gray-900;
}

.label-focused {
    @apply text-blue-600 dark:text-blue-400;
}

/* Error State */
.checkbox-error .checkbox-visual {
    @apply border-red-500;
}

.checkbox-error.checkbox-checked .checkbox-visual {
    @apply border-red-600 bg-red-600;
}

.dark .checkbox-error.checkbox-checked .checkbox-visual {
    @apply border-red-500 bg-red-500;
}

.label-error {
    @apply text-red-600 dark:text-red-400;
}

/* Disabled State */
.checkbox-disabled {
    @apply cursor-not-allowed opacity-50;
}

.checkbox-disabled .checkbox-visual,
.checkbox-disabled .checkbox-label {
    @apply cursor-not-allowed;
}

.label-disabled {
    @apply text-gray-400 dark:text-gray-600;
}

/* Switch Variant */
.checkbox-switch .checkbox-visual {
    @apply h-6 w-12 rounded-full;
    @apply bg-gray-200 dark:bg-gray-700;
    @apply border-0;
    @apply relative;
}

.checkbox-switch .checkbox-check {
    @apply h-5 w-5 rounded-full bg-white;
    @apply absolute left-0.5 top-0.5;
    @apply transition-transform duration-200 ease-in-out;
    @apply shadow-sm;
}

.checkbox-switch.checkbox-checked .checkbox-visual {
    @apply bg-blue-600;
}

.checkbox-switch.checkbox-checked .checkbox-check {
    @apply translate-x-6 transform;
}

.dark .checkbox-switch.checkbox-checked .checkbox-visual {
    @apply bg-blue-500;
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    .touch-checkbox-wrapper {
        min-height: 52px; /* Larger touch targets on mobile */
        @apply p-3;
    }

    .checkbox-visual {
        @apply h-7 w-7; /* Larger checkbox on touch devices */
    }

    .checkbox-check {
        @apply h-5 w-5;
    }
}

/* Hover Effects (Desktop Only) */
@media (hover: hover) and (pointer: fine) {
    .touch-checkbox-wrapper:hover .checkbox-visual {
        @apply border-blue-400 dark:border-blue-500;
    }

    .checkbox-checked.touch-checkbox-wrapper:hover .checkbox-visual {
        @apply border-blue-700 bg-blue-700;
    }

    .dark .checkbox-checked.touch-checkbox-wrapper:hover .checkbox-visual {
        @apply border-blue-400 bg-blue-400;
    }
}

/* Focus Visible for Keyboard Navigation */
.touch-checkbox-input:focus-visible + .checkbox-visual {
    @apply outline-2 outline-offset-2 outline-blue-500;
}

/* Reduce Motion for Accessibility */
@media (prefers-reduced-motion: reduce) {
    .checkbox-visual,
    .checkbox-check,
    .checkbox-label,
    .touch-feedback {
        transition: none;
    }
}
</style>
