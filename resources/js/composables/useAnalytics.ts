export function useAnalytics() {
  const trackEvent = (eventName: string, properties?: Record<string, any>) => {
    // Placeholder for analytics tracking
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', eventName, properties)
    } else {
      console.log('Analytics Event:', eventName, properties)
    }
  }

  const trackPageView = (page: string) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('config', 'GA_MEASUREMENT_ID', {
        page_title: document.title,
        page_location: window.location.href
      })
    }
  }

  return {
    trackEvent,
    trackPageView
  }
}
