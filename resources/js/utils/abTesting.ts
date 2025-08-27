import type { ABTestConfig, HeroComponentConfig } from '@/types/components'

interface ABTestSession {
  testId: string
  variant: string
  timestamp: number
  userId?: string
  sessionId: string
}

class ABTestingService {
  private sessions: Map<string, ABTestSession> = new Map()
  
  /**
   * Get or assign a variant for an A/B test
   */
  getVariant(config: ABTestConfig, userId?: string): string {
    if (!config.enabled || !config.variants || config.variants.length === 0) {
      return 'control'
    }
    
    const testId = config.testId || 'default'
    const sessionId = this.getSessionId()
    const sessionKey = `${testId}_${userId || sessionId}`
    
    // Check if user already has an assigned variant
    const existingSession = this.sessions.get(sessionKey)
    if (existingSession) {
      return existingSession.variant
    }
    
    // Assign new variant based on weights
    const variant = this.selectVariantByWeight(config.variants)
    
    // Store session
    const session: ABTestSession = {
      testId,
      variant,
      timestamp: Date.now(),
      userId,
      sessionId
    }
    
    this.sessions.set(sessionKey, session)
    this.persistSession(session)
    
    return variant
  }
  
  /**
   * Apply variant configuration to base config
   */
  applyVariant(baseConfig: HeroComponentConfig, variantId: string): HeroComponentConfig {
    if (!baseConfig.abTest?.enabled || !baseConfig.abTest.variants) {
      return baseConfig
    }
    
    const variant = baseConfig.abTest.variants.find(v => v.id === variantId)
    if (!variant) {
      return baseConfig
    }
    
    // Deep merge variant config with base config
    return this.deepMerge(baseConfig, variant.config)
  }
  
  /**
   * Track A/B test event
   */
  trackEvent(testId: string, variant: string, event: string, data?: Record<string, any>) {
    const eventData = {
      testId,
      variant,
      event,
      timestamp: Date.now(),
      data: data || {},
      sessionId: this.getSessionId()
    }
    
    // Send to analytics
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'ab_test_event', {
        test_id: testId,
        variant: variant,
        event_type: event,
        custom_parameters: data
      })
    }
    
    // Store locally for debugging
    this.storeEvent(eventData)
  }
  
  /**
   * Track conversion for A/B test
   */
  trackConversion(testId: string, variant: string, conversionType: string, value?: number) {
    this.trackEvent(testId, variant, 'conversion', {
      conversion_type: conversionType,
      value: value || 1
    })
  }
  
  private selectVariantByWeight(variants: Array<{ id: string; weight: number }>): string {
    const totalWeight = variants.reduce((sum, v) => sum + v.weight, 0)
    const random = Math.random() * totalWeight
    
    let currentWeight = 0
    for (const variant of variants) {
      currentWeight += variant.weight
      if (random <= currentWeight) {
        return variant.id
      }
    }
    
    return variants[0]?.id || 'control'
  }
  
  private getSessionId(): string {
    if (typeof window === 'undefined') return 'server'
    
    let sessionId = localStorage.getItem('ab_test_session_id')
    if (!sessionId) {
      sessionId = Math.random().toString(36).substring(2, 15)
      localStorage.setItem('ab_test_session_id', sessionId)
    }
    return sessionId
  }
  
  private persistSession(session: ABTestSession) {
    if (typeof window === 'undefined') return
    
    const sessions = JSON.parse(localStorage.getItem('ab_test_sessions') || '[]')
    sessions.push(session)
    
    // Keep only last 100 sessions
    if (sessions.length > 100) {
      sessions.splice(0, sessions.length - 100)
    }
    
    localStorage.setItem('ab_test_sessions', JSON.stringify(sessions))
  }
  
  private storeEvent(event: any) {
    if (typeof window === 'undefined') return
    
    const events = JSON.parse(localStorage.getItem('ab_test_events') || '[]')
    events.push(event)
    
    // Keep only last 1000 events
    if (events.length > 1000) {
      events.splice(0, events.length - 1000)
    }
    
    localStorage.setItem('ab_test_events', JSON.stringify(events))
  }
  
  private deepMerge(target: any, source: any): any {
    const result = { ...target }
    
    for (const key in source) {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        result[key] = this.deepMerge(target[key] || {}, source[key])
      } else {
        result[key] = source[key]
      }
    }
    
    return result
  }
}

export const abTestingService = new ABTestingService()

/**
 * Hook for using A/B testing in Vue components
 */
export function useABTest(config: ABTestConfig, userId?: string) {
  const variant = abTestingService.getVariant(config, userId)
  
  const trackEvent = (event: string, data?: Record<string, any>) => {
    if (config.testId) {
      abTestingService.trackEvent(config.testId, variant, event, data)
    }
  }
  
  const trackConversion = (conversionType: string, value?: number) => {
    if (config.testId) {
      abTestingService.trackConversion(config.testId, variant, conversionType, value)
    }
  }
  
  return {
    variant,
    trackEvent,
    trackConversion
  }
}

/**
 * Predefined A/B test configurations for hero components
 */
export const heroABTestConfigs = {
  individual: {
    enabled: true,
    testId: 'hero_individual_messaging',
    variants: [
      {
        id: 'success-story',
        name: 'Success Story Focus',
        weight: 50,
        config: {
          headline: 'Your Success Story Starts Here',
          variantStyling: {
            colorScheme: 'energetic' as const,
            typography: 'bold' as const
          }
        }
      },
      {
        id: 'network-focus',
        name: 'Network Focus',
        weight: 50,
        config: {
          headline: 'Unlock the Power of Your Alumni Network',
          variantStyling: {
            colorScheme: 'professional' as const,
            typography: 'modern' as const
          }
        }
      }
    ]
  },
  
  institution: {
    enabled: true,
    testId: 'hero_institution_partnership',
    variants: [
      {
        id: 'partnership-focus',
        name: 'Partnership Benefits',
        weight: 50,
        config: {
          headline: 'Transform Alumni Engagement Into Institutional Excellence',
          variantStyling: {
            colorScheme: 'professional' as const,
            typography: 'classic' as const
          }
        }
      },
      {
        id: 'roi-focus',
        name: 'ROI Focus',
        weight: 50,
        config: {
          headline: 'Increase Alumni Giving by 120% in 12 Months',
          variantStyling: {
            colorScheme: 'cool' as const,
            typography: 'bold' as const
          }
        }
      }
    ]
  },
  
  employer: {
    enabled: true,
    testId: 'hero_employer_efficiency',
    variants: [
      {
        id: 'efficiency-focus',
        name: 'Efficiency Focus',
        weight: 50,
        config: {
          headline: 'Hire Smarter, Hire Faster, Hire Better',
          variantStyling: {
            colorScheme: 'energetic' as const,
            typography: 'bold' as const
          }
        }
      },
      {
        id: 'quality-focus',
        name: 'Quality Focus',
        weight: 50,
        config: {
          headline: 'Access Pre-Vetted Alumni Talent',
          variantStyling: {
            colorScheme: 'professional' as const,
            typography: 'modern' as const
          }
        }
      }
    ]
  }
}