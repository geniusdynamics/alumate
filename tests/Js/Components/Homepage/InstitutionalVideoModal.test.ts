import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import InstitutionalVideoModal from '@/components/homepage/InstitutionalVideoModal.vue'
import type { InstitutionTestimonial } from '@/types/homepage'

const mockTestimonial: InstitutionTestimonial = {
  id: '1',
  quote: 'This platform has transformed our alumni engagement.',
  institution: {
    id: 'inst-1',
    name: 'Harvard University',
    type: 'university',
    logo: '/images/harvard-logo.png',
    website: 'https://harvard.edu',
    alumniCount: 400000,
    establishedYear: 1636,
    location: 'Cambridge, MA',
    tier: 'enterprise'
  },
  administrator: {
    id: 'admin-1',
    name: 'Dr. Emily Chen',
    title: 'Vice President of Alumni Relations',
    institution: 'Harvard University',
    email: 'emily.chen@harvard.edu',
    profileImage: '/images/emily-chen.jpg',
    responsibilities: ['Alumni Strategy', 'Digital Engagement'],
    experience: 12
  },
  results: [
    {
      metric: 'engagement',
      beforeValue: 30,
      afterValue: 85,
      improvementPercentage: 183,
      timeframe: '18 months',
      verified: true
    },
    {
      metric: 'donations',
      beforeValue: 1000000,
      afterValue: 2500000,
      improvementPercentage: 150,
      timeframe: '18 months',
      verified: true
    }
  ],
  videoTestimonial: 'https://example.com/testimonial.mp4',
  featured: true
}

// Mock HTMLVideoElement methods
const mockVideoElement = {
  pause: vi.fn(),
  play: vi.fn(),
  currentTime: 0,
  addEventListener: vi.fn(),
  removeEventListener: vi.fn()
}

// Mock Teleport component
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue')
  return {
    ...actual,
    Teleport: {
      name: 'Teleport',
      props: ['to'],
      template: '<div><slot /></div>'
    }
  }
})

describe('InstitutionalVideoModal', () => {
  let wrapper: any

  beforeEach(() => {
    // Mock document.querySelector for modal focus
    vi.spyOn(document, 'querySelector').mockReturnValue({
      focus: vi.fn()
    } as any)

    // Mock document event listeners
    vi.spyOn(document, 'addEventListener')
    vi.spyOn(document, 'removeEventListener')
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
    vi.restoreAllMocks()
  })

  it('renders modal when isOpen is true', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(true)
    expect(wrapper.find('#video-modal-title').text()).toBe('Dr. Emily Chen')
  })

  it('does not render modal when isOpen is false', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: false,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('displays testimonial information correctly', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    expect(wrapper.text()).toContain('Dr. Emily Chen')
    expect(wrapper.text()).toContain('Vice President of Alumni Relations at Harvard University')
    expect(wrapper.text()).toContain('This platform has transformed our alumni engagement.')
  })

  it('displays institution logo with correct attributes', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const logo = wrapper.find('img[alt="Harvard University logo"]')
    expect(logo.exists()).toBe(true)
    expect(logo.attributes('src')).toBe('/images/harvard-logo.png')
  })

  it('displays video element with correct source', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const video = wrapper.find('video')
    expect(video.exists()).toBe(true)
    expect(video.attributes('src')).toBe('https://example.com/video.mp4')
    expect(video.attributes('controls')).toBeDefined()
    expect(video.attributes('autoplay')).toBeDefined()
  })

  it('displays results metrics in footer', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    expect(wrapper.text()).toContain('+183%')
    expect(wrapper.text()).toContain('Engagement')
    expect(wrapper.text()).toContain('+150%')
    expect(wrapper.text()).toContain('Donations')
  })

  it('limits results display to first 3 metrics', () => {
    const testimonialWithManyResults = {
      ...mockTestimonial,
      results: [
        ...mockTestimonial.results,
        {
          metric: 'event_attendance',
          beforeValue: 100,
          afterValue: 300,
          improvementPercentage: 200,
          timeframe: '12 months',
          verified: true
        },
        {
          metric: 'app_downloads',
          beforeValue: 0,
          afterValue: 5000,
          improvementPercentage: 100,
          timeframe: '6 months',
          verified: true
        }
      ]
    }

    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: testimonialWithManyResults
      }
    })

    // Should show first 3 results
    expect(wrapper.text()).toContain('+183%')
    expect(wrapper.text()).toContain('+150%')
    expect(wrapper.text()).toContain('+200%')
    // Should not show 4th result
    expect(wrapper.text()).not.toContain('App Downloads')
  })

  it('emits close event when close button is clicked', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const closeButton = wrapper.find('button[aria-label="Close video modal"]')
    await closeButton.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('emits close event when backdrop is clicked', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const backdrop = wrapper.find('.fixed.inset-0.z-50')
    await backdrop.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('does not emit close event when modal content is clicked', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const modalContent = wrapper.find('.relative.bg-white.rounded-lg')
    await modalContent.trigger('click')

    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('emits request-demo event when demo button is clicked', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const demoButton = wrapper.find('button:contains("Request Demo")')
    await demoButton.trigger('click')

    expect(wrapper.emitted('request-demo')).toBeTruthy()
    expect(wrapper.emitted('request-demo')?.[0]).toEqual([mockTestimonial])
  })

  it('shows loading spinner initially', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    // Loading should be true initially
    expect(wrapper.find('.animate-spin').exists()).toBe(true)
  })

  it('hides loading spinner when video can play', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const video = wrapper.find('video')
    await video.trigger('canplay')

    expect(wrapper.find('.animate-spin').exists()).toBe(false)
  })

  it('shows error state when video fails to load', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/invalid-video.mp4',
        testimonial: mockTestimonial
      }
    })

    const video = wrapper.find('video')
    await video.trigger('error')

    expect(wrapper.text()).toContain('Video Unavailable')
    expect(wrapper.text()).toContain('Sorry, this video testimonial is currently unavailable.')
  })

  it('hides footer when there is an error', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/invalid-video.mp4',
        testimonial: mockTestimonial
      }
    })

    const video = wrapper.find('video')
    await video.trigger('error')

    // Footer should not be visible when there's an error
    expect(wrapper.find('.bg-gray-50').exists()).toBe(false)
  })

  it('handles modal without testimonial gracefully', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4'
      }
    })

    // Should still render video element
    expect(wrapper.find('video').exists()).toBe(true)
    
    // Should not show testimonial-specific content
    expect(wrapper.find('#video-modal-title').text()).toBe('')
  })

  it('formats metric labels correctly', () => {
    const testimonialWithVariousMetrics = {
      ...mockTestimonial,
      results: [
        {
          metric: 'alumni_participation',
          beforeValue: 25,
          afterValue: 75,
          improvementPercentage: 200,
          timeframe: '12 months',
          verified: true
        },
        {
          metric: 'job_placements',
          beforeValue: 100,
          afterValue: 300,
          improvementPercentage: 200,
          timeframe: '12 months',
          verified: true
        }
      ]
    }

    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: testimonialWithVariousMetrics
      }
    })

    expect(wrapper.text()).toContain('Alumni Participation')
    expect(wrapper.text()).toContain('Job Placements')
  })

  it('sets up keyboard event listener when modal opens', async () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: false,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    // Change isOpen to true
    await wrapper.setProps({ isOpen: true })

    expect(document.addEventListener).toHaveBeenCalledWith('keydown', expect.any(Function))
  })

  it('applies correct ARIA attributes for accessibility', () => {
    wrapper = mount(InstitutionalVideoModal, {
      props: {
        isOpen: true,
        videoUrl: 'https://example.com/video.mp4',
        testimonial: mockTestimonial
      }
    })

    const dialog = wrapper.find('[role="dialog"]')
    expect(dialog.exists()).toBe(true)
    expect(dialog.attributes('aria-modal')).toBe('true')
    expect(dialog.attributes('aria-labelledby')).toBe('video-modal-title')
  })
})