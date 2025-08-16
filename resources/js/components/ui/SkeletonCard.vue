<template>
    <div 
        class="skeleton-card"
        :class="[variantClasses, sizeClasses]"
        role="status"
        :aria-label="ariaLabel"
    >
        <!-- Header with avatar and text -->
        <div v-if="showHeader" class="skeleton-card__header">
            <SkeletonLoader 
                v-if="showAvatar"
                shape="avatar" 
                :size="avatarSize" 
                class="skeleton-card__avatar"
            />
            <div class="skeleton-card__header-content">
                <SkeletonLoader 
                    shape="text" 
                    :size="titleSize" 
                    width="60%" 
                    class="skeleton-card__title"
                />
                <SkeletonLoader 
                    v-if="showSubtitle"
                    shape="text" 
                    size="sm" 
                    width="40%" 
                    class="skeleton-card__subtitle"
                />
            </div>
            <SkeletonLoader 
                v-if="showActions"
                shape="button" 
                size="sm" 
                class="skeleton-card__action"
            />
        </div>
        
        <!-- Media/Image -->
        <SkeletonLoader 
            v-if="showImage"
            shape="image" 
            :height="imageHeight"
            class="skeleton-card__image"
        />
        
        <!-- Content -->
        <div v-if="showContent" class="skeleton-card__content">
            <SkeletonLoader 
                v-if="showTitle && !showHeader"
                shape="text" 
                :size="titleSize" 
                width="70%" 
                class="skeleton-card__content-title"
            />
            <div class="skeleton-card__text-lines">
                <SkeletonLoader 
                    v-for="line in contentLines" 
                    :key="line"
                    shape="text" 
                    size="sm"
                    :width="line === contentLines ? '60%' : '100%'"
                    class="skeleton-card__text-line"
                />
            </div>
        </div>
        
        <!-- Footer with actions -->
        <div v-if="showFooter" class="skeleton-card__footer">
            <div class="skeleton-card__footer-actions">
                <SkeletonLoader 
                    v-for="action in footerActions" 
                    :key="action"
                    shape="button" 
                    size="sm"
                    class="skeleton-card__footer-action"
                />
            </div>
            <SkeletonLoader 
                v-if="showTimestamp"
                shape="text" 
                size="xs" 
                width="30%"
                class="skeleton-card__timestamp"
            />
        </div>
        
        <!-- Screen reader text -->
        <span class="sr-only">{{ screenReaderText }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import SkeletonLoader from './SkeletonLoader.vue'

interface Props {
    // Card variant
    variant?: 'post' | 'profile' | 'job' | 'event' | 'article' | 'product' | 'basic'
    
    // Size
    size?: 'sm' | 'md' | 'lg'
    
    // Layout options
    showHeader?: boolean
    showAvatar?: boolean
    showImage?: boolean
    showContent?: boolean
    showFooter?: boolean
    showTitle?: boolean
    showSubtitle?: boolean
    showActions?: boolean
    showTimestamp?: boolean
    
    // Content configuration
    contentLines?: number
    footerActions?: number
    imageHeight?: string
    
    // Accessibility
    ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'basic',
    size: 'md',
    showHeader: true,
    showAvatar: true,
    showImage: false,
    showContent: true,
    showFooter: true,
    showTitle: true,
    showSubtitle: false,
    showActions: false,
    showTimestamp: true,
    contentLines: 3,
    footerActions: 3,
    imageHeight: '200px'
})

const variantClasses = computed(() => {
    const variants = {
        post: 'skeleton-card--post',
        profile: 'skeleton-card--profile',
        job: 'skeleton-card--job',
        event: 'skeleton-card--event',
        article: 'skeleton-card--article',
        product: 'skeleton-card--product',
        basic: 'skeleton-card--basic'
    }
    return variants[props.variant]
})

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'skeleton-card--sm',
        md: 'skeleton-card--md',
        lg: 'skeleton-card--lg'
    }
    return sizes[props.size]
})

const avatarSize = computed(() => {
    const sizes = {
        sm: 'sm',
        md: 'md',
        lg: 'lg'
    }
    return sizes[props.size]
})

const titleSize = computed(() => {
    const sizes = {
        sm: 'sm',
        md: 'md',
        lg: 'lg'
    }
    return sizes[props.size]
})

const screenReaderText = computed(() => {
    const variantText = {
        post: 'social media post',
        profile: 'user profile',
        job: 'job listing',
        event: 'event details',
        article: 'article',
        product: 'product',
        basic: 'content'
    }
    
    return `Loading ${variantText[props.variant]}, please wait`
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
.skeleton-card {
    @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden;
}

/* Size variants */
.skeleton-card--sm {
    @apply p-3 space-y-3;
}

.skeleton-card--md {
    @apply p-4 space-y-4;
}

.skeleton-card--lg {
    @apply p-6 space-y-6;
}

/* Header */
.skeleton-card__header {
    @apply flex items-start space-x-3;
}

.skeleton-card__header-content {
    @apply flex-1 space-y-2;
}

.skeleton-card__title {
    @apply block;
}

.skeleton-card__subtitle {
    @apply block;
}

.skeleton-card__action {
    @apply flex-shrink-0;
}

/* Image */
.skeleton-card__image {
    @apply w-full;
}

/* Content */
.skeleton-card__content {
    @apply space-y-3;
}

.skeleton-card__content-title {
    @apply block;
}

.skeleton-card__text-lines {
    @apply space-y-2;
}

.skeleton-card__text-line {
    @apply block;
}

/* Footer */
.skeleton-card__footer {
    @apply flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700;
}

.skeleton-card__footer-actions {
    @apply flex items-center space-x-2;
}

.skeleton-card__footer-action {
    @apply flex-shrink-0;
}

.skeleton-card__timestamp {
    @apply flex-shrink-0;
}

/* Variant-specific styles */
.skeleton-card--post .skeleton-card__image {
    @apply -mx-4 -mt-4 mb-4;
}

.skeleton-card--post.skeleton-card--sm .skeleton-card__image {
    @apply -mx-3 -mt-3 mb-3;
}

.skeleton-card--post.skeleton-card--lg .skeleton-card__image {
    @apply -mx-6 -mt-6 mb-6;
}

.skeleton-card--profile {
    @apply text-center;
}

.skeleton-card--profile .skeleton-card__header {
    @apply flex-col items-center space-x-0 space-y-3;
}

.skeleton-card--profile .skeleton-card__header-content {
    @apply items-center;
}

.skeleton-card--job .skeleton-card__header {
    @apply items-center;
}

.skeleton-card--event .skeleton-card__content {
    @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.skeleton-card--article .skeleton-card__image {
    @apply aspect-video;
}

.skeleton-card--product .skeleton-card__image {
    @apply aspect-square;
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
</style>