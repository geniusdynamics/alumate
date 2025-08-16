import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import InstitutionalTestimonialCard from '@/components/homepage/InstitutionalTestimonialCard.vue'
import type { InstitutionTestimonial } from '@/types/homepage'

const mockTestimonial: InstitutionTestimonial = {
  id: '1',
  quote: 'This platform has revolutionized how we engage with our alumni community.',
  institution: {
    id: 'inst-1',
    name: 'Stanford University',
    type: 'university',
    logo: '/images/stanford-logo.png',
    website: 'https://stanford.edu',
    alumniCount: 250000,
    establishedYear: 1885,
    location: 'Stanford, CA',
    tier: 'enterprise'
  },
  administrator: {
    id: 'admin-1',
    name: 'Dr. Sarah Johnson',
    title: 'Director of Alumni Relations',
    institution: 'Stanford University',
    email: 'sarah.johnson@stanford.edu',
    profileImage: '/images/sarah-johnson.jpg',
    responsibilities: ['Alumni Engagement', 'Event Management'],
    experience: 15
  },
  results: [
    {
      metric: 'engagement',
      beforeValue: 25,
      afterValue: 75,
      improvementPercentage: 200,
      timeframe: '12 months',
      verified: true
    },
    {
      metric: 'event_attendance',
      beforeValue: 500,
      afterValue: 1200,
      improvementPercentage: 140,
      timeframe: '12 months',
      verified: true
    }
  ],
  videoTestimonial: 'https://example.com/video.mp4',
  featured: true
}

describe('InstitutionalTestimonialCard', () => {
  it('renders testimonial information correctly', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    // Check institution information
    expect(wrapper.text()).toContain('Stanford University')
    expect(wrapper.text()).toContain('university')
    expect(wrapper.text()).toContain('250K Alumni')

    // Check testimonial quote
    expect(wrapper.text()).toContain('This platform has revolutionized how we engage with our alumni community.')

    // Check administrator information
    expect(wrapper.text()).toContain('Dr. Sarah Johnson')
    expect(wrapper.text()).toContain('Director of Alumni Relations')
    expect(wrapper.text()).toContain('15 years experience')
  })

  it('displays institution logo with correct attributes', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    const logo = wrapper.find('img[alt="Stanford University logo"]')
    expect(logo.exists()).toBe(true)
    expect(logo.attributes('src')).toBe('/images/stanford-logo.png')
    expect(logo.attributes('loading')).toBe('lazy')
  })

  it('displays administrator profile image with correct attributes', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    const profileImage = wrapper.find('img[alt="Dr. Sarah Johnson profile"]')
    expect(profileImage.exists()).toBe(true)
    expect(profileImage.attributes('src')).toBe('/images/sarah-johnson.jpg')
    expect(profileImage.attributes('loading')).toBe('lazy')
  })

  it('displays results metrics correctly', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    // Check for improvement percentages
    expect(wrapper.text()).toContain('+200%')
    expect(wrapper.text()).toContain('+140%')

    // Check for formatted metric labels
    expect(wrapper.text()).toContain('Engagement')
    expect(wrapper.text()).toContain('Event Attendance')
  })

  it('shows video testimonial button when video is available', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    const videoButton = wrapper.find('button[aria-label="Play video testimonial from Dr. Sarah Johnson"]')
    expect(videoButton.exists()).toBe(true)
    expect(videoButton.text()).toContain('Watch Video')
  })

  it('hides video testimonial button when video is not available', () => {
    const testimonialWithoutVideo = {
      ...mockTestimonial,
      videoTestimonial: undefined
    }

    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: testimonialWithoutVideo
      }
    })

    const videoButton = wrapper.find('button[aria-label*="Play video testimonial"]')
    expect(videoButton.exists()).toBe(false)
  })

  it('emits play-video event when video button is clicked', async () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    const videoButton = wrapper.find('button[aria-label="Play video testimonial from Dr. Sarah Johnson"]')
    await videoButton.trigger('click')

    expect(wrapper.emitted('play-video')).toBeTruthy()
    expect(wrapper.emitted('play-video')?.[0]).toEqual(['https://example.com/video.mp4'])
  })

  it('displays institution type badge', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    const badge = wrapper.find('.bg-blue-100.text-blue-800')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('university')
  })

  it('shows verification badge when results are verified', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    const verificationBadge = wrapper.find('.text-green-600')
    expect(verificationBadge.exists()).toBe(true)
    expect(verificationBadge.text()).toContain('Verified Results')
  })

  it('hides verification badge when no results are verified', () => {
    const testimonialWithUnverifiedResults = {
      ...mockTestimonial,
      results: mockTestimonial.results.map(result => ({ ...result, verified: false }))
    }

    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: testimonialWithUnverifiedResults
      }
    })

    const verificationBadge = wrapper.find('.text-green-600')
    expect(verificationBadge.exists()).toBe(false)
  })

  it('formats alumni count correctly', () => {
    const testCases = [
      { count: 500, expected: '500' },
      { count: 1500, expected: '1.5K' },
      { count: 250000, expected: '250.0K' },
      { count: 1500000, expected: '1.5M' }
    ]

    testCases.forEach(({ count, expected }) => {
      const testimonialWithCount = {
        ...mockTestimonial,
        institution: {
          ...mockTestimonial.institution,
          alumniCount: count
        }
      }

      const wrapper = mount(InstitutionalTestimonialCard, {
        props: {
          testimonial: testimonialWithCount
        }
      })

      expect(wrapper.text()).toContain(`${expected} Alumni`)
    })
  })

  it('formats metric labels correctly', () => {
    const testimonialWithVariousMetrics = {
      ...mockTestimonial,
      results: [
        {
          metric: 'alumni_participation',
          beforeValue: 20,
          afterValue: 60,
          improvementPercentage: 200,
          timeframe: '6 months',
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

    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: testimonialWithVariousMetrics
      }
    })

    expect(wrapper.text()).toContain('Alumni Participation')
    expect(wrapper.text()).toContain('Job Placements')
  })

  it('applies hover effects correctly', () => {
    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: mockTestimonial
      }
    })

    const card = wrapper.find('.institutional-testimonial-card')
    expect(card.classes()).toContain('hover:shadow-xl')
    expect(card.classes()).toContain('transition-shadow')
  })

  it('handles missing optional data gracefully', () => {
    const minimalTestimonial: InstitutionTestimonial = {
      id: '2',
      quote: 'Great platform!',
      institution: {
        id: 'inst-2',
        name: 'Test College',
        type: 'college',
        logo: '/test-logo.png',
        website: 'https://test.edu',
        alumniCount: 5000,
        establishedYear: 1950,
        location: 'Test City',
        tier: 'professional'
      },
      administrator: {
        id: 'admin-2',
        name: 'John Doe',
        title: 'Alumni Director',
        institution: 'Test College',
        email: 'john@test.edu',
        profileImage: '/john.jpg',
        responsibilities: [],
        experience: 5
      },
      results: [],
      featured: false
    }

    const wrapper = mount(InstitutionalTestimonialCard, {
      props: {
        testimonial: minimalTestimonial
      }
    })

    expect(wrapper.text()).toContain('Test College')
    expect(wrapper.text()).toContain('Great platform!')
    expect(wrapper.text()).toContain('John Doe')
  })
})