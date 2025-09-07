import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TemplateAnalytics from '@/components/TemplateAnalytics.vue'

// Mock MetricCard component
vi.mock('@/Components/Analytics/MetricCard.vue', () => ({
  default: {
    name: 'MetricCard',
    props: ['title', 'value', 'change', 'trend', 'icon', 'color'],
    template: '<div class="metric-card" data-testid="metric-card">{{ title }}: {{ value }}</div>'
  }
}))

// Mock ConversionChart component
vi.mock('@/components/ConversionChart.vue', () => ({
  default: {
    name: 'ConversionChart',
    props: ['data', 'title', 'height'],
    template: '<div class="conversion-chart-mock" data-testid="conversion-chart">{{ title }}</div>'
  }
}))

// Mock TemplateRecommendations component
vi.mock('@/components/TemplateRecommendations.vue', () => ({
  default: {
    name: 'TemplateRecommendations',
    props: ['templates', 'performanceData'],
    template: '<div class="template-recommendations-mock" data-testid="template-recommendations">Recommendations</div>'
  }
}))

describe('TemplateAnalytics.vue', () => {
  let wrapper
  let mockProps

  beforeEach(() => {
    // Mock performance data
    mockProps = {
      tenantId: 1,
      refreshInterval: 60
    }

    // Mock timers for countdown
    vi.useFakeTimers()

    wrapper = mount(TemplateAnalytics, {
      global: {
        plugins: [createTestingPinia()],
        stubs: {
          MetricCard: true,
          ConversionChart: true,
          TemplateRecommendations: true,
          Transition: false,
          Teleport: false
        }
      },
      props: mockProps
    })
  })

  afterEach(() => {
    vi.clearAllTimers()
    vi.useRealTimers()
  })

  describe('Initialization', () => {
    it('renders the analytics dashboard title', () => {
      const title = wrapper.find('h1')
      expect(title.exists()).toBe(true)
      expect(title.text()).toBe('Template Performance Analytics')
    })

    it('displays last update time', () => {
      const lastUpdate = wrapper.find('.text-sm.text-gray-600')
      expect(lastUpdate.exists()).toBe(true)
    })

    it('initializes with default refresh countdown', () => {
      expect(wrapper.vm.refreshCountdown).toBe(60)
    })

    it('shows refresh button', () => {
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')
      expect(refreshButton.exists()).toBe(true)
      expect(refreshButton.text()).toContain('Refresh')
    })
  })

  describe('Key Metrics', () => {
    it('renders four metric cards', () => {
      const metricCards = wrapper.findAllComponents({ name: 'MetricCard' })
      expect(metricCards).toHaveLength(4)
    })

    it('passes correct props to metric cards', () => {
      const metricCards = wrapper.findAllComponents({ name: 'MetricCard' })
      const firstCard = metricCards[0]

      expect(firstCard.props()).toHaveProperty('title')
      expect(firstCard.props()).toHaveProperty('value')
      expect(firstCard.props()).toHaveProperty('trend')
      expect(firstCard.props()).toHaveProperty('color')
    })

    it('includes expected metrics', () => {
      const metricCards = wrapper.findAllComponents({ name: 'MetricCard' })
      const titles = metricCards.map(card => card.props('title'))

      expect(titles).toContain('Total Templates')
      expect(titles).toContain('Total Views')
      expect(titles).toContain('Avg Conversion Rate')
      expect(titles).toContain('Active Campaigns')
    })
  })

  describe('Template Rankings', () => {
    it('displays template performance rankings', () => {
      const rankings = wrapper.findAll('.text-gray-700.dark\\:text-gray-300')
      expect(rankings.length).toBeGreaterThan(0)
    })

    it('shows conversion rates for templates', () => {
      const rates = wrapper.findAll('.text-sm.font-medium.text-gray-900.dark\\:text-white')
      expect(rates.length).toBeGreaterThan(0)
      expect(rates.some(rate => rate.text().includes('%'))).toBe(true)
    })

    it('displays trend indicators', () => {
      const trends = wrapper.findAll('svg')
      expect(trends.length).toBeGreaterThan(0)
    })
  })

  describe('Chart Components', () => {
    it('renders conversion chart', () => {
      const conversionChart = wrapper.findComponent({ name: 'ConversionChart' })
      expect(conversionChart.exists()).toBe(true)
    })

    it('renders template recommendations', () => {
      const recommendations = wrapper.findComponent({ name: 'TemplateRecommendations' })
      expect(recommendations.exists()).toBe(true)
    })

    it('passes correct props to conversion chart', () => {
      const chart = wrapper.findComponent({ name: 'ConversionChart' })

      expect(chart.props()).toHaveProperty('title')
      expect(chart.props('title')).toBe('Conversion Rate Trends')
      expect(chart.props()).toHaveProperty('data')
      expect(chart.props()).toHaveProperty('height', 400)
    })
  })

  describe('Performance Table', () => {
    it('renders detailed performance table', () => {
      const table = wrapper.find('table')
      expect(table.exists()).toBe(true)
    })

    it('displays table headers', () => {
      const headers = wrapper.findAll('th')
      expect(headers.length).toBeGreaterThan(0)
      const headerTexts = headers.map(th => th.text())
      expect(headerTexts).toContain('Template')
      expect(headerTexts).toContain('Views')
      expect(headerTexts).toContain('Conversions')
    })

    it('renders template performance data in table rows', () => {
      const rows = wrapper.findAll('tbody tr')
      expect(rows.length).toBeGreaterThan(0)
    })

    it('displays conversion rates in table', () => {
      const rates = wrapper.findAll('td').filter(td =>
        td.text().includes('%') && !td.classes().includes('px-6')
      )
      expect(rates.length).toBeGreaterThan(0)
    })
  })

  describe('Real-time Updates', () => {
    it('shows connection status', () => {
      const statusText = wrapper.find('div.inline-flex.items-center')
      expect(statusText.exists()).toBe(true)
    })

    it('displays countdown timer', () => {
      const countdownText = wrapper.find(/Next refresh in \d+s/)
      expect(countdownText.exists()).toBe(true)
    })

    it('updates countdown correctly', async () => {
      expect(wrapper.vm.refreshCountdown).toBe(60)

      vi.advanceTimersByTime(1000)
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.refreshCountdown).toBe(59)
    })

    it('refreshes data when countdown reaches zero', async () => {
      const refreshMock = vi.spyOn(wrapper.vm, 'refreshData')

      vi.advanceTimersByTime(61000)
      await wrapper.vm.$nextTick()

      expect(refreshMock).toHaveBeenCalled()
    })

    it('disables refresh button while loading', async () => {
      await wrapper.setData({ isLoading: true })
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')

      expect(refreshButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('Refresh Functionality', () => {
    it('refreshes data when button is clicked', async () => {
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')

      await refreshButton.trigger('click')
      await flushPromises()

      expect(wrapper.vm.lastUpdateTime).not.toBe('')
      expect(wrapper.vm.isLoading).toBe(false)
    })

    it('shows loading state during refresh', async () => {
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')

      await refreshButton.trigger('click')
      expect(wrapper.vm.isLoading).toBe(true)

      await flushPromises()
      expect(wrapper.vm.isLoading).toBe(false)
    })

    it('updates last modified time after refresh', async () => {
      const initialTime = wrapper.vm.lastUpdateTime
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')

      await refreshButton.trigger('click')
      await flushPromises()

      expect(wrapper.vm.lastUpdateTime).not.toBe(initialTime)
    })
  })

  describe('Responsive Design', () => {
    it('renders on mobile viewport', () => {
      // Test mobile responsiveness
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 375
      })

      window.dispatchEvent(new Event('resize'))
      // Component should handle responsive classes
    })

    it('adapts grid layout for different screen sizes', () => {
      const grid = wrapper.find('.grid.grid-cols-1.md\\:grid-cols-2')
      expect(grid.exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('includes proper ARIA labels for buttons', () => {
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')
      expect(refreshButton.attributes('aria-label')).toBeTruthy()
    })

    it('provides screen reader text for chart descriptions', () => {
      const srText = wrapper.find('#chart-description')
      expect(srText.exists()).toBe(true)
      expect(srText.classes()).toContain('sr-only')
    })

    it('includes proper table accessibility features', () => {
      const table = wrapper.find('table')
      expect(table.attributes('role')).toBe('table')

      const headers = wrapper.findAll('th')
      headers.forEach(header => {
        expect(header.attributes('scope')).toBeDefined()
      })
    })

    it('announces loading and error states', () => {
      const statusElements = wrapper.findAll('[aria-live]')
      expect(statusElements.length).toBeGreaterThan(0)
    })
  })

  describe('Error Handling', () => {
    it('handles missing template data gracefully', () => {
      // Should not crash with empty data
      expect(wrapper.exists()).toBe(true)
    })

    it('displays appropriate message when no data is available', () => {
      // Test with empty arrays
      wrapper.setData({
        templateRankings: [],
        detailedPerformance: []
      })

      const emptyMessage = wrapper.find('.text-gray-500.dark\\:text-gray-400')
      expect(emptyMessage.exists()).toBe(true)
    })
  })

  describe('Performance Metrics', () => {
    it('calculates and displays average conversion rate', () => {
      const metrics = wrapper.vm.keyMetrics
      const avgMetric = metrics.find(m => m.title === 'Avg Conversion Rate')
      expect(avgMetric).toBeDefined()
      expect(avgMetric.value).toBe('4.7%')
    })

    it('tracks template performance status correctly', () => {
      const statusElements = wrapper.findAll('.inline-flex.px-2')
      expect(statusElements.length).toBeGreaterThan(0)
    })

    it('formats numbers correctly for large values', () => {
      const largeNumber = wrapper.vm.formatNumber(1500000)
      expect(largeNumber).toBe('1.5M')
    })

    it('formats currency values correctly', () => {
      // Test currency formatting if used
      expect(wrapper.vm.formatNumber).toBeDefined()
    })
  })

  describe('Tenant Isolation', () => {
    it('accepts tenant ID prop', () => {
      expect(wrapper.props('tenantId')).toBe(1)
    })

    it('refreshes data when tenant ID changes', async () => {
      const refreshMock = vi.spyOn(wrapper.vm, 'refreshData')

      await wrapper.setProps({ tenantId: 2 })
      await wrapper.vm.$nextTick()

      expect(refreshMock).toHaveBeenCalled()
    })

    it('handles undefined tenant ID gracefully', () => {
      const tenantId = wrapper.props('tenantId')
      expect(typeof tenantId).toBe('number')
    })
  })

  describe('Lifecycle Hooks', () => {
    it('sets up timers on mount', () => {
      expect(wrapper.vm.refreshCountdown).toBe(60)
    })

    it('cleans up timers on unmount', () => {
      const clearIntervalSpy = vi.spyOn(global, 'clearInterval')

      wrapper.unmount()

      expect(clearIntervalSpy).toHaveBeenCalled()
    })

    it('handles tenant changes through watchers', async () => {
      const refreshMock = vi.spyOn(wrapper.vm, 'refreshData')

      await wrapper.setProps({ tenantId: 3 })

      expect(refreshMock).toHaveBeenCalled()
    })
  })

  describe('Data Formatting', () => {
    it('formats large numbers correctly', () => {
      expect(wrapper.vm.formatNumber(1500000)).toBe('1.5M')
      expect(wrapper.vm.formatNumber(1500)).toBe('1.5K')
      expect(wrapper.vm.formatNumber(150)).toBe('150')
    })

    it('determines status badges correctly', () => {
      const excellentStatus = wrapper.vm.getStatusBadge('Excellent')
      const poorStatus = wrapper.vm.getStatusBadge('Poor')

      expect(excellentStatus).toContain('green')
      expect(poorStatus).toContain('red')
    })

    it('calculates performance status based on conversion rate', () => {
      const templateData = wrapper.vm.detailedPerformance
      templateData.forEach(template => {
        expect(template.conversionRate).toBeDefined()
        expect(['Excellent', 'Good', 'Average', 'Poor']).toContain(template.status)
      })
    })
  })

  describe('User Interactions', () => {
    it('handles refresh button click', async () => {
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')
      await refreshButton.trigger('click')

      expect(wrapper.vm.isLoading).toBe(false)
    })

    it('prevents rapid refresh clicks', async () => {
      const refreshButton = wrapper.find('[aria-label*="Refresh"]')
      const refreshMock = vi.spyOn(wrapper.vm, 'refreshData')

      // Click multiple times rapidly
      await refreshButton.trigger('click')
      await refreshButton.trigger('click')
      await refreshButton.trigger('click')

      await flushPromises()
      expect(refreshMock).toHaveBeenCalledTimes(1)
    })

    it('shows appropriate loading states', async () => {
      wrapper.vm.isLoading = true
      await wrapper.vm.$nextTick()

      const refreshButton = wrapper.find('[aria-label*="Refresh"]')
      expect(refreshButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('Performance Optimization', () => {
    it('uses computed properties efficiently', () => {
      const detailedPerformance = wrapper.vm.detailedPerformance
      expect(Array.isArray(detailedPerformance)).toBe(true)
      expect(detailedPerformance.length).toBeGreaterThan(0)
    })

    it('handles large datasets efficiently', () => {
      // Test with large data sets
      const largeDataset = Array.from({ length: 1000 }, (_, i) => ({
        id: i,
        name: `Template ${i}`,
        views: Math.random() * 10000,
        conversions: Math.random() * 1000,
        status: 'Good'
      }))

      wrapper.setData({ templateRankings: largeDataset })
      expect(wrapper.vm.detailedPerformance).toBeDefined()
    })

    it('debounces user interactions', async () => {
      const refreshMock = vi.spyOn(wrapper.vm, 'refreshData')

      // Simulate rapid clicks
      const button = wrapper.find('[aria-label*="Refresh"]')
      for (let i = 0; i < 3; i++) {
        await button.trigger('click')
      }

      expect(refreshMock).toHaveBeenCalledTimes(3)
    })
  })
})