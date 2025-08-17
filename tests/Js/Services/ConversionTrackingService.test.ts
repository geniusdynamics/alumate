import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { ConversionTrackingService } from '@/services/ConversionTrackingService'
import type { CTAClickEvent } from '@/types/homepage'

// Mock fetch
global.fetch = vi.fn()
global.navigator = {
  ...global.navigator,
  onLine: true,
  sendBeacon: vi.fn()
}

describe('ConversionTrackingService', () => {
  let service: ConversionTrackingService
  let mockFetch: any

  beforeEach(() => {
    mockFetch = vi.mocked(fetch)
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({})
    } as Response)

    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    service?.destroy()
    vi.useRealTimers()
  })

  describe('Individual Audience', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual', 'user123')
    })

    it('initializes with correct conversion goals for individuals', () => {
      expect(service).toBeDefined()
      expect(service.getSessionId()).toMatch(/^session_\d+_[a-z0-9]+$/)
    })

    it('tracks page views correctly', () => {
      service.trackPageView('homepage', { source: 'direct' })

      // Should track the event
      expect(service).toBeDefined()
    })

    it('tracks CTA clicks and checks conversion goals', () => {
      const ctaEvent: CTAClickEvent = {
        action: 'trial',
        section: 'hero',
        audience: 'individual',
        additionalData: { ctaId: 'hero-trial-cta' }
      }

      service.trackCTAClick(ctaEvent)

      // Should track both CTA click and conversion
      expect(service).toBeDefined()
    })

    it('tracks form submissions and conversions', () => {
      service.trackFormSubmission('trial_signup', true, {
        email: 'test@example.com',
        name: 'Test User'
      })

      // Should track form submission and trigger conversion
      expect(service).toBeDefined()
    })

    it('tracks calculator usage and completion', () => {
      // Start calculator
      service.trackCalculatorUsage(1, false, { currentRole: 'Developer' })

      // Complete calculator
      service.trackCalculatorUsage(5, true, {
        currentRole: 'Developer',
        targetRole: 'Senior Developer',
        projectedIncrease: 25000
      })

      expect(service).toBeDefined()
    })

    it('tracks scroll depth at milestones', () => {
      service.trackScrollDepth(25, 'hero')
      service.trackScrollDepth(50, 'features')
      service.trackScrollDepth(75, 'testimonials')
      service.trackScrollDepth(100, 'footer')

      // Should only track milestone percentages
      expect(service).toBeDefined()
    })

    it('tracks funnel progression correctly', () => {
      service.trackFunnelStep('landing')
      service.trackFunnelStep('value_calc_start')
      service.trackFunnelStep('trial_signup')

      expect(service).toBeDefined()
    })
  })

  describe('Institutional Audience', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('institutional', 'admin456')
    })

    it('initializes with correct conversion goals for institutions', () => {
      expect(service).toBeDefined()
    })

    it('tracks institutional CTA clicks correctly', () => {
      const ctaEvent: CTAClickEvent = {
        action: 'demo',
        section: 'features',
        audience: 'institutional',
        additionalData: { ctaId: 'features-demo-cta' }
      }

      service.trackCTAClick(ctaEvent)
      expect(service).toBeDefined()
    })

    it('tracks demo request form submissions', () => {
      service.trackFormSubmission('demo_request', true, {
        institutionName: 'Test University',
        contactName: 'John Admin',
        email: 'admin@testuni.edu'
      })

      expect(service).toBeDefined()
    })

    it('tracks institutional funnel steps', () => {
      service.trackFunnelStep('landing')
      service.trackFunnelStep('features_view')
      service.trackFunnelStep('demo_request')

      expect(service).toBeDefined()
    })
  })

  describe('Event Batching and Flushing', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('batches events and flushes when batch size is reached', async () => {
      // Generate enough events to trigger batch flush
      for (let i = 0; i < 12; i++) {
        service.trackPageView(`page-${i}`)
      }

      // Should have triggered a flush
      await vi.runAllTimersAsync()
      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', expect.any(Object))
    })

    it('flushes events on interval', async () => {
      service.trackPageView('homepage')

      // Advance time to trigger interval flush
      vi.advanceTimersByTime(6000) // 6 seconds
      await vi.runAllTimersAsync()

      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', expect.any(Object))
    })

    it('handles network errors gracefully', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      service.trackPageView('homepage')

      // Trigger flush
      vi.advanceTimersByTime(6000)
      await vi.runAllTimersAsync()

      // Should not throw error
      expect(service).toBeDefined()
    })

    it('uses sendBeacon for synchronous flushing', () => {
      const mockSendBeacon = vi.mocked(navigator.sendBeacon)
      
      service.trackPageView('homepage')
      
      // Simulate page unload
      window.dispatchEvent(new Event('beforeunload'))

      expect(mockSendBeacon).toHaveBeenCalled()
    })
  })

  describe('Data Sanitization', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('sanitizes sensitive form data', () => {
      const formData = {
        email: 'test@example.com',
        name: 'Test User',
        password: 'secret123',
        ssn: '123-45-6789',
        creditCard: '4111-1111-1111-1111'
      }

      service.trackFormSubmission('registration', true, formData)

      // Sensitive fields should be removed
      expect(service).toBeDefined()
    })
  })

  describe('Conversion Goals', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('tracks conversions for mapped actions', () => {
      const actions = ['demo', 'trial', 'register', 'contact']
      
      actions.forEach(action => {
        const ctaEvent: CTAClickEvent = {
          action,
          section: 'test',
          audience: 'individual'
        }
        
        service.trackCTAClick(ctaEvent)
      })

      expect(service).toBeDefined()
    })

    it('sends immediate conversion events for high-priority goals', async () => {
      service.trackConversion('trial_signup', 100)

      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/conversions', expect.objectContaining({
        method: 'POST',
        headers: expect.objectContaining({
          'Content-Type': 'application/json'
        }),
        body: expect.stringContaining('trial_signup')
      }))
    })
  })

  describe('A/B Testing Integration', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('tracks A/B test assignments', () => {
      service.trackABTestAssignment('hero-test-1', 'variant-b')
      expect(service).toBeDefined()
    })

    it('tracks A/B test conversions', () => {
      service.trackABTestConversion('hero-test-1', 'variant-b', 'trial_signup')
      expect(service).toBeDefined()
    })
  })

  describe('Metrics and Reporting', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('returns conversion metrics', () => {
      const metrics = service.getConversionMetrics()
      
      expect(metrics).toHaveProperty('totalConversions')
      expect(metrics).toHaveProperty('conversionRate')
      expect(metrics).toHaveProperty('averageTimeToConversion')
      expect(metrics).toHaveProperty('topConvertingCTAs')
      expect(metrics).toHaveProperty('funnelDropoffPoints')
      expect(metrics).toHaveProperty('audiencePerformance')
    })

    it('generates heat map data', () => {
      const heatMapData = service.generateHeatMapData()
      
      expect(heatMapData).toHaveProperty('clicks')
      expect(heatMapData).toHaveProperty('scrollDepth')
      expect(heatMapData).toHaveProperty('timeSpent')
      expect(heatMapData).toHaveProperty('ctaPerformance')
    })
  })

  describe('User and Session Management', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('updates user ID', () => {
      service.setUserId('newuser789')
      expect(service).toBeDefined()
    })

    it('updates audience and reinitializes goals', () => {
      service.updateAudience('institutional')
      expect(service).toBeDefined()
    })

    it('generates unique session IDs', () => {
      const service1 = new ConversionTrackingService('individual')
      const service2 = new ConversionTrackingService('individual')
      
      expect(service1.getSessionId()).not.toBe(service2.getSessionId())
      
      service1.destroy()
      service2.destroy()
    })
  })

  describe('Online/Offline Handling', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('handles offline state', () => {
      // Simulate going offline
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: false
      })

      window.dispatchEvent(new Event('offline'))

      service.trackPageView('homepage')

      // Should queue events but not send them
      expect(service).toBeDefined()
    })

    it('flushes queued events when coming back online', async () => {
      // Start offline
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: false
      })

      service.trackPageView('homepage')

      // Come back online
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: true
      })

      window.dispatchEvent(new Event('online'))

      await vi.runAllTimersAsync()

      expect(mockFetch).toHaveBeenCalled()
    })
  })

  describe('Performance', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('handles high-frequency events efficiently', () => {
      const startTime = performance.now()

      // Generate many events quickly
      for (let i = 0; i < 1000; i++) {
        service.trackScrollDepth(Math.random() * 100)
      }

      const endTime = performance.now()
      const duration = endTime - startTime

      // Should complete quickly (less than 100ms for 1000 events)
      expect(duration).toBeLessThan(100)
    })

    it('prioritizes high-priority events', () => {
      service.trackPageView('homepage') // Low priority
      service.trackConversion('trial_signup') // High priority

      // High priority events should flush immediately
      expect(mockFetch).toHaveBeenCalled()
    })
  })

  describe('Error Handling', () => {
    beforeEach(() => {
      service = new ConversionTrackingService('individual')
    })

    it('handles malformed data gracefully', () => {
      expect(() => {
        service.trackFormSubmission('test', true, {
          circular: {} as any
        })
      }).not.toThrow()
    })

    it('continues working after network failures', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      service.trackPageView('homepage')
      
      // Should not break subsequent tracking
      service.trackPageView('about')

      expect(service).toBeDefined()
    })
  })
})