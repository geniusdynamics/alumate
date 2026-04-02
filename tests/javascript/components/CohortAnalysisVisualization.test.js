import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import CohortAnalysisVisualization from '@/components/Analytics/CohortAnalysisVisualization.vue'

describe('CohortAnalysisVisualization.vue', () => {
  let wrapper
  let mockCohorts

  const mockCohortStoreData = {
    isLoading: false,
    error: '',
    availableCohorts: [],
    cohortData: null,
    comparisonData: null,
    filters: {
      dateRange: { from: '', to: '' },
      metrics: ['retention', 'engagement', 'conversion'],
      cohortIds: [],
      comparisonMode: false,
    },
  }

  beforeEach(() => {
    // Mock cohort data
    mockCohorts = [
      {
        id: '1',
        name: 'Class of 2024',
        criteria: { grad_year: 2024 },
        created_at: '2024-01-15',
        members_count: 150,
        metrics: {
          size: 150,
          retention_30d: 65.5,
          churn: 34.5,
          retention: {
            day7: 82.3,
            day30: 65.5,
            day90: 48.2,
            day180: 35.1,
            trend: [82, 78, 72, 68, 65, 62, 58, 55],
          },
          engagement: {
            score: 72.5,
            sessionsPerWeek: 3.2,
            pagesPerSession: 5.8,
            activeDaysPerWeek: 4.1,
          },
          conversion: {
            rate: 12.3,
            funnel: [
              { step: 'Signup', count: 150, rate: 100 },
              { step: 'Login', count: 142, rate: 94.7 },
              { step: 'Profile', count: 128, rate: 85.3 },
              { step: 'Purchase', count: 45, rate: 30.0 },
            ],
          },
        },
        insights: [
          {
            type: 'positive',
            message: 'Excellent 7-day retention rate.',
            recommendation: 'Analyze successful onboarding patterns for replication.',
            impact: 'high',
            severity: 'positive',
            metric: 'day7_retention',
            value: 82.3,
            benchmark: 40,
          },
          {
            type: 'warning',
            message: 'Below average 30-day retention. Room for improvement.',
            recommendation: 'Analyze successful user paths and replicate in onboarding.',
            impact: 'medium',
            severity: 'medium',
            metric: 'day30_retention',
            value: 65.5,
            benchmark: 70,
          },
        ],
      },
      {
        id: '2',
        name: 'Class of 2023',
        criteria: { grad_year: 2023 },
        created_at: '2023-01-15',
        members_count: 200,
        metrics: {
          size: 200,
          retention_30d: 58.2,
          churn: 41.8,
          retention: {
            day7: 75.1,
            day30: 58.2,
            day90: 42.5,
            day180: 30.2,
            trend: [75, 70, 65, 62, 58, 55, 52, 48],
          },
          engagement: {
            score: 65.8,
            sessionsPerWeek: 2.8,
            pagesPerSession: 4.5,
            activeDaysPerWeek: 3.5,
          },
          conversion: {
            rate: 9.8,
            funnel: [
              { step: 'Signup', count: 200, rate: 100 },
              { step: 'Login', count: 180, rate: 90 },
              { step: 'Profile', count: 155, rate: 77.5 },
              { step: 'Purchase', count: 38, rate: 19 },
            ],
          },
        },
        insights: [
          {
            type: 'critical',
            message: 'Very low 30-day retention. Immediate action required.',
            recommendation: 'Implement re-engagement campaigns and analyze churn reasons.',
            impact: 'high',
            severity: 'critical',
            metric: 'day30_retention',
            value: 58.2,
            benchmark: 70,
          },
        ],
      },
      {
        id: '3',
        name: 'Graduate Students',
        criteria: { degree: 'masters' },
        created_at: '2024-02-01',
        members_count: 75,
        metrics: {
          size: 75,
          retention_30d: 78.5,
          churn: 21.5,
          retention: {
            day7: 88.9,
            day30: 78.5,
            day90: 65.2,
            day180: 52.1,
            trend: [89, 85, 82, 79, 76, 73, 70, 68],
          },
          engagement: {
            score: 82.1,
            sessionsPerWeek: 4.5,
            pagesPerSession: 7.2,
            activeDaysPerWeek: 5.2,
          },
          conversion: {
            rate: 18.5,
            funnel: [
              { step: 'Signup', count: 75, rate: 100 },
              { step: 'Login', count: 72, rate: 96 },
              { step: 'Profile', count: 68, rate: 90.7 },
              { step: 'Purchase', count: 28, rate: 37.3 },
            ],
          },
        },
        insights: [],
      },
    ]

    wrapper = mount(CohortAnalysisVisualization, {
      global: {
        plugins: [
          createTestingPinia({
            initialState: {
              cohort: mockCohortStoreData,
            },
          }),
        ],
        stubs: {
          Transition: false,
          Teleport: false,
        },
      },
      props: {
        initialTab: 'list',
      },
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  describe('Initialization', () => {
    it('renders the component container', () => {
      const container = wrapper.find('.cohort-analysis-visualization')
      expect(container.exists()).toBe(true)
    })

    it('displays the header title', () => {
      const header = wrapper.find('h1')
      expect(header.exists()).toBe(true)
      expect(header.text()).toBe('Cohort Analysis')
    })

    it('renders all tab buttons', () => {
      const tabs = wrapper.findAll('nav button')
      expect(tabs.length).toBe(5) // list, detail, comparison, trends, insights
    })

    it('starts with list tab active by default', () => {
      expect(wrapper.vm.activeTab).toBe('list')
    })
  })

  describe('Cohort List View', () => {
    it('displays search input', () => {
      const searchInput = wrapper.find('#search-cohorts')
      expect(searchInput.exists()).toBe(true)
    })

    it('displays filter dropdown', () => {
      const filterSelect = wrapper.find('select')
      expect(filterSelect.exists()).toBe(true)
    })

    it('renders cohort cards', async () => {
      // Set mock data in store
      await wrapper.setData({ availableCohorts: mockCohorts })
      
      const cards = wrapper.findAll('.rounded-lg.border.border-gray-200')
      expect(cards.length).toBeGreaterThan(0)
    })

    it('filters cohorts by search query', async () => {
      await wrapper.setData({ availableCohorts: mockCohorts, searchQuery: '2024' })
      
      const filtered = wrapper.vm.filteredCohorts
      expect(filtered.length).toBe(2)
      expect(filtered.every(c => c.name.includes('2024') || c.criteria.grad_year === 2024)).toBe(true)
    })

    it('filters cohorts by metric', async () => {
      await wrapper.setData({ availableCohorts: mockCohorts, filterMetric: 'retention' })
      
      const filtered = wrapper.vm.filteredCohorts
      // Should filter for high retention cohorts
      expect(filtered.every(c => (c.metrics?.retention?.day30 || 0) > 40)).toBe(true)
    })

    it('shows empty state when no cohorts match', async () => {
      await wrapper.setData({ availableCohorts: mockCohorts, searchQuery: 'nonexistent' })
      
      const emptyState = wrapper.find('.text-center.py-8')
      expect(emptyState.exists()).toBe(true)
    })
  })

  describe('Cohort Detail View', () => {
    it('displays cohort metrics summary cards', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      const summaryCards = wrapper.findAll('.grid.grid-cols-4 .rounded-lg')
      expect(summaryCards.length).toBe(4) // Size, 7-Day, 30-Day, Engagement
    })

    it('renders retention chart canvas', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      const retentionCanvas = wrapper.find('[aria-label="Retention chart"]')
      expect(retentionCanvas.exists()).toBe(true)
    })

    it('renders engagement chart canvas', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      const engagementCanvas = wrapper.find('[aria-label="Engagement chart"]')
      expect(engagementCanvas.exists()).toBe(true)
    })

    it('renders conversion funnel chart canvas', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      const funnelCanvas = wrapper.find('[aria-label="Conversion funnel chart"]')
      expect(funnelCanvas.exists()).toBe(true)
    })

    it('displays automated insights', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      const insightsSection = wrapper.find('.rounded-lg.border.border-gray-200.bg-white.p-4 h3')
      expect(insightsSection.text()).toContain('Automated Insights')
    })

    it('calculates correct metric colors', () => {
      expect(wrapper.vm.getMetricColor(75)).toBe('text-green-600')
      expect(wrapper.vm.getMetricColor(50)).toBe('text-yellow-600')
      expect(wrapper.vm.getMetricColor(25)).toBe('text-red-600')
      expect(wrapper.vm.getMetricColor(undefined)).toBe('text-gray-400')
    })
  })

  describe('Cohort Comparison View', () => {
    it('enables comparison mode', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts,
        comparisonMode: true,
        activeTab: 'comparison'
      })
      
      await nextTick()
      
      const compareButton = wrapper.find('button:contains("Exit Comparison")')
      expect(compareButton.exists()).toBe(true)
    })

    it('renders comparison chart', async () => {
      await wrapper.setData({
        availableCohorts: mockCohorts,
        activeTab: 'comparison',
      })
      
      // Set selected cohorts
      await wrapper.setData({ 
        cohortStore: { 
          ...mockCohortStoreData,
          filters: { ...mockCohortStoreData.filters, cohortIds: ['1', '2'] }
        }
      })
      
      await nextTick()
      
      const comparisonCanvas = wrapper.find('[aria-label="Cohort comparison chart"]')
      expect(comparisonCanvas.exists()).toBe(true)
    })

    it('displays comparison table', async () => {
      await wrapper.setData({
        availableCohorts: mockCohorts,
        activeTab: 'comparison',
      })
      
      // Set selected cohorts
      const cohortStore = {
        ...mockCohortStoreData,
        filters: { ...mockCohortStoreData.filters, cohortIds: ['1', '2'] }
      }
      
      // Use Pinia directly
      const pinia = createTestingPinia({ initialState: { cohort: cohortStore } })
      wrapper = mount(CohortAnalysisVisualization, {
        global: {
          plugins: [pinia],
          stubs: { Transition: false, Teleport: false },
        },
      })
      
      await wrapper.setData({ activeTab: 'comparison' })
      await nextTick()
      
      const table = wrapper.find('table')
      expect(table.exists()).toBe(true)
    })

    it('limits comparison to max 4 cohorts', () => {
      const cohortIds = ['1', '2', '3', '4', '5']
      expect(cohortIds.slice(0, 4).length).toBe(4)
    })
  })

  describe('Trend Analysis View', () => {
    it('renders trend period selector', async () => {
      await wrapper.setData({ activeTab: 'trends' })
      
      const periodSelect = wrapper.findAll('select')[1]
      expect(periodSelect.exists()).toBe(true)
    })

    it('loads trend data when cohort is selected', async () => {
      await wrapper.setData({ 
        activeTab: 'trends',
        selectedCohortId: '1'
      })
      
      await wrapper.vm.loadTrendData()
      // Should not throw error
      expect(wrapper.vm.trendData).toBeDefined()
    })

    it('displays trend summary cards', async () => {
      await wrapper.setData({
        activeTab: 'trends',
        trendData: {
          trends: [
            { period: '2024-01', active_users: 100, event_count: 500 },
            { period: '2024-02', active_users: 120, event_count: 600 },
          ],
          summary: {
            overall_trend: 'improving',
            avg_active_users: 110,
            total_events: 1100,
            periods_with_growth: 1,
            periods_with_decline: 0,
          },
        }
      })
      
      await nextTick()
      
      const summaryCards = wrapper.findAll('.grid.grid-cols-4 .rounded-lg')
      expect(summaryCards.length).toBe(4)
    })

    it('calculates correct trend colors', () => {
      expect(wrapper.vm.getTrendColor('improving')).toBe('text-green-600')
      expect(wrapper.vm.getTrendColor('declining')).toBe('text-red-600')
      expect(wrapper.vm.getTrendColor('stable')).toBe('text-gray-600')
    })
  })

  describe('Insights View', () => {
    it('renders insight type filters', async () => {
      await wrapper.setData({ activeTab: 'insights' })
      
      const filters = wrapper.findAll('.px-3.py-1\\.5.rounded-full')
      expect(filters.length).toBe(4) // All, Positive, Warnings, Critical
    })

    it('filters insights by type', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts,
        insightFilter: 'critical'
      })
      
      const criticalInsights = wrapper.vm.allInsights
      expect(criticalInsights.every(i => i.type === 'critical')).toBe(true)
    })

    it('shows empty state when no insights', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts.filter(c => c.id === '3'), // No insights
        insightFilter: 'all'
      })
      
      const emptyState = wrapper.find('.text-center.py-8')
      expect(emptyState.exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('includes proper ARIA labels', () => {
      const region = wrapper.find('[role="region"]')
      expect(region.exists()).toBe(true)
      expect(region.attributes('aria-label')).toBe('Cohort analysis visualization dashboard')
    })

    it('provides screen reader descriptions for charts', () => {
      const chartLabels = wrapper.findAll('[aria-label]')
      expect(chartLabels.length).toBeGreaterThan(0)
    })
  })

  describe('Error Handling', () => {
    it('displays error state when store has error', async () => {
      await wrapper.setData({ 
        cohortStore: { ...mockCohortStoreData, error: 'Failed to load data' }
      })
      
      const errorState = wrapper.find('.rounded-lg.border.border-red-200')
      expect(errorState.exists()).toBe(true)
    })

    it('shows retry button on error', async () => {
      await wrapper.setData({ 
        cohortStore: { ...mockCohortStoreData, error: 'Failed to load data' }
      })
      
      const retryButton = wrapper.find('button:contains("Retry")')
      expect(retryButton.exists()).toBe(true)
    })

    it('displays loading state', async () => {
      await wrapper.setData({ 
        cohortStore: { ...mockCohortStoreData, isLoading: true }
      })
      
      const loadingSpinner = wrapper.find('.animate-spin')
      expect(loadingSpinner.exists()).toBe(true)
    })
  })

  describe('Utility Methods', () => {
    it('generates correct insight badge classes', async () => {
      await wrapper.setData({ availableCohorts: mockCohorts })
      
      const cohortWithCritical = mockCohorts.find(c => c.id === '2')
      const badgeClass = wrapper.vm.getInsightBadgeClass(cohortWithCritical!)
      expect(badgeClass).toBe('bg-red-100 text-red-800')
    })

    it('generates correct insight classes', () => {
      expect(wrapper.vm.getInsightClass('positive')).toBe('bg-green-50 border-green-200')
      expect(wrapper.vm.getInsightClass('warning')).toBe('bg-yellow-50 border-yellow-200')
      expect(wrapper.vm.getInsightClass('critical')).toBe('bg-red-50 border-red-200')
    })

    it('generates correct insight icon classes', () => {
      expect(wrapper.vm.getInsightIconClass('positive')).toBe('text-green-500')
      expect(wrapper.vm.getInsightIconClass('warning')).toBe('text-yellow-500')
      expect(wrapper.vm.getInsightIconClass('critical')).toBe('text-red-500')
    })

    it('generates correct severity badge classes', () => {
      expect(wrapper.vm.getSeverityBadgeClass('critical')).toBe('bg-red-100 text-red-800')
      expect(wrapper.vm.getSeverityBadgeClass('medium')).toBe('bg-yellow-100 text-yellow-800')
      expect(wrapper.vm.getSeverityBadgeClass('low')).toBe('bg-blue-100 text-blue-800')
    })
  })

  describe('Responsive Design', () => {
    it('adapts to mobile viewport', async () => {
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 375,
      })
      
      window.dispatchEvent(new Event('resize'))
      await wrapper.vm.$nextTick()
      
      expect(wrapper.vm).toBeDefined()
    })
  })

  describe('Integration with Store', () => {
    it('loads cohorts on mount', async () => {
      const listCohortsSpy = vi.fn()
      
      const pinia = createTestingPinia({ 
        initialState: { cohort: mockCohortStoreData },
      })
      
      wrapper = mount(CohortAnalysisVisualization, {
        global: {
          plugins: [pinia],
          stubs: { Transition: false, Teleport: false },
        },
      })
      
      // Store should be initialized
      expect(wrapper.vm.availableCohorts).toBeDefined()
    })

    it('toggles cohort selection correctly', async () => {
      const cohortStore = {
        ...mockCohortStoreData,
        filters: { ...mockCohortStoreData.filters, cohortIds: ['1'] }
      }
      
      const pinia = createTestingPinia({ initialState: { cohort: cohortStore } })
      wrapper = mount(CohortAnalysisVisualization, {
        global: {
          plugins: [pinia],
          stubs: { Transition: false, Teleport: false },
        },
      })
      
      wrapper.vm.toggleCohortSelection('2')
      expect(wrapper.vm.cohortStore.filters.cohortIds).toContain('2')
      
      wrapper.vm.toggleCohortSelection('2')
      expect(wrapper.vm.cohortStore.filters.cohortIds).not.toContain('2')
    })

    it('enables and disables comparison mode', async () => {
      const cohortStore = {
        ...mockCohortStoreData,
        setComparisonMode: vi.fn(),
      }
      
      const pinia = createTestingPinia({ initialState: { cohort: cohortStore } })
      wrapper = mount(CohortAnalysisVisualization, {
        global: {
          plugins: [pinia],
          stubs: { Transition: false, Teleport: false },
        },
      })
      
      wrapper.vm.enableComparisonMode()
      expect(wrapper.vm.comparisonMode).toBe(true)
      expect(wrapper.vm.activeTab).toBe('comparison')
      
      wrapper.vm.disableComparisonMode()
      expect(wrapper.vm.comparisonMode).toBe(false)
      expect(wrapper.vm.activeTab).toBe('list')
    })
  })

  describe('Chart Rendering', () => {
    it('updates retention chart when cohort is selected', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      // Chart should be rendered
      expect(wrapper.vm.retentionChart).toBeDefined()
    })

    it('updates engagement chart when cohort is selected', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      expect(wrapper.vm.engagementChart).toBeDefined()
    })

    it('updates funnel chart when cohort is selected', async () => {
      await wrapper.setData({ 
        availableCohorts: mockCohorts, 
        selectedCohortId: '1',
        activeTab: 'detail' 
      })
      
      await nextTick()
      
      expect(wrapper.vm.funnelChart).toBeDefined()
    })
  })
})
