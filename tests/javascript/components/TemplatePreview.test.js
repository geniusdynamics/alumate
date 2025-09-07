import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TemplatePreview from '@/components/TemplatePreview.vue'

// Mock service
vi.mock('@/services/TemplateService', () => ({
  templateService: {
    generatePreview: vi.fn(),
    fetchTemplate: vi.fn()
  }
}))

// Mock components
vi.mock('@heroicons/vue/24/outline', () => ({
  ComputerDesktopIcon: { template: '<svg></svg>' },
  DevicePhoneMobileIcon: { template: '<svg></svg>' },
  DeviceTabletIcon: { template: '<svg></svg>' }
}))

describe('TemplatePreview.vue', () => {
  let wrapper
  let mockGeneratePreview

  const mockTemplate = {
    id: 1,
    name: 'Test Template',
    isPremium: false,
    category: 'landing',
    usageCount: 100
  }

  const createWrapper = (props = {}) => {
    return mount(TemplatePreview, {
      props: {
        modelValue: true,
        template: mockTemplate,
        ...props
      },
      global: {
        stubs: ['svg'],
        plugins: [createTestingPinia()],
        mocks: {
          templateService: {
            generatePreview: mockGeneratePreview
          }
        }
      }
    })
  }

  beforeEach(() => {
    mockGeneratePreview = vi.fn().mockResolvedValue({
      html: '<div>Test content</div>',
      css: '.test { color: red; }'
    })
  })

  describe('Rendering', () => {
    it('renders preview modal when open', () => {
      wrapper = createWrapper()

      expect(wrapper.find('.preview-modal').exists()).toBe(true)
      expect(wrapper.find('.preview-container').exists()).toBe(true)
    })

    it('does not render when closed', () => {
      wrapper = createWrapper({ modelValue: false })

      expect(wrapper.find('.preview-modal').exists()).toBe(false)
    })

    it('displays template name in header', () => {
      wrapper = createWrapper()

      const title = wrapper.find('.preview-title')
      expect(title.text()).toBe(mockTemplate.name)
    })

    it('shows premium badge for premium templates', () => {
      const premiumTemplate = { ...mockTemplate, isPremium: true }
      wrapper = createWrapper({ template: premiumTemplate })

      const badge = wrapper.find('.premium-badge')
      expect(badge.exists()).toBe(true)
    })

    it('displays category badge correctly', () => {
      wrapper = createWrapper()

      const badge = wrapper.find('.category-badge')
      expect(badge.text()).toBe(mockTemplate.category)
      expect(badge.classes()).toContain('category-landing')
    })

    it('shows usage statistics', () => {
      wrapper = createWrapper()

      const stats = wrapper.find('.template-stats')
      expect(stats.exists()).toBe(true)
      expect(wrapper.text()).toContain('100 uses')
    })

    it('renders viewport controls', () => {
      wrapper = createWrapper()

      const controls = wrapper.findAll('.viewport-btn')
      expect(controls).toHaveLength(3) // Desktop, tablet, mobile
    })
  })

  describe('Viewport Switching', () => {
    it('starts with desktop viewport by default', () => {
      wrapper = createWrapper()

      expect(wrapper.vm.currentViewport).toBe('desktop')
    })

    it('can switch to tablet viewport', async () => {
      wrapper = createWrapper()

      const tabletBtn = wrapper.findAll('.viewport-btn')[1] // Second button is tablet
      await tabletBtn.trigger('click')

      expect(wrapper.vm.currentViewport).toBe('tablet')
    })

    it('can switch to mobile viewport', async () => {
      wrapper = createWrapper()

      const mobileBtn = wrapper.findAll('.viewport-btn')[2] // Third button is mobile
      await mobileBtn.trigger('click')

      expect(wrapper.vm.currentViewport).toBe('mobile')
    })

    it('applies correctly viewport container classes', async () => {
      wrapper = createWrapper()

      expect(wrapper.find('.preview-iframe-container').classes()).toContain('viewport-desktop')

      await wrapper.findAll('.viewport-btn')[1].trigger('click') // Switch to tablet
      expect(wrapper.find('.preview-iframe-container').classes()).toContain('viewport-tablet')
    })
  })

  describe('Preview Generation', () => {
    it('calls generatePreview on mount with template', async () => {
      wrapper = createWrapper()

      await new Promise(resolve => setTimeout(resolve, 0)) // Wait for async

      expect(mockGeneratePreview).toHaveBeenCalledWith(mockTemplate.id, {
        templateId: mockTemplate.id,
        viewport: 'desktop',
        showControls: false,
        interactive: false
      })
    })

    it('displays iframe with generated content', async () => {
      wrapper = createWrapper()

      await new Promise(resolve => setTimeout(resolve, 0))

      const iframe = wrapper.find('.preview-iframe')
      expect(iframe.exists()).toBe(true)

      const srcDoc = wrapper.vm.previewSrcDoc
      expect(srcDoc).toContain('<div>Test content</div>')
      expect(srcDoc).toContain('.test { color: red; }')
    })

    it('shows loading state during preview generation', async () => {
      mockGeneratePreview = vi.fn().mockImplementation(() => new Promise(resolve => {
        setTimeout(() => resolve({ html: '', css: '' }), 100)
      }))

      wrapper = createWrapper()
      wrapper.vm.previewLoading = true

      expect(wrapper.find('.preview-loading').exists()).toBe(true)
      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
    })

    it('displays error state when generation fails', async () => {
      mockGeneratePreview = vi.fn().mockRejectedValue(new Error('Preview failed'))

      wrapper = createWrapper()
      wrapper.vm.previewError = 'Preview failed'

      expect(wrapper.find('.preview-error').exists()).toBe(true)
      expect(wrapper.text()).toContain('Preview failed')
    })
  })

  describe('Events', () => {
    it('emits update:modelValue when closed', async () => {
      wrapper = createWrapper()

      const closeBtn = wrapper.find('.close-btn')
      await closeBtn.trigger('click')

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
      expect(wrapper.emitted('update:modelValue')[0]).toEqual([false])
    })

    it('emits templateSelected when select button is clicked', async () => {
      wrapper = createWrapper()

      const selectBtn = wrapper.find('.select-btn')
      await selectBtn.trigger('click')

      expect(wrapper.emitted('templateSelected')).toBeTruthy()
      expect(wrapper.emitted('templateSelected')[0]).toEqual([mockTemplate])
    })
  })

  describe('Keyboard Navigation', () => {
    it('closes on Escape key', async () => {
      wrapper = createWrapper()

      const event = new KeyboardEvent('keydown', { key: 'Escape' })
      document.dispatchEvent(event)

      expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    })
  })

  describe('Embed Code', () => {
    it('can copy embed code', async () => {
      // Mock navigator.clipboard
      Object.defineProperty(navigator, 'clipboard', {
        value: {
          writeText: vi.fn().mockResolvedValue()
        },
        writable: true
      })

      wrapper = createWrapper()

      const embedBtn = wrapper.find('.embed-btn')
      await embedBtn.trigger('click')

      expect(navigator.clipboard.writeText).toHaveBeenCalledWith(
        expect.stringContaining('<iframe')
      )
    })

    it('disables embed button when no preview available', () => {
      const templateWithoutPreview = { ...mockTemplate, structure: null }
      wrapper = createWrapper({ template: templateWithoutPreview })

      const embedBtn = wrapper.find('.embed-btn')
      expect(embedBtn.attributes('disabled')).toBeDefined()
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA labels', () => {
      wrapper = createWrapper()

      expect(wrapper.find('.close-btn').attributes('aria-label')).toBe('Close preview')
    })

    it('has proper button labels for viewport switches', () => {
      wrapper = createWrapper()

      const desktopBtn = wrapper.findAll('.viewport-btn')[0]
      expect(desktopBtn.attributes('aria-label')).toBe('Switch to Desktop view')
    })

    it('supports keyboard focus management', () => {
      wrapper = createWrapper()

      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.attributes('tabindex')).toBeUndefined() // Should be focusable by default
      })
    })
  })

  describe('Error Handling', () => {
    it('displays error message for failed previews', async () => {
      const errorMessage = 'Network error occurred'
      mockGeneratePreview = vi.fn().mockRejectedValue(new Error(errorMessage))

      wrapper = createWrapper()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.vm.previewError).toBe(errorMessage)
    })

    it('provides retry functionality', async () => {
      mockGeneratePreview = vi.fn().mockRejectedValueOnce(new Error('Failed'))
        .mockResolvedValueOnce({ html: '<div>Success</div>', css: '' })

      wrapper = createWrapper()

      // First fail
      await new Promise(resolve => setTimeout(resolve, 0))
      expect(wrapper.vm.previewError).toBeTruthy()

      // Click retry
      const retryBtn = wrapper.find('.retry-btn')
      await retryBtn.trigger('click')

      expect(mockGeneratePreview).toHaveBeenCalledTimes(2)
    })
  })

  describe('Responsive Design', () => {
    it('adapts layout for mobile screens', () => {
      wrapper = createWrapper()

      // Check if responsive classes are present
      const modal = wrapper.find('.preview-modal')
      expect(modal.classes()).toContain('p-4')

      const container = wrapper.find('.preview-container')
      expect(container.classes()).toContain('max-w-6xl')

      const controls = wrapper.find('.preview-controls')
      expect(controls.exists()).toBe(true)
    })
  })
})