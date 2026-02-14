<template>
    <div class="swipeable-tabs-container">
        <!-- Tab Headers -->
        <div class="tabs-mobile" ref="tabsContainer">
            <button
                v-for="(tab, index) in tabs"
                :key="tab.id"
                :ref="(el) => (tabRefs[index] = el)"
                @click="selectTab(index)"
                class="tab-mobile touch-target"
                :class="{ active: activeTab === index }"
                :aria-selected="activeTab === index"
                role="tab"
            >
                <component v-if="tab.icon" :is="tab.icon" class="mb-1 h-5 w-5" />
                <span>{{ tab.label }}</span>
                <span v-if="tab.badge" class="ml-2 rounded-full bg-red-500 px-2 py-0.5 text-xs text-white">
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
            @transitionend="handleTransitionEnd"
        >
            <div
                class="tab-content-wrapper"
                :style="{
                    transform: `translateX(-${activeTab * 100}%)`,
                    transition: isTransitioning ? 'transform 0.3s ease-out' : 'none',
                }"
            >
                <div
                    v-for="(tab, index) in tabs"
                    :key="tab.id"
                    class="tab-content-panel"
                    :class="{ active: activeTab === index }"
                    role="tabpanel"
                    :aria-labelledby="`tab-${tab.id}`"
                >
                    <slot :name="tab.id" :tab="tab" :active="activeTab === index">
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">No content for {{ tab.label }}</div>
                    </slot>
                </div>
            </div>
        </div>

        <!-- Swipe Indicator -->
        <div v-if="showSwipeIndicator" class="swipe-indicator-container">
            <div class="swipe-indicator"></div>
        </div>

        <!-- Tab Dots (for many tabs) -->
        <div v-if="tabs.length > 4" class="tab-dots">
            <button
                v-for="(tab, index) in tabs"
                :key="`dot-${tab.id}`"
                @click="selectTab(index)"
                class="tab-dot"
                :class="{ active: activeTab === index }"
                :aria-label="`Go to ${tab.label} tab`"
            ></button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
        validator: (tabs) => tabs.every((tab) => tab.id && tab.label),
    },
    initialTab: {
        type: Number,
        default: 0,
    },
    swipeThreshold: {
        type: Number,
        default: 50,
    },
    showSwipeIndicator: {
        type: Boolean,
        default: false,
    },
    enableKeyboardNavigation: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['tab-changed', 'tab-swiped']);

// Refs
const tabsContainer = ref(null);
const contentContainer = ref(null);
const tabRefs = ref([]);

// State
const activeTab = ref(props.initialTab);
const isTransitioning = ref(false);
const isSwiping = ref(false);

// Touch handling
const touchStart = ref({ x: 0, y: 0, time: 0 });
const touchCurrent = ref({ x: 0, y: 0 });
const swipeDistance = ref(0);

// Computed
const canSwipeLeft = computed(() => activeTab.value > 0);
const canSwipeRight = computed(() => activeTab.value < props.tabs.length - 1);

// Methods
const selectTab = async (index) => {
    if (index === activeTab.value || index < 0 || index >= props.tabs.length) {
        return;
    }

    const previousTab = activeTab.value;
    activeTab.value = index;
    isTransitioning.value = true;

    // Scroll tab into view if needed
    await nextTick();
    scrollTabIntoView(index);

    emit('tab-changed', {
        activeTab: index,
        previousTab,
        tab: props.tabs[index],
    });
};

const scrollTabIntoView = (index) => {
    const tabElement = tabRefs.value[index];
    if (tabElement && tabsContainer.value) {
        const container = tabsContainer.value;
        const tab = tabElement;

        const containerRect = container.getBoundingClientRect();
        const tabRect = tab.getBoundingClientRect();

        if (tabRect.left < containerRect.left) {
            container.scrollLeft -= containerRect.left - tabRect.left + 20;
        } else if (tabRect.right > containerRect.right) {
            container.scrollLeft += tabRect.right - containerRect.right + 20;
        }
    }
};

// Touch event handlers
const handleTouchStart = (e) => {
    if (isTransitioning.value) return;

    const touch = e.touches[0];
    touchStart.value = {
        x: touch.clientX,
        y: touch.clientY,
        time: Date.now(),
    };
    touchCurrent.value = { x: touch.clientX, y: touch.clientY };
    isSwiping.value = false;
    swipeDistance.value = 0;
};

const handleTouchMove = (e) => {
    if (isTransitioning.value) return;

    const touch = e.touches[0];
    touchCurrent.value = { x: touch.clientX, y: touch.clientY };

    const deltaX = touchCurrent.value.x - touchStart.value.x;
    const deltaY = touchCurrent.value.y - touchStart.value.y;

    // Determine if this is a horizontal swipe
    if (!isSwiping.value && Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
        isSwiping.value = true;
        e.preventDefault();
    }

    if (isSwiping.value) {
        e.preventDefault();
        swipeDistance.value = deltaX;

        // Provide visual feedback during swipe
        const container = contentContainer.value;
        if (container) {
            const wrapper = container.querySelector('.tab-content-wrapper');
            if (wrapper) {
                const baseTransform = -activeTab.value * 100;
                const swipePercent = (deltaX / container.offsetWidth) * 100;
                let newTransform = baseTransform + swipePercent;

                // Add resistance at boundaries
                if ((activeTab.value === 0 && deltaX > 0) || (activeTab.value === props.tabs.length - 1 && deltaX < 0)) {
                    newTransform = baseTransform + swipePercent * 0.3;
                }

                wrapper.style.transform = `translateX(${newTransform}%)`;
            }
        }
    }
};

const handleTouchEnd = (e) => {
    if (!isSwiping.value) return;

    const deltaX = swipeDistance.value;
    const deltaTime = Date.now() - touchStart.value.time;
    const velocity = Math.abs(deltaX) / deltaTime;

    // Determine if swipe should trigger tab change
    const shouldSwipe = Math.abs(deltaX) > props.swipeThreshold || velocity > 0.5;

    if (shouldSwipe) {
        if (deltaX > 0 && canSwipeLeft.value) {
            // Swipe right (go to previous tab)
            selectTab(activeTab.value - 1);
            emit('tab-swiped', { direction: 'left', tab: props.tabs[activeTab.value - 1] });
        } else if (deltaX < 0 && canSwipeRight.value) {
            // Swipe left (go to next tab)
            selectTab(activeTab.value + 1);
            emit('tab-swiped', { direction: 'right', tab: props.tabs[activeTab.value + 1] });
        } else {
            // Snap back to current tab
            isTransitioning.value = true;
        }
    } else {
        // Snap back to current tab
        isTransitioning.value = true;
    }

    // Reset swipe state
    isSwiping.value = false;
    swipeDistance.value = 0;
};

const handleTransitionEnd = () => {
    isTransitioning.value = false;
};

// Keyboard navigation
const handleKeyDown = (e) => {
    if (!props.enableKeyboardNavigation) return;

    switch (e.key) {
        case 'ArrowLeft':
            e.preventDefault();
            if (canSwipeLeft.value) {
                selectTab(activeTab.value - 1);
            }
            break;
        case 'ArrowRight':
            e.preventDefault();
            if (canSwipeRight.value) {
                selectTab(activeTab.value + 1);
            }
            break;
        case 'Home':
            e.preventDefault();
            selectTab(0);
            break;
        case 'End':
            e.preventDefault();
            selectTab(props.tabs.length - 1);
            break;
    }
};

// Lifecycle
onMounted(() => {
    if (props.enableKeyboardNavigation) {
        document.addEventListener('keydown', handleKeyDown);
    }
});

onUnmounted(() => {
    if (props.enableKeyboardNavigation) {
        document.removeEventListener('keydown', handleKeyDown);
    }
});

// Watch for external tab changes
watch(
    () => props.initialTab,
    (newTab) => {
        if (newTab !== activeTab.value) {
            selectTab(newTab);
        }
    },
);

// Expose methods for parent Components
defineExpose({
    selectTab,
    activeTab: computed(() => activeTab.value),
    canSwipeLeft,
    canSwipeRight,
});
</script>

<style scoped>
.swipeable-tabs-container {
    @apply flex h-full flex-col;
}

.tabs-mobile {
    @apply scrollbar-hide flex overflow-x-auto border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800;
    scroll-behavior: smooth;
}

.tab-mobile {
    @apply flex flex-shrink-0 flex-col items-center justify-center border-b-2 border-transparent px-4 py-3 text-sm font-medium text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300;
    min-width: 80px;
}

.tab-mobile.active {
    @apply border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400;
}

.tab-mobile:focus-visible {
    @apply rounded-lg outline-none ring-2 ring-blue-500 ring-offset-2;
}

.tab-content-container {
    @apply relative flex-1 overflow-hidden;
    touch-action: pan-x;
}

.tab-content-wrapper {
    @apply flex h-full;
    width: calc(100% * var(--tab-count, 1));
}

.tab-content-panel {
    @apply h-full w-full flex-shrink-0 overflow-y-auto;
    width: calc(100% / var(--tab-count, 1));
}

.swipe-indicator-container {
    @apply flex justify-center py-2;
}

.swipe-indicator {
    @apply h-1 w-8 rounded-full bg-gray-300 dark:bg-gray-600;
}

.tab-dots {
    @apply flex justify-center space-x-2 border-t border-gray-200 bg-white py-3 dark:border-gray-700 dark:bg-gray-800;
}

.tab-dot {
    @apply h-2 w-2 rounded-full bg-gray-300 transition-colors dark:bg-gray-600;
}

.tab-dot.active {
    @apply bg-blue-600 dark:bg-blue-400;
}

.tab-dot:focus-visible {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Dynamic CSS custom property for tab count */
.tab-content-wrapper {
    --tab-count: v-bind(tabs.length);
}

/* Smooth scrolling for tab container */
.tabs-mobile {
    scroll-behavior: smooth;
}

/* Hide scrollbar but keep functionality */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Improved touch targets for mobile */
@media (max-width: 640px) {
    .tab-mobile {
        @apply px-3 py-4;
        min-width: 70px;
    }

    .touch-target {
        min-height: 48px;
        min-width: 48px;
    }
}

/* Animation for smooth transitions */
.tab-content-wrapper {
    transition: transform 0.3s ease-out;
}

/* Accessibility improvements */
.tab-mobile[aria-selected='true'] {
    @apply border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .tab-mobile.active {
        @apply border-b-4;
    }

    .tab-dot.active {
        @apply ring-2 ring-blue-600;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .tab-content-wrapper {
        transition: none;
    }

    .tabs-mobile {
        scroll-behavior: auto;
    }
}
</style>

