import { ref, onMounted, onUnmounted, readonly } from 'vue'
import { bundleAnalyzer } from '@/utils/bundle-analyzer'
import performanceMonitor, { recordCustomMetric, markPerformance, measurePerformance } from '../utils/performance-monitor'

interface PerformanceMetrics {
  loadTime: number
  renderTime: number
  memoryUsage: number
  bundleSize: number
  networkRequests: number
  firstContentfulPaint: number
  largestContentfulPaint: number
  cumulativeLayoutShift: number
  firstInputDelay: number
}

interface PerformanceSession {
  sessionId: string
  startTime: number
  metrics: PerformanceMetrics[]
  userAgent: string
  url: string
  viewport: { width: number; height: number }
  connection: string
}

interface PerformanceThresholds {
  loadTime: number
  renderTime: number
  memoryUsage: number
  bundleSize: number
  firstContentfulPaint: number
  largestContentfulPaint: number
  cumulativeLayoutShift: number
  firstInputDelay: number
}

/**
 * Enhanced Vue composable for comprehensive performance monitoring
 */
export function usePerformanceMonitoring(componentName = 'Unknown') {
  const isMonitoring = ref(false)
  const currentSession = ref(null)
  const metrics = ref([])
  const alerts = ref([])
  const isLoading = ref(false)
  const loadingStartTime = ref(null)
  const performanceData = ref({
    metrics: [],
    recommendations: []
  })

  let performanceObserver = null
  let sessionStartTime = 0
  let vitalsObserver = null

  // Performance thresholds (based on Core Web Vitals)
  const thresholds = {
    loadTime: 3000, // 3 seconds
    renderTime: 2500, // 2.5 seconds (FCP)
    memoryUsage: 50 * 1024 * 1024, // 50MB
    bundleSize: 1024 * 1024, // 1MB
    firstContentfulPaint: 1800, // 1.8 seconds
    largestContentfulPaint: 2500, // 2.5 seconds
    cumulativeLayoutShift: 0.1, // 0.1
    firstInputDelay: 100 // 100ms
  }

  // Track component mount performance
  const mountTracker = performanceMonitor.trackComponent(componentName, 'mount')

  const startMonitoring = () => {
    if (isMonitoring.value) return

    isMonitoring.value = true
    sessionStartTime = performance.now()
    alerts.value = []
    
    // Create new session
    currentSession.value = {
      sessionId: generateSessionId(),
      startTime: Date.now(),
      metrics: [],
      userAgent: navigator.userAgent,
      url: window.location.href,
      viewport: {
        width: window.innerWidth,
        height: window.innerHeight
      },
      connection: getConnectionType()
    }

    // Set up performance observer for navigation and resources
    if ('PerformanceObserver' in window) {
      performanceObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        processPerformanceEntries(entries)
      })

      performanceObserver.observe({ 
        entryTypes: ['navigation', 'resource', 'measure', 'paint'] 
      })

      // Set up Web Vitals observer
      vitalsObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        processWebVitals(entries)
      })

      try {
        vitalsObserver.observe({ 
          entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift'] 
        })
      } catch (e) {
        // Some browsers don't support all entry types
        console.warn('Some performance entry types not supported:', e)
      }
    }

    // Collect initial metrics
    setTimeout(() => {
      collectMetrics()
    }, 1000)

    // Collect metrics periodically
    const interval = setInterval(() => {
      if (isMonitoring.value) {
        collectMetrics()
      } else {
        clearInterval(interval)
      }
    }, 5000)
  }

  const stopMonitoring = () => {
    if (!isMonitoring.value) return

    isMonitoring.value = false
    
    if (performanceObserver) {
      performanceObserver.disconnect()
      performanceObserver = null
    }

    if (vitalsObserver) {
      vitalsObserver.disconnect()
      vitalsObserver = null
    }

    // Send final session data
    if (currentSession.value) {
      sendSessionData(currentSession.value)
    }
  }

  const collectMetrics = () => {
    const navigation = performance.getEntriesByType('navigation')[0]
    const resources = performance.getEntriesByType('resource')
    
    const newMetrics = {
      loadTime: navigation ? navigation.loadEventEnd - navigation.navigationStart : 0,
      renderTime: getFirstContentfulPaint(),
      memoryUsage: getMemoryUsage(),
      bundleSize: bundleAnalyzer.getTotalBundleSize(),
      networkRequests: resources.length,
      firstContentfulPaint: getFirstContentfulPaint(),
      largestContentfulPaint: getLargestContentfulPaint(),
      cumulativeLayoutShift: getCumulativeLayoutShift(),
      firstInputDelay: getFirstInputDelay()
    }

    metrics.value.push(newMetrics)
    
    if (currentSession.value) {
      currentSession.value.metrics.push(newMetrics)
    }

    // Check thresholds and generate alerts
    checkPerformanceThresholds(newMetrics)
  }

  const processPerformanceEntries = (entries) => {
    entries.forEach(entry => {
      if (entry.entryType === 'navigation') {
        const navEntry = entry
        if (process.env.NODE_ENV === 'development') {
          console.log('Navigation timing:', {
            domContentLoaded: navEntry.domContentLoadedEventEnd - navEntry.domContentLoadedEventStart,
            loadComplete: navEntry.loadEventEnd - navEntry.loadEventStart,
            firstByte: navEntry.responseStart - navEntry.requestStart
          })
        }
      } else if (entry.entryType === 'resource') {
        const resourceEntry = entry
        if (resourceEntry.name.includes('/build/') || resourceEntry.name.includes('/assets/')) {
          bundleAnalyzer.trackBundle(
            extractBundleName(resourceEntry.name),
            {
              name: extractBundleName(resourceEntry.name),
              size: resourceEntry.transferSize || resourceEntry.encodedBodySize,
              loadTime: resourceEntry.duration,
              isLoaded: true,
              isPreloaded: false
            }
          )
        }
      }
    })
  }

  const processWebVitals = (entries) => {
    entries.forEach(entry => {
      if (entry.entryType === 'largest-contentful-paint') {
        const lcp = entry.startTime
        if (lcp > thresholds.largestContentfulPaint) {
          addAlert(`LCP is ${lcp.toFixed(0)}ms (threshold: ${thresholds.largestContentfulPaint}ms)`)
        }
      } else if (entry.entryType === 'first-input') {
        const fid = entry.processingStart - entry.startTime
        if (fid > thresholds.firstInputDelay) {
          addAlert(`FID is ${fid.toFixed(0)}ms (threshold: ${thresholds.firstInputDelay}ms)`)
        }
      } else if (entry.entryType === 'layout-shift') {
        const cls = entry.value
        if (cls > thresholds.cumulativeLayoutShift) {
          addAlert(`CLS is ${cls.toFixed(3)} (threshold: ${thresholds.cumulativeLayoutShift})`)
        }
      }
    })
  }

  const checkPerformanceThresholds = (metrics) => {
    if (metrics.loadTime > thresholds.loadTime) {
      addAlert(`Load time is ${metrics.loadTime.toFixed(0)}ms (threshold: ${thresholds.loadTime}ms)`)
    }
    
    if (metrics.firstContentfulPaint > thresholds.firstContentfulPaint) {
      addAlert(`FCP is ${metrics.firstContentfulPaint.toFixed(0)}ms (threshold: ${thresholds.firstContentfulPaint}ms)`)
    }
    
    if (metrics.bundleSize > thresholds.bundleSize) {
      addAlert(`Bundle size is ${(metrics.bundleSize / 1024).toFixed(0)}KB (threshold: ${(thresholds.bundleSize / 1024).toFixed(0)}KB)`)
    }
    
    if (metrics.memoryUsage > thresholds.memoryUsage) {
      addAlert(`Memory usage is ${(metrics.memoryUsage / 1024 / 1024).toFixed(1)}MB (threshold: ${(thresholds.memoryUsage / 1024 / 1024).toFixed(1)}MB)`)
    }
  }

  const addAlert = (message) => {
    if (!alerts.value.includes(message)) {
      alerts.value.push(message)
      
      if (process.env.NODE_ENV === 'development') {
        console.warn('Performance Alert:', message)
      }
    }
  }

  // Helper functions
  const getFirstContentfulPaint = () => {
    const paintEntries = performance.getEntriesByType('paint')
    const fcp = paintEntries.find(entry => entry.name === 'first-contentful-paint')
    return fcp?.startTime || 0
  }

  const getLargestContentfulPaint = () => {
    const lcpEntries = performance.getEntriesByType('largest-contentful-paint')
    const lcp = lcpEntries[lcpEntries.length - 1]
    return lcp?.startTime || 0
  }

  const getCumulativeLayoutShift = () => {
    const clsEntries = performance.getEntriesByType('layout-shift')
    return clsEntries.reduce((cls, entry) => cls + entry.value, 0)
  }

  const getFirstInputDelay = () => {
    const fidEntries = performance.getEntriesByType('first-input')
    const fid = fidEntries[0]
    return fid ? fid.processingStart - fid.startTime : 0
  }

  const getMemoryUsage = () => {
    return performance.memory?.usedJSHeapSize || 0
  }

  const getConnectionType = () => {
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection
    return connection ? `${connection.effectiveType} (${connection.downlink}Mbps)` : 'unknown'
  }

  const extractBundleName = (url) => {
    const parts = url.split('/')
    const filename = parts[parts.length - 1]
    return filename.split('-')[0] || filename.split('.')[0]
  }

  const generateSessionId = () => {
    return Math.random().toString(36).substr(2, 9) + Date.now().toString(36)
  }

  const sendSessionData = async (session) => {
    try {
      await fetch('/api/performance/sessions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify(session)
      })
    } catch (error) {
      console.error('Failed to send performance data:', error)
    }
  }

  const getAverageMetrics = () => {
    if (metrics.value.length === 0) return null

    const totals = metrics.value.reduce((acc, metric) => ({
      loadTime: acc.loadTime + metric.loadTime,
      renderTime: acc.renderTime + metric.renderTime,
      memoryUsage: acc.memoryUsage + metric.memoryUsage,
      bundleSize: acc.bundleSize + metric.bundleSize,
      networkRequests: acc.networkRequests + metric.networkRequests,
      firstContentfulPaint: acc.firstContentfulPaint + metric.firstContentfulPaint,
      largestContentfulPaint: acc.largestContentfulPaint + metric.largestContentfulPaint,
      cumulativeLayoutShift: acc.cumulativeLayoutShift + metric.cumulativeLayoutShift,
      firstInputDelay: acc.firstInputDelay + metric.firstInputDelay
    }), {
      loadTime: 0,
      renderTime: 0,
      memoryUsage: 0,
      bundleSize: 0,
      networkRequests: 0,
      firstContentfulPaint: 0,
      largestContentfulPaint: 0,
      cumulativeLayoutShift: 0,
      firstInputDelay: 0
    })

    const count = metrics.value.length
    return {
      loadTime: totals.loadTime / count,
      renderTime: totals.renderTime / count,
      memoryUsage: totals.memoryUsage / count,
      bundleSize: totals.bundleSize / count,
      networkRequests: totals.networkRequests / count,
      firstContentfulPaint: totals.firstContentfulPaint / count,
      largestContentfulPaint: totals.largestContentfulPaint / count,
      cumulativeLayoutShift: totals.cumulativeLayoutShift / count,
      firstInputDelay: totals.firstInputDelay / count
    }
  }

  const getPerformanceScore = () => {
    const avg = getAverageMetrics()
    if (!avg) return 0

    let score = 100

    // Deduct points based on thresholds
    if (avg.loadTime > thresholds.loadTime) score -= 20
    if (avg.firstContentfulPaint > thresholds.firstContentfulPaint) score -= 15
    if (avg.largestContentfulPaint > thresholds.largestContentfulPaint) score -= 15
    if (avg.cumulativeLayoutShift > thresholds.cumulativeLayoutShift) score -= 15
    if (avg.firstInputDelay > thresholds.firstInputDelay) score -= 10
    if (avg.bundleSize > thresholds.bundleSize) score -= 15
    if (avg.memoryUsage > thresholds.memoryUsage) score -= 10

    return Math.max(0, score)
  }

  const generateReport = () => {
    return bundleAnalyzer.generateReport()
  }

  // Legacy component tracking methods
  const startLoading = (operation = 'default') => {
    isLoading.value = true
    loadingStartTime.value = performance.now()
    
    recordCustomMetric('LoadingStart', 0, {
      component: componentName,
      operation
    })
  }

  const endLoading = (operation = 'default', success = true, error = null) => {
    if (loadingStartTime.value) {
      const duration = performance.now() - loadingStartTime.value
      
      recordCustomMetric('LoadingEnd', duration, {
        component: componentName,
        operation,
        success,
        error: error?.message
      })
      
      loadingStartTime.value = null
    }
    
    isLoading.value = false
  }

  const trackApiCall = async (apiCall, metadata = {}) => {
    const tracker = performanceMonitor.trackAsyncOperation('ApiCall', {
      component: componentName,
      ...metadata
    })

    try {
      const result = await apiCall()
      tracker.end(true)
      return result
    } catch (error) {
      tracker.end(false, error)
      throw error
    }
  }

  const trackInteraction = (interactionType, metadata = {}) => {
    recordCustomMetric('UserInteraction', 0, {
      component: componentName,
      interaction: interactionType,
      timestamp: Date.now(),
      ...metadata
    })
  }

  const trackFormSubmission = async (submitFunction, formName = 'unknown') => {
    const tracker = performanceMonitor.trackAsyncOperation('FormSubmission', {
      component: componentName,
      form: formName
    })

    startLoading('form-submission')

    try {
      const result = await submitFunction()
      tracker.end(true)
      endLoading('form-submission', true)
      return result
    } catch (error) {
      tracker.end(false, error)
      endLoading('form-submission', false, error)
      throw error
    }
  }

  const trackSearch = async (searchFunction, query, metadata = {}) => {
    const tracker = performanceMonitor.trackAsyncOperation('Search', {
      component: componentName,
      query: query.substring(0, 50), // Limit query length for privacy
      queryLength: query.length,
      ...metadata
    })

    try {
      const result = await searchFunction()
      tracker.end(true)
      return result
    } catch (error) {
      tracker.end(false, error)
      throw error
    }
  }

  const trackNavigation = (destination, metadata = {}) => {
    recordCustomMetric('Navigation', 0, {
      component: componentName,
      destination,
      timestamp: Date.now(),
      ...metadata
    })
  }

  const getPerformanceRecommendations = async () => {
    try {
      const response = await fetch('/api/performance/recommendations')
      const data = await response.json()
      
      if (data.success) {
        performanceData.value.recommendations = data.data.recommendations
      }
    } catch (error) {
      console.warn('Failed to load performance recommendations:', error)
    }
  }

  const mark = (name) => {
    markPerformance(`${componentName}-${name}`)
  }

  const measure = (name, startMark, endMark) => {
    measurePerformance(
      `${componentName}-${name}`,
      `${componentName}-${startMark}`,
      `${componentName}-${endMark}`
    )
  }

  const trackMetric = (name, value, metadata = {}) => {
    recordCustomMetric(name, value, {
      component: componentName,
      ...metadata
    })
  }

  onMounted(() => {
    mountTracker.end()
    startMonitoring()
  })

  onUnmounted(() => {
    recordCustomMetric('ComponentUnmount', 0, {
      component: componentName,
      timestamp: Date.now()
    })
    stopMonitoring()
  })

  return {
    // Enhanced monitoring state
    isMonitoring: readonly(isMonitoring),
    currentSession: readonly(currentSession),
    metrics: readonly(metrics),
    alerts: readonly(alerts),
    thresholds: readonly(ref(thresholds)),
    
    // Legacy state
    isLoading,
    performanceData,
    
    // Enhanced monitoring methods
    startMonitoring,
    stopMonitoring,
    collectMetrics,
    getAverageMetrics,
    getPerformanceScore,
    generateReport,
    
    // Legacy loading tracking
    startLoading,
    endLoading,
    
    // Legacy operation tracking
    trackApiCall,
    trackInteraction,
    trackFormSubmission,
    trackSearch,
    trackNavigation,
    trackMetric,
    
    // Legacy performance timing
    mark,
    measure,
    
    // Legacy data fetching
    getPerformanceRecommendations
  }
}

/**
 * Composable for page-level performance monitoring
 */
export function usePagePerformance(pageName) {
  const pageMetrics = ref({})
  const isOptimized = ref(false)

  onMounted(async () => {
    // Track page load
    recordCustomMetric('PageLoad', 0, {
      page: pageName,
      url: window.location.href,
      timestamp: Date.now()
    })

    // Load page-specific performance data
    await loadPageMetrics()
  })

  const loadPageMetrics = async () => {
    try {
      const response = await fetch(`/api/performance/page?url=${encodeURIComponent(window.location.pathname)}`)
      const data = await response.json()
      
      if (data.success) {
        pageMetrics.value = data.data
      }
    } catch (error) {
      console.warn('Failed to load page performance metrics:', error)
    }
  }

  const optimizePage = () => {
    // Apply page-level optimizations
    const images = document.querySelectorAll('img:not([loading])')
    images.forEach(img => {
      img.loading = 'lazy'
    })

    // Add intersection observer for lazy loading
    if ('IntersectionObserver' in window) {
      const lazyElements = document.querySelectorAll('[data-lazy]')
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('loaded')
            observer.unobserve(entry.target)
          }
        })
      })

      lazyElements.forEach(el => observer.observe(el))
    }

    isOptimized.value = true
    
    recordCustomMetric('PageOptimization', 0, {
      page: pageName,
      optimizations: ['lazy-loading', 'intersection-observer']
    })
  }

  return {
    pageMetrics,
    isOptimized,
    loadPageMetrics,
    optimizePage
  }
}

/**
 * Composable for real-time performance monitoring
 */
export function useRealTimePerformance() {
  const realTimeMetrics = ref([])
  const isMonitoring = ref(false)
  let monitoringInterval = null

  const startMonitoring = () => {
    if (isMonitoring.value) return

    isMonitoring.value = true
    
    // Update metrics every 10 seconds
    monitoringInterval = setInterval(async () => {
      try {
        const response = await fetch('/api/performance/real-time')
        const data = await response.json()
        
        if (data.success) {
          realTimeMetrics.value = data.data
        }
      } catch (error) {
        console.warn('Failed to fetch real-time metrics:', error)
      }
    }, 10000)
  }

  const stopMonitoring = () => {
    if (monitoringInterval) {
      clearInterval(monitoringInterval)
      monitoringInterval = null
    }
    isMonitoring.value = false
  }

  onUnmounted(() => {
    stopMonitoring()
  })

  return {
    realTimeMetrics,
    isMonitoring,
    startMonitoring,
    stopMonitoring
  }
}