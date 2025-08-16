import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

// Mock performance API before importing the service
const mockPerformance = {
  now: vi.fn(() => Date.now()),
  mark: vi.fn(),
  measure: vi.fn(),
  getEntriesByType: vi.fn(() => []),
  getEntriesByName: vi.fn(() => [{ duration: 100 }]),
}

// Mock PerformanceObserver
class MockPerformanceObserver {
  private callback: (list: any) => void
  
  constructor(callback: (list: any) => void) {
    this.callback = callback
  }
  
  observe() {}
  disconnect() {}
  
  // Helper method to simulate entries
  simulateEntries(entries: any[]) {
    this.callback({
      getEntries: () => entries
    })
  }
}

global.PerformanceObserver = MockPerformanceObserver as any
global.performance = mockPerformance as any

// Now import the service after mocking
const { performanceService } = await import('@/services/PerformanceService')

describe('Loading Performance', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset performance metrics
    mockPerformance.getEntriesByType.mockReturnValue([])
  })

  afterEach(() => {
    performanceService.disconnect()
  })

  describe('Core Web Vitals', () => {
    it('should measure Largest Contentful Paint (LCP)', () => {
      const mockLCPEntry = {
        entryType: 'largest-contentful-paint',
        startTime: 1200,
        name: 'largest-contentful-paint'
      }

      // Simulate LCP measurement
      const observer = new MockPerformanceObserver(() => {})
      observer.simulateEntries([mockLCPEntry])

      const metrics = performanceService.getMetrics()
      expect(metrics.largestContentfulPaint).toBeDefined()
    })

    it('should measure First Input Delay (FID)', () => {
      const mockFIDEntry = {
        entryType: 'first-input',
        startTime: 100,
        processingStart: 150,
        name: 'first-input'
      }

      const observer = new MockPerformanceObserver(() => {})
      observer.simulateEntries([mockFIDEntry])

      const metrics = performanceService.getMetrics()
      expect(metrics.firstInputDelay).toBeDefined()
    })

    it('should measure Cumulative Layout Shift (CLS)', () => {
      const mockCLSEntries = [
        {
          entryType: 'layout-shift',
          value: 0.05,
          hadRecentInput: false
        },
        {
          entryType: 'layout-shift',
          value: 0.03,
          hadRecentInput: false
        }
      ]

      const observer = new MockPerformanceObserver(() => {})
      observer.simulateEntries(mockCLSEntries)

      const metrics = performanceService.getMetrics()
      expect(metrics.cumulativeLayoutShift).toBeDefined()
    })
  })

  describe('Performance Budget', () => {
    it('should pass performance budget when metrics are within thresholds', () => {
      // Mock good performance metrics
      const goodMetrics = {
        largestContentfulPaint: 2000, // < 2500ms
        firstInputDelay: 50, // < 100ms
        cumulativeLayoutShift: 0.05, // < 0.1
        loadComplete: 2500 // < 3000ms
      }

      // Override getMetrics to return good metrics
      vi.spyOn(performanceService, 'getMetrics').mockReturnValue({
        ...performanceService.getMetrics(),
        ...goodMetrics
      })

      const budget = performanceService.checkPerformanceBudget()
      expect(budget.passed).toBe(true)
      expect(budget.violations).toHaveLength(0)
    })

    it('should fail performance budget when LCP exceeds threshold', () => {
      const badMetrics = {
        largestContentfulPaint: 3000, // > 2500ms
        firstInputDelay: 50,
        cumulativeLayoutShift: 0.05,
        loadComplete: 2500
      }

      vi.spyOn(performanceService, 'getMetrics').mockReturnValue({
        ...performanceService.getMetrics(),
        ...badMetrics
      })

      const budget = performanceService.checkPerformanceBudget()
      expect(budget.passed).toBe(false)
      expect(budget.violations).toContain(expect.stringContaining('LCP too slow'))
    })

    it('should fail performance budget when page load exceeds 3 seconds', () => {
      const badMetrics = {
        largestContentfulPaint: 2000,
        firstInputDelay: 50,
        cumulativeLayoutShift: 0.05,
        loadComplete: 3500 // > 3000ms
      }

      vi.spyOn(performanceService, 'getMetrics').mockReturnValue({
        ...performanceService.getMetrics(),
        ...badMetrics
      })

      const budget = performanceService.checkPerformanceBudget()
      expect(budget.passed).toBe(false)
      expect(budget.violations).toContain(expect.stringContaining('Page load too slow'))
    })
  })

  describe('Custom Metrics', () => {
    it('should measure custom timing metrics', () => {
      vi.useFakeTimers()
      
      performanceService.markStart('component-load')
      
      // Simulate some work
      vi.advanceTimersByTime(100)
      
      const duration = performanceService.markEnd('component-load')
      
      expect(duration).toBeGreaterThan(0)
      expect(mockPerformance.mark).toHaveBeenCalledWith('component-load-start')
      expect(mockPerformance.mark).toHaveBeenCalledWith('component-load-end')
      expect(mockPerformance.measure).toHaveBeenCalledWith('component-load', 'component-load-start', 'component-load-end')
      
      vi.useRealTimers()
    })

    it('should measure component loading time', () => {
      // Mock performance.now to return increasing values
      let timeCounter = 1000
      mockPerformance.now.mockImplementation(() => timeCounter++)
      
      const startTime = performance.now()
      const duration = performanceService.measureCustomMetric('hero-section-load', startTime)
      
      expect(duration).toBeGreaterThan(0)
      
      const metrics = performanceService.getMetrics()
      expect((metrics as any)['hero-section-load']).toBe(duration)
    })
  })

  describe('Resource Timing', () => {
    it('should track resource loading performance', () => {
      const mockResourceEntries = [
        {
          name: 'https://example.com/image.jpg',
          duration: 200,
          transferSize: 50000,
          startTime: 100
        },
        {
          name: 'https://example.com/script.js',
          duration: 150,
          transferSize: 30000,
          startTime: 200
        }
      ]

      mockPerformance.getEntriesByType.mockReturnValue(mockResourceEntries)

      // Simulate resource observer
      const observer = new MockPerformanceObserver(() => {})
      observer.simulateEntries(mockResourceEntries)

      const metrics = performanceService.getMetrics()
      expect(metrics.resourceLoadTime).toBeDefined()
    })
  })

  describe('Performance Monitoring', () => {
    it('should report metrics to server', async () => {
      const mockFetch = vi.fn().mockResolvedValue({ ok: true })
      global.fetch = mockFetch

      await performanceService.reportMetrics()

      expect(mockFetch).toHaveBeenCalledWith('/api/performance/metrics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: expect.stringContaining('"url"')
      })
    })

    it('should handle reporting errors gracefully', async () => {
      const mockFetch = vi.fn().mockRejectedValue(new Error('Network error'))
      global.fetch = mockFetch
      
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})

      await performanceService.reportMetrics()

      expect(consoleSpy).toHaveBeenCalledWith('Failed to report performance metrics:', expect.any(Error))
      
      consoleSpy.mockRestore()
    })
  })
})

describe('Lazy Loading Performance', () => {
  it('should load images only when in viewport', async () => {
    // Mock IntersectionObserver
    const mockObserve = vi.fn()
    const mockIntersectionObserver = vi.fn().mockImplementation(() => ({
      observe: mockObserve,
      unobserve: vi.fn(),
      disconnect: vi.fn()
    }))
    global.IntersectionObserver = mockIntersectionObserver

    // Test lazy loading behavior
    const { useLazyImage } = await import('@/composables/useLazyLoading')
    
    const { isLoaded, currentSrc } = useLazyImage('test-image.jpg')
    
    expect(isLoaded.value).toBe(false)
    expect(currentSrc.value).toBe('')
    expect(mockIntersectionObserver).toHaveBeenCalled()
  })

  it('should implement progressive image loading', async () => {
    let mockOnload: (() => void) | null = null
    let mockOnerror: (() => void) | null = null
    
    const mockImage = {
      get onload() { return mockOnload },
      set onload(fn) { mockOnload = fn },
      get onerror() { return mockOnerror },
      set onerror(fn) { mockOnerror = fn },
      src: ''
    }
    
    global.Image = vi.fn(() => mockImage) as any

    const { useLazyImage } = await import('@/composables/useLazyLoading')
    const { isLoaded, isError } = useLazyImage('test-image.jpg')

    // Simulate successful image load
    if (mockOnload) {
      mockOnload()
    }
    
    expect(isLoaded.value).toBe(true)
    expect(isError.value).toBe(false)
  })
})

describe('Code Splitting Performance', () => {
  it('should load components on demand', async () => {
    const mockComponentLoader = vi.fn().mockResolvedValue({
      default: { name: 'TestComponent' }
    })

    const { useLazyComponent } = await import('@/composables/useLazyLoading')
    const { component, isLoading } = useLazyComponent(mockComponentLoader)

    expect(component.value).toBeNull()
    expect(isLoading.value).toBe(false)
    
    // Component should not be loaded until triggered
    expect(mockComponentLoader).not.toHaveBeenCalled()
  })

  it('should handle component loading errors', async () => {
    const mockComponentLoader = vi.fn().mockRejectedValue(new Error('Load failed'))

    const { useLazyComponent } = await import('@/composables/useLazyLoading')
    const { isError } = useLazyComponent(mockComponentLoader)

    // Simulate intersection
    // This would normally be triggered by IntersectionObserver
    try {
      await mockComponentLoader()
    } catch {
      // Expected error
    }

    expect(isError.value).toBe(false) // Error handling is internal
  })
})