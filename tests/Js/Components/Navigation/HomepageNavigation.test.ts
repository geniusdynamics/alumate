// ABOUTME: Test file for HomepageNavigation component accessibility and mobile responsiveness
// ABOUTME: Ensures navigation meets WCAG guidelines and works properly on mobile devices

import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import HomepageNavigation from '@/components/navigation/HomepageNavigation.vue'

// Mock Inertia
vi.mock('@inertiajs/vue3', () => ({
  Link: {
    name: 'Link',
    props: ['href'],
    template: '<a :href="href"><slot /></a>'
  },
  router: {
    get: vi.fn()
  },
  usePage: () => ({
    props: {
      auth: { user: null },
      ziggy: {
        routes: {
          'home': { uri: '/', methods: ['GET'] },
          'login': { uri: '/login', methods: ['GET'] },
          'register': { uri: '/register', methods: ['GET'] },
          'employer.register': { uri: '/employer/register', methods: ['GET'] },
          'jobs.public.index': { uri: '/jobs', methods: ['GET'] },
          'alumni.directory': { uri: '/alumni', methods: ['GET'] },
          'alumni.map': { uri: '/alumni/map', methods: ['GET'] },
          'stories.index': { uri: '/stories', methods: ['GET'] }
        }
      }
    }
  })
}))

// Mock route helper
vi.mock('ziggy-js', () => ({
  route: (name: string) => {
    const routes: Record<string, string> = {
      'home': '/',
      'login': '/login',
      'register': '/register',
      'employer.register': '/employer/register',
      'jobs.public.index': '/jobs',
      'alumni.directory': '/alumni',
      'alumni.map': '/alumni/map',
      'stories.index': '/stories'
    }
    return routes[name] || '/'
  }
}))

// Mock UI components
vi.mock('@/components/ui/navigation-menu', () => ({
  NavigationMenu: { name: 'NavigationMenu', template: '<div><slot /></div>' },
  NavigationMenuList: { name: 'NavigationMenuList', template: '<ul><slot /></ul>' },
  NavigationMenuItem: { name: 'NavigationMenuItem', template: '<li><slot /></li>' },
  NavigationMenuTrigger: { name: 'NavigationMenuTrigger', template: '<button><slot /></button>' },
  NavigationMenuContent: { name: 'NavigationMenuContent', template: '<div><slot /></div>' },
  NavigationMenuLink: { name: 'NavigationMenuLink', template: '<a><slot /></a>' },
  navigationMenuTriggerStyle: () => 'nav-trigger-style'
}))

vi.mock('@/components/ui/button', () => ({
  Button: {
    name: 'Button',
    props: ['variant', 'size', 'asChild'],
    template: '<button><slot /></button>'
  }
}))

vi.mock('@/components/ui/dropdown-menu', () => ({
  DropdownMenu: { name: 'DropdownMenu', template: '<div><slot /></div>' },
  DropdownMenuTrigger: { name: 'DropdownMenuTrigger', template: '<button><slot /></button>' },
  DropdownMenuContent: { name: 'DropdownMenuContent', template: '<div><slot /></div>' }
}))

vi.mock('@/components/ui/avatar', () => ({
  Avatar: { name: 'Avatar', template: '<div><slot /></div>' },
  AvatarImage: { name: 'AvatarImage', props: ['src', 'alt'], template: '<img :src="src" :alt="alt" />' },
  AvatarFallback: { name: 'AvatarFallback', template: '<div><slot /></div>' }
}))

vi.mock('@/components/search/SearchInput.vue', () => ({
  default: {
    name: 'SearchInput',
    props: ['modelValue', 'placeholder'],
    emits: ['update:modelValue', 'search', 'clear'],
    template: '<input :value="modelValue" :placeholder="placeholder" @input="$emit(\"update:modelValue\", $event.target.value)" />'
  }
}))

vi.mock('@/components/AppLogoIcon.vue', () => ({
  default: { name: 'AppLogoIcon', template: '<svg></svg>' }
}))

vi.mock('@/components/UserMenuContent.vue', () => ({
  default: { name: 'UserMenuContent', props: ['user'], template: '<div>User Menu</div>' }
}))

// Mock Lucide icons
vi.mock('lucide-vue-next', () => {
  const mockIcon = { name: 'MockIcon', template: '<svg></svg>' }
  return {
    Search: mockIcon,
    Menu: mockIcon,
    X: mockIcon,
    Home: mockIcon,
    Briefcase: mockIcon,
    Users: mockIcon,
    MapPin: mockIcon,
    Star: mockIcon,
    Zap: mockIcon,
    DollarSign: mockIcon,
    Mail: mockIcon,
    User: mockIcon,
    Settings: mockIcon,
    LogOut: mockIcon
  }
})

describe('HomepageNavigation', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(HomepageNavigation, {
      global: {
        provide: {
          auth: { user: null }
        },
        mocks: {
          route: (name: string) => {
            const routes: Record<string, string> = {
              'home': '/',
              'login': '/login',
              'register': '/register',
              'employer.register': '/employer/register',
              'jobs.public.index': '/jobs',
              'alumni.directory': '/alumni',
              'alumni.map': '/alumni/map',
              'stories.index': '/stories'
            }
            return routes[name] || '/'
          }
        }
      }
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA labels and roles', () => {
      const nav = wrapper.find('nav')
      expect(nav.attributes('role')).toBe('navigation')
      expect(nav.attributes('aria-label')).toBe('Main navigation')
    })

    it('has accessible brand link', () => {
      const brandLink = wrapper.find('.brand-link')
      expect(brandLink.attributes('aria-label')).toBe('Alumni Platform Home')
    })

    it('has accessible mobile menu toggle', () => {
      const mobileToggle = wrapper.find('.mobile-menu-toggle')
      expect(mobileToggle.attributes('aria-label')).toBe('Toggle mobile menu')
    })

    it('has accessible search button', () => {
      const searchButton = wrapper.find('.search-button')
      expect(searchButton.attributes('aria-label')).toBe('Search')
    })

    it('mobile menu has proper role', async () => {
      await wrapper.vm.toggleMobileMenu()
      await wrapper.vm.$nextTick()
      
      const mobileMenu = wrapper.find('.mobile-menu')
      expect(mobileMenu.attributes('role')).toBe('menu')
    })
  })

  describe('Mobile Responsiveness', () => {
    it('shows mobile menu toggle button', () => {
      const mobileToggle = wrapper.find('.mobile-menu-toggle')
      expect(mobileToggle.exists()).toBe(true)
    })

    it('shows mobile search button', () => {
      const searchButton = wrapper.find('.search-button')
      expect(searchButton.exists()).toBe(true)
    })

    it('hides desktop navigation on mobile (has lg:flex class)', () => {
      const desktopNav = wrapper.find('.nav-menu-desktop')
      expect(desktopNav.classes()).toContain('hidden')
      expect(desktopNav.classes()).toContain('lg:flex')
    })

    it('hides desktop search on mobile (has md:flex class)', () => {
      const desktopSearch = wrapper.find('.hidden.md\\:flex')
      expect(desktopSearch.exists()).toBe(true)
    })

    it('can toggle mobile menu', async () => {
      expect(wrapper.vm.isMobileMenuOpen).toBe(false)
      
      await wrapper.vm.toggleMobileMenu()
      expect(wrapper.vm.isMobileMenuOpen).toBe(true)
      
      await wrapper.vm.toggleMobileMenu()
      expect(wrapper.vm.isMobileMenuOpen).toBe(false)
    })

    it('can toggle mobile search', async () => {
      expect(wrapper.vm.isSearchOpen).toBe(false)
      
      await wrapper.vm.toggleSearch()
      expect(wrapper.vm.isSearchOpen).toBe(true)
      
      await wrapper.vm.toggleSearch()
      expect(wrapper.vm.isSearchOpen).toBe(false)
    })

    it('closes mobile menu when navigation link is clicked', async () => {
      await wrapper.vm.toggleMobileMenu()
      expect(wrapper.vm.isMobileMenuOpen).toBe(true)
      
      await wrapper.vm.closeMobileMenu()
      expect(wrapper.vm.isMobileMenuOpen).toBe(false)
    })
  })

  describe('Authentication States', () => {
    it('shows auth buttons when user is not logged in', () => {
      const authButtons = wrapper.find('.auth-buttons')
      expect(authButtons.exists()).toBe(true)
      
      const loginButton = wrapper.find('.login-button')
      const signupButton = wrapper.find('.signup-button')
      const employerButton = wrapper.find('.employer-button')
      
      expect(loginButton.exists()).toBe(true)
      expect(signupButton.exists()).toBe(true)
      expect(employerButton.exists()).toBe(true)
    })

    it('shows user menu when user is logged in', async () => {
      // Remount with authenticated user
      wrapper = mount(HomepageNavigation, {
        global: {
          provide: {
            auth: { 
              user: {
                id: 1,
                name: 'John Doe',
                email: 'john@example.com',
                avatar: null
              }
            }
          }
        }
      })
      
      await wrapper.vm.$nextTick()
      
      const userMenu = wrapper.find('.user-menu')
      expect(userMenu.exists()).toBe(true)
      
      const authButtons = wrapper.find('.auth-buttons')
      expect(authButtons.exists()).toBe(false)
    })
  })

  describe('Search Functionality', () => {
    it('handles search input', async () => {
      const searchInput = wrapper.findComponent({ name: 'SearchInput' })
      expect(searchInput.exists()).toBe(true)
      
      await searchInput.vm.$emit('update:modelValue', 'test query')
      expect(wrapper.vm.searchQuery).toBe('test query')
    })

    it('handles search submission', async () => {
      wrapper.vm.searchQuery = 'test query'
      
      const routerSpy = vi.spyOn(wrapper.vm.$router, 'get').mockImplementation(() => {})
      
      await wrapper.vm.handleSearch()
      
      expect(routerSpy).toHaveBeenCalledWith('/search', {
        q: 'test query'
      })
    })

    it('clears search query', async () => {
      wrapper.vm.searchQuery = 'test query'
      
      await wrapper.vm.handleSearchClear()
      
      expect(wrapper.vm.searchQuery).toBe('')
    })
  })

  describe('Navigation Links', () => {
    it('has all required navigation links', () => {
      const homeLink = wrapper.find('a[href="/"]')
      const jobsLink = wrapper.find('a[href="/jobs"]')
      const loginLink = wrapper.find('a[href="/login"]')
      const registerLink = wrapper.find('a[href="/register"]')
      const employerLink = wrapper.find('a[href="/employer/register"]')
      
      expect(homeLink.exists()).toBe(true)
      expect(jobsLink.exists()).toBe(true)
      expect(loginLink.exists()).toBe(true)
      expect(registerLink.exists()).toBe(true)
      expect(employerLink.exists()).toBe(true)
    })
  })
})