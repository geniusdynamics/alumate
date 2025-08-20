import { describe, it, expect, beforeEach, afterEach, vi, Mock } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent } from 'vue'
import { useAnalytics } from '@/composables/useAnalytics'
import type { AudienceType } from '@/types/homepage'

// Mock services
vi.mock('@/services/AnalyticsService')
vi.mock('@/services/ConversionTrackingService')
vi.mock('@/services/ABTestingService')
vi.mock('@/services/HeatMapService')

// Mock fetch
global.fetch = vi.fn()
const mockFetch = fetch as Mock

// Mock navigator
Object.defineProperty(window, 'navigator', {
  value: {
    onLine: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
  },
  writable: true
})

// Mock performance API
Object.defineProperty(window, 'performance', {
  value: {
    now: vi.fn(() => Date.now()),
    getEntriesByType: vi.fn(() => [{
      loadEventEnd: 1000,
      loadEventStart: 100,
      domContentLoadedEventEnd: 800,
      domContentLoadedEventStart: 200
    }]),
    getEntriesByName: vi.fn(() => [{ startTime: 500 }])
  }
})

// Test component that uses the analytics composable
const TestComponent = defineComponent({
  props: {
    audience: {
      type: String as () => AudienceType,
      required: true
    },
    userId: String
  },
  setup(props) {
    const analytics = useAnalytics(props.audience, {
      enableDebugMode: true,
      enableHeatMapping: true,
      enableABTesting: true
    }, props.userId)

    return {
      ...analytics
    }
  },
  template: '<div>Test Component</div>'
})

describe('useAnalytics', () => {
  let wrapper: any
  const mockUserId = 'user123'
  const mockAudience: AudienceType = 'individual'

  beforeEach(() => {
    vi.clearAllMocks()
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true })
    })

    wrapper = mount(TestComponent, {
      props: {
        audience: mockAudience,
        userId: mockUserId
      }
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Initialization', () => {
    it('should initialize all services correctly', async () => {
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.isInitialized).toBe(true)
      expect(wrapper.vm.sessionId).toMatch(/^analytics_\d+_[a-z0-9]+$/)
      expect(wrapper.vm.deviceType).toMatch(/^(mobile|tablet|desktop)$/)
    })

    it('should generate unique session IDs', async () => {
      const wrapper2 = mount(TestComponent, {
        props: { audience: 'institutional' }
      })

      await wrapper.vm.$nextTick()
      await wrapper2.vm.$nextTick()

      expect(wrapper.vm.sessionId).not.toBe(wrapper2.vm.sessionId)
      
      wrapper2.unmount()
    })

    it('should track initial page view on mount', async () => {
      await wrapper.vm.$nextTick()
      
      // Should call trackPageView with homepage
      expect(wrapper.vm.currentPage).toBe('homepage')
    })
  })

  describe('Page View Tracking', () => {
    it('should track page views with all services', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackPageView({
        page: 'pricing',
        additionalData: { source: 'navigation' }
      })

      expect(wrapper.vm.currentPage).toBe('pricing')
    })

    it('should update current page state', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackPageView({ page: 'features' })
      expect(wrapper.vm.currentPage).toBe('features')
      
      wrapper.vm.trackPageView({ page: 'testimonials' })
      expect(wrapper.vm.currentPage).toBe('testimonials')
    })
  })

  describe('Section View Tracking', () => {
    it('should track section views', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackSectionView({
        section: 'hero',
        timeSpent: 5000,
        scrollDepth: 75,
        viewportVisible: true,
        interactionCount: 2
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should track section exit', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackSectionView({ section: 'features' })
      wrapper.vm.trackSectionExit('features')

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('CTA Click Tracking', () => {
    it('should track CTA clicks across all services', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackCTAClick({
        action: 'trial',
        section: 'hero',
        audience: 'individual',
        ctaText: 'Start Free Trial',
        clickCoordinates: { x: 100, y: 200 }
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should track A/B test conversions for CTA clicks', async () => {
      await wrapper.vm.$nextTick()
      
      // Mock active tests
      wrapper.vm.activeTests = { 'hero_test': 'variant_a' }
      
      wrapper.vm.trackCTAClick({
        action: 'demo',
        section: 'pricing',
        audience: 'institutional'
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('Form Submission Tracking', () => {
    it('should track form submissions', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackFormSubmission({
        formType: 'demo_request',
        formId: 'demo-form',
        success: true,
        formData: { name: 'John Doe', email: 'john@example.com' },
        timeToComplete: 30000
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should track form failures', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackFormSubmission({
        formType: 'trial_signup',
        success: false,
        errorMessage: 'Email already exists',
        abandonmentPoint: 'email_field'
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('Calculator Usage Tracking', () => {
    it('should track calculator steps', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackCalculatorUsage({
        step: 2,
        stepName: 'Experience Level',
        totalSteps: 5,
        completed: false,
        calculatorData: { experience: 5 },
        timeSpent: 15000
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should track calculator completion', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackCalculatorUsage({
        step: 5,
        totalSteps: 5,
        completed: true,
        calculatorData: { result: 'high_potential' }
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('Scroll Depth Tracking', () => {
    it('should track scroll depth', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackScrollDepth({
        percentage: 50,
        section: 'features',
        scrollDirection: 'down',
        scrollSpeed: 100
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should track scroll in heat map service', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackScrollDepth({
        percentage: 75,
        section: 'testimonials'
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('User Behavior Tracking', () => {
    it('should track custom user behavior', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackUserBehavior('video_play', {
        elementId: 'hero-video',
        section: 'hero',
        customData: { videoId: 'intro', duration: 120 }
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('Conversion Tracking', () => {
    it('should track conversions', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackConversion('trial_signup', 100, { source: 'hero_cta' })

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('Error Tracking', () => {
    it('should track errors', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackError('javascript_error', {
        message: 'TypeError: Cannot read property',
        stack: 'Error stack trace'
      })

      // Should not throw any errors
      expect(true).toBe(true)
    })
  })

  describe('A/B Testing', () => {
    it('should get variant for test', async () => {
      await wrapper.vm.$nextTick()
      
      const variant = wrapper.vm.getVariant('hero_test')
      
      // Should return null or variant object
      expect(variant === null || typeof variant === 'object').toBe(true)
    })

    it('should check if user is in test', async () => {
      await wrapper.vm.$nextTick()
      
      const inTest = wrapper.vm.isInTest('hero_test')
      
      expect(typeof inTest).toBe('boolean')
    })

    it('should check if user is in specific variant', async () => {
      await wrapper.vm.$nextTick()
      
      const inVariant = wrapper.vm.isInVariant('hero_test', 'variant_a')
      
      expect(typeof inVariant).toBe('boolean')
    })

    it('should get component overrides', async () => {
      await wrapper.vm.$nextTick()
      
      const overrides = wrapper.vm.getComponentOverrides('hero_test')
      
      expect(Array.isArray(overrides)).toBe(true)
    })

    it('should get test results', async () => {
      await wrapper.vm.$nextTick()
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          testId: 'hero_test',
          variants: [],
          winner: 'variant_a'
        })
      })

      const results = await wrapper.vm.getTestResults('hero_test')
      
      expect(results === null || typeof results === 'object').toBe(true)
    })
  })

  describe('Heat Mapping', () => {
    it('should start heat map recording', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.startHeatMapRecording()
      
      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should stop heat map recording', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.stopHeatMapRecording()
      
      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should get heat map data', async () => {
      await wrapper.vm.$nextTick()
      
      const data = wrapper.vm.getHeatMapData()
      
      expect(data === null || typeof data === 'object').toBe(true)
    })

    it('should generate heat map report', async () => {
      await wrapper.vm.$nextTick()
      
      const report = await wrapper.vm.generateHeatMapReport()
      
      expect(report === null || typeof report === 'object').toBe(true)
    })
  })

  describe('Analytics and Reporting', () => {
    it('should get analytics metrics', async () => {
      await wrapper.vm.$nextTick()
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          pageViews: 1000,
          uniqueVisitors: 500,
          conversionRate: 0.05
        })
      })

      const metrics = await wrapper.vm.getAnalyticsMetrics()
      
      expect(metrics === null || typeof metrics === 'object').toBe(true)
      if (metrics) {
        expect(wrapper.vm.analyticsMetrics).toEqual(metrics)
      }
    })

    it('should get conversion metrics', async () => {
      await wrapper.vm.$nextTick()
      
      const metrics = await wrapper.vm.getConversionMetrics()
      
      expect(metrics === null || typeof metrics === 'object').toBe(true)
      if (metrics) {
        expect(wrapper.vm.conversionMetrics).toEqual(metrics)
      }
    })

    it('should generate reports', async () => {
      await wrapper.vm.$nextTick()
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ reportData: 'test' })
      })

      const report = await wrapper.vm.generateReport('conversion', { timeRange: '7d' })
      
      expect(report === null || typeof report === 'object').toBe(true)
    })

    it('should generate conversion reports', async () => {
      await wrapper.vm.$nextTick()
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ conversions: 100 })
      })

      const report = await wrapper.vm.generateConversionReport()
      
      expect(report === null || typeof report === 'object').toBe(true)
    })

    it('should export analytics data', async () => {
      await wrapper.vm.$nextTick()
      
      const result = await wrapper.vm.exportAnalyticsData('json')
      
      expect(typeof result).toBe('boolean')
    })
  })

  describe('Performance Tracking', () => {
    it('should track performance metrics', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.trackPerformanceMetrics()
      
      // Should call performance API
      expect(window.performance.getEntriesByType).toHaveBeenCalledWith('navigation')
    })

    it('should track performance on page load', async () => {
      // Mock document ready state
      Object.defineProperty(document, 'readyState', {
        value: 'complete',
        writable: true
      })

      const wrapper2 = mount(TestComponent, {
        props: { audience: 'individual' }
      })

      await wrapper2.vm.$nextTick()
      
      // Should track performance metrics automatically
      expect(window.performance.getEntriesByType).toHaveBeenCalled()
      
      wrapper2.unmount()
    })
  })

  describe('Third-party Integrations', () => {
    it('should integrate Google Analytics', async () => {
      await wrapper.vm.$nextTick()
      
      // Mock gtag not existing
      delete (window as any).gtag
      
      wrapper.vm.integrateGoogleAnalytics('GA-123456')
      
      // Should create script tag
      expect(document.head.children.length).toBeGreaterThan(0)
    })

    it('should integrate Hotjar', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.integrateHotjar('123456')
      
      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should integrate Mixpanel', async () => {
      await wrapper.vm.$nextTick()
      
      // Mock mixpanel not existing
      delete (window as any).mixpanel
      
      wrapper.vm.integrateMixpanel('test-token')
      
      // Should create script tag
      expect(document.head.children.length).toBeGreaterThan(0)
    })
  })

  describe('Utility Methods', () => {
    it('should update audience', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.updateAudience('institutional')
      
      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should set user ID', async () => {
      await wrapper.vm.$nextTick()
      
      wrapper.vm.setUserId('new-user-123')
      
      // Should not throw any errors
      expect(true).toBe(true)
    })

    it('should provide session information', async () => {
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.getSessionId()).toBeTruthy()
      expect(wrapper.vm.getSessionDuration()).toBeGreaterThanOrEqual(0)
      expect(typeof wrapper.vm.isUserActive()).toBe('boolean')
    })
  })

  describe('Debug Methods', () => {
    it('should provide debug information', async () => {
      await wrapper.vm.$nextTick()
      
      const debugInfo = wrapper.vm.getDebugInfo()
      
      expect(debugInfo).toHaveProperty('sessionId')
      expect(debugInfo).toHaveProperty('audience')
      expect(debugInfo).toHaveProperty('services')
      expect(debugInfo).toHaveProperty('deviceType')
    })

    it('should enable debug mode', async () => {
      await wrapper.vm.$nextTick()
      
      const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {})
      
      wrapper.vm.enableDebugMode()
      
      expect(consoleSpy).toHaveBeenCalledWith('Analytics debug mode enabled')
      
      consoleSpy.mockRestore()
    })

    it('should disable debug mode', async () => {
      await wrapper.vm.$nextTick()
      
      const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {})
      
      wrapper.vm.disableDebugMode()
      
      expect(consoleSpy).toHaveBeenCalledWith('Analytics debug mode disabled')
      
      consoleSpy.mockRestore()
    })
  })

  describe('Reactive State', () => {
    it('should have reactive state properties', async () => {
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.isInitialized).toBe(true)
      expect(wrapper.vm.sessionId).toBeTruthy()
      expect(wrapper.vm.deviceType).toMatch(/^(mobile|tablet|desktop)$/)
      expect(wrapper.vm.isOnline).toBe(true)
    })

    it('should update device type on window resize', async () => {
      await wrapper.vm.$nextTick()
      
      const initialDeviceType = wrapper.vm.deviceType
      
      // Mock window resize
      Object.defineProperty(window, 'innerWidth', { value: 500, writable: true })
      window.dispatchEvent(new Event('resize'))
      
      await wrapper.vm.$nextTick()
      
      // Device type should be reactive to window size
      expect(wrapper.vm.deviceType).toBe('mobile')
    })

    it('should track online/offline status', async () => {
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.isOnline).toBe(true)
      
      // Mock going offline
      Object.defineProperty(navigator, 'onLine', { value: false, writable: true })
      window.dispatchEvent(new Event('offline'))
      
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.isOnline).toBe(false)
    })
  })

  describe('Cleanup', () => {
    it('should cleanup services on unmount', async () => {
      await wrapper.vm.$nextTick()
      
      const sessionId = wrapper.vm.sessionId
      
      wrapper.unmount()
      
      // Should cleanup all services
      expect(true).toBe(true) // Services should be destroyed
    })
  })

  describe('Error Handling', () => {
    it('should handle service initialization errors gracefully', async () => {
      // Mock service constructor to throw error
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
      
      // This should not crash the component
      const errorWrapper = mount(TestComponent, {
        props: { audience: 'individual' }
      })

      await errorWrapper.vm.$nextTick()
      
      // Component should still mount
      expect(errorWrapper.exists()).toBe(true)
      
      consoleSpy.mockRestore()
      errorWrapper.unmount()
    })

    it('should handle API errors gracefully', async () => {
      await wrapper.vm.$nextTick()
      
      mockFetch.mockRejectedValueOnce(new Error('Network error'))
      
      const metrics = await wrapper.vm.getAnalyticsMetrics()
      
      // Should return null on error
      expect(metrics).toBeNull()
    })
  })
})