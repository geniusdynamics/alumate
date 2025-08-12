<template>
    <div class="swipeable-tabs">
        <!-- Tab Headers -->
        <div 
            class="tabs-mobile" 
            ref="tabsContainer"
            role="tablist"
            :aria-label="tabsAriaLabel || 'Tab navigation'"
        >
            <button
                v-for="(tab, index) in tabs"
                :key="tab.id"
                @click="selectTab(tab.id, index)"
                @keydown="handleTabKeydown($event, index)"
                class="tab-mobile"
                :class="{ 'active': activeTab === tab.id }"
                :ref="el => tabRefs[index] = el"
                role="tab"
                :aria-selected="activeTab === tab.id"
                :aria-controls="`tabpanel-${tab.id}`"
                :id="`tab-${tab.id}`"
                :tabindex="activeTab === tab.id ? 0 : -1"
                :aria-label="tab.ariaLabel || `${tab.label} tab${tab.badge ? ` (${tab.badge} items)` : ''}`"
            >
                <component v-if="tab.icon" :is="tab.icon" class="h-4 w-4 mb-1" aria-hidden="true" />
                <span>{{ tab.label }}</span>
                <span 
                    v-if="tab.badge" 
                    class="ml-1 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5"
                    :aria-label="`${tab.badge} notifications`"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </div>

        <!-- Tab Content -->
        <div 
            class="tab-content-container"
            ref="contentContainer"
            @touchstart="handleTouchStart"
            @touchmove="handleTouchMove"
            @touchend="handleTouchEnd"
            :aria-live="announceChanges ? 'polite' : 'off'"
        >
            <div 
                class="tab-content-wrapper"
                :style="{ 
                    transform: `translateX(${contentTransform}px)`,
                    transition: isTransitioning ? 'transform 0.3s ease-out' : 'none'
                }"
            >
                <div
                    v-for="(tab, index) in tabs"
                    :key="tab.id"
                    class="tab-content-panel"
                    :class="{ 'active': activeTab === tab.id }"
                    role="tabpanel"
                    :id="`tabpanel-${tab.id}`"
                    :aria-labelledby="`tab-${tab.id}`"
                    :aria-hidden="activeTab !== tab.id"
                    :tabindex="activeTab === tab.id ? 0 : -1"
                >
                    <slot :name="tab.id" :tab="tab" :index="index" :is-active="activeTab === tab.id">
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                            No content for {{ tab.label }}
                        </div>
                    </slot>
                </div>
            </div>
        </div>

        <!-- Tab Indicators (optional) -->
        <div v-if="showIndicators" class="flex justify-center space-x-2 mt-4">
            <button
                v-for="(tab, index) in tabs"
                :key="`indicator-${tab.id}`"
                @click="selectTab(tab.id, index)"
                class="w-2 h-2 rounded-full transition-colors"
                :class="activeTab === tab.id 
                    ? 'bg-blue-600 dark:bg-blue-400' 
                    : 'bg-gray-300 dark:bg-gray-600'"
            />
        </div>

        <!-- Swipe Hint (shows on first use) -->
        <div
            v-if="showSwipeHint"
            class="fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-75 text-white text-sm px-4 py-2 rounded-full z-50"
        >
            <div class="flex items-center space-x-2">
                <ArrowLeftIcon class="h-4 w-4" />
                <span>Swipe to navigate</span>
                <ArrowRightIcon class="h-4 w-4" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useSwipeableTabs } from '@/composables/useSwipeGestures'
import {
    ArrowLeftIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
        validator: (tabs) => tabs.every(tab => tab.id && tab.label)
    },
    modelValue: {
        type: String,
        default: null
    },
    enableSwipe: {
        type: Boolean,
        default: true
    },
    showIndicators: {
        type: Boolean,
        default: false
    },
    swipeThreshold: {
        type: Number,
        default: 50
    },
    animationDuration: {
        type: Number,
        default: 300
    },
    tabsAriaLabel: {
        type: String,
        default: null
    },
    announceChanges: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['update:modelValue', 'tab-changed'])

const tabsContainer = ref(null)
const contentContainer = ref(null)
const tabRefs = ref([])
const activeTab = ref(props.modelValue || props.tabs[0]?.id)
const contentTransform = ref(0)
const isTransitioning = ref(false)
const showSwipeHint = ref(false)

// Touch handling
let startX = 0
let startY = 0
let currentX = 0
let isDragging = false
let startTime = 0

const currentIndex = computed(() => {
    return props.tabs.findIndex(tab => tab.id === activeTab.value)
})

const containerWidth = computed(() => {
    return contentContainer.value?.offsetWidth || 0
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
    if (newValue && newValue !== activeTab.value) {
        const index = props.tabs.findIndex(tab => tab.id === newValue)
        if (index !== -1) {
            selectTab(newValue, index)
        }
    }
})

// Watch for active tab changes to update transform
watch(currentIndex, (newIndex) => {
    updateContentTransform(newIndex)
})

onMounted(() => {
    // Show swipe hint for first-time users
    const hasSeenHint = localStorage.getItem('swipeable-tabs-hint-seen')
    if (!hasSeenHint && props.enableSwipe) {
        showSwipeHint.value = true
        setTimeout(() => {
            showSwipeHint.value = false
            localStorage.setItem('swipeable-tabs-hint-seen', 'true')
        }, 3000)
    }
    
    // Set initial transform
    nextTick(() => {
        updateContentTransform(currentIndex.value)
    })
})

const selectTab = (tabId, index) => {
    if (activeTab.value === tabId) return
    
    const previousTab = activeTab.value
    activeTab.value = tabId
    emit('update:modelValue', tabId)
    emit('tab-changed', { tabId, index, tab: props.tabs[index] })
    
    // Scroll tab into view if needed
    scrollTabIntoView(index)
    
    // Announce tab change to screen readers
    if (props.announceChanges && previousTab !== tabId) {
        announceTabChange(props.tabs[index])
    }
}

const announceTabChange = (tab) => {
    // Create a temporary element for screen reader announcement
    const announcement = document.createElement('div')
    announcement.setAttribute('aria-live', 'polite')
    announcement.setAttribute('aria-atomic', 'true')
    announcement.className = 'sr-only'
    announcement.textContent = `Switched to ${tab.label} tab`
    
    document.body.appendChild(announcement)
    
    // Remove after announcement
    setTimeout(() => {
        document.body.removeChild(announcement)
    }, 1000)
}

const scrollTabIntoView = (index) => {
    const tabElement = tabRefs.value[index]
    if (tabElement && tabsContainer.value) {
        const container = tabsContainer.value
        const tab = tabElement
        
        const containerRect = container.getBoundingClientRect()
        const tabRect = tab.getBoundingClientRect()
        
        if (tabRect.left < containerRect.left) {
            container.scrollLeft -= containerRect.left - tabRect.left + 16
        } else if (tabRect.right > containerRect.right) {
            container.scrollLeft += tabRect.right - containerRect.right + 16
        }
    }
}

const updateContentTransform = (index) => {
    if (!containerWidth.value) return
    
    isTransitioning.value = true
    contentTransform.value = -index * containerWidth.value
    
    setTimeout(() => {
        isTransitioning.value = false
    }, props.animationDuration)
}

const goToNextTab = () => {
    const nextIndex = (currentIndex.value + 1) % props.tabs.length
    const nextTab = props.tabs[nextIndex]
    selectTab(nextTab.id, nextIndex)
}

const goToPreviousTab = () => {
    const prevIndex = currentIndex.value === 0 ? props.tabs.length - 1 : currentIndex.value - 1
    const prevTab = props.tabs[prevIndex]
    selectTab(prevTab.id, prevIndex)
}

// Touch event handlers
const handleTouchStart = (e) => {
    if (!props.enableSwipe) return
    
    const touch = e.touches[0]
    startX = touch.clientX
    startY = touch.clientY
    currentX = startX
    startTime = Date.now()
    isDragging = false
    
    // Stop any ongoing transitions
    isTransitioning.value = false
}

const handleTouchMove = (e) => {
    if (!props.enableSwipe) return
    
    const touch = e.touches[0]
    currentX = touch.clientX
    const deltaX = currentX - startX
    const deltaY = touch.clientY - startY
    
    // Only handle horizontal swipes
    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
        isDragging = true
        e.preventDefault()
        
        // Update transform with resistance at boundaries
        const baseTransform = -currentIndex.value * containerWidth.value
        let newTransform = baseTransform + deltaX
        
        // Add resistance at boundaries
        if (currentIndex.value === 0 && deltaX > 0) {
            newTransform = baseTransform + deltaX * 0.3
        } else if (currentIndex.value === props.tabs.length - 1 && deltaX < 0) {
            newTransform = baseTransform + deltaX * 0.3
        }
        
        contentTransform.value = newTransform
    }
}

const handleTouchEnd = (e) => {
    if (!props.enableSwipe || !isDragging) return
    
    const deltaX = currentX - startX
    const deltaTime = Date.now() - startTime
    const velocity = Math.abs(deltaX) / deltaTime
    
    // Determine if swipe should trigger tab change
    const shouldChange = Math.abs(deltaX) > props.swipeThreshold || velocity > 0.5
    
    if (shouldChange) {
        if (deltaX > 0 && currentIndex.value > 0) {
            goToPreviousTab()
        } else if (deltaX < 0 && currentIndex.value < props.tabs.length - 1) {
            goToNextTab()
        } else {
            // Snap back to current tab
            updateContentTransform(currentIndex.value)
        }
    } else {
        // Snap back to current tab
        updateContentTransform(currentIndex.value)
    }
    
    isDragging = false
}

// Tab keyboard navigation (ARIA pattern)
const handleTabKeydown = (e, index) => {
    switch (e.key) {
        case 'ArrowLeft':
            e.preventDefault()
            focusPreviousTab(index)
            break
        case 'ArrowRight':
            e.preventDefault()
            focusNextTab(index)
            break
        case 'Home':
            e.preventDefault()
            focusFirstTab()
            break
        case 'End':
            e.preventDefault()
            focusLastTab()
            break
        case 'Enter':
        case ' ':
            e.preventDefault()
            selectTab(props.tabs[index].id, index)
            break
    }
}

const focusPreviousTab = (currentIndex) => {
    const prevIndex = currentIndex === 0 ? props.tabs.length - 1 : currentIndex - 1
    focusTab(prevIndex)
}

const focusNextTab = (currentIndex) => {
    const nextIndex = (currentIndex + 1) % props.tabs.length
    focusTab(nextIndex)
}

const focusFirstTab = () => {
    focusTab(0)
}

const focusLastTab = () => {
    focusTab(props.tabs.length - 1)
}

const focusTab = (index) => {
    const tabElement = tabRefs.value[index]
    if (tabElement) {
        tabElement.focus()
    }
}

// Global keyboard navigation for content area
const handleKeydown = (e) => {
    if (e.target.closest('.swipeable-tabs')) {
        switch (e.key) {
            case 'ArrowLeft':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault()
                    goToPreviousTab()
                }
                break
            case 'ArrowRight':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault()
                    goToNextTab()
                }
                break
        }
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
})

// Expose methods for parent components
defineExpose({
    selectTab,
    goToNextTab,
    goToPreviousTab,
    currentIndex: computed(() => currentIndex.value),
    activeTab: computed(() => activeTab.value)
})
</script>

<style scoped>
.swipeable-tabs {
    /* Component container */
}

.tab-content-container {
    overflow: hidden;
    position: relative;
}

.tab-content-wrapper {
    display: flex;
    width: 100%;
    will-change: transform;
}

.tab-content-panel {
    flex: 0 0 100%;
    width: 100%;
}

.tab-content-panel:not(.active) {
    /* Hide inactive panels from screen readers and interaction */
    visibility: hidden;
    pointer-events: none;
}

.tab-content-panel.active {
    visibility: visible;
    pointer-events: auto;
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

/* Smooth scrolling for tab headers */
.tabs-mobile {
    scroll-behavior: smooth;
}

/* Touch feedback */
.tab-mobile:active {
    transform: scale(0.95);
    transition: transform 0.1s ease-out;
}

/* Swipe hint animation */
.swipe-hint {
    animation: swipeHintPulse 2s infinite;
}

@keyframes swipeHintPulse {
    0%, 100% {
        opacity: 0.8;
        transform: translateX(-50%) scale(1);
    }
    50% {
        opacity: 1;
        transform: translateX(-50%) scale(1.05);
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .tab-content-wrapper {
        transition: none !important;
    }
    
    .tab-mobile:active {
        transform: none;
        transition: none;
    }
    
    .swipe-hint {
        animation: none;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .tab-mobile.active {
        border-color: currentColor;
        border-width: 2px;
    }
}
</style>