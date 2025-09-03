/**
 * Core Web Vitals Performance Monitoring Service
 *
 * Tracks and monitors Core Web Vitals metrics (LCP, FID, CLS)
 * with component-specific performance insights.
 */

interface WebVitalsMetric {
  name: string;
  value: number;
  timestamp: number;
  componentId?: string;
  entry?: PerformanceEntry;
  navigationType?: string;
}

interface CoreWebVitalsData {
  lcp?: WebVitalsMetric;
  fid?: WebVitalsMetric;
  cls?: WebVitalsMetric;
  fcp?: WebVitalsMetric;
  ttfb?: WebVitalsMetric;
}

interface PerformanceBudget {
  lcp: number;
  fid: number;
  cls: number;
  fcp: number;
  ttfb: number;
}

class WebVitalsService {
  private metrics: CoreWebVitalsData = {};
  private observers: PerformanceObserver[] = [];
  private componentObservers: Map<string, PerformanceObserver> = new Map();
  private budgets: PerformanceBudget = {
    lcp: 2500, // ms
    fid: 100,  // ms
    cls: 0.1,  // score
    fcp: 1800, // ms
    ttfb: 800, // ms
  };

  constructor() {
    this.initializeObservers();
  }

  /**
   * Initialize performance observers for Core Web Vitals
   */
  private initializeObservers(): void {
    // Largest Contentful Paint (LCP)
    this.addObserver('LCP', (entry: PerformanceEntry) => {
      this.handleLCPStrategy(entry);
    });

    // First Input Delay (FID)
    this.addObserver('FID', (entry: PerformanceEntry) => {
      this.handleFIDStrategy(entry);
    });

    // Cumulative Layout Shift (CLS)
    this.addObserver('CLS', (entry: PerformanceEntry) => {
      this.handleCLSStrategy(entry);
    });

    // First Contentful Paint (FCP)
    this.addObserver('FCP', (entry: PerformanceEntry) => {
      this.handleFCPStrategy(entry);
    });

    // Time to First Byte (TTFB)
    this.addObserver('TTFB', (entry: PerformanceEntry) => {
      this.handleTTFBStrategy(entry);
    });

    // Additional navigation timing metrics
    this.addNavigationTimingObserver();
  }

  /**
   * Add performance observer for specific metric
   */
  private addObserver(type: string, callback: (entry: PerformanceEntry) => void): void {
    if ('PerformanceObserver' in window) {
      try {
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();

          for (const entry of entries) {
            callback(entry);
          }

          // Disconnect after first measurement for single-value metrics
          if (['FCP', 'TTFB', 'FID'].includes(type)) {
            observer.disconnect();
          }
        });

        const entryTypes = {
          'LCP': 'largest-contentful-paint',
          'FID': 'first-input',
          'CLS': 'layout-shift',
          'FCP': 'paint',
          'TTFB': 'navigation',
        };

        observer.observe({
          entryTypes: [entryTypes[type as keyof typeof entryTypes]],
          buffered: true
        });

        this.observers.push(observer);
      } catch (error) {
        console.warn(`Failed to observe ${type}:`, error);
      }
    } else {
      console.warn('PerformanceObserver not supported');
    }
  }

  /**
   * Handle LCP measurement
   */
  private handleLCPStrategy(entry: PerformanceEntry): void {
    const lcpOwnedEntry = entry as any;
    const startTime = lcpOwnedEntry.startTime;

    const metric: WebVitalsMetric = {
      name: 'LCP',
      value: startTime,
      timestamp: Date.now(),
      entry: entry,
      navigationType: this.getNavigationType(),
    };

    this.metrics.lcp = metric;
    this.checkAgainstBudget(metric);
    this.sendToAnalytics('LCP', startTime);
  }

  /**
   * Handle FID measurement
   */
  private handleFIDStrategy(entry: PerformanceEntry): void {
    const fidEntry = entry as any;
    const duration = fidEntry.processingStart - fidEntry.startTime;

    const metric: WebVitalsMetric = {
      name: 'FID',
      value: duration,
      timestamp: Date.now(),
      entry: entry,
      navigationType: this.getNavigationType(),
    };

    this.metrics.fid = metric;
    this.checkAgainstBudget(metric);
    this.sendToAnalytics('FID', duration);
  }

  /**
   * Handle CLS measurement
   */
  private handleCLSStrategy(entry: PerformanceEntry): void {
    const clsEntry = entry as any;
    const value = clsEntry.value;

    if (!this.metrics.cls) {
      this.metrics.cls = {
        name: 'CLS',
        value: 0,
        timestamp: Date.now(),
        navigationType: this.getNavigationType(),
      };
    }

    this.metrics.cls.value += value;
    this.checkAgainstBudget(this.metrics.cls);
  }

  /**
   * Handle FCP measurement
   */
  private handleFCPStrategy(entry: PerformanceEntry): void {
    if (entry.name === 'first-contentful-paint') {
      const fcpEntry = entry as any;
      const startTime = fcpEntry.startTime;

      const metric: WebVitalsMetric = {
        name: 'FCP',
        value: startTime,
        timestamp: Date.now(),
        entry: entry,
        navigationType: this.getNavigationType(),
      };

      this.metrics.fcp = metric;
      this.checkAgainstBudget(metric);
      this.sendToAnalytics('FCP', startTime);
    }
  }

  /**
   * Handle TTFB measurement
   */
  private handleTTFBStrategy(entry: PerformanceEntry): void {
    const navigationEntry = entry as any;
    const requestStart = navigationEntry.requestStart;
    const responseStart = navigationEntry.responseStart;
    const ttfb = responseStart - requestStart;

    const metric: WebVitalsMetric = {
      name: 'TTFB',
      value: ttfb,
      timestamp: Date.now(),
      entry: entry,
      navigationType: this.getNavigationType(),
    };

    this.metrics.ttfb = metric;
    this.checkAgainstBudget(metric);
    this.sendToAnalytics('TTFB', ttfb);
  }

  /**
   * Add navigation timing observer for additional metrics
   */
  private addNavigationTimingObserver(): void {
    if ('PerformanceObserver' in window) {
      try {
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();

          for (const entry of entries) {
            this.handleNavigationMetrics(entry);
          }
        });

        observer.observe({
          entryTypes: ['navigation'],
          buffered: true
        });

        this.observers.push(observer);
      } catch (error) {
        console.warn('Failed to observe navigation timing:', error);
      }
    }
  }

  /**
   * Handle navigation timing metrics
   */
  private handleNavigationMetrics(entry: PerformanceEntry): void {
    const navEntry = entry as any;

    // DOM Content Loaded
    if (navEntry.domContentLoadedEventEnd) {
      const dcl = navEntry.domContentLoadedEventEnd - navEntry.fetchStart;
      this.sendToAnalytics('DCL', dcl);
    }

    // On Load
    if (navEntry.loadEventEnd) {
      const onload = navEntry.loadEventEnd - navEntry.fetchStart;
      this.sendToAnalytics('OnLoad', onload);
    }
  }

  /**
   * Monitor component-specific performance
   */
  public startComponentMonitoring(componentId: string, element: Element): void {
    if ('PerformanceObserver' in window) {
      try {
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();

          for (const entry of entries) {
            if (entry.entryType === 'measure' && entry.name.includes(componentId)) {
              this.handleComponentMetric(componentId, entry);
            }
          }
        });

        observer.observe({
          entryTypes: ['measure'],
          buffered: true
        });

        this.componentObservers.set(componentId, observer);
      } catch (error) {
        console.warn(`Failed to monitor component ${componentId}:`, error);
      }
    }
  }

  /**
   * Stop monitoring specific component
   */
  public stopComponentMonitoring(componentId: string): void {
    const observer = this.componentObservers.get(componentId);
    if (observer) {
      observer.disconnect();
      this.componentObservers.delete(componentId);
    }
  }

  /**
   * Measure component render time
   */
  public measureComponentRender(componentId: string, startTime: number): void {
    if ('performance' in window) {
      const endTime = performance.now();
      const duration = endTime - startTime;

      performance.mark(`${componentId}_renderEnd`);
      performance.measure(`${componentId}_render`, `${componentId}_renderStart`, `${componentId}_renderEnd`);

      this.sendToAnalytics(`component_render_${componentId}`, duration);
    }
  }

  /**
   * Handle component-specific metrics
   */
  private handleComponentMetric(componentId: string, entry: PerformanceEntry): void {
    const value = entry.duration;

    this.sendToAnalytics(`component_${componentId}_metric`, value, {
      component_id: componentId,
      metric_type: entry.name,
    });
  }

  /**
   * Check metric against performance budget
   */
  private checkAgainstBudget(metric: WebVitalsMetric): void {
    const budgetValue = this.budgets[metric.name.toLowerCase() as keyof PerformanceBudget];

    if (budgetValue && metric.value > budgetValue) {
      console.warn(`Performance budget exceeded for ${metric.name}:`, {
        value: metric.value,
        budget: budgetValue,
        exceededBy: metric.value - budgetValue,
      });

      this.sendToAnalytics(`budget_exceeded_${metric.name}`, metric.value);
    }
  }

  /**
   * Send metrics to analytics service
   */
  private sendToAnalytics(eventName: string, value: number, additionalData?: object): void {
    // Send to your preferred analytics service
    if (window.gtag) {
      window.gtag('event', 'web_vitals', {
        event_category: 'Performance',
        event_label: eventName,
        value: Math.round(value),
        custom_parameters: additionalData || {},
      });
    }

    // Also log to console for development
    if (process.env.NODE_ENV === 'development') {
      console.log(`[WebVitals] ${eventName}:`, value, additionalData || '');
    }

    // Store in local storage for session analysis
    this.storeLocalMetric(eventName, value, additionalData);
  }

  /**
   * Store metrics locally for session analysis
   */
  private storeLocalMetric(eventName: string, value: number, additionalData?: object): void {
    try {
      const sessionMetrics = JSON.parse(localStorage.getItem('webVitalsSessionMetrics') || '{}');
      const metrics = sessionMetrics[eventName] || [];

      metrics.push({
        value,
        timestamp: Date.now(),
        ...additionalData,
      });

      // Keep only last 100 metrics per type
      if (metrics.length > 100) {
        metrics.shift();
      }

      sessionMetrics[eventName] = metrics;
      localStorage.setItem('webVitalsSessionMetrics', JSON.stringify(sessionMetrics));
    } catch (error) {
      console.warn('Failed to store local metric:', error);
    }
  }

  /**
   * Get navigation type
   */
  private getNavigationType(): string {
    if ('navigation' in performance) {
      const navEntry = performance.getEntriesByType('navigation')[0] as any;
      return navEntry.type || 'unknown';
    }
    return 'unknown';
  }

  /**
   * Get current Web Vitals metrics
   */
  public getMetrics(): CoreWebVitalsData {
    return { ...this.metrics };
  }

  /**
   * Get performance budget configuration
   */
  public getBudgets(): PerformanceBudget {
    return { ...this.budgets };
  }

  /**
   * Update performance budgets
   */
  public setBudgets(newBudgets: Partial<PerformanceBudget>): void {
    this.budgets = { ...this.budgets, ...newBudgets };
    console.log('Updated performance budgets:', this.budgets);
  }

  /**
   * Check if all Core Web Vitals are within budget
   */
  public isWithinBudget(): boolean {
    const metrics = this.getMetrics();
    const budgets = this.getBudgets();

    return (
      (!metrics.lcp || metrics.lcp.value <= budgets.lcp) &&
      (!metrics.fid || metrics.fid.value <= budgets.fid) &&
      (!metrics.cls || metrics.cls.value <= budgets.cls) &&
      (!metrics.fcp || metrics.fcp.value <= budgets.fcp) &&
      (!metrics.ttfb || metrics.ttfb.value <= budgets.ttfb)
    );
  }

  /**
   * Generate performance report
   */
  public generateReport(): object {
    const metrics = this.getMetrics();
    const budgets = this.getBudgets();
    const isWithinBudget = this.isWithinBudget();

    return {
      timestamp: Date.now(),
      isWithinBudget,
      metrics,
      budgets,
      budgetComparison: {
        lcp: metrics.lcp ? metrics.lcp.value / budgets.lcp : null,
        fid: metrics.fid ? metrics.fid.value / budgets.fid : null,
        cls: metrics.cls ? metrics.cls.value / budgets.cls : null,
        fcp: metrics.fcp ? metrics.fcp.value / budgets.fcp : null,
        ttfb: metrics.ttfb ? metrics.ttfb.value / budgets.ttfb : null,
      },
      recommendations: this.generateRecommendations(metrics, budgets),
    };
  }

  /**
   * Generate performance recommendations
   */
  private generateRecommendations(metrics: CoreWebVitalsData, budgets: PerformanceBudget): string[] {
    const recommendations: string[] = [];

    if (metrics.lcp && metrics.lcp.value > budgets.lcp) {
      recommendations.push('Optimize Largest Contentful Paint by improving server response times and resource load times');
    }

    if (metrics.fid && metrics.fid.value > budgets.fid) {
      recommendations.push('Reduce First Input Delay by minimizing JavaScript execution time');
    }

    if (metrics.cls && metrics.cls.value > budgets.cls) {
      recommendations.push('Minimize Cumulative Layout Shift by avoiding changes to layout properties');
    }

    if (metrics.fcp && metrics.fcp.value > budgets.fcp) {
      recommendations.push('Improve First Contentful Paint with faster resource loading and optimization');
    }

    if (metrics.ttfb && metrics.ttfb.value > budgets.ttfb) {
      recommendations.push('Improve Time to First Byte with optimized server configuration and CDN usage');
    }

    return recommendations;
  }

  /**
   * Clean up observers
   */
  public cleanup(): void {
    this.observers.forEach(observer => {
      observer.disconnect();
    });
    this.observers = [];

    this.componentObservers.forEach(observer => {
      observer.disconnect();
    });
    this.componentObservers.clear();
  }
}

// Global type declarations
declare global {
  interface Window {
    gtag?: (...args: any[]) => void;
  }
}

export default WebVitalsService;
export type { WebVitalsMetric, CoreWebVitalsData, PerformanceBudget };