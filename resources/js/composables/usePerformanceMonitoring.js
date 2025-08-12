/**
 * Vue Composable for Performance Monitoring
 * 
 * Provides easy integration with the performance monitoring system
 * for Vue components and pages.
 */

import { ref, onMounted, onUnmounted } from 'vue'
import performanceMonitor, { markPerformance, measurePerformance, recordCustomMetric } from '@/utils/performance-monitor'

export function usePerformanceMonitoring() {
    const isMonitoring = ref(false)
    const metrics = ref([])
    const currentPageMetrics = ref({})

    /**
     * Start monitoring for the current component/page
     */
    const startMonitoring = (pageName) => {
        if (isMonitoring.value) return

        isMonitoring.value = true
        markPerformance(`${pageName}-start`)
        
        // Record page load start
        recordCustomMetric('PageLoadStart', performance.now(), {
            page: pageName,
            url: window.location.href
        })
    }

    /**
     * Stop monitoring and record final metrics
     */
    const stopMonitoring = (pageName) => {
        if (!isMonitoring.value) return

        markPerformance(`${pageName}-end`)
        measurePerformance(`${pageName}-total`, `${pageName}-start`, `${pageName}-end`)
        
        isMonitoring.value = false
    }

    /**
     * Record a custom performance metric
     */
    const recordMetric = (name, value, metadata = {}) => {
        recordCustomMetric(name, value, {
            component: 'vue-component',
            ...metadata
        })
    }

    /**
     * Measure component mount time
     */
    const measureComponentMount = (componentName) => {
        const startTime = performance.now()
        
        return () => {
            const mountTime = performance.now() - startTime
            recordMetric('ComponentMount', mountTime, {
                component: componentName
            })
        }
    }

    /**
     * Measure API request performance
     */
    const measureApiRequest = async (requestName, requestFn) => {
        const startTime = performance.now()
        
        try {
            const result = await requestFn()
            const duration = performance.now() - startTime
            
            recordMetric('ApiRequest', duration, {
                request: requestName,
                success: true
            })
            
            return result
        } catch (error) {
            const duration = performance.now() - startTime
            
            recordMetric('ApiRequest', duration, {
                request: requestName,
                success: false,
                error: error.message
            })
            
            throw error
        }
    }

    /**
     * Measure user interaction performance
     */
    const measureInteraction = (interactionName, interactionFn) => {
        return async (...args) => {
            const startTime = performance.now()
            
            try {
                const result = await interactionFn(...args)
                const duration = performance.now() - startTime
                
                recordMetric('UserInteraction', duration, {
                    interaction: interactionName,
                    success: true
                })
                
                return result
            } catch (error) {
                const duration = performance.now() - startTime
                
                recordMetric('UserInteraction', duration, {
                    interaction: interactionName,
                    success: false,
                    error: error.message
                })
                
                throw error
            }
        }
    }

    /**
     * Measure rendering performance
     */
    const measureRender = (renderName) => {
        const startTime = performance.now()
        
        return () => {
            // Use requestAnimationFrame to measure after render
            requestAnimationFrame(() => {
                const renderTime = performance.now() - startTime
                recordMetric('RenderTime', renderTime, {
                    render: renderName
                })
            })
        }
    }

    /**
     * Track long tasks (tasks that block the main thread)
     */
    const trackLongTasks = () => {
        if ('PerformanceObserver' in window) {
            try {
                const observer = new PerformanceObserver((list) => {
                    list.getEntries().forEach((entry) => {
                        recordMetric('LongTask', entry.duration, {
                            startTime: entry.startTime,
                            name: entry.name
                        })
                    })
                })
                
                observer.observe({ type: 'longtask', buffered: true })
                
                return () => observer.disconnect()
            } catch (error) {
                console.warn('Long task tracking not supported:', error)
                return () => {}
            }
        }
        
        return () => {}
    }

    /**
     * Monitor memory usage
     */
    const trackMemoryUsage = () => {
        if ('memory' in performance) {
            const memory = performance.memory
            
            recordMetric('MemoryUsage', memory.usedJSHeapSize, {
                total: memory.totalJSHeapSize,
                limit: memory.jsHeapSizeLimit,
                percentage: (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100
            })
        }
    }

    /**
     * Get current page metrics summary
     */
    const getPageMetrics = () => {
        return performanceMonitor.getMetricsSummary()
    }

    /**
     * Create a performance-aware async function wrapper
     */
    const withPerformanceTracking = (name, asyncFn) => {
        return async (...args) => {
            const startTime = performance.now()
            markPerformance(`${name}-start`)
            
            try {
                const result = await asyncFn(...args)
                const duration = performance.now() - startTime
                
                markPerformance(`${name}-end`)
                measurePerformance(name, `${name}-start`, `${name}-end`)
                
                recordMetric('AsyncOperation', duration, {
                    operation: name,
                    success: true
                })
                
                return result
            } catch (error) {
                const duration = performance.now() - startTime
                
                recordMetric('AsyncOperation', duration, {
                    operation: name,
                    success: false,
                    error: error.message
                })
                
                throw error
            }
        }
    }

    /**
     * Monitor route changes
     */
    const trackRouteChange = (from, to) => {
        const startTime = performance.now()
        
        return () => {
            const duration = performance.now() - startTime
            recordMetric('RouteChange', duration, {
                from: from?.path || 'unknown',
                to: to?.path || 'unknown'
            })
        }
    }

    /**
     * Track form submission performance
     */
    const trackFormSubmission = (formName, submitFn) => {
        return async (formData) => {
            const startTime = performance.now()
            
            try {
                const result = await submitFn(formData)
                const duration = performance.now() - startTime
                
                recordMetric('FormSubmission', duration, {
                    form: formName,
                    success: true
                })
                
                return result
            } catch (error) {
                const duration = performance.now() - startTime
                
                recordMetric('FormSubmission', duration, {
                    form: formName,
                    success: false,
                    error: error.message
                })
                
                throw error
            }
        }
    }

    /**
     * Track search performance
     */
    const trackSearch = (searchTerm, searchFn) => {
        return async () => {
            const startTime = performance.now()
            
            try {
                const results = await searchFn()
                const duration = performance.now() - startTime
                
                recordMetric('SearchQuery', duration, {
                    query: searchTerm,
                    resultCount: results?.length || 0,
                    success: true
                })
                
                return results
            } catch (error) {
                const duration = performance.now() - startTime
                
                recordMetric('SearchQuery', duration, {
                    query: searchTerm,
                    success: false,
                    error: error.message
                })
                
                throw error
            }
        }
    }

    /**
     * Monitor component lifecycle performance
     */
    const useComponentPerformance = (componentName) => {
        const mountStartTime = performance.now()
        
        onMounted(() => {
            const mountTime = performance.now() - mountStartTime
            recordMetric('ComponentMount', mountTime, {
                component: componentName
            })
            
            // Track memory usage after mount
            trackMemoryUsage()
        })
        
        onUnmounted(() => {
            recordMetric('ComponentUnmount', performance.now(), {
                component: componentName
            })
        })
        
        return {
            recordMetric: (name, value, metadata = {}) => {
                recordMetric(name, value, {
                    component: componentName,
                    ...metadata
                })
            }
        }
    }

    return {
        // State
        isMonitoring,
        metrics,
        currentPageMetrics,
        
        // Core functions
        startMonitoring,
        stopMonitoring,
        recordMetric,
        
        // Measurement helpers
        measureComponentMount,
        measureApiRequest,
        measureInteraction,
        measureRender,
        
        // Tracking functions
        trackLongTasks,
        trackMemoryUsage,
        trackRouteChange,
        trackFormSubmission,
        trackSearch,
        
        // Utilities
        getPageMetrics,
        withPerformanceTracking,
        useComponentPerformance,
        
        // Direct access to performance monitor
        performanceMonitor
    }
}

/**
 * Page-level performance monitoring composable
 */
export function usePagePerformance(pageName) {
    const { 
        startMonitoring, 
        stopMonitoring, 
        recordMetric,
        trackLongTasks,
        trackMemoryUsage
    } = usePerformanceMonitoring()
    
    const cleanupLongTasks = ref(null)
    
    onMounted(() => {
        startMonitoring(pageName)
        cleanupLongTasks.value = trackLongTasks()
        
        // Track initial memory usage
        trackMemoryUsage()
        
        // Track page visibility changes
        const handleVisibilityChange = () => {
            recordMetric('PageVisibilityChange', performance.now(), {
                page: pageName,
                visible: !document.hidden
            })
        }
        
        document.addEventListener('visibilitychange', handleVisibilityChange)
        
        return () => {
            document.removeEventListener('visibilitychange', handleVisibilityChange)
        }
    })
    
    onUnmounted(() => {
        stopMonitoring(pageName)
        
        if (cleanupLongTasks.value) {
            cleanupLongTasks.value()
        }
    })
    
    return {
        recordMetric: (name, value, metadata = {}) => {
            recordMetric(name, value, {
                page: pageName,
                ...metadata
            })
        }
    }
}

/**
 * Component-level performance monitoring composable
 */
export function useComponentPerformance(componentName) {
    const { useComponentPerformance } = usePerformanceMonitoring()
    return useComponentPerformance(componentName)
}