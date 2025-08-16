import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AudienceSelector from '@/components/homepage/AudienceSelector.vue'
import { AudienceType, AudiencePreference } from '@/types/homepage'

// Mock Icon component
vi.mock('@/components/Icon.vue', () => ({
  default: {
    name: 'Icon',
    template: '<span class="mock-icon"></span>'
  }
}))

// Mock sessionStorage
const mockSessionStorage = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}

Object.defineProperty(window, 'sessionStorage', {
  value: mockSessionStorage
})

// Mock gtag
Object.defineProperty(window, 'gtag', {
  value: vi.fn()
})

describe('AudienceSelector.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset URL
    Object.defineProperty(window, 'location', {
      value: {
        search: '',
        href: 'http://localhost'
      },
      writable: true
    })
    // Reset document.referrer
    Object.defineProperty(document, 'referrer', {
      value: '',
      writable: true
    })
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Component Rendering', () => {
    it('renders with default individual audience', () => {
      const wrapper = mount(AudienceSelector)
      
      expect(wrapper.find('.selector-button.active').text()).toContain("I'm an Alumnus")
      expect(wrapper.find('.description-text').text()).toContain('Discover career opportunities')
    })

    it('renders with institutional audience when prop is set', () => {
      const wrapper = mount(AudienceSelector, {
        props: {
          audience: 'institutional'
        }
      })
      
      expect(wrapper.find('.selector-button.active').text()).toContain("I'm an Administrator")
      expect(wrapper.find('.description-text').text()).toContain('Engage your alumni community')
    })

    it('hides description when showDescription is false', () => {
      const wrapper = mount(AudienceSelector, {
        props: {
          showDescription: false
        }
      })
      
      expect(wrapper.find('.selector-description').exists()).toBe(false)
    })
  })

  describe('Audience Selection', () => {
    it('emits events when audience is changed', async () => {
      const wrapper = mount(AudienceSelector, {
        props: {
          audience: 'individual'
        }
      })

      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('click')

      expect(wrapper.emitted('update:audience')).toBeTruthy()
      expect(wrapper.emitted('update:audience')![0]).toEqual(['institutional'])
      
      expect(wrapper.emitted('audience-changed')).toBeTruthy()
      expect(wrapper.emitted('audience-changed')![0][0]).toBe('institutional')
      expect(wrapper.emitted('audience-changed')![0][1]).toMatchObject({
        type: 'institutional',
        source: 'manual'
      })
    })

    it('stores preference in session storage when audience changes', async () => {
      const wrapper = mount(AudienceSelector)

      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('click')

      expect(mockSessionStorage.setItem).toHaveBeenCalledWith(
        'homepage_audience_preference',
        expect.stringContaining('"type":"institutional"')
      )
    })

    it('tracks analytics when audience changes', async () => {
      const wrapper = mount(AudienceSelector, {
        props: {
          audience: 'individual'
        }
      })

      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('click')

      expect(window.gtag).toHaveBeenCalledWith('event', 'audience_change', {
        new_audience: 'institutional',
        previous_audience: 'individual',
        change_source: 'manual',
        session_id: expect.any(String)
      })
    })

    it('does not emit events when same audience is selected', async () => {
      const wrapper = mount(AudienceSelector, {
        props: {
          audience: 'individual'
        }
      })

      const individualButton = wrapper.findAll('.selector-button')[0]
      await individualButton.trigger('click')

      expect(wrapper.emitted('update:audience')).toBeFalsy()
      expect(wrapper.emitted('audience-changed')).toBeFalsy()
    })
  })

  describe('Audience Detection', () => {
    it('detects institutional audience from URL parameter', async () => {
      Object.defineProperty(window, 'location', {
        value: {
          search: '?audience=institutional',
          href: 'http://localhost?audience=institutional'
        }
      })

      const wrapper = mount(AudienceSelector, {
        props: {
          autoDetect: true
        }
      })

      // Wait for detection to complete
      await new Promise(resolve => setTimeout(resolve, 150))

      expect(wrapper.emitted('audience-changed')).toBeTruthy()
      expect(wrapper.emitted('audience-changed')![0][0]).toBe('institutional')
      expect(wrapper.emitted('audience-changed')![0][1]).toMatchObject({
        source: 'auto_detected'
      })
    })

    it('detects institutional audience from educational referrer', async () => {
      Object.defineProperty(document, 'referrer', {
        value: 'https://university.edu/alumni'
      })

      const wrapper = mount(AudienceSelector, {
        props: {
          autoDetect: true
        }
      })

      // Wait for detection to complete
      await new Promise(resolve => setTimeout(resolve, 150))

      expect(wrapper.emitted('audience-changed')).toBeTruthy()
      expect(wrapper.emitted('audience-changed')![0][0]).toBe('institutional')
    })

    it('uses stored preference when available', async () => {
      const storedPreference: AudiencePreference = {
        type: 'institutional',
        timestamp: new Date(),
        source: 'manual',
        sessionId: 'test-session'
      }

      mockSessionStorage.getItem.mockReturnValue(JSON.stringify(storedPreference))

      const wrapper = mount(AudienceSelector, {
        props: {
          autoDetect: true
        }
      })

      // Wait for detection to complete
      await new Promise(resolve => setTimeout(resolve, 150))

      expect(wrapper.emitted('audience-changed')).toBeTruthy()
      expect(wrapper.emitted('audience-changed')![0][0]).toBe('institutional')
    })

    it('falls back to individual when detection confidence is low', async () => {
      // No strong indicators for institutional audience
      const wrapper = mount(AudienceSelector, {
        props: {
          autoDetect: true,
          audience: 'individual'
        }
      })

      // Wait for detection to complete
      await new Promise(resolve => setTimeout(resolve, 150))

      // Should not change from individual
      expect(wrapper.emitted('audience-changed')).toBeFalsy()
    })

    it('shows detecting state during auto-detection', async () => {
      const wrapper = mount(AudienceSelector, {
        props: {
          autoDetect: true,
          showDescription: true
        }
      })

      // Should show detecting text initially
      expect(wrapper.find('.detecting-text').exists()).toBe(true)
      expect(wrapper.find('.detecting-text').text()).toBe('Detecting audience...')

      // Wait for detection to complete
      await new Promise(resolve => setTimeout(resolve, 150))

      // Should hide detecting text after detection
      expect(wrapper.find('.detecting-text').exists()).toBe(false)
    })
  })

  describe('Session Management', () => {
    it('generates and stores session ID', async () => {
      const wrapper = mount(AudienceSelector)

      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('click')

      expect(mockSessionStorage.setItem).toHaveBeenCalledWith(
        'homepage_session_id',
        expect.stringMatching(/^session_\d+_[a-z0-9]+$/)
      )
    })

    it('reuses existing session ID', async () => {
      mockSessionStorage.getItem.mockImplementation((key) => {
        if (key === 'homepage_session_id') {
          return 'existing-session-id'
        }
        return null
      })

      const wrapper = mount(AudienceSelector)

      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('click')

      expect(wrapper.emitted('audience-changed')![0][1]).toMatchObject({
        sessionId: 'existing-session-id'
      })
    })

    it('handles session storage errors gracefully', async () => {
      mockSessionStorage.setItem.mockImplementation(() => {
        throw new Error('Storage quota exceeded')
      })

      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})

      const wrapper = mount(AudienceSelector)

      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('click')

      expect(consoleSpy).toHaveBeenCalledWith(
        'Failed to store audience preference:',
        expect.any(Error)
      )

      consoleSpy.mockRestore()
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA labels on buttons', () => {
      const wrapper = mount(AudienceSelector)
      
      const buttons = wrapper.findAll('.selector-button')
      expect(buttons[0].attributes('aria-label')).toBe('Switch to individual alumni view')
      expect(buttons[1].attributes('aria-label')).toBe('Switch to institutional administrator view')
    })

    it('maintains focus management', async () => {
      const wrapper = mount(AudienceSelector)
      
      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('focus')
      await institutionalButton.trigger('keydown', { key: 'Enter' })

      expect(wrapper.emitted('audience-changed')).toBeTruthy()
    })
  })

  describe('Custom Events', () => {
    it('dispatches custom DOM event on audience change', async () => {
      const eventSpy = vi.spyOn(window, 'dispatchEvent')
      
      const wrapper = mount(AudienceSelector, {
        props: {
          audience: 'individual'
        }
      })

      const institutionalButton = wrapper.findAll('.selector-button')[1]
      await institutionalButton.trigger('click')

      expect(eventSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'homepage:audience-changed',
          detail: expect.objectContaining({
            newAudience: 'institutional',
            previousAudience: 'individual',
            source: 'manual'
          })
        })
      )
    })
  })
})