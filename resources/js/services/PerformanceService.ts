export interface PerformanceMetrics {
  // Core Web Vitals
  firstContentfulPaint?: number
  largestContentfulPaint?: number
  firstInputDelay?: number
  cumulativeLayoutShift?: number
  
  // Additional metrics
  timeToInteractive?: number
  totalBlockingTime?: number
  speedIndex?: number
  
  // Custom metrics
  domContentLoaded?: number
  loadComplete?: number
  resourceLoadTime?: number
  
  // Page info
  url: string
  userAgent: string
  timestamp: number
}

export interface ResourceTiming {
  name: string
  duration: number
  size?: number
  type: string
  startTime: number
  endTime: number
}

class PerformanceService {
  private metrics: PerformanceMetrics = {
    url: window.location.href,
    userAgent: navigator.userAgent,
    timestamp: Date.now()
  }

  private observer?: PerformanceObserver
  private resourceObserver?: PerformanceObserver

  constructor() {
    this.initializeObservers()
    this.measureBasicMetrics()
  }

  private initializeObservers() {
    // Observe Core Web Vitals
    if ('PerformanceObserver' in window) {
      try {
        // LCP Observer
        this.observer = new PerformanceObserver((list) => {
          const entries = list.getEntries()
          entries.forEach((entry) => {
            if (entry.entryType === 'largest-contentful-paint') {
              this.metrics.largestContentfulPaint = entry.startTime
            }
            if (entry.entryType === 'first-input') {
              this.metrics.firstInputDelay = (entry as any).processingStart - entry.startTime
            }
            if (entry.entryType === 'layout-shift' && !(entry as any).hadRecentInput) {
              this.metrics.cumulativeLayoutShift = (this.metrics.cumulativeLayoutShift || 0) + (entry as any).value
            }
          })
        })

        this.observer.observe({ entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift'] })

        // Resource timing observer
        this.resourceObserver = new PerformanceObserver((list) => {
          const entries = list.getEntries()
          this.processResourceEntries(entries as PerformanceResourceTiming[])
        })

        this.resourceObserver.observe({ entryTypes: ['resource'] })
      } catch (error) {
        console.warn('Performance Observer not supported:', error)
      }
    }
  }

  private measureBasicMetrics() {
    // Wait for page load
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        this.metrics.domContentLoaded = performance.now()
      })
    } else {
      this.metrics.domContentLoaded = performance.now()
    }

    window.addEventListener('load', () => {
      this.metrics.loadComplete = performance.now()
      this.measureNavigationTiming()
    })
  }

  private measureNavigationTiming() {
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
    
    if (navigation) {
      this.metrics.firstContentfulPaint = this.getFirstContentfulPaint()
      this.metrics.timeToInteractive = this.estimateTimeToInteractive()
    }
  }

  private getFirstContentfulPaint(): number | undefined {
    const paintEntries = performance.getEntriesByType('paint')
    const fcpEntry = paintEntries.find(entry => entry.name === 'first-contentful-paint')
    return fcpEntry?.startTime
  }

  private estimateTimeToInteractive(): number | undefined {
    // Simplified TTI estimation
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
    if (navigation) {
      return navigation.domInteractive - navigation.navigationStart
    }
    return undefined
  }

  private processResourceEntries(entries: PerformanceResourceTiming[]) {
    const resources: ResourceTiming[] = entries.map(entry => ({
      name: entry.name,
      duration: entry.duration,
      size: entry.transferSize,
      type: this.getResourceType(entry.name),
      startTime: entry.startTime,
      endTime: entry.startTime + entry.duration
    }))

    // Calculate average resource load time
    const totalDuration = resources.reduce((sum, resource) => sum + resource.duration, 0)
    this.metrics.resourceLoadTime = totalDuration / resources.length
  }

  private getResourceType(url: string): string {
    if (url.includes('.js')) return 'script'
    if (url.includes('.css')) return 'stylesheet'
    if (url.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i)) return 'image'
    if (url.match(/\.(woff|woff2|ttf|eot)$/i)) return 'font'
    return 'other'
  }

  // Public methods
  public getMetrics(): PerformanceMetrics {
    return { ...this.metrics }
  }

  public measureCustomMetric(name: string, startTime?: number): number {
    const endTime = performance.now()
    const duration = startTime ? endTime - startTime : endTime
    
    // Store custom metric
    ;(this.metrics as any)[name] = duration
    
    return duration
  }

  public markStart(name: string): void {
    performance.mark(`${name}-start`)
  }

  public markEnd(name: string): number {
    performance.mark(`${name}-end`)
    performance.measure(name, `${name}-start`, `${name}-end`)
    
    const measure = performance.getEntriesByName(name, 'measure')[0]
    return measure.duration
  }

  public async reportMetrics(): Promise<void> {
    // Wait a bit for all metrics to be collected
    await new Promise(resolve => setTimeout(resolve, 1000))

    const finalMetrics = this.getMetrics()
    
    try {
      await fetch('/api/performance/metrics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(finalMetrics)
      })
    } catch (error) {
      console.warn('Failed to report performance metrics:', error)
    }
  }

  public checkPerformanceBudget(): {
    passed: boolean
    violations: string[]
  } {
    const violations: string[] = []
    
    // Check Core Web Vitals thresholds
    if (this.metrics.largestContentfulPaint && this.metrics.largestContentfulPaint > 2500) {
      violations.push(`LCP too slow: ${this.metrics.largestContentfulPaint}ms (should be < 2500ms)`)
    }
    
    if (this.metrics.firstInputDelay && this.metrics.firstInputDelay > 100) {
      violations.push(`FID too slow: ${this.metrics.firstInputDelay}ms (should be < 100ms)`)
    }
    
    if (this.metrics.cumulativeLayoutShift && this.metrics.cumulativeLayoutShift > 0.1) {
      violations.push(`CLS too high: ${this.metrics.cumulativeLayoutShift} (should be < 0.1)`)
    }
    
    if (this.metrics.loadComplete && this.metrics.loadComplete > 3000) {
      violations.push(`Page load too slow: ${this.metrics.loadComplete}ms (should be < 3000ms)`)
    }

    return {
      passed: violations.length === 0,
      violations
    }
  }

  public disconnect(): void {
    this.observer?.disconnect()
    this.resourceObserver?.disconnect()
  }
}

// Singleton instance
export const performanceService = new PerformanceService()

// Auto-report metrics on page unload
window.addEventListener('beforeunload', () => {
  performanceService.reportMetrics()
})

export default PerformanceService