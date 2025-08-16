<template>
    <div class="mobile-tabs">
        <!-- Tab Headers -->
        <div class="tabs-mobile" ref="tabHeaderContainer">
            <button
                v-for="(tab, index) in tabs"
                :key="tab.id || tab.value"
                @click="selectTab(tab, index)"
                class="tab-mobile"
                :class="{ 'active': isActiveTab(tab) }"
                :aria-selected="isActiveTab(tab)"
                role="tab"
            >
                <component v-if="tab.icon" :is="tab.icon" class="h-4 w-4" />
                <span>{{ tab.label }}</span>
                <span v-if="tab.badge" class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-0.5">
                    {{ tab.badge }}
                </span>
            </button>
        </div>

        <!-- Tab Content with Swipe Support -->
        <div
            ref="tabContainer"
            class="relative overflow-hidden"
            :class="{ 'swipeable': enableSwipe }"
        >
            <!-- Swipe Indicator -->
            <div v-if="showSwipeIndicator" class="swipe-indicator"></div>
            
            <!-- Tab Panels -->
            <div
                class="flex transition-transform duration-300 ease-out"
                :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
            >
                <div
                    v-for="(tab, index) in tabs"
                    :key="tab.id || tab.value"
                    class="w-full flex-shrink-0"
                    :class="{ 'opacity-0': !isActiveTab(tab) && !enableSwipe }"
                    role="tabpanel"
                    :aria-hidden="!isActiveTab(tab)"
                >
                    <slot
                        :name="tab.slot || 'default'"
                        :tab="tab"
                        :index="index"
                        :active="isActiveTab(tab)"
                    >
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                {{ tab.label }}
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Content for {{ tab.label }}
                            </p>
                        </div>
                    </slot>
                </div>
            </div>
        </div>

        <!-- Tab Navigation Dots (optional) -->
        <div v-if="showDots && tabs.length > 1" class="flex justify-center space-x-2 mt-4">
            <button
                v-for="(tab, index) in tabs"
                :key="`dot-${tab.id || tab.value}`"
                @click="selectTab(tab, index)"
                class="w-2 h-2 rounded-full transition-colors"
                :class="isActiveTab(tab) 
                    ? 'bg-blue-600 dark:bg-blue-400' 
                    : 'bg-gray-300 dark:bg-gray-600'"
                :aria-label="`Go to ${tab.label}`"
            ></button>
        </div>

        <!-- Swipe Navigation Arrows (optional) -->
        <div v-if="showArrows && tabs.length > 1" class="flex justify-between items-center mt-4">
            <button
                @click="goToPreviousTab"
                :disabled="currentIndex === 0 && !infiniteLoop"
                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors touch-target"
            >
                <ChevronLeftIcon class="h-5 w-5" />
            </button>
            
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ currentIndex + 1 }} of {{ tabs.length }}
            </span>
            
            <button
                @click="goToNextTab"
                :disabled="currentIndex === tabs.length - 1 && !infiniteLoop"
                class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors touch-target"
            >
                <ChevronRightIcon class="h-5 w-5" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useSwipeableTabs } from '@/composables/useSwipeGestures'
import {
    ChevronLeftIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
        validator: (tabs) => tabs.every(tab => tab.label && (tab.id || tab.value))
    },
    modelValue: {
        type: [String, Number],
        default: null
    },
    enableSwipe: {
        type: Boolean,
        default: true
    },
    showDots: {
        type: Boolean,
        default: false
    },
    showArrows: {
        type: Boolean,
        default: false
    },
    showSwipeIndicator: {
        type: Boolean,
        default: true
    },
    infiniteLoop: {
        type: Boolean,
        default: false
    },
    autoSwitch: {
        type: Boolean,
        default: false
    },
    autoSwitchInterval: {
        type: Number,
        default: 5000
    }
})

const emit = defineEmits(['update:modelValue', 'tab-change'])

const tabHeaderContainer = ref(null)
const currentTab = ref(props.modelValue || props.tabs[0]?.id || props.tabs[0]?.value)

// Initialize swipeable tabs
const {
    tabContainer,
    currentIndex,
    goToNextTab: swipeNext,
    goToPreviousTab: swipePrevious
} = useSwipeableTabs(
    props.tabs,
    currentTab,
    {
        onTabChange: (tabId, index) => {
            selectTab(props.tabs[index], index)
        },
        enableSwipe: props.enableSwipe
    }
)

const isActiveTab = (tab) => {
    return currentTab.value === (tab.id || tab.value)
}

const selectTab = (tab, index) => {
    const tabId = tab.id || tab.value
    currentTab.value = tabId
    emit('update:modelValue', tabId)
    emit('tab-change', { tab, index })
    
    // Scroll tab header into view if needed
    scrollTabIntoView(index)
}

const goToNextTab = () => {
    if (props.infiniteLoop || currentIndex.value < props.tabs.length - 1) {
        swipeNext()
    }
}

const goToPreviousTab = () => {
    if (props.infiniteLoop || currentIndex.value > 0) {
        swipePrevious()
    }
}

const scrollTabIntoView = (index) => {
    if (!tabHeaderContainer.value) return
    
    const tabElement = tabHeaderContainer.value.children[index]
    if (tabElement) {
        tabElement.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
        })
    }
}

// Auto-switch functionality
let autoSwitchTimer = null

const startAutoSwitch = () => {
    if (!props.autoSwitch) return
    
    stopAutoSwitch()
    autoSwitchTimer = setInterval(() => {
        goToNextTab()
    }, props.autoSwitchInterval)
}

const stopAutoSwitch = () => {
    if (autoSwitchTimer) {
        clearInterval(autoSwitchTimer)
        autoSwitchTimer = null
    }
}

// Watch for prop changes
watch(() => props.modelValue, (newValue) => {
    if (newValue !== currentTab.value) {
        currentTab.value = newValue
    }
})

watch(() => props.autoSwitch, (enabled) => {
    if (enabled) {
        startAutoSwitch()
    } else {
        stopAutoSwitch()
    }
})

onMounted(() => {
    // Set initial tab if not provided
    if (!props.modelValue && props.tabs.length > 0) {
        const firstTab = props.tabs[0]
        currentTab.value = firstTab.id || firstTab.value
        emit('update:modelValue', currentTab.value)
    }
    
    // Start auto-switch if enabled
    if (props.autoSwitch) {
        startAutoSwitch()
    }
})

// Cleanup
onUnmounted(() => {
    stopAutoSwitch()
})

// Expose methods for parent components
defineExpose({
    selectTab: (tabId) => {
        const tabIndex = props.tabs.findIndex(tab => (tab.id || tab.value) === tabId)
        if (tabIndex !== -1) {
            selectTab(props.tabs[tabIndex], tabIndex)
        }
    },
    goToNext: goToNextTab,
    goToPrevious: goToPreviousTab,
    getCurrentTab: () => currentTab.value,
    getCurrentIndex: () => currentIndex.value
})
</script>

<style scoped>
/* Tab header scrolling */
.tabs-mobile {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.tabs-mobile::-webkit-scrollbar {
    display: none;
}

/* Smooth tab transitions */
.mobile-tabs .flex {
    will-change: transform;
}

/* Touch feedback */
.tab-mobile:active {
    transform: scale(0.95);
}

/* Swipe indicator animation */
.swipe-indicator {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}

/* Accessibility improvements */
.tab-mobile:focus {
    outline: 2px solid rgb(59 130 246);
    outline-offset: 2px;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .mobile-tabs .flex {
        transition: none;
    }
    
    .swipe-indicator {
        animation: none;
    }
}
</style>