import { config } from '@vue/test-utils'
import { vi } from 'vitest'
import { defineComponent } from 'vue'

console.log('Executing tests/Js/setup.ts with Inertia stubs...')

// Mock IntersectionObserver
const mockIntersectionObserver = vi.fn()
mockIntersectionObserver.mockReturnValue({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
})
vi.stubGlobal('IntersectionObserver', mockIntersectionObserver)

// Mock performance.now
vi.stubGlobal('performance', {
  now: vi.fn(),
})

// Stub Inertia components
const Link = defineComponent({
  template: '<a><slot /></a>',
})

const Head = defineComponent({
  template: '<template><slot /></template>',
})

config.global.stubs = {
  Link,
  Head,
}

config.global.mocks = {
  $page: {
    props: {
      auth: {
        user: {
          id: 1,
          name: 'Test User',
          email: 'test@example.com',
          profile_photo_url: '',
          two_factor_enabled: false,
        },
      },
      jetstream: {
        canCreateTeams: false,
        hasTeamFeatures: false,
        managesProfilePhotos: false,
      },
      errorBags: {},
      errors: {},
    },
  },
  route: () => ({
    current: (name, params) => true,
  }),
}
