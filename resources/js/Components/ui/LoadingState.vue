<template>
    <div 
        class="loading-state"
        :class="[layoutClasses, sizeClasses]"
        role="status"
        :aria-label="ariaLabel"
        :aria-live="ariaLive"
    >
        <!-- Skeleton Loading -->
        <div v-if="type === 'skeleton'" class="loading-state__skeleton">
            <!-- Card skeleton -->
            <div v-if="variant === 'card'" class="space-y-4">
                <SkeletonLoader shape="card" :size="size" />
                <div class="space-y-2">
                    <SkeletonLoader shape="text" :size="size" />
                    <SkeletonLoader shape="text" :size="size" width="75%" />
                </div>
            </div>
            
            <!-- List skeleton -->
            <div v-else-if="variant === 'list'" class="space-y-3">
                <div 
                    v-for="item in skeletonCount" 
                    :key="item"
                    class="flex items-center space-x-3"
                >
                    <SkeletonLoader shape="avatar" :size="size" />
                    <div class="flex-1 space-y-2">
                        <SkeletonLoader shape="text" :size="size" />
                        <SkeletonLoader shape="text" :size="size" width="60%" />
                    </div>
                </div>
            </div>
            
            <!-- Table skeleton -->
            <div v-else-if="variant === 'table'" class="space-y-2">
                <div 
                    v-for="row in skeletonCount" 
                    :key="row"
                    class="grid gap-4"
                    :style="{ gridTemplateColumns: `repeat(${tableColumns}, 1fr)` }"
                >
                    <SkeletonLoader 
                        v-for="col in tableColumns" 
                        :key="col"
                        shape="text" 
                        :size="size" 
                    />
                </div>
            </div>
            
            <!-- Profile skeleton -->
            <div v-else-if="variant === 'profile'" class="space-y-4">
                <div class="flex items-center space-x-4">
                    <SkeletonLoader shape="avatar" size="xl" />
                    <div class="flex-1 space-y-2">
                        <SkeletonLoader shape="text" size="lg" width="40%" />
                        <SkeletonLoader shape="text" :size="size" width="60%" />
                    </div>
                </div>
                <div class="space-y-2">
                    <SkeletonLoader shape="text" :size="size" />
                    <SkeletonLoader shape="text" :size="size" />
                    <SkeletonLoader shape="text" :size="size" width="80%" />
                </div>
            </div>
            
            <!-- Custom skeleton -->
            <div v-else class="space-y-3">
                <SkeletonLoader 
                    v-for="item in skeletonCount" 
                    :key="item"
                    :shape="skeletonShape" 
                    :size="size"
                    :width="skeletonWidth"
                    :height="skeletonHeight"
                />
            </div>
        </div>
        
        <!-- Spinner Loading -->
        <div v-else-if="type === 'spinner'" class="loading-state__spinner">
            <LoadingSpinner 
                :size="spinnerSize" 
                :color="spinnerColor"
                :text="text"
                :centered="centered"
            />
        </div>
        
        <!-- Overlay Loading -->
        <div v-else-if="type === 'overlay'" class="loading-state__overlay">
            <div class="loading-state__overlay-backdrop" />
            <div class="loading-state__overlay-content">
                <LoadingSpinner 
                    :size="spinnerSize" 
                    :color="overlaySpinnerColor"
                    :text="text"
                    centered
                />
            </div>
        </div>
        
        <!-- Progress Loading -->
        <div v-else-if="type === 'progress'" class="loading-state__progress">
            <div class="loading-state__progress-bar">
                <div 
                    class="loading-state__progress-fill"
                    :style="{ width: `${progress}%` }"
                    :aria-valuenow="progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    role="progressbar"
                />
            </div>
            <div v-if="text" class="loading-state__progress-text">
                {{ text }}
            </div>
        </div>
        
        <!-- Dots Loading -->
        <div v-else-if="type === 'dots'" class="loading-state__dots">
            <div class="loading-state__dots-container">
                <div 
                    v-for="dot in 3" 
                    :key="dot"
                    class="loading-state__dot"
                    :style="{ animationDelay: `${(dot - 1) * 0.2}s` }"
                />
            </div>
            <div v-if="text" class="loading-state__dots-text">
                {{ text }}
            </div>
        </div>
        
        <!-- Screen reader text -->
        <span class="sr-only">{{ screenReaderText }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'
import SkeletonLoader from './SkeletonLoader.vue'

interface Props {
    // Loading type
    type?: 'skeleton' | 'spinner' | 'overlay' | 'progress' | 'dots'
    
    // Skeleton variants
    variant?: 'card' | 'list' | 'table' | 'profile' | 'custom'
    skeletonCount?: number
    skeletonShape?: 'rectangle' | 'circle' | 'text' | 'avatar' | 'button'
    skeletonWidth?: string | number
    skeletonHeight?: string | number
    tableColumns?: number
    
    // Size
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    
    // Spinner options
    spinnerSize?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    spinnerColor?: 'primary' | 'secondary' | 'white' | 'gray' | 'current'
    overlaySpinnerColor?: 'primary' | 'secondary' | 'white' | 'gray' | 'current'
    
    // Progress options
    progress?: number
    
    // Layout
    centered?: boolean
    fullHeight?: boolean
    
    // Content
    text?: string
    
    // Accessibility
    ariaLabel?: string
    ariaLive?: 'polite' | 'assertive' | 'off'
}

const props = withDefaults(defineProps<Props>(), {
    type: 'spinner',
    variant: 'custom',
    skeletonCount: 3,
    skeletonShape: 'rectangle',
    tableColumns: 3,
    size: 'md',
    spinnerSize: 'md',
    spinnerColor: 'primary',
    overlaySpinnerColor: 'white',
    progress: 0,
    centered: false,
    fullHeight: false,
    ariaLive: 'polite'
})

const layoutClasses = computed(() => {
    const classes = []
    
    if (props.centered) {
        classes.push('loading-state--centered')
    }
    
    if (props.fullHeight) {
        classes.push('loading-state--full-height')
    }
    
    return classes.join(' ')
})

const sizeClasses = computed(() => {
    const sizes = {
        xs: 'loading-state--xs',
        sm: 'loading-state--sm',
        md: 'loading-state--md',
        lg: 'loading-state--lg',
        xl: 'loading-state--xl'
    }
    return sizes[props.size]
})

const screenReaderText = computed(() => {
    if (props.text) return props.text
    
    const typeText = {
        skeleton: 'Loading content structure',
        spinner: 'Loading',
        overlay: 'Loading, please wait',
        progress: `Loading progress: ${props.progress}%`,
        dots: 'Loading'
    }
    
    return typeText[props.type] || 'Loading, please wait'
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
/* Base loading state */
.loading-state {
    @apply w-full;
}

.loading-state--centered {
    @apply flex items-center justify-center;
}

.loading-state--full-height {
    @apply min-h-screen;
}

/* Size variants */
.loading-state--xs {
    @apply p-2;
}

.loading-state--sm {
    @apply p-3;
}

.loading-state--md {
    @apply p-4;
}

.loading-state--lg {
    @apply p-6;
}

.loading-state--xl {
    @apply p-8;
}

/* Skeleton loading */
.loading-state__skeleton {
    @apply w-full;
}

/* Spinner loading */
.loading-state__spinner {
    @apply w-full;
}

/* Overlay loading */
.loading-state__overlay {
    @apply fixed inset-0 z-50;
}

.loading-state__overlay-backdrop {
    @apply absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm;
}

.loading-state__overlay-content {
    @apply absolute inset-0 flex items-center justify-center;
}

/* Progress loading */
.loading-state__progress {
    @apply w-full space-y-2;
}

.loading-state__progress-bar {
    @apply w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden;
}

.loading-state__progress-fill {
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

.loading-state__progress-text {
    @apply text-sm text-gray-600 dark:text-gray-400 text-center;
}

/* Dots loading */
.loading-state__dots {
    @apply flex flex-col items-center space-y-3;
}

.loading-state__dots-container {
    @apply flex space-x-1;
}

.loading-state__dot {
    @apply w-2 h-2 bg-blue-600 dark:bg-blue-400 rounded-full;
    animation: dots-bounce 1.4s ease-in-out infinite both;
}

.loading-state__dots-text {
    @apply text-sm text-gray-600 dark:text-gray-400;
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
@keyframes progress-stripes {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 1rem 0;
    }
}

@keyframes dots-bounce {
    0%, 80%, 100% {
        transform: scale(0);
    }
    40% {
        transform: scale(1);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .loading-state__progress-fill {
        animation: none;
    }
    
    .loading-state__dot {
        animation: none;
        opacity: 0.6;
    }
    
    .loading-state__dot:nth-child(1) { opacity: 1; }
    .loading-state__dot:nth-child(2) { opacity: 0.7; }
    .loading-state__dot:nth-child(3) { opacity: 0.4; }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .loading-state__progress-bar {
        @apply border border-gray-600;
    }
    
    .loading-state__progress-fill {
        @apply border-r border-blue-800;
    }
    
    .loading-state__dot {
        @apply border border-blue-800;
    }
}

/* Print styles */
@media print {
    .loading-state__overlay {
        @apply hidden;
    }
    
    .loading-state__progress-fill {
        animation: none;
    }
    
    .loading-state__dot {
        animation: none;
    }
}
</style>