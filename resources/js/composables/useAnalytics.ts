import { ref, onMounted, onUnmounted, computed } from 'vue'
import { AnalyticsService } from '@/services/AnalyticsService'
import { ConversionTrackingService } from '@/services/ConversionTrackingService'
import { ABTestingService } from '@/services/ABTestingService'
import { HeatMapService } from '@/services/HeatMapService'
import type { 
  AudienceType, 
  AnalyticsMetrics,
  PageViewEvent,
  SectionViewEvent,
  CTAClickEvent,
  FormSubmissionEvent,
  CalculatorUsageEvent,
  ScrollTrackingEvent,
  UserBehaviorEvent,
  ConversionMetrics,
  HeatMapData,
  ABTestResult
} from '@/types/homepage'

export interface AnalyticsOptions {
  enableDebugMode?: boolean
  enableOfflineStorage?: boolean
  batchSize?: number
  flushInterval?: number
  trackingId?: string
  enableHeatMapping?: boolean
  enableABTesting?: boolean
}

export function useAnalytics(
  audience: AudienceType, 
  options: AnalyticsOptions = {},
  userId?: string
) {
  // Services
  let analyticsService: AnalyticsService | null = null
  let conversionService: ConversionTrackingService | null = null
  let abTestingService: ABTestingService | null = null
  let heatMapService: HeatMapService | null = null

  // Reactive state
  const isInitialized = ref(false)
  const sessionId = ref('')
  const currentPage = ref('')
  const sessionDuration = ref(0)
  const isActive = ref(true)
  const analyticsMetrics = ref<AnalyticsMetrics | null>(null)
  const conversionMetrics = ref<ConversionMetrics | null>(null)
  const activeTests = ref<Record<string, string>>({})
  const heatMapData = ref<HeatMapData | null>(null)

  // Computed properties
  const isOnline = computed(() => navigator.onLine)
  const deviceType = computed(() => {
    const width = window.innerWidth
    if (width < 768) return 'mobile'
    if (width < 1024) return 'tablet'
    return 'desktop'
  })

  // Session tracking
  let sessionTimer: NodeJS.Timeout | null = null
  let activityTimer: NodeJS.Timeout | null = null

  // Initialize all analytics services
  const initialize = async () => {
    try {
      // Generate session ID
      sessionId.value = `analytics_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`

      // Initialize core analytics service
      analyticsService = new AnalyticsService(audience, {
        enableDebugMode: options.enableDebugMode || false,
        enableOfflineStorage: options.enableOfflineStorage !== false,
        batchSize: options.batchSize || 10,
        flushInterval: options.flushInterval || 5000,
        trackingId: options.trackingId,
        apiEndpoint: '/api/analytics'
      }, userId)

      // Initialize conversion tracking service
      conversionService = new ConversionTrackingService(audience, userId)

      // Initialize A/B testing service if enabled
      if (options.enableABTesting !== false) {
        abTestingService = new ABTestingService(userId, sessionId.value, audience)
        activeTests.value = abTestingService.getTestAssignments()
      }

      // Initialize heat mapping service if enabled
      if (options.enableHeatMapping !== false) {
        heatMapService = new HeatMapService(sessionId.value, audience, userId)
        heatMapService.startRecording()
      }

      isInitialized.value = true

      // Start session tracking
      startSessionTracking()

      // Track initial page view
      trackPageView({ page: 'homepage' })

      if (options.enableDebugMode) {
        console.log('Analytics initialized:', {
          sessionId: sessionId.value,
          audience,
          services: {
            analytics: !!analyticsService,
            conversion: !!conversionService,
            abTesting: !!abTestingService,
            heatMap: !!heatMapService
          }
        })
      }

    } catch (error) {
      console.error('Failed to initialize analytics:', error)
    }
  }

  // Session tracking
  const startSessionTracking = () => {
    // Update session duration every second
    sessionTimer = setInterval(() => {
      sessionDuration.value += 1000
    }, 1000)

    // Track user activity
    const activityEvents = ['click', 'scroll', 'keypress', 'mousemove', 'touchstart']
    activityEvents.forEach(event => {
      document.addEventListener(event, updateActivity, { passive: true })
    })

    // Reset activity timer
    updateActivity()
  }

  const updateActivity = () => {
    isActive.value = true
    
    if (activityTimer) {
      clearTimeout(activityTimer)
    }

    // Mark as inactive after 30 seconds of no activity
    activityTimer = setTimeout(() => {
      isActive.value = false
    }, 30000)
  }

  // Core tracking methods
  const trackPageView = (event: PageViewEvent) => {
    if (!analyticsService) return

    currentPage.value = event.page
    analyticsService.trackPageView(event)

    // Also track in conversion service
    if (conversionService) {
      conversionService.trackPageView(event.page, event.additionalData)
    }
  }

  const trackSectionView = (event: SectionViewEvent) => {
    if (!analyticsService) return

    analyticsService.trackSectionView(event)

    // Also track in conversion service
    if (conversionService) {
      conversionService.trackSectionView(event.section, event.timeSpent, event.scrollDepth)
    }
  }

  const trackSectionExit = (section: string) => {
    if (!analyticsService) return
    analyticsService.trackSectionExit(section)
  }

  const trackCTAClick = (event: CTAClickEvent) => {
    if (!analyticsService) return

    analyticsService.trackCTAClick(event)

    // Track conversion
    if (conversionService) {
      conversionService.trackCTAClick(event)
    }

    // Track A/B test conversions
    if (abTestingService && activeTests.value) {
      Object.keys(activeTests.value).forEach(testId => {
        const actionGoalMap: Record<string, string> = {
          'demo': 'demo_request',
          'trial': 'trial_signup',
          'register': 'registration',
          'contact': 'contact_sales',
          'calculator-complete': 'calculator_completion'
        }

        const goalId = actionGoalMap[event.action]
        if (goalId) {
          abTestingService.trackConversion(testId, goalId)
        }
      })
    }

    // Track in heat map
    if (heatMapService && event.clickCoordinates) {
      heatMapService.trackClick({
        x: event.clickCoordinates.x,
        y: event.clickCoordinates.y,
        element: event.section,
        timestamp: Date.now()
      })
    }
  }

  const trackFormSubmission = (event: FormSubmissionEvent) => {
    if (!analyticsService) return

    analyticsService.trackFormSubmission(event)

    // Track conversion
    if (conversionService) {
      conversionService.trackFormSubmission(event.formType, event.success, event.formData)
    }
  }

  const trackCalculatorUsage = (event: CalculatorUsageEvent) => {
    if (!analyticsService) return

    analyticsService.trackCalculatorUsage(event)

    // Track conversion
    if (conversionService) {
      conversionService.trackCalculatorUsage(event.step, event.completed, event.calculatorData)
    }
  }

  const trackScrollDepth = (event: ScrollTrackingEvent) => {
    if (!analyticsService) return

    analyticsService.trackScrollDepth(event)

    // Track conversion
    if (conversionService) {
      conversionService.trackScrollDepth(event.percentage, event.section)
    }

    // Track in heat map
    if (heatMapService) {
      heatMapService.trackScroll({
        scrollY: window.pageYOffset,
        scrollPercentage: event.percentage,
        timestamp: Date.now()
      })
    }
  }

  const trackTimeOnSection = (section: string, duration: number) => {
    if (!analyticsService) return

    analyticsService.trackTimeOnSection(section, duration)

    // Track conversion
    if (conversionService) {
      conversionService.trackTimeOnSection(section, duration)
    }
  }

  const trackUserBehavior = (behaviorType: string, data: UserBehaviorEvent) => {
    if (!analyticsService) return

    analyticsService.trackUserBehavior(behaviorType, data)

    // Track conversion
    if (conversionService) {
      conversionService.trackUserBehavior(behaviorType, data)
    }
  }

  const trackConversion = (goalId: string, value?: number, additionalData?: Record<string, any>) => {
    if (!analyticsService) return

    analyticsService.trackConversion(goalId, value, additionalData)

    // Track conversion
    if (conversionService) {
      conversionService.trackConversion(goalId, value)
    }
  }

  const trackError = (errorType: string, errorData: Record<string, any>) => {
    if (!analyticsService) return
    analyticsService.trackError(errorType, errorData)
  }

  const trackCustomEvent = (eventName: string, data: Record<string, any>) => {
    if (!analyticsService) return
    analyticsService.trackCustomEvent(eventName, data)
  }

  // A/B Testing methods
  const getVariant = (testId: string) => {
    if (!abTestingService) return null
    return abTestingService.getVariant(testId)
  }

  const getComponentOverrides = (testId: string) => {
    if (!abTestingService) return []
    return abTestingService.getComponentOverrides(testId)
  }

  const isInTest = (testId: string) => {
    if (!abTestingService) return false
    return abTestingService.isInTest(testId)
  }

  const isInVariant = (testId: string, variantId: string) => {
    if (!abTestingService) return false
    return abTestingService.isInVariant(testId, variantId)
  }

  const getTestResults = async (testId: string): Promise<ABTestResult | null> => {
    if (!abTestingService) return null
    return await abTestingService.getTestResults(testId)
  }

  // Heat mapping methods
  const startHeatMapRecording = () => {
    if (!heatMapService) return
    heatMapService.startRecording()
  }

  const stopHeatMapRecording = () => {
    if (!heatMapService) return
    heatMapService.stopRecording()
  }

  const getHeatMapData = (): HeatMapData | null => {
    if (!heatMapService) return null
    const data = heatMapService.getHeatMapData()
    heatMapData.value = data
    return data
  }

  const generateHeatMapReport = async () => {
    if (!heatMapService) return null
    return await heatMapService.generateHeatMapReport()
  }

  // Analytics and reporting methods
  const getAnalyticsMetrics = async (timeRange?: { start: Date; end: Date }) => {
    if (!analyticsService) return null
    const metrics = await analyticsService.getAnalyticsMetrics(timeRange)
    analyticsMetrics.value = metrics
    return metrics
  }

  const getConversionMetrics = async () => {
    if (!conversionService) return null
    const metrics = conversionService.getConversionMetrics()
    conversionMetrics.value = metrics
    return metrics
  }

  const generateReport = async (reportType: string, options?: Record<string, any>) => {
    if (!analyticsService) return null
    return await analyticsService.generateReport(reportType, options)
  }

  const generateConversionReport = async (timeRange?: { start: Date; end: Date }) => {
    try {
      const response = await fetch('/api/analytics/conversion-report', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': sessionId.value
        },
        body: JSON.stringify({
          audience,
          userId,
          sessionId: sessionId.value,
          timeRange
        })
      })

      if (response.ok) {
        return await response.json()
      }
    } catch (error) {
      console.error('Failed to generate conversion report:', error)
    }
    return null
  }

  const exportAnalyticsData = async (format: 'csv' | 'json' = 'json', filters?: Record<string, any>) => {
    if (!analyticsService) return false
    return await analyticsService.exportData(format, filters)
  }

  // Performance tracking
  const trackPerformanceMetrics = () => {
    if (typeof window !== 'undefined' && 'performance' in window) {
      const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
      
      if (navigation) {
        trackCustomEvent('performance_metrics', {
          loadTime: navigation.loadEventEnd - navigation.loadEventStart,
          domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
          firstContentfulPaint: getPerformanceMetric('first-contentful-paint'),
          largestContentfulPaint: getPerformanceMetric('largest-contentful-paint'),
          cumulativeLayoutShift: getPerformanceMetric('layout-shift'),
          firstInputDelay: getPerformanceMetric('first-input'),
          deviceType: deviceType.value,
          connectionType: (navigator as any).connection?.effectiveType || 'unknown'
        })
      }
    }
  }

  const getPerformanceMetric = (metricName: string): number => {
    const entries = performance.getEntriesByName(metricName)
    return entries.length > 0 ? entries[0].startTime : 0
  }

  // Third-party integrations
  const integrateGoogleAnalytics = (trackingId: string) => {
    if (typeof window !== 'undefined' && !window.gtag) {
      const script = document.createElement('script')
      script.async = true
      script.src = `https://www.googletagmanager.com/gtag/js?id=${trackingId}`
      document.head.appendChild(script)

      window.dataLayer = window.dataLayer || []
      window.gtag = function() {
        window.dataLayer.push(arguments)
      }
      window.gtag('js', new Date())
      window.gtag('config', trackingId)

      // Sync events with Google Analytics
      if (analyticsService) {
        const originalTrackEvent = analyticsService.trackCustomEvent.bind(analyticsService)
        analyticsService.trackCustomEvent = (eventName: string, data: Record<string, any>) => {
          originalTrackEvent(eventName, data)
          window.gtag('event', eventName, data)
        }
      }
    }
  }

  const integrateHotjar = (hotjarId: string) => {
    if (heatMapService) {
      heatMapService.integrateHotjar(hotjarId)
    }
  }

  const integrateMixpanel = (token: string) => {
    if (typeof window !== 'undefined' && !window.mixpanel) {
      const script = document.createElement('script')
      script.src = 'https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js'
      script.onload = () => {
        window.mixpanel.init(token)
        
        // Sync events with Mixpanel
        if (analyticsService) {
          const originalTrackEvent = analyticsService.trackCustomEvent.bind(analyticsService)
          analyticsService.trackCustomEvent = (eventName: string, data: Record<string, any>) => {
            originalTrackEvent(eventName, data)
            window.mixpanel.track(eventName, data)
          }
        }
      }
      document.head.appendChild(script)
    }
  }

  // Utility methods
  const updateAudience = (newAudience: AudienceType) => {
    if (analyticsService) {
      analyticsService.updateAudience(newAudience)
    }
    
    if (conversionService) {
      conversionService.updateAudience(newAudience)
    }
    
    // Reinitialize A/B testing service with new audience
    if (abTestingService) {
      abTestingService.destroy()
      abTestingService = new ABTestingService(userId, sessionId.value, newAudience)
      activeTests.value = abTestingService.getTestAssignments()
    }
  }

  const setUserId = (newUserId: string) => {
    if (analyticsService) {
      analyticsService.setUserId(newUserId)
    }
    
    if (conversionService) {
      conversionService.setUserId(newUserId)
    }
  }

  const getSessionId = () => {
    return sessionId.value
  }

  const getSessionDuration = () => {
    return sessionDuration.value
  }

  const isUserActive = () => {
    return isActive.value
  }

  // Debug methods
  const getDebugInfo = () => {
    return {
      sessionId: sessionId.value,
      currentPage: currentPage.value,
      sessionDuration: sessionDuration.value,
      isActive: isActive.value,
      audience,
      userId,
      services: {
        analytics: !!analyticsService,
        conversion: !!conversionService,
        abTesting: !!abTestingService,
        heatMap: !!heatMapService
      },
      activeTests: activeTests.value,
      deviceType: deviceType.value,
      isOnline: isOnline.value
    }
  }

  const enableDebugMode = () => {
    if (analyticsService) {
      (analyticsService as any).config.enableDebugMode = true
    }
    console.log('Analytics debug mode enabled')
    console.log('Debug info:', getDebugInfo())
  }

  const disableDebugMode = () => {
    if (analyticsService) {
      (analyticsService as any).config.enableDebugMode = false
    }
    console.log('Analytics debug mode disabled')
  }

  // Lifecycle
  onMounted(() => {
    initialize()
    
    // Track performance metrics after page load
    if (document.readyState === 'complete') {
      trackPerformanceMetrics()
    } else {
      window.addEventListener('load', trackPerformanceMetrics)
    }
  })

  onUnmounted(() => {
    // Cleanup timers
    if (sessionTimer) {
      clearInterval(sessionTimer)
    }
    
    if (activityTimer) {
      clearTimeout(activityTimer)
    }

    // Cleanup services
    if (analyticsService) {
      analyticsService.destroy()
    }
    
    if (conversionService) {
      conversionService.destroy()
    }
    
    if (abTestingService) {
      abTestingService.destroy()
    }
    
    if (heatMapService) {
      heatMapService.destroy()
    }

    // Remove event listeners
    const activityEvents = ['click', 'scroll', 'keypress', 'mousemove', 'touchstart']
    activityEvents.forEach(event => {
      document.removeEventListener(event, updateActivity)
    })
  })

  return {
    // State
    isInitialized,
    sessionId,
    currentPage,
    sessionDuration,
    isActive,
    analyticsMetrics,
    conversionMetrics,
    activeTests,
    heatMapData,
    isOnline,
    deviceType,

    // Core tracking
    trackPageView,
    trackSectionView,
    trackSectionExit,
    trackCTAClick,
    trackFormSubmission,
    trackCalculatorUsage,
    trackScrollDepth,
    trackTimeOnSection,
    trackUserBehavior,
    trackConversion,
    trackError,
    trackCustomEvent,

    // A/B testing
    getVariant,
    getComponentOverrides,
    isInTest,
    isInVariant,
    getTestResults,

    // Heat mapping
    startHeatMapRecording,
    stopHeatMapRecording,
    getHeatMapData,
    generateHeatMapReport,

    // Analytics and reporting
    getAnalyticsMetrics,
    getConversionMetrics,
    generateReport,
    generateConversionReport,
    exportAnalyticsData,

    // Performance
    trackPerformanceMetrics,

    // Integrations
    integrateGoogleAnalytics,
    integrateHotjar,
    integrateMixpanel,

    // Utility
    updateAudience,
    setUserId,
    getSessionId,
    getSessionDuration,
    isUserActive,

    // Debug
    getDebugInfo,
    enableDebugMode,
    disableDebugMode
  }
}