import { ref, onMounted, onUnmounted } from 'vue'
import { ConversionTrackingService } from '@/services/ConversionTrackingService'
import { ABTestingService } from '@/services/ABTestingService'
import { HeatMapService } from '@/services/HeatMapService'
import type { 
  AudienceType, 
  CTAClickEvent, 
  ConversionMetrics,
  ABTestResult,
  HeatMapData
} from '@/types/homepage'

export function useConversionTracking(audience: AudienceType, userId?: string) {
  // Services
  let conversionService: ConversionTrackingService | null = null
  let abTestingService: ABTestingService | null = null
  let heatMapService: HeatMapService | null = null

  // Reactive state
  const isInitialized = ref(false)
  const sessionId = ref('')
  const activeTests = ref<Record<string, string>>({})
  const conversionMetrics = ref<ConversionMetrics | null>(null)

  // Initialize services
  const initialize = async () => {
    try {
      // Generate session ID
      sessionId.value = `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`

      // Initialize conversion tracking service
      conversionService = new ConversionTrackingService(audience, userId)

      // Initialize A/B testing service
      abTestingService = new ABTestingService(userId, sessionId.value, audience)
      
      // Get active test assignments
      activeTests.value = abTestingService.getTestAssignments()

      // Initialize heat map service
      heatMapService = new HeatMapService(sessionId.value, audience, userId)

      isInitialized.value = true

      // Track initial page view
      trackPageView('homepage')

    } catch (error) {
      console.error('Failed to initialize conversion tracking:', error)
    }
  }

  // Conversion tracking methods
  const trackPageView = (page: string, additionalData?: Record<string, any>) => {
    if (!conversionService) return
    conversionService.trackPageView(page, additionalData)
  }

  const trackSectionView = (section: string, timeSpent?: number, scrollDepth?: number) => {
    if (!conversionService) return
    conversionService.trackSectionView(section, timeSpent, scrollDepth)
  }

  const trackCTAClick = (event: CTAClickEvent) => {
    if (!conversionService) return
    
    // Track conversion
    conversionService.trackCTAClick(event)

    // Track A/B test conversions if applicable
    if (abTestingService) {
      Object.keys(activeTests.value).forEach(testId => {
        // Map actions to conversion goals
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
  }

  const trackFormSubmission = (formType: string, success: boolean, formData?: Record<string, any>) => {
    if (!conversionService) return
    conversionService.trackFormSubmission(formType, success, formData)
  }

  const trackCalculatorUsage = (step: number, completed: boolean, calculatorData?: Record<string, any>) => {
    if (!conversionService) return
    conversionService.trackCalculatorUsage(step, completed, calculatorData)
  }

  const trackScrollDepth = (percentage: number, section?: string) => {
    if (!conversionService) return
    conversionService.trackScrollDepth(percentage, section)
  }

  const trackTimeOnSection = (section: string, duration: number) => {
    if (!conversionService) return
    conversionService.trackTimeOnSection(section, duration)
  }

  const trackCustomEvent = (eventType: string, data: Record<string, any>) => {
    if (!conversionService) return
    conversionService.trackUserBehavior(eventType, data)

    // Also track in heat map service if relevant
    if (heatMapService && (eventType.includes('click') || eventType.includes('hover'))) {
      heatMapService.trackCustomEvent(eventType, data)
    }
  }

  // A/B testing methods
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

  const getTestResults = async (testId: string) => {
    if (!abTestingService) return null
    return await abTestingService.getTestResults(testId)
  }

  // Heat map methods
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
    return heatMapService.getHeatMapData()
  }

  const generateHeatMapReport = async () => {
    if (!heatMapService) return null
    return await heatMapService.generateHeatMapReport()
  }

  // Analytics and reporting methods
  const getConversionMetrics = async () => {
    if (!conversionService) return null
    const metrics = conversionService.getConversionMetrics()
    conversionMetrics.value = metrics
    return metrics
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

  const exportAnalyticsData = async (format: 'csv' | 'json' = 'json') => {
    try {
      const response = await fetch(`/api/analytics/export?format=${format}`, {
        headers: {
          'X-Session-ID': sessionId.value
        }
      })

      if (response.ok) {
        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `analytics-data-${Date.now()}.${format}`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
        return true
      }
    } catch (error) {
      console.error('Failed to export analytics data:', error)
    }
    return false
  }

  // Utility methods
  const updateAudience = (newAudience: AudienceType) => {
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
    if (conversionService) {
      conversionService.setUserId(newUserId)
    }
  }

  const getSessionId = () => {
    return sessionId.value
  }

  // Integration with third-party tools
  const integrateGoogleAnalytics = (trackingId: string) => {
    if (typeof window !== 'undefined' && !window.gtag) {
      // Load Google Analytics
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
    }
  }

  const integrateHotjar = (hotjarId: string) => {
    if (heatMapService) {
      heatMapService.integrateHotjar(hotjarId)
    }
  }

  const integrateMixpanel = (token: string) => {
    if (typeof window !== 'undefined' && !window.mixpanel) {
      // Load Mixpanel
      const script = document.createElement('script')
      script.src = 'https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js'
      script.onload = () => {
        window.mixpanel.init(token)
      }
      document.head.appendChild(script)
    }
  }

  // Performance monitoring
  const trackPerformanceMetrics = () => {
    if (typeof window !== 'undefined' && 'performance' in window) {
      const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
      
      if (navigation) {
        trackCustomEvent('performance_metrics', {
          loadTime: navigation.loadEventEnd - navigation.loadEventStart,
          domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
          firstContentfulPaint: performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 0,
          largestContentfulPaint: performance.getEntriesByName('largest-contentful-paint')[0]?.startTime || 0
        })
      }
    }
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
    // Cleanup services
    if (conversionService) {
      conversionService.destroy()
    }
    
    if (abTestingService) {
      abTestingService.destroy()
    }
    
    if (heatMapService) {
      heatMapService.destroy()
    }
  })

  return {
    // State
    isInitialized,
    sessionId,
    activeTests,
    conversionMetrics,

    // Conversion tracking
    trackPageView,
    trackSectionView,
    trackCTAClick,
    trackFormSubmission,
    trackCalculatorUsage,
    trackScrollDepth,
    trackTimeOnSection,
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
    getConversionMetrics,
    generateConversionReport,
    exportAnalyticsData,

    // Utility
    updateAudience,
    setUserId,
    getSessionId,

    // Integrations
    integrateGoogleAnalytics,
    integrateHotjar,
    integrateMixpanel,

    // Performance
    trackPerformanceMetrics
  }
}