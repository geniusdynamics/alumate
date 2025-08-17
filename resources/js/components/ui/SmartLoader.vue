<template>
    <div 
        class="smart-loader"
        :class="containerClasses"
        role="status"
        :aria-label="ariaLabel"
        :aria-live="ariaLive"
    >
        <!-- Skeleton Loading -->
        <template v-if="displayType === 'skeleton'">
            <!-- Card skeleton -->
            <SkeletonCard 
                v-if="skeletonVariant === 'card'"
                :variant="cardVariant"
                :size="size"
                :show-header="showHeader"
                :show-avatar="showAvatar"
                :show-image="showImage"
                :show-content="showContent"
                :show-footer="showFooter"
                :content-lines="contentLines"
                :footer-actions="footerActions"
            />
            
            <!-- List skeleton -->
            <SkeletonList 
                v-else-if="skeletonVariant === 'list'"
                :count="skeletonCount"
                :variant="listVariant"
                :spacing="spacing"
                :show-avatar="showAvatar"
                :show-secondary="showSecondary"
                :show-tertiary="showTertiary"
                :show-actions="showActions"
                :avatar-size="avatarSize"
                :action-count="actionCount"
                :vary-widths="varyWidths"
            />
            
            <!-- Custom skeleton -->
            <LoadingState 
                v-else
                type="skeleton"
                :variant="skeletonVariant"
                :skeleton-count="skeletonCount"
                :skeleton-shape="skeletonShape"
                :skeleton-width="skeletonWidth"
                :skeleton-height="skeletonHeight"
                :table-columns="tableColumns"
                :size="size"
                :centered="centered"
                :full-height="fullHeight"
            />
        </template>
        
        <!-- Contextual Loading -->
        <ContextualLoader 
            v-else-if="displayType === 'contextual'"
            :context="context"
            :indicator-type="indicatorType"
            :size="size"
            :layout="layout"
            :centered="centered"
            :show-icon="showIcon"
            :custom-icon="customIcon"
            :title="title"
            :message="message"
            :show-progress="showProgress"
            :progress="progress"
            :progress-text="progressText"
            :steps="steps"
            :current-step="currentStep"
            :spinner-size="spinnerSize"
            :spinner-color="spinnerColor"
        />
        
        <!-- Shimmer Loading -->
        <template v-else-if="displayType === 'shimmer'">
            <div class="smart-loader__shimmer-container">
                <ShimmerEffect 
                    v-for="item in shimmerCount" 
                    :key="item"
                    :shape="shimmerShape"
                    :size="size"
                    :width="shimmerWidth"
                    :height="shimmerHeight"
                    :animated="animated"
                    :animation-speed="animationSpeed"
                    :animation-direction="animationDirection"
                    :intensity="intensity"
                    :rounded="rounded"
                    :base-color="baseColor"
                    :shimmer-color="shimmerColor"
                />
            </div>
        </template>
        
        <!-- Spinner Loading (fallback) -->
        <LoadingSpinner 
            v-else
            :size="spinnerSize"
            :color="spinnerColor"
            :centered="centered"
            :text="message"
        />
        
        <!-- Error State -->
        <div v-if="error" class="smart-loader__error">
            <ExclamationTriangleIcon class="smart-loader__error-icon" />
            <div class="smart-loader__error-content">
                <h3 class="smart-loader__error-title">
                    Loading Error
                </h3>
                <p class="smart-loader__error-message">
                    {{ error }}
                </p>
                <button 
                    v-if="onRetry"
                    @click="onRetry"
                    class="smart-loader__error-retry"
                    type="button"
                >
                    Try Again
                </button>
            </div>
        </div>
        
        <!-- Screen reader text -->
        <span class="sr-only">{{ screenReaderText }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import LoadingState from './LoadingState.vue'
import LoadingSpinner from './LoadingSpinner.vue'
import SkeletonCard from './SkeletonCard.vue'
import SkeletonList from './SkeletonList.vue'
import ContextualLoader from './ContextualLoader.vue'
import ShimmerEffect from './ShimmerEffect.vue'

interface Props {
    // Loading state
    loading?: boolean
    error?: string | null
    
    // Type selection
    type?: 'auto' | 'skeleton' | 'contextual' | 'shimmer' | 'spinner'
    
    // Context for auto-detection
    context?: 'posts' | 'profile' | 'jobs' | 'events' | 'search' | 'upload' | 'form' | 'list' | 'card' | 'custom'
    
    // Skeleton options
    skeletonVariant?: 'card' | 'list' | 'table' | 'profile' | 'custom'
    skeletonCount?: number
    skeletonShape?: 'rectangle' | 'circle' | 'text' | 'avatar' | 'button'
    skeletonWidth?: string | number
    skeletonHeight?: string | number
    
    // Card skeleton options
    cardVariant?: 'post' | 'profile' | 'job' | 'event' | 'article' | 'product' | 'basic'
    showHeader?: boolean
    showAvatar?: boolean
    showImage?: boolean
    showContent?: boolean
    showFooter?: boolean
    contentLines?: number
    footerActions?: number
    
    // List skeleton options
    listVariant?: 'simple' | 'detailed' | 'compact' | 'card' | 'table'
    showSecondary?: boolean
    showTertiary?: boolean
    showActions?: boolean
    actionCount?: number
    varyWidths?: boolean
    tableColumns?: number
    
    // Shimmer options
    shimmerCount?: number
    shimmerShape?: 'rectangle' | 'circle' | 'text' | 'button' | 'card' | 'custom'
    shimmerWidth?: string | number
    shimmerHeight?: string | number
    animated?: boolean
    animationSpeed?: 'slow' | 'normal' | 'fast'
    animationDirection?: 'ltr' | 'rtl'
    intensity?: 'subtle' | 'normal' | 'strong'
    rounded?: boolean
    baseColor?: string
    shimmerColor?: string
    
    // Contextual loader options
    indicatorType?: 'spinner' | 'dots' | 'pulse'
    showIcon?: boolean
    customIcon?: any
    title?: string
    message?: string
    showProgress?: boolean
    progress?: number
    progressText?: string
    steps?: string[]
    currentStep?: number
    
    // Layout options
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    layout?: 'vertical' | 'horizontal'
    spacing?: 'tight' | 'normal' | 'loose'
    centered?: boolean
    fullHeight?: boolean
    
    // Avatar options
    avatarSize?: 'xs' | 'sm' | 'md' | 'lg'
    
    // Spinner options
    spinnerSize?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    spinnerColor?: 'primary' | 'secondary' | 'white' | 'gray' | 'current'
    
    // Callbacks
    onRetry?: () => void
    
    // Accessibility
    ariaLabel?: string
    ariaLive?: 'polite' | 'assertive' | 'off'
}

const props = withDefaults(defineProps<Props>(), {
    loading: true,
    type: 'auto',
    context: 'custom',
    skeletonVariant: 'custom',
    skeletonCount: 3,
    skeletonShape: 'rectangle',
    cardVariant: 'basic',
    showHeader: true,
    showAvatar: true,
    showImage: false,
    showContent: true,
    showFooter: true,
    contentLines: 3,
    footerActions: 3,
    listVariant: 'simple',
    showSecondary: true,
    showTertiary: false,
    showActions: false,
    actionCount: 1,
    varyWidths: true,
    tableColumns: 3,
    shimmerCount: 3,
    shimmerShape: 'rectangle',
    animated: true,
    animationSpeed: 'normal',
    animationDirection: 'ltr',
    intensity: 'normal',
    rounded: false,
    indicatorType: 'spinner',
    showIcon: true,
    showProgress: false,
    progress: 0,
    currentStep: 0,
    size: 'md',
    layout: 'vertical',
    spacing: 'normal',
    centered: false,
    fullHeight: false,
    avatarSize: 'md',
    spinnerSize: 'md',
    spinnerColor: 'primary',
    ariaLive: 'polite'
})

const displayType = computed(() => {
    if (!props.loading) return 'none'
    if (props.type !== 'auto') return props.type
    
    // Auto-detect based on context
    const contextMap: Record<string, string> = {
        posts: 'skeleton',
        profile: 'skeleton',
        jobs: 'skeleton',
        events: 'skeleton',
        list: 'skeleton',
        card: 'skeleton',
        search: 'spinner',
        upload: 'contextual',
        form: 'contextual',
        custom: 'spinner'
    }
    
    return contextMap[props.context] || 'spinner'
})

const containerClasses = computed(() => {
    const classes = []
    
    if (props.fullHeight) {
        classes.push('smart-loader--full-height')
    }
    
    if (props.centered && displayType.value !== 'contextual') {
        classes.push('smart-loader--centered')
    }
    
    return classes.join(' ')
})

const screenReaderText = computed(() => {
    if (props.error) {
        return `Loading failed: ${props.error}`
    }
    
    if (props.message) {
        return props.message
    }
    
    const contextText: Record<string, string> = {
        posts: 'Loading posts',
        profile: 'Loading profile',
        jobs: 'Loading job opportunities',
        events: 'Loading events',
        search: 'Searching',
        upload: 'Uploading files',
        form: 'Processing form',
        list: 'Loading list items',
        card: 'Loading content',
        custom: 'Loading'
    }
    
    return `${contextText[props.context] || 'Loading'}, please wait`
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
.smart-loader {
    @apply w-full;
}

.smart-loader--full-height {
    @apply min-h-screen;
}

.smart-loader--centered {
    @apply flex items-center justify-center;
}

/* Shimmer container */
.smart-loader__shimmer-container {
    @apply space-y-3;
}

/* Error state */
.smart-loader__error {
    @apply flex items-start space-x-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg;
}

.smart-loader__error-icon {
    @apply h-6 w-6 text-red-500 dark:text-red-400 flex-shrink-0 mt-0.5;
}

.smart-loader__error-content {
    @apply flex-1 space-y-2;
}

.smart-loader__error-title {
    @apply text-sm font-medium text-red-800 dark:text-red-200;
}

.smart-loader__error-message {
    @apply text-sm text-red-700 dark:text-red-300;
}

.smart-loader__error-retry {
    @apply inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 dark:text-red-200 dark:bg-red-800 dark:hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors;
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