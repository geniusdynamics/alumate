import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import TrialSignupModal from '@/components/homepage/TrialSignupModal.vue'
import type { TrialSignupData } from '@/types/homepage'

// Mock Heroicons
vi.mock('@heroicons/vue/24/outline', () => ({
  XMarkIcon: { template: '<div data-testid="x-mark-icon">✗</div>' },
  CheckIcon: { template: '<div data-testid="check-icon">✓</div>' },
  CheckCircleIcon: { template: '<div data-testid="check-circle-icon">✓</div>' },
  ExclamationTriangleIcon: { template: '<div data-testid="exclamation-triangle-icon">⚠</div>' }
}))

// Mock LoadingSpinner
vi.mock('@/components/ui/LoadingSpinner.vue', () => ({
  default: { template: '<div data-testid="loading-spinner">Loading...</div>' }
}))

// Mock fetch
global.fetch = vi.fn()

describe('TrialSignupModal', () => {
  let wrapper: VueWrapper<any>

  beforeEach(() => {
    wrapper = mount(TrialSignupModal, {
      props: {
        isOpen: true,
        planId: 'professional'
      }
    })
    
    // Mock CSRF token
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">'
  })

  afterEach(() => {
    wrapper.unmount()
    vi.clearAllMocks()
  })

  describe('Component Rendering', () => {
    it('renders when isOpen is true', () => {
      expect(wrapper.find('.fixed.inset-0').exists()).toBe(true)
      expect(wrapper.text()).toContain('Start Your Free Trial')
    })

    it('does not render when isOpen is false', async () => {
      await wrapper.setProps({ isOpen: false })
      expect(wrapper.find('.fixed.inset-0').exists()).toBe(false)
    })

    it('displays trial benefits', () => {
      expect(wrapper.text()).toContain('Full alumni directory access')
      expect(wrapper.text()).toContain('Unlimited messaging')
      expect(wrapper.text()).toContain('Event creation & management')
      expect(wrapper.text()).toContain('Mentorship matching')
      expect(wrapper.text()).toContain('Priority support')
    })

    it('shows check icons for benefits', () => {
      const checkIcons = wrapper.findAll('[data-testid="check-icon"]')
      expect(checkIcons.length).toBeGreaterThan(0)
    })
  })

  describe('Form Fields', () => {
    it('renders all required form fields', () => {
      expect(wrapper.find('#name').exists()).toBe(true)
      expect(wrapper.find('#email').exists()).toBe(true)
      expect(wrapper.find('#graduationYear').exists()).toBe(true)
      expect(wrapper.find('#institution').exists()).toBe(true)
      expect(wrapper.find('#currentRole').exists()).toBe(true)
      expect(wrapper.find('#industry').exists()).toBe(true)
      expect(wrapper.find('#referralSource').exists()).toBe(true)
      expect(wrapper.find('#terms').exists()).toBe(true)
    })

    it('has proper field labels', () => {
      expect(wrapper.text()).toContain('Full Name *')
      expect(wrapper.text()).toContain('Email Address *')
      expect(wrapper.text()).toContain('Graduation Year')
      expect(wrapper.text()).toContain('Institution')
      expect(wrapper.text()).toContain('Current Role')
      expect(wrapper.text()).toContain('Industry')
      expect(wrapper.text()).toContain('How did you hear about us?')
    })

    it('has proper placeholders', () => {
      expect(wrapper.find('#name').attributes('placeholder')).toBe('Enter your full name')
      expect(wrapper.find('#email').attributes('placeholder')).toBe('Enter your email address')
      expect(wrapper.find('#graduationYear').attributes('placeholder')).toBe('e.g. 2020')
      expect(wrapper.find('#institution').attributes('placeholder')).toBe('Your university or college')
      expect(wrapper.find('#currentRole').attributes('placeholder')).toBe('Your current job title')
    })

    it('has industry options', () => {
      const industrySelect = wrapper.find('#industry')
      expect(industrySelect.html()).toContain('Technology')
      expect(industrySelect.html()).toContain('Finance')
      expect(industrySelect.html()).toContain('Healthcare')
      expect(industrySelect.html()).toContain('Education')
    })

    it('has referral source options', () => {
      const referralSelect = wrapper.find('#referralSource')
      expect(referralSelect.html()).toContain('Search Engine')
      expect(referralSelect.html()).toContain('Social Media')
      expect(referralSelect.html()).toContain('Friend/Colleague')
      expect(referralSelect.html()).toContain('University')
    })
  })

  describe('Form Validation', () => {
    it('validates required fields', async () => {
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(wrapper.text()).toContain('Name is required')
      expect(wrapper.text()).toContain('Email is required')
    })

    it('validates email format', async () => {
      await wrapper.find('#name').setValue('John Doe')
      await wrapper.find('#email').setValue('invalid-email')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(wrapper.text()).toContain('Please enter a valid email address')
    })

    it('validates terms acceptance', async () => {
      await wrapper.find('#name').setValue('John Doe')
      await wrapper.find('#email').setValue('john@example.com')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(wrapper.text()).toContain('Please accept the Terms of Service and Privacy Policy')
    })

    it('clears validation errors when corrected', async () => {
      // Trigger validation errors
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      expect(wrapper.text()).toContain('Name is required')

      // Fix the error
      await wrapper.find('#name').setValue('John Doe')
      await form.trigger('submit.prevent')
      expect(wrapper.text()).not.toContain('Name is required')
    })
  })

  describe('Form Submission', () => {
    beforeEach(async () => {
      // Fill out valid form data
      await wrapper.find('#name').setValue('John Doe')
      await wrapper.find('#email').setValue('john@example.com')
      await wrapper.find('#graduationYear').setValue('2020')
      await wrapper.find('#institution').setValue('Test University')
      await wrapper.find('#currentRole').setValue('Software Engineer')
      await wrapper.find('#industry').setValue('technology')
      await wrapper.find('#referralSource').setValue('search_engine')
      await wrapper.find('#terms').setChecked(true)
    })

    it('submits form with correct data', async () => {
      const mockResponse = { ok: true, json: () => Promise.resolve({ success: true }) }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(fetch).toHaveBeenCalledWith('/api/homepage/trial-signup', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'test-token'
        },
        body: JSON.stringify({
          name: 'John Doe',
          email: 'john@example.com',
          graduationYear: 2020,
          institution: 'Test University',
          currentRole: 'Software Engineer',
          industry: 'technology',
          referralSource: 'search_engine',
          acceptTerms: true,
          planId: 'professional',
          source: 'pricing_modal'
        })
      })
    })

    it('shows loading state during submission', async () => {
      const mockResponse = new Promise(resolve => 
        setTimeout(() => resolve({ ok: true, json: () => Promise.resolve({ success: true }) }), 100)
      )
      vi.mocked(fetch).mockReturnValueOnce(mockResponse as any)

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(wrapper.find('[data-testid="loading-spinner"]').exists()).toBe(true)
      expect(wrapper.text()).toContain('Starting Trial...')
      expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    })

    it('shows success message on successful submission', async () => {
      const mockResponse = { ok: true, json: () => Promise.resolve({ success: true }) }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('Trial Started Successfully!')
      expect(wrapper.text()).toContain('Check your email for login instructions')
    })

    it('emits success event on successful submission', async () => {
      const mockResponse = { ok: true, json: () => Promise.resolve({ success: true }) }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.emitted('success')).toBeTruthy()
    })

    it('shows error message on failed submission', async () => {
      const mockResponse = { 
        ok: false, 
        json: () => Promise.resolve({ message: 'Server error' }) 
      }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('Server error')
      expect(wrapper.find('[data-testid="exclamation-triangle-icon"]').exists()).toBe(true)
    })

    it('handles network errors gracefully', async () => {
      vi.mocked(fetch).mockRejectedValueOnce(new Error('Network error'))

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('Network error')
    })
  })

  describe('Modal Interactions', () => {
    it('emits close event when close button is clicked', async () => {
      const closeButton = wrapper.find('button')
      await closeButton.trigger('click')

      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('emits close event when backdrop is clicked', async () => {
      const backdrop = wrapper.find('.fixed.inset-0')
      await backdrop.trigger('click')

      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('does not close when clicking inside modal', async () => {
      const modal = wrapper.find('.relative.bg-white')
      await modal.trigger('click')

      expect(wrapper.emitted('close')).toBeFalsy()
    })

    it('prevents closing during submission', async () => {
      const mockResponse = new Promise(resolve => 
        setTimeout(() => resolve({ ok: true, json: () => Promise.resolve({ success: true }) }), 100)
      )
      vi.mocked(fetch).mockReturnValueOnce(mockResponse as any)

      // Start submission
      await wrapper.find('#name').setValue('John Doe')
      await wrapper.find('#email').setValue('john@example.com')
      await wrapper.find('#terms').setChecked(true)
      await wrapper.find('form').trigger('submit.prevent')

      // Try to close during submission
      const closeButton = wrapper.find('button')
      await closeButton.trigger('click')

      expect(wrapper.emitted('close')).toBeFalsy()
    })
  })

  describe('Form Reset', () => {
    it('resets form when modal reopens', async () => {
      // Fill form
      await wrapper.find('#name').setValue('John Doe')
      await wrapper.find('#email').setValue('john@example.com')

      // Close and reopen modal
      await wrapper.setProps({ isOpen: false })
      await wrapper.setProps({ isOpen: true })

      expect(wrapper.vm.form.name).toBe('')
      expect(wrapper.vm.form.email).toBe('')
    })

    it('clears errors when modal reopens', async () => {
      // Trigger validation errors
      await wrapper.find('form').trigger('submit.prevent')
      expect(wrapper.text()).toContain('Name is required')

      // Close and reopen modal
      await wrapper.setProps({ isOpen: false })
      await wrapper.setProps({ isOpen: true })

      expect(wrapper.text()).not.toContain('Name is required')
    })
  })

  describe('Analytics Tracking', () => {
    it('tracks successful trial signup', async () => {
      const mockGtag = vi.fn()
      Object.defineProperty(window, 'gtag', {
        value: mockGtag,
        writable: true
      })

      const mockResponse = { ok: true, json: () => Promise.resolve({ success: true }) }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      await wrapper.find('#name').setValue('John Doe')
      await wrapper.find('#email').setValue('john@example.com')
      await wrapper.find('#terms').setChecked(true)
      await wrapper.find('form').trigger('submit.prevent')

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(mockGtag).toHaveBeenCalledWith('event', 'trial_signup', {
        plan_id: 'professional',
        source: 'pricing_modal',
        value: 29
      })
    })
  })

  describe('Accessibility', () => {
    it('has proper form labels', () => {
      const labels = wrapper.findAll('label')
      expect(labels.length).toBeGreaterThan(0)
      
      labels.forEach(label => {
        expect(label.attributes('for')).toBeDefined()
      })
    })

    it('has proper ARIA attributes', () => {
      const requiredFields = wrapper.findAll('input[required]')
      requiredFields.forEach(field => {
        expect(field.attributes('required')).toBeDefined()
      })
    })

    it('shows validation errors with proper styling', async () => {
      await wrapper.find('form').trigger('submit.prevent')
      
      const errorFields = wrapper.findAll('.border-red-500')
      expect(errorFields.length).toBeGreaterThan(0)
    })
  })
})