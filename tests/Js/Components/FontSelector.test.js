import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import FontSelector from '@/components/FontSelector.vue'

// Mock Google Fonts API
vi.mock('@/services/googleFonts', () => ({
  loadFontsApi: vi.fn().mockResolvedValue({
    families: ['Inter', 'Roboto', 'Open Sans', 'Montserrat', 'Poppins']
  }),
  loadFont: vi.fn(),
  getFontUrl: vi.fn().mockReturnValue('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700')
}))

// Mock localStorage
vi.mock('@/composables/useLocalStorage', () => ({
  useLocalStorage: () => ({
    value: vi.fn(() => ({
      'recent-fonts': ['Inter', 'Roboto', 'Montserrat']
    }))
  })
}))

// Mock debounced search
vi.mock('@/composables/useDebounce', () => ({
  useDebounce: (fn) => fn
}))

const mockFontValidation = {
  isValid: vi.fn().mockReturnValue(true),
  getFontMetrics: vi.fn().mockReturnValue({
    capHeight: 70,
    xHeight: 50,
    lineHeight: 1.2
  })
}

vi.mock('@/utils/fontValidation', () => ({
  validateGoogleFont: () => Promise.resolve(mockFontValidation)
}))

const createWrapper = (props = {}) => {
  return mount(FontSelector, {
    props: {
      modelValue: 'Inter',
      type: 'headingFont',
      name: 'Heading Font',
      ...props
    },
    global: {
      stubs: ['svg'],
      plugins: [createTestingPinia()],
      mocks: {
        $toast: {
          success: vi.fn(),
          error: vi.fn(),
          warning: vi.fn()
        }
      }
    },
    attachTo: document.body
  })
}

describe('FontSelector.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = createWrapper()
  })

  describe('Component Structure', () => {
    it('renders the font selector interface', () => {
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('.font-selector-container').exists()).toBe(true)
    })

    it('displays current font value', () => {
      const selectedFont = wrapper.find('.selected-font')
      expect(selectedFont.exists()).toBe(true)
      expect(wrapper.text()).toContain('Inter')
    })

    it('shows font preview area', () => {
      expect(wrapper.find('.font-preview').exists()).toBe(true)
    })

    it('renders search input', () => {
      const input = wrapper.find('input[type="search"]')
      expect(input.exists()).toBe(true)
      expect(input.attributes('placeholder')).toMatch(/Search fonts/i)
    })
  })

  describe('Font Loading and Initialization', () => {
    it('loads Google Fonts list on mount', async () => {
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.fonts.length).toBeGreaterThan(0)
      expect(wrapper.vm.fonts).toContain('Inter')
    })

    it('loads selected font CSS on initialization', async () => {
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.loadedFonts).toContain('Inter')
    })

    it('handles font loading errors gracefully', async () => {
      vi.mocked(wrapper.vm.loadFontsApi).mockRejectedValue(new Error('API Error'))

      wrapper = createWrapper()
      await wrapper.vm.$nextTick()

      // Should fall back to system fonts
      expect(wrapper.vm.fonts.length).toBeGreaterThan(0)
      expect(wrapper.emitted('error')).toBeDefined()
    })
  })

  describe('Font Search and Filtering', () => {
    it('filters fonts based on search input', async () => {
      const searchInput = wrapper.find('input[type="search"]')
      await searchInput.setValue('open')

      expect(wrapper.vm.filteredFonts.length).toBeGreaterThan(0)
      wrapper.vm.filteredFonts.forEach(font => {
        expect(font.toLowerCase()).toContain('open')
      })
    })

    it('shows all fonts when search is cleared', async () => {
      const searchInput = wrapper.find('input[type="search"]')
      await searchInput.setValue('roboto')
      await searchInput.setValue('')

      expect(wrapper.vm.filteredFonts.length).toBe(wrapper.vm.fonts.length)
    })

    it('filters fonts by category', async () => {
      wrapper = createWrapper({ category: 'serif' })
      await wrapper.vm.$nextTick()

      // All shown fonts should be serif (simulated)
      expect(wrapper.vm.filteredFonts.every(font => {
        return wrapper.vm.getFontCategories(font).includes('serif')
      })).toBe(true)
    })

    it('debounces search operations', async () => {
      const searchSpy = vi.spyOn(wrapper.vm, 'performSearch')

      // Rapid keystrokes
      const searchInput = wrapper.find('input[type="search"]')
      await searchInput.setValue('r')
      await searchInput.setValue('ro')
      await searchInput.setValue('rob')

      await wrapper.vm.$nextTick()

      expect(searchSpy).toHaveBeenCalledTimes(1)
    })
  })

  describe('Font Selection', () => {
    it('selects font from list on click', async () => {
      await wrapper.vm.$nextTick() // Wait for fonts to load

      const fontItems = wrapper.findAll('.font-item').filter(item => {
        return item.text().includes('Roboto')
      })

      if (fontItems.length > 0) {
        await fontItems[0].trigger('click')

        expect(wrapper.emitted().input).toBeDefined()
        expect(wrapper.emitted().input[0]).toEqual(['Roboto'])
      }
    })

    it('emits change event with font data', async () => {
      const fontItem = wrapper.find('.font-item')
      await fontItem.trigger('click')

      const emittedEvent = wrapper.emitted().change
      expect(emittedEvent).toBeDefined()
      expect(emittedEvent[0][0]).toHaveProperty('family')
      expect(emittedEvent[0][0]).toHaveProperty('category')
    })

    it('loads font CSS when selected', async () => {
      const newFont = 'Poppins'

      wrapper.vm.selectFont(newFont, 'sans-serif')

      expect(wrapper.vm.loadedFonts).toContain(newFont)
    })

    it('maintains selected font state', () => {
      wrapper = createWrapper({ modelValue: 'Montserrat' })

      expect(wrapper.vm.selectedFont).toBe('Montserrat')
    })
  })

  describe('Font Preview and Display', () => {
    it('shows font preview with correct styling', async () => {
      const preview = wrapper.find('.font-preview')
      await wrapper.vm.$nextTick()

      expect(preview.attributes('style')).toContain('font-family')
      expect(preview.text()).toBe(wrapper.vm.previewText)
    })

    it('updates preview text dynamically', async () => {
      const previewInput = wrapper.find('.preview-text-input')
      await previewInput.setValue('Hello World')

      expect(wrapper.vm.previewText).toBe('Hello World')
      expect(wrapper.find('.font-preview').text()).toBe('Hello World')
    })

    it('handles font loading states correctly', async () => {
      const loadingFont = 'NewFont'
      wrapper.vm.loadingFonts.add(loadingFont)

      expect(wrapper.find('.font-loading-spinner').exists()).toBe(true)
    })

    it('shows fallback text when font fails to load', async () => {
      vi.mocked(wrapper.vm.loadFont).mockRejectedValue(new Error('Load failed'))

      wrapper.vm.selectFont('FailingFont', 'sans-serif')
      await wrapper.vm.$nextTick()

      const preview = wrapper.find('.font-preview')
      expect(preview.classes()).toContain('fallback-font')
    })
  })

  describe('Font Validation', () => {
    it('validates font availability', async () => {
      const isValid = await wrapper.vm.validateFont('Inter')
      expect(isValid).toBe(true)

      const invalidFont = await wrapper.vm.validateFont('NonExistentFont')
      expect(invalidFont).toBe(false)
    })

    it('shows validation feedback for invalid fonts', async () => {
      const invalidFont = 'InvalidFont123'
      wrapper.vm.selectFont(invalidFont)

      await wrapper.vm.$nextTick()

      expect(wrapper.find('.font-error').exists()).toBe(true)
    })

    it('handles font metrics calculation', () => {
      const metrics = wrapper.vm.getFontMetrics('Inter')
      expect(metrics).toHaveProperty('capHeight')
      expect(metrics).toHaveProperty('xHeight')
      expect(typeof metrics.capHeight).toBe('number')
    })
  })

  describe('Font Categories and Organization', () => {
    it('groups fonts by category correctly', () => {
      const categories = wrapper.vm.groupFontsByCategory()
      expect(categories.sansSerif).toBeDefined()
      expect(categories.serif).toBeDefined()
      expect(categories.display).toBeDefined()
    })

    it('shows category tabs', () => {
      const categoryTabs = wrapper.findAll('.category-tab')
      expect(categoryTabs.length).toBeGreaterThan(0)

      expect(categoryTabs.some(tab => tab.text().toLowerCase().includes('sans'))).toBe(true)
      expect(categoryTabs.some(tab => tab.text().toLowerCase().includes('serif'))).toBe(true)
    })

    it('filters fonts by active category', async () => {
      const serifTab = wrapper.find('.category-tab-serif')
      await serifTab.trigger('click')

      expect(wrapper.vm.activeCategory).toBe('serif')
      expect(wrapper.vm.filteredFonts.every(font =>
        wrapper.vm.getFontCategories(font).includes('serif')
      )).toBe(true)
    })
  })

  describe('Recent Fonts Management', () => {
    it('displays recently used fonts', () => {
      const recentFonts = wrapper.findAll('.recent-font-item')
      expect(recentFonts.length).toBeGreaterThan(0)
    })

    it('adds font to recent list when selected', () => {
      const newFont = 'Poppins'
      wrapper.vm.selectFont(newFont, 'sans-serif')

      expect(wrapper.vm.recentFonts).toContain(newFont)
    })

    it('limits recent fonts to maximum count', () => {
      // Add many fonts
      const fonts = ['Font1', 'Font2', 'Font3', 'Font4', 'Font5', 'Font6']

      fonts.forEach(font => {
        wrapper.vm.selectFont(font, 'sans-serif')
      })

      expect(wrapper.vm.recentFonts.length).toBeLessThanOrEqual(wrapper.vm.maxRecentFonts)
    })

    it('removes duplicate fonts from recent list', () => {
      const duplicateFont = 'Inter'

      wrapper.vm.selectFont(duplicateFont, 'sans-serif')
      wrapper.vm.selectFont(duplicateFont, 'sans-serif') // Select same font again

      const occurrences = wrapper.vm.recentFonts.filter(font => font === duplicateFont).length
      expect(occurrences).toBe(1)
    })
  })

  describe('Accessibility Features', () => {
    it('provides proper ARIA labels for inputs', () => {
      const searchInput = wrapper.find('input[type="search"]')
      expect(searchInput.attributes('aria-label')).toBeDefined()

      const previewInput = wrapper.find('.preview-text-input')
      expect(previewInput.attributes('aria-label')).toBeDefined()
    })

    it('supports keyboard navigation through font list', async () => {
      const firstFont = wrapper.find('.font-item')
      await firstFont.element.focus()

      expect(document.activeElement).toBe(firstFont.element)
    })

    it('announces font selection to screen readers', async () => {
      const announceSpy = vi.spyOn(wrapper.vm, 'announceFontSelection')

      const fontItem = wrapper.find('.font-item')
      await fontItem.trigger('click')

      expect(announceSpy).toHaveBeenCalled()
    })

    it('provides adequate color contrast for text', () => {
      const fontItems = wrapper.findAll('.font-item')
      fontItems.forEach(item => {
        expect(item.classes()).toContain('text-accessible')
      })
    })

    it('supports keyboard selection of fonts', async () => {
      const fontItem = wrapper.find('.font-item')

      await fontItem.trigger('keydown.enter')
      expect(wrapper.emitted().input).toBeDefined()

      await fontItem.trigger('keydown.space')
      expect(wrapper.emitted().input).toBeDefined()
    })
  })

  describe('Performance Optimization', () => {
    it('lazy loads fonts outside of viewport', async () => {
      // Mock fonts below the fold
      const fontList = wrapper.find('.font-list')
      const lastFont = wrapper.find('.font-item').at(-1)

      // Trigger intersection observer
      const intersectionEvent = new Event('intersect')
      lastFont.element.dispatchEvent(intersectionEvent)

      await wrapper.vm.$nextTick()

      expect(wrapper.vm.lazyLoadedFonts).toContain(lastFont.text())
    })

    it('caches font data to avoid repeated API calls', async () => {
      const apiSpy = vi.spyOn(wrapper.vm, 'loadFontsApi')

      // First call
      await wrapper.vm.loadFonts()
      const firstCallCount = apiSpy.mock.calls.length

      // Second call should use cache
      await wrapper.vm.loadFonts()

      expect(apiSpy.mock.calls.length).toBe(firstCallCount)
    })

    it('debounces font preview updates', async () => {
      const previewSpy = vi.spyOn(wrapper.vm, 'updatePreview')

      // Rapid changes
      wrapper.vm.previewText = 'A'
      wrapper.vm.previewText = 'AB'
      wrapper.vm.previewText = 'ABC'

      await wrapper.vm.$nextTick()

      expect(previewSpy).toHaveBeenCalledTimes(1)
    })
  })

  describe('Error Handling', () => {
    it('handles network failures gracefully', async () => {
      vi.mocked(wrapper.vm.loadFontsApi).mockRejectedValue(new Error('Network error'))

      await wrapper.vm.loadFonts()

      expect(wrapper.vm.showError).toBe(true)
      expect(wrapper.find('.error-message').exists()).toBe(true)
    })

    it('shows retry option for failed operations', async () => {
      vi.mocked(wrapper.vm.loadFontsApi).mockRejectedValue(new Error('Network error'))

      await wrapper.vm.loadFonts()

      const retryButton = wrapper.find('.retry-button')
      expect(retryButton.exists()).toBe(true)

      await retryButton.trigger('click')

      expect(vi.mocked(wrapper.vm.loadFontsApi)).toHaveBeenCalledTimes(2)
    })

    it('handles malformed font data', () => {
      const invalidFontData = { invalid: 'data' }

      expect(() => {
        wrapper.vm.processFontData(invalidFontData)
      }).not.toThrow()
    })

    it('provides fallback fonts when API fails', () => {
      wrapper.vm.fallbackFonts = []

      expect(wrapper.vm.fonts.length).toBeGreaterThan(0) // Uses system fonts
    })
  })

  describe('Responsive Design', () => {
    it('adapts layout for mobile devices', async () => {
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        value: 400
      })

      wrapper.vm.checkResponsiveMode()

      expect(wrapper.vm.isMobile).toBe(true)
      expect(wrapper.classes()).toContain('font-selector--mobile')
    })

    it('stacks controls vertically on small screens', () => {
      wrapper = createWrapper()

      Object.defineProperty(window, 'innerWidth', { value: 768 })
      wrapper.vm.checkResponsiveMode()

      const container = wrapper.find('.font-selector-container')
      expect(container.classes()).toContain('mobile-layout')
    })

    it('adjusts font list height for smaller screens', () => {
      wrapper = createWrapper()

      Object.defineProperty(window, 'innerWidth', { value: 600 })
      wrapper.vm.checkResponsiveMode()

      const fontList = wrapper.find('.font-list')
      if (fontList.exists()) {
        expect(fontList.classes()).toContain('compact-height')
      }
    })
  })
})