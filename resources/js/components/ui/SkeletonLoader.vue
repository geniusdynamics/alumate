<template>
    <div 
        class="skeleton-loader"
        :class="[shapeClasses, sizeClasses, animationClasses]"
        :style="customStyles"
        role="status"
        :aria-label="ariaLabel"
        :aria-live="ariaLive"
    >
        <!-- Screen reader text -->
        <span class="sr-only">{{ screenReaderText }}</span>
        
        <!-- Avatar skeleton with optional icon -->
        <div v-if="shape === 'avatar'" class="skeleton-avatar-content">
            <UserIcon v-if="showIcon" class="skeleton-avatar-icon" aria-hidden="true" />
        </div>
        
        <!-- Text skeleton with multiple lines -->
        <div v-if="shape === 'text' && lines > 1" class="skeleton-text-lines">
            <div 
                v-for="line in lines" 
                :key="line"
                class="skeleton-text-line"
                :class="{ 'skeleton-text-line--short': line === lines && lastLineShort }"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { UserIcon } from '@heroicons/vue/24/outline'

interface Props {
    // Shape variants
    shape?: 'rectangle' | 'circle' | 'text' | 'avatar' | 'button' | 'card' | 'image'
    
    // Size variants
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'custom'
    
    // Dimensions (for custom size)
    width?: string | number
    height?: string | number
    
    // Text-specific props
    lines?: number
    lastLineShort?: boolean
    
    // Animation
    animated?: boolean
    
    // Appearance
    showIcon?: boolean
    rounded?: boolean
    
    // Accessibility
    ariaLabel?: string
    ariaLive?: 'polite' | 'assertive' | 'off'
}

const props = withDefaults(defineProps<Props>(), {
    shape: 'rectangle',
    size: 'md',
    lines: 1,
    lastLineShort: true,
    animated: true,
    showIcon: false,
    rounded: false,
    ariaLive: 'polite'
})

const shapeClasses = computed(() => {
    const shapes = {
        rectangle: 'skeleton-rectangle',
        circle: 'skeleton-circle',
        text: 'skeleton-text',
        avatar: 'skeleton-avatar',
        button: 'skeleton-button',
        card: 'skeleton-card',
        image: 'skeleton-image'
    }
    return shapes[props.shape]
})

const sizeClasses = computed(() => {
    if (props.size === 'custom') return ''
    
    const sizes = {
        xs: 'skeleton-xs',
        sm: 'skeleton-sm',
        md: 'skeleton-md',
        lg: 'skeleton-lg',
        xl: 'skeleton-xl'
    }
    return sizes[props.size]
})

const animationClasses = computed(() => {
    return props.animated ? 'skeleton-animated' : 'skeleton-static'
})

const customStyles = computed(() => {
    const styles: Record<string, string> = {}
    
    if (props.size === 'custom') {
        if (props.width) {
            styles.width = typeof props.width === 'number' ? `${props.width}px` : props.width
        }
        if (props.height) {
            styles.height = typeof props.height === 'number' ? `${props.height}px` : props.height
        }
    }
    
    if (props.rounded) {
        styles.borderRadius = '9999px'
    }
    
    return styles
})

const screenReaderText = computed(() => {
    const shapeText = {
        rectangle: 'content',
        circle: 'circular content',
        text: 'text content',
        avatar: 'user avatar',
        button: 'button',
        card: 'card content',
        image: 'image'
    }
    
    return `Loading ${shapeText[props.shape]}, please wait`
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
/* Base skeleton styles */
.skeleton-loader {
    @apply bg-gray-200 dark:bg-gray-700;
    position: relative;
    overflow: hidden;
}

/* Animation styles */
.skeleton-animated {
    animation: skeleton-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.skeleton-animated::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.4),
        transparent
    );
    animation: skeleton-shimmer 2s infinite;
}

.dark .skeleton-animated::before {
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.1),
        transparent
    );
}

.skeleton-static {
    opacity: 0.6;
}

/* Shape variants */
.skeleton-rectangle {
    @apply rounded;
}

.skeleton-circle {
    @apply rounded-full;
    aspect-ratio: 1;
}

.skeleton-text {
    @apply rounded h-4;
}

.skeleton-avatar {
    @apply rounded-full flex items-center justify-center;
    aspect-ratio: 1;
}

.skeleton-button {
    @apply rounded-lg;
}

.skeleton-card {
    @apply rounded-lg;
}

.skeleton-image {
    @apply rounded;
    aspect-ratio: 16/9;
}

/* Size variants */
.skeleton-xs {
    @apply h-3;
}

.skeleton-xs.skeleton-avatar,
.skeleton-xs.skeleton-circle {
    @apply h-6 w-6;
}

.skeleton-xs.skeleton-button {
    @apply h-8 w-16;
}

.skeleton-sm {
    @apply h-4;
}

.skeleton-sm.skeleton-avatar,
.skeleton-sm.skeleton-circle {
    @apply h-8 w-8;
}

.skeleton-sm.skeleton-button {
    @apply h-9 w-20;
}

.skeleton-md {
    @apply h-4;
}

.skeleton-md.skeleton-avatar,
.skeleton-md.skeleton-circle {
    @apply h-10 w-10;
}

.skeleton-md.skeleton-button {
    @apply h-10 w-24;
}

.skeleton-md.skeleton-card {
    @apply h-32 w-full;
}

.skeleton-lg {
    @apply h-5;
}

.skeleton-lg.skeleton-avatar,
.skeleton-lg.skeleton-circle {
    @apply h-12 w-12;
}

.skeleton-lg.skeleton-button {
    @apply h-11 w-28;
}

.skeleton-lg.skeleton-card {
    @apply h-40 w-full;
}

.skeleton-xl {
    @apply h-6;
}

.skeleton-xl.skeleton-avatar,
.skeleton-xl.skeleton-circle {
    @apply h-16 w-16;
}

.skeleton-xl.skeleton-button {
    @apply h-12 w-32;
}

.skeleton-xl.skeleton-card {
    @apply h-48 w-full;
}

/* Avatar content */
.skeleton-avatar-content {
    @apply w-full h-full flex items-center justify-center;
}

.skeleton-avatar-icon {
    @apply w-1/2 h-1/2 text-gray-400 dark:text-gray-500;
}

/* Text lines */
.skeleton-text-lines {
    @apply space-y-2;
}

.skeleton-text-line {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded;
    animation: skeleton-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.skeleton-text-line--short {
    @apply w-3/4;
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
@keyframes skeleton-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@keyframes skeleton-shimmer {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .skeleton-animated,
    .skeleton-animated::before,
    .skeleton-text-line {
        animation: none;
    }
    
    .skeleton-loader {
        opacity: 0.6;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .skeleton-loader {
        @apply border border-gray-400 dark:border-gray-500;
    }
    
    .skeleton-animated::before {
        background: linear-gradient(
            90deg,
            transparent,
            rgba(0, 0, 0, 0.2),
            transparent
        );
    }
    
    .dark .skeleton-animated::before {
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.2),
            transparent
        );
    }
}

/* Print styles */
@media print {
    .skeleton-loader {
        @apply bg-gray-300;
    }
    
    .skeleton-animated::before {
        display: none;
    }
}
</style>