import { tenantValidationService } from './TenantValidationService';

/**
 * Analytics Tracking Service
 *
 * Client-side event tracking library for comprehensive analytics including
 * page views, clicks, scrolls, forms, time on page, and device info.
 * Includes tenant identification, privacy compliance (GDPR/CCPA), and
 * asynchronous batch sending with retries.
 */

export interface TrackingEvent {
    eventType: string;
    properties: Record<string, any>;
    timestamp: number;
    sessionId: string;
    userId?: string;
    tenantId?: string;
    complianceFlags: {
        hasConsent: boolean;
        doNotTrack: boolean;
        anonymized: boolean;
    };
    deviceInfo: DeviceInfo;
}

export interface DeviceInfo {
    userAgent: string;
    screenResolution: string;
    viewportSize: string;
    deviceType: 'mobile' | 'tablet' | 'desktop';
    platform: string;
    language: string;
    timezone: string;
}

export interface TrackingConfig {
    batchSize: number;
    flushInterval: number;
    maxRetries: number;
    apiEndpoint: string;
    enableDebug: boolean;
    enableOfflineStorage: boolean;
}

export interface ConsentStatus {
    analytics: boolean;
    marketing: boolean;
    necessary: boolean;
}

/**
 * Analytics Tracking Composable
 *
 * Provides comprehensive client-side event tracking with privacy compliance
 */
export function useAnalyticsTracking(config: Partial<TrackingConfig> = {}) {
    // Default configuration
    const defaultConfig: TrackingConfig = {
        batchSize: 10,
        flushInterval: 30000, // 30 seconds
        maxRetries: 3,
        apiEndpoint: '/api/analytics/events',
        enableDebug: false,
        enableOfflineStorage: true,
    };

    const trackingConfig = { ...defaultConfig, ...config };

    // State
    let sessionId = generateSessionId();
    let userId: string | undefined;
    let tenantId: string | undefined;
    let eventQueue: TrackingEvent[] = [];
    let consentStatus: ConsentStatus = { analytics: false, marketing: false, necessary: true };
    let isInitialized = false;
    let pageStartTime = Date.now();
    const scrollMilestones = new Set<number>();
    let flushTimer: NodeJS.Timeout | null = null;
    const retryTimeouts = new Map<string, NodeJS.Timeout>();

    // Initialize tracking
    const initialize = () => {
        if (isInitialized) return;

        // Generate or restore session ID
        sessionId = localStorage.getItem('analytics_session_id') || generateSessionId();
        localStorage.setItem('analytics_session_id', sessionId);

        // Restore consent status
        const storedConsent = localStorage.getItem('analytics_consent');
        if (storedConsent) {
            try {
                consentStatus = { ...consentStatus, ...JSON.parse(storedConsent) };
            } catch (error) {
                console.warn('Failed to parse stored consent:', error);
            }
        }

        // Get tenant ID
        tenantId = tenantValidationService.getCurrentTenantId() || undefined;

        // Setup automatic event listeners
        setupEventListeners();

        // Start batch processing
        startBatchProcessing();

        // Track initial page view
        trackPageView();

        isInitialized = true;

        if (trackingConfig.enableDebug) {
            console.log('AnalyticsTrackingService initialized', {
                sessionId,
                tenantId,
                consentStatus,
            });
        }
    };

    // Setup automatic event tracking
    const setupEventListeners = () => {
        // Page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                trackTimeOnPage();
            } else {
                pageStartTime = Date.now();
            }
        });

        // Before page unload
        window.addEventListener('beforeunload', () => {
            trackTimeOnPage();
            flushEvents(true);
        });

        // Click tracking
        document.addEventListener('click', handleClick, { passive: true });

        // Scroll tracking
        let scrollThrottle: NodeJS.Timeout | null = null;
        window.addEventListener(
            'scroll',
            () => {
                if (scrollThrottle) return;

                scrollThrottle = setTimeout(() => {
                    trackScroll();
                    scrollThrottle = null;
                }, 100);
            },
            { passive: true },
        );

        // Form tracking
        document.addEventListener('submit', handleFormSubmit);

        // Input tracking for form interactions
        document.addEventListener('focusin', handleFormInteraction);
        document.addEventListener('input', handleFormInteraction, { passive: true });

        // Error tracking
        window.addEventListener('error', handleError);
        window.addEventListener('unhandledrejection', handleUnhandledRejection);
    };

    // Generate unique session ID
    const generateSessionId = (): string => {
        return `session_${Date.now()}_${crypto.randomUUID()}`;
    };

    // Get device information
    const getDeviceInfo = (): DeviceInfo => {
        const screenWidth = screen.width;
        const screenHeight = screen.height;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let deviceType: 'mobile' | 'tablet' | 'desktop' = 'desktop';
        if (viewportWidth < 768) {
            deviceType = 'mobile';
        } else if (viewportWidth < 1024) {
            deviceType = 'tablet';
        }

        return {
            userAgent: navigator.userAgent,
            screenResolution: `${screenWidth}x${screenHeight}`,
            viewportSize: `${viewportWidth}x${viewportHeight}`,
            deviceType,
            platform: navigator.platform,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        };
    };

    // Check privacy compliance
    const checkPrivacyCompliance = (): { hasConsent: boolean; doNotTrack: boolean; anonymized: boolean } => {
        const doNotTrack = navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.msDoNotTrack === '1';

        const hasConsent = consentStatus.analytics && !doNotTrack;

        return {
            hasConsent,
            doNotTrack,
            anonymized: !hasConsent,
        };
    };

    // Anonymize data if no consent
    const anonymizeData = (data: Record<string, any>): Record<string, any> => {
        const sensitiveFields = ['email', 'name', 'phone', 'address', 'ip', 'userId'];
        const anonymized = { ...data };

        sensitiveFields.forEach((field) => {
            if (anonymized[field]) {
                anonymized[field] = '[ANONYMIZED]';
            }
        });

        return anonymized;
    };

    // Track custom event
    const trackEvent = (eventType: string, properties: Record<string, any> = {}) => {
        const compliance = checkPrivacyCompliance();
        const deviceInfo = getDeviceInfo();

        const event: TrackingEvent = {
            eventType,
            properties: compliance.anonymized ? anonymizeData(properties) : properties,
            timestamp: Date.now(),
            sessionId,
            userId,
            tenantId,
            complianceFlags: compliance,
            deviceInfo,
        };

        // Only track if we have consent or it's a necessary event
        if (!compliance.hasConsent && !isNecessaryEvent(eventType)) {
            if (trackingConfig.enableDebug) {
                console.log('Event not tracked due to privacy settings:', eventType);
            }
            return;
        }

        eventQueue.push(event);

        if (trackingConfig.enableDebug) {
            console.log('Event tracked:', event);
        }

        // Flush immediately for high-priority events or when batch is full
        if (isHighPriorityEvent(eventType) || eventQueue.length >= trackingConfig.batchSize) {
            flushEvents();
        }
    };

    // Check if event is necessary (not requiring consent)
    const isNecessaryEvent = (eventType: string): boolean => {
        const necessaryEvents = ['page_view', 'error', 'privacy_consent'];
        return necessaryEvents.includes(eventType);
    };

    // Check if event is high priority
    const isHighPriorityEvent = (eventType: string): boolean => {
        const highPriorityEvents = ['conversion', 'error', 'form_submit', 'privacy_consent'];
        return highPriorityEvents.includes(eventType);
    };

    // Automatic event handlers
    const trackPageView = () => {
        const page = window.location.pathname + window.location.search;
        trackEvent('page_view', {
            page,
            referrer: document.referrer,
            title: document.title,
        });

        // Reset page tracking
        pageStartTime = Date.now();
        scrollMilestones.clear();
    };

    const trackTimeOnPage = () => {
        const timeSpent = Date.now() - pageStartTime;
        if (timeSpent > 1000) {
            // Only track if more than 1 second
            trackEvent('time_on_page', {
                duration: timeSpent,
                page: window.location.pathname,
            });
        }
    };

    const handleClick = (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        if (!target) return;

        const rect = target.getBoundingClientRect();
        const elementInfo = {
            tagName: target.tagName,
            id: target.id,
            className: target.className,
            text: target.textContent?.substring(0, 100),
            x: event.clientX,
            y: event.clientY,
            elementX: rect.left,
            elementY: rect.top,
            elementWidth: rect.width,
            elementHeight: rect.height,
        };

        trackEvent('click', elementInfo);
    };

    const trackScroll = () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = Math.max(
            document.body.scrollHeight,
            document.body.offsetHeight,
            document.documentElement.clientHeight,
            document.documentElement.scrollHeight,
            document.documentElement.offsetHeight,
        );

        const scrollPercentage = Math.round((scrollTop / (documentHeight - windowHeight)) * 100);

        // Track milestones
        const milestone = Math.floor(scrollPercentage / 25) * 25;
        if ([25, 50, 75, 100].includes(milestone) && !scrollMilestones.has(milestone)) {
            scrollMilestones.add(milestone);
            trackEvent('scroll_milestone', {
                percentage: milestone,
                page: window.location.pathname,
            });
        }

        // Track continuous scroll (throttled)
        trackEvent('scroll', {
            scrollTop,
            scrollPercentage,
            windowHeight,
            documentHeight,
        });
    };

    const handleFormSubmit = (event: Event) => {
        const form = event.target as HTMLFormElement;
        if (!form) return;

        const formInfo = {
            formId: form.id,
            formAction: form.action,
            formMethod: form.method,
            fieldCount: form.elements.length,
            submitTime: Date.now(),
        };

        trackEvent('form_submit', formInfo);
    };

    const handleFormInteraction = (event: Event) => {
        const target = event.target as HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;
        if (!target || !['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;

        const interactionInfo = {
            fieldType: target.type || target.tagName.toLowerCase(),
            fieldName: target.name,
            fieldId: target.id,
            hasValue: Boolean(target.value),
            interactionType: event.type,
        };

        trackEvent('form_interaction', interactionInfo);
    };

    const handleError = (event: ErrorEvent) => {
        trackEvent('javascript_error', {
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            stack: event.error?.stack,
        });
    };

    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
        trackEvent('unhandled_promise_rejection', {
            reason: event.reason?.toString(),
            stack: event.reason?.stack,
        });
    };

    // Batch processing
    const startBatchProcessing = () => {
        if (flushTimer) return;

        flushTimer = setInterval(() => {
            if (eventQueue.length > 0) {
                flushEvents();
            }
        }, trackingConfig.flushInterval);
    };

    // Flush events to server
    const flushEvents = async (synchronous = false) => {
        if (eventQueue.length === 0) return;

        const events = [...eventQueue];
        eventQueue = [];

        try {
            if (synchronous) {
                // Use sendBeacon for synchronous sending (page unload)
                const data = JSON.stringify({ events });
                navigator.sendBeacon(trackingConfig.apiEndpoint, data);
            } else {
                // Use fetch for regular async sending
                await sendEventsWithRetry(events);
            }

            if (trackingConfig.enableDebug) {
                console.log('Events sent:', events.length);
            }
        } catch (error) {
            console.error('Failed to send events:', error);

            // Re-queue events for retry
            eventQueue.unshift(...events);

            // Store offline if enabled
            if (trackingConfig.enableOfflineStorage) {
                storeOfflineEvents(events);
            }
        }
    };

    // Send events with retry logic
    const sendEventsWithRetry = async (events: TrackingEvent[], attempt = 1): Promise<void> => {
        try {
            const response = await fetch(trackingConfig.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Session-ID': sessionId,
                    'X-Tenant-ID': tenantId || '',
                },
                body: JSON.stringify({ events }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
        } catch (error) {
            if (attempt < trackingConfig.maxRetries) {
                const delay = Math.pow(2, attempt) * 1000; // Exponential backoff
                if (trackingConfig.enableDebug) {
                    console.log(`Retrying event send in ${delay}ms (attempt ${attempt + 1})`);
                }

                await new Promise((resolve) => setTimeout(resolve, delay));
                return sendEventsWithRetry(events, attempt + 1);
            } else {
                throw error;
            }
        }
    };

    // Store events offline
    const storeOfflineEvents = (events: TrackingEvent[]) => {
        try {
            const stored = localStorage.getItem('analytics_offline_events');
            const existingEvents = stored ? JSON.parse(stored) : [];
            existingEvents.push(...events);

            // Limit storage to prevent memory issues
            const maxEvents = 500;
            if (existingEvents.length > maxEvents) {
                existingEvents.splice(0, existingEvents.length - maxEvents);
            }

            localStorage.setItem('analytics_offline_events', JSON.stringify(existingEvents));
        } catch (error) {
            console.error('Failed to store offline events:', error);
        }
    };

    // Privacy and consent management
    const updateConsent = (consent: Partial<ConsentStatus>) => {
        consentStatus = { ...consentStatus, ...consent };
        localStorage.setItem('analytics_consent', JSON.stringify(consentStatus));

        trackEvent('privacy_consent', {
            consentStatus,
            timestamp: Date.now(),
        });

        if (trackingConfig.enableDebug) {
            console.log('Consent updated:', consentStatus);
        }
    };

    const getConsentStatus = (): ConsentStatus => {
        return { ...consentStatus };
    };

    // Manual tracking methods
    const trackCustomEvent = (eventType: string, properties: Record<string, any> = {}) => {
        trackEvent(eventType, properties);
    };

    // Utility methods
    const setUserId = (id: string) => {
        userId = id;
    };

    const getSessionId = (): string => {
        return sessionId;
    };

    const getTenantId = (): string | undefined => {
        return tenantId;
    };

    const getEventQueueLength = (): number => {
        return eventQueue.length;
    };

    // Cleanup
    const destroy = () => {
        if (flushTimer) {
            clearInterval(flushTimer);
            flushTimer = null;
        }

        // Clear retry timeouts
        retryTimeouts.forEach((timeout) => clearTimeout(timeout));
        retryTimeouts.clear();

        // Flush remaining events
        flushEvents(true);

        // Remove event listeners
        document.removeEventListener('visibilitychange', () => {});
        window.removeEventListener('beforeunload', () => {});
        document.removeEventListener('click', handleClick);
        window.removeEventListener('scroll', () => {});
        document.removeEventListener('submit', handleFormSubmit);
        document.removeEventListener('focusin', handleFormInteraction);
        document.removeEventListener('input', handleFormInteraction);
        window.removeEventListener('error', handleError);
        window.removeEventListener('unhandledrejection', handleUnhandledRejection);
    };

    // Initialize on first use
    if (typeof window !== 'undefined') {
        // Use requestIdleCallback for non-blocking initialization
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => initialize());
        } else {
            setTimeout(() => initialize(), 0);
        }
    }

    // Return public API
    return {
        // Core tracking
        trackEvent: trackCustomEvent,
        trackPageView,
        trackTimeOnPage,

        // Privacy and consent
        updateConsent,
        getConsentStatus,

        // Configuration
        setUserId,
        getSessionId,
        getTenantId,
        getEventQueueLength,

        // Lifecycle
        initialize,
        destroy,

        // Debug
        flushEvents: () => flushEvents(false),
    };
}

// Export types for external use
export type { ConsentStatus, DeviceInfo, TrackingConfig, TrackingEvent };
