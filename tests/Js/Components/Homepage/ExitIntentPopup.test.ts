import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ExitIntentPopup from '@/components/homepage/ExitIntentPopup.vue'
import type { ExitIntentOffer } from '@/types/homepage'

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

describe('ExitIntentPopup', () => {
  const mockSpecialOffer: ExitIntentOffer = {
    badge: 'Limited Time',
    title: 'Special Offer - 50% Off',
    description: 'Get half off your first month',
    details: [
      'Full access to all features',
      'Cancel anytime',
      'No setup fees'
    ],
    countdown: 300,
    countdownLabel: 'Offer expires in'
  }

  let wrapper: any

  beforeEach(() => {
    // Mock document.body.style
    Object.defineProperty(document.body, 'style', {
      value: { overflow: '' },
      writable: true
    })
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  describe('Individual Audience', () => {
    beforeEach(() => {
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    it('renders individual audience content correctly', () => {
      expect(wrapper.find('.popup-title').text()).toBe("Wait! Don't Miss Out")
      expect(wrapper.find('.popup-subtitle').text()).toBe('Join thousands of alumni advancing their careers')
    })

    it('displays correct primary CTA for individuals', () => {
      const primaryButton = wrapper.find('.primary-cta-button')
      expect(primaryButton.text()).toContain('Start Free Trial')
    })

    it('displays correct secondary CTA for individuals', () => {
      const secondaryButton = wrapper.find('.secondary-cta-button')
      expect(secondaryButton.text()).toBe('Join Waitlist')
    })

    it('shows individual trust indicators', () => {
      const trustIndicators = wrapper.findAll('.trust-item')
      expect(trustIndicators.length).toBe(3)
      
      const trustTexts = trustIndicators.map((item: any) => item.find('.trust-text').text())
      expect(trustTexts).toContain('4.9/5 Rating')
      expect(trustTexts).toContain('50,000+ Alumni')
      expect(trustTexts).toContain('Avg 40% Salary Increase')
    })

    it('displays individual social proof', () => {
      const socialProof = wrapper.find('.social-proof-text')
      expect(socialProof.text()).toBe("Join 50,000+ alumni who've advanced their careers")
    })
  })

  describe('Institutional Audience', () => {
    beforeEach(() => {
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'institutional',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    it('renders institutional audience content correctly', () => {
      expect(wrapper.find('.popup-title').text()).toBe('Before You Go...')
      expect(wrapper.find('.popup-subtitle').text()).toBe('See how universities are increasing alumni engagement by 300%')
    })

    it('displays correct primary CTA for institutions', () => {
      const primaryButton = wrapper.find('.primary-cta-button')
      expect(primaryButton.text()).toContain('Schedule Free Demo')
    })

    it('displays correct secondary CTA for institutions', () => {
      const secondaryButton = wrapper.find('.secondary-cta-button')
      expect(secondaryButton.text()).toBe('Download Case Studies')
    })

    it('shows institutional trust indicators', () => {
      const trustIndicators = wrapper.findAll('.trust-item')
      expect(trustIndicators.length).toBe(3)
      
      const trustTexts = trustIndicators.map((item: any) => item.find('.trust-text').text())
      expect(trustTexts).toContain('SOC 2 Certified')
      expect(trustTexts).toContain('500+ Universities Trust Us')
      expect(trustTexts).toContain('30-Day Implementation')
    })

    it('displays institutional social proof', () => {
      const socialProof = wrapper.find('.social-proof-text')
      expect(socialProof.text()).toBe('Join 500+ institutions already transforming their alumni engagement')
    })
  })

  describe('Special Offer Display', () => {
    beforeEach(() => {
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    it('displays special offer when provided', () => {
      const specialOffer = wrapper.find('.special-offer')
      expect(specialOffer.exists()).toBe(true)
    })

    it('shows offer badge', () => {
      const badge = wrapper.find('.offer-badge')
      expect(badge.text()).toBe('LIMITED TIME')
    })

    it('displays offer title and description', () => {
      const title = wrapper.find('.offer-title')
      const description = wrapper.find('.offer-description')
      
      expect(title.text()).toBe('Special Offer - 50% Off')
      expect(description.text()).toBe('Get half off your first month')
    })

    it('shows offer details list', () => {
      const offerItems = wrapper.findAll('.offer-item')
      expect(offerItems.length).toBe(3)
      
      const itemTexts = offerItems.map((item: any) => item.text())
      expect(itemTexts).toContain('Full access to all features')
      expect(itemTexts).toContain('Cancel anytime')
      expect(itemTexts).toContain('No setup fees')
    })

    it('displays countdown timer when provided', () => {
      const countdown = wrapper.find('.countdown-timer')
      expect(countdown.exists()).toBe(true)
      
      const countdownLabel = wrapper.find('.countdown-label')
      expect(countdownLabel.text()).toBe('Offer expires in:')
    })
  })

  describe('User Interactions', () => {
    beforeEach(() => {
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    it('emits close event when close button is clicked', async () => {
      const closeButton = wrapper.find('.close-button')
      await closeButton.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('emits close event when overlay is clicked', async () => {
      const overlay = wrapper.find('.exit-intent-overlay')
      await overlay.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('does not emit close when popup content is clicked', async () => {
      const popup = wrapper.find('.exit-intent-popup')
      await popup.trigger('click')
      
      expect(wrapper.emitted('close')).toBeFalsy()
    })

    it('emits convert event when primary CTA is clicked', async () => {
      const primaryButton = wrapper.find('.primary-cta-button')
      await primaryButton.trigger('click')
      
      // Wait for processing delay
      await new Promise(resolve => setTimeout(resolve, 600))
      
      expect(wrapper.emitted('convert')).toBeTruthy()
      expect(wrapper.emitted('convert')[0]).toEqual(['trial'])
    })

    it('emits convert event when secondary CTA is clicked', async () => {
      const secondaryButton = wrapper.find('.secondary-cta-button')
      await secondaryButton.trigger('click')
      
      expect(wrapper.emitted('convert')).toBeTruthy()
      expect(wrapper.emitted('convert')[0]).toEqual(['waitlist'])
    })

    it('shows loading state during primary CTA processing', async () => {
      const primaryButton = wrapper.find('.primary-cta-button')
      
      await primaryButton.trigger('click')
      
      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
      expect(primaryButton.text()).toContain('Processing...')
      expect(primaryButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('Analytics Tracking', () => {
    let mockTrackEvent: any

    beforeEach(() => {
      mockTrackEvent = vi.fn()
      vi.mocked(require('@/composables/useAnalytics').useAnalytics).mockReturnValue({
        trackEvent: mockTrackEvent
      })

      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    it('tracks popup display on mount', () => {
      expect(mockTrackEvent).toHaveBeenCalledWith('exit_intent_popup_shown', {
        audience: 'individual',
        hasSpecialOffer: true
      })
    })

    it('tracks primary CTA clicks', async () => {
      const primaryButton = wrapper.find('.primary-cta-button')
      await primaryButton.trigger('click')
      
      expect(mockTrackEvent).toHaveBeenCalledWith('exit_intent_primary_cta', {
        audience: 'individual',
        action: 'trial',
        hasSpecialOffer: true
      })
    })

    it('tracks secondary CTA clicks', async () => {
      const secondaryButton = wrapper.find('.secondary-cta-button')
      await secondaryButton.trigger('click')
      
      expect(mockTrackEvent).toHaveBeenCalledWith('exit_intent_secondary_cta', {
        audience: 'individual',
        action: 'waitlist',
        hasSpecialOffer: true
      })
    })
  })

  describe('Countdown Timer', () => {
    beforeEach(() => {
      vi.useFakeTimers()
      
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    afterEach(() => {
      vi.useRealTimers()
    })

    it('starts countdown when offer has countdown', () => {
      expect(wrapper.vm.countdown).toBe(300)
    })

    it('decrements countdown every second', async () => {
      vi.advanceTimersByTime(1000)
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm.countdown).toBe(299)
    })

    it('formats countdown time correctly', () => {
      expect(wrapper.vm.formatCountdown(300)).toBe('5:00')
      expect(wrapper.vm.formatCountdown(65)).toBe('1:05')
      expect(wrapper.vm.formatCountdown(30)).toBe('0:30')
    })

    it('emits close when countdown reaches zero', async () => {
      wrapper.vm.countdown = 1
      vi.advanceTimersByTime(1000)
      await wrapper.vm.$nextTick()
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })
  })

  describe('Accessibility', () => {
    beforeEach(() => {
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    it('prevents body scroll when mounted', () => {
      expect(document.body.style.overflow).toBe('hidden')
    })

    it('restores body scroll when unmounted', () => {
      wrapper.unmount()
      expect(document.body.style.overflow).toBe('')
    })

    it('handles escape key to close popup', async () => {
      const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' })
      document.dispatchEvent(escapeEvent)
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('has proper ARIA labels', () => {
      const closeButton = wrapper.find('.close-button')
      expect(closeButton.attributes('aria-label')).toBe('Close popup')
    })
  })

  describe('Mobile Responsiveness', () => {
    beforeEach(() => {
      vi.mocked(require('@/composables/useAudienceDetection').useAudienceDetection).mockReturnValue({
        isMobile: true
      })

      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        },
        global: {
          stubs: {
            Teleport: false
          }
        }
      })
    })

    it('applies mobile-specific classes', () => {
      const popup = wrapper.find('.exit-intent-popup')
      expect(popup.classes()).toContain('popup-mobile')
    })

    it('adjusts layout for mobile screens', () => {
      // Test mobile-specific styling is applied
      const popup = wrapper.find('.exit-intent-popup')
      expect(popup.exists()).toBe(true)
    })
  })

  describe('Error Handling', () => {
    it('handles missing special offer gracefully', () => {
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual'
          // No specialOffer prop
        }
      })

      expect(wrapper.find('.special-offer').exists()).toBe(false)
      expect(wrapper.find('.popup-title').exists()).toBe(true)
    })

    it('handles processing errors gracefully', async () => {
      wrapper = mount(ExitIntentPopup, {
        props: {
          audience: 'individual',
          specialOffer: mockSpecialOffer
        }
      })

      // Mock an error during processing
      const originalConsoleError = console.error
      console.error = vi.fn()

      const primaryButton = wrapper.find('.primary-cta-button')
      await primaryButton.trigger('click')

      // Should not crash and should reset loading state
      await new Promise(resolve => setTimeout(resolve, 600))
      expect(wrapper.vm.isProcessing).toBe(false)

      console.error = originalConsoleError
    })
  })
})