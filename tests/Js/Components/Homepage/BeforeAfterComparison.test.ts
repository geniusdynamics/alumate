import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BeforeAfterComparison from '@/components/homepage/BeforeAfterComparison.vue'
import type { CareerProgression, SuccessMetric, PlatformImpact } from '@/types/homepage'

// Mock data
const mockCareerProgression: CareerProgression = {
  before: {
    role: 'Junior Developer',
    company: 'StartupCo',
    salary: 65000,
    level: 'Junior',
    responsibilities: ['Bug fixes', 'Basic features', 'Code reviews', 'Documentation']
  },
  after: {
    role: 'Senior Tech Lead',
    company: 'TechCorp Inc.',
    salary: 145000,
    level: 'Senior',
    responsibilities: ['Team leadership', 'Architecture decisions', 'Mentoring', 'Strategic planning', 'Code reviews']
  },
  timeframe: '18 months',
  keyMilestones: []
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

const mockPlatformImpact: PlatformImpact = {
  connectionsMade: 45,
  mentorsWorkedWith: 3,
  referralsReceived: 7,
  eventsAttended: 12,
  skillsAcquired: ['Leadership', 'System Design', 'Team Management', 'Strategic Planning'],
  networkGrowth: 200
}

describe('BeforeAfterComparison', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(BeforeAfterComparison, {
      props: {
        careerProgression: mockCareerProgression,
        successMetrics: mockSuccessMetrics,
        platformImpact: mockPlatformImpact,
        alumniName: 'John Smith',
        profileImage: '/images/john-smith.jpg',
        startDate: new Date('2022-01-01'),
        endDate: new Date('2023-07-01')
      }
    })
  })

  describe('Basic Rendering', () => {
    it('renders the comparison component', () => {
      expect(wrapper.find('.before-after-comparison').exists()).toBe(true)
    })

    it('displays comparison header', () => {
      expect(wrapper.text()).toContain('Career Transformation')
      expect(wrapper.text()).toContain('18 months journey of professional growth')
    })

    it('displays before and after sections', () => {
      expect(wrapper.find('.before-section').exists()).toBe(true)
      expect(wrapper.find('.after-section').exists()).toBe(true)
    })

    it('shows transformation arrow between sections', () => {
      expect(wrapper.find('.transformation-arrow').exists()).toBe(true)
    })
  })

  describe('Before Section', () => {
    it('displays before career information', () => {
      expect(wrapper.text()).toContain('Before')
      expect(wrapper.text()).toContain('Junior Developer')
      expect(wrapper.text()).toContain('StartupCo')
    })

    it('shows before salary when available', () => {
      expect(wrapper.text()).toContain('$65,000')
    })

    it('displays before career level', () => {
      expect(wrapper.text()).toContain('Junior')
    })

    it('shows before responsibilities count', () => {
      expect(wrapper.text()).toContain('4') // 4 responsibilities
    })

    it('displays key responsibilities with truncation', () => {
      expect(wrapper.text()).toContain('Bug fixes')
      expect(wrapper.text()).toContain('Basic features')
      expect(wrapper.text()).toContain('Code reviews')
      expect(wrapper.text()).toContain('+1 more') // 4 total, showing 3 + more
    })

    it('shows profile image with correct attributes', () => {
      const beforeAvatar = wrapper.find('.before-section .profile-avatar')
      expect(beforeAvatar.exists()).toBe(true)
      expect(beforeAvatar.attributes('src')).toBe('/images/john-smith.jpg')
      expect(beforeAvatar.attributes('alt')).toBe('John Smith profile photo')
    })

    it('displays experience badge', () => {
      const beforeBadge = wrapper.find('.before-badge')
      expect(beforeBadge.exists()).toBe(true)
      expect(beforeBadge.text()).toBe('Junior')
    })
  })

  describe('After Section', () => {
    it('displays after career information', () => {
      expect(wrapper.text()).toContain('After')
      expect(wrapper.text()).toContain('Senior Tech Lead')
      expect(wrapper.text()).toContain('TechCorp Inc.')
    })

    it('shows after salary when available', () => {
      expect(wrapper.text()).toContain('$145,000')
    })

    it('displays after career level', () => {
      expect(wrapper.text()).toContain('Senior')
    })

    it('shows after responsibilities count', () => {
      expect(wrapper.text()).toContain('5') // 5 responsibilities
    })

    it('displays key responsibilities with truncation', () => {
      expect(wrapper.text()).toContain('Team leadership')
      expect(wrapper.text()).toContain('Architecture decisions')
      expect(wrapper.text()).toContain('Mentoring')
      expect(wrapper.text()).toContain('+2 more') // 5 total, showing 3 + more
    })

    it('shows experience badge with after styling', () => {
      const afterBadge = wrapper.find('.after-badge')
      expect(afterBadge.exists()).toBe(true)
      expect(afterBadge.text()).toBe('Senior')
    })
  })

  describe('Transformation Arrow', () => {
    it('displays transformation statistics', () => {
      expect(wrapper.text()).toContain('+123%') // Salary increase
      expect(wrapper.text()).toContain('18 months') // Duration
    })

    it('calculates and displays level improvement', () => {
      // Should show level improvement from Junior to Senior
      expect(wrapper.text()).toContain('+2') // Junior to Senior is +2 levels
    })

    it('handles cases where level improvement cannot be calculated', () => {
      const progressionWithCustomLevels = {
        ...mockCareerProgression,
        before: { ...mockCareerProgression.before, level: 'Custom Level 1' },
        after: { ...mockCareerProgression.after, level: 'Custom Level 2' }
      }

      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: progressionWithCustomLevels,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
        }
      })

      // Should not show level improvement for custom levels
      expect(wrapper.text()).not.toContain('Levels')
    })
  })

  describe('Salary Calculations', () => {
    it('calculates salary increase percentage correctly', () => {
      // (145000 - 65000) / 65000 * 100 = 123.08% (rounded to 123%)
      expect(wrapper.text()).toContain('+123%')
    })

    it('handles missing salary information gracefully', () => {
      const progressionWithoutSalary = {
        ...mockCareerProgression,
        before: { ...mockCareerProgression.before, salary: undefined },
        after: { ...mockCareerProgression.after, salary: undefined }
      }

      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: progressionWithoutSalary,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
        }
      })

      // Should not show salary information
      expect(wrapper.find('.stat-item').exists()).toBe(true)
      // But salary stats should not be present
      const statItems = wrapper.findAll('.stat-item')
      const salaryStats = statItems.filter(item => item.text().includes('$'))
      expect(salaryStats).toHaveLength(0)
    })
  })

  describe('Success Metrics Comparison', () => {
    it('displays metrics comparison section', () => {
      expect(wrapper.text()).toContain('Success Metrics')
      expect(wrapper.find('.metrics-comparison').exists()).toBe(true)
    })

    it('renders all success metrics', () => {
      const metricCards = wrapper.findAll('.metric-comparison-card')
      expect(metricCards).toHaveLength(3)
    })

    it('displays metric names correctly', () => {
      expect(wrapper.text()).toContain('Salary Increase')
      expect(wrapper.text()).toContain('Promotions')
      expect(wrapper.text()).toContain('Network Growth')
    })

    it('shows before and after values for metrics', () => {
      expect(wrapper.text()).toContain('Before')
      expect(wrapper.text()).toContain('After')
      expect(wrapper.text()).toContain('$0') // Before salary increase
      expect(wrapper.text()).toContain('+123%') // After salary increase
    })

    it('displays improvement values', () => {
      expect(wrapper.text()).toContain('+123%')
      expect(wrapper.text()).toContain('2')
      expect(wrapper.text()).toContain('+200%')
    })

    it('shows verification status for metrics', () => {
      const verifiedMetrics = wrapper.findAll('.metric-verified')
      expect(verifiedMetrics).toHaveLength(2) // Only 2 metrics are verified
      expect(wrapper.text()).toContain('Verified')
    })

    it('applies correct styling based on metric type', () => {
      expect(wrapper.find('.metric-salary').exists()).toBe(true)
      expect(wrapper.find('.metric-promotion').exists()).toBe(true)
      expect(wrapper.find('.metric-network').exists()).toBe(true)
    })

    it('does not show metrics section when no metrics provided', () => {
      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: mockCareerProgression,
          successMetrics: [],
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.metrics-comparison').exists()).toBe(false)
    })
  })

  describe('Platform Impact', () => {
    it('displays platform impact section', () => {
      expect(wrapper.text()).toContain('Platform Impact')
      expect(wrapper.find('.platform-impact').exists()).toBe(true)
    })

    it('shows all impact metrics', () => {
      expect(wrapper.text()).toContain('45') // Connections made
      expect(wrapper.text()).toContain('3') // Mentors
      expect(wrapper.text()).toContain('7') // Referrals
      expect(wrapper.text()).toContain('12') // Events attended
      expect(wrapper.text()).toContain('+200%') // Network growth
      expect(wrapper.text()).toContain('4') // Skills acquired count
    })

    it('displays impact labels correctly', () => {
      expect(wrapper.text()).toContain('New Connections')
      expect(wrapper.text()).toContain('Mentors')
      expect(wrapper.text()).toContain('Referrals')
      expect(wrapper.text()).toContain('Events Attended')
      expect(wrapper.text()).toContain('Network Growth')
      expect(wrapper.text()).toContain('Skills Acquired')
    })

    it('shows skills acquired section', () => {
      expect(wrapper.text()).toContain('Skills Acquired')
      expect(wrapper.text()).toContain('Leadership')
      expect(wrapper.text()).toContain('System Design')
      expect(wrapper.text()).toContain('Team Management')
      expect(wrapper.text()).toContain('Strategic Planning')
    })

    it('renders skill tags', () => {
      const skillTags = wrapper.findAll('.skill-tag')
      expect(skillTags).toHaveLength(4)
    })

    it('does not show platform impact when not provided', () => {
      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: mockCareerProgression,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.platform-impact').exists()).toBe(false)
    })

    it('does not show skills section when no skills acquired', () => {
      const impactWithoutSkills = {
        ...mockPlatformImpact,
        skillsAcquired: []
      }

      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: mockCareerProgression,
          platformImpact: impactWithoutSkills,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.skills-acquired').exists()).toBe(false)
    })
  })

  describe('Interactive Controls', () => {
    it('displays toggle view button', () => {
      expect(wrapper.find('.toggle-view-button').exists()).toBe(true)
      expect(wrapper.text()).toContain('Show More Details')
    })

    it('toggles detailed view when button is clicked', async () => {
      const toggleButton = wrapper.find('.toggle-view-button')
      
      // Initially shows "Show More Details"
      expect(wrapper.text()).toContain('Show More Details')
      
      await toggleButton.trigger('click')
      expect(wrapper.text()).toContain('Show Less')
      
      await toggleButton.trigger('click')
      expect(wrapper.text()).toContain('Show More Details')
    })

    it('has proper ARIA label for toggle button', () => {
      const toggleButton = wrapper.find('.toggle-view-button')
      expect(toggleButton.attributes('aria-label')).toBe('Show detailed view')
    })

    it('updates ARIA label when view is toggled', async () => {
      const toggleButton = wrapper.find('.toggle-view-button')
      
      await toggleButton.trigger('click')
      expect(toggleButton.attributes('aria-label')).toBe('Show simple view')
    })
  })

  describe('Date Formatting', () => {
    it('formats start and end dates correctly', () => {
      expect(wrapper.text()).toContain('Jan 2022') // Start date
      expect(wrapper.text()).toContain('Jul 2023') // End date
    })

    it('uses default dates when not provided', () => {
      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: mockCareerProgression,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
          // No startDate or endDate provided
        }
      })

      // Should still render dates (defaults)
      const dateElements = wrapper.findAll('.section-date')
      expect(dateElements.length).toBe(2)
    })
  })

  describe('Responsive Design', () => {
    it('applies responsive classes for layout', () => {
      expect(wrapper.find('.visual-comparison').classes()).toContain('lg:flex-row')
      expect(wrapper.find('.metrics-grid').classes()).toContain('md:grid-cols-2')
      expect(wrapper.find('.impact-grid').classes()).toContain('md:grid-cols-3')
    })
  })

  describe('Accessibility', () => {
    it('has proper heading hierarchy', () => {
      expect(wrapper.find('h3').text()).toBe('Career Transformation')
      expect(wrapper.find('h4').exists()).toBe(true)
      expect(wrapper.find('h5').exists()).toBe(true)
    })

    it('has proper alt text for profile images', () => {
      const profileImages = wrapper.findAll('.profile-avatar')
      profileImages.forEach(img => {
        expect(img.attributes('alt')).toBe('John Smith profile photo')
      })
    })

    it('has proper ARIA labels for interactive elements', () => {
      const toggleButton = wrapper.find('.toggle-view-button')
      expect(toggleButton.attributes('aria-label')).toBeDefined()
    })
  })

  describe('Error Handling', () => {
    it('handles missing career progression data gracefully', () => {
      const minimalProgression = {
        before: {
          role: 'Developer',
          company: 'Company A',
          level: 'Junior',
          responsibilities: []
        },
        after: {
          role: 'Senior Developer',
          company: 'Company B',
          level: 'Senior',
          responsibilities: []
        },
        timeframe: '1 year',
        keyMilestones: []
      }

      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: minimalProgression,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.before-after-comparison').exists()).toBe(true)
      expect(wrapper.text()).toContain('Developer')
      expect(wrapper.text()).toContain('Senior Developer')
    })

    it('handles empty responsibilities arrays', () => {
      const progressionWithoutResponsibilities = {
        ...mockCareerProgression,
        before: { ...mockCareerProgression.before, responsibilities: [] },
        after: { ...mockCareerProgression.after, responsibilities: [] }
      }

      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: progressionWithoutResponsibilities,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
        }
      })

      expect(wrapper.find('.before-after-comparison').exists()).toBe(true)
      expect(wrapper.text()).toContain('0') // Responsibilities count should be 0
    })

    it('handles missing optional props gracefully', () => {
      wrapper = mount(BeforeAfterComparison, {
        props: {
          careerProgression: mockCareerProgression,
          alumniName: 'John Smith',
          profileImage: '/images/john-smith.jpg'
          // No successMetrics, platformImpact, startDate, endDate
        }
      })

      expect(wrapper.find('.before-after-comparison').exists()).toBe(true)
      expect(wrapper.find('.metrics-comparison').exists()).toBe(false)
      expect(wrapper.find('.platform-impact').exists()).toBe(false)
    })
  })
})