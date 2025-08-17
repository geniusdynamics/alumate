<template>
    <div ref="container" class="pull-to-refresh-container relative">
        <!-- Pull to Refresh Indicator -->
        <div
            v-if="showIndicator"
            class="pull-to-refresh"
            :style="{ transform: `translateY(${Math.max(0, pullDistance - 60)}px)` }"
        >
            <div class="pull-to-refresh-content">
                <div v-if="isRefreshing" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                    <ArrowPathIcon class="h-5 w-5 animate-spin" />
                    <span class="text-sm font-medium">{{ refreshingText }}</span>
                </div>
                <div v-else class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                    <ArrowDownIcon 
                        class="h-5 w-5 transition-transform duration-200"
                        :class="{ 'rotate-180': pullDistance > threshold }"
                    />
                    <span class="text-sm font-medium">{{ indicatorText }}</span>
                </div>
            </div>
        </div>

        <!-- Content Slot -->
        <div
            class="pull-to-refresh-content-wrapper"
            :style="{ transform: showIndicator ? `translateY(${Math.min(pullDistance, maxPullDistance)}px)` : 'translateY(0)' }"
        >
            <slot :is-refreshing="isRefreshing" :pull-distance="pullDistance" />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePullToRefresh } from '@/composables/useSwipeGestures'
import {
    ArrowDownIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    threshold: {
        type: Number,
        default: 80
    },
    maxPullDistance: {
        type: Number,
        default: 120
    },
    refreshingText: {
        type: String,
        default: 'Refreshing...'
    },
    pullText: {
        type: String,
        default: 'Pull to refresh'
    },
    releaseText: {
        type: String,
        default: 'Release to refresh'
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['refresh'])

const {
    container,
    isRefreshing,
    pullDistance,
    showIndicator,
    indicatorText,
    triggerRefresh,
    resetPull
} = usePullToRefresh({
    threshold: props.threshold,
    onRefresh: async () => {
        emit('refresh')
    },
    refreshingText: props.refreshingText,
    pullText: props.pullText,
    releaseText: props.releaseText
})

// Expose methods for parent components
defineExpose({
    refresh: triggerRefresh,
    reset: resetPull,
    isRefreshing
})
</script>

<style scoped>
.pull-to-refresh-container {
    overflow: hidden;
}

.pull-to-refresh {
    position: absolute;
    top: -60px;
    left: 0;
    right: 0;
    height: 60px;
    z-index: 10;
}

.pull-to-refresh-content-wrapper {
    transition: transform 0.2s ease-out;
    will-change: transform;
}

/* Smooth animations */
.pull-to-refresh-content {
    transition: all 0.2s ease-out;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .pull-to-refresh-content-wrapper,
    .pull-to-refresh-content {
        transition: none;
    }
    
    .animate-spin {
        animation: none;
    }
}
</style>