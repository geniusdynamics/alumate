<template>
    <div 
        class="loading-spinner"
        :class="[sizeClasses, colorClasses, centerClasses]"
        role="status"
        :aria-label="ariaLabel"
        :aria-live="ariaLive"
    >
        <!-- Spinner SVG -->
        <svg 
            class="animate-spin" 
            :class="spinnerSizeClasses"
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
                d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
        
        <!-- Loading text -->
        <span 
            v-if="text" 
            class="loading-text"
            :class="textSizeClasses"
        >
            {{ text }}
        </span>
        
        <!-- Screen reader text -->
        <span class="sr-only">
            {{ screenReaderText }}
        </span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    // Size variants
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    
    // Color variants
    color?: 'primary' | 'secondary' | 'white' | 'gray' | 'current'
    
    // Layout
    centered?: boolean
    inline?: boolean
    
    // Content
    text?: string
    
    // Accessibility
    ariaLabel?: string
    ariaLive?: 'polite' | 'assertive' | 'off'
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    color: 'primary',
    centered: false,
    inline: false,
    ariaLive: 'polite'
})

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'loading-spinner--xs',
        sm: 'loading-spinner--sm',
        md: 'loading-spinner--md',
        lg: 'loading-spinner--lg',
        xl: 'loading-spinner--xl'
    }
    return sizes[props.size]
})

const colorClasses = computed(() => {
    const colors = {
        primary: 'text-blue-600 dark:text-blue-400',
        secondary: 'text-gray-600 dark:text-gray-400',
        white: 'text-white',
        gray: 'text-gray-500',
        current: 'text-current'
    }
    return colors[props.color]
})

const centerClasses = computed(() => {
    if (props.centered && !props.inline) {
        return 'flex items-center justify-center'
    }
    if (props.inline) {
        return 'inline-flex items-center'
    }
    return 'flex items-center'
})

const spinnerSizeClasses = computed(() => {
    const sizes = {
        xs: 'h-3 w-3',
        sm: 'h-4 w-4',
        md: 'h-5 w-5',
        lg: 'h-6 w-6',
        xl: 'h-8 w-8'
    }
    return sizes[props.size]
})

const textSizeClasses = computed(() => {
    const sizes = {
        xs: 'text-xs',
        sm: 'text-sm',
        md: 'text-sm',
        lg: 'text-base',
        xl: 'text-lg'
    }
    return sizes[props.size]
})

const screenReaderText = computed(() => {
    return props.text || 'Loading content, please wait'
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
.loading-spinner {
    /* Base styles handled by computed classes */
}

.loading-spinner--xs {
    gap: 0.375rem; /* 6px */
}

.loading-spinner--sm {
    gap: 0.5rem; /* 8px */
}

.loading-spinner--md {
    gap: 0.5rem; /* 8px */
}

.loading-spinner--lg {
    gap: 0.75rem; /* 12px */
}

.loading-spinner--xl {
    gap: 1rem; /* 16px */
}

.loading-text {
    font-weight: 500;
}

/* Screen reader only class */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .animate-spin {
        animation: none;
    }
    
    /* Show a static loading indicator instead */
    .loading-spinner svg {
        opacity: 0.6;
    }
    
    .loading-spinner::after {
        content: '';
        display: inline-block;
        width: 0.5em;
        height: 0.5em;
        border-radius: 50%;
        background-color: currentColor;
        animation: pulse 1.5s ease-in-out infinite;
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 0.4;
    }
    50% {
        opacity: 1;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .loading-spinner {
        border: 1px solid currentColor;
        border-radius: 0.25rem;
        padding: 0.25rem;
    }
}

/* Print styles */
@media print {
    .loading-spinner {
        display: none;
    }
}
</style>