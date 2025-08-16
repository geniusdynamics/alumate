<template>
    <div 
        class="contextual-loader"
        :class="[sizeClasses, layoutClasses]"
        role="status"
        :aria-label="ariaLabel"
        :aria-live="ariaLive"
    >
        <!-- Icon -->
        <div v-if="showIcon" class="contextual-loader__icon">
            <component 
                :is="iconComponent" 
                :class="iconClasses"
                aria-hidden="true"
            />
        </div>
        
        <!-- Loading indicator -->
        <div class="contextual-loader__indicator">
            <LoadingSpinner 
                v-if="indicatorType === 'spinner'"
                :size="spinnerSize" 
                :color="spinnerColor"
            />
            <div 
                v-else-if="indicatorType === 'dots'"
                class="contextual-loader__dots"
            >
                <div 
                    v-for="dot in 3" 
                    :key="dot"
                    class="contextual-loader__dot"
                    :style="{ animationDelay: `${(dot - 1) * 0.2}s` }"
                />
            </div>
            <div 
                v-else-if="indicatorType === 'pulse'"
                class="contextual-loader__pulse"
            />
        </div>
        
        <!-- Content -->
        <div class="contextual-loader__content">
            <!-- Title -->
            <h3 v-if="title" class="contextual-loader__title">
                {{ title }}
            </h3>
            
            <!-- Message -->
            <p v-if="message" class="contextual-loader__message">
                {{ message }}
            </p>
            
            <!-- Progress -->
            <div v-if="showProgress" class="contextual-loader__progress">
                <div class="contextual-loader__progress-bar">
                    <div 
                        class="contextual-loader__progress-fill"
                        :style="{ width: `${progress}%` }"
                        :aria-valuenow="progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        role="progressbar"
                    />
                </div>
                <div class="contextual-loader__progress-text">
                    {{ progressText || `${progress}%` }}
                </div>
            </div>
            
            <!-- Steps -->
            <div v-if="steps && steps.length > 0" class="contextual-loader__steps">
                <div 
                    v-for="(step, index) in steps" 
                    :key="index"
                    class="contextual-loader__step"
                    :class="{
                        'contextual-loader__step--completed': index < currentStep,
                        'contextual-loader__step--current': index === currentStep,
                        'contextual-loader__step--pending': index > currentStep
                    }"
                >
                    <div class="contextual-loader__step-indicator">
                        <CheckIcon 
                            v-if="index < currentStep"
                            class="contextual-loader__step-check"
                        />
                        <LoadingSpinner 
                            v-else-if="index === currentStep"
                            size="xs"
                            color="current"
                        />
                        <span 
                            v-else
                            class="contextual-loader__step-number"
                        >
                            {{ index + 1 }}
                        </span>
                    </div>
                    <span class="contextual-loader__step-text">
                        {{ step }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Screen reader text -->
        <span class="sr-only">{{ screenReaderText }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
    CheckIcon,
    CloudArrowUpIcon,
    MagnifyingGlassIcon,
    UserGroupIcon,
    DocumentTextIcon,
    CogIcon,
    ServerIcon,
    WifiIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from './LoadingSpinner.vue'

interface Props {
    // Context
    context?: 'upload' | 'search' | 'sync' | 'processing' | 'connecting' | 'loading' | 'saving' | 'custom'
    
    // Indicator type
    indicatorType?: 'spinner' | 'dots' | 'pulse'
    
    // Size
    size?: 'sm' | 'md' | 'lg'
    
    // Layout
    layout?: 'vertical' | 'horizontal'
    centered?: boolean
    
    // Icon
    showIcon?: boolean
    customIcon?: any
    
    // Content
    title?: string
    message?: string
    
    // Progress
    showProgress?: boolean
    progress?: number
    progressText?: string
    
    // Steps
    steps?: string[]
    currentStep?: number
    
    // Styling
    spinnerSize?: 'xs' | 'sm' | 'md' | 'lg'
    spinnerColor?: 'primary' | 'secondary' | 'white' | 'gray' | 'current'
    
    // Accessibility
    ariaLabel?: string
    ariaLive?: 'polite' | 'assertive' | 'off'
}

const props = withDefaults(defineProps<Props>(), {
    context: 'loading',
    indicatorType: 'spinner',
    size: 'md',
    layout: 'vertical',
    centered: false,
    showIcon: true,
    showProgress: false,
    progress: 0,
    currentStep: 0,
    spinnerSize: 'md',
    spinnerColor: 'primary',
    ariaLive: 'polite'
})

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'contextual-loader--sm',
        md: 'contextual-loader--md',
        lg: 'contextual-loader--lg'
    }
    return sizes[props.size]
})

const layoutClasses = computed(() => {
    const classes = []
    
    if (props.layout === 'horizontal') {
        classes.push('contextual-loader--horizontal')
    } else {
        classes.push('contextual-loader--vertical')
    }
    
    if (props.centered) {
        classes.push('contextual-loader--centered')
    }
    
    return classes.join(' ')
})

const iconComponent = computed(() => {
    if (props.customIcon) return props.customIcon
    
    const contextIcons = {
        upload: CloudArrowUpIcon,
        search: MagnifyingGlassIcon,
        sync: WifiIcon,
        processing: CogIcon,
        connecting: ServerIcon,
        loading: DocumentTextIcon,
        saving: DocumentTextIcon,
        custom: DocumentTextIcon
    }
    
    return contextIcons[props.context]
})

const iconClasses = computed(() => {
    const sizes = {
        sm: 'h-6 w-6',
        md: 'h-8 w-8',
        lg: 'h-10 w-10'
    }
    
    return `${sizes[props.size]} text-gray-400 dark:text-gray-500`
})

const contextualContent = computed(() => {
    const contexts = {
        upload: {
            title: 'Uploading Files',
            message: 'Please wait while we upload your files...'
        },
        search: {
            title: 'Searching',
            message: 'Finding the best results for you...'
        },
        sync: {
            title: 'Syncing Data',
            message: 'Synchronizing your information...'
        },
        processing: {
            title: 'Processing',
            message: 'Processing your request...'
        },
        connecting: {
            title: 'Connecting',
            message: 'Establishing connection...'
        },
        loading: {
            title: 'Loading',
            message: 'Loading content...'
        },
        saving: {
            title: 'Saving',
            message: 'Saving your changes...'
        },
        custom: {
            title: 'Loading',
            message: 'Please wait...'
        }
    }
    
    return contexts[props.context]
})

const displayTitle = computed(() => {
    return props.title || contextualContent.value.title
})

const displayMessage = computed(() => {
    return props.message || contextualContent.value.message
})

const screenReaderText = computed(() => {
    let text = displayTitle.value
    
    if (displayMessage.value) {
        text += `. ${displayMessage.value}`
    }
    
    if (props.showProgress) {
        text += `. Progress: ${props.progress}%`
    }
    
    if (props.steps && props.steps.length > 0) {
        text += `. Step ${props.currentStep + 1} of ${props.steps.length}: ${props.steps[props.currentStep]}`
    }
    
    return text
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
.contextual-loader {
    @apply w-full;
}

/* Layout variants */
.contextual-loader--vertical {
    @apply flex flex-col items-center space-y-4;
}

.contextual-loader--horizontal {
    @apply flex items-center space-x-4;
}

.contextual-loader--centered {
    @apply justify-center min-h-64;
}

/* Size variants */
.contextual-loader--sm {
    @apply p-4;
}

.contextual-loader--md {
    @apply p-6;
}

.contextual-loader--lg {
    @apply p-8;
}

/* Icon */
.contextual-loader__icon {
    @apply flex-shrink-0;
}

/* Indicator */
.contextual-loader__indicator {
    @apply flex-shrink-0;
}

.contextual-loader__dots {
    @apply flex space-x-1;
}

.contextual-loader__dot {
    @apply w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full;
    animation: dots-bounce 1.4s ease-in-out infinite both;
}

.contextual-loader__pulse {
    @apply w-4 h-4 bg-blue-600 dark:bg-blue-400 rounded-full;
    animation: pulse-scale 2s ease-in-out infinite;
}

/* Content */
.contextual-loader__content {
    @apply text-center space-y-3;
}

.contextual-loader--horizontal .contextual-loader__content {
    @apply text-left;
}

.contextual-loader__title {
    @apply text-lg font-semibold text-gray-900 dark:text-gray-100;
}

.contextual-loader--sm .contextual-loader__title {
    @apply text-base;
}

.contextual-loader--lg .contextual-loader__title {
    @apply text-xl;
}

.contextual-loader__message {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

/* Progress */
.contextual-loader__progress {
    @apply w-full max-w-xs space-y-2;
}

.contextual-loader__progress-bar {
    @apply w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
}

.contextual-loader__progress-fill {
    @apply h-full bg-blue-600 dark:bg-blue-400 transition-all duration-300 ease-out;
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.2) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.2) 50%,
        rgba(255, 255, 255, 0.2) 75%,
        transparent 75%,
        transparent
    );
    background-size: 1rem 1rem;
    animation: progress-stripes 1s linear infinite;
}

.contextual-loader__progress-text {
    @apply text-xs text-gray-500 dark:text-gray-400 text-center;
}

/* Steps */
.contextual-loader__steps {
    @apply space-y-3 text-left;
}

.contextual-loader__step {
    @apply flex items-center space-x-3;
}

.contextual-loader__step-indicator {
    @apply flex items-center justify-center w-6 h-6 rounded-full border-2 flex-shrink-0;
}

.contextual-loader__step--completed .contextual-loader__step-indicator {
    @apply bg-green-100 border-green-500 text-green-600 dark:bg-green-900 dark:border-green-400 dark:text-green-400;
}

.contextual-loader__step--current .contextual-loader__step-indicator {
    @apply bg-blue-100 border-blue-500 text-blue-600 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-400;
}

.contextual-loader__step--pending .contextual-loader__step-indicator {
    @apply bg-gray-100 border-gray-300 text-gray-400 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500;
}

.contextual-loader__step-check {
    @apply w-4 h-4;
}

.contextual-loader__step-number {
    @apply text-xs font-medium;
}

.contextual-loader__step-text {
    @apply text-sm;
}

.contextual-loader__step--completed .contextual-loader__step-text {
    @apply text-green-700 dark:text-green-300;
}

.contextual-loader__step--current .contextual-loader__step-text {
    @apply text-blue-700 dark:text-blue-300 font-medium;
}

.contextual-loader__step--pending .contextual-loader__step-text {
    @apply text-gray-500 dark:text-gray-400;
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

/* Animations */
@keyframes dots-bounce {
    0%, 80%, 100% {
        transform: scale(0);
    }
    40% {
        transform: scale(1);
    }
}

@keyframes pulse-scale {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.7;
    }
}

@keyframes progress-stripes {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 1rem 0;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .contextual-loader__dot,
    .contextual-loader__pulse {
        animation: none;
    }
    
    .contextual-loader__progress-fill {
        animation: none;
    }
    
    .contextual-loader__dot {
        opacity: 0.6;
    }
    
    .contextual-loader__dot:nth-child(1) { opacity: 1; }
    .contextual-loader__dot:nth-child(2) { opacity: 0.7; }
    .contextual-loader__dot:nth-child(3) { opacity: 0.4; }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .contextual-loader__progress-bar {
        @apply border border-gray-600;
    }
    
    .contextual-loader__progress-fill {
        @apply border-r border-blue-800;
    }
    
    .contextual-loader__dot,
    .contextual-loader__pulse {
        @apply border border-blue-800;
    }
}
</style>