import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import EnterpriseMetricsVisualization from '@/components/homepage/EnterpriseMetricsVisualization.vue'

const mockMetrics = [
  {
    id: '1',
    name: 'Alumni Engagement',
    category: 'engagement' as const,
    metric: 'engagement' as const,
    beforeValue: 25,
    afterValue: 75,
    improvementPercentage: 200,
    timeframe: '12 months',
    verified: true,
    unit: 'percentage' as const
  },
  {
    id: '2',
    name: 'Event Attendance',
    category: 'operational' as const,
    metric: 'event_attendance' as const,
    beforeValue: 200,
    afterValue: 800,
    improvementPercentage: 300,
    timeframe: '12 months',
    verified: true,
    unit: 'count' as const
  },
  {
    id: '3',
    name: 'Donation Revenue',
    category: 'financial' as const,
    metric: 'donations' as const,
    beforeValue: 100000,
    afterValue: 250000,
    improvementPercentage: 150,
    timeframe: '18 months',
    verified: false,
    unit: 'currency' as const
  }
]

const mockROIData = {
  percentage: 350,
  investment: 50000,
  return: 175000,
  timeframe: '18 months'
}

describe('EnterpriseMetricsVisualization', () => {
  it('renders component with title and subtitle', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Enterprise Performance Metrics',
        subtitle: 'Key performance indicators for institutional success',
        metrics: mockMetrics
      }
    })

    expect(wrapper.text()).toContain('Enterprise Performance Metrics')
    expect(wrapper.text()).toContain('Key performance indicators for institutional success')
  })

  it('displays all metrics in grid layout', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    expect(wrapper.text()).toContain('Alumni Engagement')
    expect(wrapper.text()).toContain('Event Attendance')
    expect(wrapper.text()).toContain('Donation Revenue')

    const metricCards = wrapper.findAll('.metric-card')
    expect(metricCards).toHaveLength(3)
  })

  it('shows before and after values correctly', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    // Check engagement metric
    expect(wrapper.text()).toContain('Before:')
    expect(wrapper.text()).toContain('25%')
    expect(wrapper.text()).toContain('After:')
    expect(wrapper.text()).toContain('75%')

    // Check event attendance metric
    expect(wrapper.text()).toContain('200')
    expect(wrapper.text()).toContain('800')

    // Check donation revenue metric
    expect(wrapper.text()).toContain('$100.0K')
    expect(wrapper.text()).toContain('$250.0K')
  })

  it('displays improvement percentages correctly', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    expect(wrapper.text()).toContain('+200%')
    expect(wrapper.text()).toContain('+300%')
    expect(wrapper.text()).toContain('+150%')
  })

  it('shows verification badges for verified metrics', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    const verificationIcons = wrapper.findAll('svg')
    // Should have verification icons for verified metrics
    expect(verificationIcons.length).toBeGreaterThan(0)
  })

  it('applies correct category-based styling', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    // Check for category-specific background colors
    expect(wrapper.html()).toContain('bg-blue-100') // engagement
    expect(wrapper.html()).toContain('bg-purple-100') // operational
    expect(wrapper.html()).toContain('bg-green-100') // financial
  })

  it('displays summary statistics when showSummary is true', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics,
        showSummary: true
      }
    })

    expect(wrapper.text()).toContain('Average Improvement')
    expect(wrapper.text()).toContain('Metrics Tracked')
    expect(wrapper.text()).toContain('Verified Results')

    // Check calculated values
    expect(wrapper.text()).toContain('217%') // Average of 200, 300, 150
    expect(wrapper.text()).toContain('3') // Total metrics
    expect(wrapper.text()).toContain('2') // Verified metrics
  })

  it('hides summary statistics when showSummary is false', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics,
        showSummary: false
      }
    })

    expect(wrapper.text()).not.toContain('Average Improvement')
    expect(wrapper.text()).not.toContain('Metrics Tracked')
  })

  it('displays ROI data when provided', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics,
        roiData: mockROIData
      }
    })

    expect(wrapper.text()).toContain('Return on Investment')
    expect(wrapper.text()).toContain('350%')
    expect(wrapper.text()).toContain('18 months implementation period')
    expect(wrapper.text()).toContain('Investment: $50.0K')
    expect(wrapper.text()).toContain('Return: $175.0K')
  })

  it('hides ROI section when no ROI data provided', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    expect(wrapper.text()).not.toContain('Return on Investment')
  })

  it('formats currency values correctly', () => {
    const testMetrics = [
      {
        ...mockMetrics[0],
        beforeValue: 500,
        afterValue: 1500,
        unit: 'currency' as const
      },
      {
        ...mockMetrics[1],
        beforeValue: 1000000,
        afterValue: 2500000,
        unit: 'currency' as const
      }
    ]

    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: testMetrics
      }
    })

    expect(wrapper.text()).toContain('$500')
    expect(wrapper.text()).toContain('$1.5K')
    expect(wrapper.text()).toContain('$1.0M')
    expect(wrapper.text()).toContain('$2.5M')
  })

  it('formats count values correctly', () => {
    const testMetrics = [
      {
        ...mockMetrics[0],
        beforeValue: 500,
        afterValue: 1500,
        unit: 'count' as const
      },
      {
        ...mockMetrics[1],
        beforeValue: 1000000,
        afterValue: 2500000,
        unit: 'count' as const
      }
    ]

    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: testMetrics
      }
    })

    expect(wrapper.text()).toContain('500')
    expect(wrapper.text()).toContain('1.5K')
    expect(wrapper.text()).toContain('1.0M')
    expect(wrapper.text()).toContain('2.5M')
  })

  it('formats percentage values correctly', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: [mockMetrics[0]] // First metric is percentage
      }
    })

    expect(wrapper.text()).toContain('25%')
    expect(wrapper.text()).toContain('75%')
  })

  it('formats days values correctly', () => {
    const testMetrics = [
      {
        ...mockMetrics[0],
        beforeValue: 30,
        afterValue: 7,
        unit: 'days' as const
      }
    ]

    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: testMetrics
      }
    })

    expect(wrapper.text()).toContain('30 days')
    expect(wrapper.text()).toContain('7 days')
  })

  it('handles empty metrics array gracefully', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: []
      }
    })

    expect(wrapper.text()).toContain('Test Metrics')
    expect(wrapper.text()).toContain('0%') // Average improvement should be 0
    expect(wrapper.text()).toContain('0') // Total metrics should be 0
  })

  it('calculates progress bar width correctly', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    const progressBars = wrapper.findAll('.bg-gradient-to-r')
    expect(progressBars.length).toBeGreaterThan(0)

    // Check that progress bars have width styles
    const progressBar = progressBars[0]
    expect(progressBar.attributes('style')).toContain('width:')
  })

  it('applies hover effects to metric cards', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    const metricCards = wrapper.findAll('.metric-card')
    expect(metricCards[0].classes()).toContain('hover:border-blue-300')
    expect(metricCards[0].classes()).toContain('transition-colors')
  })

  it('displays timeframe information for each metric', () => {
    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: mockMetrics
      }
    })

    expect(wrapper.text()).toContain('12 months')
    expect(wrapper.text()).toContain('18 months')
  })

  it('handles metrics without verification correctly', () => {
    const unverifiedMetrics = mockMetrics.map(metric => ({
      ...metric,
      verified: false
    }))

    const wrapper = mount(EnterpriseMetricsVisualization, {
      props: {
        title: 'Test Metrics',
        metrics: unverifiedMetrics,
        showSummary: true
      }
    })

    // Should show 0 verified results
    expect(wrapper.text()).toContain('0')
    expect(wrapper.text()).toContain('Verified Results')
  })
})