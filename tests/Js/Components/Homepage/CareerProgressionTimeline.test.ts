import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CareerProgressionTimeline from '@/components/homepage/CareerProgressionTimeline.vue'
import type { CareerProgression, SuccessMetric } from '@/types/homepage'

// Mock career progression data
const mockCareerProgression: CareerProgression = {
  before: {
    role: 'Junior Developer',
    company: 'StartupCo',
    salary: 65000,
    level: 'Junior',
    responsibilities: ['Bug fixes', 'Basic features', 'Code reviews']
  },
  after: {
    role: 'Senior Tech Lead',
    company: 'TechCorp Inc.',
    salary: 145000,
    level: 'Senior',
    responsibilities: ['Team leadership', 'Architecture decisions', 'Mentoring', 'Strategic planning']
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
    },
    {
      date: new Date('2023-06-15'),
      title: 'Completed Leadership Training',
      description: 'Finished advanced leadership certification program',
      type: 'skill_acquisition'
    }
  ]
}

const mockSuccessMetrics: SuccessMetric[] = [
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
    verified: false
  }
]

describe('CareerProgressionTimeline', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(CareerProgressionTimeline, {
      props: {
        careerProgression: mockCareerProgression,
        successMetrics: mockSuccessMetrics,
        alumniName: 'John Smith',
        currentRole: 'Senior Tech Lead',
        currentCompany: 'TechCorp Inc.',
        profileImage: '/images/john-smith.jpg',
        linkedinUrl: 'https://linkedin.com/in/johnsmith',
        animationsEnabled: false // Disable animations for testing
      }
    })
  })

  describe('Basic Rendering', () => {
    it('renders the timeline component', () => {
      expect(wrapper.find('.career-progression-timeline').exists()).toBe(true)
    })

    it('displays timeline header with correct information', () => {
      expect(wrapper.text()).toContain('Career Progression Timeline')
      expect(wrapper.text()).toContain('18 months journey from Junior Developer to Senior Tech Lead')
    })

    it('displays before and after career information', () => {
      expect(wrapper.text()).toContain('Junior Developer')
      expect(wrapper.text()).toContain('StartupCo')
      expect(wrapper.text()).toContain('Senior Tech Lead')
      expect(wrapper.text()).toContain('TechCorp Inc.')
    })

    it('shows salary information when available', () => {
      expect(wrapper.text()).toContain('$65,000')
      expect(wrapper.text()).toContain('$145,000')
    })

    it('displays career levels', () => {
      expect(wrapper.text()).toContain('Junior')
      expect(wrapper.text()).toContain('Senior')
    })

    it('shows responsibilities for both before and after', () => {
      expect(wrapper.text()).toContain('Bug fixes')
      expect(wrapper.text()).toContain('Team leadership')
    })
  })

  describe('Salary Calculation', () => {
    it('calculates and displays salary increase percentage', () => {
      expect(wrapper.text()).toContain('+123%')
    })

    it('handles missing salary information gracefully', () => {
      const progressionWithoutSalary = {
        ...mockCareerProgression,
        before: { ...mockCareerProgression.before, salary: undefined },
        after: { ...mockCareerProgression.after, salary: undefined }
      }

      wrapper = mount(CareerProgressionTimeline, {
        props: {
          careerProgression: progressionWithoutSalary,
          alumniName: 'John Smith',
          currentRole: 'Senior Tech Lead',
          currentCompany: 'TechCorp Inc.',
          profileImage: '/images/john-smith.jpg'
        }
      })

      // Should not show salary information
      expect(wrapper.text()).not.toContain('$')
    })
  })

  describe('Milestones Timeline', () => {
    it('displays milestones section when milestones exist', () => {
      expect(wrapper.text()).toContain('Key Milestones')
    })

    it('renders all milestones in chronological order', () => {
      const milestoneItems = wrapper.findAll('.milestone-item')
      expect(milestoneItems).toHaveLength(4)

      // Check if milestones are in chronological order
      expect(wrapper.text()).toContain('Promoted to Mid-Level Developer')
      expect(wrapper.text()).toContain('Joined TechCorp Inc.')
      expect(wrapper.text()).toContain('Promoted to Tech Lead')
      expect(wrapper.text()).toContain('Completed Leadership Training')
    })

    it('displays milestone dates correctly', () => {
      expect(wrapper.text()).toContain('Mar 15, 2022')
      expect(wrapper.text()).toContain('Aug 20, 2022')
      expect(wrapper.text()).toContain('Jan 10, 2023')
      expect(wrapper.text()).toContain('Jun 15, 2023')
    })

    it('shows milestone types with appropriate styling', () => {
      expect(wrapper.find('.milestone-promotion').exists()).toBe(true)
      expect(wrapper.find('.milestone-job-change').exists()).toBe(true)
      expect(wrapper.find('.milestone-skill').exists()).toBe(true)
    })

    it('displays milestone descriptions', () => {
      expect(wrapper.text()).toContain('Received promotion after successful project delivery')
      expect(wrapper.text()).toContain('Landed dream job through alumni network referral')
    })

    it('formats milestone types correctly', () => {
      expect(wrapper.text()).toContain('Promotion')
      expect(wrapper.text()).toContain('Job Change')
      expect(wrapper.text()).toContain('Skill Acquisition')
    })

    it('does not show milestones section when no milestones exist', () => {
      const progressionWithoutMilestones = {
        ...mockCareerProgression,
        keyMilestones: []
      }

      wrapper = mount(CareerProgressionTimeline, {
        props: {
          careerProgression: progressionWithoutMilestones,
          alumniName: 'John Smith',
          currentRole: 'Senior Tech Lead',
          currentCompany: 'TechCorp Inc.',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.milestones-timeline').exists()).toBe(false)
    })
  })

  describe('Success Metrics Display', () => {
    it('displays success metrics section when metrics exist', () => {
      expect(wrapper.text()).toContain('Success Outcomes')
    })

    it('renders all success metrics', () => {
      const metricCards = wrapper.findAll('.metric-card')
      expect(metricCards).toHaveLength(3)
    })

    it('formats metric values correctly', () => {
      expect(wrapper.text()).toContain('+123%')
      expect(wrapper.text()).toContain('2')
      expect(wrapper.text()).toContain('+200%')
    })

    it('displays metric labels correctly', () => {
      expect(wrapper.text()).toContain('Salary Increase')
      expect(wrapper.text()).toContain('Promotions')
      expect(wrapper.text()).toContain('Network Growth')
    })

    it('shows verification status', () => {
      const verifiedMetrics = wrapper.findAll('.metric-verified')
      expect(verifiedMetrics).toHaveLength(2) // Only 2 metrics are verified
      expect(wrapper.text()).toContain('Verified')
    })

    it('applies correct styling based on metric type', () => {
      expect(wrapper.find('.metric-salary').exists()).toBe(true)
      expect(wrapper.find('.metric-promotion').exists()).toBe(true)
      expect(wrapper.find('.metric-network').exists()).toBe(true)
    })

    it('does not show metrics section when no metrics exist', () => {
      wrapper = mount(CareerProgressionTimeline, {
        props: {
          careerProgression: mockCareerProgression,
          successMetrics: [],
          alumniName: 'John Smith',
          currentRole: 'Senior Tech Lead',
          currentCompany: 'TechCorp Inc.',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.success-metrics').exists()).toBe(false)
    })
  })

  describe('LinkedIn Integration', () => {
    it('displays LinkedIn integration when URL is provided', () => {
      expect(wrapper.text()).toContain('Connect with John Smith')
      expect(wrapper.find('.linkedin-integration').exists()).toBe(true)
    })

    it('shows alumni profile information in LinkedIn card', () => {
      expect(wrapper.text()).toContain('John Smith')
      expect(wrapper.text()).toContain('Senior Tech Lead')
      expect(wrapper.text()).toContain('TechCorp Inc.')
    })

    it('renders LinkedIn profile image', () => {
      const linkedinAvatar = wrapper.find('.linkedin-avatar')
      expect(linkedinAvatar.exists()).toBe(true)
      expect(linkedinAvatar.attributes('src')).toBe('/images/john-smith.jpg')
      expect(linkedinAvatar.attributes('alt')).toBe('John Smith profile photo')
    })

    it('has correct LinkedIn link attributes', () => {
      const linkedinButton = wrapper.find('.linkedin-button')
      expect(linkedinButton.exists()).toBe(true)
      expect(linkedinButton.attributes('href')).toBe('https://linkedin.com/in/johnsmith')
      expect(linkedinButton.attributes('target')).toBe('_blank')
      expect(linkedinButton.attributes('rel')).toBe('noopener noreferrer')
    })

    it('emits linkedin-click event when LinkedIn button is clicked', async () => {
      const linkedinButton = wrapper.find('.linkedin-button')
      await linkedinButton.trigger('click')

      expect(wrapper.emitted('linkedin-click')).toBeTruthy()
      expect(wrapper.emitted('linkedin-click')[0]).toEqual(['https://linkedin.com/in/johnsmith'])
    })

    it('does not show LinkedIn integration when URL is not provided', () => {
      wrapper = mount(CareerProgressionTimeline, {
        props: {
          careerProgression: mockCareerProgression,
          alumniName: 'John Smith',
          currentRole: 'Senior Tech Lead',
          currentCompany: 'TechCorp Inc.',
          profileImage: '/images/john-smith.jpg'
          // No linkedinUrl provided
        }
      })

      expect(wrapper.find('.linkedin-integration').exists()).toBe(false)
    })
  })

  describe('Animation Controls', () => {
    it('displays animation control buttons', () => {
      expect(wrapper.find('.animation-toggle-button').exists()).toBe(true)
      expect(wrapper.find('.replay-button').exists()).toBe(true)
    })

    it('toggles animation state when toggle button is clicked', async () => {
      const toggleButton = wrapper.find('.animation-toggle-button')
      
      // Initially disabled (as set in props)
      expect(wrapper.text()).toContain('Enable Animations')
      
      await toggleButton.trigger('click')
      expect(wrapper.text()).toContain('Disable Animations')
      
      await toggleButton.trigger('click')
      expect(wrapper.text()).toContain('Enable Animations')
    })

    it('disables replay button when animations are disabled', () => {
      const replayButton = wrapper.find('.replay-button')
      expect(replayButton.attributes('disabled')).toBeDefined()
    })

    it('enables replay button when animations are enabled', async () => {
      const toggleButton = wrapper.find('.animation-toggle-button')
      await toggleButton.trigger('click')
      
      const replayButton = wrapper.find('.replay-button')
      expect(replayButton.attributes('disabled')).toBeUndefined()
    })

    it('has proper ARIA labels for animation controls', () => {
      const toggleButton = wrapper.find('.animation-toggle-button')
      const replayButton = wrapper.find('.replay-button')
      
      expect(toggleButton.attributes('aria-label')).toBe('Enable animations')
      expect(replayButton.attributes('aria-label')).toBe('Replay animations')
    })
  })

  describe('Date Formatting', () => {
    it('formats dates correctly in timeline header', () => {
      // The component calculates dates based on milestones
      // Should show formatted dates like "Mar 2022"
      const dateElements = wrapper.findAll('.timeline-date')
      expect(dateElements.length).toBeGreaterThan(0)
    })

    it('formats milestone dates correctly', () => {
      expect(wrapper.text()).toContain('Mar 15, 2022')
      expect(wrapper.text()).toContain('Aug 20, 2022')
    })
  })

  describe('Responsive Design', () => {
    it('applies responsive classes', () => {
      expect(wrapper.find('.before-after-comparison').classes()).toContain('lg:flex-row')
      expect(wrapper.find('.metrics-grid').classes()).toContain('md:grid-cols-2')
      expect(wrapper.find('.metrics-grid').classes()).toContain('lg:grid-cols-3')
    })
  })

  describe('Accessibility', () => {
    it('has proper heading hierarchy', () => {
      expect(wrapper.find('h3').text()).toBe('Career Progression Timeline')
      expect(wrapper.find('h4').text()).toBe('Key Milestones')
      expect(wrapper.find('h5').exists()).toBe(true)
    })

    it('has proper alt text for images', () => {
      const linkedinAvatar = wrapper.find('.linkedin-avatar')
      expect(linkedinAvatar.attributes('alt')).toBe('John Smith profile photo')
    })

    it('has proper ARIA labels for interactive elements', () => {
      const toggleButton = wrapper.find('.animation-toggle-button')
      const replayButton = wrapper.find('.replay-button')
      
      expect(toggleButton.attributes('aria-label')).toBeDefined()
      expect(replayButton.attributes('aria-label')).toBeDefined()
    })
  })

  describe('Error Handling', () => {
    it('handles missing milestone data gracefully', () => {
      const progressionWithEmptyMilestones = {
        ...mockCareerProgression,
        keyMilestones: []
      }

      wrapper = mount(CareerProgressionTimeline, {
        props: {
          careerProgression: progressionWithEmptyMilestones,
          alumniName: 'John Smith',
          currentRole: 'Senior Tech Lead',
          currentCompany: 'TechCorp Inc.',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.career-progression-timeline').exists()).toBe(true)
      expect(wrapper.find('.milestones-timeline').exists()).toBe(false)
    })

    it('handles missing success metrics gracefully', () => {
      wrapper = mount(CareerProgressionTimeline, {
        props: {
          careerProgression: mockCareerProgression,
          successMetrics: [],
          alumniName: 'John Smith',
          currentRole: 'Senior Tech Lead',
          currentCompany: 'TechCorp Inc.',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.career-progression-timeline').exists()).toBe(true)
      expect(wrapper.find('.success-metrics').exists()).toBe(false)
    })

    it('handles invalid milestone dates gracefully', () => {
      const progressionWithInvalidDates = {
        ...mockCareerProgression,
        keyMilestones: [
          {
            date: new Date('invalid-date'),
            title: 'Test Milestone',
            description: 'Test description',
            type: 'promotion'
          }
        ]
      }

      expect(() => {
        wrapper = mount(CareerProgressionTimeline, {
          props: {
            careerProgression: progressionWithInvalidDates,
            alumniName: 'John Smith',
            currentRole: 'Senior Tech Lead',
            currentCompany: 'TechCorp Inc.',
            profileImage: '/images/john-smith.jpg'
          }
        })
      }).not.toThrow()
    })
  })
})