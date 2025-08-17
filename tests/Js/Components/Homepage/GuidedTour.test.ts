import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import GuidedTour from '@/components/homepage/GuidedTour.vue'

// Mock localStorage
const mockLocalStorage = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}
Object.defineProperty(window, 'localStorage', {
  value: mockLocalStorage
})

describe('GuidedTour.vue', () => {
  let wrapper: VueWrapper<any>

  const mockSteps = [
    {
      id: 'welcome',
      title: 'Welcome to Your Alumni Platform',
      description: 'Let\'s take a quick tour of the key features.',
      target: 'body',
      position: 'center' as const
    },
    {
      id: 'networking',
      title: 'Connect with Alumni',
      description: 'Discover and connect with alumni who share your interests.',
      target: '.networking-feature',
      position: 'bottom' as const,
      media: {
        type: 'image' as const,
        url: '/images/tour/networking-overview.png',
        alt: 'Networking feature overview'
      }
    },
    {
      id: 'jobs',
      title: 'Find Opportunities',
      description: 'Access exclusive job postings from your network.',
      target: '.job-board-feature',
      position: 'top' as const,
      interactive: {
        instruction: 'Click on a job posting to see details'
      }
    }
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    mockLocalStorage.getItem.mockReturnValue(null)
    
    // Mock DOM elements for tour targets
    document.body.innerHTML = `
      <div class="networking-feature">Networking Feature</div>
      <div class="job-board-feature">Job Board Feature</div>
    `
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    document.body.innerHTML = ''
  })

  describe('Component Rendering', () => {
    it('renders tour controls when not active', () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps,
          showControls: true
        }
      })

      expect(wrapper.find('.fixed.bottom-6.right-6').exists()).toBe(true)
      expect(wrapper.text()).toContain('Platform Tour')
      expect(wrapper.text()).toContain('Start Tour')
    })

    it('does not render when showControls is false and not active', () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps,
          showControls: false
        }
      })

      expect(wrapper.find('.fixed.bottom-6.right-6').exists()).toBe(false)
    })

    it('renders tour overlay when active', async () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })

      await wrapper.vm.startTour()
      await nextTick()

      expect(wrapper.find('.fixed.inset-0.z-50').exists()).toBe(true)
      expect(wrapper.text()).toContain(mockSteps[0].title)
      expect(wrapper.text()).toContain(mockSteps[0].description)
    })
  })

  describe('Tour Navigation', () => {
    beforeEach(async () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })

      await wrapper.vm.startTour()
      await nextTick()
    })

    it('starts with first step', () => {
      expect(wrapper.vm.currentStepIndex).toBe(0)
      expect(wrapper.text()).toContain(mockSteps[0].title)
    })

    it('navigates to next step', async () => {
      const nextButton = wrapper.find('button').filter(btn => 
        btn.text().includes('Next')
      )

      await nextButton.trigger('click')
      await nextTick()

      expect(wrapper.vm.currentStepIndex).toBe(1)
      expect(wrapper.text()).toContain(mockSteps[1].title)
    })

    it('navigates to previous step', async () => {
      // Go to second step first
      await wrapper.vm.nextStep()
      await nextTick()

      const prevButton = wrapper.find('button').filter(btn => 
        btn.text().includes('Previous')
      )

      await prevButton.trigger('click')
      await nextTick()

      expect(wrapper.vm.currentStepIndex).toBe(0)
    })

    it('shows complete button on last step', async () => {
      // Navigate to last step
      await wrapper.vm.goToStep(mockSteps.length - 1)
      await nextTick()

      const completeButton = wrapper.find('button').filter(btn => 
        btn.text().includes('Complete Tour')
      )

      expect(completeButton.exists()).toBe(true)
    })

    it('completes tour and stores completion', async () => {
      await wrapper.vm.goToStep(mockSteps.length - 1)
      await nextTick()

      const completeButton = wrapper.find('button').filter(btn => 
        btn.text().includes('Complete Tour')
      )

      await completeButton.trigger('click')
      await nextTick()

      expect(wrapper.vm.isActive).toBe(false)
      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'tour_completed_individual',
        'true'
      )
    })
  })

  describe('Progress Indicators', () => {
    beforeEach(async () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })

      await wrapper.vm.startTour()
      await nextTick()
    })

    it('displays progress dots', () => {
      const progressDots = wrapper.findAll('.w-2.h-2.rounded-full')
      expect(progressDots).toHaveLength(mockSteps.length)
    })

    it('highlights current step dot', () => {
      const progressDots = wrapper.findAll('.w-2.h-2.rounded-full')
      expect(progressDots[0].classes()).toContain('bg-blue-600')
      expect(progressDots[1].classes()).toContain('bg-gray-300')
    })

    it('allows navigation via progress dots', async () => {
      const progressDots = wrapper.findAll('.w-2.h-2.rounded-full')
      
      await progressDots[1].trigger('click')
      await nextTick()

      expect(wrapper.vm.currentStepIndex).toBe(1)
    })

    it('displays step counter', () => {
      expect(wrapper.text()).toContain('1 of 3')
    })
  })

  describe('Media Display', () => {
    beforeEach(async () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })

      await wrapper.vm.startTour()
      await nextTick()
    })

    it('displays image media when present', async () => {
      // Navigate to step with image
      await wrapper.vm.goToStep(1)
      await nextTick()

      const image = wrapper.find('img')
      expect(image.exists()).toBe(true)
      expect(image.attributes('src')).toBe(mockSteps[1].media?.url)
      expect(image.attributes('alt')).toBe(mockSteps[1].media?.alt)
    })

    it('displays interactive instructions when present', async () => {
      // Navigate to step with interactive element
      await wrapper.vm.goToStep(2)
      await nextTick()

      expect(wrapper.text()).toContain('Try it yourself!')
      expect(wrapper.text()).toContain(mockSteps[2].interactive?.instruction)
    })
  })

  describe('Keyboard Navigation', () => {
    beforeEach(async () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })

      await wrapper.vm.startTour()
      await nextTick()
    })

    it('closes tour on Escape key', async () => {
      const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' })
      window.dispatchEvent(escapeEvent)
      await nextTick()

      expect(wrapper.vm.isActive).toBe(false)
    })

    it('navigates forward on arrow keys', async () => {
      const rightArrowEvent = new KeyboardEvent('keydown', { key: 'ArrowRight' })
      window.dispatchEvent(rightArrowEvent)
      await nextTick()

      expect(wrapper.vm.currentStepIndex).toBe(1)
    })

    it('navigates backward on arrow keys', async () => {
      // Go to second step first
      await wrapper.vm.nextStep()
      await nextTick()

      const leftArrowEvent = new KeyboardEvent('keydown', { key: 'ArrowLeft' })
      window.dispatchEvent(leftArrowEvent)
      await nextTick()

      expect(wrapper.vm.currentStepIndex).toBe(0)
    })
  })

  describe('Auto-start Behavior', () => {
    it('auto-starts tour when autoStart is true and not completed', async () => {
      mockLocalStorage.getItem.mockReturnValue(null) // Not completed

      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps,
          autoStart: true
        }
      })

      // Wait for auto-start delay
      await new Promise(resolve => setTimeout(resolve, 1100))
      await nextTick()

      expect(wrapper.vm.isActive).toBe(true)
    })

    it('does not auto-start when tour is already completed', () => {
      mockLocalStorage.getItem.mockReturnValue('true') // Already completed

      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps,
          autoStart: true
        }
      })

      expect(wrapper.vm.isActive).toBe(false)
    })
  })

  describe('Event Emissions', () => {
    beforeEach(async () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })
    })

    it('emits start event when tour starts', async () => {
      await wrapper.vm.startTour()

      expect(wrapper.emitted('start')).toBeTruthy()
    })

    it('emits stepChange event when step changes', async () => {
      await wrapper.vm.startTour()
      await wrapper.vm.nextStep()

      const stepChangeEvents = wrapper.emitted('stepChange')
      expect(stepChangeEvents).toBeTruthy()
      expect(stepChangeEvents?.[0]).toEqual([1, mockSteps[1]])
    })

    it('emits complete event when tour completes', async () => {
      await wrapper.vm.startTour()
      await wrapper.vm.goToStep(mockSteps.length - 1)
      
      const completeButton = wrapper.find('button').filter(btn => 
        btn.text().includes('Complete Tour')
      )
      await completeButton.trigger('click')

      expect(wrapper.emitted('complete')).toBeTruthy()
    })

    it('emits skip event when tour is closed', async () => {
      await wrapper.vm.startTour()
      await wrapper.vm.closeTour()

      expect(wrapper.emitted('skip')).toBeTruthy()
    })
  })

  describe('Accessibility', () => {
    beforeEach(async () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })

      await wrapper.vm.startTour()
      await nextTick()
    })

    it('provides proper ARIA labels for buttons', () => {
      const closeButton = wrapper.find('button[aria-label="Close tour"]')
      expect(closeButton.exists()).toBe(true)

      const progressDots = wrapper.findAll('button[aria-label*="Go to step"]')
      expect(progressDots.length).toBe(mockSteps.length)
    })

    it('uses semantic HTML structure', () => {
      // Tour should have proper modal structure
      expect(wrapper.find('.fixed.inset-0').exists()).toBe(true)
    })
  })

  describe('Default Steps Generation', () => {
    it('generates individual audience steps when no steps provided', () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual'
        }
      })

      const steps = wrapper.vm.steps
      expect(steps.length).toBeGreaterThan(0)
      expect(steps[0].title).toContain('Welcome')
    })

    it('generates institutional audience steps when no steps provided', () => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'institutional'
        }
      })

      const steps = wrapper.vm.steps
      expect(steps.length).toBeGreaterThan(0)
      expect(steps.some((step: any) => step.title.includes('Admin'))).toBe(true)
    })
  })

  describe('Component Methods', () => {
    beforeEach(() => {
      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })
    })

    it('exposes tour control methods', () => {
      expect(wrapper.vm.startTour).toBeDefined()
      expect(wrapper.vm.closeTour).toBeDefined()
      expect(wrapper.vm.nextStep).toBeDefined()
      expect(wrapper.vm.previousStep).toBeDefined()
      expect(wrapper.vm.goToStep).toBeDefined()
    })

    it('exposes reactive properties', () => {
      expect(wrapper.vm.isActive).toBeDefined()
      expect(wrapper.vm.currentStep).toBeDefined()
      expect(wrapper.vm.currentStepIndex).toBeDefined()
    })
  })

  describe('Error Handling', () => {
    it('handles missing target elements gracefully', async () => {
      const stepsWithMissingTarget = [
        {
          id: 'missing',
          title: 'Missing Target',
          description: 'This targets a non-existent element',
          target: '.non-existent-element',
          position: 'center' as const
        }
      ]

      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: stepsWithMissingTarget
        }
      })

      await wrapper.vm.startTour()
      await nextTick()

      // Should not crash and should still show the tour
      expect(wrapper.vm.isActive).toBe(true)
    })

    it('handles media loading errors', async () => {
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})

      wrapper = mount(GuidedTour, {
        props: {
          audience: 'individual',
          steps: mockSteps
        }
      })

      await wrapper.vm.startTour()
      await wrapper.vm.goToStep(1) // Step with image
      await nextTick()

      const image = wrapper.find('img')
      if (image.exists()) {
        // Simulate image error
        image.trigger('error')
        expect(consoleSpy).toHaveBeenCalled()
      }

      consoleSpy.mockRestore()
    })
  })
})