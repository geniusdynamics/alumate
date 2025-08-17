import type { 
  AnalyticsEvent, 
  AudienceType, 
  CTAClickEvent,
  ConversionGoal,
  ConversionFunnelStep,
  UserBehaviorEvent,
  ScrollTrackingEvent,
  SectionViewEvent,
  FormSubmissionEvent,
  CalculatorUsageEvent,
  PageViewEvent
} from '@/types/homepage'

export interface AnalyticsConfig {
  batchSize: number
  flushInterval: number
  enableDebugMode: boolean
  enableOfflineStorage: boolean
  apiEndpoint: string
  trackingId?: string
}

export interface AnalyticsMetrics {
  pageViews: number
  uniqueVisitors: number
  averageSessionDuration: number
  bounceRate: number
  conversionRate: number
  topPages: Array<{ page: string; views: number }>
  topCTAs: Array<{ cta: string; clicks: number }>
  audienceBreakdown: Record<AudienceType, number>
}

export class AnalyticsService {
  private config: AnalyticsConfig
  private sessionId: string
  private userId?: string
  private audience: AudienceType
  private eventQueue: AnalyticsEvent[]
  private isOnline: boolean
  private sessionStartTime: number
  private lastActivityTime: number
  private pageViewStartTime: number
  private currentPage: string
  private sectionViewTimes: Map<string, number>
  private scrollMilestones: Set<number>
  private flushTimer?: NodeJS.Timeout

  constructor(
    audience: AudienceType, 
    config: Partial<AnalyticsConfig> = {},
    userId?: string
  ) {
    this.config = {
      batchSize: 10,
      flushInterval: 5000,
      enableDebugMode: false,
      enableOfflineStorage: true,
      apiEndpoint: '/api/analytics',
      ...config
    }

    this.sessionId = this.generateSessionId()
    this.userId = userId
    this.audience = audience
    this.eventQueue = []
    this.isOnline = navigator.onLine
    this.sessionStartTime = Date.now()
    this.lastActivityTime = Date.now()
    this.pageViewStartTime = Date.now()
    this.currentPage = ''
    this.sectionViewTimes = new Map()
    this.scrollMilestones = new Set()

    this.initializeService()
  }

  private generateSessionId(): string {
    return `analytics_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  }

  private initializeService(): void {
    this.setupEventListeners()
    this.startBatchProcessing()
    this.loadOfflineEvents()
    
    if (this.config.enableDebugMode) {
      console.log('AnalyticsService initialized:', {
        sessionId: this.sessionId,
        audience: this.audience,
        config: this.config
      })
    }
  }

  private setupEventListeners(): void {
    // Online/offline status
    window.addEventListener('online', () => {
      this.isOnline = true
      this.flushEventQueue()
    })

    window.addEventListener('offline', () => {
      this.isOnline = false
    })

    // Page visibility changes
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        this.trackPageExit()
        this.flushEventQueue()
      } else {
        this.trackPageResume()
      }
    })

    // Before page unload
    window.addEventListener('beforeunload', () => {
      this.trackPageExit()
      this.flushEventQueue(true)
    })

    // User activity tracking
    const activityEvents = ['click', 'scroll', 'keypress', 'mousemove', 'touchstart']
    activityEvents.forEach(event => {
      document.addEventListener(event, () => {
        this.lastActivityTime = Date.now()
      }, { passive: true })
    })

    // Error tracking
    window.addEventListener('error', (event) => {
      this.trackError('javascript_error', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        stack: event.error?.stack
      })
    })

    // Unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      this.trackError('unhandled_promise_rejection', {
        reason: event.reason?.toString(),
        stack: event.reason?.stack
      })
    })
  }

  private startBatchProcessing(): void {
    this.flushTimer = setInterval(() => {
      if (this.eventQueue.length > 0) {
        this.flushEventQueue()
      }
    }, this.config.flushInterval)
  }

  private loadOfflineEvents(): void {
    if (!this.config.enableOfflineStorage) return

    try {
      const storedEvents = localStorage.getItem('analytics_offline_events')
      if (storedEvents) {
        const events = JSON.parse(storedEvents) as AnalyticsEvent[]
        this.eventQueue.push(...events)
        localStorage.removeItem('analytics_offline_events')
        
        if (this.config.enableDebugMode) {
          console.log('Loaded offline events:', events.length)
        }
      }
    } catch (error) {
      console.error('Failed to load offline events:', error)
    }
  }

  // Public tracking methods

  public trackPageView(event: PageViewEvent): void {
    // Track previous page exit if exists
    if (this.currentPage) {
      this.trackPageExit()
    }

    this.currentPage = event.page
    this.pageViewStartTime = Date.now()
    this.scrollMilestones.clear()

    this.trackEvent('page_view', {
      page: event.page,
      referrer: document.referrer,
      userAgent: navigator.userAgent,
      screenResolution: `${screen.width}x${screen.height}`,
      viewportSize: `${window.innerWidth}x${window.innerHeight}`,
      timestamp: new Date(),
      ...event.additionalData
    })

    // Track performance metrics if available
    this.trackPagePerformance(event.page)
  }

  public trackSectionView(event: SectionViewEvent): void {
    const startTime = Date.now()
    this.sectionViewTimes.set(event.section, startTime)

    this.trackEvent('section_view', {
      section: event.section,
      timeSpent: event.timeSpent,
      scrollDepth: event.scrollDepth,
      viewportVisible: event.viewportVisible,
      interactionCount: event.interactionCount,
      timestamp: new Date()
    })
  }

  public trackSectionExit(section: string): void {
    const startTime = this.sectionViewTimes.get(section)
    if (startTime) {
      const timeSpent = Date.now() - startTime
      this.sectionViewTimes.delete(section)

      this.trackEvent('section_exit', {
        section,
        timeSpent,
        timestamp: new Date()
      })
    }
  }

  public trackCTAClick(event: CTAClickEvent): void {
    this.trackEvent('cta_click', {
      action: event.action,
      section: event.section,
      audience: event.audience,
      ctaText: event.ctaText,
      ctaPosition: event.ctaPosition,
      ctaType: event.ctaType,
      targetUrl: event.targetUrl,
      timestamp: new Date(),
      ...event.additionalData
    })

    // Track click coordinates for heat mapping
    if (event.clickCoordinates) {
      this.trackEvent('click_heatmap', {
        x: event.clickCoordinates.x,
        y: event.clickCoordinates.y,
        element: event.section,
        action: event.action,
        timestamp: new Date()
      })
    }
  }

  public trackFormSubmission(event: FormSubmissionEvent): void {
    this.trackEvent('form_submission', {
      formType: event.formType,
      formId: event.formId,
      success: event.success,
      errorMessage: event.errorMessage,
      formData: this.sanitizeFormData(event.formData),
      validationErrors: event.validationErrors,
      timeToComplete: event.timeToComplete,
      fieldInteractions: event.fieldInteractions,
      timestamp: new Date()
    })

    // Track form abandonment if not successful
    if (!event.success && event.abandonmentPoint) {
      this.trackEvent('form_abandonment', {
        formType: event.formType,
        abandonmentPoint: event.abandonmentPoint,
        completedFields: event.completedFields,
        timeSpent: event.timeToComplete,
        timestamp: new Date()
      })
    }
  }

  public trackCalculatorUsage(event: CalculatorUsageEvent): void {
    this.trackEvent('calculator_usage', {
      step: event.step,
      totalSteps: event.totalSteps,
      completed: event.completed,
      calculatorData: this.sanitizeFormData(event.calculatorData),
      timeSpent: event.timeSpent,
      backtrackCount: event.backtrackCount,
      helpUsed: event.helpUsed,
      timestamp: new Date()
    })

    // Track calculator funnel
    this.trackEvent('calculator_funnel', {
      step: event.step,
      stepName: event.stepName,
      completed: event.completed,
      dropoffPoint: !event.completed && event.step < event.totalSteps,
      timestamp: new Date()
    })
  }

  public trackScrollDepth(event: ScrollTrackingEvent): void {
    const milestone = Math.floor(event.percentage / 25) * 25
    
    // Only track significant milestones and avoid duplicates
    if ([25, 50, 75, 100].includes(milestone) && !this.scrollMilestones.has(milestone)) {
      this.scrollMilestones.add(milestone)
      
      this.trackEvent('scroll_depth', {
        percentage: milestone,
        section: event.section,
        page: this.currentPage,
        timeToReach: Date.now() - this.pageViewStartTime,
        scrollDirection: event.scrollDirection,
        timestamp: new Date()
      })
    }

    // Track continuous scroll behavior
    this.trackEvent('scroll_behavior', {
      percentage: event.percentage,
      section: event.section,
      scrollSpeed: event.scrollSpeed,
      scrollDirection: event.scrollDirection,
      timestamp: new Date()
    }, false) // Don't queue immediately for performance
  }

  public trackTimeOnSection(section: string, duration: number): void {
    this.trackEvent('time_on_section', {
      section,
      duration,
      page: this.currentPage,
      engagementLevel: this.calculateEngagementLevel(duration),
      timestamp: new Date()
    })
  }

  public trackUserBehavior(behaviorType: string, data: UserBehaviorEvent): void {
    this.trackEvent('user_behavior', {
      behaviorType,
      element: data.element,
      action: data.action,
      value: data.value,
      coordinates: data.coordinates,
      deviceType: this.getDeviceType(),
      timestamp: new Date(),
      ...data.customData
    })
  }

  public trackConversion(goalId: string, value?: number, additionalData?: Record<string, any>): void {
    this.trackEvent('conversion', {
      goalId,
      value,
      conversionPath: this.getConversionPath(),
      timeToConversion: Date.now() - this.sessionStartTime,
      touchpoints: this.getTouchpoints(),
      timestamp: new Date(),
      ...additionalData
    })

    // Send immediate high-priority conversion event
    this.sendImmediateEvent('conversion', {
      goalId,
      value,
      sessionId: this.sessionId,
      userId: this.userId,
      audience: this.audience,
      timestamp: new Date().toISOString()
    })
  }

  public trackError(errorType: string, errorData: Record<string, any>): void {
    this.trackEvent('error', {
      errorType,
      page: this.currentPage,
      userAgent: navigator.userAgent,
      timestamp: new Date(),
      ...errorData
    })

    // Send error immediately
    this.sendImmediateEvent('error', {
      errorType,
      errorData,
      sessionId: this.sessionId,
      timestamp: new Date().toISOString()
    })
  }

  public trackCustomEvent(eventName: string, data: Record<string, any>): void {
    this.trackEvent('custom_event', {
      eventName,
      page: this.currentPage,
      timestamp: new Date(),
      ...data
    })
  }

  // Analytics and reporting methods

  public async getAnalyticsMetrics(timeRange?: { start: Date; end: Date }): Promise<AnalyticsMetrics | null> {
    try {
      const response = await fetch(`${this.config.apiEndpoint}/metrics`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': this.sessionId
        },
        body: JSON.stringify({
          audience: this.audience,
          userId: this.userId,
          sessionId: this.sessionId,
          timeRange
        })
      })

      if (response.ok) {
        return await response.json()
      }
    } catch (error) {
      console.error('Failed to get analytics metrics:', error)
    }
    return null
  }

  public async generateReport(reportType: string, options?: Record<string, any>): Promise<any> {
    try {
      const response = await fetch(`${this.config.apiEndpoint}/reports/${reportType}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': this.sessionId
        },
        body: JSON.stringify({
          audience: this.audience,
          userId: this.userId,
          sessionId: this.sessionId,
          ...options
        })
      })

      if (response.ok) {
        return await response.json()
      }
    } catch (error) {
      console.error('Failed to generate report:', error)
    }
    return null
  }

  public async exportData(format: 'csv' | 'json' = 'json', filters?: Record<string, any>): Promise<boolean> {
    try {
      const response = await fetch(`${this.config.apiEndpoint}/export`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': this.sessionId
        },
        body: JSON.stringify({
          format,
          filters,
          audience: this.audience,
          userId: this.userId,
          sessionId: this.sessionId
        })
      })

      if (response.ok) {
        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `analytics-export-${Date.now()}.${format}`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
        return true
      }
    } catch (error) {
      console.error('Failed to export data:', error)
    }
    return false
  }

  // Utility methods

  public setUserId(userId: string): void {
    this.userId = userId
  }

  public updateAudience(audience: AudienceType): void {
    this.audience = audience
  }

  public getSessionId(): string {
    return this.sessionId
  }

  public getSessionDuration(): number {
    return Date.now() - this.sessionStartTime
  }

  public isActive(): boolean {
    return Date.now() - this.lastActivityTime < 30000 // 30 seconds
  }

  // Private helper methods

  private trackEvent(eventName: string, data: Record<string, any>, queue: boolean = true): void {
    const event: AnalyticsEvent = {
      eventName,
      audience: this.audience,
      section: data.section || 'unknown',
      action: data.action || eventName,
      value: data.value,
      customData: {
        sessionId: this.sessionId,
        userId: this.userId,
        sessionDuration: this.getSessionDuration(),
        isActive: this.isActive(),
        ...data
      },
      timestamp: data.timestamp || new Date()
    }

    if (queue) {
      this.eventQueue.push(event)

      if (this.config.enableDebugMode) {
        console.log('Analytics event tracked:', event)
      }

      // Flush immediately for high-priority events or when batch is full
      if (this.isHighPriorityEvent(eventName) || this.eventQueue.length >= this.config.batchSize) {
        this.flushEventQueue()
      }
    }
  }

  private isHighPriorityEvent(eventName: string): boolean {
    const highPriorityEvents = ['conversion', 'error', 'form_submission', 'cta_click']
    return highPriorityEvents.includes(eventName)
  }

  private async flushEventQueue(synchronous: boolean = false): Promise<void> {
    if (this.eventQueue.length === 0) return

    const events = [...this.eventQueue]
    this.eventQueue = []

    if (!this.isOnline && this.config.enableOfflineStorage) {
      this.storeOfflineEvents(events)
      return
    }

    try {
      if (synchronous) {
        // Use sendBeacon for synchronous sending (page unload)
        const data = JSON.stringify({ events, sessionId: this.sessionId })
        navigator.sendBeacon(`${this.config.apiEndpoint}/events`, data)
      } else {
        // Use fetch for regular async sending
        await fetch(`${this.config.apiEndpoint}/events`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Session-ID': this.sessionId
          },
          body: JSON.stringify({ events, sessionId: this.sessionId })
        })
      }

      if (this.config.enableDebugMode) {
        console.log('Analytics events sent:', events.length)
      }
    } catch (error) {
      console.error('Failed to send analytics events:', error)
      
      // Re-queue events for retry
      this.eventQueue.unshift(...events)
      
      // Store offline if enabled
      if (this.config.enableOfflineStorage) {
        this.storeOfflineEvents(events)
      }
    }
  }

  private async sendImmediateEvent(eventType: string, data: Record<string, any>): Promise<void> {
    try {
      await fetch(`${this.config.apiEndpoint}/${eventType}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': this.sessionId
        },
        body: JSON.stringify(data)
      })
    } catch (error) {
      console.error(`Failed to send immediate ${eventType} event:`, error)
    }
  }

  private storeOfflineEvents(events: AnalyticsEvent[]): void {
    try {
      const existingEvents = localStorage.getItem('analytics_offline_events')
      const allEvents = existingEvents ? JSON.parse(existingEvents) : []
      allEvents.push(...events)
      
      // Limit offline storage to prevent memory issues
      const maxOfflineEvents = 1000
      if (allEvents.length > maxOfflineEvents) {
        allEvents.splice(0, allEvents.length - maxOfflineEvents)
      }
      
      localStorage.setItem('analytics_offline_events', JSON.stringify(allEvents))
    } catch (error) {
      console.error('Failed to store offline events:', error)
    }
  }

  private sanitizeFormData(formData?: Record<string, any>): Record<string, any> | undefined {
    if (!formData) return undefined

    const sensitiveFields = ['password', 'ssn', 'creditCard', 'phone', 'email']
    const sanitized = { ...formData }

    sensitiveFields.forEach(field => {
      if (sanitized[field]) {
        sanitized[field] = '[REDACTED]'
      }
    })

    return sanitized
  }

  private trackPagePerformance(page: string): void {
    if (typeof window !== 'undefined' && 'performance' in window) {
      // Wait for performance data to be available
      setTimeout(() => {
        const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
        
        if (navigation) {
          this.trackEvent('page_performance', {
            page,
            loadTime: navigation.loadEventEnd - navigation.loadEventStart,
            domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
            firstContentfulPaint: this.getPerformanceMetric('first-contentful-paint'),
            largestContentfulPaint: this.getPerformanceMetric('largest-contentful-paint'),
            cumulativeLayoutShift: this.getPerformanceMetric('layout-shift'),
            firstInputDelay: this.getPerformanceMetric('first-input'),
            timestamp: new Date()
          })
        }
      }, 1000)
    }
  }

  private getPerformanceMetric(metricName: string): number {
    const entries = performance.getEntriesByName(metricName)
    return entries.length > 0 ? entries[0].startTime : 0
  }

  private trackPageExit(): void {
    if (this.currentPage) {
      const timeOnPage = Date.now() - this.pageViewStartTime
      
      this.trackEvent('page_exit', {
        page: this.currentPage,
        timeOnPage,
        maxScrollDepth: Math.max(...Array.from(this.scrollMilestones), 0),
        exitType: document.visibilityState === 'hidden' ? 'visibility_change' : 'navigation',
        timestamp: new Date()
      })
    }
  }

  private trackPageResume(): void {
    if (this.currentPage) {
      this.trackEvent('page_resume', {
        page: this.currentPage,
        timestamp: new Date()
      })
    }
  }

  private calculateEngagementLevel(duration: number): 'low' | 'medium' | 'high' {
    if (duration < 5000) return 'low'
    if (duration < 30000) return 'medium'
    return 'high'
  }

  private getDeviceType(): 'mobile' | 'tablet' | 'desktop' {
    const width = window.innerWidth
    if (width < 768) return 'mobile'
    if (width < 1024) return 'tablet'
    return 'desktop'
  }

  private getConversionPath(): string[] {
    // This would track the user's journey through the site
    // For now, return a simple path based on current page
    return [this.currentPage]
  }

  private getTouchpoints(): string[] {
    // This would track all the touchpoints in the user's journey
    // For now, return basic touchpoints
    return ['homepage', 'features', 'pricing']
  }

  public destroy(): void {
    this.flushEventQueue(true)
    
    if (this.flushTimer) {
      clearInterval(this.flushTimer)
    }

    // Remove event listeners
    window.removeEventListener('online', () => {})
    window.removeEventListener('offline', () => {})
    document.removeEventListener('visibilitychange', () => {})
    window.removeEventListener('beforeunload', () => {})
    window.removeEventListener('error', () => {})
    window.removeEventListener('unhandledrejection', () => {})
  }
}