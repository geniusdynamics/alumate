/**
 * Accessibility & Performance Setup
 * 
 * Initializes accessibility and performance monitoring for the application
 */

import performanceMonitor from './performance-monitor'
import { usePerformanceMonitoring } from '@/composables/usePerformanceMonitoring'

/**
 * Initialize accessibility and performance monitoring
 */
export function initializeAccessibilityAndPerformance() {
    // Initialize performance monitoring
    if (typeof window !== 'undefined') {
        console.log('🚀 Initializing accessibility and performance monitoring...')
        
        // Performance monitoring is auto-initialized
        // Additional setup can be done here if needed
        
        // Set up accessibility enhancements
        setupAccessibilityEnhancements()
        
        // Set up performance budgets
        setupPerformanceBudgets()
        
        console.log('✅ Accessibility and performance monitoring initialized')
    }
}

/**
 * Set up accessibility enhancements
 */
function setupAccessibilityEnhancements() {
    // Skip links for keyboard navigation
    addSkipLinks()
    
    // Focus management for single-page app
    setupFocusManagement()
    
    // Announce route changes to screen readers
    setupRouteAnnouncements()
    
    // Enhanced keyboard navigation
    setupKeyboardNavigation()
}

/**
 * Add skip links for keyboard navigation
 */
function addSkipLinks() {
    const skipLinks = document.createElement('div')
    skipLinks.className = 'skip-links'
    skipLinks.innerHTML = `
        <a href="#main-content" class="skip-link">Skip to main content</a>
        <a href="#navigation" class="skip-link">Skip to navigation</a>
        <a href="#search" class="skip-link">Skip to search</a>
    `
    
    // Add styles for skip links
    const style = document.createElement('style')
    style.textContent = `
        .skip-links {
            position: absolute;
            top: -40px;
            left: 6px;
            z-index: 1000;
        }
        
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            z-index: 1001;
            transition: top 0.3s;
        }
        
        .skip-link:focus {
            top: 6px;
        }
        
        @media (prefers-reduced-motion: reduce) {
            .skip-link {
                transition: none;
            }
        }
    `
    
    document.head.appendChild(style)
    document.body.insertBefore(skipLinks, document.body.firstChild)
}

/**
 * Set up focus management for SPA navigation
 */
function setupFocusManagement() {
    // Store the last focused element before navigation
    let lastFocusedElement = null
    
    // Listen for route changes (this would be integrated with your router)
    window.addEventListener('beforeunload', () => {
        lastFocusedElement = document.activeElement
    })
    
    window.addEventListener('load', () => {
        // Focus management after page load
        const mainContent = document.getElementById('main-content')
        if (mainContent) {
            mainContent.setAttribute('tabindex', '-1')
            mainContent.focus()
        }
    })
}

/**
 * Set up route change announcements for screen readers
 */
function setupRouteAnnouncements() {
    // Create a live region for route announcements
    const announcer = document.createElement('div')
    announcer.setAttribute('aria-live', 'polite')
    announcer.setAttribute('aria-atomic', 'true')
    announcer.className = 'sr-only route-announcer'
    announcer.style.cssText = `
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    `
    
    document.body.appendChild(announcer)
    
    // Function to announce route changes
    window.announceRouteChange = (routeName) => {
        announcer.textContent = `Navigated to ${routeName}`
        
        // Clear after announcement
        setTimeout(() => {
            announcer.textContent = ''
        }, 1000)
    }
}

/**
 * Set up enhanced keyboard navigation
 */
function setupKeyboardNavigation() {
    // Global keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Alt + M: Skip to main content
        if (e.altKey && e.key === 'm') {
            e.preventDefault()
            const mainContent = document.getElementById('main-content')
            if (mainContent) {
                mainContent.focus()
            }
        }
        
        // Alt + N: Skip to navigation
        if (e.altKey && e.key === 'n') {
            e.preventDefault()
            const navigation = document.getElementById('navigation')
            if (navigation) {
                navigation.focus()
            }
        }
        
        // Alt + S: Skip to search
        if (e.altKey && e.key === 's') {
            e.preventDefault()
            const search = document.getElementById('search')
            if (search) {
                search.focus()
            }
        }
        
        // Escape: Close modals and dropdowns
        if (e.key === 'Escape') {
            // This would be handled by individual components
            // but we can add global handling here if needed
            const openModals = document.querySelectorAll('[role="dialog"][aria-modal="true"]')
            openModals.forEach(modal => {
                const closeButton = modal.querySelector('[aria-label*="close"], [aria-label*="Close"]')
                if (closeButton) {
                    closeButton.click()
                }
            })
        }
    })
}

/**
 * Set up performance budgets and monitoring
 */
function setupPerformanceBudgets() {
    const budgets = {
        // Core Web Vitals budgets
        LCP: 2500, // Largest Contentful Paint
        FID: 100,  // First Input Delay
        CLS: 0.1,  // Cumulative Layout Shift
        FCP: 1800, // First Contentful Paint
        TTFB: 800, // Time to First Byte
        
        // Custom budgets
        ComponentMount: 500,
        RouteChange: 1000,
        ApiRequest: 2000,
        UserInteraction: 100
    }
    
    // Monitor performance budgets
    const { recordMetric } = usePerformanceMonitoring()
    
    // Check budgets periodically
    setInterval(() => {
        const metrics = performanceMonitor.getMetricsSummary()
        
        Object.entries(budgets).forEach(([metricName, budget]) => {
            const metric = metrics[metricName]
            if (metric && metric.avg > budget) {
                console.warn(`⚠️ Performance budget exceeded for ${metricName}:`, {
                    average: metric.avg,
                    budget,
                    exceedBy: metric.avg - budget
                })
                
                recordMetric('BudgetViolation', metric.avg, {
                    metric: metricName,
                    budget,
                    violation: metric.avg - budget
                })
            }
        })
    }, 30000) // Check every 30 seconds
}

/**
 * Accessibility testing helpers
 */
export const accessibilityHelpers = {
    /**
     * Check if element is focusable
     */
    isFocusable(element) {
        const focusableSelectors = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])',
            '[contenteditable="true"]'
        ]
        
        return focusableSelectors.some(selector => element.matches(selector))
    },
    
    /**
     * Get all focusable elements within a container
     */
    getFocusableElements(container = document) {
        const focusableSelectors = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])',
            '[contenteditable="true"]'
        ].join(', ')
        
        return Array.from(container.querySelectorAll(focusableSelectors))
            .filter(el => {
                // Check if element is visible
                const style = window.getComputedStyle(el)
                return style.display !== 'none' && 
                       style.visibility !== 'hidden' && 
                       style.opacity !== '0'
            })
    },
    
    /**
     * Trap focus within a container
     */
    trapFocus(container) {
        const focusableElements = this.getFocusableElements(container)
        const firstElement = focusableElements[0]
        const lastElement = focusableElements[focusableElements.length - 1]
        
        const handleKeyDown = (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    // Shift + Tab
                    if (document.activeElement === firstElement) {
                        e.preventDefault()
                        lastElement.focus()
                    }
                } else {
                    // Tab
                    if (document.activeElement === lastElement) {
                        e.preventDefault()
                        firstElement.focus()
                    }
                }
            }
        }
        
        container.addEventListener('keydown', handleKeyDown)
        
        // Focus first element
        if (firstElement) {
            firstElement.focus()
        }
        
        // Return cleanup function
        return () => {
            container.removeEventListener('keydown', handleKeyDown)
        }
    },
    
    /**
     * Announce message to screen readers
     */
    announce(message, priority = 'polite') {
        const announcer = document.createElement('div')
        announcer.setAttribute('aria-live', priority)
        announcer.setAttribute('aria-atomic', 'true')
        announcer.className = 'sr-only'
        announcer.style.cssText = `
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        `
        
        document.body.appendChild(announcer)
        announcer.textContent = message
        
        // Remove after announcement
        setTimeout(() => {
            document.body.removeChild(announcer)
        }, 1000)
    }
}

/**
 * Performance testing helpers
 */
export const performanceHelpers = {
    /**
     * Measure function execution time
     */
    measureFunction(fn, name) {
        return async (...args) => {
            const start = performance.now()
            const result = await fn(...args)
            const duration = performance.now() - start
            
            console.log(`⏱️ ${name}: ${duration.toFixed(2)}ms`)
            
            const { recordMetric } = usePerformanceMonitoring()
            recordMetric('FunctionExecution', duration, { function: name })
            
            return result
        }
    },
    
    /**
     * Check Core Web Vitals
     */
    async checkCoreWebVitals() {
        try {
            const response = await fetch('/api/performance/core-web-vitals')
            const data = await response.json()
            
            console.table(data.data.vitals)
            return data.data
        } catch (error) {
            console.error('Failed to fetch Core Web Vitals:', error)
        }
    },
    
    /**
     * Get performance analytics
     */
    async getPerformanceAnalytics(period = '24h') {
        try {
            const response = await fetch(`/api/performance/analytics?period=${period}`)
            const data = await response.json()
            
            console.log('📊 Performance Analytics:', data.data)
            return data.data
        } catch (error) {
            console.error('Failed to fetch performance analytics:', error)
        }
    }
}

// Auto-initialize when imported
if (typeof window !== 'undefined') {
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAccessibilityAndPerformance)
    } else {
        initializeAccessibilityAndPerformance()
    }
}