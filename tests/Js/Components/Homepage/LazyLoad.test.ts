import { describe, it, expect, vi, beforeEach, afterEach, beforeAll, afterAll } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import LazyLoad from '@/components/LazyLoad.vue'

// Store the global IntersectionObserver to restore it later
let originalIntersectionObserver: typeof IntersectionObserver | undefined

beforeAll(() => {
  originalIntersectionObserver = global.IntersectionObserver
})

afterAll(() => {
  global.IntersectionObserver = originalIntersectionObserver
})

describe('LazyLoad.vue', () => {
  let wrapper: VueWrapper<any>
  let mockIntersectionObserver: any
  let mockObserverInstance: any

  beforeEach(() => {
    vi.clearAllMocks()

    // Create a more detailed mock for IntersectionObserver
    mockObserverInstance = {
      observe: vi.fn(),
      unobserve: vi.fn(),
      disconnect: vi.fn(),
    }

    mockIntersectionObserver = vi.fn().mockImplementation((callback, options) => {
      mockObserverInstance.callback = callback
      mockObserverInstance.options = options
      return mockObserverInstance
    })

    global.IntersectionObserver = mockIntersectionObserver
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  const createMockEntry = (isIntersecting: boolean, intersectionRatio: number = 1) => ({
    target: null,
    isIntersecting,
    intersectionRatio,
    intersectionRect: { top: 0, left: 0, bottom: 100, right: 100 },
    boundingClientRect: { top: 0, left: 0, bottom: 100, right: 100 },
    rootBounds: null,
    time: Date.now(),
  })

  describe('Basic Rendering', () => {
    it('renders with default props', () => {
      wrapper = mount(LazyLoad, {
        slots: {
          skeleton: 'Loading...',
          default: 'Content loaded',
        },
      })

      expect(wrapper.find('.lazy-load-container').exists()).toBe(true)
      expect(wrapper.text()).toContain('Loading...')
      expect(wrapper.text()).not.toContain('Content loaded')
    })

    it('passes props to slots via scoped props', () => {
      wrapper = mount(LazyLoad, {
        slots: {
          default: (props: any) => `Visible: ${props.isVisible}, Has been visible: ${props.hasBeenVisible}`,
        },
      })

      expect(wrapper.text()).toContain('Visible: false, Has been visible: false')
    })

    it('renders skeleton slot when not visible', () => {
      wrapper = mount(LazyLoad, {
        slots: {
          skeleton: '<div>Loading skeleton</div>',
          'skeleton-content': '<div>Nested skeleton</div>',
          default: '<div>Actual content</div>',
        },
      })

      expect(wrapper.text()).toContain('Loading skeleton')
      expect(wrapper.text()).toContain('Nested skeleton')
      expect(wrapper.text()).not.toContain('Actual content')
    })
  })

  describe('Intersection Observer Behavior', () => {
    it('creates IntersectionObserver on mount', () => {
      wrapper = mount(LazyLoad)

      expect(mockIntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        expect.objectContaining({
          rootMargin: '50px',
          threshold: 0.1,
        })
      )
    })

    it('uses custom props for IntersectionObserver options', () => {
      wrapper = mount(LazyLoad, {
        props: {
          rootMargin: '100px',
          threshold: [0.3, 0.6],
        },
      })

      expect(mockIntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        expect.objectContaining({
          rootMargin: '100px',
          threshold: [0.3, 0.6],
        })
      )
    })

    it('observes the container element', () => {
      wrapper = mount(LazyLoad)

      expect(mockObserverInstance.observe).toHaveBeenCalled()
      const observedElement = mockObserverInstance.observe.mock.calls[0][0]
      expect(observedElement).toBeInstanceOf(HTMLElement)
    })

    it('shows content when element intersects', async () => {
      wrapper = mount(LazyLoad, {
        slots: {
          skeleton: 'Loading...',
          default: 'Content loaded',
        },
      })

      // Trigger intersection
      const mockEntry = createMockEntry(true)
      mockObserverInstance.callback([mockEntry])

      await nextTick()

      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)
      expect(wrapper.text()).toContain('Content loaded')
      expect(wrapper.text()).not.toContain('Loading...')
    })

    it('stays hidden when element does not intersect', async () => {
      wrapper = mount(LazyLoad, {
        slots: {
          skeleton: 'Loading...',
          default: 'Content loaded',
        },
      })

      // Trigger non-intersection
      const mockEntry = createMockEntry(false)
      mockObserverInstance.callback([mockEntry])

      await nextTick()

      expect(wrapper.find('.lazy-load-container.hidden').exists()).toBe(true)
      expect(wrapper.text()).toContain('Loading...')
      expect(wrapper.text()).not.toContain('Content loaded')
    })

    it('emits visible event when element becomes visible', async () => {
      wrapper = mount(LazyLoad)

      const mockEntry = createMockEntry(true, 0.5)
      mockObserverInstance.callback([mockEntry])

      await nextTick()

      const emittedEvents = wrapper.emitted('visible')
      expect(emittedEvents).toHaveLength(1)
      expect(emittedEvents![0][0]).toBeInstanceOf(HTMLElement)
    })

    it('emits hidden event when element becomes hidden (without triggerOnce)', async () => {
      wrapper = mount(LazyLoad, {
        props: { triggerOnce: false },
      })

      // First make it visible
      const visibleEntry = createMockEntry(true)
      mockObserverInstance.callback([visibleEntry])

      await nextTick()

      // Then make it hidden
      const hiddenEntry = createMockEntry(false)
      mockObserverInstance.callback([hiddenEntry])

      await nextTick()

      const hiddenEvents = wrapper.emitted('hidden')
      expect(hiddenEvents).toHaveLength(1)
    })
  })

  describe('Props Handling', () => {
    it('respects rootElement prop', () => {
      const mockRootElement = document.createElement('div')
      wrapper = mount(LazyLoad, {
        props: { rootElement: mockRootElement },
      })

      expect(mockIntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        expect.objectContaining({
          root: mockRootElement,
        })
      )
    })

    it('handles disabled prop correctly', () => {
      wrapper = mount(LazyLoad, {
        props: { disabled: true },
        slots: {
          default: 'Content loaded',
        },
      })

      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)
      expect(wrapper.text()).toContain('Content loaded')
      expect(mockIntersectionObserver).not.toHaveBeenCalled()
    })

    it('disconnects observer when disabled changes to true', async () => {
      wrapper = mount(LazyLoad, {
        props: { disabled: false },
      })

      expect(mockIntersectionObserver).toHaveBeenCalled()
      expect(mockObserverInstance.disconnect).not.toHaveBeenCalled()

      await wrapper.setProps({ disabled: true })

      expect(mockObserverInstance.disconnect).toHaveBeenCalled()
    })

    it('restarts observer when disabled changes to false', async () => {
      wrapper = mount(LazyLoad, {
        props: { disabled: true },
      })

      // Clear mock calls
      vi.clearAllMocks()

      await wrapper.setProps({ disabled: false })

      expect(mockIntersectionObserver).toHaveBeenCalled()
    })

    it('handles triggerOnce prop correctly', async () => {
      wrapper = mount(LazyLoad, {
        props: { triggerOnce: true },
      })

      // First intersection
      const mockEntry = createMockEntry(true)
      mockObserverInstance.callback([mockEntry])
      await nextTick()

      expect(mockObserverInstance.disconnect).toHaveBeenCalled()
    })

    it('does not disconnect when triggerOnce is false', async () => {
      wrapper = mount(LazyLoad, {
        props: { triggerOnce: false },
      })

      // First intersection
      const mockEntry = createMockEntry(true)
      mockObserverInstance.callback([mockEntry])
      await nextTick()

      expect(mockObserverInstance.disconnect).not.toHaveBeenCalled()
    })
  })

  describe('SSR Compatibility', () => {
    it('falls back when IntersectionObserver is not available', () => {
      // Remove IntersectionObserver from global
      delete (global as any).IntersectionObserver

      wrapper = mount(LazyLoad)

      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)

      // Restore original
      global.IntersectionObserver = mockIntersectionObserver
    })

    it('handles undefined window gracefully', () => {
      // Mock environment without window
      const originalWindow = global.window
      delete (global as any).window
      delete (global as any).IntersectionObserver

      wrapper = mount(LazyLoad)

      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)

      // Restore
      global.window = originalWindow
      global.IntersectionObserver = mockIntersectionObserver
    })
  })

  describe('Exposed Methods', () => {
    it('allows manual triggering via expose', async () => {
      wrapper = mount(LazyLoad, {
        slots: {
          default: 'Manually triggered content',
        },
      })

      // Access exposed method
      const vm = wrapper.vm
      vm.trigger()

      await nextTick()

      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)
      expect(wrapper.text()).toContain('Manually triggered content')
    })

    it('allows manual reset via expose', async () => {
      wrapper = mount(LazyLoad, {
        props: { triggerOnce: false },
        slots: {
          default: 'Reset content',
        },
      })

      // Trigger visibility
      const vm = wrapper.vm
      vm.trigger()
      await nextTick()

      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)

      // Reset
      vm.reset()
      await nextTick()

      expect(wrapper.find('.lazy-load-container.hidden').exists()).toBe(true)
      expect(mockObserverInstance.observe).toHaveBeenCalled()
    })

    it('allows manual destroy via expose', () => {
      wrapper = mount(LazyLoad)

      const vm = wrapper.vm
      vm.destroy()

      expect(mockObserverInstance.disconnect).toHaveBeenCalled()
    })

    it('prevents reset with triggerOnce enabled', async () => {
      wrapper = mount(LazyLoad, {
        props: { triggerOnce: true },
        slots: {
          default: 'Content',
        },
      })

      const vm = wrapper.vm

      // Trigger once
      vm.trigger()
      await nextTick()

      // Try to reset - should not work
      vm.reset()
      await nextTick()

      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)
      expect(mockObserverInstance.observe).not.toHaveBeenCalled()
    })
  })

  describe('Multiple Intersection Triggers', () => {
    it('handles multiple entries in callback correctly', async () => {
      wrapper = mount(LazyLoad)

      const entries = [
        createMockEntry(false),
        createMockEntry(true),
      ]

      mockObserverInstance.callback(entries)
      await nextTick()

      // Should trigger for the intersecting entry
      expect(wrapper.find('.lazy-load-container.visible').exists()).toBe(true)
    })

    it('triggers callback only for intersecting entries', async () => {
      wrapper = mount(LazyLoad)

      const entries = [
        createMockEntry(false),
        createMockEntry(false, 0.5),
      ]

      mockObserverInstance.callback(entries)
      await nextTick()

      expect(wrapper.find('.lazy-load-container.hidden').exists()).toBe(true)
    })
  })

  describe('Lifecycle Management', () => {
    it('cleans up observer on unmount', () => {
      wrapper = mount(LazyLoad)

      wrapper.unmount()

      expect(mockObserverInstance.disconnect).toHaveBeenCalled()
    })

    it('observes after window.IntersectionObserver becomes available', () => {
      // Simulate delayed IntersectionObserver availability
      delete (global as any).IntersectionObserver

      wrapper = mount(LazyLoad)

      // Now add it
      global.IntersectionObserver = mockIntersectionObserver

      // Trigger some logic that might create observer
      // (This tests conditional observer creation)
    })

    it('handles error conditions gracefully', () => {
      // Make observer throw error
      mockObserverInstance.observe.mockImplementation(() => {
        throw new Error('Observer error')
      })

      // Should not crash the component
      wrapper = mount(LazyLoad)

      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Accessibility and Performance', () => {
    it('maintains proper DOM structure through visibility changes', async () => {
      wrapper = mount(LazyLoad, {
        slots: {
          skeleton: '<div role="status">Loading...</div>',
          default: '<div>Main content</div>',
        },
      })

      // Initially skeleton visible
      expect(wrapper.text()).toContain('Loading...')
      expect(wrapper.text()).not.toContain('Main content')

      // After becoming visible
      const mockEntry = createMockEntry(true)
      mockObserverInstance.callback([mockEntry])
      await nextTick()

      expect(wrapper.text()).not.toContain('Loading...')
      expect(wrapper.text()).toContain('Main content')
    })

    it('preserves slot content access in visible state', async () => {
      wrapper = mount(LazyLoad, {
        slots: {
          default: (props: any) => `<div>IsVisible: ${props.isVisible}</div>`,
        },
      })

      // Initially not visible
      expect(wrapper.text()).toContain('IsVisible: false')

      // After becoming visible
      const mockEntry = createMockEntry(true)
      mockObserverInstance.callback([mockEntry])
      await nextTick()

      expect(wrapper.text()).toContain('IsVisible: true')
    })
  })

  describe('Edge Cases', () => {
    it('handles empty slots gracefully', () => {
      wrapper = mount(LazyLoad)

      expect(wrapper.find('.lazy-load-container').exists()).toBe(true)
    })

    it('handles very low threshold values', () => {
      wrapper = mount(LazyLoad, {
        props: { threshold: 0.01 },
      })

      expect(mockIntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        expect.objectContaining({
          threshold: 0.01,
        })
      )
    })

    it('handles array threshold values', () => {
      const thresholds = [0, 0.25, 0.5, 0.75, 1]
      wrapper = mount(LazyLoad, {
        props: { threshold: thresholds },
      })

      expect(mockIntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        expect.objectContaining({
          threshold: thresholds,
        })
      )
    })

    it('handles very high rootMargin values', () => {
      wrapper = mount(LazyLoad, {
        props: { rootMargin: '9999px' },
      })

      expect(mockIntersectionObserver).toHaveBeenCalledWith(
        expect.any(Function),
        expect.objectContaining({
          rootMargin: '9999px',
        })
      )
    })
  })
})