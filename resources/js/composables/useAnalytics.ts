import { ref } from 'vue'

interface AnalyticsEvent {
  name: string
  properties?: Record<string, any>
  timestamp?: number
}

interface AnalyticsConfig {
  enabled?: boolean
  debug?: boolean
  providers?: ('gtag' | 'mixpanel' | 'amplitude' | 'custom')[]
}

const analyticsConfig = ref<AnalyticsConfig>({
  enabled: true,
  debug: false,
  providers: ['gtag']
})

const eventQueue = ref<AnalyticsEvent[]>([])

export function useAnalytics(config?: AnalyticsConfig) {
  if (config) {
    analyticsConfig.value = { ...analyticsConfig.value, ...config }
  }

  const trackEvent = (eventName: string, properties?: Record<string, any>) => {
    if (!analyticsConfig.value.enabled) return

    const event: AnalyticsEvent = {
      name: eventName,
      properties: {
        ...properties,
        timestamp: Date.now(),
        url: typeof window !== 'undefined' ? window.location.href : '',
        user_agent: typeof navigator !== 'undefined' ? navigator.userAgent : ''
      },
      timestamp: Date.now()
    }

    // Add to queue for debugging
    eventQueue.value.push(event)

    // Keep only last 100 events in queue
    if (eventQueue.value.length > 100) {
      eventQueue.value = eventQueue.value.slice(-100)
    }

    if (analyticsConfig.value.debug) {
      console.log('Analytics Event:', event)
    }

    // Send to configured providers
    analyticsConfig.value.providers?.forEach(provider => {
      sendToProvider(provider, event)
    })
  }

  const trackPageView = (path?: string, title?: string) => {
    const properties = {
      page_path: path || (typeof window !== 'undefined' ? window.location.pathname : ''),
      page_title: title || (typeof document !== 'undefined' ? document.title : ''),
      page_location: typeof window !== 'undefined' ? window.location.href : ''
    }

    trackEvent('page_view', properties)
  }

  const trackUserAction = (action: string, category: string, label?: string, value?: number) => {
    trackEvent('user_action', {
      action,
      category,
      label,
      value
    })
  }

  const trackTiming = (category: string, variable: string, time: number, label?: string) => {
    trackEvent('timing', {
      category,
      variable,
      time,
      label
    })
  }

  const trackError = (error: Error | string, context?: Record<string, any>) => {
    const errorData = typeof error === 'string' 
      ? { message: error }
      : {
          message: error.message,
          stack: error.stack,
          name: error.name
        }

    trackEvent('error', {
      ...errorData,
      ...context
    })
  }

  const setUserProperties = (properties: Record<string, any>) => {
    if (!analyticsConfig.value.enabled) return

    analyticsConfig.value.providers?.forEach(provider => {
      setUserPropertiesForProvider(provider, properties)
    })
  }

  const identify = (userId: string, traits?: Record<string, any>) => {
    if (!analyticsConfig.value.enabled) return

    analyticsConfig.value.providers?.forEach(provider => {
      identifyForProvider(provider, userId, traits)
    })
  }

  return {
    trackEvent,
    trackPageView,
    trackUserAction,
    trackTiming,
    trackError,
    setUserProperties,
    identify,
    eventQueue: eventQueue.value,
    config: analyticsConfig.value
  }
}

// Provider-specific implementations
function sendToProvider(provider: string, event: AnalyticsEvent) {
  switch (provider) {
    case 'gtag':
      sendToGtag(event)
      break
    case 'mixpanel':
      sendToMixpanel(event)
      break
    case 'amplitude':
      sendToAmplitude(event)
      break
    case 'custom':
      sendToCustomProvider(event)
      break
  }
}

function sendToGtag(event: AnalyticsEvent) {
  if (typeof window !== 'undefined' && 'gtag' in window) {
    // @ts-ignore
    window.gtag('event', event.name, event.properties)
  }
}

function sendToMixpanel(event: AnalyticsEvent) {
  if (typeof window !== 'undefined' && 'mixpanel' in window) {
    // @ts-ignore
    window.mixpanel.track(event.name, event.properties)
  }
}

function sendToAmplitude(event: AnalyticsEvent) {
  if (typeof window !== 'undefined' && 'amplitude' in window) {
    // @ts-ignore
    window.amplitude.getInstance().logEvent(event.name, event.properties)
  }
}

function sendToCustomProvider(event: AnalyticsEvent) {
  // Custom analytics implementation
  // This could send to your own analytics API
  if (typeof window !== 'undefined') {
    fetch('/api/analytics/events', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(event)
    }).catch(error => {
      console.error('Failed to send analytics event:', error)
    })
  }
}

function setUserPropertiesForProvider(provider: string, properties: Record<string, any>) {
  switch (provider) {
    case 'gtag':
      if (typeof window !== 'undefined' && 'gtag' in window) {
        // @ts-ignore
        window.gtag('config', 'GA_MEASUREMENT_ID', {
          user_properties: properties
        })
      }
      break
    case 'mixpanel':
      if (typeof window !== 'undefined' && 'mixpanel' in window) {
        // @ts-ignore
        window.mixpanel.people.set(properties)
      }
      break
    case 'amplitude':
      if (typeof window !== 'undefined' && 'amplitude' in window) {
        // @ts-ignore
        window.amplitude.getInstance().setUserProperties(properties)
      }
      break
  }
}

function identifyForProvider(provider: string, userId: string, traits?: Record<string, any>) {
  switch (provider) {
    case 'gtag':
      if (typeof window !== 'undefined' && 'gtag' in window) {
        // @ts-ignore
        window.gtag('config', 'GA_MEASUREMENT_ID', {
          user_id: userId
        })
      }
      break
    case 'mixpanel':
      if (typeof window !== 'undefined' && 'mixpanel' in window) {
        // @ts-ignore
        window.mixpanel.identify(userId)
        if (traits) {
          // @ts-ignore
          window.mixpanel.people.set(traits)
        }
      }
      break
    case 'amplitude':
      if (typeof window !== 'undefined' && 'amplitude' in window) {
        // @ts-ignore
        window.amplitude.getInstance().setUserId(userId)
        if (traits) {
          // @ts-ignore
          window.amplitude.getInstance().setUserProperties(traits)
        }
      }
      break
  }
}