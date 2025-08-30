import { ref, computed } from 'vue'
import { useAnalytics } from './useAnalytics'
import type { CTATrackingParams, CTAConversionEvent } from '@/types/components'

interface ConversionData {
  eventName: string
  category: string
  action: string
  label?: string
  value?: number
  customProperties?: Record<string, any>
  timestamp: number
  sessionId: string
  userId?: string
  ctaId?: string
  ctaType?: string
  ctaVariant?: string
  pageUrl: string
  referrer?: string
  utmParams?: CTATrackingParams
}

interface ConversionGoal {
  id: string
  name: string
  description: string
  targetValue?: number
  conversionWindow?: number // in milliseconds
  attributionModel?: 'first-click' | 'last-click' | 'linear' | 'time-decay'
}

const conversionQueue = ref<ConversionData[]>([])
const conversionGoals = ref<Map<string, ConversionGoal>>(new Map())
const sessionId = ref<string>(generateSessionId())

export function useConversionTracking() {
  const { trackEvent } = useAnalytics()

  /**
   * Track a conversion event
   */
  const trackConversion = (
    eventName: string,
    conversionEvent: CTAConversionEvent,
    additionalData?: Record<string, any>
  ) => {
    const conversionData: ConversionData = {
      eventName,
      category: conversionEvent.category,
      action: conversionEvent.action,
      label: conversionEvent.label,
      value: conversionEvent.value,
      customProperties: {
        ...conversionEvent.customProperties,
        ...additionalData
      },
      timestamp: Date.now(),
      sessionId: sessionId.value,
      userId: getUserId(),
      pageUrl: typeof window !== 'undefined' ? window.location.href : '',
      referrer: typeof document !== 'undefined' ? document.referrer : '',
      utmParams: extractUtmParams()
    }

    // Add to queue
    conversionQueue.value.push(conversionData)

    // Track with analytics
    trackEvent(eventName, {
      category: conversionData.category,
      action: conversionData.action,
      label: conversionData.label,
      value: conversionData.value,
      session_id: conversionData.sessionId,
      user_id: conversionData.userId,
      page_url: conversionData.pageUrl,
      referrer: conversionData.referrer,
      utm_params: conversionData.utmParams,
      ...conversionData.customProperties
    })

    // Store in localStorage for persistence
    storeConversionData(conversionData)

    // Check for goal completion
    checkGoalCompletion(conversionData)
  }

  /**
   * Track CTA click with conversion attribution
   */
  const trackCTAClick = (
    ctaId: string,
    ctaType: string,
    ctaText: string,
    destinationUrl: string,
    trackingParams?: CTATrackingParams,
    variant?: string
  ) => {
    const clickData = {
      cta_id: ctaId,
      cta_type: ctaType,
      cta_text: ctaText,
      cta_variant: variant,
      destination_url: destinationUrl,
      tracking_params: trackingParams,
      click_timestamp: Date.now()
    }

    trackEvent('cta_click', clickData)

    // Store click attribution data
    storeClickAttribution(clickData)
  }

  /**
   * Track CTA impression
   */
  const trackCTAImpression = (
    ctaId: string,
    ctaType: string,
    variant?: string,
    context?: Record<string, any>
  ) => {
    trackEvent('cta_impression', {
      cta_id: ctaId,
      cta_type: ctaType,
      cta_variant: variant,
      impression_timestamp: Date.now(),
      ...context
    })
  }

  /**
   * Set up conversion goals
   */
  const defineConversionGoal = (goal: ConversionGoal) => {
    conversionGoals.value.set(goal.id, goal)
  }

  /**
   * Get conversion funnel data
   */
  const getConversionFunnel = (goalId: string, timeRange?: { start: Date; end: Date }) => {
    const goal = conversionGoals.value.get(goalId)
    if (!goal) return null

    const conversions = conversionQueue.value.filter(conversion => {
      const matchesGoal = conversion.eventName === goal.name
      const inTimeRange = !timeRange || (
        conversion.timestamp >= timeRange.start.getTime() &&
        conversion.timestamp <= timeRange.end.getTime()
      )
      return matchesGoal && inTimeRange
    })

    return {
      goal,
      totalConversions: conversions.length,
      conversionRate: calculateConversionRate(conversions),
      averageValue: calculateAverageValue(conversions),
      conversions
    }
  }

  /**
   * Get attribution data for a conversion
   */
  const getAttributionData = (conversionId: string) => {
    const clickAttribution = getStoredClickAttribution()
    const conversion = conversionQueue.value.find(c => 
      c.timestamp.toString() === conversionId
    )

    if (!conversion) return null

    // Find relevant clicks within attribution window
    const attributionWindow = 30 * 24 * 60 * 60 * 1000 // 30 days
    const relevantClicks = clickAttribution.filter(click => 
      click.click_timestamp <= conversion.timestamp &&
      click.click_timestamp >= (conversion.timestamp - attributionWindow)
    )

    return {
      conversion,
      attributedClicks: relevantClicks,
      attributionModel: 'last-click', // Default model
      primaryAttribution: relevantClicks[relevantClicks.length - 1] // Last click
    }
  }

  /**
   * Calculate conversion metrics
   */
  const getConversionMetrics = (timeRange?: { start: Date; end: Date }) => {
    const filteredConversions = timeRange 
      ? conversionQueue.value.filter(c => 
          c.timestamp >= timeRange.start.getTime() &&
          c.timestamp <= timeRange.end.getTime()
        )
      : conversionQueue.value

    const totalConversions = filteredConversions.length
    const totalValue = filteredConversions.reduce((sum, c) => sum + (c.value || 0), 0)
    const averageValue = totalConversions > 0 ? totalValue / totalConversions : 0

    // Group by category
    const byCategory = filteredConversions.reduce((acc, conversion) => {
      if (!acc[conversion.category]) {
        acc[conversion.category] = []
      }
      acc[conversion.category].push(conversion)
      return acc
    }, {} as Record<string, ConversionData[]>)

    // Group by CTA type
    const byCTAType = filteredConversions.reduce((acc, conversion) => {
      const ctaType = conversion.ctaType || 'unknown'
      if (!acc[ctaType]) {
        acc[ctaType] = []
      }
      acc[ctaType].push(conversion)
      return acc
    }, {} as Record<string, ConversionData[]>)

    return {
      totalConversions,
      totalValue,
      averageValue,
      byCategory,
      byCTAType,
      conversionRate: calculateOverallConversionRate()
    }
  }

  /**
   * Clear conversion data
   */
  const clearConversionData = () => {
    conversionQueue.value = []
    try {
      localStorage.removeItem('conversion_data')
      localStorage.removeItem('click_attribution')
    } catch (error) {
      console.warn('Failed to clear conversion data from localStorage:', error)
    }
  }

  return {
    trackConversion,
    trackCTAClick,
    trackCTAImpression,
    defineConversionGoal,
    getConversionFunnel,
    getAttributionData,
    getConversionMetrics,
    clearConversionData,
    conversionQueue: computed(() => conversionQueue.value),
    sessionId: computed(() => sessionId.value)
  }
}

// Helper functions
function generateSessionId(): string {
  return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
}

function getUserId(): string | undefined {
  // Try to get user ID from various sources
  if (typeof window !== 'undefined') {
    // Check for authenticated user data
    const userMeta = document.querySelector('meta[name="user-id"]')
    if (userMeta) {
      return userMeta.getAttribute('content') || undefined
    }

    // Check localStorage
    try {
      const userData = localStorage.getItem('user_data')
      if (userData) {
        const parsed = JSON.parse(userData)
        return parsed.id || parsed.user_id
      }
    } catch (error) {
      // Ignore parsing errors
    }
  }

  return undefined
}

function extractUtmParams(): CTATrackingParams | undefined {
  if (typeof window === 'undefined') return undefined

  const urlParams = new URLSearchParams(window.location.search)
  const utmParams: CTATrackingParams = {}

  const utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']
  let hasUtmParams = false

  utmKeys.forEach(key => {
    const value = urlParams.get(key)
    if (value) {
      utmParams[key] = value
      hasUtmParams = true
    }
  })

  return hasUtmParams ? utmParams : undefined
}

function storeConversionData(conversion: ConversionData) {
  try {
    const stored = JSON.parse(localStorage.getItem('conversion_data') || '[]')
    stored.push(conversion)
    
    // Keep only last 1000 conversions
    if (stored.length > 1000) {
      stored.splice(0, stored.length - 1000)
    }
    
    localStorage.setItem('conversion_data', JSON.stringify(stored))
  } catch (error) {
    console.warn('Failed to store conversion data:', error)
  }
}

function storeClickAttribution(clickData: any) {
  try {
    const stored = JSON.parse(localStorage.getItem('click_attribution') || '[]')
    stored.push(clickData)
    
    // Keep only last 500 clicks
    if (stored.length > 500) {
      stored.splice(0, stored.length - 500)
    }
    
    localStorage.setItem('click_attribution', JSON.stringify(stored))
  } catch (error) {
    console.warn('Failed to store click attribution data:', error)
  }
}

function getStoredClickAttribution(): any[] {
  try {
    return JSON.parse(localStorage.getItem('click_attribution') || '[]')
  } catch (error) {
    console.warn('Failed to retrieve click attribution data:', error)
    return []
  }
}

function checkGoalCompletion(conversion: ConversionData) {
  // Check if this conversion completes any defined goals
  conversionGoals.value.forEach((goal, goalId) => {
    if (conversion.eventName === goal.name) {
      // Goal completed - could trigger additional tracking or notifications
      console.log(`Conversion goal "${goal.name}" completed:`, conversion)
    }
  })
}

function calculateConversionRate(conversions: ConversionData[]): number {
  // This would need impression data to calculate properly
  // For now, return a placeholder
  return conversions.length > 0 ? 0.05 : 0 // 5% placeholder rate
}

function calculateAverageValue(conversions: ConversionData[]): number {
  if (conversions.length === 0) return 0
  
  const totalValue = conversions.reduce((sum, c) => sum + (c.value || 0), 0)
  return totalValue / conversions.length
}

function calculateOverallConversionRate(): number {
  // This would need impression data to calculate properly
  // For now, return a placeholder based on conversion count
  return conversionQueue.value.length > 0 ? 0.03 : 0 // 3% placeholder rate
}