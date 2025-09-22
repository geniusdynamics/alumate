import type { Socket } from 'socket.io-client';

/**
 * Analytics Integration Service
 * Handles event collection, metrics calculation, and backend API integration
 * for page performance tracking in the Vue.js Page Builder System
 */

export interface AnalyticsEvent {
    id: string;
    type: string;
    name: string;
    properties: Record<string, any>;
    timestamp: Date;
    pageId?: string;
    tenantId?: string;
    userId?: string;
    sessionId: string;
}

export interface PerformanceMetrics {
    pageLoadTime: number;
    domContentLoaded: number;
    firstPaint: number;
    firstContentfulPaint: number;
    largestContentfulPaint: number;
    cumulativeLayoutShift: number;
    firstInputDelay: number;
    interactionToNextPaint: number;
}

export interface AnalyticsMetrics {
    pageViews: number;
    uniqueVisitors: number;
    bounceRate: number;
    averageSessionDuration: number;
    conversionRate: number;
    formSubmissions: number;
    elementInteractions: number;
    scrollDepth: number;
    seoScore?: number;
}

export interface AnalyticsConfig {
    tenantId?: string;
    pageId?: string;
    userId?: string;
    sessionId: string;
    enableRealTime: boolean;
    batchSize: number;
    flushInterval: number;
}

export class AnalyticsIntegrationService {
    private config: AnalyticsConfig;
    private pendingEvents: AnalyticsEvent[] = [];
    private socket: Socket | null = null;
    private flushTimer: number | null = null;
    private isInitialized = false;
    private performanceObserver: PerformanceObserver | null = null;
    private intersectionObserver: IntersectionObserver | null = null;

    constructor(config: Partial<AnalyticsConfig> = {}) {
        this.config = {
            enableRealTime: true,
            batchSize: 10,
            flushInterval: 30000, // 30 seconds
            sessionId: this.generateSessionId(),
            ...config,
        };
    }

    /**
     * Initialize the analytics service
     */
    async initialize(socket?: Socket): Promise<void> {
        if (this.isInitialized) return;

        this.socket = socket || null;

        // Set up performance monitoring
        this.setupPerformanceMonitoring();

        // Set up scroll tracking
        this.setupScrollTracking();

        // Set up real-time sync if socket is available
        if (this.socket && this.config.enableRealTime) {
            this.setupSocketListeners();
        }

        // Start periodic flush
        this.startPeriodicFlush();

        this.isInitialized = true;

        console.log('Analytics Integration Service initialized');
    }

    /**
     * Track a custom event
     */
    async trackEvent(eventName: string, properties: Record<string, any> = {}, type: string = 'custom'): Promise<void> {
        const event: AnalyticsEvent = {
            id: this.generateEventId(),
            type,
            name: eventName,
            properties,
            timestamp: new Date(),
            pageId: this.config.pageId,
            tenantId: this.config.tenantId,
            userId: this.config.userId,
            sessionId: this.config.sessionId,
        };

        this.pendingEvents.push(event);

        // Send real-time if enabled
        if (this.config.enableRealTime && this.socket) {
            this.socket.emit('analytics:event', event);
        }

        // Flush if batch size reached
        if (this.pendingEvents.length >= this.config.batchSize) {
            await this.flush();
        }

        console.log(`Tracked event: ${eventName}`, properties);
    }

    /**
     * Track page view
     */
    async trackPageView(pageUrl: string, properties: Record<string, any> = {}): Promise<void> {
        const pageViewProperties = {
            url: pageUrl,
            title: document.title,
            referrer: document.referrer,
            userAgent: navigator.userAgent,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight,
            },
            ...properties,
        };

        await this.trackEvent('page_view', pageViewProperties, 'page');
    }

    /**
     * Track element interaction
     */
    async trackElementInteraction(elementId: string, interactionType: string, properties: Record<string, any> = {}): Promise<void> {
        const element = document.getElementById(elementId);
        const elementProperties = {
            elementId,
            interactionType,
            elementTag: element?.tagName.toLowerCase(),
            elementText: element?.textContent?.substring(0, 100),
            elementClasses: element?.className,
            ...properties,
        };

        await this.trackEvent('element_interaction', elementProperties, 'interaction');
    }

    /**
     * Track form submission
     */
    async trackFormSubmission(formId: string, formData: Record<string, any>, properties: Record<string, any> = {}): Promise<void> {
        const formProperties = {
            formId,
            fieldCount: Object.keys(formData).length,
            formData: this.sanitizeFormData(formData),
            ...properties,
        };

        await this.trackEvent('form_submission', formProperties, 'conversion');
    }

    /**
     * Track scroll depth
     */
    async trackScrollDepth(depth: number, properties: Record<string, any> = {}): Promise<void> {
        const scrollProperties = {
            scrollDepth: depth,
            maxScrollDepth: Math.max(depth, properties.maxScrollDepth || 0),
            documentHeight: document.documentElement.scrollHeight,
            viewportHeight: window.innerHeight,
            ...properties,
        };

        await this.trackEvent('scroll_depth', scrollProperties, 'engagement');
    }

    /**
     * Track performance metrics
     */
    async trackPerformanceMetrics(metrics: Partial<PerformanceMetrics>): Promise<void> {
        const performanceProperties = {
            ...metrics,
            timestamp: Date.now(),
        };

        await this.trackEvent('performance_metrics', performanceProperties, 'performance');
    }

    /**
     * Get analytics metrics
     */
    async getAnalyticsMetrics(startDate: Date, endDate: Date, filters: Record<string, any> = {}): Promise<AnalyticsMetrics> {
        try {
            const queryParams = new URLSearchParams({
                startDate: startDate.toISOString(),
                endDate: endDate.toISOString(),
                tenantId: this.config.tenantId || '',
                pageId: this.config.pageId || '',
                ...filters,
            });

            const response = await fetch(`/api/analytics/metrics?${queryParams}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch analytics metrics: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Failed to get analytics metrics:', error);
            throw error;
        }
    }

    /**
     * Get real-time analytics data
     */
    async getRealTimeData(): Promise<any> {
        try {
            const response = await fetch('/api/analytics/realtime', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch real-time data: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Failed to get real-time analytics data:', error);
            throw error;
        }
    }

    /**
     * Flush pending events to backend
     */
    async flush(): Promise<void> {
        if (this.pendingEvents.length === 0) return;

        try {
            const eventsToSend = [...this.pendingEvents];
            this.pendingEvents = [];

            const response = await fetch('/api/analytics/events', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify({
                    events: eventsToSend,
                    tenantId: this.config.tenantId,
                    sessionId: this.config.sessionId,
                }),
            });

            if (!response.ok) {
                // Re-queue events on failure
                this.pendingEvents.unshift(...eventsToSend);
                throw new Error(`Failed to send analytics events: ${response.status}`);
            }

            console.log(`Flushed ${eventsToSend.length} analytics events`);
        } catch (error) {
            console.error('Failed to flush analytics events:', error);
            throw error;
        }
    }

    /**
     * Update configuration
     */
    updateConfig(newConfig: Partial<AnalyticsConfig>): void {
        this.config = { ...this.config, ...newConfig };
    }

    /**
     * Destroy the service
     */
    destroy(): void {
        if (this.flushTimer) {
            clearInterval(this.flushTimer);
            this.flushTimer = null;
        }

        if (this.performanceObserver) {
            this.performanceObserver.disconnect();
            this.performanceObserver = null;
        }

        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
            this.intersectionObserver = null;
        }

        // Flush any remaining events
        if (this.pendingEvents.length > 0) {
            this.flush().catch((error) => console.error('Failed to flush events on destroy:', error));
        }

        this.isInitialized = false;
        console.log('Analytics Integration Service destroyed');
    }

    /**
     * Set up performance monitoring
     */
    private setupPerformanceMonitoring(): void {
        // Monitor navigation timing
        if ('performance' in window && 'getEntriesByType' in performance) {
            const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;

            if (navigation) {
                const metrics: Partial<PerformanceMetrics> = {
                    pageLoadTime: navigation.loadEventEnd - navigation.fetchStart,
                    domContentLoaded: navigation.domContentLoadedEventEnd - navigation.fetchStart,
                };

                this.trackPerformanceMetrics(metrics).catch((error) => console.error('Failed to track initial performance metrics:', error));
            }
        }

        // Set up Performance Observer for additional metrics
        if ('PerformanceObserver' in window) {
            this.performanceObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();

                entries.forEach((entry) => {
                    if (entry.entryType === 'paint') {
                        const paintEntry = entry as PerformancePaintTiming;
                        if (paintEntry.name === 'first-paint') {
                            this.trackPerformanceMetrics({ firstPaint: paintEntry.startTime }).catch(console.error);
                        } else if (paintEntry.name === 'first-contentful-paint') {
                            this.trackPerformanceMetrics({ firstContentfulPaint: paintEntry.startTime }).catch(console.error);
                        }
                    } else if (entry.entryType === 'largest-contentful-paint') {
                        const lcpEntry = entry as any;
                        this.trackPerformanceMetrics({ largestContentfulPaint: lcpEntry.startTime }).catch(console.error);
                    } else if (entry.entryType === 'layout-shift') {
                        const clsEntry = entry as any;
                        this.trackPerformanceMetrics({ cumulativeLayoutShift: clsEntry.value }).catch(console.error);
                    }
                });
            });

            try {
                this.performanceObserver.observe({ entryTypes: ['paint', 'largest-contentful-paint', 'layout-shift'] });
            } catch (error) {
                console.warn('Performance observer setup failed:', error);
            }
        }
    }

    /**
     * Set up scroll tracking
     */
    private setupScrollTracking(): void {
        let maxScrollDepth = 0;

        const handleScroll = () => {
            const scrollTop = window.scrollY;
            const documentHeight = document.documentElement.scrollHeight;
            const windowHeight = window.innerHeight;
            const scrollDepth = Math.round((scrollTop / (documentHeight - windowHeight)) * 100);

            if (scrollDepth > maxScrollDepth) {
                maxScrollDepth = scrollDepth;
                this.trackScrollDepth(scrollDepth, { maxScrollDepth }).catch(console.error);
            }
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    /**
     * Set up socket listeners for real-time sync
     */
    private setupSocketListeners(): void {
        if (!this.socket) return;

        this.socket.on('analytics:event', (event: AnalyticsEvent) => {
            console.log('Received real-time analytics event:', event);
            // Handle incoming real-time events if needed
        });

        this.socket.on('analytics:metrics', (metrics: AnalyticsMetrics) => {
            console.log('Received real-time analytics metrics:', metrics);
            // Handle incoming real-time metrics if needed
        });
    }

    /**
     * Start periodic flush of events
     */
    private startPeriodicFlush(): void {
        this.flushTimer = window.setInterval(() => {
            this.flush().catch((error) => console.error('Periodic flush failed:', error));
        }, this.config.flushInterval);
    }

    /**
     * Sanitize form data for tracking
     */
    private sanitizeFormData(formData: Record<string, any>): Record<string, any> {
        const sanitized: Record<string, any> = {};

        for (const [key, value] of Object.entries(formData)) {
            // Remove sensitive fields
            if (!['password', 'credit_card', 'ssn', 'api_key'].some((sensitive) => key.toLowerCase().includes(sensitive))) {
                sanitized[key] = typeof value === 'string' ? value.substring(0, 100) : value;
            }
        }

        return sanitized;
    }

    /**
     * Get CSRF token for API requests
     */
    private getCsrfToken(): string {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        return token || '';
    }

    /**
     * Generate unique event ID
     */
    private generateEventId(): string {
        return `event_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Generate session ID
     */
    private generateSessionId(): string {
        return `session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
}

// Export singleton instance
export const analyticsService = new AnalyticsIntegrationService();
