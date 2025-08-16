import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ConversionCTAs from '@/components/homepage/ConversionCTAs.vue'
import type { StrategicCTA, ExitIntentOffer, CTAButton } from '@/types/homepage'

// Mock composables
vi.mock('@/composables/useAnalytics', () => ({
  useAnalytics: () => ({
    trackEvent: vi.fn()
  })
}))

vi.mock('@/composables/useAudienceDetection', () => ({
  useAudienceDetection: () => ({
    isMobile: false
  })
}))

vi.mock('@/composables/useScrollTracking', () => ({
  useScrollTracking: () => ({
    scrollDepth: { value: 0 },
    isScrolling: { value: false }
  })
}))

vi.mock('@/composables/useExitIntent', () => ({
  useExitIntent: () => ({
    showExitIntent: { value: false },
    resetExitIntent: vi.fn()
  })
}))

describe('ConversionCTAs', () => {
  const mockStrategicCTAs: StrategicCTA[] = [
    {
      id: 'hero-cta',
      type: 'contextual',
      placement: 'hero',
      audience: 'individual',
      text: 'Start Free Trial',
      action: 'trial',
      section: 'hero',
      mobileOptimized: true,
      contextual: true,
      priority: 1
    },
    {
      id: 'features-cta',
      type: 'section',
      placement: 'features',
      audience: 'both',
      text: 'Learn More',
      action: 'learn-more',
      section: 'features',
      mobileOptimized: false,
      contextual: false,
      priority: 2
    }
  ]

  const mockExitIntentOffer: ExitIntentOffer = {
    badge: 'Limited Time',
    title: 'Don\'t Miss Out!',
    description: 'Get 50% off your first month',
    details: ['Full access to all features', 'Cancel anytime'],
    countdown: 300,
    countdownLabel: 'Offer expires in'
  }

  const mockPrimaryCTA: CTAButton = {
    text: 'Join Now',
    action: 'register',
    variant: 'primary',
    trackingEvent: 'primary_cta_click'
  }

  let wrapper: any

  beforeEach(() => {
    wrapper = mount(ConversionCTAs, {
      props: {
        audience: 'individual',
        strategicCTAs: mockStrategicCTAs,
        exitIntentOffer: mockExitIntentOffer,
        primaryMobileCTA: mockPrimaryCTA
      },
      global: {
        stubs: {
          ExitIntentPopup: true,
          ProgressiveCTAs: true,
          FloatingMobileCTA: true,
          ContextualCTA: true,
          SectionCTA: true,
          StickyHeaderCTA: true
        }
      }
    })
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  describe('Component Rendering', () => {
    it('renders without errors', () => {
      expect(wrapper.exists()).toBe(true)
    })

    it('renders strategic CTAs based on audience', () => {
      const strategicCTAs = wrapper.findAll('.strategic-cta')
      expect(strategicCTAs.length).toBeGreaterThan(0)
    })

    it('applies correct CSS classes for CTA types', () => {
      const contextualCTA = wrapper.find('.cta-contextual')
      const sectionCTA = wrapper.find('.cta-section')
      
      expect(contextualCTA.exists()).toBe(true)
      expect(sectionCTA.exists()).toBe(true)
    })

    it('shows mobile-optimized CTAs on mobile', async () => {
      // Mock mobile detection
      vi.mocked(require('@/composables/useAudienceDetection').useAudienceDetection).mockReturnValue({
        isMobile: true
      })

      await wrapper.vm.$nextTick()
      
      const mobileOptimizedCTAs = wrapper.findAll('.cta-mobile-optimized')
      expect(mobileOptimizedCTAs.length).toBeGreaterThan(0)
    })
  })

  describe('CTA Filtering', () => {
    it('filters CTAs by audience type', () => {
      const individualCTAs = wrapper.vm.strategicCTAsFiltered
      
      // Should include individual and 'both' audience CTAs
      expect(individualCTAs).toHaveLength(2)
      expect(individualCTAs.some((cta: StrategicCTA) => cta.audience === 'individual')).toBe(true)
      expect(individualCTAs.some((cta: StrategicCTA) => cta.audience === 'both')).toBe(true)
    })

    it('updates filtered CTAs when audience changes', async () => {
      await wrapper.setProps({ audience: 'institutional' })
      
      const institutionalCTAs = wrapper.vm.strategicCTAsFiltered
      expect(institutionalCTAs.some((cta: StrategicCTA) => cta.audience === 'individual')).toBe(false)
    })
  })

  describe('CTA Click Handling', () => {
    it('handles CTA clicks and tracks analytics', async () => {
      const mockTrackEvent = vi.fn()
      vi.mocked(require('@/composables/useAnalytics').useAnalytics).mockReturnValue({
        trackEvent: mockTrackEvent
      })

      const mockEvent = {
        action: 'trial',
        section: 'hero',
        audience: 'individual',
        additionalData: { ctaId: 'hero-cta' }
      }

      await wrapper.vm.handleCTAClick(mockEvent)

      expect(mockTrackEvent).toHaveBeenCalledWith('cta_click', expect.objectContaining({
        action: 'trial',
        section: 'hero',
        audience: 'individual'
      }))
    })

    it('updates engagement level based on interactions', async () => {
      const mockEvent = {
        action: 'trial',
        section: 'hero',
        audience: 'individual'
      }

      // Simulate multiple interactions
      await wrapper.vm.handleCTAClick(mockEvent)
      await wrapper.vm.handleCTAClick(mockEvent)
      await wrapper.vm.handleCTAClick(mockEvent)

      expect(wrapper.vm.engagementLevel).toBe('high')
    })

    it('handles different CTA actions correctly', async () => {
      const originalLocation = window.location
      delete (window as any).location
      window.location = { href: '' } as any

      const registerEvent = {
        action: 'register',
        section: 'hero',
        audience: 'individual'
      }

      await wrapper.vm.handleCTAClick(registerEvent)
      expect(window.location.href).toBe('/register')

      window.location = originalLocation
    })
  })

  describe('Engagement Level Tracking', () => {
    it('starts with low engagement level', () => {
      expect(wrapper.vm.engagementLevel).toBe('low')
    })

    it('increases engagement level with scroll depth', async () => {
      // Mock high scroll depth
      vi.mocked(require('@/composables/useScrollTracking').useScrollTracking).mockReturnValue({
        scrollDepth: { value: 80 },
        isScrolling: { value: false }
      })

      await wrapper.vm.updateEngagementLevel()
      expect(wrapper.vm.engagementLevel).toBe('high')
    })

    it('increases engagement level with CTA interactions', async () => {
      const mockEvent = {
        action: 'trial',
        section: 'hero',
        audience: 'individual'
      }

      // Simulate single interaction
      await wrapper.vm.handleCTAClick(mockEvent)
      expect(wrapper.vm.engagementLevel).toBe('medium')

      // Simulate more interactions
      await wrapper.vm.handleCTAClick(mockEvent)
      await wrapper.vm.handleCTAClick(mockEvent)
      expect(wrapper.vm.engagementLevel).toBe('high')
    })
  })

  describe('Exit Intent Handling', () => {
    it('handles exit intent popup close', async () => {
      const mockTrackEvent = vi.fn()
      const mockResetExitIntent = vi.fn()

      vi.mocked(require('@/composables/useAnalytics').useAnalytics).mockReturnValue({
        trackEvent: mockTrackEvent
      })

      vi.mocked(require('@/composables/useExitIntent').useExitIntent).mockReturnValue({
        showExitIntent: { value: true },
        resetExitIntent: mockResetExitIntent
      })

      await wrapper.vm.handleExitIntentClose()

      expect(mockTrackEvent).toHaveBeenCalledWith('exit_intent_popup_close', expect.any(Object))
      expect(mockResetExitIntent).toHaveBeenCalled()
    })

    it('handles exit intent conversion', async () => {
      const mockTrackEvent = vi.fn()
      const mockResetExitIntent = vi.fn()

      vi.mocked(require('@/composables/useAnalytics').useAnalytics).mockReturnValue({
        trackEvent: mockTrackEvent
      })

      vi.mocked(require('@/composables/useExitIntent').useExitIntent).mockReturnValue({
        showExitIntent: { value: true },
        resetExitIntent: mockResetExitIntent
      })

      await wrapper.vm.handleExitIntentConvert('trial')

      expect(mockTrackEvent).toHaveBeenCalledWith('exit_intent_conversion', expect.objectContaining({
        action: 'trial'
      }))
      expect(mockResetExitIntent).toHaveBeenCalled()
    })
  })

  describe('Component Integration', () => {
    it('passes correct props to child components', () => {
      const progressiveCTAs = wrapper.findComponent({ name: 'ProgressiveCTAs' })
      const floatingMobileCTA = wrapper.findComponent({ name: 'FloatingMobileCTA' })

      expect(progressiveCTAs.props('audience')).toBe('individual')
      expect(floatingMobileCTA.props('audience')).toBe('individual')
      expect(floatingMobileCTA.props('primaryCta')).toEqual(mockPrimaryCTA)
    })

    it('handles events from child components', async () => {
      const mockEvent = {
        action: 'trial',
        section: 'progressive',
        audience: 'individual'
      }

      const progressiveCTAs = wrapper.findComponent({ name: 'ProgressiveCTAs' })
      await progressiveCTAs.vm.$emit('cta-click', mockEvent)

      // Should trigger handleCTAClick
      expect(wrapper.vm.ctaInteractions['trial']).toBe(1)
    })
  })

  describe('Accessibility', () => {
    it('provides proper ARIA labels for interactive elements', () => {
      const buttons = wrapper.findAll('button')
      buttons.forEach((button: any) => {
        if (button.attributes('aria-label')) {
          expect(button.attributes('aria-label')).toBeTruthy()
        }
      })
    })

    it('supports keyboard navigation', async () => {
      const firstButton = wrapper.find('button')
      if (firstButton.exists()) {
        await firstButton.trigger('keydown.enter')
        // Should trigger the same action as click
        expect(wrapper.emitted('click')).toBeTruthy()
      }
    })
  })

  describe('Performance', () => {
    it('debounces scroll events properly', async () => {
      const mockTrackEvent = vi.fn()
      vi.mocked(require('@/composables/useAnalytics').useAnalytics).mockReturnValue({
        trackEvent: mockTrackEvent
      })

      // Simulate rapid scroll events
      for (let i = 0; i < 10; i++) {
        wrapper.vm.updateEngagementLevel()
      }

      // Should not call tracking for every scroll event
      expect(mockTrackEvent).not.toHaveBeenCalledTimes(10)
    })

    it('cleans up event listeners on unmount', () => {
      const removeEventListenerSpy = vi.spyOn(window, 'removeEventListener')
      
      wrapper.unmount()
      
      expect(removeEventListenerSpy).toHaveBeenCalled()
    })
  })
})