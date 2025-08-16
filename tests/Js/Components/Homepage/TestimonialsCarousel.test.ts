import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import TestimonialsCarousel from '@/components/homepage/TestimonialsCarousel.vue'
import TestimonialCard from '@/components/homepage/TestimonialCard.vue'
import VideoModal from '@/components/homepage/VideoModal.vue'
import type { Testimonial, InstitutionTestimonial } from '@/types/homepage'

// Mock fetch
const mockFetch = vi.fn()
global.fetch = mockFetch

// Mock @vueuse/core
vi.mock('@vueuse/core', () => ({
  useResizeObserver: vi.fn(() => ({}))
}))

describe('TestimonialsCarousel.vue', () => {
  let wrapper: VueWrapper<any>

  const mockIndividualTestimonials: Testimonial[] = [
    {
      id: '1',
      quote: 'This platform helped me land my dream job at Google.',
      author: {
        id: '1',
        name: 'Sarah Chen',
        graduationYear: 2019,
        degree: 'Computer Science',
        currentRole: 'Software Engineer',
        currentCompany: 'Google',
        industry: 'Technology',
        location: 'Mountain View, CA',
        profileImage: '/images/testimonials/sarah-chen.jpg',
        linkedinUrl: 'https://linkedin.com/in/sarahchen',
        careerStage: 'mid_career',
        specialties: ['Machine Learning'],
        mentorshipAvailable: true
      },
      metrics: [
        {
          type: 'salary_increase',
          value: 140,
          unit: 'percentage',
          timeframe: '18 months',
          verified: true
        }
      ],
      featured: true
    }
  ]

  const mockInstitutionalTestimonials: InstitutionTestimonial[] = [
    {
      id: '1',
      quote: 'Our alumni engagement increased by 400% after implementing the platform.',
      institution: {
        id: '1',
        name: 'Stanford University',
        type: 'university',
        logo: '/images/institutions/stanford-logo.png',
        website: 'https://stanford.edu',
        alumniCount: 50000,
        establishedYear: 1885,
        location: 'Stanford, CA',
        tier: 'enterprise'
      },
      administrator: {
        id: '1',
        name: 'Dr. Jennifer Walsh',
        title: 'Director of Alumni Relations',
        institution: 'Stanford University',
        email: 'j.walsh@stanford.edu',
        profileImage: '/images/testimonials/jennifer-walsh.jpg',
        responsibilities: ['Alumni Engagement'],
        experience: 12
      },
      results: [
        {
          metric: 'engagement',
          beforeValue: 15,
          afterValue: 60,
          improvementPercentage: 300,
          timeframe: '12 months',
          verified: true
        }
      ],
      featured: true
    }
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({
        success: true,
        data: mockIndividualTestimonials
      })
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Component Rendering', () => {
    it('renders with default props for individual audience', async () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe('What Our Alumni Say')
      expect(wrapper.text()).toContain('Hear from professionals who have transformed their careers')
    })

    it('renders with custom title and subtitle', () => {
      const customTitle = 'Custom Testimonials Title'
      const customSubtitle = 'Custom subtitle text'

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual',
          title: customTitle,
          subtitle: customSubtitle
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe(customTitle)
      expect(wrapper.text()).toContain(customSubtitle)
    })

    it('renders institutional-specific content', () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'institutional'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      expect(wrapper.find('h2').text()).toBe('Trusted by Leading Institutions')
      expect(wrapper.text()).toContain('universities and organizations')
    })
  })

  describe('Data Fetching', () => {
    it('fetches testimonials on mount', async () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/homepage/testimonials?audience=individual',
        expect.objectContaining({
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        })
      )
    })

    it('fetches institutional testimonials with correct audience parameter', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          data: mockInstitutionalTestimonials
        })
      })

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'institutional'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()

      expect(mockFetch).toHaveBeenCalledWith(
        '/api/homepage/testimonials?audience=institutional',
        expect.any(Object)
      )
    })

    it('displays testimonials after successful fetch', async () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      const testimonialCards = wrapper.findAllComponents(TestimonialCard)
      expect(testimonialCards).toHaveLength(mockIndividualTestimonials.length)
    })
  })

  describe('Loading States', () => {
    it('shows loading state while fetching', async () => {
      // Mock a delayed response
      mockFetch.mockImplementation(() => 
        new Promise(resolve => 
          setTimeout(() => resolve({
            ok: true,
            json: () => Promise.resolve({
              success: true,
              data: mockIndividualTestimonials
            })
          }), 100)
        )
      )

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()

      // Should show loading state
      expect(wrapper.find('.animate-pulse').exists()).toBe(true)
      expect(wrapper.findAllComponents(TestimonialCard)).toHaveLength(0)
    })

    it('hides loading state after successful fetch', async () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.find('.animate-pulse').exists()).toBe(false)
    })
  })

  describe('Error Handling', () => {
    it('displays error state when fetch fails', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'))

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(wrapper.text()).toContain('Network error')
      expect(wrapper.find('button').text()).toBe('Try Again')
    })

    it('retries fetch when Try Again button is clicked', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({
          ok: true,
          json: () => Promise.resolve({
            success: true,
            data: mockIndividualTestimonials
          })
        })

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Should show error state
      expect(wrapper.text()).toContain('Network error')

      // Click retry button
      await wrapper.find('button').trigger('click')
      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Should show testimonials now
      expect(wrapper.findAllComponents(TestimonialCard)).toHaveLength(mockIndividualTestimonials.length)
    })
  })

  describe('Filtering', () => {
    beforeEach(async () => {
      const multipleTestimonials = [
        ...mockIndividualTestimonials,
        {
          ...mockIndividualTestimonials[0],
          id: '2',
          author: {
            ...mockIndividualTestimonials[0].author,
            id: '2',
            name: 'John Doe',
            careerStage: 'senior' as const
          }
        }
      ]

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          data: multipleTestimonials
        })
      })

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual',
          showFilters: true
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('displays filter buttons when showFilters is true', () => {
      const filterButtons = wrapper.findAll('button[aria-pressed]')
      expect(filterButtons.length).toBeGreaterThan(0)
    })

    it('filters testimonials when filter is selected', async () => {
      // Find and click a specific filter button
      const seniorFilter = wrapper.findAll('button[aria-pressed]').find(button => 
        button.text().includes('Senior')
      )

      if (seniorFilter) {
        await seniorFilter.trigger('click')
        await nextTick()

        // Should show filtered results
        const testimonialCards = wrapper.findAllComponents(TestimonialCard)
        expect(testimonialCards.length).toBeLessThanOrEqual(2)
      }
    })

    it('shows all testimonials when "All Stories" filter is selected', async () => {
      const allFilter = wrapper.findAll('button[aria-pressed]').find(button => 
        button.text().includes('All Stories')
      )

      if (allFilter) {
        await allFilter.trigger('click')
        await nextTick()

        const testimonialCards = wrapper.findAllComponents(TestimonialCard)
        expect(testimonialCards.length).toBe(2)
      }
    })
  })

  describe('Navigation', () => {
    beforeEach(async () => {
      // Create enough testimonials to require navigation
      const manyTestimonials = Array.from({ length: 6 }, (_, i) => ({
        ...mockIndividualTestimonials[0],
        id: `${i + 1}`,
        author: {
          ...mockIndividualTestimonials[0].author,
          id: `${i + 1}`,
          name: `Person ${i + 1}`
        }
      }))

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          data: manyTestimonials
        })
      })

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual',
          showNavigation: true,
          slidesPerView: 3
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('shows navigation arrows when there are enough testimonials', () => {
      const nextButton = wrapper.find('button[aria-label="Next testimonials"]')
      const prevButton = wrapper.find('button[aria-label="Previous testimonials"]')
      
      expect(nextButton.exists()).toBe(true)
      // Previous button might not be visible on first slide
    })

    it('navigates to next slide when next button is clicked', async () => {
      const nextButton = wrapper.find('button[aria-label="Next testimonials"]')
      
      if (nextButton.exists()) {
        await nextButton.trigger('click')
        await nextTick()

        // Check that the slide position has changed
        const carousel = wrapper.find('.flex.transition-transform')
        expect(carousel.attributes('style')).toContain('translateX')
      }
    })
  })

  describe('Video Modal', () => {
    it('opens video modal when testimonial has video', async () => {
      const testimonialWithVideo = [{
        ...mockIndividualTestimonials[0],
        videoTestimonial: '/videos/testimonial.mp4'
      }]

      mockFetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          data: testimonialWithVideo
        })
      })

      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))

      // Simulate video play event from TestimonialCard
      const testimonialCard = wrapper.findComponent(TestimonialCard)
      if (testimonialCard.exists()) {
        testimonialCard.vm.$emit('playVideo', '/videos/testimonial.mp4', 'Test Video')
        await nextTick()

        expect(wrapper.findComponent(VideoModal).exists()).toBe(true)
      }
    })
  })

  describe('Accessibility', () => {
    beforeEach(async () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      await nextTick()
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('provides proper ARIA labels for navigation buttons', () => {
      const nextButton = wrapper.find('button[aria-label="Next testimonials"]')
      const prevButton = wrapper.find('button[aria-label="Previous testimonials"]')
      
      if (nextButton.exists()) {
        expect(nextButton.attributes('aria-label')).toBe('Next testimonials')
      }
      if (prevButton.exists()) {
        expect(prevButton.attributes('aria-label')).toBe('Previous testimonials')
      }
    })

    it('provides proper ARIA attributes for filter buttons', () => {
      const filterButtons = wrapper.findAll('button[aria-pressed]')
      
      filterButtons.forEach(button => {
        expect(button.attributes('aria-pressed')).toBeDefined()
      })
    })
  })

  describe('Component Methods', () => {
    it('exposes fetchTestimonials method', () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      expect(wrapper.vm.fetchTestimonials).toBeDefined()
      expect(typeof wrapper.vm.fetchTestimonials).toBe('function')
    })

    it('exposes navigation methods', () => {
      wrapper = mount(TestimonialsCarousel, {
        props: {
          audience: 'individual'
        },
        global: {
          components: {
            TestimonialCard,
            VideoModal
          }
        }
      })

      expect(wrapper.vm.nextSlide).toBeDefined()
      expect(wrapper.vm.prevSlide).toBeDefined()
      expect(wrapper.vm.goToSlide).toBeDefined()
      expect(wrapper.vm.setActiveFilter).toBeDefined()
    })
  })
})