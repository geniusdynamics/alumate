import type { 
  AnalyticsEvent, 
  ConversionGoal, 
  AudienceType, 
  CTAClickEvent,
  ConversionFunnelStep,
  ConversionMetrics,
  HeatMapData,
  UserBehaviorEvent
} from '@/types/homepage'

export class ConversionTrackingService {
  private onlineListener: () => void;
  private offlineListener: () => void;
  private visibilityChangeListener: () => void;
  private beforeUnloadListener: () => void;
  private batchIntervalId: NodeJS.Timeout;
  private sessionId: string
  private userId?: string
  private audience: AudienceType
  private conversionGoals: ConversionGoal[]
  private funnelSteps: ConversionFunnelStep[]
  private eventQueue: AnalyticsEvent[]
  private isOnline: boolean
  private batchSize: number = 10
  private flushInterval: number = 5000 // 5 seconds

  constructor(audience: AudienceType, userId?: string) {
    this.sessionId = this.generateSessionId()
    this.userId = userId
    this.audience = audience
    this.conversionGoals = []
    this.funnelSteps = []
    this.eventQueue = []
    this.isOnline = navigator.onLine

    this.initializeConversionGoals()
    this.initializeFunnelSteps()
    this.setupEventListeners()
    this.startBatchProcessing()
  }

  private generateSessionId(): string {
    return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  }

  private initializeConversionGoals(): void {
    if (this.audience === 'institutional') {
      this.conversionGoals = [
        {
          id: 'demo_request',
          name: 'Demo Request',
          audience: 'institutional',
          type: 'demo_request',
          value: 100,
          trackingCode: 'INST_DEMO_REQ'
        },
        {
          id: 'case_study_download',
          name: 'Case Study Download',
          audience: 'institutional',
          type: 'contact_form',
          value: 50,
          trackingCode: 'INST_CASE_STUDY'
        },
        {
          id: 'contact_sales',
          name: 'Contact Sales',
          audience: 'institutional',
          type: 'contact_form',
          value: 150,
          trackingCode: 'INST_CONTACT_SALES'
        }
      ]
    } else {
      this.conversionGoals = [
        {
          id: 'trial_signup',
          name: 'Trial Signup',
          audience: 'individual',
          type: 'trial_signup',
          value: 75,
          trackingCode: 'IND_TRIAL_SIGNUP'
        },
        {
          id: 'registration',
          name: 'Registration',
          audience: 'individual',
          type: 'trial_signup',
          value: 100,
          trackingCode: 'IND_REGISTRATION'
        },
        {
          id: 'calculator_completion',
          name: 'Calculator Completion',
          audience: 'individual',
          type: 'calculator_completion',
          value: 25,
          trackingCode: 'IND_CALC_COMPLETE'
        }
      ]
    }
  }

  private initializeFunnelSteps(): void {
    if (this.audience === 'institutional') {
      this.funnelSteps = [
        { id: 'landing', name: 'Landing Page View', order: 1, required: true },
        { id: 'features_view', name: 'Features Section View', order: 2, required: false },
        { id: 'testimonials_view', name: 'Testimonials View', order: 3, required: false },
        { id: 'pricing_view', name: 'Pricing View', order: 4, required: false },
        { id: 'demo_request', name: 'Demo Request', order: 5, required: true },
        { id: 'demo_scheduled', name: 'Demo Scheduled', order: 6, required: false }
      ]
    } else {
      this.funnelSteps = [
        { id: 'landing', name: 'Landing Page View', order: 1, required: true },
        { id: 'value_calc_start', name: 'Value Calculator Started', order: 2, required: false },
        { id: 'success_stories_view', name: 'Success Stories View', order: 3, required: false },
        { id: 'pricing_view', name: 'Pricing View', order: 4, required: false },
        { id: 'trial_signup', name: 'Trial Signup', order: 5, required: true },
        { id: 'account_created', name: 'Account Created', order: 6, required: false }
      ]
    }
  }

  private setupEventListeners(): void {
    this.onlineListener = () => {
      this.isOnline = true
      this.flushEventQueue()
    };
    window.addEventListener('online', this.onlineListener);

    this.offlineListener = () => {
      this.isOnline = false
    };
    window.addEventListener('offline', this.offlineListener);

    this.visibilityChangeListener = () => {
      if (document.visibilityState === 'hidden') {
        this.flushEventQueue()
      }
    };
    document.addEventListener('visibilitychange', this.visibilityChangeListener);

    this.beforeUnloadListener = () => {
      this.flushEventQueue(true)
    };
    window.addEventListener('beforeunload', this.beforeUnloadListener);
  }

  private startBatchProcessing(): void {
    this.batchIntervalId = setInterval(() => {
      if (this.eventQueue.length > 0) {
        this.flushEventQueue()
      }
    }, this.flushInterval)
  }

  // Public Methods

  public trackPageView(page: string, additionalData?: Record<string, any>): void {
    this.trackEvent('page_view', {
      page,
      timestamp: new Date(),
      ...additionalData
    })

    // Track funnel step
    if (page === 'homepage') {
      this.trackFunnelStep('landing')
    }
  }

  public trackSectionView(section: string, timeSpent?: number, scrollDepth?: number): void {
    this.trackEvent('section_view', {
      section,
      timeSpent,
      scrollDepth,
      timestamp: new Date()
    })

    // Track relevant funnel steps
    const sectionFunnelMap: Record<string, string> = {
      'features': 'features_view',
      'testimonials': 'testimonials_view',
      'success-stories': 'success_stories_view',
      'pricing': 'pricing_view'
    }

    if (sectionFunnelMap[section]) {
      this.trackFunnelStep(sectionFunnelMap[section])
    }
  }

  public trackCTAClick(event: CTAClickEvent): void {
    this.trackEvent('cta_click', {
      action: event.action,
      section: event.section,
      audience: event.audience,
      timestamp: new Date(),
      ...event.additionalData
    })

    // Check for conversion goals
    this.checkConversionGoals(event.action)

    // Track funnel progression
    const actionFunnelMap: Record<string, string> = {
      'demo': 'demo_request',
      'trial': 'trial_signup',
      'register': 'trial_signup',
      'calculator-start': 'value_calc_start'
    }

    if (actionFunnelMap[event.action]) {
      this.trackFunnelStep(actionFunnelMap[event.action])
    }
  }

  public trackFormSubmission(formType: string, success: boolean, formData?: Record<string, any>): void {
    this.trackEvent('form_submission', {
      formType,
      success,
      formData: this.sanitizeFormData(formData),
      timestamp: new Date()
    })

    if (success) {
      // Track conversion based on form type
      if (formType === 'demo_request') {
        this.trackConversion('demo_request')
        this.trackFunnelStep('demo_scheduled')
      } else if (formType === 'trial_signup') {
        this.trackConversion('trial_signup')
        this.trackFunnelStep('account_created')
      } else if (formType === 'contact_form') {
        this.trackConversion('contact_sales')
      }
    }
  }

  public trackCalculatorUsage(step: number, completed: boolean, calculatorData?: Record<string, any>): void {
    this.trackEvent('calculator_usage', {
      step,
      completed,
      calculatorData: this.sanitizeFormData(calculatorData),
      timestamp: new Date()
    })

    if (step === 1) {
      this.trackFunnelStep('value_calc_start')
    }

    if (completed) {
      this.trackConversion('calculator_completion')
    }
  }

  public trackScrollDepth(percentage: number, section?: string): void {
    // Only track significant scroll milestones
    const milestones = [25, 50, 75, 90, 100]
    if (milestones.includes(Math.round(percentage))) {
      this.trackEvent('scroll_depth', {
        percentage: Math.round(percentage),
        section,
        timestamp: new Date()
      })
    }
  }

  public trackTimeOnSection(section: string, duration: number): void {
    this.trackEvent('time_on_section', {
      section,
      duration,
      timestamp: new Date()
    })
  }

  public trackUserBehavior(behaviorType: string, data: UserBehaviorEvent): void {
    this.trackEvent('user_behavior', {
      behaviorType,
      ...data,
      timestamp: new Date()
    })
  }

  public trackConversion(goalId: string, value?: number): void {
    const goal = this.conversionGoals.find(g => g.id === goalId)
    if (!goal) return

    this.trackEvent('conversion', {
      goalId,
      goalName: goal.name,
      goalType: goal.type,
      value: value || goal.value,
      trackingCode: goal.trackingCode,
      timestamp: new Date()
    })

    // Send immediate conversion event (high priority)
    this.sendConversionEvent(goal, value)
  }

  public trackFunnelStep(stepId: string): void {
    const step = this.funnelSteps.find(s => s.id === stepId)
    if (!step) return

    this.trackEvent('funnel_step', {
      stepId,
      stepName: step.name,
      stepOrder: step.order,
      required: step.required,
      timestamp: new Date()
    })
  }

  public trackABTestAssignment(testId: string, variantId: string): void {
    this.trackEvent('ab_test_assignment', {
      testId,
      variantId,
      timestamp: new Date()
    })
  }

  public trackABTestConversion(testId: string, variantId: string, goalId: string): void {
    this.trackEvent('ab_test_conversion', {
      testId,
      variantId,
      goalId,
      timestamp: new Date()
    })
  }

  public getConversionMetrics(): ConversionMetrics {
    // This would typically fetch from analytics service
    return {
      totalConversions: 0,
      conversionRate: 0,
      averageTimeToConversion: 0,
      topConvertingCTAs: [],
      funnelDropoffPoints: [],
      audiencePerformance: {
        individual: { conversions: 0, rate: 0 },
        institutional: { conversions: 0, rate: 0 }
      }
    }
  }

  public generateHeatMapData(): HeatMapData {
    // This would integrate with heat mapping service
    return {
      clicks: [],
      scrollDepth: [],
      timeSpent: [],
      ctaPerformance: []
    }
  }

  // Private Helper Methods

  private trackEvent(eventName: string, data: Record<string, any>): void {
    const event: AnalyticsEvent = {
      eventName,
      audience: this.audience,
      section: data.section || 'unknown',
      action: data.action || eventName,
      value: data.value,
      customData: {
        sessionId: this.sessionId,
        userId: this.userId,
        ...data
      },
      timestamp: data.timestamp || new Date()
    }

    this.eventQueue.push(event)

    // Flush immediately for high-priority events
    if (this.isHighPriorityEvent(eventName)) {
      this.flushEventQueue()
    } else if (this.eventQueue.length >= this.batchSize) {
      this.flushEventQueue()
    }
  }

  private isHighPriorityEvent(eventName: string): boolean {
    const highPriorityEvents = ['conversion', 'form_submission', 'cta_click']
    return highPriorityEvents.includes(eventName)
  }

  private checkConversionGoals(action: string): void {
    const actionGoalMap: Record<string, string> = {
      'demo': 'demo_request',
      'trial': 'trial_signup',
      'register': 'registration',
      'contact': 'contact_sales'
    }

    const goalId = actionGoalMap[action]
    if (goalId) {
      this.trackConversion(goalId)
    }
  }

  private sanitizeFormData(formData?: Record<string, any>): Record<string, any> | undefined {
    if (!formData) return undefined

    // Remove sensitive information
    const sensitiveFields = ['password', 'ssn', 'creditCard', 'phone']
    const sanitized = { ...formData }

    sensitiveFields.forEach(field => {
      if (sanitized[field]) {
        delete sanitized[field]
      }
    })

    return sanitized
  }

  private async flushEventQueue(synchronous = false): Promise<void> {
    if (this.eventQueue.length === 0 || !this.isOnline) return

    const events = [...this.eventQueue]
    this.eventQueue = []

    try {
      if (synchronous) {
        // Use sendBeacon for synchronous sending (page unload)
        const data = JSON.stringify({ events, sessionId: this.sessionId })
        navigator.sendBeacon('/api/analytics/events', data)
      } else {
        // Use fetch for regular async sending
        await fetch('/api/analytics/events', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': this.sessionId
          },
          body: JSON.stringify({ events, sessionId: this.sessionId })
        })
      }
    } catch (error) {
      console.error('Failed to send analytics events:', error)
      // Re-queue events for retry
      this.eventQueue.unshift(...events)
    }
  }

  private async sendConversionEvent(goal: ConversionGoal, value?: number): Promise<void> {
    try {
      await fetch('/api/analytics/conversions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': this.sessionId
        },
        body: JSON.stringify({
          goalId: goal.id,
          goalName: goal.name,
          goalType: goal.type,
          value: value || goal.value,
          trackingCode: goal.trackingCode,
          audience: this.audience,
          sessionId: this.sessionId,
          userId: this.userId,
          timestamp: new Date().toISOString()
        })
      })
    } catch (error) {
      console.error('Failed to send conversion event:', error)
    }
  }

  // Public utility methods

  public setUserId(userId: string): void {
    this.userId = userId
  }

  public updateAudience(audience: AudienceType): void {
    this.audience = audience
    this.initializeConversionGoals()
    this.initializeFunnelSteps()
  }

  public getSessionId(): string {
    return this.sessionId
  }

  public destroy(): void {
    this.flushEventQueue(true)
    window.removeEventListener('online', this.onlineListener)
    window.removeEventListener('offline', this.offlineListener)
    document.removeEventListener('visibilitychange', this.visibilityChangeListener)
    window.removeEventListener('beforeunload', this.beforeUnloadListener)
    clearInterval(this.batchIntervalId)
  }
}