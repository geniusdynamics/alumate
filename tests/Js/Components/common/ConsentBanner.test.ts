import { describe, it, expect, beforeEach, vi, afterEach } from '@jest/globals'
import { mount } from '@vue/test-utils'
import ConsentBanner from '@/Components/common/ConsentBanner.vue'
import { router } from '@inertiajs/vue3'

// Mock Inertia.js router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn()
  }
}))

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn()
}

Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock custom events
const dispatchEventSpy = vi.spyOn(window, 'dispatchEvent')

describe('ConsentBanner', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorageMock.getItem.mockClear()
    localStorageMock.setItem.mockClear()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('renders banner when no consent decision exists', () => {
    localStorageMock.getItem.mockReturnValue(null)

    const wrapper = mount(ConsentBanner)

    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.find('#consent-title').text()).toBe('Analytics Consent')
    expect(wrapper.find('#consent-description').text()).toContain('We use analytics to improve your experience')
    expect(wrapper.find('button').text()).toContain('Accept Analytics')
  })

  it('does not render banner when consent decision exists', () => {
    localStorageMock.getItem.mockReturnValue('granted')

    const wrapper = mount(ConsentBanner)

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('renders banner when forceShow prop is true', () => {
    localStorageMock.getItem.mockReturnValue('granted')

    const wrapper = mount(ConsentBanner, {
      props: {
        forceShow: true
      }
    })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
  })

  it('shows loading state when granting consent', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    mockRouterPost.mockResolvedValueOnce({})

    const wrapper = mount(ConsentBanner)

    const grantButton = wrapper.find('button:contains("Accept Analytics")')
    await grantButton.trigger('click')

    expect(wrapper.find('span:contains("Processing...")').exists()).toBe(true)
    expect(grantButton.attributes('disabled')).toBeDefined()
  })

  it('grants consent successfully', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    mockRouterPost.mockResolvedValueOnce({})

    const wrapper = mount(ConsentBanner)

    const grantButton = wrapper.find('button:contains("Accept Analytics")')
    await grantButton.trigger('click')

    expect(mockRouterPost).toHaveBeenCalledWith('/api/consent/grant', {
      type: 'analytics'
    })
    expect(localStorageMock.setItem).toHaveBeenCalledWith('analytics_consent_decision', 'granted')
    expect(dispatchEventSpy).toHaveBeenCalledWith(new CustomEvent('analytics-consent-granted'))
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('handles grant consent error', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    mockRouterPost.mockRejectedValueOnce(new Error('API Error'))

    const wrapper = mount(ConsentBanner)

    const grantButton = wrapper.find('button:contains("Accept Analytics")')
    await grantButton.trigger('click')

    expect(consoleSpy).toHaveBeenCalledWith('Failed to grant consent:', expect.any(Error))
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true) // Banner should still be visible
  })

  it('shows loading state when revoking consent', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    mockRouterPost.mockResolvedValueOnce({})

    const wrapper = mount(ConsentBanner)

    const revokeButton = wrapper.find('button:contains("Decline Analytics")')
    await revokeButton.trigger('click')

    expect(wrapper.find('span:contains("Processing...")').exists()).toBe(true)
    expect(revokeButton.attributes('disabled')).toBeDefined()
  })

  it('revokes consent successfully', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    mockRouterPost.mockResolvedValueOnce({})

    const wrapper = mount(ConsentBanner)

    const revokeButton = wrapper.find('button:contains("Decline Analytics")')
    await revokeButton.trigger('click')

    expect(mockRouterPost).toHaveBeenCalledWith('/api/consent/revoke', {
      type: 'analytics'
    })
    expect(localStorageMock.setItem).toHaveBeenCalledWith('analytics_consent_decision', 'revoked')
    expect(dispatchEventSpy).toHaveBeenCalledWith(new CustomEvent('analytics-consent-revoked'))
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('handles revoke consent error', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    mockRouterPost.mockRejectedValueOnce(new Error('API Error'))

    const wrapper = mount(ConsentBanner)

    const revokeButton = wrapper.find('button:contains("Decline Analytics")')
    await revokeButton.trigger('click')

    expect(consoleSpy).toHaveBeenCalledWith('Failed to revoke consent:', expect.any(Error))
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true) // Banner should still be visible
  })

  it('updates banner visibility when forceShow prop changes', async () => {
    localStorageMock.getItem.mockReturnValue('granted')

    const wrapper = mount(ConsentBanner, {
      props: {
        forceShow: false
      }
    })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)

    await wrapper.setProps({ forceShow: true })
    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
  })

  it('has proper accessibility attributes', () => {
    localStorageMock.getItem.mockReturnValue(null)

    const wrapper = mount(ConsentBanner)

    const dialog = wrapper.find('[role="dialog"]')
    expect(dialog.attributes('aria-modal')).toBe('true')
    expect(dialog.attributes('aria-labelledby')).toBe('consent-title')
    expect(dialog.attributes('aria-describedby')).toBe('consent-description')

    expect(wrapper.find('#consent-title').exists()).toBe(true)
    expect(wrapper.find('#consent-description').exists()).toBe(true)
  })

  it('renders privacy policy link', () => {
    localStorageMock.getItem.mockReturnValue(null)

    const wrapper = mount(ConsentBanner)

    const privacyLink = wrapper.find('a[href="/privacy"]')
    expect(privacyLink.exists()).toBe(true)
    expect(privacyLink.attributes('target')).toBe('_blank')
    expect(privacyLink.text()).toBe('privacy policy')
  })

  it('disables both buttons during loading', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    mockRouterPost.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)))

    const wrapper = mount(ConsentBanner)

    const grantButton = wrapper.find('button:contains("Accept Analytics")')
    const revokeButton = wrapper.find('button:contains("Decline Analytics")')

    await grantButton.trigger('click')

    expect(grantButton.attributes('disabled')).toBeDefined()
    expect(revokeButton.attributes('disabled')).toBeDefined()
  })

  it('uses Teleport to render in body', () => {
    localStorageMock.getItem.mockReturnValue(null)

    const wrapper = mount(ConsentBanner)

    expect(wrapper.findComponent({ name: 'Teleport' }).exists()).toBe(true)
    expect(wrapper.findComponent({ name: 'Teleport' }).attributes('to')).toBe('body')
  })

  it('has proper transition classes', () => {
    localStorageMock.getItem.mockReturnValue(null)

    const wrapper = mount(ConsentBanner)

    const transition = wrapper.findComponent({ name: 'Transition' })
    expect(transition.exists()).toBe(true)
    expect(transition.attributes('enter-active-class')).toContain('transition ease-out duration-300')
    expect(transition.attributes('leave-active-class')).toContain('transition ease-in duration-200')
  })

  it('cleans up loading state after grant consent completes', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    mockRouterPost.mockResolvedValueOnce({})

    const wrapper = mount(ConsentBanner)

    const grantButton = wrapper.find('button:contains("Accept Analytics")')
    await grantButton.trigger('click')

    // Wait for the async operation to complete
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.vm.loading).toBe(false)
  })

  it('cleans up loading state after revoke consent completes', async () => {
    localStorageMock.getItem.mockReturnValue(null)
    const mockRouterPost = vi.mocked(router.post)
    mockRouterPost.mockResolvedValueOnce({})

    const wrapper = mount(ConsentBanner)

    const revokeButton = wrapper.find('button:contains("Decline Analytics")')
    await revokeButton.trigger('click')

    // Wait for the async operation to complete
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(wrapper.vm.loading).toBe(false)
  })
})