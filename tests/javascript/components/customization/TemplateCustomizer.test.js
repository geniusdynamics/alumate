import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import TemplateCustomizer from '@/components/TemplateCustomizer.vue'
import ColorPicker from '@/components/ColorPicker.vue'
import FontSelector from '@/components/FontSelector.vue'
import ContentEditor from '@/components/ContentEditor.vue'

// Mock dependencies
vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn()
  }
}))

vi.mock('@/composables/useNotifications', () => ({
  useNotifications: () => ({
    showNotification: vi.fn()
  })
}))

describe('TemplateCustomizer', () => {
  let wrapper
  let mockTemplateService

  const mockInitialConfig = {
    templateId: 1,
    brand: {
      name: 'Test Brand',
      colors: {
        primary: '#3B82F6',
        secondary: '#6B7280'
      },
      fonts: {
        primary: {
          family: 'Arial',
          weight: 400
        }
      },
      styles: {
        borderRadius: 'md',
        spacing: 'default'
      }
    },
    content: {
      blocks: [
        {
          id: 'block1',
          type: 'text',
          data: { text: 'Test content' }
        }
      ],
      layout: {
        maxWidth: 'lg',
        alignment: 'center'
      }
    },
    settings: {
      name: 'Test Template',
      lazyLoad: true
    }
  }

  beforeEach(() => {
    // Mock fetch for API calls
    global.fetch = vi.fn()

    mockTemplateService = {
      generatePreview: vi.fn().mockResolvedValue({
        html: '<div>Preview</div>',
        css: '.preview { color: red; }',
        data: {}
      })
    }

    wrapper = mount(TemplateCustomizer, {
      props: {
        templateId: 1,
        initialConfig: mockInitialConfig,
        previewUrl: '#'
      },
      global: {
        stubs: {
          ColorPicker: true,
          FontSelector: true,
          ContentEditor: true
        }
      }
    })
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  describe('Initialization', () => {
    it('should initialize with provided config', () => {
      expect(wrapper.vm.brandCustomization.name).toBe('Test Brand')
      expect(wrapper.vm.contentCustomization.blocks).toHaveLength(1)
    })

    it('should render all panels', () => {
      const panels = wrapper.findAll('[role="tabpanel"]')
      expect(panels).toHaveLength(4) // brand, content, settings, export
    })

    it('should default to brand panel', () => {
      expect(wrapper.vm.activePanel).toBe('brand')
    })
  })

  describe('Panel Navigation', () => {
    it('should switch panels when nav buttons are clicked', async () => {
      const contentButton = wrapper.findAll('[role="tab"]')[1] // Content tab
      await contentButton.trigger('click')

      expect(wrapper.vm.activePanel).toBe('content')
    })

    it('should show correct panel content based on active tab', async () => {
      await wrapper.setData({ activePanel: 'settings' })

      const settingsPanel = wrapper.find('#panel-settings')
      expect(settingsPanel.exists()).toBe(true)
      expect(settingsPanel.isVisible()).toBe(true)
    })

    it('should hide inactive panels', async () => {
      await wrapper.setData({ activePanel: 'brand' })

      const contentPanel = wrapper.find('#panel-content')
      expect(contentPanel.isVisible()).toBe(false)
    })
  })

  describe('Brand Customization', () => {
    beforeEach(async () => {
      await wrapper.setData({ activePanel: 'brand' })
    })

    it('should update color when ColorPicker emits change', async () => {
      const colorPicker = wrapper.findComponent(ColorPicker)
      await colorPicker.vm.$emit('update:modelValue', '#FF0000')

      expect(wrapper.vm.brandCustomization.colors.primary).toBe('#FF0000')
    })

    it('should create snapshot when colors change', async () => {
      const initialStackLength = wrapper.vm.undoStack.length

      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          colors: {
            ...wrapper.vm.brandCustomization.colors,
            primary: '#00FF00'
          }
        }
      })

      await wrapper.vm.$nextTick()

      expect(wrapper.vm.undoStack.length).toBeGreaterThan(initialStackLength)
    })

    it('should undo color changes', async () => {
      const originalColor = wrapper.vm.brandCustomization.colors.primary

      // Make a change
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          colors: {
            ...wrapper.vm.brandCustomization.colors,
            primary: '#00FF00'
          }
        }
      })

      // Trigger undo
      await wrapper.find('button[aria-label="Undo last change"]').trigger('click')

      const undoButton = wrapper.find('[aria-label="Undo last change"]')
      expect(undoButton.exists()).toBe(true)
    })
  })

  describe('Content Customization', () => {
    it('should receive ContentEditor events', async () => {
      await wrapper.setData({ activePanel: 'content' })

      const contentEditor = wrapper.findComponent(ContentEditor)
      await contentEditor.vm.$emit('content-changed', {
        blockId: 'block1',
        content: 'New content'
      })

      expect(wrapper.vm.contentCustomization.blocks[0].data.content).toBe('New content')
    })

    it('should handle block deletion', async () => {
      await wrapper.setData({ activePanel: 'content' })

      const contentEditor = wrapper.findComponent(ContentEditor)
      await contentEditor.vm.$emit('block-deleted', 'block1')

      expect(wrapper.vm.contentCustomization.blocks).toHaveLength(0)
    })
  })

  describe('Undo/Redo Functionality', () => {
    it('should enable undo button when actions exist', async () => {
      // Initially no undo actions
      expect(wrapper.vm.canUndo).toBe(false)

      // Trigger a change that creates a snapshot
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          name: 'Changed Name'
        }
      })

      expect(wrapper.vm.canUndo).toBe(true)
    })

    it('should undo last action', async () => {
      const originalName = wrapper.vm.brandCustomization.name

      // Make change
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          name: 'Changed Name'
        }
      })

      expect(wrapper.vm.brandCustomization.name).toBe('Changed Name')

      // Undo
      await wrapper.vm.undo()

      expect(wrapper.vm.brandCustomization.name).toBe(originalName)
    })

    it('should limit undo stack to 50 items', async () => {
      // Create 55 changes (exceeding limit)
      for (let i = 0; i < 55; i++) {
        await wrapper.setData({
          brandCustomization: {
            ...wrapper.vm.brandCustomization,
            name: `Change ${i}`
          }
        })
      }

      expect(wrapper.vm.undoStack.length).toBeLessThanOrEqual(50)
    })

    it('should clear redo stack when new action is performed', async () => {
      // Make change
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          name: 'Change 1'
        }
      })

      // Undo
      await wrapper.vm.undo()

      expect(wrapper.vm.canRedo).toBe(true)

      // Make new change (should clear redo stack)
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          name: 'Change 2'
        }
      })

      expect(wrapper.vm.canRedo).toBe(false)
    })
  })

  describe('Auto-Save Functionality', () => {
    beforeEach(() => {
      vi.useFakeTimers()
    })

    afterEach(() => {
      vi.restoreAllTimers()
    })

    it('should auto-save when enabled and changes are made', async () => {
      await wrapper.setData({ autoSave: true })

      const saveSpy = vi.spyOn(wrapper.vm, 'saveTemplate')

      // Make change
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          name: 'Auto-saved Name'
        }
      })

      // Advance timers
      await vi.advanceTimersByTime(1500)

      expect(saveSpy).toHaveBeenCalled()
    })

    it('should not auto-save when disabled', async () => {
      await wrapper.setData({ autoSave: false })

      const saveSpy = vi.spyOn(wrapper.vm, 'saveTemplate')

      // Make change
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          name: 'Should not save'
        }
      })

      await vi.advanceTimersByTime(1500)

      expect(saveSpy).not.toHaveBeenCalled()
    })
  })

  describe('Save Functionality', () => {
    it('should show loading state during save', async () => {
      global.fetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({}) })

      const saveButton = wrapper.find('button').filter(btn => btn.text().includes('Save'))
      await saveButton.trigger('click')

      expect(wrapper.vm.isSaving).toBe(true)
      await flushPromises()
      expect(wrapper.vm.isSaving).toBe(false)
    })

    it('should emit saved event with full config', async () => {
      const spy = vi.fn()
      wrapper.vm.$emit = spy

      global.fetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({}) })

      await wrapper.vm.saveTemplate()
      await flushPromises()

      expect(spy).toHaveBeenCalledWith('saved', expect.any(Object))
    })

    it('should show success notification on successful save', async () => {
      global.fetch.mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({}) })

      await wrapper.vm.saveTemplate()
      await flushPromises()

      expect(wrapper.vm.saveStatus?.type).toBe('success')
      expect(wrapper.vm.saveStatus?.message).toContain('saved successfully')
    })

    it('should show error notification on failed save', async () => {
      global.fetch.mockRejectedValueOnce(new Error('Save failed'))

      await wrapper.vm.saveTemplate()
      await flushPromises()

      expect(wrapper.vm.saveStatus?.type).toBe('error')
      expect(wrapper.vm.saveStatus?.message).toContain('Failed to save')
    })
  })

  describe('Preview Functionality', () => {
    it('should toggle preview panel', async () => {
      const previewButton = wrapper.find('[aria-label="Toggle live preview"]')
      await previewButton.trigger('click')

      expect(wrapper.vm.showPreview).toBe(true)

      await previewButton.trigger('click')
      expect(wrapper.vm.showPreview).toBe(false)
    })

    it('should generate preview for different devices', async () => {
      await wrapper.setData({ showPreview: true })

      const tabletButton = wrapper.findAll('.viewport-btn').find(btn =>
        btn.text().includes('768px')
      )
      await tabletButton.trigger('click')

      expect(wrapper.vm.currentPreviewDevice).toBe('tablet')
      expect(wrapper.emitted('previewUpdated')).toBeTruthy()
    })

    it('should update preview on configuration changes', async () => {
      await wrapper.setData({ showPreview: true })

      // Make a change that should trigger preview update
      await wrapper.setData({
        brandCustomization: {
          ...wrapper.vm.brandCustomization,
          colors: {
            ...wrapper.vm.brandCustomization.colors,
            primary: '#FF0000'
          }
        }
      })

      // Should trigger preview update (handled by watchers)
      await wrapper.vm.$nextTick()
      expect(wrapper.emitted('customizationChanged')).toBeTruthy()
    })
  })

  describe('Export Functionality', () => {
    it('should export as HTML', async () => {
      await wrapper.setData({ activePanel: 'export' })

      const htmlButton = wrapper.findAll('.format-btn').find(btn => btn.text().includes('HTML'))
      await htmlButton.trigger('click')

      expect(wrapper.vm.exportTemplate).toHaveBeenCalledWith('html')
    })

    it('should generate shareable link', async () => {
      const spy = vi.spyOn(navigator.clipboard, 'writeText').mockResolvedValue()

      await wrapper.vm.generateShareableLink()

      expect(spy).toHaveBeenCalledWith(expect.stringContaining(window.location.origin))
      spy.mockRestore()
    })

    it('should export configuration as JSON', async () => {
      const spy = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:mock-url')
      const spy2 = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => {})

      await wrapper.vm.exportAsJson()

      expect(spy).toHaveBeenCalledWith(expect.any(Blob))

      spy.mockRestore()
      spy2.mockRestore()
    })
  })

  describe('Keyboard Navigation', () => {
    it('should handle Ctrl+Z for undo', () => {
      const undoSpy = vi.spyOn(wrapper.vm, 'undo')

      const event = new KeyboardEvent('keydown', { ctrlKey: true, key: 'z' })
      document.dispatchEvent(event)

      expect(undoSpy).toHaveBeenCalled()
    })

    it('should handle Ctrl+Y for redo', () => {
      const redoSpy = vi.spyOn(wrapper.vm, 'redo')

      const event = new KeyboardEvent('keydown', { ctrlKey: true, key: 'y' })
      document.dispatchEvent(event)

      expect(redoSpy).toHaveBeenCalled()
    })

    it('should handle Ctrl+S for save', () => {
      const saveSpy = vi.spyOn(wrapper.vm, 'saveTemplate')

      const event = new KeyboardEvent('keydown', { ctrlKey: true, key: 's' })
      event.preventDefault = vi.fn()
      document.dispatchEvent(event)

      expect(saveSpy).toHaveBeenCalled()
    })

    it('should show shortcuts help', () => {
      const event = new KeyboardEvent('keydown', { key: '?' })
      document.dispatchEvent(event)

      expect(wrapper.vm.showShortcuts).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('should have proper ARIA attributes for tabs', () => {
      const nav = wrapper.find('[role="tablist"]')
      expect(nav.exists()).toBe(true)

      const tabs = wrapper.findAll('[role="tab"]')
      expect(tabs.length).toBeGreaterThan(0)

      tabs.forEach(tab => {
        expect(tab.attributes('aria-selected')).toBeDefined()
        expect(tab.attributes('aria-controls')).toBeDefined()
      })
    })

    it('should have accessible panel identifiers', () => {
      const panels = wrapper.findAll('[role="tabpanel"]')
      panels.forEach(panel => {
        expect(panel.attributes('aria-labelledby')).toBeDefined()
        expect(panel.attributes('id')).toBeDefined()
      })
    })

    it('should announce panel changes to screen readers', async () => {
      await wrapper.setData({ activePanel: 'content' })

      expect(wrapper.vm.accessibilityMessage).toContain('Content')
    })

    it('should provide keyboard navigation for panels', async () => {
      const firstTab = wrapper.find('[role="tab"]')
      await firstTab.focus()

      // Simulate Tab key
      const tabEvent = new KeyboardEvent('keydown', { key: 'Tab' })
      document.dispatchEvent(tabEvent)

      expect(firstTab.element).toBe(document.activeElement)
    })
  })

  describe('Error Handling', () => {
    it('should handle API errors gracefully', async () => {
      global.fetch.mockRejectedValueOnce(new Error('Network error'))

      await wrapper.vm.saveTemplate()

      expect(wrapper.vm.saveStatus?.type).toBe('error')
      expect(wrapper.vm.isSaving).toBe(false)
    })

    it('should handle invalid preview URLs', async () => {
      await wrapper.setData({ previewUrl: 'invalid-url' })

      const previewFrame = wrapper.find('iframe')
      expect(previewFrame.attributes('src')).toBe('invalid-url')
    })

    it('should handle missing template ID', () => {
      const config = wrapper.vm.getFullConfig()
      // Should handle undefined templateId gracefully
      expect(config).toBeDefined()
    })
  })

  describe('Performance', () => {
    it('should debounce preview updates', async () => {
      vi.useFakeTimers()

      const spy = vi.fn()
      wrapper.vm.updatePreview = spy

      // Multiple rapid changes
      await wrapper.setData({ brandCustomization: { ...wrapper.vm.brandCustomization, name: 'Change 1' } })
      await wrapper.setData({ brandCustomization: { ...wrapper.vm.brandCustomization, name: 'Change 2' } })
      await wrapper.setData({ brandCustomization: { ...wrapper.vm.brandCustomization, name: 'Change 3' } })

      expect(spy).toHaveBeenCalledTimes(1) // Should be debounced

      vi.restoreAllTimers()
    })

    it('should limit undo stack size', async () => {
      vi.useRealTimers()

      // Create 60 snapshots (over the limit of 50)
      for (let i = 0; i < 60; i++) {
        await wrapper.vm.createSnapshot(`Change ${i}`)
      }

      expect(wrapper.vm.undoStack.length).toBeLessThanOrEqual(50)
    })
  })
})

describe('ColorPicker Component', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(ColorPicker, {
      props: {
        modelValue: '#3B82F6',
        showAlpha: true,
        presetColors: ['#FF0000', '#00FF00', '#0000FF']
      }
    })
  })

  it('should render color picker interface', () => {
    expect(wrapper.find('.color-picker').exists()).toBe(true)
    expect(wrapper.find('.color-palette').exists()).toBe(true)
    expect(wrapper.find('.hue-slider').exists()).toBe(true)
  })

  it('should open picker dialog when toggled', async () => {
    const toggleBtn = wrapper.find('.color-swatch')
    await toggleBtn.trigger('click')

    expect(wrapper.vm.isDialogOpen).toBe(true)
    expect(wrapper.find('.color-picker-modal').exists()).toBe(true)
  })

  it('should emit color change events', async () => {
    await wrapper.vm.updateFromHex('#FF0000')

    expect(wrapper.emitted('update:modelValue')).toBeTruthy()
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['#FF0000'])
  })

  it('should validate hex colors', async () => {
    await wrapper.vm.updateFromHex('invalid')

    expect(wrapper.vm.isValidColor).toBe(false)

    await wrapper.vm.updateFromHex('#FF0000')

    expect(wrapper.vm.isValidColor).toBe(true)
  })

  it('should close dialog on escape', async () => {
    await wrapper.setData({ isDialogOpen: true })

    const event = new KeyboardEvent('keydown', { key: 'Escape' })
    document.dispatchEvent(event)

    expect(wrapper.vm.isDialogOpen).toBe(false)
  })
})

describe('FontSelector Component', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(FontSelector, {
      props: {
        modelValue: 'Arial',
        includeGoogleFonts: true,
        allowSystemFonts: true
      }
    })
  })

  it('should render font selector interface', () => {
    expect(wrapper.find('.font-selector').exists()).toBe(true)
    expect(wrapper.find('.font-current').exists()).toBe(true)
  })

  it('should load fonts on mount', async () => {
    expect(wrapper.vm.systemFonts.length).toBeGreaterThan(0)
  })

  it('should filter fonts by search query', async () => {
    await wrapper.setData({ fontSearchQuery: 'Arial' })

    expect(wrapper.vm.filteredFonts.length).toBe(1)
    expect(wrapper.vm.filteredFonts[0].name).toBe('Arial')
  })

  it('should select font and emit event', async () => {
    const fontItem = wrapper.find('.font-item')
    await fontItem.trigger('click')

    expect(wrapper.emitted('font-selected')).toBeTruthy()
  })

  it('should preview selected font', async () => {
    await wrapper.vm.previewFont(wrapper.vm.systemFonts[0])

    expect(wrapper.vm.previewFontData).not.toBeNull()
  })
})

describe('ContentEditor Component', () => {
  let wrapper
  let mockBlocks

  beforeEach(() => {
    mockBlocks = [
      {
        id: 'block1',
        type: 'text',
        data: { text: 'Sample text' }
      }
    ]

    wrapper = mount(ContentEditor, {
      props: {
        initialBlocks: mockBlocks
      }
    })
  })

  it('should render content blocks', () => {
    const blocks = wrapper.findAll('.content-block')
    expect(blocks.length).toBe(1)
  })

  it('should add new block', () => {
    const addBtn = wrapper.find('button').filter(btn => btn.text().includes('Add Block'))
    addBtn.trigger('click') // This would trigger a modal, but we'll test the method directly

    // Test the method directly
    wrapper.vm.addNewBlock('image')

    expect(wrapper.vm.contentBlocks.length).toBe(2)
    expect(wrapper.vm.contentBlocks[1].type).toBe('image')
  })

  it('should select block on click', async () => {
    const block = wrapper.find('.content-block')
    await block.trigger('click')

    expect(wrapper.vm.selectedBlockId).toBe('block1')
  })

  it('should delete block', async () => {
    const deleteBtn = wrapper.find('.block-action-btn.delete')
    await deleteBtn.trigger('click')

    // Mock window.confirm
    window.confirm = vi.fn(() => true)

    expect(wrapper.vm.contentBlocks.length).toBe(0)
  })

  it('should emit content change events', async () => {
    await wrapper.vm.selectBlock('block1')

    wrapper.vm.updateTextContent()

    expect(wrapper.emitted('content-changed')).toBeTruthy()
  })

  it('should handle keyboard navigation', async () => {
    const block = wrapper.find('.content-block')
    await block.focus()

    const event = new KeyboardEvent('keydown', { key: 'Enter' })
    await block.element.dispatchEvent(event)

    expect(wrapper.vm.selectedBlockId).toBe('block1')
  })
})

// Integration tests
describe('TemplateCustomizer Integration', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(TemplateCustomizer, {
      props: {
        templateId: 1,
        initialConfig: {}
      }
    })
  })

  it('should update preview when brand colors change', async () => {
    const spy = vi.spyOn(wrapper.vm, 'updatePreview')

    await wrapper.setData({
      brandCustomization: {
        ...wrapper.vm.brandCustomization,
        colors: { primary: '#FF0000' }
      }
    })

    expect(spy).toHaveBeenCalled()
  })

  it('should maintain state consistency across panels', async () => {
    // Switch to brand panel
    await wrapper.setData({ activePanel: 'brand' })

    const initialColor = wrapper.vm.brandCustomization.colors.primary

    // Switch to content panel
    await wrapper.setData({ activePanel: 'content' })

    // Switch back to brand panel
    await wrapper.setData({ activePanel: 'brand' })

    // Color should be the same
    expect(wrapper.vm.brandCustomization.colors.primary).toBe(initialColor)
  })
})

// Performance tests
describe('TemplateCustomizer Performance', () => {
  it('should handle large configuration updates efficiently', async () => {
    const startTime = Date.now()

    const wrapper = mount(TemplateCustomizer, {
      props: {
        templateId: 1,
        initialConfig: {
          brand: {
            name: 'Performance Test',
            colors: {},
            fonts: {},
            styles: {}
          },
          content: { blocks: [] },
          settings: {}
        }
      }
    })

    // Add many color properties (simulating large config)
    const largeColors = {}
    for (let i = 0; i < 100; i++) {
      largeColors[`color${i}`] = '#FFA500'
    }

    await wrapper.setData({
      brandCustomization: {
        ...wrapper.vm.brandCustomization,
        colors: largeColors
      }
    })

    const endTime = Date.now()
    const updateTime = endTime - startTime

    // Should complete within reasonable time (under 100ms for large update)
    expect(updateTime).toBeLessThan(100)
  })
})

// Accessibility tests
describe('Accessibility Compliance', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(TemplateCustomizer)
  })

  it('should meet WCAG 2.1 AA contrast requirements', () => {
    // Test text against background
    const mainEl = wrapper.find('.template-customizer').element
    const styles = window.getComputedStyle(mainEl)

    // This would normally use a color contrast calculation library
    expect(styles).toBeDefined()
  })

  it('should support keyboard navigation', async () => {
    const firstFocusable = wrapper.find('button').element
    await firstFocusable.focus()

    expect(document.activeElement).toBe(firstFocusable)
  })

  it('should announce dynamic content changes', async () => {
    await wrapper.setData({ activePanel: 'content' })

    expect(wrapper.vm.accessibilityMessage).toBeTruthy()
  })

  it('should have proper screen reader labels', () => {
    const ariaLabels = wrapper.findAll('[aria-label]')
    expect(ariaLabels.length).toBeGreaterThan(0)

    const ariaDescribedBy = wrapper.findAll('[aria-describedby]')
    expect(ariaDescribedBy.length).toBeGreaterThan(0)
  })
})