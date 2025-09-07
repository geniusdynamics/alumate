import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TemplateCustomizer from '@/components/TemplateCustomizer.vue'

// Mock dependencies
vi.mock('@/services/TemplateService', () => ({
  templateService: {
    generatePreview: vi.fn().mockResolvedValue({
      css: 'body { color: red; }',
      html: '<div>Preview content</div>'
    })
  }
}))

describe('TemplateCustomizer.vue', () => {
  let wrapper
  let mockTemplate
  let mockCustomization

  beforeEach(() => {
    // Mock template data
    mockTemplate = {
      id: 1,
      name: 'Test Template',
      category: 'landing',
      audienceType: 'individual'
    }

    // Mock customization data
    mockCustomization = {
      templateId: 1,
      brand: {
        name: 'Test Brand',
        colors: {
          primary: {
            type: 'primary' as const,
            color: '#007bff'
          },
          secondary: {
            type: 'secondary' as const,
            color: '#6c757d'
          }
        },
        fonts: {
          heading: {
            type: 'heading' as const,
            family: 'Arial',
            weight: 700,
            size: 24,
            lineHeight: 1.2,
            fallbacks: ['sans-serif'],
            source: 'system' as const
          },
          body: {
            type: 'body' as const,
            family: 'Times New Roman',
            weight: 400,
            size: 16,
            lineHeight: 1.5,
            fallbacks: ['serif'],
            source: 'system' as const
          }
        },
        logos: [],
        styles: {
          borderRadius: 'md' as const,
          spacing: 'default' as const,
          shadow: 'md' as const
        }
      },
      content: {
        blocks: [],
        layout: {
          maxWidth: 'lg' as const,
          alignment: 'center' as const,
          verticalSpacing: 'md' as const,
          responsiveGaps: true
        },
        global: {}
      },
      settings: {
        autoSave: false,
        realTimePreview: true,
        showAccessibilityWarnings: true,
        darkModePreview: false,
        responsivePreview: true,
        exportFormats: ['pdf', 'png', 'jpg', 'html']
      },
      metadata: {
        createdBy: 1,
        createdAt: new Date().toISOString(),
        version: 1,
        changeLog: []
      }
    }

    wrapper = mount(TemplateCustomizer, {
      global: {
        plugins: [createTestingPinia()],
        stubs: {
          ColorPicker: true,
          FontSelector: true,
          ContentEditor: true,
          Transition: false,
          Teleport: false
        }
      },
      props: {
        modelValue: mockCustomization,
        template: mockTemplate
      }
    })
  })

  describe('Initialization', () => {
    it('renders with correct template information', () => {
      const title = wrapper.find('.template-title')
      const category = wrapper.find('.template-category')
      const audience = wrapper.find('.template-audience')

      expect(title.text()).toBe('Test Template')
      expect(category.text()).toBe('landing')
      expect(audience.text()).toBe('individual')
    })

    it('initializes with provided customization data', () => {
      expect(wrapper.vm.customizationData).toEqual(mockCustomization)
    })

    it('starts with brand panel as active by default', () => {
      expect(wrapper.vm.activePanel).toBe('brand')
    })

    it('starts with desktop viewport by default', () => {
      expect(wrapper.vm.activeViewport).toBe('desktop')
    })
  })

  describe('Brand Panel', () => {
    beforeEach(async () => {
      await wrapper.setData({ activePanel: 'brand' })
    })

    it('contains brand colors section', () => {
      const colorsSection = wrapper.find('[data-testid="brand-colors"]')
      expect(colorsSection.exists()).toBe(true)
    })

    it('contains typography section', () => {
      const typographySection = wrapper.find('[data-testid="brand-typography"]')
      expect(typographySection.exists()).toBe(true)
    })

    it('contains brand assets section', () => {
      const assetsSection = wrapper.find('[data-testid="brand-assets"]')
      expect(assetsSection.exists()).toBe(true)
    })

    it('updates brand color when color picker changes', async () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')

      // Simulate color change
      wrapper.vm.updateBrandColor('primary', '#ff0000')

      expect(wrapper.vm.customizationData.brand.colors.primary.color).toBe('#ff0000')
      expect(emit).toHaveBeenCalledWith('update:modelValue', wrapper.vm.customizationData)
    })

    it('updates brand font when font selector changes', async () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')
      const newFont = {
        family: 'Helvetica',
        weight: 600,
        size: 18
      }

      wrapper.vm.updateBrandFont('heading', newFont)

      expect(wrapper.vm.customizationData.brand.fonts.heading.family).toBe('Helvetica')
      expect(emit).toHaveBeenCalledWith('update:modelValue', wrapper.vm.customizationData)
    })
  })

  describe('Content Panel', () => {
    beforeEach(async () => {
      await wrapper.setData({ activePanel: 'content' })
    })

    it('contains content editor component', () => {
      const contentEditor = wrapper.findComponent({ name: 'ContentEditor' })
      expect(contentEditor.exists()).toBe(true)
    })

    it('passes correct props to content editor', () => {
      const contentEditor = wrapper.findComponent({ name: 'ContentEditor' })
      const props = contentEditor.props()

      expect(props.modelValue).toBe(wrapper.vm.customizationData.content)
      expect(props.template).toBe(mockTemplate)
    })

    it('updates content when content editor changes', () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')
      const newContent = { blocks: [{ id: 'test-block' }] }

      wrapper.vm.updateContent(newContent)

      expect(wrapper.vm.customizationData.content).toEqual(newContent)
      expect(emit).toHaveBeenCalledWith('update:modelValue', wrapper.vm.customizationData)
    })
  })

  describe('Settings Panel', () => {
    beforeEach(async () => {
      await wrapper.setData({ activePanel: 'settings' })
    })

    it('contains settings sections', () => {
      const generalSettings = wrapper.find('[data-testid="settings-general"]')
      const accessibilitySettings = wrapper.find('[data-testid="settings-accessibility"]')
      const exportSettings = wrapper.find('[data-testid="settings-export"]')

      expect(generalSettings.exists()).toBe(true)
      expect(accessibilitySettings.exists()).toBe(true)
      expect(exportSettings.exists()).toBe(true)
    })

    it('updates auto-save setting', async () => {
      const checkbox = wrapper.find('#auto-save')
      await checkbox.setValue(true)

      expect(wrapper.vm.customizationSettings.autoSave).toBe(true)
    })

    it('updates real-time preview setting', async () => {
      const checkbox = wrapper.find('#real-time-preview')
      await checkbox.setValue(false)

      expect(wrapper.vm.customizationSettings.realTimePreview).toBe(false)
    })
  })

  describe('Preview Functionality', () => {
    it('shows preview error when generation fails', async () => {
      const { templateService } = await import('@/services/TemplateService')
      templateService.generatePreview.mockRejectedValue(new Error('Preview failed'))

      wrapper.vm.generatePreview()
      await flushPromises()

      expect(wrapper.vm.previewError).toBe('Preview generation failed')
      expect(wrapper.vm.previewSrc).toBe('')
    })

    it('shows loading state during preview generation', async () => {
      const { templateService } = await import('@/services/TemplateService')
      templateService.generatePreview.mockImplementation(() =>
        new Promise(resolve => setTimeout(resolve, 100))
      )

      const generatePromise = wrapper.vm.generatePreview()
      expect(wrapper.vm.previewLoading).toBe(true)

      await generatePromise
      expect(wrapper.vm.previewLoading).toBe(false)
    })

    it('toggles fullscreen preview', async () => {
      expect(wrapper.vm.isFullScreen).toBe(false)

      wrapper.vm.toggleFullScreen()
      expect(wrapper.vm.isFullScreen).toBe(true)

      wrapper.vm.toggleFullScreen()
      expect(wrapper.vm.isFullScreen).toBe(false)
    })
  })

  describe('History Management', () => {
    it('tracks changes for undo/redo', () => {
      const initialState = JSON.stringify(wrapper.vm.customizationData)

      wrapper.vm.updateBrandColor('primary', '#ff0000')

      expect(wrapper.vm.canUndo).toBe(true)
      expect(wrapper.vm.undoStack.length).toBe(1)

      wrapper.vm.undo()

      expect(JSON.stringify(wrapper.vm.customizationData)).toBe(initialState)
      expect(wrapper.vm.canRedo).toBe(true)
    })

    it('prevents undo when no history exists', () => {
      expect(wrapper.vm.canUndo).toBe(false)

      wrapper.vm.undo() // Should not throw
      expect(wrapper.vm.canUndo).toBe(false)
    })

    it('clears undo stack on discard changes', () => {
      wrapper.vm.updateBrandColor('primary', '#ff0000')
      expect(wrapper.vm.undoStack.length).toBe(1)

      // Mock confirm dialog
      global.confirm = vi.fn().mockReturnValue(true)

      wrapper.vm.discardChanges()

      expect(wrapper.vm.undoStack.length).toBe(0)
      expect(wrapper.vm.customizationData).toEqual(mockCustomization)
    })
  })

  describe('Save Functionality', () => {
    it('shows Save Changes button when there are unsaved changes', async () => {
      wrapper.vm.updateBrandColor('primary', '#ff0000')

      const saveButton = wrapper.find('.action-btn--primary')
      expect(saveButton.text()).toContain('Save Changes')
      expect(saveButton.attributes('disabled')).toBeUndefined()
    })

    it('disables Save Changes button when no unsaved changes', () => {
      const saveButton = wrapper.find('.action-btn--primary')
      expect(saveButton.attributes('disabled')).toBeDefined()
    })

    it('emits saved event on successful save', async () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')

      wrapper.vm.updateBrandColor('primary', '#ff0000')
      await wrapper.vm.saveChanges()

      expect(emit).toHaveBeenCalledWith('saved', wrapper.vm.customizationData)
      expect(wrapper.vm.undoStack.length).toBe(0)
      expect(wrapper.vm.redoStack.length).toBe(0)
    })

    it('shows loading state during save', async () => {
      wrapper.vm.updateBrandColor('primary', '#ff0000')
      wrapper.vm.isLoading = true

      const saveButton = wrapper.find('.action-btn--primary')
      expect(saveButton.text()).toContain('Save Changes') // Button text doesn't change
    })
  })

  describe('Panel Navigation', () => {
    const panels = ['brand', 'content', 'settings']

    it.each(panels)('activates %s panel when clicked', async (panel) => {
      const panelButton = wrapper.find(`[data-testid="panel-${panel}"]`)
      await panelButton.trigger('click')

      expect(wrapper.vm.activePanel).toBe(panel)
    })

    it('shows brand panel by default', () => {
      expect(wrapper.vm.activePanel).toBe('brand')
    })

    it('toggles sidebar visibility', () => {
      expect(wrapper.vm.sidebarCollapsed).toBe(false)

      wrapper.vm.toggleSidebar()
      expect(wrapper.vm.sidebarCollapsed).toBe(true)

      wrapper.vm.toggleSidebar()
      expect(wrapper.vm.sidebarCollapsed).toBe(false)
    })
  })

  describe('Keyboard Shortcuts', () => {
    beforeEach(() => {
      wrapper.vm.showKeyboardShortcuts = false
    })

    it('opens shortcuts dialog with Ctrl+K', async () => {
      const event = new KeyboardEvent('keydown', {
        ctrlKey: true,
        key: 'k'
      })
      document.dispatchEvent(event)

      expect(wrapper.vm.showKeyboardShortcuts).toBe(true)
    })

    it('closes shortcuts dialog with Escape', async () => {
      wrapper.vm.showKeyboardShortcuts = true

      const event = new KeyboardEvent('keydown', { key: 'Escape' })
      document.dispatchEvent(event)

      expect(wrapper.vm.showKeyboardShortcuts).toBe(false)
    })

    it('saves changes with Ctrl+S', () => {
      const mockSave = vi.spyOn(wrapper.vm, 'saveChanges')
      wrapper.vm.updateBrandColor('primary', '#ff0000')

      const event = new KeyboardEvent('keydown', {
        ctrlKey: true,
        key: 's'
      })
      document.dispatchEvent(event)

      expect(mockSave).toHaveBeenCalled()
    })
  })

  describe('Accessibility', () => {
    it('includes proper ARIA labels for all interactive elements', () => {
      const panelButtons = wrapper.findAll('[role="button"]')
      expect(panelButtons.length).toBeGreaterThan(0)

      panelButtons.forEach(button => {
        expect(button.attributes('aria-label')).toBeTruthy()
      })
    })

    it('indicates pressed state for toggle buttons', () => {
      const brandPanel = wrapper.find('[data-testid="panel-brand"]')

      expect(brandPanel.attributes()['aria-pressed']).toBe('true')
    })

    it('provides feedback for disabled states', () => {
      const saveButton = wrapper.find('.action-btn--primary')
      const undoButton = wrapper.find('[data-testid="undo-btn"]')

      expect(saveButton.attributes()['aria-label']).toBeTruthy()
      expect(undoButton.attributes()['aria-disabled']).toBe('true')
    })

    it('announces loading states', () => {
      wrapper.vm.previewLoading = true

      const loadingElement = wrapper.find('[aria-label*="loading"]')
      expect(loadingElement.exists()).toBe(true)
    })
  })

  describe('Error Handling', () => {
    it('handles template service errors gracefully', async () => {
      const { templateService } = await import('@/services/TemplateService')
      templateService.generatePreview.mockRejectedValue(new Error('Service unavailable'))

      await wrapper.vm.generatePreview()

      expect(wrapper.vm.previewError).toBe('Preview generation failed')
      expect(wrapper.vm.previewLoading).toBe(false)
    })

    it('prevents undefined template access', () => {
      wrapper = mount(TemplateCustomizer, {
        global: {
          plugins: [createTestingPinia()]
        },
        props: {
          modelValue: mockCustomization,
          template: null
        }
      })

      expect(wrapper.vm.template).toBe(null)
      expect(wrapper.find('.template-title').text()).toBe('Custom Template')
    })

    it('validates customization data structure', () => {
      const invalidCustomization = {
        templateId: 1,
        brand: null, // Invalid
        content: null // Invalid
      }

      expect(() => {
        wrapper.setProps({ modelValue: invalidCustomization })
      }).toThrow()
    })
  })

  describe('Lifecycle Hooks', () => {
    it('sets up keyboard event listeners on mount', () => {
      const addEventListenerSpy = vi.spyOn(document, 'addEventListener')

      // Re-mount component to test lifecycle
      wrapper.unmount()
      wrapper = mount(TemplateCustomizer, {
        global: {
          plugins: [createTestingPinia()]
        },
        props: {
          modelValue: mockCustomization,
          template: mockTemplate
        }
      })

      expect(addEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function))
    })

    it('cleans up event listeners on unmount', () => {
      const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener')

      wrapper.unmount()

      expect(removeEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function))
    })

    it('watches for prop changes', async () => {
      const newCustomization = {
        ...mockCustomization,
        brand: {
          ...mockCustomization.brand,
          name: 'Updated Brand'
        }
      }

      await wrapper.setProps({ modelValue: newCustomization })

      expect(wrapper.vm.customizationData.brand.name).toBe('Updated Brand')
    })
  })
})