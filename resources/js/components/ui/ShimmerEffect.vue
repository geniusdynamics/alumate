<template>
    <div 
        class="shimmer-effect"
        :class="[shapeClasses, sizeClasses, animationClasses]"
        :style="customStyles"
        role="status"
        :aria-label="ariaLabel"
    >
        <!-- Shimmer overlay -->
        <div 
            class="shimmer-effect__overlay"
            :class="overlayClasses"
        />
        
        <!-- Content slot for custom shimmer shapes -->
        <slot />
        
        <!-- Screen reader text -->
        <span class="sr-only">{{ screenReaderText }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
    // Shape variants
    shape?: 'rectangle' | 'circle' | 'text' | 'button' | 'card' | 'custom'
    
    // Size variants
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'custom'
    
    // Dimensions (for custom size)
    width?: string | number
    height?: string | number
    
    // Animation
    animated?: boolean
    animationSpeed?: 'slow' | 'normal' | 'fast'
    animationDirection?: 'ltr' | 'rtl'
    
    // Appearance
    intensity?: 'subtle' | 'normal' | 'strong'
    rounded?: boolean
    
    // Colors
    baseColor?: string
    shimmerColor?: string
    
    // Accessibility
    ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
    shape: 'rectangle',
    size: 'md',
    animated: true,
    animationSpeed: 'normal',
    animationDirection: 'ltr',
    intensity: 'normal',
    rounded: false
})

const shapeClasses = computed(() => {
    const shapes = {
        rectangle: 'shimmer-rectangle',
        circle: 'shimmer-circle',
        text: 'shimmer-text',
        button: 'shimmer-button',
        card: 'shimmer-card',
        custom: 'shimmer-custom'
    }
    return shapes[props.shape]
})

const sizeClasses = computed(() => {
    if (props.size === 'custom') return ''
    
    const sizes = {
        xs: 'shimmer-xs',
        sm: 'shimmer-sm',
        md: 'shimmer-md',
        lg: 'shimmer-lg',
        xl: 'shimmer-xl'
    }
    return sizes[props.size]
})

const animationClasses = computed(() => {
    const classes = []
    
    if (props.animated) {
        classes.push('shimmer-animated')
        classes.push(`shimmer-${props.animationSpeed}`)
        classes.push(`shimmer-${props.animationDirection}`)
    } else {
        classes.push('shimmer-static')
    }
    
    classes.push(`shimmer-${props.intensity}`)
    
    return classes.join(' ')
})

const overlayClasses = computed(() => {
    const classes = ['shimmer-effect__overlay-base']
    
    if (props.animated) {
        classes.push('shimmer-effect__overlay-animated')
    }
    
    return classes.join(' ')
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
    
    if (props.baseColor) {
        styles.backgroundColor = props.baseColor
    }
    
    if (props.shimmerColor) {
        styles['--shimmer-color'] = props.shimmerColor
    }
    
    return styles
})

const screenReaderText = computed(() => {
    const shapeText = {
        rectangle: 'content',
        circle: 'circular content',
        text: 'text content',
        button: 'button',
        card: 'card content',
        custom: 'content'
    }
    
    return `Loading ${shapeText[props.shape]}, please wait`
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
/* Base shimmer styles */
.shimmer-effect {
    @apply bg-gray-200 dark:bg-gray-700;
    position: relative;
    overflow: hidden;
}

/* Shape variants */
.shimmer-rectangle {
    @apply rounded;
}

.shimmer-circle {
    @apply rounded-full;
    aspect-ratio: 1;
}

.shimmer-text {
    @apply rounded h-4;
}

.shimmer-button {
    @apply rounded-lg;
}

.shimmer-card {
    @apply rounded-lg;
}

.shimmer-custom {
    /* Custom styles applied via props */
}

/* Size variants */
.shimmer-xs {
    @apply h-3;
}

.shimmer-xs.shimmer-circle {
    @apply h-6 w-6;
}

.shimmer-xs.shimmer-button {
    @apply h-8 w-16;
}

.shimmer-sm {
    @apply h-4;
}

.shimmer-sm.shimmer-circle {
    @apply h-8 w-8;
}

.shimmer-sm.shimmer-button {
    @apply h-9 w-20;
}

.shimmer-md {
    @apply h-4;
}

.shimmer-md.shimmer-circle {
    @apply h-10 w-10;
}

.shimmer-md.shimmer-button {
    @apply h-10 w-24;
}

.shimmer-md.shimmer-card {
    @apply h-32 w-full;
}

.shimmer-lg {
    @apply h-5;
}

.shimmer-lg.shimmer-circle {
    @apply h-12 w-12;
}

.shimmer-lg.shimmer-button {
    @apply h-11 w-28;
}

.shimmer-lg.shimmer-card {
    @apply h-40 w-full;
}

.shimmer-xl {
    @apply h-6;
}

.shimmer-xl.shimmer-circle {
    @apply h-16 w-16;
}

.shimmer-xl.shimmer-button {
    @apply h-12 w-32;
}

.shimmer-xl.shimmer-card {
    @apply h-48 w-full;
}

/* Shimmer overlay */
.shimmer-effect__overlay-base {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.4)),
        transparent
    );
}

.dark .shimmer-effect__overlay-base {
    background: linear-gradient(
        90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.1)),
        transparent
    );
}

.shimmer-effect__overlay-animated {
    animation: shimmer-slide var(--shimmer-duration, 2s) infinite;
}

/* Animation variants */
.shimmer-animated {
    --shimmer-duration: 2s;
}

.shimmer-slow {
    --shimmer-duration: 3s;
}

.shimmer-normal {
    --shimmer-duration: 2s;
}

.shimmer-fast {
    --shimmer-duration: 1s;
}

.shimmer-rtl .shimmer-effect__overlay-base {
    left: 100%;
    background: linear-gradient(
        -90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.4)),
        transparent
    );
}

.dark .shimmer-rtl .shimmer-effect__overlay-base {
    background: linear-gradient(
        -90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.1)),
        transparent
    );
}

.shimmer-rtl .shimmer-effect__overlay-animated {
    animation: shimmer-slide-rtl var(--shimmer-duration, 2s) infinite;
}

/* Intensity variants */
.shimmer-subtle .shimmer-effect__overlay-base {
    background: linear-gradient(
        90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.2)),
        transparent
    );
}

.dark .shimmer-subtle .shimmer-effect__overlay-base {
    background: linear-gradient(
        90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.05)),
        transparent
    );
}

.shimmer-strong .shimmer-effect__overlay-base {
    background: linear-gradient(
        90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.6)),
        transparent
    );
}

.dark .shimmer-strong .shimmer-effect__overlay-base {
    background: linear-gradient(
        90deg,
        transparent,
        var(--shimmer-color, rgba(255, 255, 255, 0.2)),
        transparent
    );
}

/* Static variant */
.shimmer-static {
    opacity: 0.6;
}

.shimmer-static .shimmer-effect__overlay-base {
    display: none;
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
@keyframes shimmer-slide {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}

@keyframes shimmer-slide-rtl {
    0% {
        left: 100%;
    }
    100% {
        left: -100%;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .shimmer-effect__overlay-animated {
        animation: none;
    }
    
    .shimmer-animated {
        opacity: 0.6;
    }
    
    .shimmer-effect__overlay-base {
        display: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .shimmer-effect {
        @apply border border-gray-400 dark:border-gray-500;
    }
    
    .shimmer-effect__overlay-base {
        background: linear-gradient(
            90deg,
            transparent,
            rgba(0, 0, 0, 0.3),
            transparent
        );
    }
    
    .dark .shimmer-effect__overlay-base {
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.3),
            transparent
        );
    }
}

/* Print styles */
@media print {
    .shimmer-effect {
        @apply bg-gray-300;
    }
    
    .shimmer-effect__overlay-base {
        display: none;
    }
}
</style>