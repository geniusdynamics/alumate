import { describe, it, expect, beforeEach, afterEach, vi, Mock } from 'vitest'
import { AnalyticsService } from '@/services/AnalyticsService'
import type { 
  AudienceType,
  PageViewEvent,
  SectionViewEvent,
  CTAClickEvent,
  FormSubmissionEvent,
  CalculatorUsageEvent,
  ScrollTrackingEvent,
  UserBehaviorEvent
} from '@/types/homepage'

// Mock fetch
global.fetch = vi.fn()
const mockFetch = fetch as Mock

// Mock navigator
Object.defineProperty(window, 'navigator', {
  value: {
    onLine: true,
    userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    sendBeacon: vi.fn()
  },
  writable: true
})

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock performance API
Object.defineProperty(window, 'performance', {
  value: {
    getEntriesByType: vi.fn(() => [{
      loadEventEnd: 1000,
      loadEventStart: 100,
      domContentLoadedEventEnd: 800,
      domContentLoadedEventStart: 200
    }]),
    getEntriesByName: vi.fn(() => [{ startTime: 500 }])
  }
})

describe('AnalyticsService', () => {
  let analyticsService: AnalyticsService
  const mockUserId = 'user123'
  const mockAudience: AudienceType = 'individual'

  beforeEach(() => {
    vi.clearAllMocks()
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true })
    })
    
    analyticsService = new AnalyticsService(mockAudience, {
      enableDebugMode: true,
      batchSize: 5,
      flushInterval: 1000
    }, mockUserId)
  })

  afterEach(() => {
    analyticsService.destroy()
  })

  describe('Initialization', () => {
    it('should initialize with correct configuration', () => {
      expect(analyticsService.getSessionId()).toMatch(/^analytics_\d+_[a-z0-9]+$/)
      expect(analyticsService.getSessionDuration()).toBeGreaterThan(0)
    })

    it('should generate unique session IDs', () => {
      const service1 = new AnalyticsService('individual')
      const service2 = new AnalyticsService('individual')
      
      expect(service1.getSessionId()).not.toBe(service2.getSessionId())
      
      service1.destroy()
      service2.destroy()
    })

    it('should load offline events from localStorage', () => {
      const offlineEvents = [
        { eventName: 'page_view', timestamp: new Date() }
      ]
      localStorageMock.getItem.mockReturnValue(JSON.stringify(offlineEvents))
      
      const service = new AnalyticsService('individual', { enableOfflineStorage: true })
      
      expect(localStorageMock.getItem).toHaveBeenCalledWith('analytics_offline_events')
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('analytics_offline_events')
      
      service.destroy()
    })
  })

  describe('Page View Tracking', () => {
    it('should track page views correctly', () => {
      const pageViewEvent: PageViewEvent = {
        page: 'homepage',
        additionalData: { source: 'direct' }
      }

      analyticsService.trackPageView(pageViewEvent)

      // Should trigger API call after batch processing
      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': analyticsService.getSessionId()
          },
          body: expect.stringContaining('page_view')
        })
      }, 100)
    })

    it('should track page performance metrics', () => {
      const pageViewEvent: PageViewEvent = {
        page: 'homepage'
      }

      analyticsService.trackPageView(pageViewEvent)

      // Performance tracking should be called after a delay
      setTimeout(() => {
        expect(window.performance.getEntriesByType).toHaveBeenCalledWith('navigation')
      }, 1100)
    })

    it('should track page exit when switching pages', () => {
      analyticsService.trackPageView({ page: 'page1' })
      analyticsService.trackPageView({ page: 'page2' })

      // Should track exit for page1 when switching to page2
      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('/api/analytics/events'),
          expect.objectContaining({
            body: expect.stringContaining('page_exit')
          })
        )
      }, 100)
    })
  })

  describe('Section View Tracking', () => {
    it('should track section views with all data', () => {
      const sectionViewEvent: SectionViewEvent = {
        section: 'hero',
        timeSpent: 5000,
        scrollDepth: 75,
        viewportVisible: true,
        interactionCount: 3
      }

      analyticsService.trackSectionView(sectionViewEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': analyticsService.getSessionId()
          },
          body: expect.stringContaining('section_view')
        })
      }, 100)
    })

    it('should track section exit and calculate time spent', () => {
      analyticsService.trackSectionView({ section: 'features' })
      
      // Wait a bit then track exit
      setTimeout(() => {
        analyticsService.trackSectionExit('features')
        
        setTimeout(() => {
          expect(mockFetch).toHaveBeenCalledWith(
            expect.stringContaining('/api/analytics/events'),
            expect.objectContaining({
              body: expect.stringContaining('section_exit')
            })
          )
        }, 100)
      }, 100)
    })
  })

  describe('CTA Click Tracking', () => {
    it('should track CTA clicks with all metadata', () => {
      const ctaClickEvent: CTAClickEvent = {
        action: 'trial',
        section: 'hero',
        audience: 'individual',
        ctaText: 'Start Free Trial',
        ctaPosition: 'primary',
        ctaType: 'button',
        targetUrl: '/signup',
        clickCoordinates: { x: 100, y: 200 },
        additionalData: { variant: 'A' }
      }

      analyticsService.trackCTAClick(ctaClickEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': analyticsService.getSessionId()
          },
          body: expect.stringContaining('cta_click')
        })
      }, 100)
    })

    it('should track click coordinates for heat mapping', () => {
      const ctaClickEvent: CTAClickEvent = {
        action: 'demo',
        section: 'pricing',
        audience: 'institutional',
        clickCoordinates: { x: 150, y: 300 }
      }

      analyticsService.trackCTAClick(ctaClickEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('/api/analytics/events'),
          expect.objectContaining({
            body: expect.stringContaining('click_heatmap')
          })
        )
      }, 100)
    })
  })

  describe('Form Submission Tracking', () => {
    it('should track successful form submissions', () => {
      const formEvent: FormSubmissionEvent = {
        formType: 'demo_request',
        formId: 'demo-form',
        success: true,
        formData: { name: 'John Doe', email: 'john@example.com' },
        timeToComplete: 30000,
        fieldInteractions: { name: 2, email: 1 }
      }

      analyticsService.trackFormSubmission(formEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': analyticsService.getSessionId()
          },
          body: expect.stringContaining('form_submission')
        })
      }, 100)
    })

    it('should track form abandonment for failed submissions', () => {
      const formEvent: FormSubmissionEvent = {
        formType: 'trial_signup',
        success: false,
        abandonmentPoint: 'email_field',
        completedFields: ['name'],
        timeToComplete: 15000
      }

      analyticsService.trackFormSubmission(formEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('/api/analytics/events'),
          expect.objectContaining({
            body: expect.stringContaining('form_abandonment')
          })
        )
      }, 100)
    })

    it('should sanitize sensitive form data', () => {
      const formEvent: FormSubmissionEvent = {
        formType: 'contact',
        success: true,
        formData: {
          name: 'John Doe',
          email: 'john@example.com',
          password: 'secret123',
          phone: '555-1234'
        }
      }

      analyticsService.trackFormSubmission(formEvent)

      setTimeout(() => {
        const callBody = JSON.parse(mockFetch.mock.calls[0][1].body)
        const formData = callBody.events[0].customData.formData
        
        expect(formData.name).toBe('John Doe')
        expect(formData.password).toBe('[REDACTED]')
        expect(formData.phone).toBe('[REDACTED]')
      }, 100)
    })
  })

  describe('Calculator Usage Tracking', () => {
    it('should track calculator steps and completion', () => {
      const calculatorEvent: CalculatorUsageEvent = {
        step: 3,
        stepName: 'Career Goals',
        totalSteps: 5,
        completed: false,
        calculatorData: { currentRole: 'Developer', experience: 5 },
        timeSpent: 45000,
        backtrackCount: 1,
        helpUsed: true
      }

      analyticsService.trackCalculatorUsage(calculatorEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': analyticsService.getSessionId()
          },
          body: expect.stringContaining('calculator_usage')
        })
      }, 100)
    })

    it('should track calculator funnel progression', () => {
      const calculatorEvent: CalculatorUsageEvent = {
        step: 5,
        stepName: 'Results',
        totalSteps: 5,
        completed: true
      }

      analyticsService.trackCalculatorUsage(calculatorEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('/api/analytics/events'),
          expect.objectContaining({
            body: expect.stringContaining('calculator_funnel')
          })
        )
      }, 100)
    })
  })

  describe('Scroll Depth Tracking', () => {
    it('should track scroll milestones', () => {
      const scrollEvent: ScrollTrackingEvent = {
        percentage: 50,
        section: 'features',
        scrollSpeed: 100,
        scrollDirection: 'down'
      }

      analyticsService.trackScrollDepth(scrollEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': analyticsService.getSessionId()
          },
          body: expect.stringContaining('scroll_depth')
        })
      }, 100)
    })

    it('should only track significant scroll milestones', () => {
      // Track various percentages
      analyticsService.trackScrollDepth({ percentage: 23 })
      analyticsService.trackScrollDepth({ percentage: 25 })
      analyticsService.trackScrollDepth({ percentage: 27 })
      analyticsService.trackScrollDepth({ percentage: 50 })

      setTimeout(() => {
        // Should only track 25% and 50% milestones
        const calls = mockFetch.mock.calls.filter(call => 
          call[1].body.includes('scroll_depth')
        )
        expect(calls).toHaveLength(2)
      }, 100)
    })

    it('should track continuous scroll behavior', () => {
      const scrollEvent: ScrollTrackingEvent = {
        percentage: 35,
        section: 'testimonials',
        scrollSpeed: 150,
        scrollDirection: 'up'
      }

      analyticsService.trackScrollDepth(scrollEvent)

      // Continuous scroll behavior should be tracked but not queued immediately
      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('/api/analytics/events'),
          expect.objectContaining({
            body: expect.stringContaining('scroll_behavior')
          })
        )
      }, 1100) // After flush interval
    })
  })

  describe('User Behavior Tracking', () => {
    it('should track custom user behavior events', () => {
      const behaviorEvent: UserBehaviorEvent = {
        elementId: 'hero-video',
        elementText: 'Play Video',
        section: 'hero',
        x: 500,
        y: 300,
        customData: { videoId: 'intro-video', duration: 120 }
      }

      analyticsService.trackUserBehavior('video_play', behaviorEvent)

      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': analyticsService.getSessionId()
          },
          body: expect.stringContaining('user_behavior')
        })
      }, 100)
    })
  })

  describe('Conversion Tracking', () => {
    it('should track conversions with immediate sending', () => {
      analyticsService.trackConversion('trial_signup', 100, { source: 'hero_cta' })

      // Should send immediate conversion event
      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/conversion', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': analyticsService.getSessionId()
        },
        body: expect.stringContaining('trial_signup')
      })
    })

    it('should track conversion path and touchpoints', () => {
      analyticsService.trackConversion('demo_request', 150)

      setTimeout(() => {
        if (mockFetch.mock.calls.length > 0 && mockFetch.mock.calls[0][1]) {
          const callBody = JSON.parse(mockFetch.mock.calls[0][1].body)
          const conversionData = callBody.events[0].customData
          
          expect(conversionData).toHaveProperty('conversionPath')
          expect(conversionData).toHaveProperty('touchpoints')
          expect(conversionData).toHaveProperty('timeToConversion')
        }
      }, 100)
    })
  })

  describe('Error Tracking', () => {
    it('should track JavaScript errors immediately', () => {
      const errorData = {
        message: 'TypeError: Cannot read property',
        filename: 'app.js',
        lineno: 42,
        stack: 'Error stack trace'
      }

      analyticsService.trackError('javascript_error', errorData)

      // Should send error immediately
      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/error', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': analyticsService.getSessionId()
        },
        body: expect.stringContaining('javascript_error')
      })
    })
  })

  describe('Batch Processing', () => {
    it('should batch events and flush when batch size is reached', () => {
      // Track multiple events to reach batch size (5)
      for (let i = 0; i < 5; i++) {
        analyticsService.trackCustomEvent(`test_event_${i}`, { index: i })
      }

      // Should flush immediately when batch size is reached
      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': analyticsService.getSessionId()
        },
        body: expect.stringContaining('test_event_0')
      })
    })

    it('should flush events on interval', async () => {
      analyticsService.trackCustomEvent('interval_test', { data: 'test' })

      // Wait for flush interval
      await new Promise(resolve => setTimeout(resolve, 1100))

      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/events', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': analyticsService.getSessionId()
        },
        body: expect.stringContaining('interval_test')
      })
    })

    it('should use sendBeacon for synchronous flushing', () => {
      analyticsService.trackCustomEvent('beacon_test', { data: 'test' })
      
      // Simulate page unload
      window.dispatchEvent(new Event('beforeunload'))

      expect(navigator.sendBeacon).toHaveBeenCalledWith(
        '/api/analytics/events',
        expect.stringContaining('beacon_test')
      )
    })
  })

  describe('Offline Storage', () => {
    it('should store events offline when network is unavailable', () => {
      // Mock offline state
      Object.defineProperty(navigator, 'onLine', { value: false, writable: true })
      
      const service = new AnalyticsService('individual', { enableOfflineStorage: true })
      service.trackCustomEvent('offline_test', { data: 'test' })

      // Should store in localStorage instead of sending
      expect(localStorageMock.setItem).toHaveBeenCalledWith(
        'analytics_offline_events',
        expect.stringContaining('offline_test')
      )
      
      service.destroy()
    })

    it('should limit offline storage to prevent memory issues', () => {
      const existingEvents = Array(1000).fill(null).map((_, i) => ({
        eventName: `event_${i}`,
        timestamp: new Date()
      }))
      
      localStorageMock.getItem.mockReturnValue(JSON.stringify(existingEvents))
      
      const service = new AnalyticsService('individual', { enableOfflineStorage: true })
      
      // Should limit to 1000 events
      expect(localStorageMock.setItem).toHaveBeenCalledWith(
        'analytics_offline_events',
        expect.stringMatching(/event_\d+/)
      )
      
      service.destroy()
    })
  })

  describe('Analytics Metrics', () => {
    it('should fetch analytics metrics from API', async () => {
      const mockMetrics = {
        pageViews: 1000,
        uniqueVisitors: 500,
        averageSessionDuration: 180000,
        bounceRate: 0.3,
        conversionRate: 0.05
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockMetrics)
      })

      const metrics = await analyticsService.getAnalyticsMetrics()

      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/metrics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': analyticsService.getSessionId()
        },
        body: expect.stringContaining(mockAudience)
      })

      expect(metrics).toEqual(mockMetrics)
    })

    it('should handle API errors gracefully', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500
      })

      const metrics = await analyticsService.getAnalyticsMetrics()
      expect(metrics).toBeNull()
    })
  })

  describe('Data Export', () => {
    it('should export analytics data in JSON format', async () => {
      const mockBlob = new Blob(['{"data": "test"}'], { type: 'application/json' })
      
      mockFetch.mockResolvedValueOnce({
        ok: true,
        blob: () => Promise.resolve(mockBlob)
      })

      // Mock URL.createObjectURL
      global.URL.createObjectURL = vi.fn(() => 'blob:mock-url')
      global.URL.revokeObjectURL = vi.fn()

      // Mock document.createElement and appendChild
      const mockAnchor = {
        href: '',
        download: '',
        click: vi.fn()
      }
      document.createElement = vi.fn(() => mockAnchor)
      document.body.appendChild = vi.fn()
      document.body.removeChild = vi.fn()

      const result = await analyticsService.exportData('json')

      expect(result).toBe(true)
      expect(mockFetch).toHaveBeenCalledWith('/api/analytics/export', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': analyticsService.getSessionId()
        },
        body: expect.stringContaining('"format":"json"')
      })
    })
  })

  describe('Session Management', () => {
    it('should track session duration', () => {
      const initialDuration = analyticsService.getSessionDuration()
      
      // Wait a bit
      setTimeout(() => {
        const newDuration = analyticsService.getSessionDuration()
        expect(newDuration).toBeGreaterThan(initialDuration)
      }, 100)
    })

    it('should detect user activity', () => {
      expect(analyticsService.isActive()).toBe(true)
      
      // Simulate user activity
      document.dispatchEvent(new Event('click'))
      expect(analyticsService.isActive()).toBe(true)
    })

    it('should update audience and reinitialize', () => {
      analyticsService.updateAudience('institutional')
      
      // Should track the audience change
      setTimeout(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('/api/analytics/events'),
          expect.objectContaining({
            body: expect.stringContaining('institutional')
          })
        )
      }, 100)
    })
  })

  describe('Utility Methods', () => {
    it('should set and update user ID', () => {
      const newUserId = 'user456'
      analyticsService.setUserId(newUserId)
      
      // Next event should include new user ID
      analyticsService.trackCustomEvent('user_id_test', {})
      
      setTimeout(() => {
        const callBody = JSON.parse(mockFetch.mock.calls[0][1].body)
        expect(callBody.events[0].customData.userId).toBe(newUserId)
      }, 100)
    })

    it('should provide session information', () => {
      expect(analyticsService.getSessionId()).toBeTruthy()
      expect(analyticsService.getSessionDuration()).toBeGreaterThanOrEqual(0)
      expect(typeof analyticsService.isActive()).toBe('boolean')
    })
  })

  describe('Cleanup', () => {
    it('should cleanup resources on destroy', () => {
      const service = new AnalyticsService('individual')
      const sessionId = service.getSessionId()
      
      service.destroy()
      
      // Should flush remaining events
      expect(navigator.sendBeacon).toHaveBeenCalledWith(
        '/api/analytics/events',
        expect.stringContaining(sessionId)
      )
    })
  })
})