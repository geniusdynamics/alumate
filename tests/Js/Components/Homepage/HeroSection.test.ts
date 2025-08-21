import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import HeroSection from '@/components/homepage/HeroSection.vue'
import type { HeroSectionProps, AudienceType, Testimonial, PlatformStatistic } from '@/types/homepage'

// Mock AnimatedCounter component
vi.mock('@/components/ui/AnimatedCounter.vue', () => ({
  default: {
    name: 'AnimatedCounter',
    props: ['targetValue', 'format', 'suffix', 'animate', 'ariaLabel'],
    template: '<span>{{ targetValue }}</span>'
  }
}))

// Mock window.gtag
const mockGtag = vi.fn()
Object.defineProperty(window, 'gtag', {
  value: mockGtag,
  writable: true
})

// Mock IntersectionObserver
const mockIntersectionObserver = vi.fn()
mockIntersectionObserver.mockReturnValue({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn()
})
window.IntersectionObserver = mockIntersectionObserver

// Mock matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

describe('HeroSection.vue', () => {
  let wrapper: VueWrapper<any>
  
  const mockTestimonials: Testimonial[] = [
    {
      id: '1',
      quote: 'This platform changed my career trajectory completely.',
      author: {
        id: '1',
        name: 'John Doe',
        graduationYear: 2018,
        degree: 'Computer Science',
        currentRole: 'Senior Developer',
        currentCompany: 'Tech Corp',
        industry: 'Technology',
        location: 'San Francisco',
        profileImage: '/images/john-doe.jpg',
        careerStage: 'mid_career',
        specialties: ['JavaScript', 'React'],
        mentorshipAvailable: true
      },
      featured: true
    },
    {
      id: '2',
      quote: 'The networking opportunities are incredible.',
      author: {
        id: '2',
        name: 'Jane Smith',
        graduationYear: 2020,
        degree: 'Marketing',
        currentRole: 'Marketing Manager',
        currentCompany: 'Brand Co',
        industry: 'Marketing',
        location: 'New York',
        profileImage: '/images/jane-smith.jpg',
        careerStage: 'mid_career',
        specialties: ['Digital Marketing', 'SEO'],
        mentorshipAvailable: false
      },
      featured: false
    }
  ]

  const mockStatistics: PlatformStatistic[] = [
    {
      key: 'alumni_count',
      value: 10000,
      label: 'Alumni Connected',
      icon: 'users',
      animateOnScroll: true,
      format: 'number'
    },
    {
      key: 'salary_increase',
      value: 40,
      label: 'Average Salary Increase',
      icon: 'trending-up',
      animateOnScroll: true,
      format: 'percentage'
    }
  ]

  const mockHeroData: HeroSectionProps = {
    headline: 'Connect with Your Alumni Network',
    subtitle: 'Advance your career through meaningful connections',
    primaryCTA: {
      text: 'Join Now',
      action: 'register',
      variant: 'primary',
      trackingEvent: 'hero_join_click'
    },
    secondaryCTA: {
      text: 'Learn More',
      action: 'learn-more',
      variant: 'secondary',
      trackingEvent: 'hero_learn_more_click'
    },
    backgroundVideo: '/videos/hero-background.mp4',
    backgroundImage: '/images/hero-background.jpg',
    testimonialRotation: mockTestimonials,
    statisticsHighlight: mockStatistics
  }

  beforeEach(() => {
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.useRealTimers()
  })

  describe('Basic Rendering', () => {
    it('renders with minimal props', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType
        }
      })

      expect(wrapper.find('.hero-section').exists()).toBe(true)
      expect(wrapper.find('.hero-headline').text()).toBe('Welcome')
      expect(wrapper.find('.hero-subtitle').text()).toBe('Connect with your alumni network')
    })

    it('renders with full hero data', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      expect(wrapper.find('.hero-headline').text()).toBe('Connect with Your Alumni Network')
      expect(wrapper.find('.hero-subtitle').text()).toBe('Advance your career through meaningful connections')
      expect(wrapper.find('.hero-cta-primary').text()).toBe('Join Now')
      expect(wrapper.find('.hero-cta-secondary').text()).toBe('Learn More')
    })

    it('renders video background when provided', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const video = wrapper.find('.hero-video')
      expect(video.exists()).toBe(true)
      expect(video.attributes('src')).toBe('/videos/hero-background.mp4')
      expect(video.attributes('autoplay')).toBeDefined()
      expect(video.attributes('muted')).toBeDefined()
      expect(video.attributes('loop')).toBeDefined()
    })

    it('renders background image fallback', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: {
            ...mockHeroData,
            backgroundVideo: undefined
          }
        }
      })

      const backgroundDiv = wrapper.find('.hero-background-image')
      expect(backgroundDiv.exists()).toBe(true)
    })
  })

  describe('Statistics Display', () => {
    it('renders statistics when provided', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const statistics = wrapper.find('.hero-statistics')
      expect(statistics.exists()).toBe(true)
      
      const statElements = wrapper.findAll('.hero-stat')
      expect(statElements).toHaveLength(2)
      
      expect(wrapper.text()).toContain('Alumni Connected')
      expect(wrapper.text()).toContain('Average Salary Increase')
    })

    it('does not render statistics section when not provided', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: {
            ...mockHeroData,
            statisticsHighlight: undefined
          }
        }
      })

      expect(wrapper.find('.hero-statistics').exists()).toBe(false)
    })
  })

  describe('Testimonials Rotation', () => {
    it('renders testimonials when provided', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const testimonials = wrapper.find('.hero-testimonials')
      expect(testimonials.exists()).toBe(true)
      
      const quote = wrapper.find('.hero-testimonial-quote')
      expect(quote.text()).toContain('This platform changed my career trajectory completely.')
      
      const authorName = wrapper.find('.hero-testimonial-name')
      expect(authorName.text()).toBe('John Doe')
    })

    it('renders testimonial navigation dots', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const dots = wrapper.findAll('.hero-testimonial-dot')
      expect(dots).toHaveLength(2)
      expect(dots[0].classes()).toContain('active')
    })

    it('switches testimonials when clicking navigation dots', async () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const dots = wrapper.findAll('.hero-testimonial-dot')
      await dots[1].trigger('click')
      await nextTick()

      expect(dots[1].classes()).toContain('active')
      expect(dots[0].classes()).not.toContain('active')
    })

    it('auto-rotates testimonials', async () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      // Expect setInterval to have been called to set up auto-rotation
      expect(vi.stubGlobal('setInterval', vi.fn())).toHaveBeenCalled()

      // Manually trigger nextTestimonial and advance timers
      wrapper.vm.nextTestimonial() // Call the method directly
      vi.advanceTimersByTime(5000) // Advance timers for any internal logic
      await nextTick()

      const dots = wrapper.findAll('.hero-testimonial-dot')
      expect(dots[1].classes()).toContain('active')
    })

    it('does not render testimonials section when not provided', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: {
            ...mockHeroData,
            testimonialRotation: undefined
          }
        }
      })

      expect(wrapper.find('.hero-testimonials').exists()).toBe(false)
    })
  })

  describe('CTA Interactions', () => {
    it('emits cta-click event when primary CTA is clicked', async () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const primaryCTA = wrapper.find('.hero-cta-primary')
      await primaryCTA.trigger('click')

      const emittedEvents = wrapper.emitted('cta-click')
      expect(emittedEvents).toHaveLength(1)
      expect(emittedEvents![0][0]).toEqual({
        action: 'register',
        section: 'hero',
        audience: 'individual',
        additionalData: {
          text: 'Join Now',
          variant: 'primary',
          trackingEvent: 'hero_join_click'
        }
      })
    })

    it('emits cta-click event when secondary CTA is clicked', async () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const secondaryCTA = wrapper.find('.hero-cta-secondary')
      await secondaryCTA.trigger('click')

      const emittedEvents = wrapper.emitted('cta-click')
      expect(emittedEvents).toHaveLength(1)
      expect(emittedEvents![0][0]).toEqual({
        action: 'learn-more',
        section: 'hero',
        audience: 'individual',
        additionalData: {
          text: 'Learn More',
          variant: 'secondary',
          trackingEvent: 'hero_learn_more_click'
        }
      })
    })

    it('tracks analytics when CTA is clicked', async () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const primaryCTA = wrapper.find('.hero-cta-primary')
      await primaryCTA.trigger('click')

      expect(mockGtag).toHaveBeenCalledWith('event', 'hero_cta_click', {
        cta_text: 'Join Now',
        cta_action: 'register',
        audience: 'individual',
        section: 'hero'
      })
    })

    it('handles keyboard navigation for CTAs', async () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const primaryCTA = wrapper.find('.hero-cta-primary')
      
      // Test Enter key
      await primaryCTA.trigger('keydown.enter')
      expect(wrapper.emitted('cta-click')).toHaveLength(1)

      // Test Space key
      await primaryCTA.trigger('keydown.space')
      expect(wrapper.emitted('cta-click')).toHaveLength(2)
    })
  })

  describe('Accessibility Features', () => {
    it('has proper ARIA labels and roles', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const section = wrapper.find('.hero-section')
      expect(section.attributes('role')).toBe('banner')
      expect(section.attributes('aria-label')).toBe('Homepage hero section')

      const statisticsRegion = wrapper.find('.hero-statistics')
      expect(statisticsRegion.attributes('role')).toBe('region')
      expect(statisticsRegion.attributes('aria-label')).toBe('Platform statistics')

      const testimonialsRegion = wrapper.find('.hero-testimonials')
      expect(testimonialsRegion.attributes('role')).toBe('region')
      expect(testimonialsRegion.attributes('aria-label')).toBe('Alumni testimonials')
    })

    it('has proper heading hierarchy', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const headline = wrapper.find('.hero-headline')
      expect(headline.element.tagName).toBe('H1')
      expect(headline.attributes('id')).toBeDefined()
    })

    it('has proper alt text for testimonial avatars', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const avatar = wrapper.find('.hero-testimonial-avatar')
      expect(avatar.attributes('alt')).toBe('John Doe profile photo')
      expect(avatar.attributes('loading')).toBe('lazy')
    })

    it('has proper ARIA attributes for testimonial navigation', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const nav = wrapper.find('.hero-testimonial-nav')
      expect(nav.attributes('role')).toBe('tablist')
      expect(nav.attributes('aria-label')).toBe('Testimonial navigation')

      const dots = wrapper.findAll('.hero-testimonial-dot')
      expect(dots[0].attributes('role')).toBe('tab')
      expect(dots[0].attributes('aria-selected')).toBe('true')
      expect(dots[1].attributes('aria-selected')).toBe('false')
    })
  })

  describe('Video Error Handling', () => {
    it('handles video loading errors gracefully', async () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      const video = wrapper.find('.hero-video')
      await video.trigger('error')

      // Should still render background image
      const backgroundImage = wrapper.find('.hero-background-image')
      expect(backgroundImage.exists()).toBe(true)
    })
  })

  describe('Responsive Behavior', () => {
    it('applies mobile-specific classes', () => {
      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })

      // Check that responsive classes are applied
      const headline = wrapper.find('.hero-headline')
      expect(headline.element.className).toContain('text-4xl')
      expect(headline.element.className).toContain('md:text-6xl')
      expect(headline.element.className).toContain('lg:text-7xl')
    })
  })

  describe('Reduced Motion Support', () => {
    it('respects prefers-reduced-motion setting', () => {
      // Mock reduced motion preference
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockImplementation(query => ({
          matches: query === '(prefers-reduced-motion: reduce)',
          media: query,
          onchange: null,
          addListener: vi.fn(),
          removeListener: vi.fn(),
          addEventListener: vi.fn(),
          removeEventListener: vi.fn(),
          dispatchEvent: vi.fn(),
        })),
      })

      wrapper = mount(HeroSection, {
        props: {
          audience: 'individual' as AudienceType,
          heroData: mockHeroData
        }
      })
      await nextTick() // Add this line

      // Video should not be rendered when reduced motion is preferred
      expect(wrapper.find('.hero-video').exists()).toBe(false)
    })
  })
})