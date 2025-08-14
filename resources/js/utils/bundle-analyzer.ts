/**
 * Bundle Analysis and Performance Monitoring Tools
 */

interface BundleInfo {
  name: string
  size: number
  gzipSize?: number
  loadTime?: number
  isLoaded: boolean
  isPreloaded: boolean
}

interface PerformanceMetrics {
  bundleSize: number
  loadTime: number
  renderTime: number
  interactiveTime: number
  memoryUsage: number
  networkRequests: number
}

class BundleAnalyzer {
  private bundles: Map<string, BundleInfo> = new Map()
  private performanceObserver: PerformanceObserver | null = null
  private resourceObserver: PerformanceObserver | null = null

  constructor() {
    this.initializeObservers()
    this.trackInitialBundles()
  }

  private initializeObservers(): void {
    // Track navigation and resource timing
    if ('PerformanceObserver' in window) {
      this.performanceObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          if (entry.entryType === 'navigation') {
            this.trackNavigationTiming(entry as PerformanceNavigationTiming)
          } else if (entry.entryType === 'resource') {
            this.trackResourceTiming(entry as PerformanceResourceTiming)
          }
        }
      })

      this.performanceObserver.observe({ 
        entryTypes: ['navigation', 'resource', 'measure', 'mark'] 
      })
    }
  }

  private trackInitialBundles(): void {
    // Track script tags that are already loaded
    const scripts = document.querySelectorAll('script[src]')
    scripts.forEach((script) => {
      const src = (script as HTMLScriptElement).src
      if (src.includes('/build/') || src.includes('/assets/')) {
        this.trackBundle(this.extractBundleName(src), {
          name: this.extractBundleName(src),
          size: 0, // Will be updated by resource timing
          isLoaded: true,
          isPreloaded: false
        })
      }
    })
  }

  private trackNavigationTiming(entry: PerformanceNavigationTiming): void {
    const metrics = {
      domContentLoaded: entry.domContentLoadedEventEnd - entry.domContentLoadedEventStart,
      loadComplete: entry.loadEventEnd - entry.loadEventStart,
      firstPaint: this.getFirstPaint(),
      firstContentfulPaint: this.getFirstContentfulPaint(),
      largestContentfulPaint: this.getLargestContentfulPaint()
    }

    this.reportMetrics('navigation', metrics)
  }

  private trackResourceTiming(entry: PerformanceResourceTiming): void {
    if (entry.name.includes('/build/') || entry.name.includes('/assets/')) {
      const bundleName = this.extractBundleName(entry.name)
      const bundleInfo: BundleInfo = {
        name: bundleName,
        size: entry.transferSize || entry.encodedBodySize,
        loadTime: entry.responseEnd - entry.requestStart,
        isLoaded: true,
        isPreloaded: entry.name.includes('preload')
      }

      this.trackBundle(bundleName, bundleInfo)
    }
  }

  private extractBundleName(url: string): string {
    const parts = url.split('/')
    const filename = parts[parts.length - 1]
    return filename.split('-')[0] || filename.split('.')[0]
  }

  private getFirstPaint(): number {
    const paintEntries = performance.getEntriesByType('paint')
    const firstPaint = paintEntries.find(entry => entry.name === 'first-paint')
    return firstPaint?.startTime || 0
  }

  private getFirstContentfulPaint(): number {
    const paintEntries = performance.getEntriesByType('paint')
    const fcp = paintEntries.find(entry => entry.name === 'first-contentful-paint')
    return fcp?.startTime || 0
  }

  private getLargestContentfulPaint(): number {
    const lcpEntries = performance.getEntriesByType('largest-contentful-paint')
    const lcp = lcpEntries[lcpEntries.length - 1] as any
    return lcp?.startTime || 0
  }

  public trackBundle(name: string, info: BundleInfo): void {
    this.bundles.set(name, info)
    this.reportBundleLoad(name, info)
  }

  public getBundleInfo(name: string): BundleInfo | undefined {
    return this.bundles.get(name)
  }

  public getAllBundles(): BundleInfo[] {
    return Array.from(this.bundles.values())
  }

  public getTotalBundleSize(): number {
    return Array.from(this.bundles.values())
      .reduce((total, bundle) => total + bundle.size, 0)
  }

  public getPerformanceMetrics(): PerformanceMetrics {
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
    
    return {
      bundleSize: this.getTotalBundleSize(),
      loadTime: navigation ? navigation.loadEventEnd - navigation.navigationStart : 0,
      renderTime: this.getFirstContentfulPaint(),
      interactiveTime: navigation ? navigation.domInteractive - navigation.navigationStart : 0,
      memoryUsage: this.getMemoryUsage(),
      networkRequests: performance.getEntriesByType('resource').length
    }
  }

  private getMemoryUsage(): number {
    // @ts-ignore - memory API is not in all browsers
    return (performance as any).memory?.usedJSHeapSize || 0
  }

  private reportBundleLoad(name: string, info: BundleInfo): void {
    if (process.env.NODE_ENV === 'development') {
      console.log(`Bundle loaded: ${name}`, {
        size: `${(info.size / 1024).toFixed(2)}KB`,
        loadTime: info.loadTime ? `${info.loadTime.toFixed(2)}ms` : 'N/A'
      })
    }

    // Send to analytics if configured
    this.sendToAnalytics('bundle_load', {
      bundle_name: name,
      bundle_size: info.size,
      load_time: info.loadTime
    })
  }

  private reportMetrics(type: string, metrics: any): void {
    if (process.env.NODE_ENV === 'development') {
      console.log(`Performance metrics (${type}):`, metrics)
    }

    this.sendToAnalytics('performance_metrics', {
      type,
      ...metrics
    })
  }

  private sendToAnalytics(event: string, data: any): void {
    // Send to your analytics service
    if (window.gtag) {
      window.gtag('event', event, data)
    }

    // Send to custom analytics endpoint
    if (navigator.sendBeacon) {
      navigator.sendBeacon('/api/analytics/performance', JSON.stringify({
        event,
        data,
        timestamp: Date.now(),
        url: window.location.href
      }))
    }
  }

  public generateReport(): string {
    const bundles = this.getAllBundles()
    const metrics = this.getPerformanceMetrics()
    
    let report = '# Bundle Analysis Report\n\n'
    
    report += '## Bundle Information\n'
    bundles.forEach(bundle => {
      report += `- **${bundle.name}**: ${(bundle.size / 1024).toFixed(2)}KB`
      if (bundle.loadTime) {
        report += ` (${bundle.loadTime.toFixed(2)}ms)`
      }
      report += '\n'
    })
    
    report += '\n## Performance Metrics\n'
    report += `- Total Bundle Size: ${(metrics.bundleSize / 1024).toFixed(2)}KB\n`
    report += `- Load Time: ${metrics.loadTime.toFixed(2)}ms\n`
    report += `- First Contentful Paint: ${metrics.renderTime.toFixed(2)}ms\n`
    report += `- Time to Interactive: ${metrics.interactiveTime.toFixed(2)}ms\n`
    report += `- Memory Usage: ${(metrics.memoryUsage / 1024 / 1024).toFixed(2)}MB\n`
    report += `- Network Requests: ${metrics.networkRequests}\n`
    
    return report
  }

  public destroy(): void {
    if (this.performanceObserver) {
      this.performanceObserver.disconnect()
    }
    if (this.resourceObserver) {
      this.resourceObserver.disconnect()
    }
  }
}

// Global instance
export const bundleAnalyzer = new BundleAnalyzer()

// Export for manual usage
export { BundleAnalyzer, type BundleInfo, type PerformanceMetrics }