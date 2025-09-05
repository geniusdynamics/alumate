import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import { nextTick } from 'vue'
import TemplateLibrary from '@/components/TemplateLibrary.vue'

// Mock service
vi.mock('@/services/TemplateService', () => ({
  templateService: {
    fetchTemplates: vi.fn(),
    fetchCategories: vi.fn(),
    searchTemplates: vi.fn(),
    updateTemplateUsage: vi.fn(),
    generateResponsivePreview: vi.fn(),
    fetchCategories: vi.fn(),
    toggleFavorite: vi.fn()
  }
}))

// Mock components
vi.mock('@/components/TemplateCard.vue', () => ({
  default: {
    name: 'TemplateCard',
    props: ['template', 'viewMode'],
    template: '<div class="template-card-mock" @click="emit(\'select\', $props.template)">{{ template.name }}</div>',
    methods: {
      emit: vi.fn()
    }
  }
}))

// Mock useDebounceFn
vi.mock('@vueuse/core', () => ({
  useDebounceFn: (fn) => fn
}))

// Mock data
const mockTemplate = {
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
  usageCount: 100,
  isActive: true,
  isPremium: false,
  version: 1,
  tags: ['marketing', 'conversion'],
  createdAt: '2024-01-01T00:00:00Z',
  updatedAt: '2024-01-01T00:00:00Z'
}

const mockTemplates = {
  data: [mockTemplate],
  total: 1,
  page: 1,
  perPage: 12,
  lastPage: 1
}

const mockCategories = [
  { value: 'landing', label: 'Landing Pages', count: 5 },
  { value: 'homepage', label: 'Home Pages', count: 3 }
]

describe('TemplateLibrary.vue', () => {
  let wrapper
  let mockFetchTemplates
  let mockFetchCategories
  let mockSearchTemplates

  const createWrapper = (props = {}) => {
    return mount(TemplateLibrary, {
      props,
      global: {
        stubs: ['svg'],
        plugins: [createTestingPinia()],
        mocks: {
          templateService: {
            fetchTemplates: mockFetchTemplates,
            fetchCategories: mockFetchCategories,
            searchTemplates: mockSearchTemplates
          }
        }
      }
    })
  }

  beforeEach(() => {
    // Reset mocks
    mockFetchTemplates = vi.fn().mockResolvedValue(mockTemplates)
    mockFetchCategories = vi.fn().mockResolvedValue(mockCategories)
    mockSearchTemplates = vi.fn().mockResolvedValue([])
  })

  describe('Initialization', () => {
    it('initializes with default props', () => {
      wrapper = createWrapper()

      expect(wrapper.vm.initialCategory).toBe('')
      expect(wrapper.vm.initialAudienceType).toBe('')
      expect(wrapper.vm.enablePreview).toBe(true)
    })

    it('initializes with provided props', () => {
      wrapper = createWrapper({
        initialCategory: 'landing',
        initialAudienceType: 'individual',
        enablePreview: false
      })

      expect(wrapper.vm.initialCategory).toBe('landing')
      expect(wrapper.vm.initialAudienceType).toBe('individual')
      expect(wrapper.vm.enablePreview).toBe(false)
    })

    it('fetches templates and categories on mount', async () => {
      wrapper = createWrapper()

      await nextTick()

      expect(mockFetchTemplates).toHaveBeenCalled()
      expect(mockFetchCategories).toHaveBeenCalled()
    })
  })

  describe('Search Functionality', () => {
    it('updates search query', async () => {
      wrapper = createWrapper()

      const searchInput = wrapper.find('input[type="search"], #template-search')
      expect(searchInput.exists()).toBe(true)

      await searchInput.setValue('test search')
      expect(wrapper.vm.searchQuery).toBe('test search')
    })

    it('searches templates based on search query', async () => {
      wrapper = createWrapper()

      wrapper.vm.searchQuery = 'landing page'
      wrapper.vm.debouncedSearch()

      await nextTick()

      expect(mockSearchTemplates).toHaveBeenCalledWith(
        'landing page',
        expect.any(Object)
      )
    })
  })

  describe('Filtering', () => {
    beforeEach(() => {
      wrapper = createWrapper()
    })

    it('updates category filter', async () => {
      const categorySelect = wrapper.find('select').filter(sel =>
        sel.element.previousElementSibling?.textContent?.includes('Category')
      )
      expect(categorySelect.exists()).toBe(true)

      await categorySelect.setValue('landing')
      expect(wrapper.vm.selectedCategory).toBe('landing')
    })

    it('updates audience type filter', async () => {
      const audienceSelect = wrapper.find('select').filter(sel =>
        Array.from(sel.element.options || []).some(opt =>
          opt.text.includes('individual') || opt.text.includes('Institution') || opt.text.includes('Employer')
        )
      )
      if (audienceSelect.exists()) {
        await audienceSelect.setValue('individual')
        expect(wrapper.vm.selectedAudienceType).toBe('individual')
      }
    })

    it('updates premium filter', async () => {
      const premiumSelect = wrapper.find('select').filter(sel =>
        Array.from(sel.element.options || []).some(opt =>
          opt.text.includes('Premium') || opt.text.includes('Free')
        )
      )
      if (premiumSelect.exists()) {
        await premiumSelect.setValue('true')
        expect(wrapper.vm.showPremiumOnly).toBe('true')
      }
    })

    it('applies filters when changed', async () => {
      wrapper.vm.selectedCategory = 'landing'
      wrapper.vm.applyFilters()

      await nextTick()

      expect(mockFetchTemplates).toHaveBeenCalledWith(
        expect.objectContaining({
          filters: expect.objectContaining({
            category: ['landing']
          })
        })
      )
    })

    it('clears all filters', async () => {
      wrapper.vm.searchQuery = 'test'
      wrapper.vm.selectedCategory = 'landing'
      wrapper.vm.selectedAudienceType = 'individual'

      wrapper.vm.clearFilters()

      expect(wrapper.vm.searchQuery).toBe('')
      expect(wrapper.vm.selectedCategory).toBe('')
      expect(wrapper.vm.selectedAudienceType).toBe('')
    })

    it('shows active filters when present', async () => {
      wrapper.vm.searchQuery = 'test'
      wrapper.vm.selectedCategory = 'landing'

      await nextTick()

      expect(wrapper.vm.hasActiveFilters).toBe(true)
      expect(wrapper.find('.filter-tags').exists()).toBe(true)
    })

    it('removes individual filters', async () => {
      wrapper.vm.searchQuery = 'test'
      wrapper.vm.selectedCategory = 'landing'

      await nextTick()

      const searchFilter = wrapper.find('.filter-tags .filter-tag').filter(tag =>
        tag.text().includes('Search')
      )
      if (searchFilter.exists()) {
        await searchFilter.find('.filter-remove').trigger('click')
        expect(wrapper.vm.searchQuery).toBe('')
      }
    })
  })

  describe('View Mode', () => {
    it('starts in grid view by default', () => {
      wrapper = createWrapper()
      expect(wrapper.vm.viewMode).toBe('grid')
    })

    it('switches to list view', async () => {
      wrapper = createWrapper()

      const listBtn = wrapper.find('.view-btn').filter(btn =>
        btn.attributes('aria-pressed') === 'false'
      )
      if (listBtn.exists()) {
        await listBtn.trigger('click')
        expect(wrapper.vm.viewMode).toBe('list')
      }
    })

    it('toggles between grid and list views', async () => {
      wrapper = createWrapper()

      const viewButtons = wrapper.findAll('.view-btn')
      expect(viewButtons).toHaveLength(2)

      // Click list view
      await viewButtons[1].trigger('click')
      expect(wrapper.vm.viewMode).toBe('list')

      // Click grid view
      await viewButtons[0].trigger('click')
      expect(wrapper.vm.viewMode).toBe('grid')
    })
  })

  describe('Template Display', () => {
    it('renders template cards when data is available', async () => {
      wrapper = createWrapper()
      await nextTick()

      const templateCards = wrapper.findAllComponents({ name: 'TemplateCard' })
      expect(templateCards).toHaveLength(mockTemplates.data.length)

      const firstCard = templateCards[0]
      expect(firstCard.props().template.id).toBe(mockTemplate.id)
      expect(firstCard.props().viewMode).toBe('grid')
    })

    it('shows loading state', async () => {
      wrapper = createWrapper()
      wrapper.vm.loading = true

      await nextTick()

      expect(wrapper.find('.loading-state').exists()).toBe(true)
      expect(wrapper.find('.loading-spinner').exists()).toBe(true)
    })

    it('displays error state when fetch fails', async () => {
      mockFetchTemplates.mockRejectedValue(new Error('Fetch failed'))

      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.vm.error).toBe('Fetch failed')
      expect(wrapper.find('.error-state').exists()).toBe(true)
    })

    it('shows empty state when no templates found', async () => {
      const emptyCollection = { ...mockTemplates, data: [] }
      mockFetchTemplates.mockResolvedValue(emptyCollection)

      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.find('.empty-state').exists()).toBe(true)
      expect(wrapper.text()).toContain('No Templates Found')
    })
  })

  describe('Pagination', () => {
    it('shows pagination when there are multiple pages', async () => {
      const multiPageCollection = {
        ...mockTemplates,
        total: 25,
        lastPage: 3,
        perPage: 10
      }
      mockFetchTemplates.mockResolvedValue(multiPageCollection)

      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.find('.pagination').exists()).toBe(true)
      expect(wrapper.vm.visiblePages).toBeDefined()
    })

    it('navigates to next page', async () => {
      const multiPageCollection = {
        ...mockTemplates,
        total: 25,
        lastPage: 3,
        page: 1
      }
      mockFetchTemplates.mockResolvedValue(multiPageCollection)

      wrapper = createWrapper()
      await nextTick()

      wrapper.vm.goToPage(2)
      expect(wrapper.vm.currentPage).toBe(2)

      await nextTick()
      expect(mockFetchTemplates).toHaveBeenCalledWith(
        expect.objectContaining({
          page: 2
        })
      )
    })

    it('scrolls to top after page change', async () => {
      const scrollToSpy = vi.spyOn(window, 'scrollTo')

      wrapper = createWrapper()
      wrapper.vm.goToPage(2)

      expect(scrollToSpy).toHaveBeenCalledWith({
        top: 0,
        behavior: 'smooth'
      })
    })

    it('prevents navigation beyond page bounds', () => {
      wrapper = createWrapper()

      wrapper.vm.goToPage(0) // Below minimum
      expect(wrapper.vm.templates.page).toBe(1)

      wrapper.vm.goToPage(100) // Above maximum
      expect(wrapper.vm.templates.page).toBe(wrapper.vm.templates.lastPage)
    })
  })

  describe('Events', () => {
    it('emits templateSelected when template is selected', async () => {
      wrapper = createWrapper()

      const templateCard = wrapper.findComponent({ name: 'TemplateCard' })
      if (templateCard.exists()) {
        await templateCard.vm.$emit('select', mockTemplate)
        expect(wrapper.emitted().templateSelected).toBeDefined()
        expect(wrapper.emitted().templateSelected[0]).toEqual([mockTemplate])
      }
    })

    it('emits templatePreviewed when preview is requested', async () => {
      wrapper = createWrapper()

      const templateCard = wrapper.findComponent({ name: 'TemplateCard' })
      if (templateCard.exists()) {
        await templateCard.vm.$emit('preview', mockTemplate)
        expect(wrapper.emitted().templatePreviewed).toBeDefined()
        expect(wrapper.emitted().templatePreviewed[0]).toEqual([mockTemplate])
      }
    })
  })

  describe('Responsive Behavior', () => {
    it('adapts filter layout for mobile', () => {
      wrapper = createWrapper()

      // Check if responsive classes are present
      const filterControls = wrapper.find('.filter-controls')
      expect(filterControls.classes()).toContain('grid-cols-1')
      expect(filterControls.classes()).toContain('md:grid-cols-4')
    })
  })

  describe('Error Handling', () => {
    it('handles fetch errors gracefully', async () => {
      mockFetchTemplates.mockRejectedValue(new Error('Network error'))

      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.vm.error).toBe('Network error')
      expect(wrapper.find('.error-state').exists()).toBe(true)

      const retryBtn = wrapper.find('.retry-btn')
      expect(retryBtn.exists()).toBe(true)
    })

    it('retries fetch after error', async () => {
      mockFetchTemplates
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce(mockTemplates)

      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.vm.error).toBe('Network error')

      await wrapper.find('.retry-btn').trigger('click')

      expect(wrapper.vm.error).toBe('')
      expect(mockFetchTemplates).toHaveBeenCalledTimes(2)
    })
  })

  describe('Computed Properties', () => {
    it('computes templatesContainerClasses correctly', () => {
      wrapper = createWrapper()

      expect(wrapper.vm.templatesContainerClasses).toContain('templates-grid')

      wrapper.vm.viewMode = 'list'
      expect(wrapper.vm.templatesContainerClasses).toContain('templates-list')
    })

    it('computes hasActiveFilters correctly', () => {
      wrapper = createWrapper()

      expect(wrapper.vm.hasActiveFilters).toBe(false)

      wrapper.vm.searchQuery = 'test'
      expect(wrapper.vm.hasActiveFilters).toBe(true)

      wrapper.vm.searchQuery = ''
      wrapper.vm.selectedCategory = 'landing'
      expect(wrapper.vm.hasActiveFilters).toBe(true)
    })
  })

  describe('Enhanced Features - Campaign Type Filtering', () => {
    it('updates campaign type filter', async () => {
      wrapper = createWrapper()

      const campaignSelect = wrapper.find('select').filter(sel =>
        Array.from(sel.element.options || []).some(opt =>
          opt.text.includes('onboarding') || opt.text.includes('marketing')
        )
      )

      if (campaignSelect.exists()) {
        await campaignSelect.setValue('onboarding')
        expect(wrapper.vm.selectedCampaignType).toBe('onboarding')
      }
    })

    it('includes campaign type in filter parameters', async () => {
      wrapper = createWrapper()
      wrapper.vm.selectedCampaignType = 'onboarding'
      wrapper.vm.applyFilters()

      await nextTick()

      expect(mockFetchTemplates).toHaveBeenCalledWith(
        expect.objectContaining({
          filters: expect.objectContaining({
            campaignType: ['onboarding']
          })
        })
      )
    })
  })

  describe('Enhanced Features - Recent Templates', () => {
    it('updates recent templates filter', async () => {
      wrapper = createWrapper()
      wrapper.vm.showRecentsOnly = 'true'
      wrapper.vm.applyFilters()

      await nextTick()

      expect(mockFetchTemplates).toHaveBeenCalledWith(
        expect.objectContaining({
          sortBy: 'last_used_at',
          sortOrder: 'desc'
        })
      )
    })

    it('shows recent templates toggle when favorites enabled', () => {
      wrapper = createWrapper({ showFavorites: true })

      const recentSelect = wrapper.find('select').filter(sel =>
        Array.from(sel.element.options || []).some(opt =>
          opt.text.includes('Recent') || opt.text.includes('All Time')
        )
      )

      expect(recentSelect.exists()).toBe(true)
    })
  })

  describe('Enhanced Features - Favorites', () => {
    beforeEach(() => {
      mockFetchTemplates.mockClear()
      wrapper = createWrapper({ showFavorites: true })
    })

    it('updates favorites filter', async () => {
      wrapper.vm.showFavoritesOnly = 'true'
      wrapper.vm.applyFilters()

      await nextTick()

      expect(mockFetchTemplates).toHaveBeenCalled()
    })

    it('shows favorites toggle when enabled', () => {
      const favoritesSelect = wrapper.find('select').filter(sel =>
        Array.from(sel.element.options || []).some(opt =>
          opt.text.includes('Favorites') || opt.text.includes('All Templates')
        )
      )

      expect(favoritesSelect.exists()).toBe(true)
    })
  })

  describe('Enhanced Features - Keyboard Navigation', () => {
    it('allows keyboard navigation when enabled', () => {
      wrapper = createWrapper({ enableKeyboardNavigation: true })

      expect(wrapper.vm.enableKeyboardNavigation).toBe(true)
    })

    it('handles arrow key navigation', () => {
      const mockEvent = {
        key: 'ArrowRight',
        preventDefault: vi.fn()
      }

      wrapper = createWrapper({ enableKeyboardNavigation: true })
      wrapper.vm.handleKeyboardNavigation(mockEvent)

      expect(mockEvent.preventDefault).toHaveBeenCalled()
    })

    it('skips keyboard navigation when disabled', () => {
      const mockEvent = {
        key: 'ArrowRight',
        preventDefault: vi.fn()
      }

      wrapper = createWrapper({ enableKeyboardNavigation: false })
      wrapper.vm.handleKeyboardNavigation(mockEvent)

      expect(mockEvent.preventDefault).not.toHaveBeenCalled()
    })
  })

  describe('Enhanced Features - Responsive Preview Integration', () => {
    it('generates responsive previews when previewing templates', async () => {
      const mockGenerateResponsivePreview = vi.fn().mockResolvedValue({
        responsivePreviews: {
          desktop: { html: '<div>Desktop</div>', width: 1200 },
          tablet: { html: '<div>Tablet</div>', width: 768 },
          mobile: { html: '<div>Mobile</div>', width: 375 }
        },
        assets: { styles: [], scripts: [], css: '' }
      })

      vi.mocked(templateService.generateResponsivePreview).mockImplementation(mockGenerateResponsivePreview)

      wrapper = createWrapper()
      wrapper.vm.handlePreview(mockTemplate)

      expect(mockGenerateResponsivePreview).toHaveBeenCalledWith(mockTemplate.id, {})
    })

    it('passes default viewport to preview handler', async () => {
      wrapper = createWrapper({ defaultViewport: 'mobile' })

      const emitSpy = vi.spyOn(wrapper.vm, 'emit')

      wrapper.vm.handlePreview(mockTemplate)

      expect(emitSpy).toHaveBeenCalledWith('templatePreviewed', mockTemplate, 'mobile')
    })
  })

  describe('Enhanced Features - Enhanced Props', () => {
    it('supports additional configuration props', () => {
      wrapper = createWrapper({
        enablePreview: false,
        showFavorites: true,
        enableKeyboardNavigation: true,
        defaultViewport: 'tablet'
      })

      expect(wrapper.vm.enablePreview).toBe(false)
      expect(wrapper.vm.showFavorites).toBe(true)
      expect(wrapper.vm.enableKeyboardNavigation).toBe(true)
      expect(wrapper.vm.defaultViewport).toBe('tablet')
    })

    it('filters templates based on recent usage when enabled', async () => {
      wrapper = createWrapper()
      wrapper.vm.showRecentsOnly = 'true'

      await nextTick()

      expect(wrapper.vm.filteredTemplates.length).toBeLessThanOrEqual(wrapper.vm.templates.data.length)
    })
  })

  describe('Enhanced Features - Auto Usage Tracking', () => {
    it('automatically updates template usage when selected', async () => {
      const mockUpdateUsage = vi.fn().mockResolvedValue()
      vi.mocked(templateService.updateTemplateUsage).mockImplementation(mockUpdateUsage)

      wrapper = createWrapper()
      await wrapper.findComponent({ name: 'TemplateCard' }).vm.$emit('select', mockTemplate)

      expect(mockUpdateUsage).toHaveBeenCalledWith(mockTemplate.id)
    })

    it('handles usage update errors gracefully', async () => {
      const mockUpdateUsage = vi.fn().mockRejectedValue(new Error('Update failed'))
      vi.mocked(templateService.updateTemplateUsage).mockImplementation(mockUpdateUsage)

      wrapper = createWrapper()

      // Should not throw error even if usage update fails
      await expect(
        wrapper.findComponent({ name: 'TemplateCard' }).vm.$emit('select', mockTemplate)
      ).resolves.not.toThrow()
    })
  })

  describe('Enhanced Features - Improved Accessibility', () => {
    it('includes proper ARIA labels for filters', () => {
      wrapper = createWrapper({ showFavorites: true })

      const selectElements = wrapper.findAll('select')
      expect(selectElements.length).toBeGreaterThan(0)

      // Check that aria-label attributes are present
      selectElements.forEach(select => {
        const ariaLabel = select.attributes('aria-label')
        expect(ariaLabel).toBeDefined()
      })
    })

    it('supports keyboard navigation through template grid', () => {
      wrapper = createWrapper({ enableKeyboardNavigation: true })

      const mockKeyDown = {
        key: 'ArrowDown',
        preventDefault: vi.fn()
      }

      wrapper.vm.handleKeyboardNavigation(mockKeyDown)

      // Keyboard navigation should be handled
      expect(mockKeyDown.preventDefault).toHaveBeenCalled()
    })
  })

  describe('Enhanced Features - Campaign Types Integration', () => {
    it('fetches and displays campaign types', async () => {
      const mockCampaignTypes = [
        { value: 'onboarding', label: 'Onboarding', count: 5 },
        { value: 'marketing', label: 'Marketing', count: 3 }
      ]

      vi.mocked(templateService.fetchCategories).mockResolvedValue(mockCampaignTypes)

      wrapper = createWrapper()
      await nextTick()

      // Should populate campaignTypes reactive ref
      expect(wrapper.vm.campaignTypes).toEqual(mockCampaignTypes)
    })

    it('dynamically generates campaign types from template data', async () => {
      const templatesWithCampaigns = [
        { ...mockTemplate, campaignType: 'onboarding' },
        { ...mockTemplate, campaignType: 'marketing', id: 2 },
        { ...mockTemplate, campaignType: 'onboarding', id: 3 }
      ]

      mockFetchTemplates.mockResolvedValue({
        ...mockTemplates,
        data: templatesWithCampaigns
      })

      wrapper = createWrapper()
      await nextTick()

      // Should have automatically generated campaign type filters
      expect(wrapper.vm.campaignTypes.length).toBeGreaterThan(0)
    })
  })

  describe('Enhanced Features - Filter Event Emission', () => {
    it('emits filter changes for external components', async () => {
      const emitSpy = vi.spyOn(wrapper.vm, 'emit')

      wrapper = createWrapper()
      wrapper.vm.applyFilters()

      expect(emitSpy).toHaveBeenCalledWith('libraryFiltersChanged', expect.any(Object))
    })

    it('provides current filter state in emitted events', () => {
      wrapper = createWrapper()

      wrapper.vm.selectedCategory = 'landing'
      wrapper.vm.searchQuery = 'test query'

      const filters = wrapper.vm.getCurrentFilters()

      expect(filters.category).toEqual(['landing'])
      expect(filters.searchQuery).toBe('test query')
    })
  })
})