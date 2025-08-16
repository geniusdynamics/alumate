import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import EnterpriseDemo from '@/components/homepage/EnterpriseDemo.vue'
import type { DemoRequestData } from '@/types/homepage'

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

describe('EnterpriseDemo', () => {
  let wrapper: VueWrapper<any>

  beforeEach(() => {
    wrapper = mount(EnterpriseDemo, {
      props: {
        isOpen: true,
        planId: 'enterprise'
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
      expect(wrapper.text()).toContain('Request Enterprise Demo')
    })

    it('does not render when isOpen is false', async () => {
      await wrapper.setProps({ isOpen: false })
      expect(wrapper.find('.fixed.inset-0').exists()).toBe(false)
    })

    it('displays demo benefits', () => {
      expect(wrapper.text()).toContain('Custom Branded App')
      expect(wrapper.text()).toContain('Admin Dashboard')
      expect(wrapper.text()).toContain('Analytics & Insights')
      expect(wrapper.text()).toContain('Integration Options')
    })

    it('shows check icons for benefits', () => {
      const checkIcons = wrapper.findAll('[data-testid="check-icon"]')
      expect(checkIcons.length).toBeGreaterThan(0)
    })

    it('displays what will be shown in demo', () => {
      expect(wrapper.text()).toContain('What you\'ll see in your demo:')
      expect(wrapper.text()).toContain('See your institution\'s mobile app')
      expect(wrapper.text()).toContain('Comprehensive management tools')
      expect(wrapper.text()).toContain('Track engagement and ROI')
      expect(wrapper.text()).toContain('Connect with existing systems')
    })
  })

  describe('Form Fields', () => {
    it('renders all required form fields', () => {
      expect(wrapper.find('#institutionName').exists()).toBe(true)
      expect(wrapper.find('#contactName').exists()).toBe(true)
      expect(wrapper.find('#email').exists()).toBe(true)
      expect(wrapper.find('#title').exists()).toBe(true)
      expect(wrapper.find('#phone').exists()).toBe(true)
      expect(wrapper.find('#alumniCount').exists()).toBe(true)
      expect(wrapper.find('#currentSolution').exists()).toBe(true)
      expect(wrapper.find('#preferredTime').exists()).toBe(true)
      expect(wrapper.find('#message').exists()).toBe(true)
    })

    it('has proper field labels', () => {
      expect(wrapper.text()).toContain('Institution Name *')
      expect(wrapper.text()).toContain('Your Name *')
      expect(wrapper.text()).toContain('Email Address *')
      expect(wrapper.text()).toContain('Job Title')
      expect(wrapper.text()).toContain('Phone Number')
      expect(wrapper.text()).toContain('Alumni Count')
      expect(wrapper.text()).toContain('Current Alumni Management Solution')
      expect(wrapper.text()).toContain('Preferred Demo Time')
      expect(wrapper.text()).toContain('Additional Information')
    })

    it('has alumni count options', () => {
      const alumniSelect = wrapper.find('#alumniCount')
      expect(alumniSelect.html()).toContain('Under 1,000')
      expect(alumniSelect.html()).toContain('1,000 - 5,000')
      expect(alumniSelect.html()).toContain('5,000 - 10,000')
      expect(alumniSelect.html()).toContain('10,000 - 25,000')
      expect(alumniSelect.html()).toContain('25,000 - 50,000')
      expect(alumniSelect.html()).toContain('Over 50,000')
    })

    it('has current solution options', () => {
      const solutionSelect = wrapper.find('#currentSolution')
      expect(solutionSelect.html()).toContain('No current solution')
      expect(solutionSelect.html()).toContain('Spreadsheets/Manual tracking')
      expect(solutionSelect.html()).toContain('CRM system')
      expect(solutionSelect.html()).toContain('Other alumni platform')
      expect(solutionSelect.html()).toContain('Custom-built solution')
    })

    it('has preferred time options', () => {
      const timeSelect = wrapper.find('#preferredTime')
      expect(timeSelect.html()).toContain('Morning (9 AM - 12 PM)')
      expect(timeSelect.html()).toContain('Afternoon (12 PM - 5 PM)')
      expect(timeSelect.html()).toContain('Evening (5 PM - 8 PM)')
      expect(timeSelect.html()).toContain('I\'m flexible')
    })

    it('renders interest checkboxes', () => {
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      expect(checkboxes.length).toBeGreaterThan(0)
      
      expect(wrapper.text()).toContain('Mobile App')
      expect(wrapper.text()).toContain('Alumni Directory')
      expect(wrapper.text()).toContain('Event Management')
      expect(wrapper.text()).toContain('Fundraising')
      expect(wrapper.text()).toContain('Mentorship Programs')
      expect(wrapper.text()).toContain('Job Board')
      expect(wrapper.text()).toContain('Analytics & Reporting')
      expect(wrapper.text()).toContain('System Integrations')
      expect(wrapper.text()).toContain('Custom Branding')
    })
  })

  describe('Form Validation', () => {
    it('validates required fields', async () => {
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(wrapper.text()).toContain('Institution name is required')
      expect(wrapper.text()).toContain('Your name is required')
      expect(wrapper.text()).toContain('Email is required')
    })

    it('validates email format', async () => {
      await wrapper.find('#institutionName').setValue('Test University')
      await wrapper.find('#contactName').setValue('John Doe')
      await wrapper.find('#email').setValue('invalid-email')
      
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(wrapper.text()).toContain('Please enter a valid email address')
    })

    it('clears validation errors when corrected', async () => {
      // Trigger validation errors
      const form = wrapper.find('form')
      await form.trigger('submit.prevent')
      expect(wrapper.text()).toContain('Institution name is required')

      // Fix the error
      await wrapper.find('#institutionName').setValue('Test University')
      await form.trigger('submit.prevent')
      expect(wrapper.text()).not.toContain('Institution name is required')
    })
  })

  describe('Interest Selection', () => {
    it('allows multiple interest selection', async () => {
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      
      // Select multiple interests
      await checkboxes[0].setChecked(true)
      await checkboxes[1].setChecked(true)
      await checkboxes[2].setChecked(true)

      expect(wrapper.vm.form.interests).toHaveLength(3)
    })

    it('updates interests array when checkboxes are toggled', async () => {
      const mobileAppCheckbox = wrapper.find('input[value="mobile_app"]')
      
      await mobileAppCheckbox.setChecked(true)
      expect(wrapper.vm.form.interests).toContain('mobile_app')
      
      await mobileAppCheckbox.setChecked(false)
      expect(wrapper.vm.form.interests).not.toContain('mobile_app')
    })
  })

  describe('Form Submission', () => {
    beforeEach(async () => {
      // Fill out valid form data
      await wrapper.find('#institutionName').setValue('Test University')
      await wrapper.find('#contactName').setValue('John Doe')
      await wrapper.find('#email').setValue('john@testuniversity.edu')
      await wrapper.find('#title').setValue('Alumni Relations Director')
      await wrapper.find('#phone').setValue('(555) 123-4567')
      await wrapper.find('#alumniCount').setValue('5000_10000')
      await wrapper.find('#currentSolution').setValue('spreadsheets')
      await wrapper.find('#preferredTime').setValue('morning')
      await wrapper.find('#message').setValue('Looking to improve alumni engagement')
      
      // Select some interests
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      await checkboxes[0].setChecked(true)
      await checkboxes[1].setChecked(true)
    })

    it('submits form with correct data', async () => {
      const mockResponse = { ok: true, json: () => Promise.resolve({ success: true }) }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      expect(fetch).toHaveBeenCalledWith('/api/homepage/demo-request', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'test-token'
        },
        body: expect.stringContaining('"institutionName":"Test University"')
      })

      const callArgs = vi.mocked(fetch).mock.calls[0][1]
      const bodyData = JSON.parse(callArgs?.body as string)
      
      expect(bodyData).toMatchObject({
        institutionName: 'Test University',
        contactName: 'John Doe',
        email: 'john@testuniversity.edu',
        title: 'Alumni Relations Director',
        phone: '(555) 123-4567',
        alumniCount: '5000_10000',
        currentSolution: 'spreadsheets',
        preferredTime: 'morning',
        message: 'Looking to improve alumni engagement',
        planId: 'enterprise',
        source: 'pricing_modal'
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
      expect(wrapper.text()).toContain('Scheduling Demo...')
      expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined()
    })

    it('shows success message on successful submission', async () => {
      const mockResponse = { ok: true, json: () => Promise.resolve({ success: true }) }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      const form = wrapper.find('form')
      await form.trigger('submit.prevent')

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('Demo Request Submitted!')
      expect(wrapper.text()).toContain('Our team will contact you within 24 hours')
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
  })

  describe('Form Reset', () => {
    it('resets form when modal reopens', async () => {
      // Fill form
      await wrapper.find('#institutionName').setValue('Test University')
      await wrapper.find('#contactName').setValue('John Doe')

      // Close and reopen modal
      await wrapper.setProps({ isOpen: false })
      await wrapper.setProps({ isOpen: true })

      expect(wrapper.vm.form.institutionName).toBe('')
      expect(wrapper.vm.form.contactName).toBe('')
    })

    it('clears interests when modal reopens', async () => {
      // Select interests
      const checkboxes = wrapper.findAll('input[type="checkbox"]')
      await checkboxes[0].setChecked(true)
      expect(wrapper.vm.form.interests).toHaveLength(1)

      // Close and reopen modal
      await wrapper.setProps({ isOpen: false })
      await wrapper.setProps({ isOpen: true })

      expect(wrapper.vm.form.interests).toHaveLength(0)
    })
  })

  describe('Analytics Tracking', () => {
    it('tracks successful demo request', async () => {
      const mockGtag = vi.fn()
      Object.defineProperty(window, 'gtag', {
        value: mockGtag,
        writable: true
      })

      const mockResponse = { ok: true, json: () => Promise.resolve({ success: true }) }
      vi.mocked(fetch).mockResolvedValueOnce(mockResponse as any)

      await wrapper.find('#institutionName').setValue('Test University')
      await wrapper.find('#contactName').setValue('John Doe')
      await wrapper.find('#email').setValue('john@testuniversity.edu')
      await wrapper.find('#alumniCount').setValue('5000_10000')
      await wrapper.find('form').trigger('submit.prevent')

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(mockGtag).toHaveBeenCalledWith('event', 'demo_request', {
        plan_id: 'enterprise',
        source: 'pricing_modal',
        institution_name: 'Test University',
        alumni_count: '5000_10000'
      })
    })
  })

  describe('Responsive Design', () => {
    it('has responsive grid classes', () => {
      const grids = wrapper.findAll('.grid')
      grids.forEach(grid => {
        expect(grid.classes()).toContain('grid-cols-1')
      })
    })

    it('has mobile-optimized layout', () => {
      expect(wrapper.find('.max-w-2xl').exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('has proper form labels', () => {
      const labels = wrapper.findAll('label')
      expect(labels.length).toBeGreaterThan(0)
      
      labels.forEach(label => {
        if (label.attributes('for')) {
          expect(label.attributes('for')).toBeDefined()
        }
      })
    })

    it('has proper ARIA attributes for required fields', () => {
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