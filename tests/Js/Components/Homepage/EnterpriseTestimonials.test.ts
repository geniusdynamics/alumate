import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import EnterpriseTestimonials from '@/components/homepage/EnterpriseTestimonials.vue'
import InstitutionalTestimonialCard from '@/components/homepage/InstitutionalTestimonialCard.vue'
import InstitutionalCaseStudy from '@/components/homepage/InstitutionalCaseStudy.vue'
import InstitutionalVideoModal from '@/components/homepage/InstitutionalVideoModal.vue'
import type { InstitutionTestimonial, InstitutionalCaseStudy as CaseStudyType } from '@/types/homepage'

const mockTestimonials: InstitutionTestimonial[] = [
  {
    id: '1',
    quote: 'Outstanding platform for alumni engagement.',
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
      responsibilities: ['Alumni Engagement'],
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
      }
    ],
    videoTestimonial: 'https://example.com/video1.mp4',
    featured: false
  },
  {
    id: '2',
    quote: 'Transformed our alumni community.',
    institution: {
      id: 'inst-2',
      name: 'MIT',
      type: 'university',
      logo: '/images/mit-logo.png',
      website: 'https://mit.edu',
      alumniCount: 150000,
      establishedYear: 1861,
      location: 'Cambridge, MA',
      tier: 'enterprise'
    },
    administrator: {
      id: 'admin-2',
      name: 'Dr. Michael Chen',
      title: 'VP Alumni Affairs',
      institution: 'MIT',
      email: 'michael.chen@mit.edu',
      profileImage: '/images/michael-chen.jpg',
      responsibilities: ['Strategy'],
      experience: 10
    },
    results: [
      {
        metric: 'participation',
        beforeValue: 30,
        afterValue: 80,
        improvementPercentage: 167,
        timeframe: '18 months',
        verified: true
      }
    ],
    featured: false
  }
]

const mockFeaturedTestimonial: InstitutionTestimonial = {
  id: 'featured',
  quote: 'This platform has revolutionized how we connect with our alumni.',
  institution: {
    id: 'inst-featured',
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
    id: 'admin-featured',
    name: 'Dr. Emily Rodriguez',
    title: 'Chief Alumni Officer',
    institution: 'Harvard University',
    email: 'emily.rodriguez@harvard.edu',
    profileImage: '/images/emily-rodriguez.jpg',
    responsibilities: ['Alumni Strategy', 'Digital Innovation'],
    experience: 20
  },
  results: [
    {
      metric: 'engagement',
      beforeValue: 20,
      afterValue: 85,
      improvementPercentage: 325,
      timeframe: '24 months',
      verified: true
    },
    {
      metric: 'donations',
      beforeValue: 5000000,
      afterValue: 15000000,
      improvementPercentage: 200,
      timeframe: '24 months',
      verified: true
    },
    {
      metric: 'events',
      beforeValue: 50,
      afterValue: 200,
      improvementPercentage: 300,
      timeframe: '24 months',
      verified: true
    }
  ],
  videoTestimonial: 'https://example.com/featured-video.mp4',
  featured: true
}

const mockCaseStudies: CaseStudyType[] = [
  {
    id: '1',
    title: 'Digital Transformation at Yale',
    institutionName: 'Yale University',
    institutionType: 'university',
    challenge: 'Low digital engagement among alumni.',
    solution: 'Comprehensive digital platform implementation.',
    implementation: [
      {
        phase: 'Planning',
        duration: '4 weeks',
        activities: ['Strategy development'],
        deliverables: ['Project plan'],
        milestones: ['Kickoff']
      }
    ],
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
    timeline: '16 weeks',
    alumniCount: 180000,
    engagementIncrease: 300,
    featured: true
  }
]

// Mock child components
vi.mock('@/components/homepage/InstitutionalTestimonialCard.vue', () => ({
  default: {
    name: 'InstitutionalTestimonialCard',
    props: ['testimonial'],
    emits: ['play-video'],
    template: '<div class="testimonial-card">{{ testimonial.administrator.name }}</div>'
  }
}))

vi.mock('@/components/homepage/InstitutionalCaseStudy.vue', () => ({
  default: {
    name: 'InstitutionalCaseStudy',
    props: ['caseStudy'],
    emits: ['request-demo'],
    template: '<div class="case-study">{{ caseStudy.title }}</div>'
  }
}))

vi.mock('@/components/homepage/InstitutionalVideoModal.vue', () => ({
  default: {
    name: 'InstitutionalVideoModal',
    props: ['isOpen', 'videoUrl', 'testimonial'],
    emits: ['close', 'request-demo'],
    template: '<div v-if="isOpen" class="video-modal">Video Modal</div>'
  }
}))

describe('EnterpriseTestimonials', () => {
  it('renders section header correctly', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    expect(wrapper.text()).toContain('Trusted by Leading Institutions')
    expect(wrapper.text()).toContain('Universities, colleges, and organizations worldwide trust our platform')
  })

  it('displays featured testimonial when provided', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        featuredTestimonial: mockFeaturedTestimonial,
        caseStudies: mockCaseStudies
      }
    })

    expect(wrapper.text()).toContain('Harvard University')
    expect(wrapper.text()).toContain('Dr. Emily Rodriguez')
    expect(wrapper.text()).toContain('Chief Alumni Officer')
    expect(wrapper.text()).toContain('This platform has revolutionized how we connect with our alumni.')
    expect(wrapper.text()).toContain('400K Alumni Network')
  })

  it('displays featured testimonial results metrics', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        featuredTestimonial: mockFeaturedTestimonial,
        caseStudies: mockCaseStudies
      }
    })

    // Should show first 3 results
    expect(wrapper.text()).toContain('+325%')
    expect(wrapper.text()).toContain('+200%')
    expect(wrapper.text()).toContain('+300%')
  })

  it('renders testimonial cards for each testimonial', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    const testimonialCards = wrapper.findAllComponents({ name: 'InstitutionalTestimonialCard' })
    expect(testimonialCards).toHaveLength(2)
  })

  it('renders case study components when provided', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    expect(wrapper.text()).toContain('Success Stories')
    expect(wrapper.text()).toContain('Detailed case studies showing real results')
    
    const caseStudyComponents = wrapper.findAllComponents({ name: 'InstitutionalCaseStudy' })
    expect(caseStudyComponents).toHaveLength(1)
  })

  it('hides case studies section when no case studies provided', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: []
      }
    })

    expect(wrapper.text()).not.toContain('Success Stories')
  })

  it('displays call to action section', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    expect(wrapper.text()).toContain('Ready to Transform Your Alumni Engagement?')
    expect(wrapper.text()).toContain('Join hundreds of institutions that have increased alumni participation')
    
    const requestDemoButton = wrapper.find('button:contains("Request Demo")')
    const downloadButton = wrapper.find('button:contains("Download Case Studies")')
    
    expect(requestDemoButton.exists()).toBe(true)
    expect(downloadButton.exists()).toBe(true)
  })

  it('emits request-demo event when demo button is clicked', async () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    const requestDemoButton = wrapper.find('button:contains("Request Demo")')
    await requestDemoButton.trigger('click')

    expect(wrapper.emitted('request-demo')).toBeTruthy()
  })

  it('emits download-case-studies event when download button is clicked', async () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    const downloadButton = wrapper.find('button:contains("Download Case Studies")')
    await downloadButton.trigger('click')

    expect(wrapper.emitted('download-case-studies')).toBeTruthy()
  })

  it('opens video modal when testimonial card emits play-video', async () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    const testimonialCard = wrapper.findComponent({ name: 'InstitutionalTestimonialCard' })
    await testimonialCard.vm.$emit('play-video', 'https://example.com/video1.mp4')

    const videoModal = wrapper.findComponent({ name: 'InstitutionalVideoModal' })
    expect(videoModal.props('isOpen')).toBe(true)
    expect(videoModal.props('videoUrl')).toBe('https://example.com/video1.mp4')
  })

  it('closes video modal when modal emits close', async () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    // Open modal first
    const testimonialCard = wrapper.findComponent({ name: 'InstitutionalTestimonialCard' })
    await testimonialCard.vm.$emit('play-video', 'https://example.com/video1.mp4')

    // Close modal
    const videoModal = wrapper.findComponent({ name: 'InstitutionalVideoModal' })
    await videoModal.vm.$emit('close')

    expect(videoModal.props('isOpen')).toBe(false)
  })

  it('forwards demo request from case study component', async () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    const caseStudyComponent = wrapper.findComponent({ name: 'InstitutionalCaseStudy' })
    const testData = { test: 'data' }
    await caseStudyComponent.vm.$emit('request-demo', testData)

    expect(wrapper.emitted('request-demo')).toBeTruthy()
    expect(wrapper.emitted('request-demo')?.[0]).toEqual([testData])
  })

  it('forwards demo request from video modal', async () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    // Open modal first
    const testimonialCard = wrapper.findComponent({ name: 'InstitutionalTestimonialCard' })
    await testimonialCard.vm.$emit('play-video', 'https://example.com/video1.mp4')

    // Emit demo request from modal
    const videoModal = wrapper.findComponent({ name: 'InstitutionalVideoModal' })
    const testData = { test: 'modal-data' }
    await videoModal.vm.$emit('request-demo', testData)

    expect(wrapper.emitted('request-demo')).toBeTruthy()
    expect(wrapper.emitted('request-demo')?.[0]).toEqual([testData])
  })

  it('finds correct testimonial for video modal', async () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        featuredTestimonial: mockFeaturedTestimonial,
        caseStudies: mockCaseStudies
      }
    })

    // Test with regular testimonial
    const testimonialCard = wrapper.findComponent({ name: 'InstitutionalTestimonialCard' })
    await testimonialCard.vm.$emit('play-video', 'https://example.com/video1.mp4')

    const videoModal = wrapper.findComponent({ name: 'InstitutionalVideoModal' })
    expect(videoModal.props('testimonial')).toEqual(mockTestimonials[0])

    // Test with featured testimonial
    await testimonialCard.vm.$emit('play-video', 'https://example.com/featured-video.mp4')
    expect(videoModal.props('testimonial')).toEqual(mockFeaturedTestimonial)
  })

  it('formats alumni count correctly', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        featuredTestimonial: mockFeaturedTestimonial,
        caseStudies: mockCaseStudies
      }
    })

    expect(wrapper.text()).toContain('400K Alumni Network')
  })

  it('formats metric labels correctly', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        featuredTestimonial: mockFeaturedTestimonial,
        caseStudies: mockCaseStudies
      }
    })

    expect(wrapper.text()).toContain('Engagement')
    expect(wrapper.text()).toContain('Donations')
    expect(wrapper.text()).toContain('Events')
  })

  it('applies correct CSS classes', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: mockTestimonials,
        caseStudies: mockCaseStudies
      }
    })

    const section = wrapper.find('.enterprise-testimonials')
    expect(section.exists()).toBe(true)
    expect(section.classes()).toContain('py-16')
    expect(section.classes()).toContain('bg-white')
  })

  it('handles empty testimonials array', () => {
    const wrapper = mount(EnterpriseTestimonials, {
      props: {
        testimonials: [],
        caseStudies: []
      }
    })

    expect(wrapper.text()).toContain('Trusted by Leading Institutions')
    const testimonialCards = wrapper.findAllComponents({ name: 'InstitutionalTestimonialCard' })
    expect(testimonialCards).toHaveLength(0)
  })
})