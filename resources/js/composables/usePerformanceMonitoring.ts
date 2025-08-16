import { ref, onMounted, onUnmounted, readonly } from 'vue'

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
  const currentSession = ref<PerformanceSession | null>(null)
  const metrics = ref<PerformanceMetrics[]>([])
  const alerts = ref<string[]>([])
  const isLoading = ref(false)
  const loadingStartTime = ref<number | null>(null)
  const performanceData = ref({
    metrics: [],
    recommendations: []
  })

  let performanceObserver: PerformanceObserver | null = null
  let sessionStartTime = 0
  let vitalsObserver: PerformanceObserver | null = null

  // Performance thresholds (based on Core Web Vitals)
  const thresholds: PerformanceThresholds = {
    loadTime: 3000, // 3 seconds
    renderTime: 2500, // 2.5 seconds (FCP)
    memoryUsage: 50 * 1024 * 1024, // 50MB
    bundleSize: 1024 * 1024, // 1MB
    firstContentfulPaint: 1800, // 1.8 seconds
    largestContentfulPaint: 2500, // 2.5 seconds
    cumulativeLayoutShift: 0.1, // 0.1
    firstInputDelay: 100 // 100ms
  }

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
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
    const resources = performance.getEntriesByType('resource')
    
    const newMetrics: PerformanceMetrics = {
      loadTime: navigation ? navigation.loadEventEnd - navigation.navigationStart : 0,
      renderTime: getFirstContentfulPaint(),
      memoryUsage: getMemoryUsage(),
      bundleSize: 0, // Would need bundle analyzer
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

  const processPerformanceEntries = (entries: PerformanceEntry[]) => {
    entries.forEach(entry => {
      if (entry.entryType === 'navigation') {
        const navEntry = entry as PerformanceNavigationTiming
        if (process.env.NODE_ENV === 'development') {
          console.log('Navigation timing:', {
            domContentLoaded: navEntry.domContentLoadedEventEnd - navEntry.domContentLoadedEventStart,
            loadComplete: navEntry.loadEventEnd - navEntry.loadEventStart,
            firstByte: navEntry.responseStart - navEntry.requestStart
          })
        }
      }
    })
  }

  const processWebVitals = (entries: PerformanceEntry[]) => {
    entries.forEach(entry => {
      if (entry.entryType === 'largest-contentful-paint') {
        const lcp = entry.startTime
        if (lcp > thresholds.largestContentfulPaint) {
          addAlert(`LCP is ${lcp.toFixed(0)}ms (threshold: ${thresholds.largestContentfulPaint}ms)`)
        }
      } else if (entry.entryType === 'first-input') {
        const fid = (entry as any).processingStart - entry.startTime
        if (fid > thresholds.firstInputDelay) {
          addAlert(`FID is ${fid.toFixed(0)}ms (threshold: ${thresholds.firstInputDelay}ms)`)
        }
      } else if (entry.entryType === 'layout-shift') {
        const cls = (entry as any).value
        if (cls > thresholds.cumulativeLayoutShift) {
          addAlert(`CLS is ${cls.toFixed(3)} (threshold: ${thresholds.cumulativeLayoutShift})`)
        }
      }
    })
  }

  const checkPerformanceThresholds = (metrics: PerformanceMetrics) => {
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

  const addAlert = (message: string) => {
    if (!alerts.value.includes(message)) {
      alerts.value.push(message)
      
      if (process.env.NODE_ENV === 'development') {
        console.warn('Performance Alert:', message)
      }
    }
  }

  // Helper functions
  const getFirstContentfulPaint = (): number => {
    const paintEntries = performance.getEntriesByType('paint')
    const fcp = paintEntries.find(entry => entry.name === 'first-contentful-paint')
    return fcp?.startTime || 0
  }

  const getLargestContentfulPaint = (): number => {
    const lcpEntries = performance.getEntriesByType('largest-contentful-paint')
    const lcp = lcpEntries[lcpEntries.length - 1]
    return lcp?.startTime || 0
  }

  const getCumulativeLayoutShift = (): number => {
    const clsEntries = performance.getEntriesByType('layout-shift')
    return clsEntries.reduce((cls, entry) => cls + (entry as any).value, 0)
  }

  const getFirstInputDelay = (): number => {
    const fidEntries = performance.getEntriesByType('first-input')
    const fid = fidEntries[0] as any
    return fid ? fid.processingStart - fid.startTime : 0
  }

  const getMemoryUsage = (): number => {
    return (performance as any).memory?.usedJSHeapSize || 0
  }

  const getConnectionType = (): string => {
    const connection = (navigator as any).connection || (navigator as any).mozConnection || (navigator as any).webkitConnection
    return connection ? `${connection.effectiveType} (${connection.downlink}Mbps)` : 'unknown'
  }

  const generateSessionId = (): string => {
    return Math.random().toString(36).substr(2, 9) + Date.now().toString(36)
  }

  const sendSessionData = async (session: PerformanceSession) => {
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

  const getAverageMetrics = (): PerformanceMetrics | null => {
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

  const startLoading = () => {
    isLoading.value = true
    loadingStartTime.value = performance.now()
  }

  const endLoading = () => {
    isLoading.value = false
    loadingStartTime.value = null
  }

  onMounted(() => {
    startMonitoring()
  })

  onUnmounted(() => {
    stopMonitoring()
  })

  return {
    isMonitoring: readonly(isMonitoring),
    currentSession: readonly(currentSession),
    metrics: readonly(metrics),
    alerts: readonly(alerts),
    isLoading,
    performanceData,
    startMonitoring,
    stopMonitoring,
    collectMetrics,
    getAverageMetrics,
    startLoading,
    endLoading
  }
}