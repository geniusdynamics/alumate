import { ref, onMounted, onUnmounted } from 'vue'

export function useScrollTracking() {
  const scrollDepth = ref(0)
  const isScrolling = ref(false)
  const scrollDirection = ref<'up' | 'down'>('down')
  const lastScrollY = ref(0)
  const maxScrollDepth = ref(0)

  let scrollTimer: NodeJS.Timeout | null = null

  const updateScrollMetrics = () => {
    const windowHeight = window.innerHeight
    const documentHeight = document.documentElement.scrollHeight
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop

    // Calculate scroll depth as percentage
    const scrollableHeight = documentHeight - windowHeight
    const currentDepth = scrollableHeight > 0 ? (scrollTop / scrollableHeight) * 100 : 0
    
    scrollDepth.value = Math.min(Math.max(currentDepth, 0), 100)
    
    // Track maximum scroll depth
    if (scrollDepth.value > maxScrollDepth.value) {
      maxScrollDepth.value = scrollDepth.value
    }

    // Determine scroll direction
    if (scrollTop > lastScrollY.value) {
      scrollDirection.value = 'down'
    } else if (scrollTop < lastScrollY.value) {
      scrollDirection.value = 'up'
    }
    
    lastScrollY.value = scrollTop

    // Set scrolling state
    isScrolling.value = true
    
    // Clear existing timer
    if (scrollTimer) {
      clearTimeout(scrollTimer)
    }
    
    // Set timer to detect when scrolling stops
    scrollTimer = setTimeout(() => {
      isScrolling.value = false
    }, 150)
  }

  const getScrollPosition = () => {
    return {
      x: window.pageXOffset || document.documentElement.scrollLeft,
      y: window.pageYOffset || document.documentElement.scrollTop
    }
  }

  const getViewportInfo = () => {
    return {
      width: window.innerWidth,
      height: window.innerHeight,
      scrollWidth: document.documentElement.scrollWidth,
      scrollHeight: document.documentElement.scrollHeight
    }
  }

  const isElementInViewport = (element: Element, threshold = 0) => {
    const rect = element.getBoundingClientRect()
    const windowHeight = window.innerHeight
    const windowWidth = window.innerWidth

    const verticalThreshold = windowHeight * threshold
    const horizontalThreshold = windowWidth * threshold

    return (
      rect.top >= -verticalThreshold &&
      rect.left >= -horizontalThreshold &&
      rect.bottom <= windowHeight + verticalThreshold &&
      rect.right <= windowWidth + horizontalThreshold
    )
  }

  const scrollToElement = (element: Element | string, options?: ScrollIntoViewOptions) => {
    const targetElement = typeof element === 'string' 
      ? document.querySelector(element)
      : element

    if (targetElement) {
      targetElement.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
        inline: 'nearest',
        ...options
      })
    }
  }

  const scrollToTop = (smooth = true) => {
    window.scrollTo({
      top: 0,
      left: 0,
      behavior: smooth ? 'smooth' : 'auto'
    })
  }

  const scrollToBottom = (smooth = true) => {
    window.scrollTo({
      top: document.documentElement.scrollHeight,
      left: 0,
      behavior: smooth ? 'smooth' : 'auto'
    })
  }

  onMounted(() => {
    // Initial calculation
    updateScrollMetrics()
    
    // Add scroll listener
    window.addEventListener('scroll', updateScrollMetrics, { passive: true })
    
    // Add resize listener to recalculate on window resize
    window.addEventListener('resize', updateScrollMetrics, { passive: true })
  })

  onUnmounted(() => {
    window.removeEventListener('scroll', updateScrollMetrics)
    window.removeEventListener('resize', updateScrollMetrics)
    
    if (scrollTimer) {
      clearTimeout(scrollTimer)
    }
  })

  return {
    scrollDepth,
    isScrolling,
    scrollDirection,
    maxScrollDepth,
    getScrollPosition,
    getViewportInfo,
    isElementInViewport,
    scrollToElement,
    scrollToTop,
    scrollToBottom
  }
}