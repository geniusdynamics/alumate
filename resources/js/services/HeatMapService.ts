import type { 
  HeatMapData, 
  HeatMapClick, 
  HeatMapScroll, 
  HeatMapTimeSpent, 
  CTAPerformanceData,
  AudienceType,
  UserBehaviorEvent
} from '@/types/homepage'

export class HeatMapService {
  private sessionId: string
  private userId?: string
  private audience: AudienceType
  private clickData: HeatMapClick[] = []
  private scrollData: HeatMapScroll[] = []
  private timeSpentData: HeatMapTimeSpent[] = []
  private ctaPerformanceData: CTAPerformanceData[] = []
  private isRecording: boolean = false
  private startTime: number = Date.now()
  private lastScrollTime: number = Date.now()
  private sectionStartTimes: Map<string, number> = new Map()
  private batchSize: number = 20
  private flushInterval: number = 10000 // 10 seconds

  constructor(sessionId: string, audience: AudienceType, userId?: string) {
    this.sessionId = sessionId
    this.userId = userId
    this.audience = audience
    this.initializeTracking()
  }

  private initializeTracking(): void {
    this.setupClickTracking()
    this.setupScrollTracking()
    this.setupTimeTracking()
    this.setupCTATracking()
    this.startBatchProcessing()
    this.isRecording = true
  }

  private setupClickTracking(): void {
    document.addEventListener('click', (event) => {
      if (!this.isRecording) return

      const target = event.target as Element
      const rect = target.getBoundingClientRect()
      const viewportWidth = window.innerWidth
      const viewportHeight = window.innerHeight

      // Get element information
      const elementInfo = this.getElementInfo(target)
      
      // Calculate relative position
      const relativeX = (event.clientX / viewportWidth) * 100
      const relativeY = (event.clientY / viewportHeight) * 100

      const clickData: HeatMapClick = {
        x: event.clientX,
        y: event.clientY,
        relativeX,
        relativeY,
        element: elementInfo.tagName,
        elementId: elementInfo.id,
        elementClass: elementInfo.className,
        elementText: elementInfo.text,
        section: this.getCurrentSection(event.clientY),
        timestamp: Date.now(),
        viewportWidth,
        viewportHeight,
        pageUrl: window.location.pathname,
        audience: this.audience
      }

      this.clickData.push(clickData)
      this.checkBatchSize()
    }, { passive: true })
  }

  private setupScrollTracking(): void {
    let scrollTimeout: NodeJS.Timeout

    window.addEventListener('scroll', () => {
      if (!this.isRecording) return

      clearTimeout(scrollTimeout)
      scrollTimeout = setTimeout(() => {
        const scrollY = window.pageYOffset
        const documentHeight = document.documentElement.scrollHeight
        const viewportHeight = window.innerHeight
        const scrollPercentage = (scrollY / (documentHeight - viewportHeight)) * 100

        const scrollData: HeatMapScroll = {
          scrollY,
          scrollPercentage: Math.min(Math.max(scrollPercentage, 0), 100),
          timestamp: Date.now(),
          timeSinceLastScroll: Date.now() - this.lastScrollTime,
          viewportHeight,
          documentHeight,
          pageUrl: window.location.pathname,
          audience: this.audience
        }

        this.scrollData.push(scrollData)
        this.lastScrollTime = Date.now()
        this.checkBatchSize()
      }, 100)
    }, { passive: true })
  }

  private setupTimeTracking(): void {
    // Track time spent in different sections
    const observer = new IntersectionObserver((entries) => {
      if (!this.isRecording) return

      entries.forEach(entry => {
        const sectionId = entry.target.getAttribute('data-section') || 'unknown'
        
        if (entry.isIntersecting) {
          // Section entered viewport
          this.sectionStartTimes.set(sectionId, Date.now())
        } else {
          // Section left viewport
          const startTime = this.sectionStartTimes.get(sectionId)
          if (startTime) {
            const timeSpent = Date.now() - startTime
            
            const timeSpentData: HeatMapTimeSpent = {
              section: sectionId,
              timeSpent,
              timestamp: Date.now(),
              pageUrl: window.location.pathname,
              audience: this.audience
            }

            this.timeSpentData.push(timeSpentData)
            this.sectionStartTimes.delete(sectionId)
            this.checkBatchSize()
          }
        }
      })
    }, {
      threshold: 0.5 // Track when 50% of section is visible
    })

    // Observe all sections
    document.querySelectorAll('[data-section]').forEach(section => {
      observer.observe(section)
    })

    // Track page visibility changes
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        this.flushTimeSpentData()
      }
    })
  }

  private setupCTATracking(): void {
    // Track CTA interactions and performance
    document.addEventListener('click', (event) => {
      if (!this.isRecording) return

      const target = event.target as Element
      const ctaElement = target.closest('[data-cta]') || target.closest('button') || target.closest('a')
      
      if (ctaElement) {
        const ctaInfo = this.getCTAInfo(ctaElement)
        const rect = ctaElement.getBoundingClientRect()
        
        const ctaData: CTAPerformanceData = {
          ctaId: ctaInfo.id,
          ctaText: ctaInfo.text,
          ctaType: ctaInfo.type,
          section: this.getCurrentSection(rect.top + rect.height / 2),
          position: {
            x: rect.left + rect.width / 2,
            y: rect.top + rect.height / 2,
            relativeX: ((rect.left + rect.width / 2) / window.innerWidth) * 100,
            relativeY: ((rect.top + rect.height / 2) / window.innerHeight) * 100
          },
          timestamp: Date.now(),
          pageUrl: window.location.pathname,
          audience: this.audience,
          viewportWidth: window.innerWidth,
          viewportHeight: window.innerHeight
        }

        this.ctaPerformanceData.push(ctaData)
        this.checkBatchSize()
      }
    }, { passive: true })
  }

  private startBatchProcessing(): void {
    setInterval(() => {
      this.flushData()
    }, this.flushInterval)
  }

  private checkBatchSize(): void {
    const totalData = this.clickData.length + this.scrollData.length + 
                     this.timeSpentData.length + this.ctaPerformanceData.length
    
    if (totalData >= this.batchSize) {
      this.flushData()
    }
  }

  private flushTimeSpentData(): void {
    // Flush any remaining time spent data for sections still in view
    this.sectionStartTimes.forEach((startTime, sectionId) => {
      const timeSpent = Date.now() - startTime
      
      const timeSpentData: HeatMapTimeSpent = {
        section: sectionId,
        timeSpent,
        timestamp: Date.now(),
        pageUrl: window.location.pathname,
        audience: this.audience
      }

      this.timeSpentData.push(timeSpentData)
    })
    
    this.sectionStartTimes.clear()
  }

  // Public Methods

  public startRecording(): void {
    this.isRecording = true
    this.startTime = Date.now()
  }

  public stopRecording(): void {
    this.isRecording = false
    this.flushTimeSpentData()
    this.flushData()
  }

  public pauseRecording(): void {
    this.isRecording = false
  }

  public resumeRecording(): void {
    this.isRecording = true
  }

  public trackCustomEvent(eventType: string, data: UserBehaviorEvent): void {
    if (!this.isRecording) return

    // Add custom event to appropriate data collection
    if (eventType === 'cta_hover') {
      // Track CTA hover events
      const ctaData: CTAPerformanceData = {
        ctaId: data.elementId || 'unknown',
        ctaText: data.elementText || '',
        ctaType: 'hover',
        section: data.section || 'unknown',
        position: {
          x: data.x || 0,
          y: data.y || 0,
          relativeX: data.relativeX || 0,
          relativeY: data.relativeY || 0
        },
        timestamp: Date.now(),
        pageUrl: window.location.pathname,
        audience: this.audience,
        viewportWidth: window.innerWidth,
        viewportHeight: window.innerHeight
      }

      this.ctaPerformanceData.push(ctaData)
    }
  }

  public getHeatMapData(): HeatMapData {
    return {
      clicks: [...this.clickData],
      scrollDepth: [...this.scrollData],
      timeSpent: [...this.timeSpentData],
      ctaPerformance: [...this.ctaPerformanceData]
    }
  }

  public async generateHeatMapReport(): Promise<any> {
    try {
      const response = await fetch('/api/analytics/heatmap/report', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': this.sessionId
        },
        body: JSON.stringify({
          audience: this.audience,
          pageUrl: window.location.pathname,
          timeRange: {
            start: this.startTime,
            end: Date.now()
          }
        })
      })

      if (response.ok) {
        return await response.json()
      }
    } catch (error) {
      console.error('Failed to generate heat map report:', error)
    }
    return null
  }

  public clearData(): void {
    this.clickData = []
    this.scrollData = []
    this.timeSpentData = []
    this.ctaPerformanceData = []
  }

  // Private Helper Methods

  private getElementInfo(element: Element): {
    tagName: string
    id: string
    className: string
    text: string
  } {
    return {
      tagName: element.tagName.toLowerCase(),
      id: element.id || '',
      className: element.className || '',
      text: element.textContent?.trim().substring(0, 100) || ''
    }
  }

  private getCTAInfo(element: Element): {
    id: string
    text: string
    type: string
  } {
    const ctaId = element.getAttribute('data-cta') || 
                  element.getAttribute('id') || 
                  element.className || 
                  'unknown'
    
    const ctaText = element.textContent?.trim() || 
                    element.getAttribute('aria-label') || 
                    element.getAttribute('title') || 
                    ''

    const ctaType = element.tagName.toLowerCase() === 'button' ? 'button' :
                    element.tagName.toLowerCase() === 'a' ? 'link' :
                    'other'

    return {
      id: ctaId,
      text: ctaText.substring(0, 50),
      type: ctaType
    }
  }

  private getCurrentSection(y: number): string {
    const sections = document.querySelectorAll('[data-section]')
    
    for (const section of sections) {
      const rect = section.getBoundingClientRect()
      if (y >= rect.top && y <= rect.bottom) {
        return section.getAttribute('data-section') || 'unknown'
      }
    }
    
    return 'unknown'
  }

  private async flushData(): Promise<void> {
    if (this.clickData.length === 0 && this.scrollData.length === 0 && 
        this.timeSpentData.length === 0 && this.ctaPerformanceData.length === 0) {
      return
    }

    const data = {
      sessionId: this.sessionId,
      userId: this.userId,
      audience: this.audience,
      pageUrl: window.location.pathname,
      timestamp: Date.now(),
      clicks: [...this.clickData],
      scrollData: [...this.scrollData],
      timeSpent: [...this.timeSpentData],
      ctaPerformance: [...this.ctaPerformanceData]
    }

    // Clear data arrays
    this.clickData = []
    this.scrollData = []
    this.timeSpentData = []
    this.ctaPerformanceData = []

    try {
      await fetch('/api/analytics/heatmap', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': this.sessionId
        },
        body: JSON.stringify(data)
      })
    } catch (error) {
      console.error('Failed to send heat map data:', error)
      // Re-add data for retry (simplified - in production you'd want better retry logic)
      this.clickData.push(...data.clicks)
      this.scrollData.push(...data.scrollData)
      this.timeSpentData.push(...data.timeSpent)
      this.ctaPerformanceData.push(...data.ctaPerformance)
    }
  }

  // Integration with third-party heat mapping tools

  public integrateHotjar(hotjarId: string): void {
    if (typeof window !== 'undefined' && !window.hj) {
      (function(h: any, o: any, t: any, j: any, a?: any, r?: any) {
        h.hj = h.hj || function() { (h.hj.q = h.hj.q || []).push(arguments) }
        h._hjSettings = { hjid: hotjarId, hjsv: 6 }
        a = o.getElementsByTagName('head')[0]
        r = o.createElement('script')
        r.async = 1
        r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv
        a.appendChild(r)
      })(window, document, 'https://static.hotjar.com/c/hotjar-', '.js?sv=')
    }
  }

  public integrateCrazyEgg(crazyEggId: string): void {
    if (typeof window !== 'undefined') {
      const script = document.createElement('script')
      script.type = 'text/javascript'
      script.src = `//script.crazyegg.com/pages/scripts/${crazyEggId}.js`
      script.async = true
      document.head.appendChild(script)
    }
  }

  public integrateFullStory(orgId: string): void {
    if (typeof window !== 'undefined' && !window.FS) {
      (function(m: any, n: any, e: any, t: any, l: any, o: any, g: any, y: any) {
        if (e in m) { if (m.console && m.console.log) { m.console.log('FullStory namespace conflict. Please set window["_fs_namespace"].') } return }
        g = m[e] = function(a: any, b: any, s?: any) { g.q ? g.q.push([a, b, s]) : g._api(a, b, s) }
        g.q = []
        o = n.createElement(t)
        o.async = 1
        o.crossOrigin = 'anonymous'
        o.src = 'https://edge.fullstory.com/s/fs.js'
        y = n.getElementsByTagName(t)[0]
        y.parentNode.insertBefore(o, y)
        g.identify = function(i: any, v?: any, s?: any) { g(l, { uid: i }, s); if (v) g(l, v, s) }
        g.setUserVars = function(v: any, s?: any) { g(l, v, s) }
        g.event = function(i: any, v?: any, s?: any) { g('event', { n: i, p: v }, s) }
        g.anonymize = function() { g.identify(false) }
        g.shutdown = function() { g('rec', false) }
        g.restart = function() { g('rec', true) }
        g.log = function(a: any, s?: any) { g('log', [a], s) }
        g.consent = function(a: any) { g('consent', !arguments.length || a) }
        g.identifyAccount = function(i: any, v?: any) { o = 'account'; v = v || {}; v.acctId = i; g(o, v) }
        g.clearUserCookie = function() {}
        g.setVars = function(n: any, p: any) { g('setVars', [n, p]) }
        g._w = {}
        y = 'XMLHttpRequest'
        g._w[y] = m[y]
        y = 'fetch'
        g._w[y] = m[y]
        if (m[y]) m[y] = function() { return g._w[y].apply(this, arguments) }
        g._v = '1.3.0'
      })(window, document, 'FS', 'script', 'user')

      window.FS.identify(orgId)
    }
  }

  // Cleanup

  public destroy(): void {
    this.stopRecording()
    this.clearData()
  }
}