import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import InstitutionalBeforeAfterComparison from '@/components/homepage/InstitutionalBeforeAfterComparison.vue'

const mockBeforeMetrics = [
  {
    key: 'engagement',
    label: 'Alumni Engagement Rate',
    value: 25,
    unit: 'percentage' as const
  },
  {
    key: 'events',
    label: 'Monthly Events',
    value: 5,
    unit: 'count' as const
  },
  {
    key: 'donations',
    label: 'Annual Donations',
    value: 500000,
    unit: 'currency' as const
  }
]

const mockAfterMetrics = [
  {
    key: 'engagement',
    label: 'Alumni Engagement Rate',
    value: 75,
    unit: 'percentage' as const
  },
  {
    key: 'events',
    label: 'Monthly Events',
    value: 20,
    unit: 'count' as const
  },
  {
    key: 'donations',
    label: 'Annual Donations',
    value: 1250000,
    unit: 'currency' as const
  }
]

const mockBeforeChallenges = [
  'Low alumni participation in events',
  'Limited digital engagement channels',
  'Difficulty tracking alumni career progress',
  'Inefficient communication methods'
]

const mockAfterBenefits = [
  'Increased alumni participation by 200%',
  'Streamlined digital communication platform',
  'Real-time alumni career tracking',
  'Automated engagement workflows'
]

const mockProps = {
  title: 'Digital Transformation Success',
  subtitle: 'How Stanford University revolutionized alumni engagement',
  institutionName: 'Stanford University',
  institutionType: 'university',
  institutionLogo: '/images/stanford-logo.png',
  alumniCount: 250000,
  beforeMetrics: mockBeforeMetrics,
  afterMetrics: mockAfterMetrics,
  beforeChallenges: mockBeforeChallenges,
  afterBenefits: mockAfterBenefits,
  timeframe: '18 months',
  impactSummary: 'Stanford University achieved a 200% increase in alumni engagement through strategic digital transformation.'
}

describe('InstitutionalBeforeAfterComparison', () => {
  it('renders component with title and subtitle', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    expect(wrapper.text()).toContain('Digital Transformation Success')
    expect(wrapper.text()).toContain('How Stanford University revolutionized alumni engagement')
  })

  it('displays institution information correctly', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    expect(wrapper.text()).toContain('Stanford University')
    expect(wrapper.text()).toContain('university')
    expect(wrapper.text()).toContain('250K Alumni')

    const logo = wrapper.find('img[alt="Stanford University logo"]')
    expect(logo.exists()).toBe(true)
    expect(logo.attributes('src')).toBe('/images/stanford-logo.png')
  })

  it('displays before section with metrics and challenges', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    expect(wrapper.text()).toContain('Before Implementation')
    
    // Check before metrics
    expect(wrapper.text()).toContain('Alumni Engagement Rate')
    expect(wrapper.text()).toContain('25%')
    expect(wrapper.text()).toContain('Monthly Events')
    expect(wrapper.text()).toContain('5')
    expect(wrapper.text()).toContain('Annual Donations')
    expect(wrapper.text()).toContain('$500.0K')

    // Check challenges
    expect(wrapper.text()).toContain('Key Challenges:')
    expect(wrapper.text()).toContain('Low alumni participation in events')
    expect(wrapper.text()).toContain('Limited digital engagement channels')
  })

  it('displays after section with metrics and benefits', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    expect(wrapper.text()).toContain('After Implementation')
    
    // Check after metrics
    expect(wrapper.text()).toContain('75%')
    expect(wrapper.text()).toContain('20')
    expect(wrapper.text()).toContain('$1.3M')

    // Check improvement percentages
    expect(wrapper.text()).toContain('+200%') // (75-25)/25 * 100
    expect(wrapper.text()).toContain('+300%') // (20-5)/5 * 100
    expect(wrapper.text()).toContain('+150%') // (1250000-500000)/500000 * 100

    // Check benefits
    expect(wrapper.text()).toContain('Key Benefits:')
    expect(wrapper.text()).toContain('Increased alumni participation by 200%')
    expect(wrapper.text()).toContain('Streamlined digital communication platform')
  })

  it('shows transformation timeframe', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    expect(wrapper.text()).toContain('18 months Transformation')
  })

  it('displays overall impact summary', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    expect(wrapper.text()).toContain('Overall Impact')
    expect(wrapper.text()).toContain('217% Average Improvement') // Average of 200, 300, 150
    expect(wrapper.text()).toContain('Stanford University achieved a 200% increase in alumni engagement')
  })

  it('calculates improvement percentages correctly', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    // Engagement: (75-25)/25 * 100 = 200%
    expect(wrapper.text()).toContain('+200%')
    
    // Events: (20-5)/5 * 100 = 300%
    expect(wrapper.text()).toContain('+300%')
    
    // Donations: (1250000-500000)/500000 * 100 = 150%
    expect(wrapper.text()).toContain('+150%')
  })

  it('formats different metric units correctly', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    // Percentage
    expect(wrapper.text()).toContain('25%')
    expect(wrapper.text()).toContain('75%')

    // Count
    expect(wrapper.text()).toContain('5')
    expect(wrapper.text()).toContain('20')

    // Currency
    expect(wrapper.text()).toContain('$500.0K')
    expect(wrapper.text()).toContain('$1.3M')
  })

  it('formats alumni count correctly', () => {
    const testCases = [
      { count: 500, expected: '500' },
      { count: 1500, expected: '1.5K' },
      { count: 250000, expected: '250.0K' },
      { count: 2500000, expected: '2.5M' }
    ]

    testCases.forEach(({ count, expected }) => {
      const wrapper = mount(InstitutionalBeforeAfterComparison, {
        props: {
          ...mockProps,
          alumniCount: count
        }
      })

      expect(wrapper.text()).toContain(`${expected} Alumni`)
    })
  })

  it('handles metrics with days unit', () => {
    const propsWithDays = {
      ...mockProps,
      beforeMetrics: [
        {
          key: 'response_time',
          label: 'Response Time',
          value: 30,
          unit: 'days' as const
        }
      ],
      afterMetrics: [
        {
          key: 'response_time',
          label: 'Response Time',
          value: 7,
          unit: 'days' as const
        }
      ]
    }

    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: propsWithDays
    })

    expect(wrapper.text()).toContain('30 days')
    expect(wrapper.text()).toContain('7 days')
  })

  it('handles zero before values gracefully', () => {
    const propsWithZero = {
      ...mockProps,
      beforeMetrics: [
        {
          key: 'new_metric',
          label: 'New Feature Usage',
          value: 0,
          unit: 'count' as const
        }
      ],
      afterMetrics: [
        {
          key: 'new_metric',
          label: 'New Feature Usage',
          value: 100,
          unit: 'count' as const
        }
      ]
    }

    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: propsWithZero
    })

    expect(wrapper.text()).toContain('0')
    expect(wrapper.text()).toContain('100')
    // Should not show improvement percentage for zero before value
    expect(wrapper.text()).toContain('+0%')
  })

  it('applies correct styling classes', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    // Check for gradient header
    expect(wrapper.find('.bg-gradient-to-r.from-blue-600.to-blue-700').exists()).toBe(true)

    // Check for before section styling (red theme)
    expect(wrapper.find('.bg-red-100').exists()).toBe(true)
    expect(wrapper.find('.bg-red-50').exists()).toBe(true)

    // Check for after section styling (green theme)
    expect(wrapper.find('.bg-green-100').exists()).toBe(true)
    expect(wrapper.find('.bg-green-50').exists()).toBe(true)
  })

  it('displays correct icons for before and after sections', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    // Should have warning icon for before section
    const beforeIcons = wrapper.findAll('.text-red-600')
    expect(beforeIcons.length).toBeGreaterThan(0)

    // Should have checkmark icon for after section
    const afterIcons = wrapper.findAll('.text-green-600')
    expect(afterIcons.length).toBeGreaterThan(0)
  })

  it('handles missing institution logo gracefully', () => {
    const propsWithoutLogo = {
      ...mockProps,
      institutionLogo: undefined
    }

    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: propsWithoutLogo
    })

    const logo = wrapper.find('img[alt="Stanford University logo"]')
    expect(logo.exists()).toBe(false)
    
    // Should still display institution name
    expect(wrapper.text()).toContain('Stanford University')
  })

  it('handles empty challenges and benefits arrays', () => {
    const propsWithEmptyArrays = {
      ...mockProps,
      beforeChallenges: [],
      afterBenefits: []
    }

    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: propsWithEmptyArrays
    })

    expect(wrapper.text()).toContain('Key Challenges:')
    expect(wrapper.text()).toContain('Key Benefits:')
    
    // Should not have any challenge or benefit items
    const challengeItems = wrapper.findAll('.before-section li')
    const benefitItems = wrapper.findAll('.after-section li')
    expect(challengeItems.length).toBe(0)
    expect(benefitItems.length).toBe(0)
  })

  it('calculates overall improvement correctly with different scenarios', () => {
    // Test with mixed improvements
    const mixedProps = {
      ...mockProps,
      beforeMetrics: [
        { key: 'metric1', label: 'Metric 1', value: 10, unit: 'count' as const },
        { key: 'metric2', label: 'Metric 2', value: 50, unit: 'percentage' as const }
      ],
      afterMetrics: [
        { key: 'metric1', label: 'Metric 1', value: 30, unit: 'count' as const }, // 200% improvement
        { key: 'metric2', label: 'Metric 2', value: 100, unit: 'percentage' as const } // 100% improvement
      ]
    }

    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mixedProps
    })

    // Average of 200% and 100% = 150%
    expect(wrapper.text()).toContain('150% Average Improvement')
  })

  it('applies hover effects to metric cards', () => {
    const wrapper = mount(InstitutionalBeforeAfterComparison, {
      props: mockProps
    })

    const beforeCards = wrapper.findAll('.bg-red-50')
    const afterCards = wrapper.findAll('.bg-green-50')

    expect(beforeCards.length).toBeGreaterThan(0)
    expect(afterCards.length).toBeGreaterThan(0)
  })
})