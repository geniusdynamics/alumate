/**
 * Performance Monitoring Utility
 * 
 * Provides comprehensive performance monitoring for the Modern Alumni Platform
 * including Core Web Vitals, custom metrics, and accessibility performance.
 */

class PerformanceMonitor {
    constructor() {
        this.metrics = new Map()
        this.observers = new Map()
        this.config = {
            enableLogging: process.env.NODE_ENV === 'development',
            enableReporting: process.env.NODE_ENV === 'production',
            reportingEndpoint: '/api/performance/metrics',
            sampleRate: 0.1, // 10% sampling in production
            thresholds: {
                // Core Web Vitals thresholds
                LCP: 2500, // Largest Contentful Paint (ms)
                FID: 100,  // First Input Delay (ms)
                CLS: 0.1,  // Cumulative Layout Shift
                FCP: 1800, // First Contentful Paint (ms)
                TTFB: 800, // Time to First Byte (ms)
                
                // Custom thresholds
                routeChange: 1000, // Route change time (ms)
                componentMount: 500, // Component mount time (ms)
                apiResponse: 2000, // API response time (ms)
            }
        }
        
        this.init()
    }

    /**
     * Initialize performance monitoring
     */
    init() {
        if (typeof window === 'undefined') return

        // Only monitor a sample of users in production
        if (this.config.enableReporting && Math.random() > this.config.sampleRate) {
            return
        }

        this.setupCoreWebVitals()
        this.setupCustomMetrics()
        this.setupNavigationTiming()
        this.setupResourceTiming()
        this.setupUserTiming()
        this.setupAccessibilityMetrics()
        
        // Report metrics periodically
        this.startReporting()
    }

    /**
     * Setup Core Web Vitals monitoring
     */
    setupCoreWebVitals() {
        // Largest Contentful Paint (LCP)
        this.observePerformanceEntry('largest-contentful-paint', (entries) => {
            const lastEntry = entries[entries.length - 1]
            this.recordMetric('LCP', lastEntry.startTime, {
                element: lastEntry.element?.tagName,
                url: lastEntry.url
            })
        })

        // First Input Delay (FID)
        this.observePerformanceEntry('first-input', (entries) => {
            entries.forEach(entry => {
                this.recordMetric('FID', entry.processingStart - entry.startTime, {
                    eventType: entry.name,
                    target: entry.target?.tagName
                })
            })
        })

        // Cumulative Layout Shift (CLS)
        this.observePerformanceEntry('layout-shift', (entries) => {
            let clsValue = 0
            entries.forEach(entry => {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value
                }
            })
            this.recordMetric('CLS', clsValue)
        })

        // First Contentful Paint (FCP)
        this.observePerformanceEntry('paint', (entries) => {
            entries.forEach(entry => {
                if (entry.name === 'first-contentful-paint') {
                    this.recordMetric('FCP', entry.startTime)
                }
            })
        })
    }

    /**
     * Setup custom application metrics
     */
    setupCustomMetrics() {
        // Vue component mounting time
        this.measureComponentPerformance()
        
        // Route change performance
        this.measureRouteChanges()
        
        // API request performance
        this.measureApiRequests()
        
        // User interaction metrics
        this.measureUserInteractions()
    }

    /**
     * Setup navigation timing metrics
     */
    setupNavigationTiming() {
        window.addEventListener('load', () => {
            const navigation = performance.getEntriesByType('navigation')[0]
            if (navigation) {
                this.recordMetric('TTFB', navigation.responseStart - navigation.requestStart)
                this.recordMetric('DOMContentLoaded', navigation.domContentLoadedEventEnd - navigation.navigationStart)
                this.recordMetric('LoadComplete', navigation.loadEventEnd - navigation.navigationStart)
            }
        })
    }

    /**
     * Setup resource timing monitoring
     */
    setupResourceTiming() {
        this.observePerformanceEntry('resource', (entries) => {
            entries.forEach(entry => {
                // Monitor critical resources
                if (this.isCriticalResource(entry.name)) {
                    this.recordMetric('ResourceLoad', entry.duration, {
                        resource: entry.name,
                        type: entry.initiatorType,
                        size: entry.transferSize
                    })
                }
            })
        })
    }

    /**
     * Setup user timing API monitoring
     */
    setupUserTiming() {
        this.observePerformanceEntry('measure', (entries) => {
            entries.forEach(entry => {
                this.recordMetric('UserTiming', entry.duration, {
                    name: entry.name,
                    detail: entry.detail
                })
            })
        })
    }

    /**
     * Setup accessibility performance metrics
     */
    setupAccessibilityMetrics() {
        // Measure focus management performance
        let focusStartTime = null
        
        document.addEventListener('focusin', () => {
            focusStartTime = performance.now()
        })
        
        document.addEventListener('focusout', () => {
            if (focusStartTime) {
                const focusDuration = performance.now() - focusStartTime
                this.recordMetric('FocusTime', focusDuration)
                focusStartTime = null
            }
        })

        // Measure keyboard navigation performance
        let keyboardStartTime = null
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' || e.key === 'Enter' || e.key === ' ') {
                keyboardStartTime = performance.now()
            }
        })
        
        document.addEventListener('keyup', (e) => {
            if (keyboardStartTime && (e.key === 'Tab' || e.key === 'Enter' || e.key === ' ')) {
                const keyboardDuration = performance.now() - keyboardStartTime
                this.recordMetric('KeyboardNavigation', keyboardDuration, {
                    key: e.key
                })
                keyboardStartTime = null
            }
        })
    }

    /**
     * Measure Vue component performance
     */
    measureComponentPerformance() {
        // Hook into Vue's component lifecycle
        if (window.Vue && window.Vue.config) {
            const originalMount = window.Vue.config.performance
            window.Vue.config.performance = true
            
            // Monitor component mount times
            this.observePerformanceEntry('measure', (entries) => {
                entries.forEach(entry => {
                    if (entry.name.includes('vue-component')) {
                        this.recordMetric('ComponentMount', entry.duration, {
                            component: entry.name
                        })
                    }
                })
            })
        }
    }

    /**
     * Measure route change performance
     */
    measureRouteChanges() {
        let routeStartTime = null
        
        // Listen for route changes (works with Vue Router)
        window.addEventListener('beforeunload', () => {
            routeStartTime = performance.now()
        })
        
        window.addEventListener('load', () => {
            if (routeStartTime) {
                const routeDuration = performance.now() - routeStartTime
                this.recordMetric('RouteChange', routeDuration, {
                    from: document.referrer,
                    to: window.location.href
                })
                routeStartTime = null
            }
        })
    }

    /**
     * Measure API request performance
     */
    measureApiRequests() {
        // Intercept fetch requests
        const originalFetch = window.fetch
        window.fetch = async (...args) => {
            const startTime = performance.now()
            const url = args[0]
            
            try {
                const response = await originalFetch(...args)
                const duration = performance.now() - startTime
                
                this.recordMetric('ApiRequest', duration, {
                    url: typeof url === 'string' ? url : url.url,
                    method: args[1]?.method || 'GET',
                    status: response.status,
                    success: response.ok
                })
                
                return response
            } catch (error) {
                const duration = performance.now() - startTime
                
                this.recordMetric('ApiRequest', duration, {
                    url: typeof url === 'string' ? url : url.url,
                    method: args[1]?.method || 'GET',
                    error: error.message,
                    success: false
                })
                
                throw error
            }
        }
    }

    /**
     * Measure user interaction performance
     */
    measureUserInteractions() {
        const interactionTypes = ['click', 'keydown', 'touchstart']
        
        interactionTypes.forEach(type => {
            document.addEventListener(type, (e) => {
                const startTime = performance.now()
                
                // Measure time to next paint
                requestAnimationFrame(() => {
                    const duration = performance.now() - startTime
                    this.recordMetric('UserInteraction', duration, {
                        type,
                        target: e.target?.tagName,
                        id: e.target?.id,
                        className: e.target?.className
                    })
                })
            }, { passive: true })
        })
    }

    /**
     * Observe performance entries
     */
    observePerformanceEntry(type, callback) {
        if (!('PerformanceObserver' in window)) return

        try {
            const observer = new PerformanceObserver((list) => {
                callback(list.getEntries())
            })
            
            observer.observe({ type, buffered: true })
            this.observers.set(type, observer)
        } catch (error) {
            console.warn(`Failed to observe ${type}:`, error)
        }
    }

    /**
     * Record a performance metric
     */
    recordMetric(name, value, metadata = {}) {
        const metric = {
            name,
            value,
            timestamp: Date.now(),
            url: window.location.href,
            userAgent: navigator.userAgent,
            connection: this.getConnectionInfo(),
            ...metadata
        }

        this.metrics.set(`${name}-${Date.now()}`, metric)

        // Log in development
        if (this.config.enableLogging) {
            const threshold = this.config.thresholds[name]
            const status = threshold && value > threshold ? '⚠️' : '✅'
            console.log(`${status} ${name}: ${value.toFixed(2)}ms`, metadata)
        }

        // Check thresholds and warn
        this.checkThreshold(name, value, metadata)
    }

    /**
     * Check if metric exceeds threshold
     */
    checkThreshold(name, value, metadata) {
        const threshold = this.config.thresholds[name]
        if (threshold && value > threshold) {
            console.warn(`Performance threshold exceeded for ${name}: ${value}ms > ${threshold}ms`, metadata)
            
            // Record threshold violation
            this.recordMetric('ThresholdViolation', value, {
                metric: name,
                threshold,
                ...metadata
            })
        }
    }

    /**
     * Get connection information
     */
    getConnectionInfo() {
        if ('connection' in navigator) {
            const conn = navigator.connection
            return {
                effectiveType: conn.effectiveType,
                downlink: conn.downlink,
                rtt: conn.rtt,
                saveData: conn.saveData
            }
        }
        return null
    }

    /**
     * Check if resource is critical
     */
    isCriticalResource(url) {
        const criticalPatterns = [
            /\.css$/,
            /\.js$/,
            /\/api\//,
            /fonts/,
            /critical/
        ]
        
        return criticalPatterns.some(pattern => pattern.test(url))
    }

    /**
     * Start periodic reporting
     */
    startReporting() {
        if (!this.config.enableReporting) return

        // Report metrics every 30 seconds
        setInterval(() => {
            this.reportMetrics()
        }, 30000)

        // Report on page unload
        window.addEventListener('beforeunload', () => {
            this.reportMetrics(true)
        })

        // Report on visibility change (when user switches tabs)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                this.reportMetrics(true)
            }
        })
    }

    /**
     * Integrate with Vue Router for route change tracking
     */
    integrateWithRouter(router) {
        if (!router) return

        let routeStartTime = null

        router.beforeEach((to, from, next) => {
            routeStartTime = performance.now()
            this.recordMetric('RouteChangeStart', 0, {
                from: from.path,
                to: to.path
            })
            next()
        })

        router.afterEach((to, from) => {
            if (routeStartTime) {
                const routeDuration = performance.now() - routeStartTime
                this.recordMetric('RouteChange', routeDuration, {
                    from: from.path,
                    to: to.path
                })
                routeStartTime = null
            }
        })
    }

    /**
     * Track component performance
     */
    trackComponent(componentName, operation = 'mount') {
        const startTime = performance.now()
        
        return {
            end: () => {
                const duration = performance.now() - startTime
                this.recordMetric('ComponentPerformance', duration, {
                    component: componentName,
                    operation
                })
            }
        }
    }

    /**
     * Track async operation performance
     */
    trackAsyncOperation(operationName, metadata = {}) {
        const startTime = performance.now()
        
        return {
            end: (success = true, error = null) => {
                const duration = performance.now() - startTime
                this.recordMetric('AsyncOperation', duration, {
                    operation: operationName,
                    success,
                    error: error?.message,
                    ...metadata
                })
            }
        }
    }

    /**
     * Report metrics to server
     */
    async reportMetrics(isBeacon = false) {
        if (this.metrics.size === 0) return

        const metricsData = {
            metrics: Array.from(this.metrics.values()),
            session: this.getSessionInfo(),
            timestamp: Date.now()
        }

        try {
            if (isBeacon && 'sendBeacon' in navigator) {
                // Use beacon API for reliable reporting on page unload
                navigator.sendBeacon(
                    this.config.reportingEndpoint,
                    JSON.stringify(metricsData)
                )
            } else {
                // Use fetch for regular reporting
                await fetch(this.config.reportingEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(metricsData)
                })
            }

            // Clear reported metrics
            this.metrics.clear()
        } catch (error) {
            console.warn('Failed to report performance metrics:', error)
        }
    }

    /**
     * Get session information
     */
    getSessionInfo() {
        return {
            url: window.location.href,
            referrer: document.referrer,
            userAgent: navigator.userAgent,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            },
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth
            },
            connection: this.getConnectionInfo(),
            memory: 'memory' in performance ? performance.memory : null
        }
    }

    /**
     * Get current metrics summary
     */
    getMetricsSummary() {
        const summary = {}
        
        this.metrics.forEach(metric => {
            if (!summary[metric.name]) {
                summary[metric.name] = {
                    count: 0,
                    total: 0,
                    min: Infinity,
                    max: -Infinity,
                    avg: 0
                }
            }
            
            const stat = summary[metric.name]
            stat.count++
            stat.total += metric.value
            stat.min = Math.min(stat.min, metric.value)
            stat.max = Math.max(stat.max, metric.value)
            stat.avg = stat.total / stat.count
        })
        
        return summary
    }

    /**
     * Mark a custom timing point
     */
    mark(name) {
        if ('performance' in window && 'mark' in performance) {
            performance.mark(name)
        }
    }

    /**
     * Measure between two timing points
     */
    measure(name, startMark, endMark) {
        if ('performance' in window && 'measure' in performance) {
            try {
                performance.measure(name, startMark, endMark)
            } catch (error) {
                console.warn(`Failed to measure ${name}:`, error)
            }
        }
    }

    /**
     * Cleanup observers and listeners
     */
    destroy() {
        this.observers.forEach(observer => {
            observer.disconnect()
        })
        this.observers.clear()
        this.metrics.clear()
    }
}

// Create singleton instance
const performanceMonitor = new PerformanceMonitor()

// Export for use in components
export default performanceMonitor

// Export utilities for component use
export const markPerformance = (name) => performanceMonitor.mark(name)
export const measurePerformance = (name, start, end) => performanceMonitor.measure(name, start, end)
export const recordCustomMetric = (name, value, metadata) => performanceMonitor.recordMetric(name, value, metadata)