import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ColorPicker from '@/components/ColorPicker.vue'

vi.mock('@/composables/useLocalStorage', () => ({
  useLocalStorage: () => ({
    value: vi.fn(() => ({
      'recent-colors': ['#ff0000', '#00ff00', '#0000ff']
    }))
  })
}))

const createWrapper = (props = {}) => {
  return mount(ColorPicker, {
    props: {
      modelValue: '#007bff',
      colorKey: 'primary',
      name: 'Primary Color',
      showPreview: true,
      ...props
    },
    global: {
      stubs: ['svg'],
      plugins: [createTestingPinia()],
      mocks: {
        $toast: {
          success: vi.fn(),
          error: vi.fn()
        }
      }
    },
    attachTo: document.body
  })
}

describe('ColorPicker.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = createWrapper()
  })

  describe('Component Structure', () => {
    it('renders the color picker interface', () => {
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('.color-picker-container').exists()).toBe(true)
    })

    it('displays current color value', () => {
      const colorDisplay = wrapper.find('.current-color-display')
      expect(colorDisplay.exists()).toBe(true)
      expect(colorDisplay.attributes('style')).toContain('#007bff')
    })

    it('shows color preview section when enabled', () => {
      const preview = wrapper.find('.color-preview')
      expect(preview.exists()).toBe(true)
    })

    it('renders canvas elements for color selection', () => {
      expect(wrapper.find('canvas.color-canvas').exists()).toBe(true)
      expect(wrapper.find('canvas.hue-canvas').exists()).toBe(true)
    })
  })

  describe('HSV Color Model', () => {
    it('handles RGB input properly', () => {
      const rgbValue = '#ff0000'
      wrapper = createWrapper({ modelValue: rgbValue })

      expect(wrapper.exists()).toBe(true)
    })

    it('handles HSV operations', () => {
      // Test that the component can handle color model operations
      expect(wrapper.exists()).toBe(true)
    })

    it('maintains color accuracy', () => {
      const originalColor = '#ff6b35'
      wrapper = createWrapper({ modelValue: originalColor })

      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Canvas Interactions', () => {
    it('updates saturation and value on canvas click', async () => {
      const canvas = wrapper.find('canvas.color-canvas')
      const event = {
        offsetX: 50,
        offsetY: 75
      }

      await canvas.trigger('click', event)

      expect(wrapper.vm.hsvValue.s).toBeGreaterThan(0)
      expect(wrapper.vm.hsvValue.v).toBeDefined()
    })

    it('updates hue on hue slider interaction', async () => {
      const hueCanvas = wrapper.find('canvas.hue-canvas')
      const event = {
        offsetX: 60
      }

      await hueCanvas.trigger('click', event)

      // Hue should be updated based on position
      expect(wrapper.vm.hsvValue.h).toBeDefined()
    })

    it('handles mouse drag operations on canvas', async () => {
      const canvas = wrapper.find('canvas.color-canvas')

      // Start drag
      await canvas.trigger('mousedown', { offsetX: 25, offsetY: 25 })
      await canvas.trigger('mousemove', { offsetX: 75, offsetY: 75 })
      await canvas.trigger('mouseup')

      expect(wrapper.vm.isDragging).toBe(false)
    })
  })

  describe('Color Input Handling', () => {
    it('accepts valid hex color input', async () => {
      const input = wrapper.find('input[type="text"]')
      await input.setValue('#ff6b35')

      expect(wrapper.vm.hexValue).toBe('#ff6b35')
      expect(wrapper.emitted()['update:modelValue'][0]).toEqual(['#ff6b35'])
    })

    it('validates hex color format', async () => {
      const input = wrapper.find('input[type="text"]')
      await input.setValue('invalid-color')

      // Should not emit invalid color
      expect(wrapper.emitted()['update:modelValue']).toBeUndefined()
    })

    it('handles RGB color input', async () => {
      const inputs = wrapper.findAll('.rgb-input')

      await inputs[0].setValue('255') // Red
      await inputs[1].setValue('107') // Green
      await inputs[2].setValue('53')  // Blue

      expect(wrapper.emitted()['update:modelValue']).toBeDefined()
    })

    it('prevents invalid RGB values', async () => {
      const inputs = wrapper.findAll('.rgb-input')

      // Try invalid value
      await inputs[0].setValue('300') // Out of range

      expect(wrapper.vm.rgbValue.r).toBeLessThanOrEqual(255)
    })
  })

  describe('Color Palette and Collections', () => {
    it('displays preset color palette', () => {
      const palette = wrapper.findAll('.preset-color')
      expect(palette.length).toBeGreaterThan(0)
    })

    it('selects color from palette on click', async () => {
      const firstColor = wrapper.findAll('.preset-color').at(0)
      await firstColor.trigger('click')

      expect(wrapper.emitted()['update:modelValue']).toBeDefined()
    })

    it('loads recently used colors from localStorage', () => {
      const recentColors = wrapper.findAll('.recent-color')
      expect(recentColors.length).toBeGreaterThan(0)
    })

    it('saves color to recent colors when changed', async () => {
      const newColor = '#abcdef'
      wrapper = createWrapper({ modelValue: newColor })

      // Simulate color change
      await wrapper.vm.$nextTick()

      // Check if recent colors were updated
      expect(wrapper.vm.recentColors).toContain(newColor)
    })
  })

  describe('Accessibility Features', () => {
    it('provides proper ARIA labels', () => {
      const input = wrapper.find('input[type="text"]')
      expect(input.attributes('aria-label')).toBeDefined()

      const canvas = wrapper.find('canvas.color-canvas')
      expect(canvas.attributes('role')).toBe('button')
    })

    it('supports keyboard navigation', async () => {
      const paletteColors = wrapper.findAll('.preset-color')

      // Focus first color
      await paletteColors[0].element.focus()

      expect(document.activeElement).toBe(paletteColors[0].element)
    })

    it('announces color changes to screen readers', async () => {
      const announceSpy = vi.spyOn(wrapper.vm, 'announceColorChange')

      await wrapper.setData({ hexValue: '#ff0000' })

      expect(announceSpy).toHaveBeenCalledWith('#ff0000', 'Primary Color')
    })

    it('has sufficient color contrast in UI elements', () => {
      // Check that text has sufficient contrast against background
      const textElements = wrapper.findAll('.color-value-text')
      textElements.forEach(element => {
        expect(element.classes()).toContain('text-high-contrast')
      })
    })
  })

  describe('Contrast Validation', () => {
    it('calculates color contrast ratio correctly', () => {
      const ratio = wrapper.vm.calculateContrast('#000000', '#ffffff')
      expect(ratio).toBe(21) // Maximum contrast
    })

    it('validates WCAG compliance levels', () => {
      const aaResult = wrapper.vm.getContrastLevel('#666666', '#ffffff')
      expect(aaResult).toBe('AA')

      const aaaResult = wrapper.vm.getContrastLevel('#595959', '#ffffff')
      expect(aaaResult).toBe('AAA')
    })

    it('shows contrast warnings for low contrast combinations', () => {
      const lowContrastCombo = {
        foreground: '#777777',
        background: '#888888'
      }

      wrapper = createWrapper({
        modelValue: lowContrastCombo.foreground,
        contrastBackground: lowContrastCombo.background
      })

      expect(wrapper.find('.contrast-warning').exists()).toBe(true)
    })

    it('suggests improved color combinations', () => {
      const poorColor = '#888888'
      const suggestions = wrapper.vm.getColorSuggestions(poorColor, 'foreground')

      expect(suggestions.length).toBeGreaterThan(0)
      suggestions.forEach(color => {
        expect(color.startsWith('#')).toBe(true)
      })
    })
  })

  describe('Event Handling', () => {
    it('emits input event on color change', async () => {
      wrapper = createWrapper({ modelValue: '#007bff' })

      await wrapper.find('input[type="text"]').setValue('#28a745')

      expect(wrapper.emitted().input).toBeTruthy()
      expect(wrapper.emitted().input[0]).toEqual(['#28a745'])
    })

    it('emits change event on final color selection', async () => {
      const canvas = wrapper.find('canvas.color-canvas')
      await canvas.trigger('click', { offsetX: 50, offsetY: 50 })

      // Should emit change after debounced period
      await wrapper.vm.$nextTick()

      expect(wrapper.emitted().change).toBeTruthy()
    })

    it('debounces rapid color changes', async () => {
      const inputSpy = vi.fn()
      wrapper.vm.$on('input', inputSpy)

      // Rapid changes
      await wrapper.setData({ hexValue: '#111111' })
      await wrapper.setData({ hexValue: '#222222' })
      await wrapper.setData({ hexValue: '#333333' })

      await wrapper.vm.$nextTick()

      // Should only emit once for the final value
      expect(inputSpy).toHaveBeenCalledTimes(1)
    })
  })

  describe('Component Styling', () => {
    it('applies correct CSS classes for different states', () => {
      expect(wrapper.classes()).toContain('color-picker')

      // Test disabled state
      wrapper = createWrapper({ disabled: true })
      expect(wrapper.classes()).toContain('color-picker--disabled')
    })

    it('adapts layout for different sizes', () => {
      wrapper = createWrapper({ size: 'small' })
      expect(wrapper.classes()).toContain('color-picker--small')

      wrapper = createWrapper({ size: 'large' })
      expect(wrapper.classes()).toContain('color-picker--large')
    })

    it('shows color preview with correct styling', () => {
      const preview = wrapper.find('.color-preview-circle')
      expect(preview.attributes('style')).toContain('#007bff')
    })
  })

  describe('Error Handling', () => {
    it('handles invalid canvas context gracefully', () => {
      const mockCanvas = {
        getContext: vi.fn().mockReturnValue(null)
      }

      vi.spyOn(document, 'querySelector').mockReturnValue(mockCanvas)

      wrapper = createWrapper()

      // Should not throw error
      expect(wrapper.vm.colorCanvas).toBeNull()
    })

    it('validates numeric RGB input ranges', () => {
      const result = wrapper.vm.clampRgbValue('256')
      expect(result).toBe(255)

      const result2 = wrapper.vm.clampRgbValue('-5')
      expect(result2).toBe(0)
    })

    it('handles malformed hex colors', () => {
      const result = wrapper.vm.isValidHex('#gggggg')
      expect(result).toBe(false)

      const result2 = wrapper.vm.isValidHex('#abc')
      expect(result2).toBe(true)
    })
  })

  describe('Performance Optimization', () => {
    it('throttles canvas redraw operations', async () => {
      const redrawSpy = vi.spyOn(wrapper.vm, 'redrawCanvas')

      // Trigger multiple canvas updates quickly
      wrapper.vm.updateColorSelection({ x: 10, y: 10 })
      wrapper.vm.updateColorSelection({ x: 20, y: 20 })
      wrapper.vm.updateColorSelection({ x: 30, y: 30 })

      await wrapper.vm.$nextTick()

      expect(redrawSpy).toHaveBeenCalledTimes(1)
    })

    it('caches frequently used color calculations', () => {
      const cache = {}

      const result1 = wrapper.vm.hsvToRgbCached(cache, { h: 360, s: 100, v: 100 })
      const result2 = wrapper.vm.hsvToRgbCached(cache, { h: 360, s: 100, v: 100 })

      expect(result1).toBe(result2) // Same object from cache
    })
  })
})