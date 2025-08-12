<template>
    <component
        :is="tag"
        :type="tag === 'button' ? type : undefined"
        :href="tag === 'a' ? href : undefined"
        :to="tag === 'router-link' ? to : undefined"
        :disabled="disabled"
        :aria-label="ariaLabel"
        :aria-describedby="ariaDescribedby"
        :aria-expanded="ariaExpanded"
        :aria-controls="ariaControls"
        :aria-pressed="ariaPressed"
        class="base-button"
        :class="[
            sizeClasses,
            variantClasses,
            {
                'base-button--disabled': disabled,
                'base-button--loading': loading,
                'base-button--icon-only': iconOnly,
                'base-button--full-width': fullWidth
            }
        ]"
        @click="handleClick"
        @keydown="handleKeydown"
    >
        <!-- Loading spinner -->
        <div v-if="loading" class="base-button__spinner" aria-hidden="true">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Left icon -->
        <component
            v-if="leftIcon && !loading"
            :is="leftIcon"
            class="base-button__icon base-button__icon--left"
            :class="iconClasses"
            aria-hidden="true"
        />

        <!-- Button content -->
        <span v-if="!iconOnly" class="base-button__content">
            <slot />
        </span>

        <!-- Right icon -->
        <component
            v-if="rightIcon && !loading"
            :is="rightIcon"
            class="base-button__icon base-button__icon--right"
            :class="iconClasses"
            aria-hidden="true"
        />

        <!-- Badge/notification indicator -->
        <span
            v-if="badge"
            class="base-button__badge"
            :aria-label="`${badge} notifications`"
        >
            {{ badge }}
        </span>
    </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    // Element type
    tag?: 'button' | 'a' | 'router-link'
    type?: 'button' | 'submit' | 'reset'
    href?: string
    to?: string | object
    
    // Appearance
    variant?: 'primary' | 'secondary' | 'tertiary' | 'danger' | 'success' | 'warning' | 'ghost'
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    fullWidth?: boolean
    
    // State
    disabled?: boolean
    loading?: boolean
    
    // Icons
    leftIcon?: any
    rightIcon?: any
    iconOnly?: boolean
    
    // Badge
    badge?: string | number
    
    // Accessibility
    ariaLabel?: string
    ariaDescribedby?: string
    ariaExpanded?: boolean | string
    ariaControls?: string
    ariaPressed?: boolean | string
}

const props = withDefaults(defineProps<Props>(), {
    tag: 'button',
    type: 'button',
    variant: 'primary',
    size: 'md',
    fullWidth: false,
    disabled: false,
    loading: false,
    iconOnly: false
})

const emit = defineEmits<{
    click: [event: Event]
}>()

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'base-button--xs',
        sm: 'base-button--sm',
        md: 'base-button--md',
        lg: 'base-button--lg',
        xl: 'base-button--xl'
    }
    return sizes[props.size]
})

const variantClasses = computed(() => {
    const variants = {
        primary: 'base-button--primary',
        secondary: 'base-button--secondary',
        tertiary: 'base-button--tertiary',
        danger: 'base-button--danger',
        success: 'base-button--success',
        warning: 'base-button--warning',
        ghost: 'base-button--ghost'
    }
    return variants[props.variant]
})

const iconClasses = computed(() => {
    const sizes = {
        xs: 'h-3 w-3',
        sm: 'h-4 w-4',
        md: 'h-4 w-4',
        lg: 'h-5 w-5',
        xl: 'h-6 w-6'
    }
    return sizes[props.size]
})

const handleClick = (event: Event) => {
    if (props.disabled || props.loading) {
        event.preventDefault()
        return
    }
    emit('click', event)
}

const handleKeydown = (event: KeyboardEvent) => {
    // Handle Enter and Space for button-like behavior
    if (props.tag !== 'button' && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault()
        handleClick(event)
    }
}
</script>

<style scoped>
/* Base button styles */
.base-button {
    @apply relative inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed;
    
    /* Ensure minimum touch target size */
    min-height: 44px;
    min-width: 44px;
}

/* Size variants */
.base-button--xs {
    @apply px-2.5 py-1.5 text-xs rounded-md;
    min-height: 32px;
    min-width: 32px;
}

.base-button--sm {
    @apply px-3 py-2 text-sm rounded-md;
    min-height: 36px;
    min-width: 36px;
}

.base-button--md {
    @apply px-4 py-2.5 text-sm rounded-lg;
}

.base-button--lg {
    @apply px-6 py-3 text-base rounded-lg;
    min-height: 48px;
}

.base-button--xl {
    @apply px-8 py-4 text-lg rounded-xl;
    min-height: 56px;
}

/* Color variants */
.base-button--primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 active:bg-blue-800;
}

.base-button--secondary {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500 active:bg-gray-400;
}

.base-button--tertiary {
    @apply bg-transparent text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-500 active:bg-gray-100;
}

.base-button--danger {
    @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 active:bg-red-800;
}

.base-button--success {
    @apply bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 active:bg-green-800;
}

.base-button--warning {
    @apply bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500 active:bg-yellow-700;
}

.base-button--ghost {
    @apply bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-500 active:bg-gray-200;
}

/* Dark theme variants */
.dark .base-button--secondary {
    @apply bg-gray-700 text-gray-100 hover:bg-gray-600 active:bg-gray-500;
}

.dark .base-button--tertiary {
    @apply text-gray-300 border-gray-600 hover:bg-gray-800 active:bg-gray-700;
}

.dark .base-button--ghost {
    @apply text-gray-300 hover:bg-gray-800 active:bg-gray-700;
}

/* State modifiers */
.base-button--disabled {
    @apply opacity-50 cursor-not-allowed;
}

.base-button--loading {
    @apply cursor-wait;
}

.base-button--full-width {
    @apply w-full;
}

.base-button--icon-only {
    @apply p-2;
}

.base-button--icon-only.base-button--xs {
    @apply p-1.5;
}

.base-button--icon-only.base-button--sm {
    @apply p-2;
}

.base-button--icon-only.base-button--lg {
    @apply p-3;
}

.base-button--icon-only.base-button--xl {
    @apply p-4;
}

/* Icon positioning */
.base-button__icon--left {
    @apply mr-2;
}

.base-button__icon--right {
    @apply ml-2;
}

.base-button--icon-only .base-button__icon--left,
.base-button--icon-only .base-button__icon--right {
    @apply m-0;
}

/* Loading spinner */
.base-button__spinner {
    @apply mr-2;
}

.base-button--icon-only .base-button__spinner {
    @apply m-0;
}

/* Badge */
.base-button__badge {
    @apply absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 min-w-[1.25rem] h-5 flex items-center justify-center;
}

/* Focus styles for better accessibility */
.base-button:focus-visible {
    @apply ring-2 ring-offset-2;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .base-button {
        @apply border-2 border-current;
    }
    
    .base-button--primary {
        @apply border-blue-600;
    }
    
    .base-button--secondary {
        @apply border-gray-600;
    }
    
    .base-button--danger {
        @apply border-red-600;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .base-button {
        @apply transition-none;
    }
    
    .base-button__spinner {
        animation: none;
    }
}

/* Print styles */
@media print {
    .base-button {
        @apply bg-transparent text-black border border-black;
    }
}
</style>