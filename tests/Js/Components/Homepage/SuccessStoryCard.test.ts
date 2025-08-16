import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SuccessStoryCard from '@/components/homepage/SuccessStoryCard.vue'
import type { SuccessStory } from '@/types/homepage'

// Mock success story data
const mockSuccessStory: SuccessStory = {
  id: '1',
  title: 'From Junior Developer to Tech Lead',
  summary: 'How networking through our platform helped me advance my career in just 18 months.',
  alumniProfile: {
    id: 'alumni-1',
    name: 'John Smith',
    graduationYear: 2018,
    degree: 'Computer Science',
    currentRole: 'Senior Tech Lead',
    currentCompany: 'TechCorp Inc.',
    industry: 'Technology',
    location: 'San Francisco, CA',
    profileImage: '/images/john-smith.jpg',
    linkedinUrl: 'https://linkedin.com/in/johnsmith',
    careerStage: 'mid_career',
    specialties: ['JavaScript', 'React', 'Node.js'],
    mentorshipAvailable: true
  },
  careerProgression: {
    before: {
      role: 'Junior Developer',
      company: 'StartupCo',
      salary: 65000,
      level: 'Junior',
      responsibilities: ['Bug fixes', 'Basic features']
    },
    after: {
      role: 'Senior Tech Lead',
      company: 'TechCorp Inc.',
      salary: 145000,
      level: 'Senior',
      responsibilities: ['Team leadership', 'Architecture decisions', 'Mentoring']
    },
    timeframe: '18 months',
    keyMilestones: [
      {
        date: new Date('2022-03-15'),
        title: 'Promoted to Mid-Level Developer',
        description: 'Received promotion after successful project delivery',
        type: 'promotion'
      },
      {
        date: new Date('2022-08-20'),
        title: 'Joined TechCorp Inc.',
        description: 'Landed dream job through alumni network referral',
        type: 'job_change'
      },
      {
        date: new Date('2023-01-10'),
        title: 'Promoted to Tech Lead',
        description: 'Promoted to leadership role with team of 8 developers',
        type: 'promotion'
      }
    ]
  },
  platformImpact: {
    connectionsMade: 45,
    mentorsWorkedWith: 3,
    referralsReceived: 7,
    eventsAttended: 12,
    skillsAcquired: ['Leadership', 'System Design', 'Team Management'],
    networkGrowth: 200
  },
  testimonialVideo: 'https://youtube.com/watch?v=example123',
  metrics: [
    {
      type: 'salary_increase',
      value: 123,
      unit: 'percentage',
      timeframe: '18 months',
      verified: true
    },
    {
      type: 'promotion',
      value: 2,
      unit: 'count',
      timeframe: '18 months',
      verified: true
    },
    {
      type: 'network_expansion',
      value: 200,
      unit: 'percentage',
      timeframe: '18 months',
      verified: true
    }
  ],
  industry: 'Technology',
  graduationYear: 2018,
  featured: false,
  tags: ['career-advancement', 'networking', 'technology']
}

describe('SuccessStoryCard', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(SuccessStoryCard, {
      props: {
        story: mockSuccessStory
      }
    })
  })

  describe('Basic Rendering', () => {
    it('renders the success story card', () => {
      expect(wrapper.find('.success-story-card').exists()).toBe(true)
    })

    it('displays alumni profile information', () => {
      expect(wrapper.text()).toContain('John Smith')
      expect(wrapper.text()).toContain('Senior Tech Lead')
      expect(wrapper.text()).toContain('TechCorp Inc.')
      expect(wrapper.text()).toContain('Class of 2018')
      expect(wrapper.text()).toContain('Technology')
    })

    it('displays story title and summary', () => {
      expect(wrapper.text()).toContain('From Junior Developer to Tech Lead')
      expect(wrapper.text()).toContain('How networking through our platform helped me advance my career')
    })

    it('displays career progression preview', () => {
      expect(wrapper.text()).toContain('Junior Developer')
      expect(wrapper.text()).toContain('StartupCo')
      expect(wrapper.text()).toContain('Senior Tech Lead')
      expect(wrapper.text()).toContain('TechCorp Inc.')
      expect(wrapper.text()).toContain('18 months')
    })

    it('displays top metrics', () => {
      expect(wrapper.text()).toContain('+123%')
      expect(wrapper.text()).toContain('Salary Increase')
    })

    it('renders profile image with correct attributes', () => {
      const profileImage = wrapper.find('.profile-image')
      expect(profileImage.exists()).toBe(true)
      expect(profileImage.attributes('src')).toBe('/images/john-smith.jpg')
      expect(profileImage.attributes('alt')).toBe('John Smith profile photo')
    })

    it('renders LinkedIn link when available', () => {
      const linkedinLink = wrapper.find('.linkedin-link')
      expect(linkedinLink.exists()).toBe(true)
      expect(linkedinLink.attributes('href')).toBe('https://linkedin.com/in/johnsmith')
      expect(linkedinLink.attributes('target')).toBe('_blank')
      expect(linkedinLink.attributes('rel')).toBe('noopener noreferrer')
    })
  })

  describe('Expand/Collapse Functionality', () => {
    it('starts in collapsed state', () => {
      expect(wrapper.find('.expanded-content').exists()).toBe(false)
      expect(wrapper.text()).toContain('Read Full Story')
    })

    it('expands when clicked', async () => {
      await wrapper.find('.expand-button').trigger('click')
      
      expect(wrapper.find('.expanded-content').exists()).toBe(true)
      expect(wrapper.text()).toContain('Show Less')
    })

    it('shows expanded content when expanded', async () => {
      await wrapper.find('.expand-button').trigger('click')
      
      // Platform impact
      expect(wrapper.text()).toContain('Platform Impact')
      expect(wrapper.text()).toContain('45')
      expect(wrapper.text()).toContain('Connections Made')
      expect(wrapper.text()).toContain('3')
      expect(wrapper.text()).toContain('Mentors')
      
      // Key milestones
      expect(wrapper.text()).toContain('Key Milestones')
      expect(wrapper.text()).toContain('Promoted to Mid-Level Developer')
      expect(wrapper.text()).toContain('Joined TechCorp Inc.')
      
      // Skills acquired
      expect(wrapper.text()).toContain('Skills Acquired')
      expect(wrapper.text()).toContain('Leadership')
      expect(wrapper.text()).toContain('System Design')
    })

    it('collapses when clicked again', async () => {
      // First expand
      await wrapper.find('.expand-button').trigger('click')
      expect(wrapper.find('.expanded-content').exists()).toBe(true)
      
      // Then collapse
      await wrapper.find('.expand-button').trigger('click')
      expect(wrapper.find('.expanded-content').exists()).toBe(false)
      expect(wrapper.text()).toContain('Read Full Story')
    })

    it('supports keyboard navigation', async () => {
      await wrapper.trigger('keydown.enter')
      expect(wrapper.find('.expanded-content').exists()).toBe(true)
      
      await wrapper.trigger('keydown.space')
      expect(wrapper.find('.expanded-content').exists()).toBe(false)
    })
  })

  describe('Video Testimonial', () => {
    it('shows video play button when video is available', async () => {
      await wrapper.find('.expand-button').trigger('click')
      
      const videoButton = wrapper.find('.video-play-button')
      expect(videoButton.exists()).toBe(true)
      expect(videoButton.text()).toContain('Watch Video Testimonial')
    })

    it('emits play-video event when video button is clicked', async () => {
      await wrapper.find('.expand-button').trigger('click')
      await wrapper.find('.video-play-button').trigger('click')
      
      expect(wrapper.emitted('playVideo')).toBeTruthy()
      expect(wrapper.emitted('playVideo')[0]).toEqual(['https://youtube.com/watch?v=example123'])
    })

    it('does not show video button when no video is available', async () => {
      const storyWithoutVideo = { ...mockSuccessStory, testimonialVideo: undefined }
      wrapper = mount(SuccessStoryCard, {
        props: { story: storyWithoutVideo }
      })
      
      await wrapper.find('.expand-button').trigger('click')
      expect(wrapper.find('.video-play-button').exists()).toBe(false)
    })
  })

  describe('Social Sharing', () => {
    beforeEach(async () => {
      await wrapper.find('.expand-button').trigger('click')
    })

    it('shows social sharing buttons when expanded', () => {
      expect(wrapper.find('.social-sharing').exists()).toBe(true)
      expect(wrapper.find('.share-button.linkedin').exists()).toBe(true)
      expect(wrapper.find('.share-button.twitter').exists()).toBe(true)
      expect(wrapper.find('.share-button.copy').exists()).toBe(true)
    })

    it('emits share event for LinkedIn', async () => {
      await wrapper.find('.share-button.linkedin').trigger('click')
      
      expect(wrapper.emitted('share')).toBeTruthy()
      expect(wrapper.emitted('share')[0]).toEqual(['linkedin', mockSuccessStory])
    })

    it('emits share event for Twitter', async () => {
      await wrapper.find('.share-button.twitter').trigger('click')
      
      expect(wrapper.emitted('share')).toBeTruthy()
      expect(wrapper.emitted('share')[0]).toEqual(['twitter', mockSuccessStory])
    })

    it('emits share event for copy link', async () => {
      await wrapper.find('.share-button.copy').trigger('click')
      
      expect(wrapper.emitted('share')).toBeTruthy()
      expect(wrapper.emitted('share')[0]).toEqual(['copy', mockSuccessStory])
    })
  })

  describe('Featured Story Styling', () => {
    it('applies featured styling when story is featured', () => {
      const featuredStory = { ...mockSuccessStory, featured: true }
      wrapper = mount(SuccessStoryCard, {
        props: { story: featuredStory }
      })
      
      expect(wrapper.find('.success-story-card').classes()).toContain('featured')
    })

    it('does not apply featured styling for regular stories', () => {
      expect(wrapper.find('.success-story-card').classes()).not.toContain('featured')
    })
  })

  describe('Metric Formatting', () => {
    it('formats percentage metrics correctly', () => {
      expect(wrapper.text()).toContain('+123%')
    })

    it('formats count metrics correctly', () => {
      const storyWithCountMetric = {
        ...mockSuccessStory,
        metrics: [
          {
            type: 'promotion',
            value: 2,
            unit: 'count',
            timeframe: '18 months',
            verified: true
          }
        ]
      }
      
      wrapper = mount(SuccessStoryCard, {
        props: { story: storyWithCountMetric }
      })
      
      expect(wrapper.text()).toContain('2')
    })

    it('formats dollar metrics correctly', () => {
      const storyWithDollarMetric = {
        ...mockSuccessStory,
        metrics: [
          {
            type: 'salary_increase',
            value: 80000,
            unit: 'dollar',
            timeframe: '18 months',
            verified: true
          }
        ]
      }
      
      wrapper = mount(SuccessStoryCard, {
        props: { story: storyWithDollarMetric }
      })
      
      expect(wrapper.text()).toContain('$80,000')
    })
  })

  describe('Accessibility', () => {
    it('has proper ARIA attributes', () => {
      const card = wrapper.find('.success-story-card')
      expect(card.attributes('role')).toBe('button')
      expect(card.attributes('aria-expanded')).toBe('false')
      expect(card.attributes('tabindex')).toBe('0')
    })

    it('updates ARIA attributes when expanded', async () => {
      await wrapper.find('.expand-button').trigger('click')
      
      const card = wrapper.find('.success-story-card')
      expect(card.attributes('aria-expanded')).toBe('true')
    })

    it('has proper alt text for images', () => {
      const profileImage = wrapper.find('.profile-image')
      expect(profileImage.attributes('alt')).toBe('John Smith profile photo')
    })

    it('has proper aria-labels for buttons', () => {
      const linkedinLink = wrapper.find('.linkedin-link')
      expect(linkedinLink.attributes('aria-label')).toBe('View LinkedIn profile')
      
      const expandButton = wrapper.find('.expand-button')
      expect(expandButton.attributes('aria-label')).toBe('Expand story')
    })
  })

  describe('Date Formatting', () => {
    it('formats milestone dates correctly', async () => {
      await wrapper.find('.expand-button').trigger('click')
      
      // Check if dates are formatted as "Mar 2022" format
      expect(wrapper.text()).toContain('Mar 2022')
      expect(wrapper.text()).toContain('Aug 2022')
      expect(wrapper.text()).toContain('Jan 2023')
    })
  })

  describe('Error Handling', () => {
    it('handles missing LinkedIn URL gracefully', () => {
      const storyWithoutLinkedIn = {
        ...mockSuccessStory,
        alumniProfile: {
          ...mockSuccessStory.alumniProfile,
          linkedinUrl: undefined
        }
      }
      
      wrapper = mount(SuccessStoryCard, {
        props: { story: storyWithoutLinkedIn }
      })
      
      expect(wrapper.find('.linkedin-link').exists()).toBe(false)
    })

    it('handles empty skills array gracefully', async () => {
      const storyWithoutSkills = {
        ...mockSuccessStory,
        platformImpact: {
          ...mockSuccessStory.platformImpact,
          skillsAcquired: []
        }
      }
      
      wrapper = mount(SuccessStoryCard, {
        props: { story: storyWithoutSkills }
      })
      
      await wrapper.find('.expand-button').trigger('click')
      expect(wrapper.find('.skills-acquired').exists()).toBe(false)
    })

    it('handles missing metrics gracefully', () => {
      const storyWithoutMetrics = {
        ...mockSuccessStory,
        metrics: []
      }
      
      wrapper = mount(SuccessStoryCard, {
        props: { story: storyWithoutMetrics }
      })
      
      expect(wrapper.find('.metrics-preview').exists()).toBe(true)
      // Should show empty metrics preview
    })
  })
})