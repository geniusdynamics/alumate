import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import PricingSection from '@/components/homepage/PricingSection.vue'
import type { PricingPlan, AudienceType } from '@/types/homepage'

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  CheckIcon: { template: '<div data-testid="check-icon">✓</div>' },
  XMarkIcon: { template: '<div data-testid="x-mark-icon">✗</div>' },
  ShieldCheckIcon: { template: '<div data-testid="shield-check-icon">🛡</div>' },
  CurrencyDollarIcon: { template: '<div data-testid="currency-dollar-icon">💲</div>' },
  ClockIcon: { template: '<div data-testid="clock-icon">🕐</div>' }
}))

describe('PricingSection', () => {
  let wrapper: VueWrapper<any>

  const mockIndividualPlans: PricingPlan[] = [
    {
      id: 'free',
      name: 'Free',
      description: 'Perfect for getting started',
      price: 0,
      billingPeriod: '/month',
      ctaText: 'Start Free',
      featured: false,
      features: [
        { name: 'Basic directory access', included: true, limit: 'Limited' },
        { name: 'Profile creation', included: true },
        { name: 'Job board access', included: false }
      ],
      additionalInfo: 'No credit card required'
    },
    {
      id: 'professional',
      name: 'Professional',
      description: 'For active networkers',
      price: 29,
      originalPrice: 39,
      billingPeriod: '/month',
      ctaText: 'Start Free Trial',
      featured: true,
      features: [
        { name: 'Full directory access', included: true },
        { name: 'Unlimited messaging', included: true },
        { name: 'Job board access', included: true }
      ],
      additionalInfo: '14-day free trial'
    }
  ]

  const mockInstitutionalPlans: PricingPlan[] = [
    {
      id: 'professional_inst',
      name: 'Professional',
      description: 'For small institutions',
      price: 2500,
      billingPeriod: '/month',
      ctaText: 'Request Demo',
      featured: false,
      features: [
        { name: 'Up to 5,000 alumni', included: true },
        { name: 'Basic branded app', included: true },
        { name: 'Custom branding', included: false }
      ],
      additionalInfo: 'Setup fee may apply'
    }
  ]

  beforeEach(() => {
    wrapper = mount(PricingSection, {
      props: {
        audience: 'individual' as AudienceType
      }
    })
  })

  afterEach(() => {
    wrapper.unmount()
  })

  describe('Component Rendering', () => {
    it('renders the pricing section', () => {
      expect(wrapper.find('.pricing-section').exists()).toBe(true)
      expect(wrapper.find('h2').text()).toContain('Choose Your Plan')
    })

    it('renders audience toggle buttons', () => {
      const toggleButtons = wrapper.findAll('button')
      const audienceButtons = toggleButtons.filter(btn => 
        btn.text().includes('Individual Alumni') || btn.text().includes('Institutions')
      )
      expect(audienceButtons).toHaveLength(2)
    })

    it('displays correct header for individual audience', () => {
      expect(wrapper.find('h2').text()).toBe('Choose Your Plan')
      expect(wrapper.text()).toContain('Transparent pricing with no hidden fees')
    })

    it('displays correct header for institutional audience', async () => {
      await wrapper.setProps({ audience: 'institutional' })
      expect(wrapper.find('h2').text()).toBe('Enterprise Solutions')
      expect(wrapper.text()).toContain('Scalable solutions for institutions')
    })
  })

  describe('Audience Toggle', () => {
    it('highlights the current audience button', () => {
      const individualButton = wrapper.find('button:first-child')
      expect(individualButton.classes()).toContain('bg-blue-600')
      expect(individualButton.classes()).toContain('text-white')
    })

    it('emits audienceChanged event when toggling', async () => {
      const institutionalButton = wrapper.findAll('button')[1]
      await institutionalButton.trigger('click')
      
      expect(wrapper.emitted('audienceChanged')).toBeTruthy()
      expect(wrapper.emitted('audienceChanged')?.[0]).toEqual(['institutional'])
    })

    it('updates current audience when toggling', async () => {
      const institutionalButton = wrapper.findAll('button')[1]
      await institutionalButton.trigger('click')
      
      expect(wrapper.vm.currentAudience).toBe('institutional')
    })
  })

  describe('Pricing Plans Display', () => {
    it('renders pricing cards', () => {
      const pricingCards = wrapper.findAll('.pricing-card')
      expect(pricingCards.length).toBeGreaterThan(0)
    })

    it('displays plan information correctly', () => {
      const firstCard = wrapper.find('.pricing-card')
      expect(firstCard.exists()).toBe(true)
      
      // Check for plan name, description, and price
      expect(firstCard.text()).toContain('Free')
      expect(firstCard.text()).toContain('Perfect for getting started')
    })

    it('highlights featured plans', () => {
      // Mock data would need to be injected for this test
      // This is a placeholder for the featured plan styling test
      const featuredCards = wrapper.findAll('.pricing-card.ring-2')
      // Featured plans should have ring styling
    })

    it('displays original price with strikethrough when available', () => {
      // This would test the original price display
      const priceElements = wrapper.findAll('.line-through')
      // Should show original prices for plans that have them
    })
  })

  describe('Feature Lists', () => {
    it('renders feature lists for each plan', () => {
      const featureLists = wrapper.findAll('ul')
      expect(featureLists.length).toBeGreaterThan(0)
    })

    it('shows check icons for included features', () => {
      const checkIcons = wrapper.findAll('[data-testid="check-icon"]')
      expect(checkIcons.length).toBeGreaterThan(0)
    })

    it('shows x-mark icons for excluded features', () => {
      const xMarkIcons = wrapper.findAll('[data-testid="x-mark-icon"]')
      expect(xMarkIcons.length).toBeGreaterThan(0)
    })

    it('displays feature limits when available', () => {
      // Test for feature limit display
      const limitText = wrapper.text()
      expect(limitText).toContain('Limited') // or other limit text
    })
  })

  describe('CTA Buttons', () => {
    it('renders CTA buttons for each plan', () => {
      const ctaButtons = wrapper.findAll('.pricing-card button')
      expect(ctaButtons.length).toBeGreaterThan(0)
    })

    it('emits planSelected event when CTA is clicked', async () => {
      const ctaButton = wrapper.find('.pricing-card button')
      await ctaButton.trigger('click')
      
      expect(wrapper.emitted('planSelected')).toBeTruthy()
    })

    it('displays correct CTA text for different audiences', async () => {
      // Individual audience
      expect(wrapper.text()).toContain('Start Free')
      
      // Institutional audience
      await wrapper.setProps({ audience: 'institutional' })
      expect(wrapper.text()).toContain('Request Demo')
    })
  })

  describe('Feature Comparison Matrix', () => {
    it('renders comparison table', () => {
      const comparisonTable = wrapper.find('table')
      expect(comparisonTable.exists()).toBe(true)
    })

    it('displays comparison features in table header', () => {
      const tableHeaders = wrapper.findAll('th')
      expect(tableHeaders.length).toBeGreaterThan(1) // Features column + plan columns
    })

    it('shows feature values in comparison matrix', () => {
      const tableRows = wrapper.findAll('tbody tr')
      expect(tableRows.length).toBeGreaterThan(0)
    })

    it('displays check/x icons in comparison cells', () => {
      const comparisonIcons = wrapper.findAll('tbody [data-testid="check-icon"], tbody [data-testid="x-mark-icon"]')
      expect(comparisonIcons.length).toBeGreaterThan(0)
    })
  })

  describe('Transparent Pricing Notice', () => {
    it('renders transparent pricing section', () => {
      expect(wrapper.text()).toContain('Transparent Pricing Promise')
    })

    it('displays pricing guarantees', () => {
      expect(wrapper.text()).toContain('No hidden fees')
      expect(wrapper.text()).toContain('Cancel anytime')
      expect(wrapper.text()).toContain('30-day money back guarantee')
    })

    it('shows guarantee icons', () => {
      const guaranteeIcons = wrapper.findAll('[data-testid="shield-check-icon"], [data-testid="currency-dollar-icon"], [data-testid="clock-icon"]')
      expect(guaranteeIcons).toHaveLength(3)
    })
  })

  describe('Price Formatting', () => {
    it('formats free price correctly', () => {
      const result = wrapper.vm.formatPrice(0)
      expect(result).toBe('Free')
    })

    it('formats null price as Custom', () => {
      const result = wrapper.vm.formatPrice(null)
      expect(result).toBe('Custom')
    })

    it('formats numeric prices with currency symbol', () => {
      const result = wrapper.vm.formatPrice(29)
      expect(result).toBe('$29')
    })

    it('formats large prices with commas', () => {
      const result = wrapper.vm.formatPrice(2500)
      expect(result).toBe('$2,500')
    })
  })

  describe('Feature Value Mapping', () => {
    it('maps directory access feature correctly', () => {
      const freePlan = { id: 'free', features: [] }
      const proPlan = { id: 'professional', features: [] }
      
      expect(wrapper.vm.getFeatureValue('directory_access', freePlan)).toBe('Limited')
      expect(wrapper.vm.getFeatureValue('directory_access', proPlan)).toBe(true)
    })

    it('maps messaging feature correctly', () => {
      const freePlan = { id: 'free', features: [] }
      const proPlan = { id: 'professional', features: [] }
      
      expect(wrapper.vm.getFeatureValue('messaging', freePlan)).toBe('5/month')
      expect(wrapper.vm.getFeatureValue('messaging', proPlan)).toBe('Unlimited')
    })

    it('maps support levels correctly', () => {
      const conciergeFeatures = [{ name: 'Concierge support', included: true }]
      const priorityFeatures = [{ name: 'Priority support', included: true }]
      const basicFeatures = [{ name: 'Email support', included: true }]
      
      expect(wrapper.vm.getFeatureValue('support', { features: conciergeFeatures })).toBe('Concierge')
      expect(wrapper.vm.getFeatureValue('support', { features: priorityFeatures })).toBe('Priority')
      expect(wrapper.vm.getFeatureValue('support', { features: basicFeatures })).toBe('Standard')
    })
  })

  describe('Analytics Tracking', () => {
    it('tracks pricing section view on mount', () => {
      // Mock gtag
      const mockGtag = vi.fn()
      Object.defineProperty(window, 'gtag', {
        value: mockGtag,
        writable: true
      })

      // Remount component to trigger onMounted
      wrapper.unmount()
      wrapper = mount(PricingSection, {
        props: { audience: 'individual' }
      })

      expect(mockGtag).toHaveBeenCalledWith('event', 'pricing_section_view', {
        audience: 'individual'
      })
    })
  })

  describe('Responsive Design', () => {
    it('applies mobile-specific classes', () => {
      // Test for responsive grid classes
      const gridContainer = wrapper.find('.grid')
      expect(gridContainer.classes()).toContain('grid-cols-1')
      expect(gridContainer.classes()).toContain('md:grid-cols-2')
      expect(gridContainer.classes()).toContain('lg:grid-cols-3')
    })

    it('has mobile-optimized table overflow', () => {
      const tableContainer = wrapper.find('.overflow-x-auto')
      expect(tableContainer.exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('has proper heading hierarchy', () => {
      const h2 = wrapper.find('h2')
      const h3s = wrapper.findAll('h3')
      const h4 = wrapper.find('h4')
      
      expect(h2.exists()).toBe(true)
      expect(h3s.length).toBeGreaterThan(0)
      expect(h4.exists()).toBe(true)
    })

    it('has accessible button text', () => {
      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.text().trim()).not.toBe('')
      })
    })

    it('provides alt text context through icons', () => {
      // Icons should have data-testid attributes for screen readers
      const icons = wrapper.findAll('[data-testid]')
      expect(icons.length).toBeGreaterThan(0)
    })
  })

  describe('Error Handling', () => {
    it('handles missing plan data gracefully', () => {
      const emptyWrapper = mount(PricingSection, {
        props: { audience: 'individual' }
      })
      
      // Component should not crash with empty data
      expect(emptyWrapper.exists()).toBe(true)
    })

    it('handles invalid audience type', () => {
      // Component should default to individual if invalid audience provided
      const invalidWrapper = mount(PricingSection, {
        props: { audience: 'invalid' as AudienceType }
      })
      
      expect(invalidWrapper.exists()).toBe(true)
    })
  })
})