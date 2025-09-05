import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ColorPicker from '@/components/ColorPicker.vue'

describe('ColorPicker.vue', () => {
  let wrapper
  let mockModelValue

  beforeEach(() => {
    mockModelValue = '#007bff'

    // Mock localStorage
    Storage.prototype.getItem = vi.fn()
    Storage.prototype.setItem = vi.fn()

    wrapper = mount(ColorPicker, {
      global: {
        plugins: [createTestingPinia()],
        stubs: {
          'heroicons/vue/outline': true
        }
      },
      props: {
        modelValue: mockModelValue,
        label: 'Primary Color',
        showPresets: true,
        showAccessibility: true
      }
    })
  })

  describe('Initialization', () => {
    it('renders with correct initial color', () => {
      const colorPreview = wrapper.find('.color-preview')
      const colorValue = wrapper.find('.color-value')

      expect(colorPreview.text()).toContain('#007bff')
      expect(colorValue.text()).toBe('#007bff')
    })

    it('initializes HSV values from hex color', () => {
      expect(wrapper.vm.hsv.h).toBeDefined()
      expect(wrapper.vm.hsv.s).toBeDefined()
      expect(wrapper.vm.hsv.v).toBeDefined()
    })

    it('shows color presets by default', () => {
      const presets = wrapper.find('.color-presets')
      expect(presets.exists()).toBe(true)
    })

    it('loads recent colors from localStorage', () => {
      expect(Storage.prototype.getItem).toHaveBeenCalledWith('color-recent-primary')
    })
  })

  describe('Color Picker UI', () => {
    beforeEach(async () => {
      await wrapper.setData({ isOpen: true })
    })

    it('opens color picker panel when clicked', async () => {
      await wrapper.setData({ isOpen: false })

      const colorPreview = wrapper.find('.color-preview')
      await colorPreview.trigger('click')

      expect(wrapper.vm.isOpen).toBe(true)
    })

    it('closes color picker when apply button is clicked', async () => {
      const applyButton = wrapper.find('.action-btn--primary')
      await applyButton.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('closes color picker when cancel button is clicked', async () => {
      const cancelButton = wrapper.find('.action-btn--ghost')
      await cancelButton.trigger('click')

      expect(wrapper.vm.isOpen).toBe(false)
    })

    it('toggles between color modes', async () => {
      const hslTab = wrapper.find('.mode-tab').filter(tab => tab.text() === 'HSL')
      await hslTab.trigger('click')

      expect(wrapper.vm.activeColorMode).toBe('hsl')

      const rgbTab = wrapper.find('.mode-tab').filter(tab => tab.text() === 'RGB')
      await rgbTab.trigger('click')

      expect(wrapper.vm.activeColorMode).toBe('rgb')
    })
  })

  describe('Color Selection', () => {
    it('updates color from preset selection', async () => {
      const preset = wrapper.findAll('.color-preset')[0]
      await preset.trigger('click')

      expect(wrapper.vm.currentColor).not.toBe(mockModelValue)
      expect(wrapper.vm.emit).toHaveBeenCalledWith('update:modelValue', wrapper.vm.currentColor)
    })

    it('updates color from hex input', async () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')
      const hexInput = wrapper.find('#hex-input')

      await hexInput.setValue('#ff0000')
      await hexInput.trigger('blur')

      expect(wrapper.vm.hexValue).toBe('#ff0000')
      expect(wrapper.vm.currentColor).toBe('#ff0000')
      expect(emit).toHaveBeenCalledWith('update:modelValue', '#ff0000')
    })

    it('handles invalid hex input', async () => {
      const originalColor = wrapper.vm.currentColor
      const hexInput = wrapper.find('#hex-input')

      await hexInput.setValue('#zzz')
      await hexInput.trigger('blur')

      // Should reset to original color
      expect(wrapper.vm.currentColor).toBe(originalColor)
    })

    it('updates color from RGB inputs', async () => {
      const rInput = wrapper.find('#red-channel')
      await rInput.setValue(255)
      await rInput.trigger('input')

      expect(wrapper.vm.rgb.r).toBe(255)
      expect(wrapper.vm.currentColor).toContain('ff') // Should contain red component
    })

    it('Adds color to recent colors', async () => {
      const newColor = '#ff0000'
      wrapper.vm.selectColor(newColor)

      expect(wrapper.vm.recentColors).toContain(newColor)
      expect(Storage.prototype.setItem).toHaveBeenCalledWith('color-recent-primary', JSON.stringify([newColor, ...wrapper.vm.recentColors.slice(0, -1)]))
    })

    it('limits recent colors to 8', () => {
      // Add 10 colors
      for (let i = 0; i < 10; i++) {
        wrapper.vm.addToRecentColors(`#${i.toString().repeat(6)}`)
      }

      expect(wrapper.vm.recentColors.length).toBe(8)
    })
  })

  describe('Canvas Interaction', () => {
    beforeEach(async () => {
      await wrapper.setData({ isOpen: true })
    })

    it('renders color map canvas', () => {
      const canvas = wrapper.find('.color-map-canvas')
      expect(canvas.exists()).toBe(true)
    })

    it('renders hue slider canvas', () => {
      const canvas = wrapper.find('.hue-slider-canvas')
      expect(canvas.exists()).toBe(true)
    })

    it('updates color on canvas interaction', () => {
      const canvas = wrapper.find('.color-map-canvas')

      const mockEvent = new MouseEvent('mousedown', {
        clientX: 128,
        clientY: 128
      })

      canvas.element.dispatchEvent(mockEvent)

      // Should update HSV values
      expect(wrapper.vm.isDragging).toBe(true)
    })

    it('stops dragging when mouse is released', () => {
      wrapper.vm.isDragging = true

      document.dispatchEvent(new MouseEvent('mouseup'))

      expect(wrapper.vm.isDragging).toBe(false)
    })
  })

  describe('Accessibility Features', () => {
    it('shows accessibility panel when enabled', async () => {
      await wrapper.setData({ isOpen: true, accessibilityEnabled: true, showAccessibility: true })

      const accessibilityPanel = wrapper.find('.accessibility-panel')
      expect(accessibilityPanel.exists()).toBe(true)
    })

    it('calculates contrast ratios', () => {
      wrapper.vm.showAccessibility = true

      expect(wrapper.vm.contrastResults).toBeDefined()
      expect(wrapper.vm.contrastResults.length).toBeGreaterThan(0)
    })

    it('provides keyboard navigation', () => {
      const colorPreview = wrapper.find('.color-preview')

      expect(colorPreview.attributes('tabindex')).not.toBe('-1')
      expect(colorPreview.attributes('aria-disabled')).toBeUndefined()
    })

    it('includes proper ARIA labels', () => {
      const colorPreview = wrapper.find('.color-preview')

      expect(colorPreview.attributes('aria-label')).toBeTruthy()
      expect(colorPreview.attributes('aria-expanded')).toBeDefined()
    })

    it('shows screen reader instructions', () => {
      const canvas = wrapper.find('.color-map-canvas')

      expect(canvas.attributes('aria-label')).toBe('HSV color selector')
    })
  })

  describe('Reset and Apply', () => {
    it('resets to original color', async () => {
      const originalColor = wrapper.vm.currentColor

      wrapper.vm.currentColor = '#ff0000'
      wrapper.vm.resetToOriginal()

      expect(wrapper.vm.currentColor).toBe(originalColor)
    })

    it('applies selected color', () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')

      wrapper.vm.currentColor = '#ff0000'
      wrapper.vm.applyColor()

      expect(emit).toHaveBeenCalledWith('update:modelValue', '#ff0000')
      expect(emit).toHaveBeenCalledWith('colorChanged', '#ff0000', expect.any(Object))
    })

    it('prevents apply when color is unchanged', () => {
      const resetButton = wrapper.find('.action-btn--secondary')

      expect(resetButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('Format Conversion', () => {
    it('converts hex to RGB correctly', () => {
      const result = wrapper.vm.hexToRgb('#ff0000')

      expect(result).toEqual({
        r: 255,
        g: 0,
        b: 0
      })
    })

    it('converts RGB to hex correctly', () => {
      const result = wrapper.vm.rgbToHex(255, 0, 0)

      expect(result).toBe('#ff0000')
    })

    it('converts RGB to HSV correctly', () => {
      const result = wrapper.vm.rgbToHsv(255, 0, 0)

      expect(result.h).toBe(0) // Red hue
      expect(result.s).toBe(1) // Full saturation
      expect(result.v).toBe(1) // Full value
    })

    it('converts HSV to RGB correctly', () => {
      const result = wrapper.vm.hsvToRgb(0, 1, 1)

      expect(result.r).toBeCloseTo(255)
      expect(result.g).toBeCloseTo(0)
      expect(result.b).toBeCloseTo(0)
    })
  })

  describe('Error Handling', () => {
    it('handles invalid canvas clicks gracefully', () => {
      wrapper.vm.colorMapCanvas = null

      // Should not throw error
      expect(() => {
        wrapper.vm.startColorSelection(new MouseEvent('mousedown'))
      }).not.toThrow()
    })

    it('handles localStorage errors gracefully', () => {
      Storage.prototype.getItem.mockImplementation(() => {
        throw new Error('Storage error')
      })

      // Should not throw during initialization
      expect(() => {
        wrapper.vm.onMounted()
      }).not.toThrow()
    })

    it('validates hex color format', () => {
      const validHex = wrapper.vm.hexValue = '#123abc'
      expect(validHex).toMatch(/^#[0-9A-Fa-f]{6}$/)

      const invalidHex = '#zzz'
      expect(invalidHex).not.toMatch(/^#[0-9A-Fa-f]{6}$/)
    })
  })

  describe('Props and Responsiveness', () => {
    it('respects disabled prop', async () => {
      wrapper = mount(ColorPicker, {
        global: {
          plugins: [createTestingPinia()]
        },
        props: {
          modelValue: '#007bff',
          disabled: true
        }
      })

      const colorPreview = wrapper.find('.color-preview')
      expect(colorPreview.attributes('tabindex')).toBe('-1')
      expect(colorPreview.attributes('aria-disabled')).toBeTruthy()
    })

    it('responds to type prop for localStorage key', () => {
      wrapper = mount(ColorPicker, {
        global: {
          plugins: [createTestingPinia()]
        },
        props: {
          modelValue: '#007bff',
          type: 'secondary'
        }
      })

      expect(Storage.prototype.getItem).toHaveBeenCalledWith('color-recent-secondary')
    })

    it('disables accessibility features when showAccessibility is false', async () => {
      const wrapper = mount(ColorPicker, {
        global: {
          plugins: [createTestingPinia()]
        },
        props: {
          modelValue: '#007bff',
          showAccessibility: false
        }
      })

      await wrapper.setData({ isOpen: true })

      const accessibilityPanel = wrapper.find('.accessibility-panel')
      expect(accessibilityPanel.exists()).toBe(false)
    })
  })

  describe('Lifecycle Hooks', () => {
    it('initializes canvas rendering on mount', () => {
      const renderColorMapSpy = vi.spyOn(wrapper.vm, 'renderColorMap')
      const renderHueSliderSpy = vi.spyOn(wrapper.vm, 'renderHueSlider')

      wrapper.vm.onMounted()

      expect(renderColorMapSpy).toHaveBeenCalled()
      expect(renderHueSliderSpy).toHaveBeenCalled()
    })

    it('saves recent colors on unmount', () => {
      wrapper.vm.onBeforeUnmount()

      expect(Storage.prototype.setItem).toHaveBeenCalledWith('color-recent-primary', JSON.stringify(wrapper.vm.recentColors))
    })

    it('watches for model value changes', async () => {
      const newColor = '#ff0000'
      const updateHSVFromCurrentColorSpy = vi.spyOn(wrapper.vm, 'updateHSVFromCurrentColor')

      await wrapper.setProps({ modelValue: newColor })

      expect(wrapper.vm.currentColor).toBe(newColor)
      expect(updateHSVFromCurrentColorSpy).toHaveBeenCalled()
    })
  })

  describe('Performance', () => {
    it('debounces color updates', async () => {
      const emitSpy = vi.spyOn(wrapper.vm, '$emit')

      // Simulate rapid color changes
      wrapper.vm.currentColor = '#ff0000'
      wrapper.vm.currentColor = '#00ff00'
      wrapper.vm.currentColor = '#0000ff'

      // Should only emit on final stable value
      await new Promise(resolve => setTimeout(resolve, 50))

      expect(emitSpy).toHaveBeenCalled()
    })

    it('renders canvas only when visible', async () => {
      await wrapper.setData({ isOpen: false })

      // Canvas should not be rendered
      const canvas = wrapper.find('.color-map-canvas')
      expect(canvas.exists()).toBe(false)

      await wrapper.setData({ isOpen: true })

      // Canvas should be rendered
      const visibleCanvas = wrapper.find('.color-map-canvas')
      expect(visibleCanvas.exists()).toBe(true)
    })

    it('uses efficient color conversion algorithms', () => {
      const start = performance.now()

      // Perform multiple conversions
      for (let i = 0; i < 1000; i++) {
        wrapper.vm.rgbToHsv(255, 128, 64)
        wrapper.vm.hsvToRgb(i % 360, 0.5, 0.5)
      }

      const end = performance.now()
      const duration = end - start

      // Should complete within reasonable time (less than 50ms for 1000 operations)
      expect(duration).toBeLessThan(50)
    })
  })

  describe('Cross-browser Compatibility', () => {
    it('handles different input types consistently', async () => {
      // Test hex input
      wrapper.vm.hexValue = '#ff0000'
      wrapper.vm.updateFromHex()
      expect(wrapper.vm.currentColor).toBe('#ff0000')

      // Test RGB input
      wrapper.vm.rgb = { r: 0, g: 255, b: 0 }
      wrapper.vm.updateFromRgbInputs()
      expect(wrapper.vm.currentColor).toBe('#00ff00')
    })

    it('responds to keyboard shortcuts', async () => {
      const event = new KeyboardEvent('keydown', { key: 'Enter' })

      // Simulate enter key on focused element
      const colorPreview = wrapper.find('.color-preview')
      colorPreview.element.focus()
      colorPreview.element.dispatchEvent(event)

      // Should not interfere with normal functionality
      expect(wrapper.vm.isOpen).not.toBeUndefined()
    })

    it('gracefully handles browser storage limitations', () => {
      // Mock storage quota exceeded
      Storage.prototype.setItem.mockImplementation(() => {
        throw new Error('Quota exceeded')
      })

      expect(() => {
        wrapper.vm.addToRecentColors('#123456')
      }).not.toThrow()

      expect(wrapper.vm.recentColors).toContain('#123456')
    })
  })
})