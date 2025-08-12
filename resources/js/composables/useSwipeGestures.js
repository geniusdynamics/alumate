import { ref, onMounted, onUnmounted } from 'vue'

export function useSwipeGestures(element, options = {}) {
    const {
        threshold = 50, // Minimum distance for swipe
        restraint = 100, // Maximum perpendicular distance
        allowedTime = 300, // Maximum time for swipe
        onSwipeLeft = () => {},
        onSwipeRight = () => {},
        onSwipeUp = () => {},
        onSwipeDown = () => {},
        preventDefault = true
    } = options

    const startX = ref(0)
    const startY = ref(0)
    const startTime = ref(0)
    const isTracking = ref(false)

    const handleTouchStart = (e) => {
        const touch = e.touches[0]
        startX.value = touch.clientX
        startY.value = touch.clientY
        startTime.value = Date.now()
        isTracking.value = true
    }

    const handleTouchMove = (e) => {
        if (!isTracking.value) return
        
        if (preventDefault) {
            e.preventDefault()
        }
    }

    const handleTouchEnd = (e) => {
        if (!isTracking.value) return
        
        const touch = e.changedTouches[0]
        const endX = touch.clientX
        const endY = touch.clientY
        const endTime = Date.now()
        
        const distanceX = endX - startX.value
        const distanceY = endY - startY.value
        const elapsedTime = endTime - startTime.value
        
        isTracking.value = false
        
        // Check if swipe is within time limit
        if (elapsedTime > allowedTime) return
        
        // Determine swipe direction
        if (Math.abs(distanceX) >= threshold && Math.abs(distanceY) <= restraint) {
            // Horizontal swipe
            if (distanceX > 0) {
                onSwipeRight(distanceX, elapsedTime)
            } else {
                onSwipeLeft(Math.abs(distanceX), elapsedTime)
            }
        } else if (Math.abs(distanceY) >= threshold && Math.abs(distanceX) <= restraint) {
            // Vertical swipe
            if (distanceY > 0) {
                onSwipeDown(distanceY, elapsedTime)
            } else {
                onSwipeUp(Math.abs(distanceY), elapsedTime)
            }
        }
    }

    const handleTouchCancel = () => {
        isTracking.value = false
    }

    const addListeners = (el) => {
        if (!el) return
        
        el.addEventListener('touchstart', handleTouchStart, { passive: false })
        el.addEventListener('touchmove', handleTouchMove, { passive: false })
        el.addEventListener('touchend', handleTouchEnd, { passive: true })
        el.addEventListener('touchcancel', handleTouchCancel, { passive: true })
    }

    const removeListeners = (el) => {
        if (!el) return
        
        el.removeEventListener('touchstart', handleTouchStart)
        el.removeEventListener('touchmove', handleTouchMove)
        el.removeEventListener('touchend', handleTouchEnd)
        el.removeEventListener('touchcancel', handleTouchCancel)
    }

    onMounted(() => {
        if (element.value) {
            addListeners(element.value)
        }
    })

    onUnmounted(() => {
        if (element.value) {
            removeListeners(element.value)
        }
    })

    return {
        addListeners,
        removeListeners,
        isTracking: readonly(isTracking)
    }
}

// Composable for tab navigation with swipe gestures
export function useSwipeableTabs(tabs, currentTab, options = {}) {
    const {
        onTabChange = () => {},
        enableSwipe = true,
        ...swipeOptions
    } = options

    const tabContainer = ref(null)
    const currentIndex = computed(() => {
        return tabs.findIndex(tab => tab.id === currentTab.value || tab.value === currentTab.value)
    })

    const goToNextTab = () => {
        const nextIndex = (currentIndex.value + 1) % tabs.length
        const nextTab = tabs[nextIndex]
        onTabChange(nextTab.id || nextTab.value, nextIndex)
    }

    const goToPreviousTab = () => {
        const prevIndex = currentIndex.value === 0 ? tabs.length - 1 : currentIndex.value - 1
        const prevTab = tabs[prevIndex]
        onTabChange(prevTab.id || prevTab.value, prevIndex)
    }

    const swipeGestures = enableSwipe ? useSwipeGestures(tabContainer, {
        onSwipeLeft: goToNextTab,
        onSwipeRight: goToPreviousTab,
        ...swipeOptions
    }) : null

    return {
        tabContainer,
        currentIndex,
        goToNextTab,
        goToPreviousTab,
        swipeGestures
    }
}

// Composable for pull-to-refresh functionality
export function usePullToRefresh(options = {}) {
    const {
        threshold = 80,
        onRefresh = () => {},
        refreshingText = 'Refreshing...',
        pullText = 'Pull to refresh',
        releaseText = 'Release to refresh'
    } = options

    const container = ref(null)
    const isRefreshing = ref(false)
    const pullDistance = ref(0)
    const showIndicator = ref(false)
    const indicatorText = ref(pullText)

    let startY = 0
    let currentY = 0

    const handleTouchStart = (e) => {
        if (isRefreshing.value) return
        startY = e.touches[0].clientY
    }

    const handleTouchMove = (e) => {
        if (isRefreshing.value) return
        
        currentY = e.touches[0].clientY
        const diff = currentY - startY
        
        // Only trigger if at top of page and pulling down
        if (window.scrollY === 0 && diff > 0) {
            e.preventDefault()
            
            const distance = Math.min(diff * 0.5, threshold * 1.5)
            pullDistance.value = distance
            
            if (distance > 10) {
                showIndicator.value = true
                indicatorText.value = distance > threshold ? releaseText : pullText
            }
        }
    }

    const handleTouchEnd = () => {
        if (isRefreshing.value) return
        
        if (pullDistance.value > threshold) {
            triggerRefresh()
        } else {
            resetPull()
        }
    }

    const triggerRefresh = async () => {
        isRefreshing.value = true
        indicatorText.value = refreshingText
        
        try {
            await onRefresh()
        } finally {
            setTimeout(() => {
                resetPull()
                isRefreshing.value = false
            }, 500)
        }
    }

    const resetPull = () => {
        showIndicator.value = false
        pullDistance.value = 0
        indicatorText.value = pullText
    }

    onMounted(() => {
        if (container.value) {
            const el = container.value
            el.addEventListener('touchstart', handleTouchStart, { passive: false })
            el.addEventListener('touchmove', handleTouchMove, { passive: false })
            el.addEventListener('touchend', handleTouchEnd, { passive: true })
        }
    })

    onUnmounted(() => {
        if (container.value) {
            const el = container.value
            el.removeEventListener('touchstart', handleTouchStart)
            el.removeEventListener('touchmove', handleTouchMove)
            el.removeEventListener('touchend', handleTouchEnd)
        }
    })

    return {
        container,
        isRefreshing: readonly(isRefreshing),
        pullDistance: readonly(pullDistance),
        showIndicator: readonly(showIndicator),
        indicatorText: readonly(indicatorText),
        triggerRefresh,
        resetPull
    }
}

// Utility function to detect if device supports touch
export function isTouchDevice() {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0
}

// Utility function to prevent default touch behavior
export function preventTouchDefault(element) {
    if (!element) return
    
    element.addEventListener('touchstart', (e) => e.preventDefault(), { passive: false })
    element.addEventListener('touchmove', (e) => e.preventDefault(), { passive: false })
}