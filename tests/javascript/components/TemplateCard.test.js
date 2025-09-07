import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TemplateCard from '@/components/TemplateCard.vue'
import type { Template } from '@/types/components'

// Mock template data
const mockTemplate: Template = {
  id: 1,
  tenantId: 1,
  name: 'Test Landing Page',
  slug: 'test-landing-page',
  description: 'A beautiful landing page template',
  category: 'landing',
  audienceType: 'individual',
  campaignType: 'onboarding',
  structure: {},
  defaultConfig: {},
  usageCount: 150,
  isActive: true,
  isPremium: false,
  version: 1,
  tags: ['marketing', 'lead-generation'],
  createdAt: '2024-01-01T00:00:00Z',
  updatedAt: '2024-01-01T00:00:00Z'
}

describe('TemplateCard.vue', () => {
  let wrapper

  const createWrapper = (props = {}, options = {}) => {
    return mount(TemplateCard, {
      props: {
        template: mockTemplate,
        viewMode: 'grid',
        ...props
      },
      global: {
        stubs: ['svg'],
        plugins: [createTestingPinia()],
        ...options.global
      },
      ...options
    })
  }

  beforeEach(() => {
    wrapper = createWrapper()
  })

  describe('Rendering', () => {
    it('renders template information correctly', () => {
      expect(wrapper.find('.template-name').text()).toBe(mockTemplate.name)
      expect(wrapper.find('.template-description').text()).toBe(mockTemplate.description)
    })

    it('displays preview image when available', () => {
      const templateWithImage = {
        ...mockTemplate,
        previewImage: 'https://example.com/image.jpg'
      }

      wrapper = createWrapper({ template: templateWithImage })

      const img = wrapper.find('.preview-image')
      expect(img.exists()).toBe(true)
      expect(img.attributes('src')).toBe(templateWithImage.previewImage)
      expect(img.attributes('alt')).toBe(`${templateWithImage.name} preview`)
    })

    it('shows placeholder when preview image is not available', () => {
      const templateWithoutImage = {
        ...mockTemplate,
        previewImage: undefined
      }

      wrapper = createWrapper({ template: templateWithoutImage })

      expect(wrapper.find('.preview-placeholder').exists()).toBe(true)
      expect(wrapper.find('.preview-image').exists()).toBe(false)
    })

    it('displays premium badge for premium templates', () => {
      const premiumTemplate = {
        ...mockTemplate,
        isPremium: true
      }

      wrapper = createWrapper({ template: premiumTemplate })

      const premiumBadge = wrapper.find('.premium-badge')
      expect(premiumBadge.exists()).toBe(true)
      expect(premiumBadge.text().trim()).toContain('Premium')
    })

    it('shows usage statistics', () => {
      const usageText = wrapper.text()
      expect(usageText).toContain('150 uses')
    })

    it('displays tags correctly', () => {
      const tags = wrapper.findAll('.tag-badge')
      expect(tags).toHaveLength(2)
      expect(tags[0].text()).toBe('#marketing')
      expect(tags[1].text()).toBe('#lead-generation')
    })

    it('shows audience type badge', () => {
      expect(wrapper.text()).toContain(mockTemplate.audienceType)
    })

    it('displays category badge with correct styling', () => {
      const categoryBadge = wrapper.find('.category-badge')
      expect(categoryBadge.exists()).toBe(true)
      expect(categoryBadge.text()).toBe(mockTemplate.category)
      expect(categoryBadge.classes()).toContain('category-landing')
    })
  })

  describe('Computed Properties', () => {
    it('computes placeholder colors correctly', async () => {
      const templateWithId = {
        ...mockTemplate,
        id: 1
      }

      wrapper = createWrapper({ template: templateWithId })

      // Access the computed property through the component's vm
      const vm = wrapper.vm
      const colors = vm.placeholderColors

      expect(colors).toHaveLength(2)
      expect(typeof colors[0]).toBe('string')
      expect(typeof colors[1]).toBe('string')
    })

    it('computes popular status based on usage count', () => {
      const vm = wrapper.vm
      expect(vm.isPopular).toBe(true) // 150 > 100

      const lowUsageTemplate = { ...mockTemplate, usageCount: 50 }
      wrapper = createWrapper({ template: lowUsageTemplate })

      const vm2 = wrapper.vm
      expect(vm2.isPopular).toBe(false) // 50 < 100
    })
  })

  describe('Events', () => {
    it('emits preview event when preview button is clicked', async () => {
      const previewBtn = wrapper.find('.preview-btn')
      expect(previewBtn.exists()).toBe(true)

      await previewBtn.trigger('click')

      expect(wrapper.emitted().preview).toBeTruthy()
      expect(wrapper.emitted().preview[0]).toEqual([mockTemplate])
    })

    it('emits select event when select button is clicked', async () => {
      const selectBtn = wrapper.find('.action-btn--primary')
      expect(selectBtn.exists()).toBe(true)

      await selectBtn.trigger('click')

      expect(wrapper.emitted().select).toBeTruthy()
      expect(wrapper.emitted().select[0]).toEqual([mockTemplate])
    })

    it('emits select event when card is clicked', async () => {
      const card = wrapper.find('.template-card')
      expect(card.exists()).toBe(true)

      await card.trigger('click')

      expect(wrapper.emitted().select).toBeTruthy()
      expect(wrapper.emitted().select[0]).toEqual([mockTemplate])
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA attributes', () => {
      const card = wrapper.find('.template-card')

      expect(card.attributes('role')).toBe('button')
      expect(card.attributes('tabindex')).toBe('0')
      expect(card.attributes('aria-label')).toMatch(`View template ${mockTemplate.name}`)
    })

    it('has proper button labels', () => {
      const previewBtn = wrapper.find('.preview-btn')
      const selectBtn = wrapper.find('.action-btn--primary')

      expect(previewBtn.attributes('aria-label')).toMatch(`Preview ${mockTemplate.name}`)
      expect(selectBtn.attributes('aria-label')).toMatch(`Select ${mockTemplate.name}`)
    })

    it('supports keyboard navigation', async () => {
      const card = wrapper.find('.template-card')

      await card.trigger('keydown.enter')

      expect(wrapper.emitted().select).toBeTruthy()
    })

    it('supports space key activation', async () => {
      const card = wrapper.find('.template-card')

      await card.trigger('keydown.space')

      expect(wrapper.emitted().select).toBeTruthy()
    })
  })

  describe('Responsiveness', () => {
    it('displays in grid mode by default', () => {
      expect(wrapper.classes()).not.toContain('template-card--list')
    })

    it('displays in list mode when viewMode is list', () => {
      wrapper = createWrapper({ viewMode: 'list' })

      expect(wrapper.classes()).toContain('template-card--list')
    })
  })

  describe('Edge Cases', () => {
    it('handles templates without tags', () => {
      const templateWithoutTags = { ...mockTemplate, tags: [] }
      wrapper = createWrapper({ template: templateWithoutTags })

      const tags = wrapper.findAll('.tag-badge')
      expect(tags).toHaveLength(0)
    })

    it('handles templates without description', () => {
      const templateWithoutDesc = { ...mockTemplate, description: undefined }
      wrapper = createWrapper({ template: templateWithoutDesc })

      expect(wrapper.text()).not.toContain(undefined)
    })

    it('handles templates with no usage', () => {
      const templateWithNoUsage = { ...mockTemplate, usageCount: 0 }
      wrapper = createWrapper({ template: templateWithNoUsage })

      expect(wrapper.text()).toContain('0 uses')
    })

    it('limits tag display to 3 tags', () => {
      const templateWithManyTags = {
        ...mockTemplate,
        tags: ['tag1', 'tag2', 'tag3', 'tag4', 'tag5']
      }
      wrapper = createWrapper({ template: templateWithManyTags })

      const tags = wrapper.findAll('.tag-badge')
      expect(tags).toHaveLength(3) // Only first 3 displayed
      expect(wrapper.text()).toContain('+2 more') // Shows remaining count
    })
  })

  describe('Lifecycle', () => {
    it('initializes correctly with props', () => {
      expect(wrapper.vm.template.id).toBe(mockTemplate.id)
      expect(wrapper.vm.viewMode).toBe('grid')
    })
  })
})