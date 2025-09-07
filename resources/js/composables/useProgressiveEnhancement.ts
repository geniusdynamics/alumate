import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

export interface ProgressiveEnhancementOptions {
  enableJavaScript: boolean
  enableRealTimeValidation: boolean
  enableAutoSave: boolean
  enableAccessibilityFeatures: boolean
  enablePerformanceOptimizations: boolean
  fallbackMode: 'basic' | 'enhanced' | 'auto'
  connectionSpeed: 'slow' | 'fast' | 'auto'
}

export function useProgressiveEnhancement() {
  const isJavaScriptEnabled = ref(true)
  const isOnline = ref(navigator.onLine)
  const connectionSpeed = ref<'slow' | 'fast'>('fast')
  const supportsIntersectionObserver = ref(false)
  const supportsResizeObserver = ref(false)
  const supportsWebWorkers = ref(false)
  const supportsLocalStorage = ref(false)
  const supportsSessionStorage = ref(false)
  const prefersReducedMotion = ref(false)
  const isHighContrast = ref(false)
  const isTouchDevice = ref(false)
  const screenReaderDetected = ref(false)
  const keyboardNavigationActive = ref(false)

  const enhancementLevel = computed(() => {
    if (!isJavaScriptEnabled.value || !isOnline.value) {
      return 'basic'
    }
    
    if (connectionSpeed.value === 'slow' || prefersReducedMotion.value) {
      return 'minimal'
    }
    
    return 'full'
  })

  const shouldEnableFeature = (feature: keyof ProgressiveEnhancementOptions): boolean => {
    const level = enhancementLevel.value
    
    switch (feature) {
      case 'enableJavaScript':
        return isJavaScriptEnabled.value
        
      case 'enableRealTimeValidation':
        return level === 'full' && connectionSpeed.value === 'fast'
        
      case 'enableAutoSave':
        return level !== 'basic' && supportsLocalStorage.value
        
      case 'enableAccessibilityFeatures':
        return true // Always enable accessibility
        
      case 'enablePerformanceOptimizations':
        return level === 'full'
        
      default:
        return level === 'full'
    }
  }

  const detectCapabilities = () => {
    // Feature detection
    supportsIntersectionObserver.value = 'IntersectionObserver' in window
    supportsResizeObserver.value = 'ResizeObserver' in window
    supportsWebWorkers.value = 'Worker' in window
    
    // Storage detection
    try {
      localStorage.setItem('test', 'test')
      localStorage.removeItem('test')
      supportsLocalStorage.value = true
    } catch {
      supportsLocalStorage.value = false
    }
    
    try {
      sessionStorage.setItem('test', 'test')
      sessionStorage.removeItem('test')
      supportsSessionStorage.value = true
    } catch {
      supportsSessionStorage.value = false
    }
    
    // Accessibility preferences
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    prefersReducedMotion.value = mediaQuery.matches
    
    const contrastQuery = window.matchMedia('(prefers-contrast: high)')
    isHighContrast.value = contrastQuery.matches
    
    // Touch device detection
    isTouchDevice.value = 'ontouchstart' in window || navigator.maxTouchPoints > 0
    
    // Screen reader detection (heuristic)
    detectScreenReader()
    
    // Connection speed detection
    detectConnectionSpeed()
  }

  const detectScreenReader = () => {
    // Heuristic detection of screen readers
    const indicators = [
      // Check for common screen reader user agents
      /JAWS|NVDA|ORCA|VoiceOver|TalkBack/i.test(navigator.userAgent),
      
      // Check for accessibility APIs
      'speechSynthesis' in window,
      
      // Check for high contrast mode (often used with screen readers)
      isHighContrast.value,
      
      // Check for reduced motion (often preferred by screen reader users)
      prefersReducedMotion.value
    ]
    
    screenReaderDetected.value = indicators.some(Boolean)
  }

  const detectConnectionSpeed = () => {
    // Use Network Information API if available
    if ('connection' in navigator) {
      const connection = (navigator as any).connection
      const effectiveType = connection.effectiveType
      
      if (effectiveType === 'slow-2g' || effectiveType === '2g') {
        connectionSpeed.value = 'slow'
      } else {
        connectionSpeed.value = 'fast'
      }
    } else {
      // Fallback: measure load time
      const startTime = performance.now()
      
      // Create a small image to test connection
      const img = new Image()
      img.onload = () => {
        const loadTime = performance.now() - startTime
        connectionSpeed.value = loadTime > 1000 ? 'slow' : 'fast'
      }
      img.onerror = () => {
        connectionSpeed.value = 'slow'
      }
      img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
    }
  }

  const setupKeyboardNavigation = () => {
    let isUsingKeyboard = false
    
    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Tab') {
        isUsingKeyboard = true
        keyboardNavigationActive.value = true
        document.body.classList.add('keyboard-navigation')
      }
    }
    
    const handleMouseDown = () => {
      if (isUsingKeyboard) {
        isUsingKeyboard = false
        keyboardNavigationActive.value = false
        document.body.classList.remove('keyboard-navigation')
      }
    }
    
    document.addEventListener('keydown', handleKeyDown)
    document.addEventListener('mousedown', handleMouseDown)
    
    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      document.removeEventListener('mousedown', handleMouseDown)
    }
  }

  const setupOnlineStatusTracking = () => {
    const handleOnline = () => {
      isOnline.value = true
    }
    
    const handleOffline = () => {
      isOnline.value = false
    }
    
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
    
    return () => {
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }

  const setupAccessibilityMonitoring = () => {
    // Monitor for accessibility preference changes
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    const contrastQuery = window.matchMedia('(prefers-contrast: high)')
    
    const handleMotionChange = (e: MediaQueryListEvent) => {
      prefersReducedMotion.value = e.matches
    }
    
    const handleContrastChange = (e: MediaQueryListEvent) => {
      isHighContrast.value = e.matches
    }
    
    motionQuery.addEventListener('change', handleMotionChange)
    contrastQuery.addEventListener('change', handleContrastChange)
    
    return () => {
      motionQuery.removeEventListener('change', handleMotionChange)
      contrastQuery.removeEventListener('change', handleContrastChange)
    }
  }

  const getFormEnhancementConfig = () => {
    return {
      enableRealTimeValidation: shouldEnableFeature('enableRealTimeValidation'),
      enableAutoSave: shouldEnableFeature('enableAutoSave'),
      enableAccessibilityFeatures: shouldEnableFeature('enableAccessibilityFeatures'),
      enablePerformanceOptimizations: shouldEnableFeature('enablePerformanceOptimizations'),
      
      // Validation settings
      validationDebounce: connectionSpeed.value === 'slow' ? 1000 : 300,
      showInlineErrors: true,
      showErrorSummary: screenReaderDetected.value || keyboardNavigationActive.value,
      announceErrors: screenReaderDetected.value,
      
      // Auto-save settings
      autoSaveInterval: connectionSpeed.value === 'slow' ? 60000 : 30000, // 1 min vs 30 sec
      autoSaveOnBlur: true,
      
      // Accessibility settings
      highContrast: isHighContrast.value,
      reducedMotion: prefersReducedMotion.value,
      keyboardNavigation: keyboardNavigationActive.value,
      screenReaderOptimized: screenReaderDetected.value,
      
      // Performance settings
      lazyLoadImages: shouldEnableFeature('enablePerformanceOptimizations'),
      deferNonCriticalJS: connectionSpeed.value === 'slow',
      compressData: connectionSpeed.value === 'slow',
      
      // Touch settings
      touchOptimized: isTouchDevice.value,
      largerTouchTargets: isTouchDevice.value,
      
      // Connection settings
      offlineMode: !isOnline.value,
      slowConnection: connectionSpeed.value === 'slow'
    }
  }

  const applyProgressiveEnhancements = (formElement: HTMLElement) => {
    const config = getFormEnhancementConfig()
    
    // Apply accessibility enhancements
    if (config.enableAccessibilityFeatures) {
      // Add ARIA live regions for error announcements
      if (!document.getElementById('form-errors-live')) {
        const liveRegion = document.createElement('div')
        liveRegion.id = 'form-errors-live'
        liveRegion.setAttribute('aria-live', 'polite')
        liveRegion.setAttribute('aria-atomic', 'true')
        liveRegion.className = 'sr-only'
        document.body.appendChild(liveRegion)
      }
      
      // Enhance form labels and descriptions
      const inputs = formElement.querySelectorAll('input, select, textarea')
      inputs.forEach(input => {
        const label = formElement.querySelector(`label[for="${input.id}"]`)
        if (label && !input.getAttribute('aria-labelledby')) {
          input.setAttribute('aria-labelledby', label.id || `${input.id}-label`)
        }
      })
    }
    
    // Apply performance enhancements
    if (config.enablePerformanceOptimizations) {
      // Lazy load non-critical form elements
      if (supportsIntersectionObserver.value) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const element = entry.target as HTMLElement
              element.classList.add('enhanced')
              observer.unobserve(element)
            }
          })
        })
        
        const nonCriticalElements = formElement.querySelectorAll('[data-lazy-enhance]')
        nonCriticalElements.forEach(el => observer.observe(el))
      }
    }
    
    // Apply touch enhancements
    if (config.touchOptimized) {
      formElement.classList.add('touch-optimized')
      
      // Increase touch target sizes
      if (config.largerTouchTargets) {
        const touchTargets = formElement.querySelectorAll('button, input[type="checkbox"], input[type="radio"]')
        touchTargets.forEach(target => {
          target.classList.add('touch-target-large')
        })
      }
    }
    
    // Apply high contrast enhancements
    if (config.highContrast) {
      formElement.classList.add('high-contrast')
    }
    
    // Apply reduced motion enhancements
    if (config.reducedMotion) {
      formElement.classList.add('reduced-motion')
    }
    
    return config
  }

  const createFallbackForm = (formElement: HTMLElement) => {
    // Create a basic HTML form that works without JavaScript
    const form = formElement.cloneNode(true) as HTMLElement
    
    // Remove JavaScript-dependent attributes
    const jsElements = form.querySelectorAll('[data-js-only]')
    jsElements.forEach(el => el.remove())
    
    // Add basic HTML5 validation
    const inputs = form.querySelectorAll('input, select, textarea')
    inputs.forEach(input => {
      const field = input as HTMLInputElement
      
      // Add required attribute for required fields
      if (field.dataset.required === 'true') {
        field.required = true
      }
      
      // Add pattern validation
      if (field.dataset.pattern) {
        field.pattern = field.dataset.pattern
      }
      
      // Add min/max for numbers
      if (field.type === 'number') {
        if (field.dataset.min) field.min = field.dataset.min
        if (field.dataset.max) field.max = field.dataset.max
      }
    })
    
    return form
  }

  let cleanupFunctions: (() => void)[] = []

  onMounted(() => {
    detectCapabilities()
    
    cleanupFunctions.push(
      setupKeyboardNavigation(),
      setupOnlineStatusTracking(),
      setupAccessibilityMonitoring()
    )
  })

  onUnmounted(() => {
    cleanupFunctions.forEach(cleanup => cleanup())
  })

  return {
    // State
    isJavaScriptEnabled,
    isOnline,
    connectionSpeed,
    supportsIntersectionObserver,
    supportsResizeObserver,
    supportsWebWorkers,
    supportsLocalStorage,
    supportsSessionStorage,
    prefersReducedMotion,
    isHighContrast,
    isTouchDevice,
    screenReaderDetected,
    keyboardNavigationActive,
    enhancementLevel,
    
    // Methods
    shouldEnableFeature,
    detectCapabilities,
    getFormEnhancementConfig,
    applyProgressiveEnhancements,
    createFallbackForm
  }
}