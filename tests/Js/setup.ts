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

vi.stubGlobal('setInterval', vi.fn(() => 123));
vi.stubGlobal('clearInterval', vi.fn());

Object.defineProperty(HTMLMediaElement.prototype, 'load', {
  configurable: true,
  value: vi.fn(),
});

// Mock PerformanceObserver for Core Web Vitals
class MockPerformanceObserver {
  private callback: PerformanceObserverCallback;
  constructor(callback: PerformanceObserverCallback) {
    this.callback = callback;
  }
  observe(options?: PerformanceObserverInit): void {
    if (options?.entryTypes?.includes('largest-contentful-paint')) {
      this.callback({
        getEntries: () => [{ entryType: 'largest-contentful-paint', startTime: 1000 } as PerformanceEntry],
      } as PerformanceObserverEntryList, this as PerformanceObserver);
    }
    if (options?.entryTypes?.includes('first-input')) {
      this.callback({
        getEntries: () => [{ entryType: 'first-input', startTime: 50, processingStart: 60 } as PerformanceEntry],
      } as PerformanceObserverEntryList, this as PerformanceObserver);
    }
    if (options?.entryTypes?.includes('layout-shift')) {
      this.callback({
        getEntries: () => [{ entryType: 'layout-shift', value: 0.05, hadRecentInput: false } as PerformanceEntry],
      } as PerformanceObserverEntryList, this as PerformanceObserver);
    }
  }
  disconnect(): void {}
  takeRecords(): PerformanceEntryList { return { getEntries: () => [] } as PerformanceEntryList; }
}
Object.defineProperty(window, 'PerformanceObserver', {
  writable: true,
  configurable: true,
  value: MockPerformanceObserver,
});

// Mock performance.getEntriesByType for navigation timing
Object.defineProperty(window.performance, 'getEntriesByType', {
  writable: true,
  configurable: true,
  value: vi.fn((type: string) => {
    if (type === 'navigation') {
      return [{
        domInteractive: 1500,
        navigationStart: 0,
      }] as PerformanceNavigationTiming[];
    }
    if (type === 'paint') {
      return [{
        name: 'first-contentful-paint',
        startTime: 800,
      }] as PerformancePaintTiming[];
    }
    return [];
  }),
});

// Mock performance.mark and performance.measure
Object.defineProperty(window.performance, 'mark', {
  writable: true,
  configurable: true,
  value: vi.fn(),
});
Object.defineProperty(window.performance, 'measure', {
  writable: true,
  configurable: true,
  value: vi.fn(),
});
Object.defineProperty(window.performance, 'getEntriesByName', {
  writable: true,
  configurable: true,
  value: vi.fn((name: string, type?: string) => {
    if (name === 'custom-metric' && type === 'measure') {
      return [{ duration: 100 }] as PerformanceMeasure[];
    }
    return [];
  }),
});

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
  route: (name) => {
    const routes = {
      'super-admin.dashboard': '/super-admin/dashboard',
      'super-admin.analytics': '/super-admin/analytics',
      'super-admin.users': '/super-admin/users',
      'super-admin.content': '/super-admin/content',
      'super-admin.activity': '/super-admin/activity',
      'super-admin.database': '/super-admin/database',
      'security.dashboard': '/security/dashboard',
      'super-admin.performance': '/super-admin/performance',
      'super-admin.notifications': '/super-admin/notifications',
      'super-admin.settings': '/super-admin/settings',
      'super-admin.institutions': '/super-admin/institutions',
      'super-admin.employer-verification': '/super-admin/employer-verification',
      'super-admin.reports': '/super-admin/reports',
    };
    return routes[name] || `/${name.replace(/\./g, '/')}`;
  },
}

vi.mock('@inertiajs/vue3', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    ...actual,
    createInertiaApp: vi.fn(),
    router: {
      get: vi.fn(),
      post: vi.fn(),
      put: vi.fn(),
      delete: vi.fn(),
      patch: vi.fn(),
      reload: vi.fn(),
      visit: vi.fn(),
      replace: vi.fn(),
      push: vi.fn(),
      back: vi.fn(),
      flush: vi.fn(),
      finish: vi.fn(),
      cancel: vi.fn(),
      on: vi.fn(),
      off: vi.fn(),
      once: vi.fn(),
      setPage: vi.fn(),
      resolveComponent: vi.fn(),
      swapComponent: vi.fn(),
      setContext: vi.fn(),
      getContext: vi.fn(),
      version: 'test-version',
    },
    usePage: vi.fn(() => ({
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
    })),
    useForm: vi.fn((initialData) => ({
      ...initialData,
      hasErrors: false,
      errors: {},
      post: vi.fn(),
      put: vi.fn(),
      delete: vi.fn(),
      patch: vi.fn(),
      processing: false,
      reset: vi.fn(),
      clearErrors: vi.fn(),
    })),
    createProvider: vi.fn(() => ({
      install: vi.fn(),
    })),
  }
})

vi.mock('/images/logo.png', () => ({ default: 'logo.png' }));

vi.mock('@heroicons/vue/24/outline', async (importOriginal) => {
  const actual = await importOriginal()
  return {
    ...actual,
    ShieldCheckIcon: vi.fn(() => ({ template: '<div data-testid="shield-check-icon"></div>' })),
    UserGroupIcon: vi.fn(() => ({ template: '<div data-testid="user-group-icon"></div>' })),
    ClockIcon: vi.fn(() => ({ template: '<div data-testid="clock-icon"></div>' })),
    StarIcon: vi.fn(() => ({ template: '<div data-testid="star-icon"></div>' })),
    TrendingUpIcon: vi.fn(() => ({ template: '<div data-testid="trending-up-icon"></div>' })),
  }
})